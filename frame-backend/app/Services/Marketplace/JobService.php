<?php

namespace App\Services\Marketplace;

use App\Enums\UserRole;
use App\Enums\WorkStatus;
use App\Enums\WorkType;
use App\Models\User;
use App\Models\WorkItem;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

final class JobService
{
    public function __construct(private readonly MarketplaceAccess $access) {}

    public function jobs(): Builder
    {
        return WorkItem::query()->where('type', WorkType::Job->value);
    }

    public function open(Builder $jobs, ?User $viewer): Builder
    {
        return $jobs->where('status', WorkStatus::Open->value)->whereNull('editor_id')->where('deadline', '>', now())
            ->whereHas('creator', fn (Builder $users) => $this->access->people($users, $viewer, UserRole::Creator));
    }

    public function save(User $actor, ?string $id, array $data): WorkItem
    {
        return $this->access->write($actor, UserRole::Creator, function (User $creator) use ($id, $data) {
            $job = $id === null ? $creator->createdWorkItems()->make() : $this->locked($creator, $id);
            abort_unless($job->status === WorkStatus::Draft && $job->editor_id === null, 409);
            $job->fill($data);

            foreach (['content_type_id', 'editing_tool_id', 'currency'] as $field) {
                if (array_key_exists($field, $data)) {
                    $job->{$field} = $data[$field];
                }
            }

            if ($job->budget_min !== null && $job->budget_max !== null
                && BigDecimal::of($job->budget_min)->isGreaterThan($job->budget_max)) {
                throw ValidationException::withMessages(['budget_max' => 'The maximum must be at least the minimum.']);
            }

            $job->save();

            return $job;
        });
    }

    public function transition(User $actor, string $id, WorkStatus $target): WorkItem
    {
        return $this->access->write($actor, UserRole::Creator, function (User $creator) use ($id, $target) {
            $job = $this->locked($creator, $id);
            $allowed = match ($target) {
                WorkStatus::Open => $job->status === WorkStatus::Draft
                    && $job->deadline?->isFuture() && $job->contentType()->exists()
                    && ($job->editing_tool_id === null || $job->editingTool()->exists()),
                WorkStatus::Closed => in_array($job->status, [WorkStatus::Open, WorkStatus::Paused], true),
                WorkStatus::Cancelled => in_array($job->status, [WorkStatus::Draft, WorkStatus::Open, WorkStatus::Paused], true),
                default => false,
            };
            abort_unless($allowed && $job->editor_id === null, 409);
            $job->status = $target;
            $job->save();

            return $job;
        });
    }

    private function locked(User $creator, string $id): WorkItem
    {
        return $this->jobs()->where('creator_id', $creator->id)->lockForUpdate()->findOrFail($id);
    }
}
