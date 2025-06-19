@props([
'currentMonth',
'prevUrl',
'nextUrl',
])

<div class="month-nav">
  <div class="month-nav__navigation">
    <a class="month-nav__link" href="{{ $prevUrl }}">
      <span class="month-nav__link-inner">
        <img src="{{ asset('images/left-arrow.svg') }}" alt="前月" class="icon-arrow">
        前月
      </span>
    </a>
  </div>

  <div class="month-nav__current-month">
    <span class="month-nav-label">
      <img src="{{ asset('images/calendar.svg') }}" alt="カレンダーアイコン" class="icon-calendar">
      {{ $currentMonth->format('Y/m') }}
    </span>
  </div>

  <div class="month-nav__navigation">
    <a class="month-nav__link" href="{{ $nextUrl }}">
      <span class="month-nav__link-inner">
        翌月
        <img src="{{ asset('images/right-arrow.svg') }}" alt="翌月" class="icon-arrow">
      </span>
    </a>
  </div>
</div>