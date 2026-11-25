<?php

namespace App\Models;

use App\Enums\ConnectionStatus;
use App\Models\Concerns\RetainsDomainHistory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Connection extends Model
{
    use HasFactory, HasUuids, RetainsDomainHistory;

    const UPDATED_AT = null;

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'status' => ConnectionStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public static function between(User $requester, User $recipient): self
    {
        $requesterId = $requester->getKey();
        $recipientId = $recipient->getKey();

        if (! $requester->exists || ! $recipient->exists || $requesterId === $recipientId) {
            throw ValidationException::withMessages(['recipient' => 'Choose a different, saved user.']);
        }

        $record = new self;
        $record->setConnection($requester->getConnectionName());

        return $requester->getConnection()->transaction(function () use ($record, $requester, $requesterId, $recipientId) {
            // Lock both users in the same order for either request direction.
            $ids = [$requesterId, $recipientId];
            sort($ids, SORT_STRING);

            foreach ($ids as $id) {
                $requester->newQuery()->lockForUpdate()->findOrFail($id);
            }

            $existing = $record->newQuery()
                ->whereIn('requester_id', $ids)
                ->whereIn('recipient_id', $ids)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $record->requester_id = $requesterId;
            $record->recipient_id = $recipientId;
            $record->save();

            return $record;
        }, 3);
    }
}
