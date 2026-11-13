<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Service> */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);
        $type = fake()->randomElement(['installation', 'repair', 'product_only']);

        return [
            'type' => $type,
            'title' => $title,
            'slug' => Str::slug($title, '-'),
            'description' => fake()->optional(0.7)->text(300),
            'scope' => null,
            'applications' => null,
            'process' => null,
            'published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(['published' => true]);
    }
}
