@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/components/month/nav.css') }}">
<link rel="stylesheet" href="{{ asset('css/shared/attendances/index.css') }}">
@endsection

@section('title')
{{ auth('admin')->check() ? $user->name.'さんの勤怠' : '勤怠一覧' }}
@endsection

@section('content')
<div class="attendance-index-page">
  <div class="attendance-index-page__container">
    <h1 class="attendance-index-page__heading content__heading">
      <span class="attendance-index-page__heading-text">
        {{ auth('admin')->check() ? $user->name.'さんの勤怠' : '勤怠一覧' }}
      </span>
    </h1>

    <!-- 前月・翌月リンクを表示するナビゲーション -->
    <x-month.nav
      :currentMonth="$currentMonth"
      :prevUrl="$prevUrl"
      :nextUrl="$nextUrl" />

    <!-- 名前・出勤・退勤・休憩・合計　詳細ボタン -->
    <div class="attendance-index-page__table-wrapper">
      <table class="attendance-index-page__table">

        <thead>
          <tr>
            <th class="attendance-index-page__table-head">
              日付
            </th>
            <th class="attendance-index-page__table-head">
              出勤
            </th>
            <th class="attendance-index-page__table-head">
              退勤
            </th>
            <th class="attendance-index-page__table-head">
              休憩
            </th>
            <th class="attendance-index-page__table-head">
              合計
            </th>
            <th class="attendance-index-page__table-head">
              詳細
            </th>
          </tr>
        </thead>

        <tbody>
          @foreach($datesInMonth as $date)
          @php
          $attendance = $attendances->first(function ($att) use ($date) {
          return \Carbon\Carbon::parse($att->work_date)->isSameDay($date);
          });
          @endphp
          <tr>
            <td class="attendance-index-page__table-cell">
              {{ $date->locale('ja')->isoFormat('MM/DD(dd)') }}
            </td>

            @if (!$attendance)
            {{-- レコードがない日（お休み） --}}
            <td class="attendance-index-page__table-cell" colspan="4"></td>
            <td class="attendance-index-page__table-cell">
              <span class="attendance-index-page__table-link--disabled">詳細</span>
            </td>

            @else
            {{-- レコードがある日 --}}
            <td class="attendance-index-page__table-cell">
              @if ($attendance && $attendance->clock_in)
              {{ optional($attendance->clock_in)->format('H:i') }}
              @endif
            </td>

            <td class="attendance-index-page__table-cell">
              @if ($attendance && $attendance->clock_in && $attendance->clock_out)
              {{ optional($attendance->clock_out)->format('H:i') }}
              @elseif ($attendance && $attendance->clock_in)
              --:--
              @endif
            </td>

            <td class="attendance-index-page__table-cell">
              @if ($attendance && $attendance->clock_in && ($attendance->clock_out || $attendance->breakTimes->isNotEmpty()))
              {{ App\Services\AttendanceService::calculateBreakTime($attendance) }}
              @elseif ($attendance && $attendance->clock_in)
              --:--
              @endif
            </td>

            <td class="attendance-index-page__table-cell">
              @if ($attendance && $attendance->clock_in && $attendance->clock_out)
              {{ App\Services\AttendanceService::calculateWorkTime($attendance) }}
              @elseif ($attendance && $attendance->clock_in)
              --:--
              @endif
            </td>

            <td class="attendance-index-page__table-cell">
              @if ($attendance && $attendance->id)
              <a class="attendance-index-page__table-link" href="{{ route('attendances.show', ['id' => $attendance->id]) }}">
                詳細
              </a>
              @else
              <span class="attendance-index-page__table-link--disabled">
                詳細
              </span>
              @endif
            </td>
            @endif
          </tr>
          @endforeach
        </tbody>
      </table>

      @isset($user)
      @if(Auth::guard('admin')->check())
      <div class="attendance-index-page__export">
        <form class="attendance-index-page__export-form" method="GET"
          action="{{ route('admin.attendances.export', ['id' => $user->id]) }}">
          <input type="hidden" name="month" value="{{ $currentMonth->format('Y-m') }}">
          <button class="attendance-index-page__submit-button" type="submit">
            CSV出力
          </button>
        </form>
      </div>
      @endif
      @endisset

    </div>
  </div>
  @endsection