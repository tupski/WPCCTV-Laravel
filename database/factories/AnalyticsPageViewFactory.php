<?php

namespace Database\Factories;

use App\Models\AnalyticsPageView;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsPageView>
 */
class AnalyticsPageViewFactory extends Factory
{
    protected $model = AnalyticsPageView::class;

    public function definition(): array
    {
        return [
            'session_id' => fake()->uuid(),
            'page_url' => fake()->randomElement(['/', '/products/hikvision-ds-2cd2041', '/packages/residential-4-camera', '/services/installation', '/blog/cctv-buyers-guide']),
            'page_type' => fake()->randomElement(['home', 'product', 'package', 'service', 'article', null]),
            'ref_domain' => fake()->optional()->domainName(),
            'utm_source' => fake()->optional()->randomElement(['google', 'direct', 'whatsapp']),
            'utm_medium' => fake()->optional()->randomElement(['organic', 'cpc', 'referral']),
            'utm_campaign' => null,
            'device_category' => fake()->randomElement(['desktop', 'tablet', 'mobile']),
            'created_at' => now(),
        ];
    }

    public function fromProduct(): static
    {
        return $this->state(fn () => [
            'page_type' => 'product',
            'page_url' => '/products/' . fake()->slug(2),
        ]);
    }

    public function fromMobile(): static
    {
        return $this->state(fn () => ['device_category' => 'mobile']);
    }
}
