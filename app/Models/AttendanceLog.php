<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'updated_by_admin_id',
        'action_type',
        'before_clock_in',
        'after_clock_in',
        'before_clock_out',
        'after_clock_out',
        'before_breaks',
        'after_breaks',
        'before_reason',
        'after_reason',
    ];
}
