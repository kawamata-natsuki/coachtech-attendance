<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use App\Services\AttendanceLogService;
use Illuminate\Http\Request;
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
        // URLのクエリパラメータから「status」を取得(デフォルトはpending)
        $status = $request->query('status', 'pending');

        // ユーザーの修正申請を取得
        $requestsQuery = CorrectionRequest::with('user');

        // 承認待ちの申請を作成日の古い順で取得
        // 承認済みの申請を作成日の新しい順で取得
        if ($status === 'pending') {
            $requestsQuery->where('approval_status', ApprovalStatus::PENDING)
                ->orderBy('created_at', 'asc');
        } elseif ($status === 'approved') {
            $requestsQuery->where('approval_status', ApprovalStatus::APPROVED)
                ->orderBy('created_at', 'desc');
        }

        // 申請データを取得
        $requests = $requestsQuery->get();

        return view('shared.correction-requests.index', [
            'status' => $status,
            'requests' => $requests,
        ]);
    }

    // 修正申請承認画面（管理者）表示
    public function show(Request $request, $id)
    {
        // 修正申請をIDで取得（関連する勤怠・ユーザー・修正休憩をまとめて取得）
        $correctionRequest = CorrectionRequest::with(['attendance.user', 'correctionBreakTimes'])
            ->findOrFail($id);

        // 勤怠データに通常の休憩時間もまとめて取得
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
        // 修正申請をIDで取得（関連する勤怠データと休憩データをまとめて取得）
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

        // breakTimes テーブルに申請内容を上書き
        $correctionBreaks = $correctionRequest->correctionBreakTimes;
        $originalBreaks = $attendance->breakTimes->values();

        foreach ($correctionBreaks as $index => $correctionBreak) {
            $original = $originalBreaks->get($index);

            $start = $correctionBreak->requested_break_start;
            $end   = $correctionBreak->requested_break_end;

            // 両方とも未入力（--:-- ~ --:--）の場合は削除扱い
            $isDeleted = is_null($start) && is_null($end);
            if ($isDeleted) {
                if ($original) {
                    $original->delete();
                }
                continue;
            }

            // 既存データがあれば更新、なければ新規作成
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
