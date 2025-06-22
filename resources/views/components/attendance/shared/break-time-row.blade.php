@props([
'index',
'breakStart' => null,
'breakEnd' => null,
'breakId' => null,
'disabled' => false,
'isNew' => false,
])

<!-- 入力の初期値（休憩開始時刻・休憩終了時刻）を「時」と「分」に分割 -->
<!-- 例："12:00"-> ["12", "00"] -->
<!-- isNew = True （新しく追加された空の休憩行） -->
<!-- isNew = False （既存の休憩データあり） -->
@php
if ($isNew) {
$start = [null, null];
$end = [null, null];
} else {
$start = explode(':', old("requested_breaks.$index.requested_break_start", optional($breakStart)->format('H:i')));
$end = explode(':', old("requested_breaks.$index.requested_break_end", optional($breakEnd)->format('H:i')));
}
@endphp

<tr class="attendance-show-page__table-row">
  <th class="attendance-show-page__table-head">
    休憩{{ $index + 1 }}
  </th>

  <td class="attendance-show-page__table-cell--time-select">
    <div class="time-range-wrapper">

      <!-- 休憩開始時刻セレクトボックス -->
      <div class="time-block">
        <x-attendance.shared.time-select
          name="requested_breaks[{{ $index }}][requested_break_start]"
          :selectedHour="data_get($start, 0)"
          :selectedMinute="data_get($start, 1)"
          :disabled="$disabled" />
      </div>

      <span class="time-range-separator">～</span>

      <!-- 休憩終了時刻セレクトボックス -->
      <div class="time-block">
        <x-attendance.shared.time-select
          name="requested_breaks[{{ $index }}][requested_break_end]"
          :selectedHour="data_get($end, 0)"
          :selectedMinute="data_get($end, 1)"
          :disabled="$disabled" />
      </div>

      <!-- 既存の休憩データを編集する際に break_time_id を送る -->
      @if (!$disabled && !$isNew && $breakId)
      <input type="hidden" name="requested_breaks[{{ $index }}][break_time_id]" value="{{ $breakId }}">
      @endif
    </div>

    <div class="time-select__error-wrapper">
      <x-error.attendance-message :field="'requested_breaks.' . $index . '.requested_break_start'" />
      <x-error.attendance-message :field="'requested_breaks.' . $index . '.requested_break_end'" />
      <x-error.attendance-message :field="'break_time_logic_' . $index" />
    </div>

    </div>
  </td>
</tr>