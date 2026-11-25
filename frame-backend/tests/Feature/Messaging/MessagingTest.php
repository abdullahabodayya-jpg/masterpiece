<?php

namespace Tests\Feature\Messaging;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use App\Models\WorkItem;
use App\Support\ArchivedPivot;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Marketplace\MarketplaceTestCase;

final class MessagingTest extends MarketplaceTestCase
{
    protected function expectedDatabase(): string
    {
        return 'frame_messaging_test_001';
    }

    public function test_direct_creation_reuses_the_pair_and_refuses_archived_memberships(): void
    {
        $url = '/api/v1/conversations';
        $this->getJson($url)->assertUnauthorized();
        $creator = $this->signIn(User::factory()->creator()->create());
        $editor = User::factory()->editor()->create();
        $inactive = User::factory()->editor()->create(['status' => 'restricted']);
        $staff = User::factory()->admin()->create();

        $this->postJson($url, ['recipient_id' => strtoupper($creator->id)])->assertUnprocessable();
        $this->postJson($url, ['recipient_id' => $inactive->id])->assertNotFound();
        $this->postJson($url, ['recipient_id' => $staff->id])->assertNotFound();
        $this->postJson($url, ['recipient_id' => $editor->id, 'created_by_user_id' => $staff->id])->assertUnprocessable();
        ArchivedPivot::attachOrRestore($editor->blockedUsers(), $creator->id);
        $this->postJson($url, ['recipient_id' => $editor->id])->assertNotFound();
        $this->signIn($editor);
        $this->postJson($url, ['recipient_id' => $creator->id])->assertNotFound();
        ArchivedPivot::archive($editor->blockedUsers(), $creator->id);

        $this->signIn($creator);
        $id = $this->postJson($url, ['recipient_id' => $editor->id, 'title' => 'First thread'])->assertCreated()
            ->assertJsonPath('data.work_item_id', null)->assertJsonCount(2, 'data.participants')
            ->assertJsonMissingPath('data.participants.0.email')->json('data.id');
        $this->signIn($editor);
        $this->postJson($url, ['recipient_id' => $creator->id, 'title' => 'Replacement'])->assertOk()
            ->assertJsonPath('data.id', $id)->assertJsonPath('data.title', 'First thread');
        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseCount('conversation_participants', 2);
        $this->assertDatabaseHas('conversations', ['id' => $id, 'created_by_user_id' => $creator->id]);

        ArchivedPivot::archive(Conversation::findOrFail($id)->participants(), $creator->id);
        $this->postJson($url, ['recipient_id' => $creator->id])->assertConflict();
        $this->getJson("{$url}/{$id}")->assertNotFound();
        $this->signIn($creator);
        $this->getJson("{$url}/{$id}/messages")->assertNotFound();
        $this->assertDatabaseCount('conversations', 1);
        $this->assertNotNull(DB::table('conversation_participants')->where('user_id', $creator->id)->value('deleted_at'));
    }

    public function test_project_conversations_follow_assignment_and_preserve_work_and_finance(): void
    {
        $work = WorkItem::factory()->assigned()->create(['status' => 'pending_agreement', 'agreed_at' => null, 'started_at' => null]);
        $url = "/api/v1/work-items/{$work->id}/conversations";
        $outsider = $this->signIn(User::factory()->editor()->create());
        $this->postJson($url, ['title' => 'Project'])->assertNotFound();
        $creator = $this->signIn($work->creator);
        $unassigned = WorkItem::factory()->for($creator, 'creator')->open()->create();
        $this->postJson("/api/v1/work-items/{$unassigned->id}/conversations", ['title' => 'Job'])->assertNotFound();
        $this->postJson($url, ['title' => 'Project', 'participants' => [$outsider->id]])->assertUnprocessable();

        $id = $this->postJson($url, ['title' => 'Project'])->assertCreated()
            ->assertJsonPath('data.work_item_id', $work->id)->json('data.id');
        $thread = "/api/v1/conversations/{$id}";
        $editor = $this->signIn($work->editor);
        $this->postJson($url, ['title' => 'Again'])->assertOk()->assertJsonPath('data.id', $id);
        $this->postJson("{$thread}/messages", ['message_body' => 'Discuss terms'])->assertCreated();
        ArchivedPivot::attachOrRestore($creator->blockedUsers(), $editor->id);
        $this->postJson($url, ['title' => 'Another thread'])->assertNotFound();
        $this->postJson("{$thread}/messages", ['message_body' => 'Blocked'])->assertNotFound();
        $this->getJson("{$thread}/messages")->assertOk()->assertJsonCount(1, 'data');

        $work->editor_id = $outsider->id;
        $work->save();
        $this->getJson($thread)->assertNotFound();
        $this->signIn($outsider);
        $this->getJson($thread)->assertNotFound();
        $this->postJson($url, ['title' => 'New assignment'])->assertConflict();
        $this->assertDatabaseHas('work_items', ['id' => $work->id, 'status' => 'pending_agreement',
            'agreed_at' => null, 'started_at' => null, 'completed_at' => null]);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_messages_and_conversation_previews_are_private_ordered_and_bounded(): void
    {
        $first = $this->signIn(User::factory()->creator()->create());
        $second = User::factory()->creator()->create();
        $outsider = User::factory()->editor()->create();
        $this->travelTo(now()->startOfSecond());
        $id = $this->direct($second);
        $otherId = $this->direct($outsider);
        $url = "/api/v1/conversations/{$id}";
        foreach ([
            ['message_body' => "\u{00a0}\u{200b}"],
            ['message_body' => str_repeat('x', 5001)],
            ['message_body' => 'Text', 'sender_id' => $second->id],
            ['message_body' => 'Text', 'storage_key' => 'private'],
        ] as $invalid) {
            $this->postJson("{$url}/messages", $invalid)->assertUnprocessable();
        }

        $this->travel(2)->seconds();
        $text = '<script>display as text</script>';
        $message = $this->postJson("{$url}/messages", ['message_body' => $text])->assertCreated()
            ->assertJsonPath('data.sender_id', $first->id)->assertJsonPath('data.message_body', $text)->json('data.id');
        $this->assertDatabaseHas('conversation_body', ['id' => $message, 'message_body' => $text]);
        $this->travel(2)->seconds();
        $latest = $this->message($id, 'Latest');
        $this->assertSame(3, $this->queryCount(fn () => $this->getJson('/api/v1/conversations?per_page=2')->assertOk()
            ->assertJsonPath('data.0.id', $id)->assertJsonPath('data.0.latest_message.id', $latest)
            ->assertJsonPath('data.0.latest_message.preview', 'Latest')->assertJsonPath('data.1.id', $otherId)));
        $this->assertSame(2, $this->queryCount(fn () => $this->getJson("{$url}/messages?per_page=1")->assertOk()
            ->assertJsonPath('data.0.id', $latest)->assertJsonPath('meta.has_more', true)));
        $this->getJson("{$url}/messages?per_page=1&page=2")->assertOk()->assertJsonPath('data.0.id', $message);
        $this->getJson("{$url}/messages?sort=oldest")->assertOk()->assertJsonPath('data.0.id', $message);
        $this->assertEquals(now(), Conversation::findOrFail($id)->updated_at);

        $this->signIn($second);
        $this->getJson('/api/v1/conversations')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $id);
        $this->getJson($url)->assertOk()->assertJsonMissingPath('data.latest_message.storage_key');
        ArchivedPivot::attachOrRestore($first->blockedUsers(), $second->id);
        $this->getJson("{$url}/messages")->assertOk()->assertJsonCount(2, 'data');
        $this->postJson("{$url}/messages", ['message_body' => 'Blocked'])->assertNotFound();
        $this->signIn($outsider);
        $this->getJson($url)->assertNotFound();
        $this->getJson("{$url}/messages")->assertNotFound();
        $this->postJson("{$url}/messages", ['message_body' => 'Outsider'])->assertNotFound();

        $second->status = 'suspended';
        $second->save();
        $this->signIn($second);
        $this->getJson($url)->assertForbidden();
    }

    public function test_read_markers_are_own_monotonic_and_have_second_precision(): void
    {
        $creator = $this->signIn(User::factory()->creator()->create());
        $editor = User::factory()->editor()->create();
        $other = User::factory()->editor()->create();
        $id = $this->direct($editor);
        $otherId = $this->direct($other);
        $url = "/api/v1/conversations/{$id}";
        $old = $this->message($id, 'Old');
        $this->travel(3)->seconds();
        $latest = $this->message($id, 'Latest');
        $same = $this->message($id, 'Same second');
        $unrelated = $this->message($otherId, 'Other');
        $marker = "{$url}/read-marker";

        $this->signIn($other);
        $this->postJson($marker, ['message_id' => $latest])->assertNotFound();
        $this->signIn($editor);
        $this->postJson($marker, ['message_id' => $unrelated])->assertNotFound();
        $this->postJson($marker, ['message_id' => $latest, 'last_read_at' => now()->addDay()->toISOString()])->assertUnprocessable();
        foreach ([$latest, $latest, $old, $same] as $message) {
            $this->postJson($marker, ['message_id' => $message])->assertNoContent();
        }
        $post = ConversationMessage::findOrFail($latest);
        $this->assertEquals($post->created_at, ConversationMessage::findOrFail($same)->created_at);
        $this->assertDatabaseHas('conversation_participants', ['conversation_id' => $id, 'user_id' => $editor->id,
            'last_read_at' => $post->getRawOriginal('created_at')]);
        $this->assertDatabaseHas('conversation_participants', ['conversation_id' => $id, 'user_id' => $creator->id, 'last_read_at' => null]);
        $this->getJson($url)->assertOk()->assertJsonPath('data.last_read_at', $post->created_at->toISOString())
            ->assertJsonMissingPath('data.unread_count');
    }

    public function test_second_membership_failure_rolls_back_both_conversation_and_first_membership(): void
    {
        $this->signIn(User::factory()->editor()->create());
        $editor = User::factory()->editor()->create();
        $writes = 0;
        DB::connection()->beforeExecuting(function (string $sql) use (&$writes) {
            if (str_starts_with($sql, 'insert into `conversation_participants`') && ++$writes === 2) {
                throw new \RuntimeException('Synthetic second membership failure');
            }
        });
        $this->postJson('/api/v1/conversations', ['recipient_id' => $editor->id])->assertServerError();
        $this->assertSame(2, $writes);
        $this->assertDatabaseCount('conversations', 0);
        $this->assertDatabaseCount('conversation_participants', 0);
        $this->direct($editor);
        $this->assertDatabaseCount('conversation_participants', 2);
    }

    private function direct(User $recipient): string
    {
        return $this->postJson('/api/v1/conversations', ['recipient_id' => $recipient->id])->assertCreated()->json('data.id');
    }

    private function message(string $conversation, string $body): string
    {
        return $this->postJson("/api/v1/conversations/{$conversation}/messages", ['message_body' => $body])
            ->assertCreated()->json('data.id');
    }
}
