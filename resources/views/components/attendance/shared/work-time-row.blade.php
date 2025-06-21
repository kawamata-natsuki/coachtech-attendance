@props([
'clockIn' => null,
'clockOut' => null,
'disabled' => false,
])

<tr class="attendance-show-page__table-row">
  <th class="attendance-show-page__table-head">
    出勤・退勤
  </th>

  <td class="attendance-show-page__table-cell time-select-cell">
    <div @class([ 'time-range-wrapper' , 'time-range-wrapper--disabled'=> $disabled,])>

      <!-- 入力の初期値（出勤・退勤）を「時」と「分」に分割 -->
      <!-- 例："09:00"-> ["09", "00"] -->
      @php
      $clockInParts = explode(':', old('requested_clock_in', $clockIn ?? ''));
      $clockOutParts = explode(':', old('requested_clock_out', $clockOut ?? ''));
      @endphp

      <!-- 出勤時刻セレクトボックス -->
      <x-attendance.shared.time-select
        name="requested_clock_in"
        :selectedHour="data_get($clockInParts, 0)"
        :selectedMinute="data_get($clockInParts, 1)"
        :disabled="$disabled" />

      <span class="time-range-separator">～</span>

      <!-- 退勤時刻セレクトボックス -->
      <x-attendance.shared.time-select
        name="requested_clock_out"
        :selectedHour="data_get($clockOutParts, 0)"
        :selectedMinute="data_get($clockOutParts, 1)"
        :disabled="$disabled" />
    </div>

    <div class="time-select__error-wrapper">
      <x-error.attendance-message field="requested_clock_in" />
      <x-error.attendance-message field="requested_clock_out" />
      <x-error.attendance-message field="work_time_logic" />
    </div>

    </div>
  </td>
</tr>