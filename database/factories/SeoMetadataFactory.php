<?php

namespace Database\Factories;

use App\Models\SeoMetadata;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SeoMetadata> */
class SeoMetadataFactory extends Factory
{
    public function definition(): array
    {
        return [
            'seoable_type' => null,
            'seoable_id' => null,
            'seo_title' => fake()->optional(0.5)->sentence(6),
            'meta_description' => fake()->optional(0.5)->sentence(15),
            'canonical' => null,
            'robots' => null,
            'og_title' => null,
            'og_description' => null,
            'og_image' => null,
            'og_type' => null,
            'twitter_card' => null,
            'twitter_title' => null,
            'twitter_description' => null,
            'twitter_image' => null,
        ];
    }
}
