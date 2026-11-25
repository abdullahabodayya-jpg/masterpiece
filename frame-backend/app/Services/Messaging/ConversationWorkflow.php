<?php

namespace App\Services\Messaging;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use App\Models\WorkItem;
use App\Services\Marketplace\MarketplaceAccess;
use App\Support\ArchivedPivot;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as Query;
use Illuminate\Validation\ValidationException;

final class ConversationWorkflow
{
    public function __construct(
        private readonly MarketplaceAccess $accounts,
        private readonly ConversationAccess $access,
    ) {}

    public function summaries(User $actor): Builder
    {
        return $this->access->visible($actor)->select('conversations.*')
            ->selectRaw('COALESCE(conversations.updated_at, conversations.created_at) AS activity_at')
            ->withCasts(['activity_at' => 'datetime'])
            ->with(['participants:users.id,users.display_name,users.name', 'latestMessage:conversation_body.id,conversation_body.conversation_id,conversation_body.sender_id,conversation_body.message_body,conversation_body.created_at']);
    }

    public function direct(User $actor, string $recipientId, ?string $title): Conversation
    {
        $recipientId = strtolower($recipientId);
        if ($recipientId === $actor->id) {
            throw ValidationException::withMessages(['recipient_id' => 'Choose another participant.']);
        }

        return $this->accounts->lockAccounts($actor, [$recipientId], function (User $sender, array $users) use ($recipientId, $title) {
            $this->access->checkPair($sender, $users[$recipientId]);
            $query = Conversation::withTrashed()->whereNull('work_item_id');
            foreach ([$sender->id, $recipientId] as $id) {
                $query->whereExists(fn (Query $members) => $members->selectRaw('1')->from('conversation_participants')
                    ->whereColumn('conversation_id', 'conversations.id')->where('user_id', $id));
            }

            return $this->open($sender, $recipientId, $title, $query);
        });
    }

    public function project(User $actor, string $workId, string $title): Conversation
    {
        $candidate = $this->access->project($actor)->findOrFail($workId);

        return $this->accounts->lockAccounts($actor, [$candidate->creator_id, $candidate->editor_id], function (User $current, array $users) use ($workId, $title) {
            $work = $this->access->project($current)->lockForUpdate()->findOrFail($workId);
            abort_unless(isset($users[$work->creator_id], $users[$work->editor_id]), 409);
            $other = $this->counterpart($work, $current, $users);
            $this->access->checkPair($current, $other);

            return $this->open($current, $other->id, $title, Conversation::withTrashed()->where('work_item_id', $work->id), $work);
        });
    }

    public function send(User $actor, string $id, string $body): ConversationMessage
    {
        return $this->locked($actor, $id, function (User $current, Conversation $conversation, array $users, ?WorkItem $work) use ($body) {
            $other = $work ? $this->counterpart($work, $current, $users) : collect($users)->except([$current->id])->sole();
            $this->access->checkPair($current, $other);
            $message = $conversation->messages()->make(['message_body' => $body]);
            $message->sender()->associate($current);
            $message->save();
            $conversation->touch();

            return $message;
        });
    }

    public function markRead(User $actor, string $id, string $messageId): void
    {
        $this->locked($actor, $id, function (User $current, Conversation $conversation) use ($messageId) {
            $message = $conversation->messages()->findOrFail($messageId);
            abort_if($message->created_at->isFuture(), 409);
            $timestamp = $message->getRawOriginal('created_at');

            $conversation->getConnection()->table('conversation_participants')
                ->where('conversation_id', $conversation->id)->where('user_id', $current->id)->whereNull('deleted_at')
                ->where(fn (Query $marker) => $marker->whereNull('last_read_at')->orWhere('last_read_at', '<', $timestamp))
                ->update(['last_read_at' => $timestamp, 'updated_at' => now()]);
        });
    }

    private function open(User $actor, string $otherId, ?string $title, Builder $candidates, ?WorkItem $work = null): Conversation
    {
        $existing = $candidates->orderBy('id')->limit(2)->lockForUpdate()->pluck('id');
        abort_if($existing->count() > 1, 409);
        if ($existing->isNotEmpty()) {
            $conversation = $this->access->visible($actor)->find($existing->first());
            abort_unless($conversation !== null, 409);

            return $conversation;
        }

        $conversation = new Conversation(['title' => $title]);
        $conversation->createdBy()->associate($actor);
        if ($work) {
            $conversation->workItem()->associate($work);
        }
        $conversation->save();
        ArchivedPivot::attachOrRestore($conversation->participants(), $actor->id);
        ArchivedPivot::attachOrRestore($conversation->participants(), $otherId);

        return $conversation;
    }

    private function locked(User $actor, string $id, Closure $action): mixed
    {
        $candidate = $this->access->visible($actor)->findOrFail($id);
        $ids = $candidate->getConnection()->table('conversation_participants')
            ->where('conversation_id', $id)->orderBy('user_id')->pluck('user_id')->all();

        return $this->accounts->lockAccounts($actor, $ids, function (User $current, array $users) use ($candidate, $ids, $action) {
            $work = $candidate->work_item_id === null ? null
                : $this->access->project($current)->lockForUpdate()->findOrFail($candidate->work_item_id);

            $conversation = $this->access->visible($current)
                ->where('work_item_id', $candidate->work_item_id)->lockForUpdate()->findOrFail($candidate->id);
            $lockedIds = $conversation->getConnection()->table('conversation_participants')
                ->where('conversation_id', $conversation->id)->whereNull('deleted_at')
                ->orderBy('user_id')->lockForUpdate()->pluck('user_id')->all();
            abort_unless($ids === $lockedIds && count($lockedIds) === 2, 404);

            return $action($current, $conversation, $users, $work);
        });
    }

    private function counterpart(WorkItem $work, User $actor, array $users): User
    {
        $creator = $this->accounts->actor($users[$work->creator_id], UserRole::Creator);
        $editor = $this->accounts->actor($users[$work->editor_id], UserRole::Editor);

        return $actor->is($creator) ? $editor : $creator;
    }
}
