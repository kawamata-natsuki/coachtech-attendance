<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CorrectionRequestController extends Controller
{
    // 申請一覧画面（管理者）表示
    public function index()
    {
        return view('shared.correction-requests.index');
    }

    // 修正申請承認画面（管理者）表示
    public function show()
    {
        return view('admin.correction-requests.approve');
    }

    // 修正申請承認画面（管理者）承認処理
    public function approve()
    {
        return redirect()->route('admin.correction-requests.approve');
    }
}
