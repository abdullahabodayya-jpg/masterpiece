<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommentStatus;
use App\Enums\PostStatus;
use App\Enums\PostVisibility;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Review;
use App\Models\User;
use App\Services\Marketplace\MarketplaceAccess;
use App\Services\Payments\PaymentAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

final class ModerationController
{
    public function __construct(
        private readonly MarketplaceAccess $accounts,
        private readonly PaymentAccess $access,
    ) {}

    public function removePost(Request $request, string $id): Response
    {
        return $this->moderate($request, $id, 'post.removed');
    }

    public function hideComment(Request $request, string $id): Response
    {
        return $this->moderate($request, $id, 'comment.hidden');
    }

    public function hideReview(Request $request, string $id): Response
    {
        return $this->moderate($request, $id, 'review.hidden');
    }

    private function moderate(Request $request, string $id, string $action): Response
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'max:1000']])['reason'];
        $this->accounts->lockAccounts($request->user(), [], function (User $admin) use ($id, $action) {
            $this->access->staff($admin);
            match ($action) {
                'post.removed' => $this->remove($id),
                'comment.hidden' => $this->hide($id),
                'review.hidden' => $this->archiveReview($id),
            };
        });

        Log::notice('Admin moderation action.', [
            'admin_id' => $request->user()->id, 'target_id' => $id, 'action' => $action, 'reason' => $reason,
        ]);

        return response()->noContent();
    }

    private function remove(string $id): void
    {
        $post = Post::query()->where('visibility', PostVisibility::Public->value)->lockForUpdate()->findOrFail($id);
        abort_unless($post->status === PostStatus::Published, 409);
        $post->status = PostStatus::Removed;
        $post->save();
    }

    private function hide(string $id): void
    {
        $candidate = Comment::query()->findOrFail($id);
        // Lock the parent before the comment, consistent with community mutation ordering.
        Post::query()->where('visibility', PostVisibility::Public->value)->lockForUpdate()->findOrFail($candidate->post_id);
        $comment = Comment::query()->where('post_id', $candidate->post_id)->lockForUpdate()->findOrFail($id);
        abort_unless($comment->status === CommentStatus::Published, 409);
        $comment->status = CommentStatus::Hidden;
        $comment->save();
    }

    private function archiveReview(string $id): void
    {
        $review = Review::withTrashed()->lockForUpdate()->findOrFail($id);
        abort_if($review->trashed(), 409);
        $review->delete();
    }
}
