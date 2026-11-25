<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Enums\PostVisibility;
use App\Models\Concerns\RetainsDomainHistory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory, HasUuids, RetainsDomainHistory;

    protected $fillable = ['body', 'visibility'];

    protected $hidden = ['media_storage_key'];

    protected $attributes = [
        'visibility' => 'public',
        'status' => 'published',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => PostVisibility::class,
            'status' => PostStatus::class,
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class, 'post_id');
    }
}
