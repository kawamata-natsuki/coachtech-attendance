@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/components/attendance/time-select.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/correction-requests/approve.css') }}">
@endsection

@section('title', '勤怠詳細')

@section('content')
<div class="admin-correction-approve-page">
  <div class="admin-correction-approve-page__container">
    <h1 class="admin-correction-approve-page__heading content__heading">
      <span class="admin-correction-approve-page__heading-text">
        勤怠詳細
      </span>
    </h1>

    <!-- フラッシュメッセージ -->
    @include('shared.flash-message')

    <table class="admin-correction-approve-page__table">
      <!-- 名前 -->
      <tr class="admin-correction-approve-page__table-row">
        <th class="admin-correction-approve-page__table-head">名前</th>
        <td class="admin-correction-approve-page__table-cell--name">
          {{ $attendance->user->name }}
        </td>
      </tr>

      <!-- 日付 -->
      <tr class="admin-correction-approve-page__table-row">
        <th class="admin-correction-approve-page__table-head">
          日付
        </th>
        <td class="admin-correction-approve-page__table-cell--date">
          {{ $attendance->work_date->format('Y年n月j日') }}
        </td>
      </tr>

      <!-- 出勤・退勤 -->
      <tr class="admin-correction-approve-page__table-row">
        <th class="admin-correction-approve-page__table-head">
          出勤・退勤
        </th>
        <td class="admin-correction-approve-page__table-cell--time">
          <span class="time-display-block">
            {{ $correctionRequest->requested_clock_in?->format('H:i') ?? '--:--' }}
          </span>
          <span class="time-display-separator">～</span>
          <span class="time-display-block">
            {{ $correctionRequest->requested_clock_out?->format('H:i') ?? '--:--' }}
          </span>
        </td>
      </tr>

      <!-- 休憩 -->
      @php
      $breaks = $correctionRequest?->correctionBreakTimes ?? $attendance->breakTimes;
      @endphp

      @foreach ($correctionRequest->correctionBreakTimes as $index => $break)
      @continue(is_null($break->requested_break_start) && is_null($break->requested_break_end))
      <tr class="admin-correction-approve-page__table-row">
        <th class="admin-correction-approve-page__table-head">
          休憩{{ $index + 1 }}
        </th>
        <td class="admin-correction-approve-page__table-cell--time">
          <span class="time-display-block">
            {{ optional($break->requested_break_start)->format('H:i') ?? '--:--' }}
          </span>
          <span class="time-display-separator">～</span>
          <span class="time-display-block">
            {{ optional($break->requested_break_end)->format('H:i') ?? '--:--' }}
          </span>
        </td>
      </tr>
      @endforeach

      <!-- 備考 -->
      <tr class="admin-correction-approve-page__table-row">
        <th class="admin-correction-approve-page__table-head">
          備考
        </th>
        <td class="admin-correction-approve-page__table-cell--reason">
          <span>{{ $correctionRequest->reason ?? '' }}</span>
        </td>
      </tr>
    </table>

    <!-- 承認ボタン -->
    <div class="admin-correction-approve-page__button">
      @if ($correctionRequest && $correctionRequest->isApproved())
      <button class="admin-correction-approve-page__submit-button admin-correction-approve-page__submit-button--disabled" disabled>
        承認済み
      </button>
      @elseif ($correctionRequest)
      <form method="POST" action="{{ route('admin.correction-requests.approve', ['attendance_correct_request' => $correctionRequest->id]) }}">
        @csrf
        <button type="submit" class="admin-correction-approve-page__submit-button">
          承認する
        </button>
      </form>
      @endif
    </div>
  </div>
</div>
@endsection