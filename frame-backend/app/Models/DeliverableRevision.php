<?php

namespace App\Models;

use App\Enums\RevisionStatus;
use App\Models\Concerns\RetainsDomainHistory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliverableRevision extends Model
{
    use HasFactory, HasUuids, RetainsDomainHistory;

    public $timestamps = false;

    protected $fillable = ['reason', 'details'];

    protected $attributes = [
        'status' => 'requested',
    ];

    protected function casts(): array
    {
        return [
            'status' => RevisionStatus::class,
            'requested_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(Deliverable::class, 'deliverable_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}
