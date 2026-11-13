<?php

namespace Database\Factories;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MediaAsset> */
class MediaAssetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'file_path' => 'media/' . fake()->unique()->uuid() . '.jpg',
            'disk' => 'public',
            'type' => 'image',
            'alt_text' => fake()->optional(0.5)->sentence(8),
            'size' => fake()->numberBetween(10000, 5000000),
            'width' => fake()->numberBetween(100, 4000),
            'height' => fake()->numberBetween(100, 4000),
            'mime_type' => 'image/jpeg',
            'created_by' => User::factory(),
        ];
    }
}
