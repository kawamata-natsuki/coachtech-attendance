<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class CorrectionRequestController extends Controller
{
    public function index(Request $request)
    {
        return view('shared.correction-requests.index');
    }
}
