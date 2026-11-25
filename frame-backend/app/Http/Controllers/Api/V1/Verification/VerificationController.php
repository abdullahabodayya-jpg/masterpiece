<?php

namespace App\Http\Controllers\Api\V1\Verification;

use App\Enums\VerificationStatus;
use App\Http\Requests\Marketplace\CatalogRequest;
use App\Http\Requests\Verification\VerificationDecisionRequest;
use App\Http\Requests\Verification\VerificationUploadRequest;
use App\Http\Resources\Verification\VerificationResource;
use App\Models\EditorVerificationRequest;
use App\Services\Verification\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class VerificationController
{
    public function __construct(private readonly VerificationService $verification) {}

    public function store(VerificationUploadRequest $request): JsonResponse
    {
        $record = $this->verification->submit($request->user('sanctum'), $request->validated('document_type'), $request->file('document'));

        return VerificationResource::make($record)->response()->setStatusCode(201);
    }

    public function index(CatalogRequest $request): JsonResponse
    {
        $editor = $request->user('sanctum');
        $this->verification->editor($editor);

        return $request->page($editor->editorVerificationRequests(), VerificationResource::class);
    }

    public function status(Request $request): JsonResponse
    {
        $editor = $request->user('sanctum');
        $this->verification->editor($editor);

        return response()->json(['data' => [
            'status' => $editor->editorVerificationRequests()->orderByDesc('created_at')->orderByDesc('id')->first(['status'])?->status->value,
            'latest_decision' => $this->verification->current($editor),
            'account_status' => $editor->status->value,
        ]]);
    }

    public function show(Request $request, string $verificationId): VerificationResource
    {
        return new VerificationResource($this->owned($request, $verificationId));
    }

    public function document(Request $request, string $verificationId): StreamedResponse
    {
        return $this->verification->download($this->owned($request, $verificationId));
    }

    public function queue(CatalogRequest $request): JsonResponse
    {
        $this->verification->reviewer($request->user('sanctum'));

        return $request->page(EditorVerificationRequest::query(), VerificationResource::class);
    }

    public function review(Request $request, string $verificationId): VerificationResource
    {
        return new VerificationResource($this->forReview($request, $verificationId));
    }

    public function reviewDocument(Request $request, string $verificationId): StreamedResponse
    {
        return $this->verification->download($this->forReview($request, $verificationId));
    }

    public function decide(VerificationDecisionRequest $request, string $verificationId): VerificationResource
    {
        $decision = $request->validated('decision') === 'approve' ? VerificationStatus::Approved : VerificationStatus::Rejected;

        return new VerificationResource($this->verification->decide(
            $request->user('sanctum'), $verificationId, $decision, $request->validated('reason'),
        ));
    }

    private function owned(Request $request, string $id): EditorVerificationRequest
    {
        $editor = $request->user('sanctum');
        $this->verification->editor($editor);

        return $editor->editorVerificationRequests()->findOrFail($id);
    }

    private function forReview(Request $request, string $id): EditorVerificationRequest
    {
        $this->verification->reviewer($request->user('sanctum'));

        return EditorVerificationRequest::query()->findOrFail($id);
    }
}
