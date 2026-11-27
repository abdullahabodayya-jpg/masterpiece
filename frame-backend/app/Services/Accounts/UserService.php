<?php

namespace App\Services\Accounts;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;

class UserService
{
    public function create(array $data): User
    {
        $user = new User;

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password_hash = $data['password'];
        $user->role = UserRole::from($data['role']);
        $user->status = AccountStatus::PendingVerification;

        $user->save();

        return $user;
    }
}