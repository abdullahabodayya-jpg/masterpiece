<?php

namespace App\Models;

use App\Enums\CommentStatus;
use App\Models\Concerns\RetainsDomainHistory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    use HasFactory, HasUuids, RetainsDomainHistory;

    const UPDATED_AT = null;

    protected $fillable = ['body'];

    protected $attributes = [
        'status' => 'published',
    ];

    protected function casts(): array
    {
        return [
            'status' => CommentStatus::class,
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class, 'comment_id');
    }
}
