<!-- 出勤・退勤 -->
<tr class="attendance-show-page__table-row">
  <th class="attendance-show-page__table-head">
    出勤・退勤
  </th>
  <td class="attendance-show-page__text--time">
    <span class="time-display-block"> {{ $correctionRequest?->requested_clock_in?->format('H:i') ?? $attendance->clock_in?->format('H:i') ?? '--:--' }} </span>
    <span class="time-display-separator">～</span>
    <span class="time-display-block"> {{ $correctionRequest?->requested_clock_out?->format('H:i') ?? $attendance->clock_out?->format('H:i') ?? '--:--' }} </span>
  </td>
</tr>

<!-- 休憩 -->
@foreach ($attendance->breakTimes as $break)
<tr class="attendance-show-page__table-row">
  <th class="attendance-show-page__table-head">
    休憩
  </th>
  <td class="attendance-show-page__text--time">
    <span class="time-display-block">
      {{ $break->requested_break_start?->format('H:i') ?? $break->break_start?->format('H:i') ?? '--:--' }}
    </span>
    <span class="time-display-separator">～</span>
    <span class="time-display-block">
      {{ $break->requested_break_end?->format('H:i') ?? $break->break_end?->format('H:i') ?? '--:--' }}
    </span>
  </td>
</tr>
@endforeach

<!-- 備考 -->
<tr class="attendance-show-page__table-row">
  <th class="attendance-show-page__table-head">備考</th>
  <td class="attendance-show-page__table-cell--reason">
    {{ $correctionRequest?->reason ?? $attendance->reason }}
  </td>
</tr>