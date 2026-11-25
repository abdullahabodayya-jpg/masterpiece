<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentType extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_content_types',
            'content_type_id',
            'user_id'
        )
            ->withPivot('deleted_at')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    public function portfolioProjects(): HasMany
    {
        return $this->hasMany(PortfolioProject::class, 'content_type_id');
    }

    public function editorServices(): HasMany
    {
        return $this->hasMany(EditorService::class, 'content_type_id');
    }

    public function workItems(): HasMany
    {
        return $this->hasMany(WorkItem::class, 'content_type_id');
    }
}
