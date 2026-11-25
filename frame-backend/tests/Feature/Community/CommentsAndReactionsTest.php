<?php

namespace Tests\Feature\Community;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Reaction;
use App\Support\ArchivedPivot;

final class CommentsAndReactionsTest extends CommunityTestCase
{
    public function test_comments_require_an_accessible_post_and_retain_removed_records(): void
    {
        $actor = $this->signIn();
        $post = Post::factory()->create();
        $url = "/api/v1/posts/{$post->id}/comments";

        $this->postJson($url, ['body' => ''])->assertUnprocessable();
        $this->postJson($url, ['body' => 'Hello', 'parent_comment_id' => null])->assertUnprocessable();
        $response = $this->postJson($url, ['body' => 'Hello'])->assertCreated()
            ->assertJsonPath('data.author.id', $actor->id)->assertJsonPath('data.reactions_count', 0);
        $id = $response->json('data.id');
        $this->assertDatabaseHas('comments', ['id' => $id, 'post_id' => $post->id, 'author_id' => $actor->id]);

        $other = Comment::factory()->for($post)->create();
        $this->assertSame(3, $this->queryCount(fn () => $this->getJson("{$url}?per_page=1")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.has_more', true)));
        $this->patchJson("{$url}/{$other->id}", ['body' => 'edited'])->assertForbidden();
        $differentPost = Post::factory()->create();
        $this->patchJson("/api/v1/posts/{$differentPost->id}/comments/{$id}", ['body' => 'bad'])->assertNotFound();
        $this->patchJson("{$url}/{$id}", ['body' => 'edited'])->assertOk()->assertJsonPath('data.body', 'edited');
        $this->deleteJson("{$url}/{$id}")->assertNoContent();
        $this->assertDatabaseHas('comments', ['id' => $id, 'status' => 'removed']);
        $this->getJson($url)->assertJsonCount(1, 'data');

        $post->visibility = 'private';
        $post->save();
        $this->getJson($url)->assertNotFound();
        $this->postJson($url, ['body' => 'Hello'])->assertNotFound();
        $this->deleteJson("{$url}/{$other->id}")->assertNotFound();
    }

    public function test_reaction_changes_and_restores_the_same_row_and_counts_for_each_target(): void
    {
        $actor = $this->signIn();
        $comment = Comment::factory()->create();
        $post = "/api/v1/posts/{$comment->post_id}";
        $comments = "{$post}/comments";

        foreach ([
            ['post_id', $comment->post_id, $post, 'data'],
            ['comment_id', $comment->id, "{$comments}/{$comment->id}", 'data.0'],
        ] as [$column, $id, $target, $path]) {
            $reactionUrl = "{$target}/reaction";
            $readUrl = $column === 'post_id' ? $post : $comments;
            $this->putJson($reactionUrl, ['reaction_type' => str_repeat('x', 51)])->assertUnprocessable();
            $this->putJson($reactionUrl, ['reaction_type' => 'like', 'user_id' => $actor->id])->assertUnprocessable();
            $this->putJson($reactionUrl, ['reaction_type' => 'like'])->assertOk()->assertJsonPath("data.{$column}", $id);
            $this->putJson($reactionUrl, ['reaction_type' => 'like'])->assertOk();
            $query = Reaction::withTrashed()->where($column, $id)->where('user_id', $actor->id);
            $original = $query->sole();
            $this->getJson($readUrl)->assertOk()->assertJsonPath("{$path}.reactions_count", 1)
                ->assertJsonPath("{$path}.my_reaction", 'like');

            $this->deleteJson($reactionUrl)->assertNoContent();
            $this->assertTrue($query->sole()->trashed());
            $this->getJson($readUrl)->assertJsonPath("{$path}.reactions_count", 0)->assertJsonPath("{$path}.my_reaction", null);

            $this->putJson($reactionUrl, ['reaction_type' => 'helpful'])->assertOk();
            $restored = $query->sole();
            $this->assertSame($original->id, $restored->id);
            $this->assertEquals($original->created_at, $restored->created_at);
            $this->assertFalse($restored->trashed());
            $this->assertSame('helpful', $restored->reaction_type);
        }
    }

    public function test_hidden_blocked_and_mismatched_comment_targets_are_unavailable(): void
    {
        $actor = $this->signIn();
        $comment = Comment::factory()->create();
        $another = Post::factory()->create();
        $url = "/api/v1/posts/{$comment->post_id}/comments/{$comment->id}/reaction";
        $input = ['reaction_type' => 'like'];

        $this->putJson("/api/v1/posts/{$another->id}/comments/{$comment->id}/reaction", $input)->assertNotFound();
        $comment->status = 'hidden';
        $comment->save();
        $this->putJson($url, $input)->assertNotFound();
        $comment->status = 'published';
        $comment->save();
        ArchivedPivot::attachOrRestore($actor->blockedUsers(), $comment->author_id);
        $this->putJson($url, $input)->assertNotFound();
        $this->getJson("/api/v1/posts/{$comment->post_id}/comments")->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/v1/posts/{$comment->post_id}")->assertJsonPath('data.comments_count', 0);
        $this->assertDatabaseCount('reactions', 0);
    }
}
