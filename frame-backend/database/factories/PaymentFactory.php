<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\WorkItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'work_item_id' => WorkItem::factory()->assigned(),
            'creator_id' => fn (array $attributes) => WorkItem::findOrFail($attributes['work_item_id'])->creator_id,
            'amount' => '35.00',
            'platform_fee' => '5.00',
            'editor_net_amount' => '30.00',
        ];
    }

    public function succeeded(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Succeeded,
            'provider_name' => 'synthetic',
            'provider_payment_id' => 'test-'.fake()->uuid(),
            'paid_at' => now(),
        ]);
    }
}
