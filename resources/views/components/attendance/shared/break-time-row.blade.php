{{-- 休憩時間セレクトボックス --}}

@props([
'index', // 何番目の休憩か
'requestedBreakStart' => null, // 初期表示用の休憩開始時刻（Carbon型 or null）
'requestedBreakEnd' => null, // 初期表示用の休憩終了時刻（Carbon型 or null）
'breakId' => null, // 既存の休憩データがある場合のID（hiddenで送信）
'isNew' => false, // trueなら新規追加（既存データではない）
])

{{-- バリデーションエラー後の再表示 --}}
@php
$breakStartInput = old("requested_breaks.$index.requested_break_start");
$breakEndInput = old("requested_breaks.$index.requested_break_end");

if ($isNew) {
$breakStartHour = null;
$breakStartMinute = null;
$breakEndHour = null;
$breakEndMinute = null;
} else {
if (is_array($breakStartInput)) {
$breakStartHour = $breakStartInput['hour'] ?? null;
$breakStartMinute = $breakStartInput['minute'] ?? null;
} elseif ($requestedBreakStart) {
[$breakStartHour, $breakStartMinute] = explode(':', $requestedBreakStart->format('H:i'));
} else {
$breakStartHour = null;
$breakStartMinute = null;
}

if (is_array($breakEndInput)) {
$breakEndHour = $breakEndInput['hour'] ?? null;
$breakEndMinute = $breakEndInput['minute'] ?? null;
} else {
[$breakEndHour, $breakEndMinute] = explode(':', optional($requestedBreakEnd)->format('H:i'));
}
}
@endphp

<tr class="attendance-show-page__table-row">
  <th class="attendance-show-page__table-head">
    休憩{{ $index + 1 }}
  </th>

  <td class="attendance-show-page__table-cell--time-select">
    <div class="time-range-wrapper">
      <div class="time-inputs">

        {{-- ▼ 休憩開始時刻セレクトボックス --}}
        <div class="time-block">
          <x-attendance.shared.time-select
            name="requested_breaks[{{ $index }}][requested_break_start]"
            :selectedHour="$breakStartHour"
            :selectedMinute="$breakStartMinute" />
        </div>

        <span class="time-range-separator">～</span>

        {{-- ▼ 休憩終了時刻セレクトボックス --}}
        <div class="time-block">
          <x-attendance.shared.time-select
            name="requested_breaks[{{ $index }}][requested_break_end]"
            :selectedHour="$breakEndHour"
            :selectedMinute="$breakEndMinute" />
        </div>

        {{-- ▼ 既存の休憩データを編集する際に break_time_id を送る --}}
        @if (!$isNew && $breakId)
        <input type="hidden" name="requested_breaks[{{ $index }}][break_time_id]" value="{{ $breakId }}">
        @endif
      </div>

      {{-- ▼ エラーメッセージ --}}
      <div class="time-select__error-wrapper">
        <x-error.attendance-message :field="'requested_breaks.' . $index . '.requested_break_start'" />
        <x-error.attendance-message :field="'requested_breaks.' . $index . '.requested_break_end'" />
      </div>

    </div>
  </td>
</tr>