@props([
'name',
'selectedHour' => null,
'selectedMinute' => null,
'disabled' => false,
])

<div class="time-select-wrapper {{ $disabled ? 'time-select-wrapper--disabled' : '' }}">
  <!-- 時間（Hour） -->
  <select
    class="time-select-wrapper__select"
    name="{{ $name }}[hour]"
    {{ $disabled ? 'disabled' : '' }}>

    <option value="" {{ $selectedHour === null ? 'selected' : '' }}>
      --
    </option>

    @php
    $hours = collect(range(0, 23))->map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT));
    @endphp
    <!-- 選択肢00～23を生成 -->
    @foreach ($hours as $h)
    <option value="{{ $h }}" {{ $h == $selectedHour ? 'selected' : '' }}>
      {{ $h }}
    </option>
    @endforeach

  </select>

  <span class="time-separator">:</span>

  <!-- 分（Minute） -->
  <select
    class="time-select-wrapper__select"
    name="{{ $name }}[minute]"
    {{ $disabled ? 'disabled' : '' }}>

    <option value="" {{ $selectedMinute === null ? 'selected' : '' }}>
      --
    </option>

    @php
    $minutes = collect(range(0, 59))->map(fn($m) => str_pad($m, 2, '0', STR_PAD_LEFT));
    @endphp
    <!-- 選択肢00～59を生成 -->
    @foreach ($minutes as $m)
    <option value="{{ $m }}" {{ $m == $selectedMinute ? 'selected' : '' }}>
      {{ $m }}
    </option>
    @endforeach

  </select>
</div>