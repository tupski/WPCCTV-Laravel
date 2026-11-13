<?php

namespace Database\Factories;

use App\Models\BlogCategory;
use App\Models\BlogArticle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<BlogArticle> */
class BlogArticleFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->words(4, true);

        return [
            'user_id' => User::factory(),
            'blog_category_id' => null,
            'title' => $title,
            'slug' => Str::slug($title, '-'),
            'excerpt' => fake()->optional(0.7)->sentence(15),
            'body' => fake()->paragraphs(3, true),
            'featured_image' => null,
            'status' => 'draft',
            'published_at' => null,
            'scheduled_for' => null,
            'reading_time' => 0,
            'published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state([
            'status' => 'published',
            'published_at' => now(),
            'published' => true,
        ]);
    }

    public function withCategory(): static
    {
        return $this->state(['blog_category_id' => BlogCategory::factory()]);
    }
}
