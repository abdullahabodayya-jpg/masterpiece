<?php

namespace Tests\Feature\Community;

use App\Models\Connection;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class NetworkingTest extends CommunityTestCase
{
    public function test_follow_lists_status_counts_and_archive_restoration(): void
    {
        $actor = $this->signIn();
        $other = User::factory()->editor()->create();
        $url = "/api/v1/me/follows/{$other->id}";

        $this->putJson('/api/v1/me/follows/'.strtoupper($actor->id))->assertUnprocessable();
        $this->putJson($url)->assertNoContent();
        $this->putJson($url)->assertNoContent();
        $this->assertDatabaseCount('user_follows', 1);
        $initial = DB::table('user_follows')->sole();
        $this->assertSame(2, $this->queryCount(fn () => $this->getJson("/api/v1/users/{$other->id}/followers?per_page=1")
            ->assertOk()->assertJsonPath('data.0.id', $actor->id)->assertJsonPath('meta.has_more', false)));
        $this->assertSame(2, $this->queryCount(fn () => $this->getJson("/api/v1/users/{$actor->id}/following")
            ->assertOk()->assertJsonPath('data.0.id', $other->id)));
        $this->getJson("/api/v1/users/{$other->id}/relationship")->assertOk()
            ->assertJsonPath('data.following', true)->assertJsonPath('data.followed_by', false)
            ->assertJsonPath('data.followers_count', 1)->assertJsonPath('data.following_count', 0);

        $this->deleteJson($url)->assertNoContent();
        $this->assertNotNull(DB::table('user_follows')->sole()->deleted_at);
        $this->getJson("/api/v1/users/{$other->id}/followers")->assertOk()->assertJsonCount(0, 'data');
        $this->putJson($url)->assertNoContent();
        $restored = DB::table('user_follows')->sole();
        $this->assertSame($initial->created_at, $restored->created_at);
        $this->assertNull($restored->deleted_at);
    }

    public function test_connections_are_visible_only_to_participants_and_only_recipients_decide(): void
    {
        $requester = $this->signIn();
        $recipient = User::factory()->editor()->create();
        $post = Post::factory()->for($requester, 'author')->create(['visibility' => 'connections_only']);
        $connect = '/api/v1/connections';

        $this->postJson($connect, ['recipient_id' => $requester->id])->assertUnprocessable();
        $this->postJson($connect, ['recipient_id' => 1])->assertUnprocessable();
        $this->postJson($connect, ['recipient_id' => $recipient->id, 'status' => 'accepted'])->assertUnprocessable();
        $id = $this->postJson($connect, ['recipient_id' => $recipient->id])->assertCreated()
            ->assertJsonPath('data.requester_id', $requester->id)->assertJsonPath('data.status', 'pending')->json('data.id');
        $decision = "{$connect}/{$id}/decisions";
        $this->getJson("{$connect}/outgoing")->assertOk()->assertJsonPath('data.0.id', $id);
        $this->postJson($decision, ['decision' => 'accept'])->assertForbidden();

        $this->signIn();
        $this->postJson($decision, ['decision' => 'accept'])->assertNotFound();
        $this->getJson("{$connect}/incoming")->assertOk()->assertJsonCount(0, 'data');

        $this->signIn($recipient);
        $this->getJson("{$connect}/incoming")->assertOk()->assertJsonPath('data.0.id', $id)
            ->assertJsonMissingPath('data.0.requester.email');
        $this->postJson($connect, ['recipient_id' => $requester->id])->assertConflict();
        $this->deleteJson("{$connect}/{$id}")->assertForbidden();
        $this->postJson($decision, ['decision' => 'accept'])->assertOk()->assertJsonPath('data.status', 'accepted');
        $this->postJson($decision, ['decision' => 'reject'])->assertConflict()->assertJsonPath('error.code', 'state_conflict');
        $this->assertSame(3, $this->queryCount(fn () => $this->getJson("{$connect}/accepted")
            ->assertOk()->assertJsonPath('data.0.id', $id)));
        $this->getJson("/api/v1/posts/{$post->id}")->assertOk();
        $this->assertNotNull(Connection::findOrFail($id)->responded_at);
        $this->assertDatabaseCount('connections', 1);
    }

    public function test_connections_can_be_rejected_or_cancelled_while_pending(): void
    {
        $actor = $this->signIn();
        $other = User::factory()->editor()->create();
        $third = User::factory()->editor()->create();
        $url = '/api/v1/connections';
        $rejected = $this->postJson($url, ['recipient_id' => $other->id])->assertCreated()->json('data.id');
        $cancelled = $this->postJson($url, ['recipient_id' => $third->id])->assertCreated()->json('data.id');
        $this->deleteJson("{$url}/{$cancelled}")->assertNoContent();
        $this->deleteJson("{$url}/{$cancelled}")->assertConflict();

        $this->signIn($other);
        $this->postJson("{$url}/{$rejected}/decisions", ['decision' => 'reject'])
            ->assertOk()->assertJsonPath('data.status', 'rejected');
        $this->postJson($url, ['recipient_id' => $actor->id])->assertConflict();
        $this->assertDatabaseHas('connections', ['id' => $cancelled, 'status' => 'cancelled']);
        $this->assertDatabaseHas('connections', ['id' => $rejected, 'status' => 'rejected']);
    }

    public function test_blocks_are_private_and_disable_interaction_in_both_directions(): void
    {
        $actor = $this->signIn();
        $other = User::factory()->editor()->create();
        $post = Post::factory()->for($other, 'author')->create();
        $actorPost = Post::factory()->for($actor, 'author')->create();
        $block = "/api/v1/me/blocks/{$other->id}";
        $follow = "/api/v1/me/follows/{$other->id}";

        $this->putJson("/api/v1/me/blocks/{$actor->id}")->assertUnprocessable();
        $this->putJson($follow)->assertNoContent();
        $id = $this->postJson('/api/v1/connections', ['recipient_id' => $other->id])->assertCreated()->json('data.id');
        $this->putJson($block)->assertNoContent();
        $this->putJson($block)->assertNoContent();
        $initial = DB::table('user_blocks')->sole();
        $this->getJson('/api/v1/me/blocks')->assertOk()->assertJsonPath('data.0.id', $other->id)
            ->assertJsonMissingPath('data.0.email');
        $this->getJson("/api/v1/posts/{$post->id}")->assertNotFound();
        $this->postJson("/api/v1/posts/{$post->id}/comments", ['body' => 'test'])->assertNotFound();
        $this->putJson("/api/v1/posts/{$post->id}/reaction", ['reaction_type' => 'like'])->assertNotFound();
        $this->putJson($follow)->assertNotFound();
        $this->getJson("/api/v1/users/{$actor->id}/following")->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/connections/outgoing')->assertOk()->assertJsonCount(0, 'data');
        $this->postJson('/api/v1/connections', ['recipient_id' => $other->id])->assertNotFound();

        $this->signIn($other);
        $this->getJson('/api/v1/me/blocks')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/v1/posts/{$actorPost->id}")->assertNotFound();
        $this->putJson("/api/v1/me/follows/{$actor->id}")->assertNotFound();
        $this->postJson("/api/v1/connections/{$id}/decisions", ['decision' => 'accept'])->assertNotFound();

        $this->signIn($actor);
        $this->deleteJson($block)->assertNoContent();
        $this->assertNotNull(DB::table('user_blocks')->sole()->deleted_at);
        $this->getJson("/api/v1/posts/{$post->id}")->assertOk();
        $this->getJson("/api/v1/users/{$other->id}/relationship")->assertOk()
            ->assertJsonPath('data.following', true)->assertJsonPath('data.connection.status', 'pending');
        $this->putJson($block)->assertNoContent();
        $restored = DB::table('user_blocks')->sole();
        $this->assertSame($initial->created_at, $restored->created_at);
        $this->assertNull($restored->deleted_at);
    }
}
