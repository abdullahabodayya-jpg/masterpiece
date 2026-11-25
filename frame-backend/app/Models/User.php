<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\AccountStatus;
use App\Enums\EditorExperienceLevel;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

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
        'profile_image_key',
        'cover_image_key',
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
            'role' => UserRole::class,
            'status' => AccountStatus::class,
            'experience_level' => EditorExperienceLevel::class,
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

    public function userLinks(): HasMany
    {
        return $this->hasMany(UserLink::class, 'user_id');
    }

    public function portfolioProjects(): HasMany
    {
        return $this->hasMany(PortfolioProject::class, 'editor_id');
    }

    public function editorServices(): HasMany
    {
        return $this->hasMany(EditorService::class, 'editor_id');
    }

    public function createdWorkItems(): HasMany
    {
        return $this->hasMany(WorkItem::class, 'creator_id');
    }

    public function assignedWorkItems(): HasMany
    {
        return $this->hasMany(WorkItem::class, 'editor_id');
    }

    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'editor_id');
    }

    public function createdConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'created_by_user_id');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class, 'sender_id');
    }

    public function deliverableRevisions(): HasMany
    {
        return $this->hasMany(DeliverableRevision::class, 'requested_by_user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'creator_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'editor_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'requested_by_user_id');
    }

    public function writtenReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_user_id');
    }

    public function receivedReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewed_user_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'author_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class, 'user_id');
    }

    public function sentConnections(): HasMany
    {
        return $this->hasMany(Connection::class, 'requester_id');
    }

    public function receivedConnections(): HasMany
    {
        return $this->hasMany(Connection::class, 'recipient_id');
    }

    public function editorVerificationRequests(): HasMany
    {
        return $this->hasMany(EditorVerificationRequest::class, 'editor_id');
    }

    public function reviewedVerificationRequests(): HasMany
    {
        return $this->hasMany(EditorVerificationRequest::class, 'reviewed_by_user_id');
    }

    public function editingTools(): BelongsToMany
    {
        return $this->belongsToMany(EditingTool::class, 'editor_tools', 'editor_id', 'editing_tool_id')
            ->withPivot('deleted_at')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants', 'user_id', 'conversation_id')
            ->withPivot('joined_at', 'last_read_at', 'deleted_at')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_follows', 'follower_id', 'following_id')
            ->withPivot('deleted_at')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_follows', 'following_id', 'follower_id')
            ->withPivot('deleted_at')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_blocks', 'blocker_id', 'blockee_id')
            ->withPivot('deleted_at')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    public function blockedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_blocks', 'blockee_id', 'blocker_id')
            ->withPivot('deleted_at')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }
}
