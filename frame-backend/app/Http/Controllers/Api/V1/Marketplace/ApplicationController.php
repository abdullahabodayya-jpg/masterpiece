<?php

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Http\Requests\Marketplace\ApplicationRequest;
use App\Http\Requests\Marketplace\CatalogRequest;
use App\Http\Resources\Marketplace\ApplicationResource;
use App\Http\Resources\Marketplace\WorkResource;
use App\Services\Marketplace\ApplicationService;
use App\Services\Marketplace\JobService;
use App\Services\Marketplace\MarketplaceAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ApplicationController
{
    public function __construct(
        private readonly ApplicationService $applications,
        private readonly MarketplaceAccess $access,
        private readonly JobService $jobs,
    ) {}

    public function mine(CatalogRequest $request): JsonResponse
    {
        $editor = $this->access->actor($request->user('sanctum'), UserRole::Editor);

        return $request->page($this->applications->visible($editor), ApplicationResource::class);
    }

    public function job(CatalogRequest $request, string $workItemId): JsonResponse
    {
        $creator = $this->access->actor($request->user('sanctum'), UserRole::Creator);
        $this->jobs->jobs()->where('creator_id', $creator->id)->findOrFail($workItemId);

        return $request->page($this->applications->visible($creator)->where('work_item_id', $workItemId), ApplicationResource::class);
    }

    public function show(Request $request, string $applicationId): ApplicationResource
    {
        return new ApplicationResource($this->applications->visible($request->user('sanctum'))->findOrFail($applicationId));
    }

    public function store(ApplicationRequest $request, string $workItemId): JsonResponse
    {
        return ApplicationResource::make($this->applications->submit($request->user('sanctum'), $workItemId, $request->validated()))
            ->response()->setStatusCode(201);
    }

    public function update(ApplicationRequest $request, string $applicationId): ApplicationResource
    {
        return new ApplicationResource($this->applications->revise($request->user('sanctum'), $applicationId, $request->validated()));
    }

    public function shortlist(Request $request, string $applicationId): ApplicationResource
    {
        return new ApplicationResource($this->applications->shortlist($request->user('sanctum'), $applicationId));
    }

    public function select(Request $request, string $applicationId): WorkResource
    {
        return new WorkResource($this->applications->select($request->user('sanctum'), $applicationId));
    }

    public function withdraw(Request $request, string $applicationId): ApplicationResource
    {
        return new ApplicationResource($this->applications->transition($request->user('sanctum'), $applicationId, ApplicationStatus::Withdrawn));
    }

    public function reject(Request $request, string $applicationId): ApplicationResource
    {
        return new ApplicationResource($this->applications->transition($request->user('sanctum'), $applicationId, ApplicationStatus::Rejected));
    }
}
