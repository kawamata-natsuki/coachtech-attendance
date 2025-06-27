<?php

namespace Tests\TestHelpers;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

trait AuthTestHelper
{
  // =========================================================
  // 一般ユーザー関連
  // =========================================================

  // 一般ユーザー（ログイン前）
  public function createUser(): User
  {
    return User::create([
      'name' => 'guest',
      'email' => 'guest@example.com',
      'password' => Hash::make('guest123'),
      'email_verified_at' => now(),
    ]);
  }

  // 一般ユーザー（ログイン済、未出勤）
  public function loginUser(): User
  {
    $user = $this->createUser();
    $user->email_verified_at = now();
    $user->save();

    $this->actingAs($user);
    return $user;
  }

  // 一般ユーザー（ログイン済、出勤中）
  public function loginWorkingUser(): User
  {
    $user = $this->createUser();
    $user->email_verified_at = now();
    $user->save();

    Attendance::create([
      'user_id' => $user->id,
      'work_date' => today(),
      'clock_in' => now()->subHour(),
    ]);

    $this->actingAs($user);
    return $user;
  }

  // 一般ユーザー（ログイン済、休憩中）
  public function loginOnBreakUser(): User
  {
    $user = $this->createUser();
    $user->email_verified_at = now();
    $user->save();

    $attendance = Attendance::create([
      'user_id' => $user->id,
      'work_date' => today(),
      'clock_in' => now()->subHours(3),
    ]);

    $attendance->breakTimes()->create([
      'break_start' => now()->subMinutes(30),
      'break_end' => null,
    ]);

    $this->actingAs($user);
    return $user;
  }

  // 一般ユーザー（ログイン済、退勤済）
  public function loginClockedOutUser(): User
  {
    $user = $this->createUser();
    $user->email_verified_at = now();
    $user->save();

    Attendance::create([
      'user_id' => $user->id,
      'work_date' => today(),
      'clock_in' => now()->subHours(8),
      'clock_out' => now()->subHour(),
    ]);

    $this->actingAs($user);
    return $user;
  }

  // =========================================================
  // 管理者関連
  // =========================================================

  // 管理者(ログイン前)
  public function createAdmin(array $override = []): Admin
  {
    return Admin::create(array_merge([
      'name' => 'admin',
      'email' => 'admin@example.com',
      'password' => Hash::make('admin123'),
      'email_verified_at' => now(),
    ], $override));
  }

  // 管理者(ログイン済)
  public function loginAdmin(array $override = []): Admin
  {
    $admin = $this->createAdmin($override);
    $this->actingAs($admin, 'admin');
    return $admin;
  }
}
