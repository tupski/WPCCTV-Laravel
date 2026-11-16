<?php

namespace Database\Factories;

use App\Models\AnalyticsDaily;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsDaily>
 */
class AnalyticsDailyFactory extends Factory
{
    protected $model = AnalyticsDaily::class;

    public function definition(): array
    {
        return [
            'date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'page_views' => fake()->numberBetween(0, 500),
            'unique_sessions' => fake()->numberBetween(0, 200),
            'whatsapp_clicks' => fake()->numberBetween(0, 50),
            'leads_created' => fake()->numberBetween(0, 20),
            'leads_qualified' => fake()->numberBetween(0, 10),
            'leads_won' => fake()->numberBetween(0, 5),
            'top_product_id' => null,
            'top_package_id' => null,
            'top_article_id' => null,
        ];
    }
}
