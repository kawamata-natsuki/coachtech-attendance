@props([
'clockIn' => null,
'clockOut' => null,
'disabled' => false,
])

<tr class="attendance-show-page__table-row">
  <th class="attendance-show-page__table-head">
    出勤・退勤
  </th>

  <td class="attendance-show-page__table-cell">
    <div
      class="attendance-show-page__time-wrapper {{ $disabled ? 'attendance-show-page__time-wrapper--disabled' : '' }}">
      <input class="attendance-show-page__time-input" type="text" name="requested_clock_in"
        value="{{ old('requested_clock_in', $clockIn) }}" pattern="^([01]\d|2[0-3]):[0-5]\d$" inputmode="numeric" {{
        $disabled ? 'disabled' : '' }}>

      <span class="attendance-show-page__separator">～</span>

      <input class="attendance-show-page__time-input" type="text" name="requested_clock_out"
        value="{{ old('requested_clock_out', $clockOut) }}" pattern="^([01]\d|2[0-3]):[0-5]\d$" inputmode="numeric" {{
        $disabled ? 'disabled' : '' }}>
    </div>

    <div class="attendance-show-page__error-wrapper">
      <x-error.attendance-message field="requested_clock_in" />
      <x-error.attendance-message field="requested_clock_out" />
    </div>

    </div>
  </td>
</tr>