@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/attendances/record.css') }}">
@endsection

@section('title', '勤怠登録')

@vite(['resources/js/attendance/record.js'])

@section('content')
<div class="attendance-record-page">
  <div class="attendance-record-page__container">
    <h1 class="sr-only">勤怠登録</h1>

    <!-- 勤務ステータス -->
    <div class="attendance-record-page__status">
      <span class="attendance-record-page__status-label">
        {{ $statusLabel }}
      </span>
    </div>

    <!-- 日付 -->
    <div class="attendance-record-page__date">
      {{ $attendance->work_date->isoFormat('YYYY年M月D日(dd)') }}
    </div>

    <!-- 時刻 -->
    <div class="attendance-record-page__time" id="clock">
      --:--
    </div>

    <!-- 打刻ボタン -->
    <div class="attendance-record-page__record">
      @if (is_null($attendance->clock_in))
      <!-- 出勤前 -->
      <x-attendance.record-form />

      @elseif ($attendance->isWorking())
      <!-- 出勤中 -->
      <x-attendance.working-form />

      @elseif ($attendance->isBreak())
      <!-- 休憩中 -->
      <x-attendance.break-form />

      @elseif ($attendance->isCompleted())
      <!-- 退勤済 -->
      <p class="attendance-record-page__message">
        お疲れ様でした。
      </p>
      @endif
    </div>
  </div>
</div>
@endsection