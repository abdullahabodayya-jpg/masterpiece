<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Enums\UserRole;
use App\Http\Requests\Marketplace\CatalogRequest;
use App\Http\Requests\Profile\LinkRequest;
use App\Http\Requests\Profile\ProfileRequest;
use App\Http\Resources\Profile\CatalogResource;
use App\Http\Resources\Profile\LinkResource;
use App\Http\Resources\Profile\ProfessionalResource;
use App\Models\ContentType;
use App\Models\EditingTool;
use App\Models\User;
use App\Services\Marketplace\MarketplaceAccess;
use App\Services\Profile\ProfileService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ProfileController
{
    public function __construct(
        private readonly MarketplaceAccess $access,
        private readonly ProfileService $profiles,
    ) {}

    public function me(Request $request): ProfessionalResource
    {
        return new ProfessionalResource($request->user('sanctum')->load($this->profiles->relations()));
    }

    public function update(ProfileRequest $request): ProfessionalResource
    {
        return new ProfessionalResource($this->profiles->update($request->user('sanctum'), $request->validated()));
    }

    public function editor(Request $request, string $editorId): ProfessionalResource
    {
        return new ProfessionalResource($this->profiles->profiles($request->user('sanctum'), UserRole::Editor)->findOrFail($editorId));
    }

    public function creator(Request $request, string $creatorId): ProfessionalResource
    {
        return new ProfessionalResource($this->profiles->profiles($request->user('sanctum'), UserRole::Creator)->findOrFail($creatorId));
    }

    public function editors(CatalogRequest $request): JsonResponse
    {
        $query = $this->profiles->profiles($request->user('sanctum'), UserRole::Editor)
            ->select(['id', 'name', 'display_name', 'bio', 'role', 'professional_title', 'experience_level', 'years_of_experience', 'is_open_to_work']);
        $filters = $request->validated();

        if (isset($filters['content_type_id'])) {
            $query->whereHas('contentTypes', fn (Builder $types) => $types->whereKey($filters['content_type_id']));
        }
        if (isset($filters['editing_tool_id'])) {
            $query->whereHas('editingTools', fn (Builder $tools) => $tools->whereKey($filters['editing_tool_id']));
        }
        if (isset($filters['experience_level'])) {
            $query->where('experience_level', $filters['experience_level']);
        }
        if (isset($filters['open_to_work'])) {
            $query->where('is_open_to_work', $filters['open_to_work']);
        }
        if (isset($filters['professional_title'])) {
            $query->where('professional_title', 'like', $this->pattern($filters['professional_title']));
        }
        if (isset($filters['q'])) {
            $pattern = $this->pattern($filters['q']);
            $query->where(fn (Builder $users) => $users->where('name', 'like', $pattern)->orWhere('display_name', 'like', $pattern));
        }
        if (isset($filters['recorded_approval'])) {
            $this->access->recordedApproval($query);
        }

        return $request->page($query, ProfessionalResource::class);
    }

    public function contentTypes(CatalogRequest $request): JsonResponse
    {
        return $request->page(ContentType::query(), CatalogResource::class, null);
    }

    public function editingTools(CatalogRequest $request): JsonResponse
    {
        return $request->page(EditingTool::query(), CatalogResource::class, null);
    }

    public function links(CatalogRequest $request): JsonResponse
    {
        return $request->page($request->user('sanctum')->userLinks(), LinkResource::class);
    }

    public function createLink(LinkRequest $request): JsonResponse
    {
        $user = $request->user('sanctum');
        $link = $this->access->write($user, $user->role, fn (User $actor) => $actor->userLinks()->create($request->validated()));

        return LinkResource::make($link)->response()->setStatusCode(201);
    }

    public function updateLink(LinkRequest $request, string $linkId): LinkResource
    {
        $user = $request->user('sanctum');
        $link = $this->access->write($user, $user->role, function (User $actor) use ($request, $linkId) {
            $link = $actor->userLinks()->findOrFail($linkId);
            $link->update($request->validated());

            return $link;
        });

        return new LinkResource($link);
    }

    public function removeLink(Request $request, string $linkId): Response
    {
        $user = $request->user('sanctum');
        $this->access->write($user, $user->role, fn (User $actor) => $actor->userLinks()->findOrFail($linkId)->delete());

        return response()->noContent();
    }

    private function pattern(string $value): string
    {
        return '%'.addcslashes($value, '\\%_').'%';
    }
}
