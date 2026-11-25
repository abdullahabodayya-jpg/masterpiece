<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EditingTool extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public $timestamps = false;

    protected $fillable = ['name'];

    public function workItems(): HasMany
    {
        return $this->hasMany(WorkItem::class, 'editing_tool_id');
    }

    public function editors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'editor_tools', 'editing_tool_id', 'editor_id')
            ->withPivot('deleted_at')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }
}
