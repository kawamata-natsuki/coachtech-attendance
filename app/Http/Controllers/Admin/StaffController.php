<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Http\Controllers\Controller;

class StaffController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('admin.staff.index', [
            'users' => $users
        ]);
    }
}
