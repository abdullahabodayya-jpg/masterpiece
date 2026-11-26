<?php

namespace App\Services\Marketplace;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Enums\WorkStatus;
use App\Enums\WorkType;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\WorkItem;
use App\Services\Payments\Agreement;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Builder;

final class ApplicationService
{
    public function __construct(private readonly MarketplaceAccess $access) {}

    public function visible(User $viewer): Builder
    {
        $query = JobApplication::query()->whereHas('workItem', function (Builder $jobs) use ($viewer) {
            $jobs->where('type', WorkType::Job->value);
            if ($viewer->role === UserRole::Creator) {
                $jobs->where('creator_id', $viewer->id);
            }
        });

        if ($viewer->role === UserRole::Editor) {
            $query->where('editor_id', $viewer->id);
        } else {
            $query->whereHas('editor', fn (Builder $editors) => $this->access->unblocked($editors, $viewer));
        }

        return $query;
    }

    public function submit(User $actor, string $jobId, array $proposal): JobApplication
    {
        $this->access->actor($actor, UserRole::Editor);
        $candidate = WorkItem::query()->where('type', WorkType::Job->value)->findOrFail($jobId);

        return $this->access->lockAccounts($actor, [$candidate->creator_id], function (User $editor, array $users) use ($candidate, $proposal) {
            $this->access->actor($editor, UserRole::Editor);
            abort_if($editor->id === $candidate->creator_id, 403);
            $creator = $this->access->actor($users[$candidate->creator_id], UserRole::Creator);
            $job = $this->openJob($candidate->id);
            $this->access->requireEditor($editor, $job->content_type_id, $creator);
            abort_if($job->jobApplications()->where('editor_id', $editor->id)->exists(), 409);

            $application = $job->jobApplications()->make($proposal);
            $application->editor()->associate($editor);
            $application->save();

            return $application;
        });
    }

    public function revise(User $actor, string $id, array $proposal): JobApplication
    {
        return $this->onOpenApplication($actor, $id, UserRole::Editor, function (JobApplication $application) use ($proposal) {
            abort_unless($application->status === ApplicationStatus::Pending, 409);
            $application->update($proposal);

            return $application;
        });
    }

    public function shortlist(User $actor, string $id): JobApplication
    {
        return $this->onOpenApplication($actor, $id, UserRole::Creator, function (JobApplication $application) {
            abort_unless($application->status === ApplicationStatus::Pending, 409);
            $application->status = ApplicationStatus::Shortlisted;
            $application->save();

            return $application;
        });
    }

    public function select(User $actor, string $id, string $fingerprint): WorkItem
    {
        return $this->onOpenApplication($actor, $id, UserRole::Creator,
            function (JobApplication $application, WorkItem $job) use ($fingerprint): WorkItem {
                abort_unless(in_array($application->status, [ApplicationStatus::Pending, ApplicationStatus::Shortlisted], true)
                    && $application->proposed_price !== null && BigDecimal::of($application->proposed_price)->isPositive(), 409);
                abort_if($job->jobApplications()->where('status', ApplicationStatus::Accepted->value)->exists(), 409);

                abort_unless(hash_equals(Agreement::selection($job, $application), $fingerprint), 409);

                $application->status = ApplicationStatus::Accepted;
                $application->save();
                $job->editor_id = $application->editor_id;
                $job->agreed_price = $application->proposed_price;
                $job->status = WorkStatus::PendingAgreement;
                $job->save();

                return $job;
            },
        );
    }

    private function onOpenApplication(User $actor, string $id, UserRole $role, \Closure $action): JobApplication|WorkItem
    {
        $this->access->actor($actor, $role);
        $candidate = $this->visible($actor)->with('workItem:id,creator_id')->findOrFail($id);
        $creatorId = $candidate->workItem->creator_id;

        // Lock both accounts in UUID order, then the work item, then the application.
        return $this->access->lockAccounts($actor, [$creatorId, $candidate->editor_id], function (User $current, array $users) use ($role, $creatorId, $candidate, $action) {
            $this->access->actor($current, $role);
            $creator = $this->access->actor($users[$creatorId], UserRole::Creator);
            $job = $this->openJob($candidate->work_item_id);
            $application = $this->visible($current)->where('work_item_id', $job->id)->lockForUpdate()->findOrFail($candidate->id);
            $this->access->requireEditor($users[$application->editor_id], $job->content_type_id, $creator);

            return $action($application, $job);
        });
    }

    private function openJob(string $id): WorkItem
    {
        $job = WorkItem::query()->where('type', WorkType::Job->value)->lockForUpdate()->findOrFail($id);
        abort_unless($job->status === WorkStatus::Open && $job->editor_id === null && $job->deadline?->isFuture(), 409);

        return $job;
    }

    public function transition(User $actor, string $id, ApplicationStatus $next): JobApplication
    {
        abort_unless(in_array($next, [ApplicationStatus::Withdrawn, ApplicationStatus::Rejected], true), 409);
        $role = $next === ApplicationStatus::Withdrawn ? UserRole::Editor : UserRole::Creator;

        return $this->access->write($actor, $role, function (User $user) use ($id, $next) {
            $application = $this->visible($user)->lockForUpdate()->findOrFail($id);
            abort_unless(in_array($application->status, [ApplicationStatus::Pending, ApplicationStatus::Shortlisted], true), 409);
            $application->status = $next;
            $application->save();

            return $application;
        });
    }
}
