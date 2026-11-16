<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Brand and operational settings for the public website.
 *
 * Values mirror config/site.php defaults so the site renders correctly both
 * with and without the settings rows. Admin-editable keys (whatsapp_number,
 * retention) are NOT touched here: retention is provisional policy owned by
 * the operator, and overwriting it on every seed would clobber operator
 * decisions. Idempotent via firstOrCreate on the unique `key` column.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'site_name' => 'WP CCTV Indonesia',
            'site_tagline' => 'SMART SECURITY SOLUTIONS',
            'whatsapp_number' => '6287811556995',
            'contact_email' => 'halo@wpcctv.co.id',
            'contact_locality' => 'Jakarta Timur',
            'contact_region' => 'DKI Jakarta',
            'operating_hours' => 'Senin–Sabtu, 08.00–18.00 WIB',
            'service_areas' => ['Jakarta', 'Bogor', 'Depok', 'Tangerang', 'Bekasi', 'Bandung'],
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'updated_by' => null],
            );
        }
    }
}
