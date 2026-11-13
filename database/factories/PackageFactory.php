<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Package> */
class PackageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'brand_id' => null,
            'title' => $title,
            'slug' => Str::slug($title, '-'),
            'description' => fake()->optional(0.7)->text(300),
            'hero_image' => null,
            'camera_quantity' => fake()->randomElement([1, 2, 4, 8, 16]),
            'included_equipment' => null,
            'installation_scope' => null,
            'relevant_specs' => null,
            'installation_process' => null,
            'exclusions' => null,
            'price' => null,
            'published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(['published' => true]);
    }
}
