{{-- 出退勤の時間入力用セレクトボックス --}}

@props([
'clockIn' => null,
'clockOut' => null,
])

<tr class="attendance-show-page__table-row">
  <th class="attendance-show-page__table-head">
    出勤・退勤
  </th>

  <td class="attendance-show-page__table-cell--time-select">
    <div class="time-range-wrapper">
      <div class="time-inputs">

        {{-- 入力の初期値（出勤・退勤）を「時」と「分」に分割  --}}
        @php
        $clockInHour = old('requested_clock_in.hour', data_get(explode(':', $clockIn), 0));
        $clockInMinute = old('requested_clock_in.minute', data_get(explode(':', $clockIn), 1));

        {{-- バリデーションエラー後の再表示はold()の値を優先 --}}
        $clockOutHour = old('requested_clock_out.hour', data_get(explode(':', $clockOut), 0));
        $clockOutMinute = old('requested_clock_out.minute', data_get(explode(':', $clockOut), 1));
        @endphp

        {{-- ▼ 出勤時刻セレクトボックス  --}}
        <div class="time-block">
          <x-attendance.shared.time-select
            name="requested_clock_in"
            :selectedHour="$clockInHour"
            :selectedMinute="$clockInMinute" />
        </div>

        <span class="time-range-separator">～</span>

        {{-- ▼ 退勤時刻セレクトボックス  --}}
        <div class="time-block">
          <x-attendance.shared.time-select
            name="requested_clock_out"
            :selectedHour="$clockOutHour"
            :selectedMinute="$clockOutMinute" />
        </div>
      </div>

      {{-- ▼ エラーメッセージ  --}}
      <div class="time-select__error-wrapper">
        <x-error.attendance-message field="requested_clock_out" />
        <x-error.attendance-message field="work_time_invalid" />
      </div>

    </div>
  </td>
</tr>