@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/attendances/record.css') }}">
@endsection

@section('title', '勤怠登録')

@if (app()->environment('testing'))
{{-- テスト用にはViteを使わず直接ビルド済みのファイルを参照 --}}
<link rel="stylesheet" href="{{ asset('build/assets/app.css') }}">
<script src="{{ asset('build/assets/app.js') }}" defer></script>
@else
{{-- 開発 or 本番は通常通りViteを使う --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
@endif

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
    @env(['local', 'testing', 'test'])
    <div id="server-time">
      {{ now()->format('H:i') }}
    </div>
    @endenv
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