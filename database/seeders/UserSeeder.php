<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => '西 怜奈',
                'email' => 'reina.n@coachtech.com',
            ],
            [
                'name' => '山田 太郎',
                'email' => 'taro.y@coachtech.com',
            ],
            [
                'name' => '増田 一世',
                'email' => 'issei.m@coachtech.com',
            ],
            [
                'name' => '山田 敬吉',
                'email' => 'keikichi.y@coachtech.com',
            ],
            [
                'name' => '秋田 朋美',
                'email' => 'tomomi.a@coachtech.com',
            ],
            [
                'name' => '中西 教夫',
                'email' => 'norio.n@coachtech.com',
            ],
        ];

        foreach ($users as $user) {
            User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]);
        }

        // 管理者ユーザー
        User::create([
            'name' => 'ADMIN',
            'email' => 'admin@coachtech.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }
}
