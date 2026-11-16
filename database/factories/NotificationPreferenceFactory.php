<?php

namespace Database\Factories;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationPreference>
 */
class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'whatsapp_clicks_enabled' => true,
            'lead_updates_enabled' => true,
            'updated_at' => now(),
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn () => [
            'whatsapp_clicks_enabled' => false,
            'lead_updates_enabled' => false,
        ]);
    }
}
