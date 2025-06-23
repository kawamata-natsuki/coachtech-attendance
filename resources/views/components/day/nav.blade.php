<!-- 日ナビゲーション -->
<div class="day-nav">
  <div class="day-nav__navigation">
    <a class="day-nav__link" href="{{ $prevUrl }}">
      <span class="day-nav__link-inner">
        <img src="{{ asset('images/left-arrow.svg') }}" alt="前日" class="icon-arrow">
        前日
      </span>
    </a>
  </div>

  <div class="day-nav__current-date">
    <span class="day-nav__label">
      <img src="{{ asset('images/calendar.svg') }}" alt="カレンダーアイコン" class="icon-calendar">
      {{ $currentDate->format('Y/n/j') }}
    </span>
  </div>

  <div class="day-nav__navigation">
    <a class="day-nav__link" href="{{ $nextUrl }}">
      <span class="day-nav__link-inner">
        翌日
        <img src="{{ asset('images/right-arrow.svg') }}" alt="翌日" class="icon-arrow">
      </span>
    </a>
  </div>
</div>