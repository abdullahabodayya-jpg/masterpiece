<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder;
use InvalidArgumentException;

final class ArchivedPivot
{
    public static function attachOrRestore(BelongsToMany $relation, string $relatedId): void
    {
        $keys = self::keys($relation, $relatedId);
        $now = $relation->getParent()->freshTimestampString();
        $values = $keys + ['created_at' => $now, 'updated_at' => $now, 'deleted_at' => null];

        if ($relation->getTable() === 'conversation_participants') {
            $values['joined_at'] = $now;
        }

        self::table($relation)->upsert($values, array_keys($keys), ['updated_at', 'deleted_at']);
    }

    public static function archive(BelongsToMany $relation, string $relatedId): bool
    {
        $now = $relation->getParent()->freshTimestampString();

        return self::table($relation)->where(self::keys($relation, $relatedId))
            ->whereNull('deleted_at')
            ->update(['deleted_at' => $now, 'updated_at' => $now]) === 1;
    }

    public static function restore(BelongsToMany $relation, string $relatedId): bool
    {
        return self::table($relation)->where(self::keys($relation, $relatedId))
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null, 'updated_at' => $relation->getParent()->freshTimestampString()]) === 1;
    }

    private static function keys(BelongsToMany $relation, string $relatedId): array
    {
        $parent = $relation->getParent();

        if (! $parent->exists) {
            throw new InvalidArgumentException('Save the parent before writing a relationship.');
        }

        return [
            $relation->getForeignPivotKeyName() => $parent->getAttribute($relation->getParentKeyName()),
            $relation->getRelatedPivotKeyName() => $relatedId,
        ];
    }

    private static function table(BelongsToMany $relation): Builder
    {
        if (! in_array($relation->getTable(), [
            'user_content_types', 'editor_tools', 'conversation_participants', 'user_follows', 'user_blocks',
        ], true)) {
            throw new InvalidArgumentException('This relationship does not support pivot archiving.');
        }

        return $relation->getParent()->getConnection()->table($relation->getTable());
    }
}
