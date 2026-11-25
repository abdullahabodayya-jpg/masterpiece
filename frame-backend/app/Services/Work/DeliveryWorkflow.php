<?php

namespace App\Services\Work;

use App\Enums\DeliverableStatus;
use App\Enums\RevisionStatus;
use App\Enums\UserRole;
use App\Enums\WorkStatus;
use App\Models\Deliverable;
use App\Models\DeliverableRevision;
use App\Models\User;
use App\Models\WorkItem;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Throwable;

final class DeliveryWorkflow
{
    public function __construct(
        private readonly WorkAccess $access,
        private readonly DeliveryFiles $files,
    ) {}

    public function deliveries(User $actor): Builder
    {
        return Deliverable::query()->whereIn('work_item_id', $this->access->project($actor)->select('id'));
    }

    public function revisions(User $actor): Builder
    {
        return DeliverableRevision::query()->whereIn('deliverable_id', $this->deliveries($actor)->select('id'));
    }

    public function create(User $actor, string $workId, array $metadata): Deliverable
    {
        return $this->access->write($actor, $workId, UserRole::Creator, function (WorkItem $work) use ($metadata) {
            abort_unless($work->status === WorkStatus::InProgress, 409);
            $sequence = (int) $work->deliverables()->max('sequence_number') + 1;
            abort_if($sequence > 2147483647, 409);
            $delivery = $work->deliverables()->make($metadata);
            $delivery->sequence_number = $sequence;
            $delivery->save();

            return $delivery;
        });
    }

    public function update(User $actor, string $id, array $metadata): Deliverable
    {
        return $this->mutateDelivery($actor, $id, UserRole::Creator, function (Deliverable $delivery, User $user, WorkItem $work) use ($metadata) {
            abort_unless($work->status === WorkStatus::InProgress && $delivery->status === DeliverableStatus::Pending
                && $delivery->submitted_at === null && $delivery->storage_key === null, 409);
            $delivery->update($metadata);

            return $delivery;
        });
    }

    public function submit(User $actor, string $id, UploadedFile $file): Deliverable
    {
        abort_unless($actor->role === UserRole::Editor, 403);
        $candidate = $this->deliveries($actor)->with('workItem')->findOrFail($id);
        $this->access->requireDeliveryState($candidate->workItem);
        abort_unless($candidate->status === DeliverableStatus::Pending
            && $candidate->submitted_at === null && $candidate->storage_key === null, 409);
        $path = $this->files->store($file);

        try {
            return $this->mutateDelivery($actor, $id, UserRole::Editor, function (Deliverable $delivery) use ($path) {
                abort_unless($delivery->status === DeliverableStatus::Pending
                    && $delivery->submitted_at === null && $delivery->storage_key === null, 409);
                $delivery->storage_key = $path;
                $delivery->file_name = basename($path);
                $delivery->submitted_at = now();
                $delivery->status = DeliverableStatus::Submitted;
                $delivery->save();

                return $delivery;
            });
        } catch (Throwable $exception) {
            $this->files->discard($path);
            throw $exception;
        }
    }

    public function review(User $actor, string $id, array $input, bool $accept): Deliverable
    {
        return $this->mutateDelivery($actor, $id, UserRole::Creator, function (Deliverable $delivery) use ($input, $accept) {
            $this->reviewable($delivery, $input['submission_token']);
            abort_if($this->unresolved($delivery->deliverableRevisions()->getQuery())->exists(), 409);
            abort_if(! $accept && $delivery->status !== DeliverableStatus::Submitted, 409);
            $delivery->status = $accept ? DeliverableStatus::Accepted : DeliverableStatus::InReview;
            $delivery->reviewed_at = now();
            if (array_key_exists('review_notes', $input)) {
                $delivery->review_notes = $input['review_notes'];
            }
            $delivery->save();

            return $delivery;
        });
    }

    public function requestRevision(User $actor, string $id, array $input): DeliverableRevision
    {
        return $this->mutateDelivery($actor, $id, UserRole::Creator, function (Deliverable $delivery, User $creator) use ($input) {
            $this->reviewable($delivery, $input['submission_token']);
            abort_if($this->unresolved($delivery->deliverableRevisions()->getQuery())->exists(), 409);

            $revision = $delivery->deliverableRevisions()->make(['reason' => $input['reason'], 'details' => $input['details'] ?? null]);
            $revision->requestedBy()->associate($creator);
            $revision->requested_at = now();
            $revision->save();
            $delivery->status = DeliverableStatus::InReview;
            $delivery->reviewed_at = now();
            $delivery->save();

            return $revision;
        });
    }

    public function changeRevision(User $actor, string $id, bool $cancel): DeliverableRevision
    {
        $candidate = $this->revisions($actor)->findOrFail($id);
        $role = $cancel ? UserRole::Creator : UserRole::Editor;

        return $this->mutateDelivery($actor, $candidate->deliverable_id, $role, function (Deliverable $delivery) use ($id, $cancel) {
            abort_unless($delivery->status === DeliverableStatus::InReview, 409);
            $revision = $delivery->deliverableRevisions()->lockForUpdate()->findOrFail($id);

            if ($cancel) {
                abort_unless(in_array($revision->status, [RevisionStatus::Requested, RevisionStatus::InProgress], true), 409);
                $revision->status = RevisionStatus::Cancelled;
                $revision->resolved_at = now();
            } else {
                abort_unless($revision->status === RevisionStatus::Requested, 409);
                $revision->status = RevisionStatus::InProgress;
            }
            $revision->save();

            return $revision;
        });
    }

    public function progress(User $actor, string $workId): array
    {
        $work = $this->access->project($actor)->findOrFail($workId);
        $counts = $work->deliverables()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $total = (int) $counts->sum();
        $accepted = (int) $counts->get(DeliverableStatus::Accepted->value, 0);
        $submitted = (int) $counts->get(DeliverableStatus::Submitted->value, 0)
            + (int) $counts->get(DeliverableStatus::InReview->value, 0);
        $unresolved = $this->unresolved(DeliverableRevision::query())
            ->whereIn('deliverable_id', $work->deliverables()->select('id')->getQuery())->count();

        return [
            'work_item_id' => $work->id,
            'type' => $work->type->value,
            'status' => $work->status->value,
            'total_deliverables' => $total,
            'submitted_deliverables' => $submitted,
            'accepted_deliverables' => $accepted,
            'outstanding_deliverables' => $total - $accepted,
            'unresolved_revisions' => $unresolved,
            'delivery_ready' => $total > 0 && $accepted === $total && $unresolved === 0,
        ];
    }

    private function mutateDelivery(User $actor, string $id, UserRole $role, Closure $change): mixed
    {
        $candidate = $this->deliveries($actor)->findOrFail($id);

        return $this->access->write($actor, $candidate->work_item_id, $role, function (WorkItem $work, User $current) use ($id, $change) {
            $delivery = $work->deliverables()->lockForUpdate()->findOrFail($id);

            return $change($delivery, $current, $work);
        });
    }

    private function reviewable(Deliverable $delivery, string $token): void
    {
        abort_unless(in_array($delivery->status, [DeliverableStatus::Submitted, DeliverableStatus::InReview], true)
            && hash_equals(DeliveryFiles::token($delivery) ?? '', $token)
            && $this->files->exists($delivery), 409);
    }

    private function unresolved(Builder $revisions): Builder
    {
        return $revisions->whereIn('status', [RevisionStatus::Requested->value, RevisionStatus::InProgress->value]);
    }
}
