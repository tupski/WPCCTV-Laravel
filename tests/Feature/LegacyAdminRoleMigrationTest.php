<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regression coverage for PHASE_4_CLOSURE_AUDIT.md H3.
 *
 * Root cause confirmed: a `users.role` rewrite ('admin' → 'administrator') sat
 * at the end of 2026_01_01_000002_create_business_tables.php — a pure-DDL
 * migration — with no down() covering the data change. A schema migration that
 * silently mutates business data is invisible in `git log` and cannot be
 * reverted or reasoned about on its own.
 */
class LegacyAdminRoleMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const DATA_MIGRATION = '2026_01_01_000003_normalize_legacy_admin_role';

    private const DDL_MIGRATION = '2026_01_01_000002_create_business_tables';

    public function test_data_migration_exists_as_its_own_file(): void
    {
        $files = glob(database_path('migrations/*normalize_legacy_admin_role.php'));

        $this->assertCount(1, $files, 'The role rewrite must live in a dedicated migration file.');
    }

    /**
     * The DDL migration must no longer contain the DML statement.
     */
    public function test_ddl_migration_no_longer_rewrites_user_roles(): void
    {
        $source = file_get_contents(
            database_path('migrations/2026_01_01_000002_create_business_tables.php')
        );

        $this->assertStringNotContainsString("'administrator'", $source);
        $this->assertStringNotContainsString('where(\'role\', \'admin\')', $source);
        $this->assertStringNotContainsString('SELECT role FROM users GROUP BY role', $source);
    }

    public function test_data_migration_converts_the_legacy_role_and_is_idempotent(): void
    {
        $legacy = User::factory()->create(['role' => 'admin']);
        $modern = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);

        // Re-run the data migration over a table that already contains the row.
        (include database_path('migrations/2026_01_01_000003_normalize_legacy_admin_role.php'))->up();

        $this->assertSame('administrator', $legacy->fresh()->role);
        $this->assertSame('administrator', $modern->fresh()->role);

        // Idempotent: a second run changes nothing and does not error.
        (include database_path('migrations/2026_01_01_000003_normalize_legacy_admin_role.php'))->up();

        $this->assertSame(2, User::count());
        $this->assertSame(0, User::where('role', 'admin')->count());
    }

    /**
     * The documented, deliberate rollback decision: rolling back the data
     * migration must NOT demote real administrators.
     */
    public function test_data_migration_down_is_a_documented_no_op(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);

        (include database_path('migrations/2026_01_01_000003_normalize_legacy_admin_role.php'))->down();

        $this->assertSame(
            'administrator',
            $admin->fresh()->role,
            'down() must not demote administrators — the rewrite is forward-only by design.'
        );

        $source = file_get_contents(
            database_path('migrations/2026_01_01_000003_normalize_legacy_admin_role.php')
        );
        $this->assertStringContainsString('No-op by design', $source);
    }

    /**
     * Acceptance criterion: migrate → rollback → migrate round-trips on SQLite.
     *
     * This exercises the real Artisan commands against the test database, not
     * a hand-rolled simulation.
     */
    public function test_migrate_rollback_migrate_round_trips_cleanly(): void
    {
        // RefreshDatabase already migrated; verify the current state first.
        $this->assertTrue(Schema::hasTable('whatsapp_clicks'));
        $this->assertTrue(Schema::hasTable('notification_preferences'));

        $rollback = Artisan::call('migrate:rollback', ['--force' => true]);
        $rollbackOutput = Artisan::output();

        $this->assertSame(0, $rollback, "migrate:rollback failed:\n{$rollbackOutput}");

        // A bare rollback reverses every batch, so all app tables go.
        $this->assertFalse(Schema::hasTable('whatsapp_clicks'), 'Rollback must drop the business tables.');
        $this->assertFalse(Schema::hasTable('notification_preferences'));
        $this->assertFalse(Schema::hasTable('users'));

        $remigrate = Artisan::call('migrate', ['--force' => true]);
        $remigrateOutput = Artisan::output();

        $this->assertSame(0, $remigrate, "migrate after rollback failed:\n{$remigrateOutput}");
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('whatsapp_clicks'));
        $this->assertTrue(Schema::hasTable('notification_preferences'));
    }

    /**
     * Both migrations must expose a callable down().
     */
    public function test_both_migrations_define_down_methods(): void
    {
        foreach ([
            'migrations/2026_01_01_000002_create_business_tables.php',
            'migrations/2026_01_01_000003_normalize_legacy_admin_role.php',
        ] as $relative) {
            $migration = include database_path($relative);

            $this->assertTrue(
                method_exists($migration, 'down'),
                "{$relative} must define down()."
            );

            $reflection = new \ReflectionMethod($migration, 'down');
            $this->assertSame(0, $reflection->getNumberOfRequiredParameters());
        }
    }

    /**
     * The DDL down() must be driver-aware: SQLite cannot drop an FK by name.
     *
     * The migration file is only required once — anonymous class instances are
     * distinct per include, so reflect on a single instance.
     */
    public function test_ddl_down_handles_sqlite_foreign_key_limitation(): void
    {
        $migration = include database_path('migrations/2026_01_01_000002_create_business_tables.php');

        $method = new \ReflectionMethod($migration, 'canDropForeignKeysByName');
        $method->setAccessible(true);

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertFalse(
            $method->invoke($migration),
            'SQLite must skip named FK drops; the driver-aware guard is what makes the round-trip work.'
        );

        $source = file_get_contents(
            database_path('migrations/2026_01_01_000002_create_business_tables.php')
        );

        $this->assertStringContainsString('canDropForeignKeysByName', $source);
        $this->assertStringContainsString("'sqlite'", $source);
    }
}
