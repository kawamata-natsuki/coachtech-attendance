<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CorrectionRequestController extends Controller
{
    // 申請一覧画面表示
    public function index(Request $request)
    {
        return view('shared.correction-requests.index');
    }
}
