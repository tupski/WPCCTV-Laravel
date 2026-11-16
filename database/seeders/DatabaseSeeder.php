<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Every seeder is idempotent and safe to run repeatedly: entities are
     * matched by their unique key (slug, type, question, email, settings key)
     * via firstOrCreate/updateOrCreate, so `db:seed` twice and
     * `migrate:fresh --seed` both succeed.
     */
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            UserSeeder::class,
            ProductSeeder::class,
            PackageSeeder::class,
            ServiceSeeder::class,
            BlogSeeder::class,
            PageSeeder::class,
        ]);
    }
}
