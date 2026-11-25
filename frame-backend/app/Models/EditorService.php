<?php

namespace App\Models;

use App\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EditorService extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['title', 'description', 'price', 'currency', 'delivery_days'];

    protected $attributes = [
        'currency' => 'JOD',
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'delivery_days' => 'integer',
            'status' => ServiceStatus::class,
        ];
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function contentType(): BelongsTo
    {
        return $this->belongsTo(ContentType::class, 'content_type_id');
    }

    public function workItems(): HasMany
    {
        return $this->hasMany(WorkItem::class, 'editor_service_id');
    }
}
