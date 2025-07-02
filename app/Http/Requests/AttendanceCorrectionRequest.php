<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'max:255', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required'   => '備考を記入してください',
            'reason.max'        => '備考は255文字以内で入力してください'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockIn = $this->combineTimeFromArray($this->input('requested_clock_in'));
            $clockOut = $this->combineTimeFromArray($this->input('requested_clock_out'));

            // ================================
            // 出勤・退勤時刻の入力チェック
            // ================================

            // 両方未入力なら即エラー、片方未入力は個別エラー
            if (!$clockIn && !$clockOut) {
                $validator->errors()->add(
                    "requested_clock_in",
                    '出勤時間・退勤時間を入力してください'
                );
            } elseif (!$clockIn) {
                $validator->errors()->add(
                    "requested_clock_in",
                    '出勤時間を入力してください'
                );
            } elseif (!$clockOut) {
                $validator->errors()->add(
                    "requested_clock_in",
                    '退勤時間を入力してください'
                );
            }

            // 出勤時間 > 退勤時間の場合はエラー
            if ($clockIn && $clockOut && Carbon::parse($clockIn)->gt(Carbon::parse($clockOut))) {
                $validator->errors()->add(
                    'work_time_invalid',
                    '出勤時間もしくは退勤時間が不適切な値です'
                );
            }

            // ================================
            // 休憩時間の範囲チェック
            // ================================

            // 休憩時間が勤務時間内か検証
            if ($clockIn && $clockOut) {
                $clockInTime = Carbon::createFromFormat('H:i', $clockIn);
                $clockOutTime = Carbon::createFromFormat('H:i', $clockOut);

                foreach ($this->input('requested_breaks', []) as $i => $break) {
                    $start = $this->combineTimeFromArray($break['requested_break_start'] ?? []);
                    $end = $this->combineTimeFromArray($break['requested_break_end'] ?? []);

                    if ($start && $end) {
                        $breakStart = Carbon::createFromFormat('H:i', $start);
                        $breakEnd = Carbon::createFromFormat('H:i', $end);

                        // 休憩開始が勤務時間外の場合
                        if ($breakStart->lt($clockInTime) || $breakStart->gt($clockOutTime)) {
                            $validator->errors()->add("requested_breaks.$i.requested_break_start", '休憩時間が勤務時間外です');
                            continue;
                        }

                        // 休憩終了が勤務時間外の場合
                        if ($breakEnd->lt($clockInTime) || $breakEnd->gt($clockOutTime)) {
                            $validator->errors()->add("requested_breaks.$i.requested_break_end", '休憩時間が勤務時間外です');
                            continue;
                        }

                        // 休憩開始時間 > 休憩終了時間の場合
                        if ($breakStart->gt($breakEnd)) {
                            $validator->errors()->add("requested_breaks.$i.requested_break_start", '休憩時間が不適切な値です');
                        }
                    }
                }
            }

            // 今日が対象の日付であれば、退勤時刻が未来ならエラー
            if ($this->isToday()) {
                $now = now();

                if ($clockOut) {
                    $clockOutTime = Carbon::createFromFormat('H:i', $clockOut);
                    if ($clockOutTime->gt($now)) {
                        $validator->errors()->add(
                            'requested_clock_out',
                            '未来の退勤時刻は入力できません'
                        );
                    }
                }
            }
        });
    }

    // 配列で送られてきた時刻（hourとminute）を「00:00」のような文字列に変換
    private function combineTimeFromArray(?array $timeParts): ?string
    {
        $hour = $timeParts['hour'] ?? null;
        $minute = $timeParts['minute'] ?? null;

        if (filled($hour) && filled($minute)) {
            return sprintf('%02d:%02d', $hour, $minute);
        }

        return null;
    }

    private function isToday(): bool
    {
        $targetDate =
            $this->route('date')
            ?? $this->input('date')
            ?? optional($this->route('attendance'))->work_date
            ?? optional(Attendance::find($this->route('id')))->work_date;

        return $targetDate ? Carbon::parse($targetDate)->isToday() : false;
    }
}
