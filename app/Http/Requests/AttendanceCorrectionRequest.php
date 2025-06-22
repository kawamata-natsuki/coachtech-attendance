<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        $this->merge([
            'requested_clock_in' => sprintf(
                '%02d:%02d',
                $this->input('requested_clock_in_hour'),
                $this->input('requested_clock_in_minute')
            ),
            'requested_clock_out' => sprintf(
                '%02d:%02d',
                $this->input('requested_clock_out_hour'),
                $this->input('requested_clock_out_minute')
            ),
        ]);

        $breaks = $this->input('requested_breaks', []);

        foreach ($breaks as $i => $break) {
            $hourStart = $break['requested_break_start_hour'] ?? null;
            $minuteStart = $break['requested_break_start_minute'] ?? null;

            $hourEnd = $break['requested_break_end_hour'] ?? null;
            $minuteEnd = $break['requested_break_end_minute'] ?? null;

            $breaks[$i]['requested_break_start'] =
                ($hourStart !== null && $hourStart !== '' && $minuteStart !== null && $minuteStart !== '')
                ? sprintf('%02d:%02d', $hourStart, $minuteStart)
                : null;

            $breaks[$i]['requested_break_end'] =
                ($hourEnd !== null && $hourEnd !== '' && $minuteEnd !== null && $minuteEnd !== '')
                ? sprintf('%02d:%02d', $hourEnd, $minuteEnd)
                : null;
        }

        $this->merge(['requested_breaks' => $breaks]);
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
        logger()->debug('input', $this->all());

        $validator->after(function ($validator) {
            $clockIn = $this->input('requested_clock_in');
            $clockOut = $this->input('requested_clock_out');
            $breaks = $this->input('requested_breaks', []);

            // 1. 出勤 > 退勤
            if ($clockIn && $clockOut && $clockIn > $clockOut) {
                $validator->errors()->add('work_time_logic', '出勤時間もしくは退勤時間が不適切な値です');
            }

            // 2. 勤務時間外の休憩チェック
            foreach ($breaks as $i => $break) {
                $breakStart = $break['requested_break_start'] ?? null;
                $breakEnd = $break['requested_break_end'] ?? null;

                if ($clockIn && $breakStart && $breakStart < $clockIn) {
                    $validator->errors()->add("break_time_logic_$i", '休憩時間が勤務時間外です');
                }

                if ($clockOut && $breakEnd && $breakEnd > $clockOut) {
                    $validator->errors()->add("break_time_logic_$i", '休憩時間が勤務時間外です');
                }

                // 任意：休憩は開始・終了セット（UIで制御されてなければ残す）
                if (($breakStart && !$breakEnd) || (!$breakStart && $breakEnd)) {
                    $validator->errors()->add("requested_breaks.$i.requested_break_start", '休憩時間は開始・終了をセットで入力してください');
                }
            }

            // 3. 今日の日付なら未来時刻は禁止
            if ($this->isToday()) {
                $now = now()->format('H:i');

                if ($clockIn && $clockIn > $now) {
                    $validator->errors()->add('requested_clock_in', '未来の出勤時刻は入力できません');
                }

                if ($clockOut && $clockOut > $now) {
                    $validator->errors()->add('requested_clock_out', '未来の退勤時刻は入力できません');
                }

                foreach ($breaks as $i => $break) {
                    $breakStart = $break['requested_break_start'] ?? null;
                    $breakEnd = $break['requested_break_end'] ?? null;

                    if ($breakStart && $breakStart > $now) {
                        $validator->errors()->add("requested_breaks.$i.requested_break_start", '未来の休憩開始時刻は入力できません');
                    }

                    if ($breakEnd && $breakEnd > $now) {
                        $validator->errors()->add('requested_clock_out', '未来の退勤時刻は入力できません');
                    }
                }
            }

            // 4. 未来の時間は申請不可
            $clockOutHour = $this->input('requested_clock_out.hour');
            $clockOutMinute = $this->input('requested_clock_out.minute');

            if (filled($clockOutHour) && filled($clockOutMinute)) {
                $clockOutTime = Carbon::createFromTime($clockOutHour, $clockOutMinute);
                if ($clockOutTime->gt(now())) {
                    $validator->errors()->add('requested_clock_out.hour', '未来の時間は申請できません。');
                }
            }


            logger()->debug('退勤H', [$clockOutHour]);
            logger()->debug('退勤M', [$clockOutMinute]);
        });
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
