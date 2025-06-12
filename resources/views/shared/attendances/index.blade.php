@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/components/month/nav.css') }}">
<link rel="stylesheet" href="{{ asset('css/shared/attendances/index.css') }}">
@endsection

@section('title', '勤怠一覧')

@section('content')
<div class="attendance-index-page">
  <div class="attendance-index-page__container">
    <h1 class="attendance-index-page__heading content__heading">
      <span class="attendance-index-page__heading-text">勤怠一覧</span>
    </h1>

    <x-month.nav
      :currentMonth="$currentMonth"
      :prevUrl="route('user.attendances.index', ['month' => $currentMonth->copy()->subMonth()->format('Y-m')])"
      :nextUrl="route('user.attendances.index', ['month' => $currentMonth->copy()->addMonth()->format('Y-m')])" />

    <!-- 日付・出勤・退勤・休憩時間（合計）・合計（勤務）　詳細ボタン -->
    <table class="attendance-index-page__table">

      <thead class="">
        <tr>
          <th class="attendance-table__head">日付</th>
          <th class="attendance-table__head">出勤</th>
          <th class="attendance-table__head">退勤</th>
          <th class="attendance-table__head">休憩</th>
          <th class="attendance-table__head">合計</th>
          <th class="attendance-table__head">詳細</th>
        </tr>
      </thead>

      <tbody>
        @foreach($attendances as $attendance)
        <tr>
          <td class="attendance-table__cell">
            {{ $attendance->present()->workDateForIndex() }}
          </td>

          @if ($attendance->is_future)
          <td class="attendance-table__cell" colspan="4"></td>
          <td class="attendance-table__cell"></td>
          @else

          <td class="attendance-table__cell">{{ $attendance->present()->clockInFormatted() }}</td>
          <td class="attendance-table__cell">{{ $attendance->present()->clockOutFormatted() }}</td>
          <td class="attendance-table__cell">{{ $attendance->present()->totalBreakTime() }}</td>
          <td class="attendance-table__cell">{{ $attendance->present()->totalWorkTime() }}</td>

          <!-- 未来の勤怠データをスキップ -->
          <td class="attendance-table__cell">
            @if ($attendance->work_date <= now())
              <a class="attendance-table__link" href="{{ route('user.attendances.show',['id' => $attendance->id]) }}">
              詳細
              </a>
              @else
              <!-- 未来のデータにはボタンを表示しない -->
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