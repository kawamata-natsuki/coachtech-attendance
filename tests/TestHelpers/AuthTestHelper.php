<?php

namespace Tests\TestHelpers;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

trait AuthTestHelper
{
  // 一般ユーザー（ログイン済）作成
  public function loginUser(array $override = []): User
  {
    $credentials = array_merge([
      'name' => 'user',
      'email' => 'test@example.com',
      'password' => Hash::make('pass1234'),
      'email_verified_at' => now(),
    ], $override);

    $user = User::create($credentials);

    $this->actingAs($user);

    return $user;
  }

  // ゲストユーザー（ログイン前）作成
  public function createUser(array $attributes = []): User
  {
    return User::create(array_merge([
      'name' => 'guest',
      'email' => 'guest@example.com',
      'password' => Hash::make('guest123'),
      'email_verified_at' => now(),
    ], $attributes));
  }

  // 管理者(ログイン済)作成
  public function loginAdmin(array $override = []): Admin
  {
    $admin = Admin::create([
      'name' => 'admin',
      'email' => 'admin@example.com',
      'password' => Hash::make('admin123'),
      'email_verified_at' => now(),
    ]);

    $this->actingAs($admin, 'admin');

    return $admin;
  }
}
