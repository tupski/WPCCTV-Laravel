<?php

namespace Database\Factories;

use App\Models\WhatsAppClick;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WhatsAppClick>
 */
class WhatsAppClickFactory extends Factory
{
    protected $model = WhatsAppClick::class;

    public function definition(): array
    {
        return [
            'event_id' => (string) Str::uuid(),
            'page_url' => '/products/' . fake()->slug(2),
            'page_title' => fake()->sentence(3),
            'page_type' => fake()->randomElement(WhatsAppClick::PAGE_TYPES),
            'context_id' => null,
            'referrer' => fake()->optional()->url(),
            'utm_source' => fake()->optional()->word(),
            'utm_medium' => fake()->optional()->word(),
            'utm_campaign' => fake()->optional()->word(),
            'device_category' => fake()->randomElement(['desktop', 'tablet', 'mobile']),
            'visitor_token' => fake()->optional()->uuid(),
            'converted_lead_id' => null,
        ];
    }

    public function fromProduct(): static
    {
        return $this->state(fn () => [
            'page_type' => 'product',
            'page_url' => '/products/' . fake()->slug(2),
        ]);
    }

    public function fromPackage(): static
    {
        return $this->state(fn () => [
            'page_type' => 'package',
            'page_url' => '/packages/' . fake()->slug(2),
        ]);
    }

    public function fromHome(): static
    {
        return $this->state(fn () => [
            'page_type' => 'home',
            'page_url' => '/',
        ]);
    }
}
