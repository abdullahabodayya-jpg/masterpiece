<?php

namespace Tests\Feature\Database;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Enums\WorkStatus;
use App\Models\Comment;
use App\Models\Connection;
use App\Models\ContentType;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Deliverable;
use App\Models\DeliverableRevision;
use App\Models\EditingTool;
use App\Models\EditorService;
use App\Models\EditorVerificationRequest;
use App\Models\JobApplication;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\PortfolioProject;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\Refund;
use App\Models\Review;
use App\Models\User;
use App\Models\UserLink;
use App\Models\WorkItem;
use App\Support\ArchivedPivot;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FrameDatabaseTest extends TestCase
{
    private static bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = DB::connection();
        if ($connection->getDriverName() === 'sqlite' && $connection->getDatabaseName() === ':memory:') {
            $this->markTestSkipped('Set DB_CONNECTION=mysql and DB_DATABASE=frame_database_test_002 for MariaDB tests.');
        }

        $this->assertContains($connection->getDriverName(), ['mariadb', 'mysql']);
        $this->assertSame('frame_database_test_002', $connection->getDatabaseName());

        $pdo = $connection->getPdo();
        $this->assertSame('frame_database_test_002', $pdo->query('SELECT DATABASE()')->fetchColumn());
        $this->assertStringContainsString('MariaDB', $pdo->getAttribute(PDO::ATTR_SERVER_VERSION));

        if (! self::$migrated) {
            $this->artisan('migrate', ['--force' => true])->assertSuccessful();
            self::$migrated = true;
        }

        $connection->beginTransaction();
        $this->beforeApplicationDestroyed(fn () => $connection->rollBack());
    }

    public function test_migrations_create_exactly_the_approved_tables_and_infrastructure(): void
    {
        $domain = [
            'users', 'content_types', 'user_content_types', 'editing_tools', 'editor_tools', 'user_links',
            'portfolio_projects', 'editor_services', 'work_items', 'job_applications', 'conversations',
            'conversation_participants', 'conversation_body', 'deliverables', 'deliverable_revisions',
            'payments', 'payouts', 'refunds', 'reviews', 'posts', 'comments', 'reactions', 'user_follows',
            'connections', 'user_blocks', 'editor_verification_requests',
        ];
        $infrastructure = [
            'cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs', 'migrations',
            'password_reset_tokens', 'personal_access_tokens', 'sessions',
        ];

        $tables = DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->pluck('ENGINE', 'TABLE_NAME');

        $this->assertEqualsCanonicalizing([...$domain, ...$infrastructure], $tables->keys()->all());
        $this->assertSame(['InnoDB'], $tables->unique()->values()->all());
    }

    public static function standaloneModels(): iterable
    {
        foreach ([
            User::class, ContentType::class, EditingTool::class, UserLink::class, PortfolioProject::class,
            EditorService::class, WorkItem::class, JobApplication::class, Conversation::class,
            ConversationMessage::class, Deliverable::class, DeliverableRevision::class, Payment::class,
            Payout::class, Refund::class, Review::class, Post::class, Comment::class, Reaction::class,
            Connection::class, EditorVerificationRequest::class,
        ] as $model) {
            yield class_basename($model) => [$model];
        }
    }

    #[DataProvider('standaloneModels')]
    public function test_standalone_factory_records_have_uuid_keys(string $model): void
    {
        $record = $model::factory()->create();
        $this->assertTrue(Str::isUuid($record->getKey()));
        $this->assertSame($record->getKey(), $record->fresh()->getKey());
    }

    public function test_seeders_are_idempotent_and_preserve_archived_reference_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        ContentType::where('name', 'Podcasts')->firstOrFail()->delete();
        EditingTool::where('name', 'CapCut')->firstOrFail()->delete();
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(15, ContentType::withTrashed()->count());
        $this->assertSame(5, EditingTool::withTrashed()->count());
        $this->assertSame(14, ContentType::count());
        $this->assertSame(4, EditingTool::count());
    }

    public function test_user_casts_factory_states_and_soft_deletion(): void
    {
        $user = User::factory()->creator()->unverified()->create();
        $this->assertSame(UserRole::Creator, $user->role);
        $this->assertSame(AccountStatus::Active, $user->status);
        $this->assertNull($user->email_verified_at);
        $this->assertSame(UserRole::Editor, User::factory()->editor()->create()->role);
        $this->assertSame(UserRole::Admin, User::factory()->admin()->create()->role);
        $this->assertSame(UserRole::Support, User::factory()->support()->create()->role);
        $this->assertSame('password_hash', $user->getAuthPasswordName());
        $this->assertArrayNotHasKey('password_hash', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
        $user->delete();
        $this->assertNull(User::find($user->id));
        $user->restore();
        $this->assertNotNull(User::find($user->id));
    }

    public function test_all_pivots_archive_and_restore_the_same_row_in_both_directions(): void
    {
        $user = User::factory()->editor()->create();
        $other = User::factory()->create();
        $content = ContentType::factory()->create();
        $tool = EditingTool::factory()->create();
        $conversation = Conversation::factory()->create();
        $pairs = [
            [$user->contentTypes(), $content->users(), $content->id],
            [$user->editingTools(), $tool->editors(), $tool->id],
            [$user->conversations(), $conversation->participants(), $conversation->id],
            [$user->following(), $other->followers(), $other->id],
            [$user->blockedUsers(), $other->blockedByUsers(), $other->id],
        ];

        foreach ($pairs as [$relation, $inverse, $id]) {
            $this->travelTo(now()->startOfSecond());
            ArchivedPivot::attachOrRestore($relation, $id);
            $original = $relation->firstOrFail()->pivot;
            $this->assertTrue($inverse->exists());
            $this->assertTrue(ArchivedPivot::archive($relation, $id));
            $this->assertFalse(ArchivedPivot::archive($relation, $id));
            $this->assertFalse($relation->exists());
            $this->assertFalse($inverse->exists());
            $this->travel(2)->seconds();
            $this->assertTrue(ArchivedPivot::restore($relation, $id));
            $this->assertFalse(ArchivedPivot::restore($relation, $id));
            $later = $relation->firstOrFail()->pivot;
            $this->assertEquals($original->created_at, $later->created_at);
            $this->assertTrue($later->updated_at->greaterThan($original->updated_at));
            ArchivedPivot::archive($relation, $id);
            ArchivedPivot::attachOrRestore($relation, $id);
            $this->assertEquals($original->created_at, $relation->firstOrFail()->pivot->created_at);
            $this->assertSame(1, DB::table($relation->getTable())->count());
            $this->travelBack();
        }
    }

    public function test_catalog_marketplace_and_message_inverse_relationships(): void
    {
        $service = EditorService::factory()->create();
        $work = WorkItem::factory()->forService($service)->create();
        $message = ConversationMessage::factory()
            ->for(Conversation::factory()->for($work, 'workItem'))
            ->create();

        $this->assertTrue($service->workItems()->firstOrFail()->is($work));
        $this->assertTrue($work->contentType->workItems()->firstOrFail()->is($work));
        $this->assertTrue($message->conversation->workItem->is($work));
        $this->assertTrue($message->conversation->messages()->firstOrFail()->is($message));
        $this->assertTrue($message->conversation->participants()->firstOrFail()->is($message->sender));
        $this->assertSame(WorkStatus::Draft, $work->fresh()->status);
    }

    public function test_restricted_foreign_keys_and_marketplace_uniqueness(): void
    {
        $application = JobApplication::factory()->create();
        $deliverable = Deliverable::factory()->create();
        $review = Review::factory()->create();

        $this->rejects(fn () => $application->replicate()->save(), 1062);
        $this->rejects(fn () => $deliverable->replicate()->save(), 1062);
        $this->rejects(fn () => $review->replicate()->save(), 1062);
        $this->rejects(fn () => ContentType::factory()->create(['name' => null]), 1048);
        $this->rejects(fn () => $application->editor->forceDelete(), 1451);
        $this->rejects(fn () => JobApplication::factory()->create(['editor_id' => (string) Str::uuid()]), 1452);
    }

    public function test_reactions_require_exactly_one_target_and_are_unique_per_target(): void
    {
        $postReaction = Reaction::factory()->create();
        $commentReaction = Reaction::factory()->forComment()->create(['user_id' => $postReaction->user_id]);
        $this->rejects(fn () => $postReaction->replicate()->save(), 1062);
        $this->rejects(fn () => $commentReaction->replicate()->save(), 1062);
        $this->rejects(fn () => Reaction::factory()->create(['post_id' => null]), 4025);
        $this->rejects(fn () => Reaction::factory()->create(['comment_id' => $commentReaction->comment_id]), 4025);
    }

    public function test_networking_rejects_self_links_and_duplicate_pairs(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        foreach ([$a->following(), $a->blockedUsers()] as $relation) {
            ArchivedPivot::attachOrRestore($relation, $b->id);
            $row = DB::table($relation->getTable())->first();
            $this->rejects(fn () => DB::table($relation->getTable())->insert((array) $row), 1062);
            $this->rejects(fn () => ArchivedPivot::attachOrRestore($relation, $a->id), 4025);
        }

        $connection = Connection::between($a, $b);
        $this->assertTrue(Connection::between($b, $a)->is($connection));
        $this->rejects(fn () => $connection->replicate()->save(), 1062);
        $this->rejects(fn () => Connection::factory()->create(['requester_id' => $a->id, 'recipient_id' => $a->id]), 4025);
    }

    public function test_financial_references_exact_decimals_and_serialization(): void
    {
        $payment = Payment::factory()->succeeded()->create(['amount' => '9999999999.99']);
        $payout = Payout::factory()->create(['payment_id' => $payment->id]);
        $refund = Refund::factory()->create(['payment_id' => $payment->id]);
        $this->assertSame('9999999999.99', $payment->fresh()->amount);
        $this->assertSame('5.00', $payment->fresh()->platform_fee);
        $this->assertTrue($payment->payouts()->firstOrFail()->is($payout));
        $this->assertTrue($payment->refunds()->firstOrFail()->is($refund));
        $this->assertArrayNotHasKey('provider_payment_id', $payment->toArray());
        $this->rejects(fn () => $payment->replicate()->save(), 1062);
        $this->rejects(fn () => Payout::factory()->create([
            'payment_id' => $payment->id,
            'work_item_id' => WorkItem::factory()->assigned()->create()->id,
        ]), 1452);

        foreach ([$refund, $payout, $payment] as $record) {
            $this->rejects(fn () => DB::table($record->getTable())->where('id', $record->id)->delete(), 1644);
        }

        $this->expectException(LogicException::class);
        $payment->delete();
    }

    public function test_private_keys_and_verification_decisions_are_not_serialized(): void
    {
        $verification = EditorVerificationRequest::factory()->create([
            'document_storage_key' => 'private/document',
            'decision_notes' => 'private decision',
        ]);
        $deliverable = Deliverable::factory()->submitted()->create();
        $this->assertArrayNotHasKey('document_storage_key', $verification->toArray());
        $this->assertArrayNotHasKey('decision_notes', $verification->toArray());
        $this->assertArrayNotHasKey('storage_key', $deliverable->toArray());
        $this->assertFalse($verification->isFillable('status'));
        $this->assertFalse((new User)->isFillable('role'));
        $this->assertFalse((new Payment)->isFillable('status'));
    }

    private function rejects(callable $write, int $errorCode): void
    {
        try {
            $write();
        } catch (QueryException $exception) {
            $this->assertSame($errorCode, $exception->errorInfo[1]);

            return;
        }

        $this->fail("Expected MariaDB to reject the operation with error {$errorCode}.");
    }
}
