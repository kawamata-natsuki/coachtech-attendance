@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/components/attendance/time-select.css') }}">
<link rel="stylesheet" href="{{ asset('css/shared/attendances/show.css') }}">
@endsection

@section('title', '勤怠詳細')

@section('content')
<div class="attendance-show-page">
  <div class="attendance-show-page__container">
    <h1 class="attendance-show-page__heading content__heading">
      <span class="attendance-index-page__heading-text">勤怠詳細</span>
    </h1>

    @if (session('success'))
    <div class="flash-message flash-message--success">
      {{ session('success') }}
    </div>
    @endif

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

        <!-- 出勤・退勤 -->
        <x-attendance.shared.work-time-row
          :clockIn="optional($correctionRequest?->requested_clock_in
            ?? $attendance->clock_in)->format('H:i')"
          :clockOut="optional($correctionRequest?->requested_clock_out
            ?? $attendance->clock_out)->format('H:i')"
          :disabled="$isCorrectionDisabled" />

        <!-- 休憩 -->
        @foreach ($breakTimes as $i => $break)
        <x-attendance.shared.break-time-row
          :index="$i"
          :breakStart="$break->requested_break_start ?? $break->break_start"
          :breakEnd="$break->requested_break_end ?? $break->break_end"
          :breakId="$break->id ?? null"
          :disabled="$isCorrectionDisabled" />
        @endforeach

        <!-- 空欄の休憩追加フォーム -->
        @if (!$isCorrectionDisabled)
        <x-attendance.shared.break-time-row
          :index="$nextIndex"
          :breakStart="null"
          :breakEnd="null"
          :breakId="null"
          :disabled="false"
          :isNew="true" />
        @endif

        <!-- 備考 -->
        <x-attendance.shared.reason-field
          :disabled="$isCorrectionDisabled"
          :reason="$correctionRequest?->reason ?? $attendance->reason" />
      </table>

      <!-- 修正ボタン -->
      <div class="attendance-show-page__button">

        @if (auth('admin')->check())
        <!-- 管理者 -->
        @if ($correctionRequest?->isApproved())
        <button class="attendance-show-page__submit-button" disabled>
          承認済み
        </button>
        @else
        <button type="submit" class="attendance-show-page__submit-button">
          修正
        </button>
        @endif

        @else
        <!-- 一般ユーザー -->
        @if($correctionRequest?->isPending())
        <p class="attendance-show-page__pending-message">
          承認待ちです
        </p>
        @elseif($correctionRequest?->isApproved())
        <button class="attendance-show-page__submit-button" disabled>
          承認済み
        </button>
        @else
        <button type="submit" class="attendance-show-page__submit-button">
          修正
        </button>
        @endif
        @endif
      </div>
    </form>
  </div>
</div>
@endsection