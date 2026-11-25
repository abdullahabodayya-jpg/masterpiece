<?php

namespace Tests\Feature\Community;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PDO;
use Tests\TestCase;

abstract class CommunityTestCase extends TestCase
{
    private static bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = DB::connection();
        if ($connection->getDriverName() === 'sqlite' && $connection->getDatabaseName() === ':memory:') {
            $this->markTestSkipped('Run with the dedicated MariaDB schema frame_community_test_001.');
        }

        $this->assertContains($connection->getDriverName(), ['mysql', 'mariadb']);
        $this->assertSame('frame_community_test_001', $connection->getDatabaseName());
        $pdo = $connection->getPdo();
        $this->assertSame('frame_community_test_001', $pdo->query('SELECT DATABASE()')->fetchColumn());
        $this->assertStringContainsString('MariaDB', $pdo->getAttribute(PDO::ATTR_SERVER_VERSION));
        $this->assertSame('frame_community_test_001', $connection->getReadPdo()->query('SELECT DATABASE()')->fetchColumn());

        if (! self::$migrated) {
            $this->artisan('migrate', ['--force' => true])->assertSuccessful();
            self::$migrated = true;
        }

        $connection->beginTransaction();
        $this->beforeApplicationDestroyed(fn () => $connection->rollBack());

        Route::prefix('api/v1')->middleware('api')->group(base_path('routes/api/community.php'));
    }

    protected function signIn(?User $user = null): User
    {
        $user ??= User::factory()->creator()->create();
        $this->app['auth']->forgetGuards();
        $this->actingAs($user, 'web');

        return $user;
    }

    protected function queryCount(Closure $request): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $request();

            return count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }
}
