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
@php
$breaks = $correctionRequest?->correctionBreakTimes ?? $attendance->breakTimes;
@endphp

@foreach ($correctionRequest->correctionBreakTimes as $index => $break)
@continue(is_null($break->requested_break_start) && is_null($break->requested_break_end))
<tr class="attendance-show-page__table-row">
  <th class="attendance-show-page__table-head">
    休憩{{ $index + 1 }}
  </th>
  <td class="attendance-show-page__text--time">
    <span class="time-display-block">
      {{ optional($break->requested_break_start)->format('H:i') ?? '--:--' }}
    </span>
    <span class="time-display-separator">～</span>
    <span class="time-display-block">
      {{ optional($break->requested_break_end)->format('H:i') ?? '--:--' }}
    </span>
  </td>
</tr>
@endforeach

<!-- 備考 -->
<tr class="attendance-show-page__table-row">
  <th class="attendance-show-page__table-head">
    備考
  </th>
  <td class="attendance-show-page__text--reason">{{ $correctionRequest?->reason ?? $attendance->reason }}</td>
</tr>