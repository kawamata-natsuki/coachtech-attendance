<?php

namespace App\Models;

use App\Models\BreakTime;
use App\Models\CorrectionRequest;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectionBreakTime extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'correction_request_id',
        'break_time_id',
        'requested_break_start',
        'requested_break_end',
        'original_break_start',
        'original_break_end',
    ];

    protected $casts = [
        'requested_break_start' => 'datetime',
        'requested_break_end' => 'datetime',
        'original_break_start' => 'datetime',
        'original_break_end' => 'datetime',
    ];

    // リレーション
    public function correctionRequest(): BelongsTo
    {
        return $this->belongsTo(CorrectionRequest::class);
    }

    public function breakTime(): BelongsTo
    {
        return $this->belongsTo(BreakTime::class);
    }
}
