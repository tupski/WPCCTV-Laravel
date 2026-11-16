<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Models\WhatsAppClick;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadActivity>
 */
class LeadActivityFactory extends Factory
{
    protected $model = LeadActivity::class;

    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'user_id' => null,
            'action' => fake()->randomElement(LeadActivity::ACTIONS),
            'field' => null,
            'old_value' => null,
            'new_value' => null,
            'whatsapp_click_id' => null,
            'created_at' => now(),
        ];
    }

    public function statusChange(): static
    {
        return $this->state(fn () => [
            'action' => LeadActivity::ACTION_STATUS_CHANGE,
            'field' => 'status',
            'old_value' => 'new',
            'new_value' => 'contacted',
        ]);
    }

    public function note(): static
    {
        return $this->state(fn () => [
            'action' => LeadActivity::ACTION_NOTE,
            'new_value' => fake()->sentence(),
        ]);
    }

    public function byUser(): static
    {
        return $this->state(fn () => [
            'user_id' => User::factory(),
        ]);
    }

    public function fromWhatsAppClick(): static
    {
        return $this->state(fn () => [
            'whatsapp_click_id' => WhatsAppClick::factory(),
        ]);
    }
}
