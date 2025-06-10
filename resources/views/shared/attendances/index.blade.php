@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/shared/attendances/index.css') }}">
@endsection

@section('title', '勤怠一覧')

@section('content')
<div class="attendance-index-page">
  <div class="attendance-index-page__container">
    <h1 class="attendance-index-page__heading content__heading">
      勤怠一覧
    </h1>

    <!-- 月ナビゲーション -->
    <div class="attendance-index-page__month-nav">

      <a href="{{ route('user.attendances.index', ['month' => $currentMonth->copy()->subMonth()->format('Y-m')]) }}">
        <img src="{{ asset('images/left-arrow.svg') }}" alt="前月" class="icon-arrow">
        前月
      </a>

      <span class="attendance-index-page__month-label">
        <img src="{{ asset('images/calendar.svg') }}" alt="カレンダーアイコン" class="icon-calendar">
        {{ $currentMonth->format('Y/m') }}
      </span>

      <a href="{{ route('user.attendances.index', ['month' => $currentMonth->copy()->addMonth()->format('Y-m')]) }}">
        翌月
        <img src="{{ asset('images/right-arrow.svg') }}" alt="翌月" class="icon-arrow">
      </a>
    </div>

    <!-- 日付・出勤・退勤・休憩時間（合計）・合計（勤務）　詳細ボタン -->
    <table class="attendance-index-page__table">
      <thead>
        <tr>
          <th>日付</th>
          <th>出勤</th>
          <th>退勤</th>
          <th>休憩</th>
          <th>合計</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($attendances as $attendance)
        <tr>
          <td>{{ $attendance->work_date_formatted }}</td>

          @if ($attendance->is_future)
          <td colspan="4"></td>
          <td></td>
          @else

          <td>{{ $attendance->clock_in_formatted }}</td>
          <td>{{ $attendance->clock_out_formatted }}</td>
          <td>{{ $attendance->break_duration }}</td>
          <td>{{ $attendance->work_duration }}</td>
          <td>
            @if ($attendance->id)
            <a href="{{ route('user.attendances.show', $attendance->id) }}">詳細</a>
            @endif
          </td>
          @endif
        </tr>
        @endforeach
      </tbody>
    </table>


  </div>
</div>

@endsection