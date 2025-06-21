<?php

namespace App\Models;

use App\Enums\WorkStatus;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
        'work_status',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'work_status' => WorkStatus::class,
    ];

    // リレーション
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function correctionRequests(): HasMany
    {
        return $this->hasMany(CorrectionRequest::class);
    }

    public function breakTimes(): HasMany
    {
        return $this->hasMany(BreakTime::class);
    }

    // 勤務ステータス判定
    public function isOff(): bool
    {
        return optional($this->work_status)->is(WorkStatus::OFF);
    }

    public function isWorking(): bool
    {
        return optional($this->work_status)->is(WorkStatus::WORKING);
    }

    public function isBreak(): bool
    {
        return optional($this->work_status)->is(WorkStatus::BREAK);
    }

    public function isCompleted(): bool
    {
        return optional($this->work_status)->is(WorkStatus::COMPLETED);
    }
}
