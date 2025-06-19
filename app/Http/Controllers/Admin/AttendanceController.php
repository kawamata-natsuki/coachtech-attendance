<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    // 勤怠一覧画面（管理者）表示
    public function index()
    {
        return view('admin.attendances.index');
    }

    // 勤怠詳細画面（管理者）表示
    public function show()
    {
        return view('shared.attendances.show');
    }

    // 勤怠詳細画面（管理者）修正処理
    public function update()
    {
        return redirect()->route('admin.attendances.show');
    }

    // スタッフ別勤怠一覧画面（管理者）表示
    public function staff()
    {
        return view('shared.attendances.index');
    }

    // スタッフ別勤怠一覧画面CSVエクスポート処理
    public function exportCsv()
    {
        //
    }
}
