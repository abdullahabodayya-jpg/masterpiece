<?php

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Enums\UserRole;
use App\Http\Requests\Marketplace\CatalogRequest;
use App\Http\Resources\Marketplace\WorkResource;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkController
{
    public function index(CatalogRequest $request): JsonResponse
    {
        return $request->page($this->participant($request->user('sanctum')), WorkResource::class);
    }

    public function show(Request $request, string $workItemId): WorkResource
    {
        return new WorkResource($this->participant($request->user('sanctum'))->findOrFail($workItemId));
    }

    private function participant(User $user): Builder
    {
        $field = $user->role === UserRole::Creator ? 'creator_id' : 'editor_id';

        return WorkItem::query()->where($field, $user->id);
    }
}
