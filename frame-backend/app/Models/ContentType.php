<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentType extends Model
{
    use SoftDeletes, HasUuids, HasFactory;
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
}
