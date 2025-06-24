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

    <x-month.nav
      :currentMonth="$currentMonth"
      :prevUrl="$prevUrl"
      :nextUrl="$nextUrl" />

    <!-- 日付・出勤・退勤・休憩時間（合計）・合計（勤務）　詳細ボタン -->
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
        @foreach($attendances as $attendance)
        <tr>
          <td class="attendance-index-page__table-cell">
            {{ $attendance->work_date->locale('ja')->isoFormat('MM/DD(dd)') }}
          </td>

          @if ($attendance?->is_future)
          <td class="attendance-index-page__table-cell" colspan="4">
          </td>
          <td class="attendance-index-page__table-cell">
          </td>
          @else
          <td class="attendance-index-page__table-cell">
            {{ optional($attendance->clock_in)->format('H:i') }}
          </td>
          <td class="attendance-index-page__table-cell">
            {{ optional($attendance->clock_out)->format('H:i') }}
          </td>
          <td class="attendance-index-page__table-cell">
            {{ App\Services\AttendanceService::calculateBreakTime($attendance) }}
          </td>
          <td class="attendance-index-page__table-cell">
            {{ App\Services\AttendanceService::calculateWorkTime($attendance) }}
          </td>

          <td class="attendance-index-page__table-cell">
            @if ($attendance->work_date->lte(now()))
            @if (!is_null($attendance->id))
            <a class="attendance-index-page__table-link" href="{{ route('attendances.show',['id' => $attendance->id]) }}">
              詳細
            </a>
            @else
            <span class="attendance-index-page__table-link--disabled">
              詳細
            </span>
            @endif
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