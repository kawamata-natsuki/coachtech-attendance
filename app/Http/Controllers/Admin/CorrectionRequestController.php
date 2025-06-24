<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Services\AttendanceLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class CorrectionRequestController extends Controller
{
    private AttendanceLogService $logService;

    public function __construct(AttendanceLogService $logService)
    {
        $this->logService = $logService;
    }

    // 申請一覧画面（管理者）表示
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $query = CorrectionRequest::with('user');

        if ($status === 'pending') {
            $query->where('approval_status', ApprovalStatus::PENDING);
        } elseif ($status === 'approved') {
            $query->where('approval_status', ApprovalStatus::APPROVED);
        }

        $correctionRequests = $query->get();

        return view('shared.correction-requests.index', [
            'status' => $status,
            'requests' => $correctionRequests,
        ]);
    }

    // 修正申請承認画面（管理者）表示
    public function show(Request $request, $id)
    {
        $correctionRequest = CorrectionRequest::with(['attendance.user', 'correctionBreakTimes'])
            ->findOrFail($id);

        $attendance = $correctionRequest->attendance->load('breakTimes');

        return view('admin.correction-requests.approve', [
            'correctionRequest' => $correctionRequest,
            'attendance' => $correctionRequest->attendance,
            'breakTimes' => $attendance->breakTimes,
            'nextIndex' => 1,
            'isCorrectionDisabled' => true,
        ]);
    }

    // 修正申請承認画面（管理者）承認処理
    public function approve($id)
    {
        $correctionRequest = CorrectionRequest::with('attendance.breakTimes')->findOrFail($id);
        $attendance = $correctionRequest->attendance;

        // 修正前の状態を取得（attendanceとbreakTimes）
        $beforeBreaks = $attendance->breakTimes->map(function ($break) {
            return [
                'break_start' => optional($break->break_start)->format('H:i'),
                'break_end' => optional($break->break_end)->format('H:i'),
            ];
        })->toArray();
        $beforeClockIn = $attendance->clock_in;
        $beforeClockOut = $attendance->clock_out;
        $beforeReason = $attendance->reason;

        // attendances テーブルに申請内容を上書き
        $attendance->update([
            'clock_in' => $correctionRequest->requested_clock_in,
            'clock_out' => $correctionRequest->requested_clock_out,
            'reason' => $correctionRequest->reason,
        ]);

        // breakTimesも上書き
        $correctionBreaks = $correctionRequest->correctionBreakTimes;
        $originalBreaks = $attendance->breakTimes->values();

        foreach ($correctionBreaks as $index => $correctionBreak) {
            $original = $originalBreaks->get($index);

            $start = $correctionBreak->requested_break_start;
            $end   = $correctionBreak->requested_break_end;

            // --:-- ~ --:-- が選ばれた場合（nullになってる）
            $isDeleted = is_null($start) && is_null($end);

            if ($isDeleted) {
                if ($original) {
                    $original->delete();
                }
                continue;
            }

            // 上書き or 新規追加（Carbonのまま渡す）
            if ($original) {
                $original->update([
                    'break_start' => $start,
                    'break_end'   => $end,
                ]);
            } else {
                $attendance->breakTimes()->create([
                    'break_start' => $start,
                    'break_end'   => $end,
                ]);
            }
        }

        // 修正後の状態を取得
        $attendance->load('breakTimes');

        $afterBreaks = $attendance->breakTimes->map(function ($break) {
            return [
                'break_start' => optional($break->break_start)->format('H:i'),
                'break_end' => optional($break->break_end)->format('H:i'),
            ];
        })->toArray();

        // ログ保存
        $this->logService->logManual(
            $attendance,
            $beforeClockIn,
            $beforeClockOut,
            $attendance->clock_in,
            $attendance->clock_out,
            $beforeReason,
            $attendance->reason,
            $beforeBreaks,
            $afterBreaks,
            Auth::guard('admin')->user(),
            'approve'
        );

        // ステータス更新
        $correctionRequest->approval_status = ApprovalStatus::APPROVED;
        $correctionRequest->approved_at = now();
        $correctionRequest->approver_id = Auth::guard('admin')->id();
        $correctionRequest->save();

        return redirect()
            ->route('admin.correction-requests.show', $correctionRequest->id)
            ->with('success', '申請を承認しました');
    }
}
