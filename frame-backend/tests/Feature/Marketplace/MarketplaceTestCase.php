<?php

namespace Tests\Feature\Marketplace;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PDO;
use Tests\TestCase;

abstract class MarketplaceTestCase extends TestCase
{
    private static bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        $db = DB::connection();
        if ($db->getDriverName() === 'sqlite' && $db->getDatabaseName() === ':memory:') {
            $this->markTestSkipped('Use the isolated frame_marketplace_test_001 MariaDB schema.');
        }

        $this->assertContains($db->getDriverName(), ['mysql', 'mariadb']);
        $this->assertSame('frame_marketplace_test_001', $db->getDatabaseName());
        foreach ([$db->getPdo(), $db->getReadPdo()] as $pdo) {
            $this->assertSame('frame_marketplace_test_001', $pdo->query('SELECT DATABASE()')->fetchColumn());
            $this->assertStringContainsString('MariaDB', $pdo->getAttribute(PDO::ATTR_SERVER_VERSION));
        }

        if (! self::$migrated) {
            $this->artisan('migrate', ['--force' => true])->assertSuccessful();
            self::$migrated = true;
        }

        $db->beginTransaction();
        $this->beforeApplicationDestroyed(fn () => $db->rollBack());

        foreach (['profile', 'marketplace'] as $module) {
            Route::prefix('api/v1')->middleware('api')->group(base_path("routes/api/{$module}.php"));
        }
    }

    protected function signIn(User $user): User
    {
        $this->app['auth']->forgetGuards();
        $this->actingAs($user, 'web');

        return $user;
    }

    protected function queryCount(Closure $operation): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $operation();

            return count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }
}
