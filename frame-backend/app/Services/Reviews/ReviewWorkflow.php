<?php

namespace App\Services\Reviews;

use App\Enums\UserRole;
use App\Enums\WorkStatus;
use App\Models\Review;
use App\Models\User;
use App\Models\WorkItem;
use App\Services\Marketplace\MarketplaceAccess;
use App\Services\Payments\PaymentAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;

final class ReviewWorkflow
{
    public function __construct(
        private readonly PaymentAccess $payments,
        private readonly MarketplaceAccess $accounts,
    ) {}

    public function project(User $actor, string $id): WorkItem
    {
        return $this->payments->participant($actor, $id);
    }

    public function reviews(?User $participant = null): Builder
    {
        return Review::query()->whereBetween('rating', [1, 5])->whereHas('workItem', function (Builder $work) use ($participant) {
            if ($participant) {
                $work->where($this->participantColumn($participant), $participant->id);
            }

            $this->qualifyingWork($work)->where(function (Builder $pair) {
                $pair->where(fn (Builder $direction) => $direction
                    ->whereColumn('reviews.reviewer_user_id', 'work_items.creator_id')
                    ->whereColumn('reviews.reviewed_user_id', 'work_items.editor_id'))
                    ->orWhere(fn (Builder $direction) => $direction
                        ->whereColumn('reviews.reviewer_user_id', 'work_items.editor_id')
                        ->whereColumn('reviews.reviewed_user_id', 'work_items.creator_id'));
            });
        });
    }

    public function publicReviews(UserRole $role, string $id, ?User $viewer): Builder
    {
        $subject = $this->accounts->people(User::query(), $viewer, $role)->findOrFail($id);
        $reviewerRole = $role === UserRole::Editor ? UserRole::Creator : UserRole::Editor;

        return $this->reviews()->where('reviewed_user_id', $subject->id)
            ->whereHas('reviewer', fn (Builder $authors) => $this->accounts->people($authors, $viewer, $reviewerRole));
    }

    public function requireQualifyingWork(WorkItem $work): void
    {
        abort_unless($this->qualifyingWork(WorkItem::query())->whereKey($work->id)->exists(), 409);
    }

    public function status(User $actor, WorkItem $work): array
    {
        $canReview = $this->reviewableWork($actor)->whereKey($work->id)->exists();
        $reviewed = $this->existing($work, $actor)->exists();

        return ['eligible' => $canReview, 'has_reviewed' => $reviewed, 'can_review' => $canReview && ! $reviewed];
    }

    public function publish(User $actor, string $id, array $input): Review
    {
        $candidate = $this->project($actor, $id);

        return $this->payments->locked($candidate, $actor, function (WorkItem $work, array $users, User $current) use ($actor, $input) {
            $this->payments->verifyActor($work, $current, $actor->role);
            $this->payments->activePair($work, $users);
            abort_unless($this->reviewableWork($current)->whereKey($work->id)->exists(), 409);
            abort_if($this->existing($work, $current)->exists(), 409);

            $otherId = $current->id === $work->creator_id ? $work->editor_id : $work->creator_id;
            $review = $work->reviews()->make($input);
            $review->reviewer()->associate($current);
            $review->reviewed()->associate($users[$otherId]);
            try {
                $review->save();
            } catch (UniqueConstraintViolationException) {
                abort(409);
            }

            return $review;
        });
    }

    private function qualifyingWork(Builder $work): Builder
    {
        return $this->payments->fundedStartedWork($work)
            ->where('work_items.status', WorkStatus::Completed->value)
            ->whereColumn('work_items.creator_id', '<>', 'work_items.editor_id')
            ->whereColumn('work_items.agreed_at', '<=', 'work_items.started_at')
            ->whereColumn('work_items.started_at', '<=', 'work_items.completed_at')
            ->where('work_items.completed_at', '<=', now())
            ->where('work_items.agreed_price', '>', 0);
    }

    private function reviewableWork(User $actor): Builder
    {
        return $this->qualifyingWork(WorkItem::query())->where($this->participantColumn($actor), $actor->id)
            ->whereHas('creator', fn (Builder $users) => $this->accounts->people($users, $actor, UserRole::Creator))
            ->whereHas('editor', fn (Builder $users) => $this->accounts->people($users, $actor, UserRole::Editor));
    }

    private function participantColumn(User $actor): string
    {
        return $actor->role === UserRole::Creator ? 'work_items.creator_id' : 'work_items.editor_id';
    }

    private function existing(WorkItem $work, User $actor): Builder
    {
        $otherId = $actor->id === $work->creator_id ? $work->editor_id : $work->creator_id;

        return $work->reviews()->getQuery()->withTrashed()->where('reviewer_user_id', $actor->id)->where('reviewed_user_id', $otherId);
    }
}
