<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CorrectionRequestController extends Controller
{
    public function index()
    {
        return view('shared.correction-requests.index');
    }
}
