<?php

namespace App\Models;

use App\Enums\WorkStatus;
use Carbon\Carbon;
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

    // WorkStatus の判定
    public function isOff(): bool
    {
        return $this->work_status->is(WorkStatus::OFF);
    }

    public function isWorking(): bool
    {
        return $this->work_status->is(WorkStatus::WORKING);
    }

    public function isOnBreak(): bool
    {
        return $this->work_status->is(WorkStatus::BREAK);
    }

    public function isCompleted(): bool
    {
        return $this->work_status->is(WorkStatus::COMPLETED);
    }

    public function getWorkDateFormattedAttribute(): string
    {
        $date = Carbon::parse($this->work_date);
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        return $date->format('m/d') . '(' . $weekdays[$date->dayOfWeek] . ')';
    }

    public function getIsFutureAttribute(): bool
    {
        return Carbon::parse($this->work_date)->isFuture();
    }

    public function getClockOutFormattedAttribute(): ?string
    {
        return $this->clock_out
            ? Carbon::parse($this->clock_out)->format('H:i')
            : null;
    }

    public function getClockInFormattedAttribute(): ?string
    {
        return $this->clock_in
            ? Carbon::parse($this->clock_in)->format('H:i')
            : null;
    }
}
