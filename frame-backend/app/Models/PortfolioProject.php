<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PortfolioProject extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['title', 'description', 'sample_type', 'file_name', 'external_url'];

    protected $hidden = ['storage_key'];

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function contentType(): BelongsTo
    {
        return $this->belongsTo(ContentType::class, 'content_type_id');
    }
}
