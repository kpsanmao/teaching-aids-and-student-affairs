<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@lncu.cn'],
            [
                'name' => '系统管理员',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'teacher@lncu.cn'],
            [
                'name' => '示例教师',
                'password' => Hash::make('password'),
                'role' => User::ROLE_TEACHER,
                'email_verified_at' => now(),
            ],
        );
    }
}
