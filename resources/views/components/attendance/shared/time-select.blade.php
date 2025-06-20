@props([
'nameHour',
'nameMinute',
'selectedHour' => null,
'selectedMinute' => null,
'disabled' => false,
])

<div class="time-select-wrapper">
  <select
    class="time-select-wrapper__select"
    name="{{ $nameHour }}"
    {{ $disabled ? 'disabled' : '' }}>
    @for ($h = 0; $h < 24; $h++)
      @php $val=sprintf('%02d', $h); @endphp
      <option value="{{ $val }}" {{ $val == $selectedHour ? 'selected' : '' }}>{{ $val }}</option>
      @endfor
  </select>

  <span class="time-separator">:</span>

  <select
    class="time-select-wrapper__select"
    name="{{ $nameMinute }}"
    {{ $disabled ? 'disabled' : '' }}>
    @for ($m = 0; $m < 60; $m++)
      @php $val=sprintf('%02d', $m); @endphp
      <option value="{{ $val }}" {{ $val == $selectedMinute ? 'selected' : '' }}>{{ $val }}</option>
      @endfor
  </select>
</div>