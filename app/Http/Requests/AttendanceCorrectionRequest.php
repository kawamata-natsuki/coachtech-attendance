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
            'reason' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockIn = $this->combineTimeFromArray($this->input('requested_clock_in'));
            $clockOut = $this->combineTimeFromArray($this->input('requested_clock_out'));

            // 出勤 > 退勤 のチェック（両方が入力されている場合のみ）
            if ($clockIn && $clockOut && Carbon::parse($clockIn)->gt(Carbon::parse($clockOut))) {
                $validator->errors()->add('work_time_invalid', '出勤時間もしくは退勤時間が不適切な値です');
            }

            // 出勤または退勤が未入力 → 無条件でエラー出す
            if (!$clockIn || !$clockOut) {
                $validator->errors()->add("requested_clock_in", '出勤・退勤を入力してください');
            }

            // 出勤・退勤が両方入力されていれば、休憩時間がその範囲外にある場合をチェック
            if ($clockIn && $clockOut) {
                $clockInTime = Carbon::createFromFormat('H:i', $clockIn);
                $clockOutTime = Carbon::createFromFormat('H:i', $clockOut);

                foreach ($this->input('requested_breaks', []) as $i => $break) {
                    $start = $this->combineTimeFromArray($break['requested_break_start'] ?? []);
                    $end = $this->combineTimeFromArray($break['requested_break_end'] ?? []);

                    if ($start && $end) {
                        $breakStart = Carbon::createFromFormat('H:i', $start);
                        $breakEnd = Carbon::createFromFormat('H:i', $end);

                        // 開始が勤務時間外か（開始 < 出勤 or 開始 > 退勤）
                        if ($breakStart->lt($clockInTime) || $breakStart->gt($clockOutTime)) {
                            $validator->errors()->add("requested_breaks.$i.requested_break_start", '休憩時間が勤務時間外です');
                            continue; // 勤務時間外なら他のチェックはスキップ
                        }

                        // 終了が勤務時間外か（終了 < 出勤 or 終了 > 退勤）
                        if ($breakEnd->lt($clockInTime) || $breakEnd->gt($clockOutTime)) {
                            $validator->errors()->add("requested_breaks.$i.requested_break_end", '休憩時間が勤務時間外です');
                            continue; // 勤務時間外なら他のチェックはスキップ
                        }

                        // 開始 > 終了か
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
                        $validator->errors()->add('requested_clock_out', '未来の退勤時刻は入力できません');
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
