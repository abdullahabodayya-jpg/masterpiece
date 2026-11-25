<?php

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Enums\UserRole;
use App\Enums\WorkStatus;
use App\Http\Requests\Marketplace\CatalogRequest;
use App\Http\Requests\Marketplace\JobRequest;
use App\Http\Resources\Marketplace\WorkResource;
use App\Services\Marketplace\JobService;
use App\Services\Marketplace\MarketplaceAccess;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class JobController
{
    public function __construct(
        private readonly JobService $jobs,
        private readonly MarketplaceAccess $access,
    ) {}

    public function index(CatalogRequest $request): JsonResponse
    {
        $query = $this->jobs->open($this->jobs->jobs(), $request->user('sanctum'));
        $filters = $request->validated();
        foreach (['content_type_id', 'editing_tool_id'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (isset($filters['budget_at_least'], $filters['budget_at_most'])
            && BigDecimal::of($filters['budget_at_least'])->isGreaterThan($filters['budget_at_most'])) {
            abort(400);
        }
        if (isset($filters['budget_at_least'])) {
            $query->where('budget_max', '>=', $filters['budget_at_least']);
        }
        if (isset($filters['budget_at_most'])) {
            $query->where('budget_min', '<=', $filters['budget_at_most']);
        }
        if (isset($filters['deadline_before'])) {
            $query->where('deadline', '<=', $request->date('deadline_before'));
        }

        return $request->page($query, WorkResource::class);
    }

    public function mine(CatalogRequest $request): JsonResponse
    {
        $creator = $this->access->actor($request->user('sanctum'), UserRole::Creator);

        return $request->page($this->jobs->jobs()->where('creator_id', $creator->id), WorkResource::class);
    }

    public function show(Request $request, string $workItemId): WorkResource
    {
        $viewer = $request->user('sanctum');
        $job = $this->jobs->jobs()->where(function (Builder $scope) use ($viewer) {
            $scope->where(fn (Builder $open) => $this->jobs->open($open, $viewer));
            if ($viewer) {
                $scope->orWhere('creator_id', $viewer->id);
            }
        })->findOrFail($workItemId);

        return new WorkResource($job);
    }

    public function store(JobRequest $request): JsonResponse
    {
        return WorkResource::make($this->jobs->save($request->user('sanctum'), null, $request->validated()))
            ->response()->setStatusCode(201);
    }

    public function update(JobRequest $request, string $workItemId): WorkResource
    {
        return new WorkResource($this->jobs->save($request->user('sanctum'), $workItemId, $request->validated()));
    }

    public function publish(Request $request, string $workItemId): WorkResource
    {
        return new WorkResource($this->jobs->transition($request->user('sanctum'), $workItemId, WorkStatus::Open));
    }

    public function close(Request $request, string $workItemId): WorkResource
    {
        return new WorkResource($this->jobs->transition($request->user('sanctum'), $workItemId, WorkStatus::Closed));
    }

    public function cancel(Request $request, string $workItemId): WorkResource
    {
        return new WorkResource($this->jobs->transition($request->user('sanctum'), $workItemId, WorkStatus::Cancelled));
    }
}
