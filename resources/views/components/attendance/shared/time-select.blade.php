{{-- 出退勤・休憩時間などの時間入力用セレクトボックス --}}

@props([
'name',
'selectedHour' => null,
'selectedMinute' => null,
])

<div class="time-select-wrapper">
  {{-- 時間（Hour） --}}
  <select
    class="time-select-wrapper__select"
    name="{{ $name }}[hour]">

    <option value="" {{ $selectedHour === null ? 'selected' : '' }}>
      --
    </option>

    {{-- 00〜23 を2桁文字列に変換してコレクション生成 --}}
    @php
    $hours = collect(range(0, 23))->map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT));
    @endphp

    @foreach ($hours as $h)
    <option value="{{ $h }}" {{ $h == $selectedHour ? 'selected' : '' }}>
      {{ $h }}
    </option>
    @endforeach

  </select>

  <span class="time-separator">:</span>

  {{-- 分（Minute） --}}
  <select
    class="time-select-wrapper__select"
    name="{{ $name }}[minute]">

    <option value="" {{ $selectedMinute === null ? 'selected' : '' }}>
      --
    </option>

    {{-- 00〜59 を2桁文字列に変換してコレクション生成 --}}
    @php
    $minutes = collect(range(0, 59))->map(fn($m) => str_pad($m, 2, '0', STR_PAD_LEFT));
    @endphp

    @foreach ($minutes as $m)
    <option value="{{ $m }}" {{ $m == $selectedMinute ? 'selected' : '' }}>
      {{ $m }}
    </option>
    @endforeach

  </select>
</div>