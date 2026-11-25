<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserLink extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    const UPDATED_AT = null;

    protected $fillable = ['platform_name', 'title', 'url', 'handle', 'is_primary'];

    protected $attributes = [
        'is_primary' => false,
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
