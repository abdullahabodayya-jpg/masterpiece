<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        if (User::where('email', 'admin@frame.test')->exists()) {
            return;
        }
        $admin = new User;

        $admin->name = 'Frame Admin';
        $admin->email = 'admin@frame.test';
        $admin->password_hash = env('FRAME_ADMIN_PASSWORD');
        $admin->role = UserRole::Admin;
        $admin->status = AccountStatus::Active;

        $admin->save();
    }
}
