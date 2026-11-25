<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory()->succeeded(),
            'requested_by_user_id' => fn (array $attributes) => Payment::findOrFail($attributes['payment_id'])->creator_id,
            'amount' => '10.00',
        ];
    }
}
