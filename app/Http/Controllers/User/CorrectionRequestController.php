<?php

namespace App\Http\Controllers\User;

use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CorrectionRequestController extends Controller
{
    // 申請一覧画面の表示処理
    public function index(Request $request)
    {
        $user = Auth::user();

        // URLのクエリパラメータから「status」を取得(デフォルトはpending)
        $status = $request->query('status', 'pending');

        // ログインユーザーの修正申請を取得
        $requestsQuery = CorrectionRequest::with('user')
            ->where('user_id', $user->id);

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
}
