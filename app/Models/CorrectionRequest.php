<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CorrectionRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'work_date',
        'requested_clock_in',
        'requested_clock_out',
        'original_clock_in',
        'original_clock_out',
        'reason',
        'approved_at',
        'approver_id',
        'approval_status',
    ];

    protected $casts = [
        'work_date' => 'date',
        'requested_clock_in' => 'datetime',
        'requested_clock_out' => 'datetime',
        'original_clock_in' => 'datetime',
        'original_clock_out' => 'datetime',
        'approved_at' => 'datetime',
        'approval_status' => ApprovalStatus::class,
    ];

    // リレーション定義
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function correctionBreakTimes(): HasMany
    {
        return $this->hasMany(CorrectionBreakTime::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approver_id');
    }

    // 承認ステータスの判定
    public function isPending(): bool
    {
        return optional($this->approval_status)->is(ApprovalStatus::PENDING);
    }

    public function isApproved(): bool
    {
        return optional($this->approval_status)->is(ApprovalStatus::APPROVED);
    }
}
