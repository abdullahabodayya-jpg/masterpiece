<?php

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Http\Requests\Marketplace\CatalogRequest;
use App\Http\Resources\Marketplace\WorkResource;
use App\Services\Work\WorkAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkController
{
    public function __construct(private readonly WorkAccess $access) {}

    public function index(CatalogRequest $request): JsonResponse
    {
        return $request->page($this->access->participant($request->user('sanctum')), WorkResource::class);
    }

    public function show(Request $request, string $workItemId): WorkResource
    {
        return new WorkResource($this->access->participant($request->user('sanctum'))->findOrFail($workItemId));
    }
}
