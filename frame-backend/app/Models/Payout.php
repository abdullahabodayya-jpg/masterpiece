<?php

namespace App\Models;

use App\Enums\PayoutStatus;
use App\Models\Concerns\RetainsDomainHistory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    use HasFactory, HasUuids, RetainsDomainHistory;

    const UPDATED_AT = null;

    protected $hidden = ['provider_payout_id', 'failure_reason'];

    protected $attributes = [
        'currency' => 'JOD',
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PayoutStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class, 'work_item_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
