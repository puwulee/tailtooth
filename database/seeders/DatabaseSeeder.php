<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * 建立第一個平台管理者（可用以登入後台輸入 API 金鑰、開賽事）。
     * 帳密可用 ADMIN_EMAIL / ADMIN_PASSWORD 覆寫。
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@tailtooth.local')],
            ['name' => '平台管理者', 'password' => Hash::make(env('ADMIN_PASSWORD', 'password'))],
        );

        foreach ([UserRole::Platform, UserRole::System, UserRole::Organizer] as $role) {
            $admin->assignRole($role);
        }
    }
}
