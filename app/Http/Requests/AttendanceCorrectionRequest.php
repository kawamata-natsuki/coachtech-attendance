<?php

namespace App\Http\Requests;

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
            'requested_clock_in.date_format' => '出勤時間は「時:分」の形式で入力してください。',
            'requested_clock_out.date_format' => '退勤時間は「時:分」の形式で入力してください。',
            'reason.required' => '備考を記入してください。',
            'requested_breaks.*.requested_break_start.date_format' => '休憩開始時間は「時:分」の形式で入力してください。',
            'requested_breaks.*.requested_break_end.date_format' => '休憩終了時間は「時:分」の形式で入力してください。',
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
                $validator->errors()->add('requested_clock_in', '出勤・退勤・休憩のいずれかを入力してください。');
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
        });
    }

    public function failedValidation(Validator $validator)
    {
        logger()->error('バリデーション失敗:', $validator->errors()->toArray());

        throw new HttpResponseException(
            redirect()
                ->back()
                ->withErrors($validator)
                ->withInput()
        );
    }
}
