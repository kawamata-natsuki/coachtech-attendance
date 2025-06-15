<?php

namespace App\Http\Controllers\User;

use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use Illuminate\Http\Request;

class CorrectionRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $query = CorrectionRequest::with('user');

        if ($status === 'pending') {
            $query->where('approval_status', ApprovalStatus::PENDING);
        } elseif ($status === 'approved') {
            $query->where('approval_status', ApprovalStatus::APPROVED);
        }

        $correctionRequests = CorrectionRequest::with('user')
            ->where('user_id', auth()->id())
            ->get();

        return view('shared.correction-requests.index', compact('correctionRequests', 'status'));
    }
}
