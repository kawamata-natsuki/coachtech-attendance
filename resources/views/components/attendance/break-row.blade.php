@props([
'index',
'breakStart' => null,
'breakEnd' => null,
'breakId' => null,
'disabled' => false,
'isNew' => false,
])

<tr class="attendance-show-page__table-row">
  <th class="attendance-show-page__table-head">休憩{{ $index + 1 }}</th>
  <td class="attendance-show-page__table-cell">
    <div class="attendance-show-page__form-group">

      {{-- 入力フォーム --}}
      <div class="attendance-show-page__time-wrapper {{ $disabled ? 'attendance-show-page__time-wrapper--disabled' : '' }}">
        <input class="attendance-show-page__time-input"
          type="text"
          name="requested_breaks[{{ $index }}][requested_break_start]"
          value="{{ old("requested_breaks.$index.requested_break_start", optional($breakStart)->format('H:i')) }}"
          pattern="\d{2}:\d{2}"
          inputmode="numeric"
          {{ $disabled ? 'disabled' : '' }}>

        <span class="attendance-show-page__separator">～</span>

        <input class="attendance-show-page__time-input"
          type="text"
          name="requested_breaks[{{ $index }}][requested_break_end]"
          value="{{ old("requested_breaks.$index.requested_break_end", optional($breakEnd)->format('H:i')) }}"
          pattern="\d{2}:\d{2}"
          inputmode="numeric"
          {{ $disabled ? 'disabled' : '' }}>

        @if (!$disabled && !$isNew && $breakId)
        <input type="hidden" name="requested_breaks[{{ $index }}][break_time_id]" value="{{ $breakId }}">
        @endif
      </div>

      {{-- エラーメッセージ --}}
      <div class="attendance-show-page__error-wrapper">
        <x-error.attendance-message :field="'requested_breaks.' . $index . '.requested_break_start'" />
        <x-error.attendance-message :field="'requested_breaks.' . $index . '.requested_break_end'" />
      </div>

    </div>
  </td>
</tr>