<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Admin;
use App\Models\AttendanceLog;
use Illuminate\Support\Carbon;

class AttendanceLogService
{
  /**
   * 勤怠修正のログを保存
   */

  public function logManual(
    Attendance $attendance,
    ?Carbon $beforeClockIn,
    ?Carbon $beforeClockOut,
    ?Carbon $afterClockIn,
    ?Carbon $afterClockOut,
    ?string $beforeReason,
    ?string $afterReason,
    array $beforeBreaks,
    array $afterBreaks,
    Admin $admin,
    string $actionType = 'direct'
  ): AttendanceLog {
    return AttendanceLog::create([
      'attendance_id' => $attendance->id,
      'updated_by_admin_id' => $admin->id,
      'action_type' => $actionType,

      'before_clock_in' => $beforeClockIn,
      'before_clock_out' => $beforeClockOut,
      'after_clock_in' => $afterClockIn,
      'after_clock_out' => $afterClockOut,

      'before_breaks' => json_encode($beforeBreaks),
      'after_breaks' => json_encode($afterBreaks),

      'before_reason' => $beforeReason,
      'after_reason' => $afterReason,
    ]);
  }
}
