<?php

namespace Tests\TestHelpers;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

trait AuthTestHelper
{
  // 一般ユーザー（ログイン前）作成
  public function createUser(array $override = []): User
  {
    return User::create(array_merge([
      'name' => 'guest',
      'email' => 'guest@example.com',
      'password' => Hash::make('guest123'),
      'email_verified_at' => now(),
    ], $override));
  }

  // 一般ユーザー（ログイン済）作成
  public function loginUser(array $override = []): User
  {
    $user = $this->createUser($override);
    $this->actingAs($user);
    return $user;
  }

  // 管理者(ログイン前)作成
  public function createAdmin(array $override = []): Admin
  {
    return Admin::create(array_merge([
      'name' => 'admin',
      'email' => 'admin@example.com',
      'password' => Hash::make('admin123'),
      'email_verified_at' => now(),
    ], $override));
  }

  // 管理者(ログイン済)作成
  public function loginAdmin(array $override = []): Admin
  {
    $admin = $this->createAdmin($override);
    $this->actingAs($admin, 'admin');
    return $admin;
  }
}
