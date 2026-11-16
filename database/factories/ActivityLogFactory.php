<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'action' => fake()->randomElement(['created', 'updated', 'deleted', 'login', 'export']),
            'subject_type' => null,
            'subject_id' => null,
            'context' => null,
            'ip_address' => fake()->ipv4(),
            'created_at' => now(),
        ];
    }

    public function byUser(): static
    {
        return $this->state(fn () => [
            'user_id' => User::factory(),
        ]);
    }

    public function forSubject(string $class, int $id): static
    {
        return $this->state(fn () => [
            'subject_type' => $class,
            'subject_id' => $id,
        ]);
    }
}
