<?php

namespace Database\Factories;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(3),
            'value' => fake()->randomElement(['enabled', 'disabled']),
            'updated_by' => null,
        ];
    }

    public function forUser(): static
    {
        return $this->state(fn () => [
            'updated_by' => User::factory(),
        ]);
    }
}
