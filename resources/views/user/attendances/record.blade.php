@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/attendances/record.css') }}">

@if (app()->environment('testing'))
{{-- テスト用にはビルド済みファイルを直接読み込み --}}
<link rel="stylesheet" href="{{ asset('build/assets/app.css') }}">
@else
{{-- 開発 or 本番はViteのHMRを利用 --}}
@vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/attendance/record.js'])
@endif
@endsection

@section('title', '勤怠登録')

@section('content')
<div class="attendance-record-page">
  <div class="attendance-record-page__container">
    <h1 class="sr-only">
      勤怠登録
    </h1>

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

    <!-- 時刻（テスト用サーバー時刻） -->
    @env('testing')
    <div id="server-time">
      {{ now()->format('H:i') }}
    </div>
    @endenv
    <!-- 時刻（開発・本番はJSで更新） -->
    <div class="attendance-record-page__time" id="clock">
      --:--
    </div>

    <!-- 打刻ボタン -->
    <div class="attendance-record-page__record">
      @if (is_null($attendance->clock_in))
      <!-- 出勤前 -->
      <x-attendance.record.clock-in-button />

      @elseif ($attendance->isWorking())
      <!-- 出勤中 -->
      <x-attendance.record.clock-out-button />

      @elseif ($attendance->isBreak())
      <!-- 休憩中 -->
      <x-attendance.record.break-button />

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