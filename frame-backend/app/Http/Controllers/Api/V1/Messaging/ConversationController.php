<?php

namespace App\Http\Controllers\Api\V1\Messaging;

use App\Http\Requests\Marketplace\CatalogRequest;
use App\Http\Requests\Messaging\MessagingRequest;
use App\Http\Resources\Messaging\ConversationResource;
use App\Http\Resources\Messaging\MessageResource;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use App\Services\Messaging\ConversationAccess;
use App\Services\Messaging\ConversationWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ConversationController
{
    public function __construct(
        private readonly ConversationAccess $access,
        private readonly ConversationWorkflow $workflow,
    ) {}

    public function index(CatalogRequest $request): JsonResponse
    {
        $query = $this->workflow->summaries($request->user('sanctum'))
            ->orderBy('activity_at', $request->validated('sort') === 'oldest' ? 'asc' : 'desc');

        return $request->page($query, ConversationResource::class, null);
    }

    public function show(Request $request, string $conversationId): ConversationResource
    {
        return new ConversationResource($this->workflow->summaries($request->user('sanctum'))->findOrFail($conversationId));
    }

    public function direct(MessagingRequest $request): JsonResponse
    {
        $actor = $request->user('sanctum');
        $conversation = $this->workflow->direct($actor, $request->validated('recipient_id'), $request->validated('title'));

        return $this->opened($actor, $conversation);
    }

    public function project(MessagingRequest $request, string $workItemId): JsonResponse
    {
        $actor = $request->user('sanctum');
        $conversation = $this->workflow->project($actor, $workItemId, $request->validated('title'));

        return $this->opened($actor, $conversation);
    }

    public function messages(CatalogRequest $request, string $conversationId): JsonResponse
    {
        $visible = $this->access->visible($request->user('sanctum'));
        $conversation = (clone $visible)->findOrFail($conversationId);
        $messages = ConversationMessage::query()->where('conversation_id', $conversation->id)
            ->whereIn('conversation_id', $visible->select('id'));

        return $request->page($messages, MessageResource::class);
    }

    public function send(MessagingRequest $request, string $conversationId): JsonResponse
    {
        $message = $this->workflow->send($request->user('sanctum'), $conversationId, $request->validated('message_body'));

        return MessageResource::make($message)->response()->setStatusCode(201);
    }

    public function read(MessagingRequest $request, string $conversationId): Response
    {
        $this->workflow->markRead($request->user('sanctum'), $conversationId, $request->validated('message_id'));

        return response()->noContent();
    }

    private function opened(User $actor, Conversation $conversation): JsonResponse
    {
        $summary = $this->workflow->summaries($actor)->findOrFail($conversation->id);

        return ConversationResource::make($summary)->response()->setStatusCode($conversation->wasRecentlyCreated ? 201 : 200);
    }
}
