<?php

namespace App\Services\Messaging;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Marketplace\MarketplaceAccess;
use App\Services\Work\WorkAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as Query;

final class ConversationAccess
{
    public function __construct(
        private readonly MarketplaceAccess $accounts,
        private readonly WorkAccess $work,
    ) {}

    public function visible(User $actor): Builder
    {
        $this->accounts->actor($actor);

        return Conversation::query()
            ->whereExists(fn (Query $members) => $this->members($members)->where('user_id', $actor->id)->whereNull('deleted_at'))
            ->where(fn (Query $members) => $this->members($members)->selectRaw('COUNT(*)'), '=', 2)
            ->whereNotExists(fn (Query $members) => $this->members($members)->whereNotNull('deleted_at'))
            ->where(function (Builder $scope) use ($actor) {
                $scope->where(function (Builder $direct) {
                    $direct->whereNull('work_item_id')
                        ->whereExists(fn (Query $members) => $this->members($members)->whereColumn('user_id', 'conversations.created_by_user_id'));
                })->orWhereExists(function (Query $work) use ($actor) {
                    $side = $actor->role === UserRole::Creator ? 'creator_id' : 'editor_id';
                    $other = $actor->role === UserRole::Creator ? 'editor_id' : 'creator_id';
                    $work->selectRaw('1')->from('work_items')->whereColumn('work_items.id', 'conversations.work_item_id')
                        ->where("work_items.{$side}", $actor->id)
                        ->whereColumn('work_items.creator_id', '<>', 'work_items.editor_id')
                        ->whereExists(fn (Query $members) => $this->members($members)->whereColumn('user_id', "work_items.{$other}"));
                });
            });
    }

    public function project(User $actor): Builder
    {
        return $this->work->project($actor)->whereColumn('creator_id', '<>', 'editor_id');
    }

    public function checkPair(User $actor, User $other): void
    {
        $this->accounts->actor($actor);
        abort_unless($this->accounts->people($other->newQuery(), $actor, $other->role)
            ->whereKey($other->id)->whereIn('users.role', [UserRole::Creator->value, UserRole::Editor->value])->exists(), 404);
    }

    private function members(Query $query): Query
    {
        return $query->from('conversation_participants')
            ->whereColumn('conversation_participants.conversation_id', 'conversations.id');
    }
}
