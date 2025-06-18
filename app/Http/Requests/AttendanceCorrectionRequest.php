<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'requested_clock_in' => ['nullable', 'date_format:H:i'],
            'requested_clock_out' => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string'],
            'requested_breaks' => ['array'],
        ];





















        // 休憩時間（配列入力）のバリデーションルール
        foreach ($this->input('requested_breaks', []) as $i => $break) {
            $hasInput = !empty($break['requested_break_start']) || !empty($break['requested_break_end']);

            if (!empty($break['break_time_id']) || $hasInput) {
                $rules["requested_breaks.$i.break_time_id"] = ['nullable', 'integer', 'exists:break_times,id'];
            }

            $rules["requested_breaks.$i.requested_break_start"] = ['nullable', 'date_format:H:i'];
            $rules["requested_breaks.$i.requested_break_end"] = ['nullable', 'date_format:H:i'];
        }
        return $rules;
    }

    public function messages(): array
    {
        return [
            'requested_clock_in.date_format' => '出勤時間は「時:分」の形式で入力してください',
            'requested_clock_out.date_format' => '退勤時間は「時:分」の形式で入力してください',
            'reason.required' => '備考を記入してください',
            'requested_breaks.*.requested_break_start.date_format' => '休憩開始時間は「時:分」の形式で入力してください',
            'requested_breaks.*.requested_break_end.date_format' => '休憩終了時間は「時:分」の形式で入力してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockIn = $this->input('requested_clock_in');
            $clockOut = $this->input('requested_clock_out');
            $breaks = $this->input('requested_breaks', []);

            $hasBreakUpdate = collect($breaks)->contains(function ($break) {
                return !empty($break['requested_break_start']) || !empty($break['requested_break_end']);
            });

            if (empty($clockIn) && empty($clockOut) && !$hasBreakUpdate) {
                $validator->errors()->add('requested_clock_in', '出勤・退勤・休憩のいずれかを入力してください');
            }

            // ① 出勤時間 > 退勤時間 のチェック
            if ($clockIn && $clockOut && $clockIn > $clockOut) {
                $validator->errors()->add('requested_clock_in', '出勤時間もしくは退勤時間が不適切な値です');
            }

            // ② 勤務時間外に休憩があるチェック
            foreach ($this->input('requested_breaks', []) as $i => $break) {
                $breakStart = $break['requested_break_start'] ?? null;
                $breakEnd = $break['requested_break_end'] ?? null;

                if ($clockIn && $breakStart && $breakStart < $clockIn) {
                    $validator->errors()->add("requested_breaks.$i.requested_break_start", '休憩時間が勤務時間外です');
                }

                if ($clockOut && $breakEnd && $breakEnd > $clockOut) {
                    $validator->errors()->add("requested_breaks.$i.requested_break_end", '休憩時間が勤務時間外です');
                }

                if (($breakStart && !$breakEnd) || (!$breakStart && $breakEnd)) {
                    $validator->errors()->add("requested_breaks.$i.requested_break_start", '休憩時間は開始・終了をセットで入力してください');
                }
            }

            // 承認済みは再申請不可
            $attendance = Attendance::find($this->route('id'));
            $latestRequest = $attendance?->correctionRequests()->latest()->first();

            if ($latestRequest?->isApproved()) {
                $validator->errors()->add('approved', 'この勤怠は承認済みのため修正できません。');
            }

            // 今日の日付なら未来時刻の入力は禁止
            if ($this->isToday()) {
                $now = now()->format('H:i');

                if ($clockIn && $clockIn > $now) {
                    $validator->errors()->add('requested_clock_in', '未来の出勤時刻は入力できません');
                }

                if ($clockOut && $clockOut > $now) {
                    $validator->errors()->add('requested_clock_out', '未来の退勤時刻は入力できません');
                }

                foreach ($this->input('requested_breaks', []) as $i => $break) {
                    $breakStart = $break['requested_break_start'] ?? null;
                    $breakEnd = $break['requested_break_end'] ?? null;

                    if ($breakStart && $breakStart > $now) {
                        $validator->errors()->add("requested_breaks.$i.requested_break_start", '未来の休憩開始時刻は入力できません');
                    }

                    if ($breakEnd && $breakEnd > $now) {
                        $validator->errors()->add("requested_breaks.$i.requested_break_end", '未来の休憩終了時刻は入力できません');
                    }
                }
            }
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
