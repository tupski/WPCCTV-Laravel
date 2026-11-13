<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Faq> */
class FaqFactory extends Factory
{
    public function definition(): array
    {
        return [
            'context_type' => null,
            'context_id' => null,
            'question' => fake()->sentence(8),
            'answer' => fake()->text(200),
            'sort_order' => 0,
            'published' => true,
        ];
    }
}
