<?php

namespace App\Http\Controllers\User;

use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class CorrectionRequestController extends Controller
{
    // 申請一覧画面の表示処理
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $query = CorrectionRequest::with('user')
            ->where('user_id', auth()->id());

        if ($status === 'pending') {
            $query->where('approval_status', ApprovalStatus::PENDING)
                ->orderBy('created_at', 'asc'); // 古い順
        } elseif ($status === 'approved') {
            $query->where('approval_status', ApprovalStatus::APPROVED)
                ->orderBy('created_at', 'desc'); // 新しい順
        }

        $correctionRequests = $query->get();

        return view('shared.correction-requests.index', [
            'status' => $status,
            'requests' => $correctionRequests,
        ]);
    }
}
