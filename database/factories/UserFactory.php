<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => User::ROLE_ADMINISTRATOR,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user has the super_admin role.
     */
    public function superAdmin(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_SUPER_ADMIN]);
    }

    /**
     * Indicate that the user has the administrator role.
     */
    public function administrator(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_ADMINISTRATOR]);
    }

    /**
     * Indicate that the user has the content_editor role.
     */
    public function contentEditor(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_CONTENT_EDITOR]);
    }

    /**
     * Indicate that the user has the lead_manager role.
     */
    public function leadManager(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_LEAD_MANAGER]);
    }

    /**
     * Indicate that the user has the analyst role.
     */
    public function analyst(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_ANALYST]);
    }
}
