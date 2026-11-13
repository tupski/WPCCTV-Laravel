<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductBrand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'brand_id' => ProductBrand::factory(),
            'name' => $name,
            'slug' => Str::slug($name, '-'),
            'model' => fake()->optional(0.4)->word(),
            'camera_type' => fake()->randomElement(['indoor', 'outdoor', 'dome', 'bullet', 'ptz']),
            'description' => fake()->optional(0.7)->text(200),
            'key_features' => null,
            'technical_specs' => null,
            'recommended_applications' => null,
            'image' => null,
            'gallery' => null,
            'published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(['published' => true]);
    }
}
