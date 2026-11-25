<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password_hash',
        'display_name',
        'bio',
        'profile_image_key',
        'cover_image_key',
        'creator_niche',
        'audience_size',
        'audience_size_updated_at',
        'experience_level',
        'years_of_experience',
        'professional_title',
        'is_open_to_work',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_hash' => 'hashed',
            'audience_size_updated_at' => 'datetime',
            'audience_size' => 'integer',
            'years_of_experience' => 'integer',
            'is_open_to_work' => 'boolean',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }
    public function contentTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            ContentType::class,
            'user_content_types',
            'user_id',
            'content_type_id'
        )
            ->withPivot('deleted_at')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }
}
