<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Enums\UserRole;
use App\Http\Requests\Marketplace\CatalogRequest;
use App\Http\Requests\Profile\PortfolioRequest;
use App\Http\Resources\Profile\PortfolioResource;
use App\Models\PortfolioProject;
use App\Models\User;
use App\Services\Marketplace\MarketplaceAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class PortfolioController
{
    public function __construct(private readonly MarketplaceAccess $access) {}

    public function index(CatalogRequest $request, string $editorId): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $this->access->people(User::query(), $viewer, UserRole::Editor)->findOrFail($editorId);

        return $request->page($this->visible($viewer)->where('editor_id', $editorId), PortfolioResource::class);
    }

    public function show(Request $request, string $editorId, string $portfolioId): PortfolioResource
    {
        return new PortfolioResource($this->visible($request->user('sanctum'))->where('editor_id', $editorId)->findOrFail($portfolioId));
    }

    public function mine(CatalogRequest $request): JsonResponse
    {
        $actor = $this->access->actor($request->user('sanctum'), UserRole::Editor);

        return $request->page($actor->portfolioProjects(), PortfolioResource::class);
    }

    public function store(PortfolioRequest $request): JsonResponse
    {
        $item = $this->save($request);

        return PortfolioResource::make($item)->response()->setStatusCode(201);
    }

    public function update(PortfolioRequest $request, string $portfolioId): PortfolioResource
    {
        return new PortfolioResource($this->save($request, $portfolioId));
    }

    public function destroy(Request $request, string $portfolioId): Response
    {
        $this->access->write($request->user('sanctum'), UserRole::Editor,
            fn (User $editor) => $editor->portfolioProjects()->findOrFail($portfolioId)->delete());

        return response()->noContent();
    }

    private function save(PortfolioRequest $request, ?string $id = null): PortfolioProject
    {
        return $this->access->write($request->user('sanctum'), UserRole::Editor, function (User $editor) use ($request, $id) {
            $item = $id === null ? $editor->portfolioProjects()->make() : $editor->portfolioProjects()->findOrFail($id);
            $data = $request->validated();
            $item->fill($data);
            if (isset($data['content_type_id'])) {
                $item->content_type_id = $data['content_type_id'];
            }
            if (isset($data['external_url'])) {
                $item->sample_type = 'external';
            }
            $item->save();

            return $item;
        });
    }

    private function visible(?User $viewer): Builder
    {
        return PortfolioProject::query()->where('sample_type', 'external')->whereNotNull('external_url')
            ->whereHas('editor', fn (Builder $users) => $this->access->people($users, $viewer, UserRole::Editor));
    }
}
