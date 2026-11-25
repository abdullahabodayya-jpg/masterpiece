<?php

namespace App\Services\Community;

use App\Enums\CommentStatus;
use App\Enums\PostStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;

final class CommunityContent
{
    public function __construct(private readonly CommunityAccess $access) {}

    public function posts(?User $viewer): Builder
    {
        return $this->withEngagement($this->access->posts($viewer), $viewer)
            ->withCount(['comments' => fn (Builder $query) => $this->visibleComments($query, $viewer)]);
    }

    public function comments(string $postId, ?User $viewer): Builder
    {
        $this->access->posts($viewer)->findOrFail($postId);

        return $this->withEngagement(
            $this->visibleComments(
                Comment::query()->where('post_id', $postId)
                    ->whereIn('post_id', $this->access->posts($viewer)->select('posts.id')),
                $viewer,
            ),
            $viewer,
        );
    }

    public function createPost(User $actor, array $input): Post
    {
        return $this->access->withLockedUsers($actor, [], fn (User $current) => $current->posts()->create($input));
    }

    public function changePost(User $actor, string $postId, ?array $input): void
    {
        $this->inPost($actor, $postId, function (Post $post) use ($actor, $input) {
            abort_unless($post->author_id === $actor->id, 403);
            if ($input === null) {
                $post->status = PostStatus::Removed;
                $post->save();
            } else {
                $post->update($input);
            }
        });
    }

    public function createComment(User $actor, string $postId, array $input): Comment
    {
        return $this->inPost($actor, $postId, function (Post $post) use ($actor, $input) {
            $comment = $post->comments()->make($input);
            $comment->author()->associate($actor);
            $comment->save();

            return $comment;
        });
    }

    public function changeComment(User $actor, string $postId, string $commentId, ?array $input): void
    {
        $this->inPost($actor, $postId, function (Post $post, Comment $comment) use ($actor, $input) {
            abort_unless($comment->author_id === $actor->id, 403);
            if ($input === null) {
                $comment->status = CommentStatus::Removed;
                $comment->save();
            } else {
                $comment->update($input);
            }
        }, $commentId);
    }

    public function react(User $actor, string $postId, ?string $commentId, ?string $type): ?Reaction
    {
        return $this->inPost($actor, $postId, function (Post $post, ?Comment $comment) use ($actor, $type) {
            $target = $comment ?? $post;
            $existing = $target->reactions()->withTrashed()->where('user_id', $actor->id)->lockForUpdate()->first();

            if ($type === null) {
                if ($existing && ! $existing->trashed()) {
                    $existing->delete();
                }

                return null;
            }

            $reaction = $existing ?? $target->reactions()->make();
            $reaction->user()->associate($actor);
            $reaction->reaction_type = $type;
            if ($reaction->trashed()) {
                $reaction->restore();
            } else {
                $reaction->save();
            }

            return $reaction;
        }, $commentId);
    }

    private function inPost(User $actor, string $postId, Closure $work, ?string $commentId = null): mixed
    {
        // Resolve immutable author IDs before entering the transaction and recheck access under locks.
        $ids = [Post::query()->findOrFail($postId, ['author_id'])->author_id];
        if ($commentId !== null) {
            $ids[] = Comment::query()->where('post_id', $postId)->findOrFail($commentId, ['author_id'])->author_id;
        }

        return $this->access->withLockedUsers($actor, $ids, function (User $viewer) use ($postId, $commentId, $work) {
            $post = $this->access->posts($viewer)->lockForUpdate()->findOrFail($postId);
            $comment = $commentId === null
                ? null
                : $this->visibleComments(Comment::query()->where('post_id', $postId), $viewer)
                    ->lockForUpdate()->findOrFail($commentId);

            return $work($post, $comment);
        });
    }

    private function visibleComments(Builder $query, ?User $viewer): Builder
    {
        return $query->where('comments.status', CommentStatus::Published->value)
            ->whereHas('author', fn (Builder $users) => $this->access->people($users, $viewer));
    }

    private function withEngagement(Builder $query, ?User $viewer): Builder
    {
        $query->with('author:id,name,display_name,professional_title,role')
            ->withCount(['reactions' => fn (Builder $reactions) => $reactions
                ->whereHas('user', fn (Builder $users) => $this->access->people($users, $viewer))]);

        if ($viewer) {
            $targetKey = $query->getModel() instanceof Post ? 'post_id' : 'comment_id';
            $query->addSelect(['my_reaction' => Reaction::query()->select('reaction_type')
                ->whereColumn($targetKey, $query->qualifyColumn('id'))
                ->where('user_id', $viewer->id)
                ->limit(1)]);
        }

        return $query;
    }
}
