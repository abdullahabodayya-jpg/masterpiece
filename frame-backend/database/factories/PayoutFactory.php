<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Payout;
use App\Models\WorkItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payout>
 */
class PayoutFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory()->succeeded(),
            'work_item_id' => fn (array $attributes) => Payment::findOrFail($attributes['payment_id'])->work_item_id,
            'editor_id' => fn (array $attributes) => WorkItem::findOrFail($attributes['work_item_id'])->editor_id,
            'amount' => '30.00',
        ];
    }
}
