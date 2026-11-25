<?php

namespace App\Services\Marketplace;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Enums\WorkType;
use App\Models\JobApplication;
use App\Models\User;
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

    public function transition(User $actor, string $id, ApplicationStatus $next): JobApplication
    {
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
