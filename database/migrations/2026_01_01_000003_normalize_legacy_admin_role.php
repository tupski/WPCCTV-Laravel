<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Normalize the legacy `users.role` value 'admin' → 'administrator'.
 *
 * Extracted from 2026_01_01_000002_create_business_tables.php (audit H3):
 * a DML statement had been appended to the end of a pure-DDL migration, with
 * no down(), so the schema change and the data change could not be reasoned
 * about — or reverted — independently, and the data rewrite was invisible in
 * `git log` for the migration that appeared to only create tables.
 *
 * The value rewrite exists because `2026_01_01_000000_add_role_to_users_table`
 * created `users.role` with `->default('admin')`, while the role system and the
 * `admin` gate use 'administrator'. Any row written before the default was
 * fixed still carries 'admin' and would fail the gate.
 */
return new class extends Migration
{
    /**
     * The value written by the old column default.
     */
    private const LEGACY_ROLE = 'admin';

    /**
     * The value the role system and gates expect.
     */
    private const CURRENT_ROLE = 'administrator';

    public function up(): void
    {
        // Guarded: this migration must be safe on a database where `users`
        // somehow predates the role column, and on re-runs after a rollback.
        if (! $this->hasRoleColumn()) {
            return;
        }

        DB::table('users')
            ->where('role', self::LEGACY_ROLE)
            ->update(['role' => self::CURRENT_ROLE]);
    }

    /**
     * Intentionally a no-op, and that is a deliberate decision rather than an
     * omission.
     *
     * Reversing this rewrite would have to run
     * `UPDATE users SET role = 'admin' WHERE role = 'administrator'`, which is
     * not an inverse: it cannot distinguish rows this migration converted from
     * rows that were legitimately created as 'administrator'. Running it would
     * demote every real administrator and lock them out of the `admin` gate —
     * a rollback that breaks the application is worse than a rollback that
     * does nothing.
     *
     * The rewrite is therefore forward-only. Rolling back the rest of the
     * schema is safe and is handled by the DDL migration's own down(); rolling
     * back *this data change* must come from a database backup. Leaving the
     * value at 'administrator' on a rolled-back schema is harmless: the
     * column retains it, and re-running up() is idempotent.
     */
    public function down(): void
    {
        // No-op by design — see the docblock above.
    }

    private function hasRoleColumn(): bool
    {
        return \Illuminate\Support\Facades\Schema::hasColumn('users', 'role');
    }
};
