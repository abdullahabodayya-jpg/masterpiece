<?php

namespace App\Http\Controllers\Api\V1\Work;

use App\Http\Requests\Marketplace\CatalogRequest;
use App\Http\Requests\Work\DeliveryRequest;
use App\Http\Resources\Work\DeliverableResource;
use App\Http\Resources\Work\RevisionResource;
use App\Services\Work\DeliveryFiles;
use App\Services\Work\DeliveryWorkflow;
use App\Services\Work\WorkAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DeliveryController
{
    public function __construct(
        private readonly DeliveryWorkflow $workflow,
        private readonly DeliveryFiles $files,
        private readonly WorkAccess $access,
    ) {}

    public function progress(Request $request, string $workItemId): JsonResponse
    {
        return response()->json(['data' => $this->workflow->progress($request->user('sanctum'), $workItemId)]);
    }

    public function index(CatalogRequest $request, string $workItemId): JsonResponse
    {
        $work = $this->access->project($request->user('sanctum'))->findOrFail($workItemId);

        return $request->page($work->deliverables(), DeliverableResource::class, 'sequence_number');
    }

    public function store(DeliveryRequest $request, string $workItemId): JsonResponse
    {
        $delivery = $this->workflow->create($request->user('sanctum'), $workItemId, $request->validated());

        return DeliverableResource::make($delivery)->response()->setStatusCode(201);
    }

    public function show(Request $request, string $deliverableId): DeliverableResource
    {
        return new DeliverableResource($this->workflow->deliveries($request->user('sanctum'))->findOrFail($deliverableId));
    }

    public function update(DeliveryRequest $request, string $deliverableId): DeliverableResource
    {
        return new DeliverableResource($this->workflow->update($request->user('sanctum'), $deliverableId, $request->validated()));
    }

    public function submit(DeliveryRequest $request, string $deliverableId): DeliverableResource
    {
        return new DeliverableResource($this->workflow->submit($request->user('sanctum'), $deliverableId, $request->file('file')));
    }

    public function document(Request $request, string $deliverableId): StreamedResponse
    {
        return $this->files->download($this->workflow->deliveries($request->user('sanctum'))->findOrFail($deliverableId));
    }

    public function review(DeliveryRequest $request, string $deliverableId): DeliverableResource
    {
        return new DeliverableResource($this->workflow->review($request->user('sanctum'), $deliverableId, $request->validated(), false));
    }

    public function accept(DeliveryRequest $request, string $deliverableId): DeliverableResource
    {
        return new DeliverableResource($this->workflow->review($request->user('sanctum'), $deliverableId, $request->validated(), true));
    }

    public function requestRevision(DeliveryRequest $request, string $deliverableId): JsonResponse
    {
        $revision = $this->workflow->requestRevision($request->user('sanctum'), $deliverableId, $request->validated());

        return RevisionResource::make($revision)->response()->setStatusCode(201);
    }

    public function revisions(CatalogRequest $request, string $deliverableId): JsonResponse
    {
        $delivery = $this->workflow->deliveries($request->user('sanctum'))->findOrFail($deliverableId);

        return $request->page($delivery->deliverableRevisions(), RevisionResource::class, 'requested_at');
    }

    public function revision(Request $request, string $revisionId): RevisionResource
    {
        return new RevisionResource($this->workflow->revisions($request->user('sanctum'))->findOrFail($revisionId));
    }

    public function begin(Request $request, string $revisionId): RevisionResource
    {
        return new RevisionResource($this->workflow->changeRevision($request->user('sanctum'), $revisionId, false));
    }

    public function cancel(Request $request, string $revisionId): RevisionResource
    {
        return new RevisionResource($this->workflow->changeRevision($request->user('sanctum'), $revisionId, true));
    }
}
