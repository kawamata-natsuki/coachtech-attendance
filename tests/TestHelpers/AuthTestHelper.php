<?php

namespace Tests\TestHelpers;

use App\Enums\WorkStatus;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

trait AuthTestHelper
{
  /**
   * 指定ユーザーで当日の勤怠データを作成するヘルパー
   */
  private function createTodayAttendanceWithBreakTime($user): Attendance
  {
    return Attendance::factory()
      ->for($user)
      ->withBreakTime()
      ->create([
        'work_date' => today()->toDateString(),
      ]);
  }

  // =========================================================
  // 一般ユーザー関連
  // =========================================================

  // 一般ユーザー（ログイン前）
  public function createUser(): User
  {
    return User::firstOrCreate(
      ['email' => 'guest@example.com'],
      [
        'name' => 'guest',
        'password' => Hash::make('guest123'),
        'email_verified_at' => now(),
      ]
    );
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
      'clock_in' => now()->setTime(9, 0),
      'work_status' => WorkStatus::WORKING,
    ]);

    $this->actingAs($user);
    return $user;
  }

  // 一般ユーザー（ログイン済、休憩中）
  public function loginBreakUser(): User
  {
    $user = $this->createUser();
    $user->email_verified_at = now();
    $user->save();

    $attendance = Attendance::create([
      'user_id' => $user->id,
      'work_date' => today(),
      'clock_in' => now()->setTime(9, 0),
      'work_status' => WorkStatus::BREAK,
    ]);

    $attendance->breakTimes()->create([
      'break_start' => now()->subMinutes(30),
      'break_end' => null,
    ]);

    $this->actingAs($user);
    return $user;
  }

  // 一般ユーザー（ログイン済、退勤済）
  public function loginCompletedUser(): User
  {
    $user = $this->createUser();
    $user->email_verified_at = now();
    $user->save();

    Attendance::create([
      'user_id' => $user->id,
      'work_date' => today(),
      'clock_in' => now()->setTime(9, 0),
      'clock_out' => now()->setTime(18, 0),
      'work_status' => WorkStatus::COMPLETED,
    ]);

    $this->actingAs($user);
    return $user;
  }

  // =========================================================
  // 管理者関連
  // =========================================================

  // 管理者(ログイン前)
  public function createAdmin(): Admin
  {
    return Admin::firstOrCreate(
      ['email' => 'admin@example.com'],
      [
        'name' => 'admin',
        'password' => Hash::make('admin123'),
        'email_verified_at' => now(),
      ]
    );
  }

  // 管理者(ログイン済)
  public function loginAdmin(): Admin
  {
    $admin = $this->createAdmin();
    $admin->email_verified_at = now();
    $admin->save();

    $this->actingAs($admin, 'admin');
    return $admin;
  }
}
