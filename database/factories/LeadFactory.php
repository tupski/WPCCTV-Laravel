<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsAppClick;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'whatsapp_click_id' => null,
            'customer_name' => fake()->name(),
            'whatsapp_number' => '+62' . fake()->numberBetween(80000000000, 89999999999),
            'lead_type' => fake()->randomElement(Lead::TYPES),
            'requirements' => fake()->paragraph(),
            'status' => Lead::STATUS_NEW,
            'priority' => null,
            'notes' => null,
            'follow_up_at' => null,
            'assigned_to' => null,
            'converted_at' => null,
        ];
    }

    public function fresh(): static
    {
        return $this->state(fn () => ['status' => Lead::STATUS_NEW]);
    }

    public function qualified(): static
    {
        return $this->state(fn () => ['status' => Lead::STATUS_QUALIFIED]);
    }

    public function won(): static
    {
        return $this->state(fn () => [
            'status' => Lead::STATUS_WON,
            'converted_at' => now(),
        ]);
    }

    public function lost(): static
    {
        return $this->state(fn () => ['status' => Lead::STATUS_LOST]);
    }

    public function fromWhatsAppClick(): static
    {
        return $this->state(fn () => [
            'whatsapp_click_id' => WhatsAppClick::factory(),
        ]);
    }

    public function assignedTo(): static
    {
        return $this->state(fn () => [
            'assigned_to' => User::factory(),
        ]);
    }
}
