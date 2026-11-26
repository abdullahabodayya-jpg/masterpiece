<?php

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Enums\WorkStatus;
use App\Http\Requests\Marketplace\CatalogRequest;
use App\Http\Requests\Marketplace\ServiceIntakeRequest;
use App\Http\Resources\Marketplace\WorkResource;
use App\Services\Marketplace\ServiceIntake;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServiceIntakeController
{
    public function __construct(private readonly ServiceIntake $intake) {}

    public function index(CatalogRequest $request): JsonResponse
    {
        return $request->page($this->intake->visible($request->user('sanctum')), WorkResource::class);
    }

    public function store(ServiceIntakeRequest $request, string $serviceId): JsonResponse
    {
        return WorkResource::make($this->intake->submit($request->user('sanctum'), $serviceId, $request->validated()))
            ->response()->setStatusCode(201);
    }

    public function accept(Request $request, string $workItemId): WorkResource
    {
        $fingerprint = $request->validate(['terms_fingerprint' => ['sometimes', 'required', 'string', 'size:64']])['terms_fingerprint'] ?? '';

        return new WorkResource($this->intake->transition($request->user('sanctum'), $workItemId, WorkStatus::PendingAgreement, $fingerprint));
    }

    public function reject(Request $request, string $workItemId): WorkResource
    {
        return new WorkResource($this->intake->transition($request->user('sanctum'), $workItemId, WorkStatus::Closed));
    }

    public function cancel(Request $request, string $workItemId): WorkResource
    {
        return new WorkResource($this->intake->transition($request->user('sanctum'), $workItemId, WorkStatus::Cancelled));
    }
}
