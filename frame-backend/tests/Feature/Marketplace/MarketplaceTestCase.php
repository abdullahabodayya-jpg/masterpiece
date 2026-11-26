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

        $expected = $this->expectedDatabase();
        if ($expected === 'frame_payments_test_002') {
            $this->assertTrue($this->app->environment('testing'));
            config(['frame.payment_mode' => 'disabled', 'frame.stripe' => []]);
        }
        $this->assertContains($db->getDriverName(), ['mysql', 'mariadb']);
        $this->assertSame($expected, $db->getDatabaseName());
        foreach ([$db->getPdo(), $db->getReadPdo()] as $pdo) {
            $this->assertSame($expected, $pdo->query('SELECT DATABASE()')->fetchColumn());
            $this->assertStringContainsString('MariaDB', $pdo->getAttribute(PDO::ATTR_SERVER_VERSION));
        }

        if (! self::$migrated) {
            $this->artisan('migrate', ['--force' => true])->assertSuccessful();
            self::$migrated = true;
        }

        $db->beginTransaction();
        $this->beforeApplicationDestroyed(fn () => $db->rollBack());

        $modules = match ($expected) {
            'frame_payments_test_002' => ['profile', 'marketplace', 'verification', 'work', 'messaging', 'payments', 'reviews'],
            'frame_messaging_test_001' => ['profile', 'marketplace', 'verification', 'work', 'messaging', 'payments'],
            'frame_work_test_001' => ['profile', 'marketplace', 'verification', 'work', 'payments'],
            'frame_verification_test_001' => ['profile', 'marketplace', 'verification', 'payments'],
            default => ['profile', 'marketplace'],
        };
        foreach ($modules as $module) {
            Route::prefix('api/v1')->middleware('api')->group(base_path("routes/api/{$module}.php"));
        }
    }

    protected function expectedDatabase(): string
    {
        if (getenv('FRAME_PAYMENTS_REGRESSION') === '1') {
            return 'frame_payments_test_002';
        }

        if (getenv('FRAME_MESSAGING_REGRESSION') === '1') {
            return 'frame_messaging_test_001';
        }

        if (getenv('FRAME_WORK_REGRESSION') === '1') {
            return 'frame_work_test_001';
        }

        return getenv('FRAME_VERIFICATION_REGRESSION') === '1' ? 'frame_verification_test_001' : 'frame_marketplace_test_001';
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
