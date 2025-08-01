@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/components/attendance/time-select.css') }}">
<link rel="stylesheet" href="{{ asset('css/shared/attendances/show-table.css') }}">
<link rel="stylesheet" href="{{ asset('css/shared/attendances/show-display.css') }}">
<link rel="stylesheet" href="{{ asset('css/shared/attendances/show.css') }}">
@endsection

@section('title', '勤怠詳細')

@section('content')
<div class="attendance-show-page">
  <div class="attendance-show-page__container">
    <h1 class="attendance-show-page__heading content__heading">
      <span class="attendance-index-page__heading-text">
        勤怠詳細
      </span>
    </h1>

    <!-- フラッシュメッセージ -->
    @include('shared.flash-message')

    <form action="{{ route('attendances.update', ['id' => $attendance->id]) }}" method="post" novalidate>
      @csrf
      @method('PUT')

      <table class="attendance-show-page__table">
        <!-- 名前 -->
        <tr class="attendance-show-page__table-row">
          <th class="attendance-show-page__table-head">
            名前
          </th>
          <td class="attendance-show-page__table-cell--name">
            {{ $attendance->user->name }}
          </td>
        </tr>

        <!-- 日付 -->
        <tr class="attendance-show-page__table-row">
          <th class="attendance-show-page__table-head">
            日付
          </th>
          <td class="attendance-show-page__table-cell--date">
            {{ $attendance->work_date->format('Y年n月j日') }}
          </td>
        </tr>

        <!-- 表示専用モード（申請済） -->
        @if ($isCorrectionDisabled)
        @include('shared.attendances.display-fields', [
        'attendance' => $attendance,
        'correctionRequest' => $correctionRequest,
        ])
        @else

        <!-- 出勤・退勤 -->
        <x-attendance.shared.work-time-row
          :requestedClockIn="$requestedClockIn"
          :requestedClockOut="$requestedClockOut" />

        <!-- 休憩 -->
        @foreach ($breakTimes as $i => $break)
        <x-attendance.shared.break-time-row
          :index="$i"
          :requestedBreakStart="$break->requested_break_start ?? $break->break_start"
          :requestedBreakEnd="$break->requested_break_end ?? $break->break_end"
          :breakId="$break->id ?? null" />
        @endforeach

        <!-- 空欄の休憩追加フォーム -->
        <x-attendance.shared.break-time-row
          :index="$nextIndex"
          :requestedBreakStart="null"
          :requestedBreakEnd="null"
          :breakId="null"
          :isNew="true" />

        <!-- 備考 -->
        <x-attendance.shared.reason-field :reason="$attendance->reason" />
        @endif
      </table>

      <div class="attendance-show-page__button">
        <!-- 管理者用修正ボタン -->
        @auth('admin')
        <button type="submit" class="attendance-show-page__submit-button">
          修正
        </button>
        @endauth
        <!-- 一般ユーザー用修正ボタン -->
        @auth('web')
        @if ($correctionRequest?->isPending())
        <p class="attendance-show-page__pending-message">
          *承認待ちのため修正はできません。
        </p>
        @else
        <button type="submit" class="attendance-show-page__submit-button">
          修正
        </button>
        @endif
        @endauth
      </div>

    </form>
  </div>
</div>
@endsection