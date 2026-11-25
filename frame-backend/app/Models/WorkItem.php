<?php

namespace App\Models;

use App\Enums\WorkStatus;
use App\Enums\WorkType;
use App\Models\Concerns\RetainsDomainHistory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkItem extends Model
{
    use HasFactory, HasUuids, RetainsDomainHistory;

    protected $fillable = ['title', 'description', 'budget_min', 'budget_max', 'quantity', 'deadline'];

    protected $attributes = [
        'type' => 'job',
        'currency' => 'JOD',
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'type' => WorkType::class,
            'budget_min' => 'decimal:2',
            'budget_max' => 'decimal:2',
            'agreed_price' => 'decimal:2',
            'quantity' => 'integer',
            'deadline' => 'datetime',
            'status' => WorkStatus::class,
            'agreed_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function editorService(): BelongsTo
    {
        return $this->belongsTo(EditorService::class, 'editor_service_id');
    }

    public function contentType(): BelongsTo
    {
        return $this->belongsTo(ContentType::class, 'content_type_id');
    }

    public function editingTool(): BelongsTo
    {
        return $this->belongsTo(EditingTool::class, 'editing_tool_id');
    }

    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'work_item_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'work_item_id');
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(Deliverable::class, 'work_item_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'work_item_id');
    }

    public function payout(): HasOne
    {
        return $this->hasOne(Payout::class, 'work_item_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'work_item_id');
    }
}
