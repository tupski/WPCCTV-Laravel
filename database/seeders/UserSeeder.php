<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo author account for published content.
 *
 * Per DECISIONS_REQUIRED.md §2, no default credential is ever seeded: the
 * account carries a random password nobody knows, so it cannot be logged into
 * — it exists only to satisfy blog_articles.user_id and attribute seeded
 * articles. Real admin accounts are created with `php artisan admin:create`.
 *
 * Idempotent: matched by email, never duplicated.
 */
class UserSeeder extends Seeder
{
    public const DEMO_AUTHOR_EMAIL = 'penulis@wpcctv.co.id';

    public function run(): void
    {
        User::firstOrCreate(
            ['email' => self::DEMO_AUTHOR_EMAIL],
            [
                'name' => 'Tim Konten WP CCTV',
                'password' => bcrypt(Str::random(40)),
                'role' => User::ROLE_CONTENT_EDITOR,
            ],
        );
    }
}
