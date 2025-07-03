@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/components/day/nav.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/attendances/index.css') }}">
@endsection

@section('title')
{{ $currentDate->format('Y年n月j日') }}の勤怠一覧
@endsection

@section('content')
<div class="admin-attendance-index-page">
  <div class="admin-attendance-index-page__container">
    <h1 class="admin-attendance-index-page__heading content__heading">
      <span class="admin-attendance-index-page__heading-text">
        {{ $currentDate->format('Y年n月j日') }}の勤怠
      </span>
    </h1>

    <x-day.nav
      :currentDate="$currentDate"
      :prevUrl="$prevUrl"
      :nextUrl="$nextUrl" />

    <!-- 名前・出勤・退勤・休憩・合計　詳細ボタン -->
    <table class="admin-attendance-index-page__table">

      <thead>
        <tr>
          <th class="admin-attendance-index-page__table-head">
            名前
          </th>
          <th class="admin-attendance-index-page__table-head">
            出勤
          </th>
          <th class="admin-attendance-index-page__table-head">
            退勤
          </th>
          <th class="admin-attendance-index-page__table-head">
            休憩
          </th>
          <th class="admin-attendance-index-page__table-head">
            合計
          </th>
          <th class="admin-attendance-index-page__table-head">
            詳細
          </th>
        </tr>
      </thead>

      <tbody>
        @foreach ($users as $user)
        @php
        $attendance = $user->attendanceForDay;
        @endphp
        <tr>
          <td class="admin-attendance-index-page__table-cell">
            {{ $user->name }}
          </td>

          @if ($attendance && $attendance->isFuture())
          <td class="admin-attendance-index-page__table-cell" colspan="4">
          </td>
          <td class="admin-attendance-index-page__table-cell">
          </td>
          @else
          <td class="admin-attendance-index-page__table-cell">
            {{ $attendance?->clock_in?->format('H:i') ?? '' }}
          </td>

          <td class="admin-attendance-index-page__table-cell">
            {{ $attendance?->clock_out?->format('H:i') ?? '' }}
          </td>

          <td class="admin-attendance-index-page__table-cell">
            {{ $attendance ? App\Services\AttendanceService::calculateBreakTime($attendance) : '' }}
          </td>

          <td class="admin-attendance-index-page__table-cell">
            {{ $attendance ?  App\Services\AttendanceService::calculateWorkTime($attendance)  : '' }}
          </td>

          <td class="admin-attendance-index-page__table-cell">
            @if ($attendance && $attendance->id && $attendance->work_date->lte(now()))
            <a class="admin-attendance-index-page__table-link" href="{{ route('attendances.show',['id' => $attendance->id]) }}">
              詳細
            </a>
            @else
            <span class="admin-attendance-index-page__table-link--disabled">
              詳細
            </span>
            @endif
          </td> @endif
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection