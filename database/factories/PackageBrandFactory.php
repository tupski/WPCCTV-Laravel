<?php

namespace Database\Factories;

use App\Models\PackageBrand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PackageBrand> */
class PackageBrandFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name, '-'),
            'logo' => null,
            'description' => fake()->optional(0.5)->sentence(8),
        ];
    }
}
