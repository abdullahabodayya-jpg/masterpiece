<?php

namespace App\Services\Profile;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Marketplace\MarketplaceAccess;
use App\Support\ArchivedPivot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;

final class ProfileService
{
    public function __construct(private readonly MarketplaceAccess $access) {}

    public function profiles(?User $viewer, UserRole $role): Builder
    {
        return $this->access->people(User::query(), $viewer, $role)->with($this->relations());
    }

    public function update(User $actor, array $data): User
    {
        return $this->access->write($actor, $actor->role, function (User $user) use ($data) {
            $user->fill(Arr::except($data, ['content_type_ids', 'editing_tool_ids']));
            if ($user->isDirty('audience_size')) {
                $user->audience_size_updated_at = now();
            }
            $user->save();

            if (array_key_exists('content_type_ids', $data)) {
                $this->replace($user->contentTypes(), $data['content_type_ids']);
            }
            if (array_key_exists('editing_tool_ids', $data)) {
                $this->replace($user->editingTools(), $data['editing_tool_ids']);
            }

            return $user->load($this->relations());
        });
    }

    public function relations(): array
    {
        return ['contentTypes:id,name', 'editingTools:id,name'];
    }

    private function replace(BelongsToMany $relation, array $ids): void
    {
        $ids = array_map('strtolower', $ids);
        $current = $relation->pluck($relation->getRelated()->qualifyColumn('id'))->all();

        foreach (array_diff($current, $ids) as $id) {
            ArchivedPivot::archive($relation, $id);
        }
        foreach (array_diff($ids, $current) as $id) {
            ArchivedPivot::attachOrRestore($relation, $id);
        }
    }
}
