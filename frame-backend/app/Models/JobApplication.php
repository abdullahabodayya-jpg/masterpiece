<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Models\Concerns\RetainsDomainHistory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    use HasFactory, HasUuids, RetainsDomainHistory;

    protected $fillable = ['cover_message', 'proposed_price', 'estimated_delivery_days'];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'proposed_price' => 'decimal:2',
            'estimated_delivery_days' => 'integer',
            'status' => ApplicationStatus::class,
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
}
