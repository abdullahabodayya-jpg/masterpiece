<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Models\Concerns\RetainsDomainHistory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory, HasUuids, RetainsDomainHistory;

    const UPDATED_AT = null;

    protected $hidden = ['provider_name', 'provider_payment_id', 'failure_reason'];

    protected $attributes = [
        'currency' => 'JOD',
        'platform_fee' => 0,
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'editor_net_amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class, 'work_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'payment_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'payment_id');
    }
}
