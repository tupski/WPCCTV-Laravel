<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Page> */
class PageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title' => $title,
            'slug' => Str::slug($title, '-'),
            'body' => fake()->paragraphs(2, true),
            'published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(['published' => true]);
    }
}
