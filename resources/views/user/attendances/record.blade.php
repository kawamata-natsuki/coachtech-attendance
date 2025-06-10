@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/attendances/record.css') }}">
@endsection

@section('title', '勤怠登録')

@vite(['resources/js/attendances/record.js'])

@section('content')
<div class="attendance-record-page">
  <div class="attendance-record-page__container">
    <h1 class="sr-only">勤怠登録</h1>

    <!-- 勤務ステータス -->
    <div class="attendance-record-page__status">
      <span class="attendance-record-page__status-label">{{ $statusLabel }}</span>
    </div>

    <!-- 日付 -->
    <div class="attendance-record-page__date">
      {{ $formattedDate }}
    </div>

    <!-- 時刻 -->
    <div class="attendance-record-page__time" id="clock">
      {{ now()->format('H:i') }}
    </div>

    <!-- 打刻ボタン -->
    <div class="attendance-record-page__record">
      @if (is_null($attendance->clock_in))
      <!-- 出勤前 -->
      <form class="button-wrapper" method="POST" action="{{ route('user.attendances.store') }}">
        @csrf
        <button class="attendance-record-page__button  button--black" type="submit" name="action" value="clock_in">
          出勤
        </button>
      </form>

      @elseif ($statusValue === 'working')
      <!-- 出勤中 -->
      <form class="button-wrapper" method="POST" action="{{ route('user.attendances.store') }}">
        @csrf
        <button class="attendance-record-page__button  button--black" type="submit" name="action" value="clock_out">
          退勤
        </button>
        <button class="attendance-record-page__button  button--white" type="submit" name="action" value="break_start">
          休憩入
        </button>
      </form>

      @elseif ($statusValue === 'break')
      <!-- 休憩中 -->
      <form class="button-wrapper" method="POST" action="{{ route('user.attendances.store') }}">
        @csrf
        <button class="attendance-record-page__button  button--white" type="submit" name="action" value="break_end">
          休憩戻
        </button>
      </form>

      @elseif ($statusValue === 'completed')
      <!-- 退勤済 -->
      <p class="attendance-record-page__message">お疲れ様でした。</p>
      @endif
    </div>
  </div>
</div>
@endsection