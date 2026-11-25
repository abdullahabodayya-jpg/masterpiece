<?php

namespace App\Models;

use App\Enums\DeliverableStatus;
use App\Models\Concerns\RetainsDomainHistory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deliverable extends Model
{
    use HasFactory, HasUuids, RetainsDomainHistory;

    protected $fillable = ['title', 'description', 'due_at'];

    protected $hidden = ['storage_key', 'review_notes'];

    protected $attributes = [
        'sequence_number' => 1,
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
            'status' => DeliverableStatus::class,
            'due_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class, 'work_item_id');
    }

    public function deliverableRevisions(): HasMany
    {
        return $this->hasMany(DeliverableRevision::class, 'deliverable_id');
    }
}
