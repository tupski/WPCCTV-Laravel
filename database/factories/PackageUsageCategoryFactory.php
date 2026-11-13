<?php

namespace Database\Factories;

use App\Models\PackageUsageCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PackageUsageCategory> */
class PackageUsageCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name, '-'),
        ];
    }
}
