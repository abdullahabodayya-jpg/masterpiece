<?php

namespace Tests\Feature\Community;

use App\Enums\PostStatus;
use App\Models\Connection;
use App\Models\Post;
use App\Models\User;
use App\Support\ArchivedPivot;
use Illuminate\Support\Str;

final class PostsTest extends CommunityTestCase
{
    public function test_creation_requires_authentication_and_validated_fields(): void
    {
        $url = '/api/v1/posts';
        $valid = ['body' => 'A useful editing tip', 'visibility' => 'public'];

        $this->postJson($url, $valid)->assertUnauthorized()
            ->assertJsonPath('error.code', 'authentication_required');

        $author = $this->signIn();
        $this->postJson($url, ['body' => ' ', 'visibility' => 'unsupported'])->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')->assertJsonStructure(['error' => ['request_id', 'fields']]);
        $this->postJson($url, $valid + ['author_id' => Str::uuid()->toString(), 'status' => 'archived'])
            ->assertUnprocessable()->assertJsonStructure(['error' => ['fields' => ['author_id', 'status']]]);
        $this->postJson($url, $valid + ['extra' => true])->assertUnprocessable()
            ->assertJsonStructure(['error' => ['fields' => ['extra']]]);

        $response = $this->postJson($url, $valid)->assertCreated()
            ->assertJsonPath('data.author.id', $author->id)->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.comments_count', 0)->assertJsonPath('data.reactions_count', 0)
            ->assertJsonPath('data.my_reaction', null)->assertJsonMissingPath('data.author.email')
            ->assertJsonMissingPath('data.author.password_hash')->assertJsonMissingPath('data.media_storage_key');
        $this->assertDatabaseHas('posts', ['id' => $response->json('data.id'), 'author_id' => $author->id]);
    }

    public function test_only_the_owner_can_edit_and_remove_a_published_post(): void
    {
        $actor = $this->signIn();
        $post = Post::factory()->create();
        $url = "/api/v1/posts/{$post->id}";

        $this->patchJson($url, ['body' => 'edited'])->assertForbidden();
        $this->deleteJson($url)->assertForbidden();
        $this->assertSame(PostStatus::Published, $post->fresh()->status);

        $this->signIn($post->author);
        $this->patchJson($url, ['author_id' => $actor->id])->assertUnprocessable();
        $this->patchJson($url, ['body' => 'edited'])->assertOk()->assertJsonPath('data.body', 'edited');
        $this->deleteJson($url)->assertNoContent();
        $this->assertSame(PostStatus::Removed, $post->fresh()->status);
        $this->getJson($url)->assertNotFound()->assertJsonPath('error.code', 'not_found');
        $this->getJson('/api/v1/posts')->assertJsonCount(0, 'data');
    }

    public function test_public_reads_match_visibility_status_and_block_rules(): void
    {
        $author = User::factory()->editor()->create();
        $viewer = User::factory()->creator()->create();
        $public = Post::factory()->for($author, 'author')->create();
        $private = Post::factory()->for($author, 'author')->create(['visibility' => 'private']);
        $connected = Post::factory()->for($author, 'author')->create(['visibility' => 'connections_only']);
        $archived = Post::factory()->for($author, 'author')->create(['status' => 'archived']);

        $this->getJson('/api/v1/posts')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $public->id);
        $this->getJson("/api/v1/posts/{$private->id}")->assertNotFound();
        $this->getJson("/api/v1/posts/{$connected->id}")->assertNotFound();

        $this->signIn($viewer);
        $this->getJson("/api/v1/posts/{$connected->id}")->assertNotFound();
        $pair = Connection::between($viewer, $author);
        $pair->status = 'accepted';
        $pair->save();

        $this->getJson("/api/v1/posts/{$connected->id}")->assertOk();
        $this->getJson("/api/v1/posts/{$private->id}")->assertNotFound();
        $this->getJson("/api/v1/posts/{$archived->id}")->assertNotFound();
        $this->getJson("/api/v1/users/{$author->id}/posts")->assertOk()->assertJsonCount(2, 'data');

        ArchivedPivot::attachOrRestore($author->blockedUsers(), $viewer->id);
        $this->getJson('/api/v1/posts')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/v1/posts/{$public->id}")->assertNotFound();
        $this->getJson("/api/v1/users/{$author->id}/posts")->assertNotFound();
    }

    public function test_followed_feed_is_bounded_and_query_count_does_not_grow_per_post(): void
    {
        $viewer = $this->signIn();
        $author = User::factory()->editor()->create();
        Post::factory()->count(5)->for($author, 'author')->create();
        Post::factory()->create();
        ArchivedPivot::attachOrRestore($viewer->following(), $author->id);

        $this->assertSame(2, $this->queryCount(fn () => $this->getJson('/api/v1/me/following/posts?per_page=1')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.has_more', true)));
        $this->assertSame(2, $this->queryCount(fn () => $this->getJson('/api/v1/me/following/posts?per_page=5')
            ->assertOk()->assertJsonCount(5, 'data')->assertJsonPath('meta.has_more', false)));
        $this->assertSame(2, $this->queryCount(fn () => $this->getJson('/api/v1/posts')->assertOk()));
        $this->getJson('/api/v1/posts?per_page=101')->assertBadRequest()
            ->assertJsonPath('error.code', 'invalid_request');
        ArchivedPivot::archive($viewer->following(), $author->id);
        $this->getJson('/api/v1/me/following/posts')->assertJsonCount(0, 'data');
    }

    public function test_inactive_and_staff_actors_cannot_use_community_operations(): void
    {
        foreach (['pending_verification', 'restricted', 'suspended', 'closed'] as $status) {
            $this->signIn(User::factory()->creator()->create(['status' => $status]));
            $this->getJson('/api/v1/posts')->assertForbidden()->assertJsonPath('error.code', 'forbidden');
            $this->postJson('/api/v1/posts', ['body' => 'Test', 'visibility' => 'public'])->assertForbidden();
        }
        $this->signIn(User::factory()->admin()->create());
        $this->postJson('/api/v1/posts', ['body' => 'Test', 'visibility' => 'public'])->assertForbidden();
        $this->assertDatabaseCount('posts', 0);
    }
}
