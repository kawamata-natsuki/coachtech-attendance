@extends('layouts.app')

@section('css')
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

    <form action="{{ route('user.attendances.update',['id' => $attendance->id]) }}" method="post">
      @csrf
      @method('PUT')
      <table class="attendance-show-page__table">
        <!-- 名前 -->
        <tr class="attendance-show-page__table-row">
          <th class="attendance-show-page__table-head">
            名前
          </th>
          <td class="attendance-show-page__table-cell">{{ $attendance->user->name }}</td>
        </tr>

        <!-- 日付 -->
        <tr class="attendance-show-page__table-row">
          <th class="attendance-show-page__table-head">
            日付
          </th>
          <td class="attendance-show-page__table-cell">
            {{ $attendance->work_date->format('Y年n月j日') }}
          </td>
        </tr>

        <!-- 出勤・退勤 -->
        <tr class="attendance-show-page__table-row">
          <th class="attendance-show-page__table-head">
            出勤・退勤
          </th>
          <td class="attendance-show-page__table-cell">
            <input class="attendance-show-page__time-input"
              type="time"
              name="requested_clock_in"
              value="{{ old('requested_clock_in', $attendance->clock_in?->format('H:i')) }}">

            <span class="attendance-show-page__separator">～</span>

            <input class="attendance-show-page__time-input"
              type="time"
              name="requested_clock_out"
              value="{{ old('requested_clock_out', $attendance->clock_out?->format('H:i')) }}">

            <x-error.message field="requested_clock_in" />
            <x-error.message field="requested_clock_out" />
          </td>
        </tr>

        <!-- 休憩 -->
        @foreach ($break_times as $i => $break)
        <tr class="attendance-show-page__table-row">
          <th class="attendance-show-page__table-head">
            休憩{{ count($break_times) + 1 }}
          </th>
          <td class="attendance-show-page__table-cell">
            <input class="attendance-show-page__time-input"
              type="time"
              name="requested_breaks[{{ count($break_times) }}][requested_break_start]"
              value="">

            <span class="attendance-show-page__separator">～</span>

            <input class="attendance-show-page__time-input"
              type="time"
              name="requested_breaks[{{ count($break_times) }}][requested_break_end]"
              value="">

            <x-error.message :field="'requested_breaks.' . count($break_times) . '.requested_break_start'" />
            <x-error.message :field="'requested_breaks.' . count($break_times) . '.requested_break_end'" />
          </td>
        </tr>
        @endforeach

        <!-- 新規追加用の空休憩フォーム -->
        <tr class="attendance-show-page__table-row">
          <th class="attendance-show-page__table-head">
            休憩{{ count($break_times) + 1 }}
          </th>
          <td class="attendance-show-page__table-cell">
            <input class="attendance-show-page__time-input"
              type="time"
              name="requested_breaks[{{ count($break_times) }}][requested_break_start]"
              value="">

            <span class="attendance-show-page__separator">～</span>

            <input class="attendance-show-page__time-input"
              type="time"
              name="requested_breaks[{{ count($break_times) }}][requested_break_end]"
              value="">

            <x-error.message :field="'requested_breaks.' . count($break_times) . '.requested_break_start'" />
            <x-error.message :field="'requested_breaks.' . count($break_times) . '.requested_break_end'" />

          </td>
        </tr>

        <!-- 備考 -->
        <tr class="attendance-show-page__table-row">
          <th class="attendance-show-page__table-head">
            備考
          </th>
          <td class="attendance-show-page__table-cell">
            <textarea class="attendance-show-page__textarea" name="reason" id="reason">{{ old('reason') }}</textarea>

            <x-error.message field="reason" />

          </td>
        </tr>
      </table>

      <div class="attendance-show-page__button">
        <button class="attendance-show-page__submit-button" type="submit">
          修正
        </button>
      </div>

    </form>
  </div>
</div>
@endsection