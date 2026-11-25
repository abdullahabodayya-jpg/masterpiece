<?php

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Http\Requests\Marketplace\CatalogRequest;
use App\Http\Requests\Marketplace\ServiceRequest;
use App\Http\Resources\Marketplace\ServiceResource;
use App\Models\EditorService;
use App\Models\User;
use App\Services\Marketplace\MarketplaceAccess;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ServiceController
{
    public function __construct(private readonly MarketplaceAccess $access) {}

    public function index(CatalogRequest $request): JsonResponse
    {
        return $this->listing($request, $this->visible($request->user('sanctum')));
    }

    public function editor(CatalogRequest $request, string $editorId): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $this->access->people(User::query(), $viewer, UserRole::Editor)->findOrFail($editorId);

        return $this->listing($request, $this->visible($viewer)->where('editor_id', $editorId));
    }

    public function mine(CatalogRequest $request): JsonResponse
    {
        $editor = $this->access->actor($request->user('sanctum'), UserRole::Editor);

        return $this->listing($request, $editor->editorServices()->getQuery());
    }

    public function show(Request $request, string $serviceId): ServiceResource
    {
        $viewer = $request->user('sanctum');
        $query = EditorService::query()->where(function (Builder $either) use ($viewer) {
            $either->whereIn('id', $this->visible($viewer)->select('editor_services.id'));
            if ($viewer) {
                $either->orWhere('editor_id', $viewer->id);
            }
        });

        return new ServiceResource($query->findOrFail($serviceId));
    }

    public function store(ServiceRequest $request): JsonResponse
    {
        return ServiceResource::make($this->save($request))->response()->setStatusCode(201);
    }

    public function update(ServiceRequest $request, string $serviceId): ServiceResource
    {
        return new ServiceResource($this->save($request, $serviceId));
    }

    public function publish(Request $request, string $serviceId): ServiceResource
    {
        $service = $this->access->write($request->user('sanctum'), UserRole::Editor, function (User $editor) use ($serviceId) {
            $listing = $editor->editorServices()->lockForUpdate()->findOrFail($serviceId);
            abort_unless(in_array($listing->status, [ServiceStatus::Draft, ServiceStatus::Paused], true), 409);
            abort_unless($listing->content_type_id !== null && BigDecimal::of($listing->price)->isPositive(), 409);
            $this->access->requireEditor($editor, $listing->content_type_id);
            $listing->status = ServiceStatus::Active;
            $listing->save();

            return $listing;
        });

        return new ServiceResource($service);
    }

    public function pause(Request $request, string $serviceId): ServiceResource
    {
        $service = $this->access->write($request->user('sanctum'), UserRole::Editor, function (User $editor) use ($serviceId) {
            $listing = $editor->editorServices()->lockForUpdate()->findOrFail($serviceId);
            abort_unless($listing->status === ServiceStatus::Active, 409);
            $listing->status = ServiceStatus::Paused;
            $listing->save();

            return $listing;
        });

        return new ServiceResource($service);
    }

    public function destroy(Request $request, string $serviceId): Response
    {
        $this->access->write($request->user('sanctum'), UserRole::Editor,
            fn (User $editor) => $editor->editorServices()->lockForUpdate()->findOrFail($serviceId)->delete());

        return response()->noContent();
    }

    private function save(ServiceRequest $request, ?string $id = null): EditorService
    {
        return $this->access->write($request->user('sanctum'), UserRole::Editor, function (User $editor) use ($request, $id) {
            $service = $id === null ? $editor->editorServices()->make()
                : $editor->editorServices()->lockForUpdate()->findOrFail($id);
            abort_unless(in_array($service->status, [ServiceStatus::Draft, ServiceStatus::Paused], true), 409);

            $data = $request->validated();
            $service->fill($data);
            if (array_key_exists('content_type_id', $data)) {
                $service->content_type_id = $data['content_type_id'];
            }
            $service->save();

            return $service;
        });
    }

    private function visible(?User $viewer): Builder
    {
        return EditorService::query()->where('status', ServiceStatus::Active->value)
            ->whereHas('editor', fn (Builder $editors) => $this->access->eligibleEditors($editors, $viewer));
    }

    private function listing(CatalogRequest $request, Builder $query): JsonResponse
    {
        if ($contentType = $request->validated('content_type_id')) {
            $query->where('content_type_id', $contentType);
        }

        return $request->page($query, ServiceResource::class);
    }
}
