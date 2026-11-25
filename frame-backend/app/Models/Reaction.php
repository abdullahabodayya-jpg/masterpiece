<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reaction extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    const UPDATED_AT = null;

    protected $fillable = ['reaction_type'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'comment_id');
    }
}
