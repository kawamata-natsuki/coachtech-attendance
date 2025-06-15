<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use Illuminate\Http\Request;

class CorrectionRequestController extends Controller
{
    public function index()
    {
        $correctionRequests = CorrectionRequest::with('user')
            ->where('user_id', auth()->id())
            ->get();

        return view('shared.correction-requests.index', compact('correctionRequests'));
    }
}
