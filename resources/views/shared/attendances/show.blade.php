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
          <td class="attendance-show-page__table-cell--name">{{ $attendance->user->name }}</td>
        </tr>

        <!-- 日付 -->
        <tr class="attendance-show-page__table-row">
          <th class="attendance-show-page__table-head">
            日付
          </th>
          <td class="attendance-show-page__table-cell--date">
            {{ $presenter->workDateForShow() }}
          </td>
        </tr>

        <!-- 出勤・退勤 -->
        <x-attendance.clock-row
          :clockIn="$presenter->requestedClockIn()"
          :clockOut="$presenter->requestedClockOut()"
          :disabled="$presenter->isCorrectionDisabled()" />

        <!-- 休憩 -->
        @foreach ($presenter->breaks() as $i => $break)
        <x-attendance.break-row
          :index="$i"
          :breakStart="$break->requested_break_start ?? $break->break_start"
          :breakEnd="$break->requested_break_end ?? $break->break_end"
          :breakId="$break->id ?? null"
          :disabled="$presenter->isCorrectionDisabled()" />
        @endforeach

        <!-- 新規追加用の空休憩フォーム -->
        @if (!$presenter->isCorrectionDisabled())
        @php $nextIndex = $presenter->nextBreakIndex(); @endphp
        <x-attendance.break-row
          :index="$nextIndex"
          :breakStart="null"
          :breakEnd="null"
          :breakId="null"
          :disabled="false"
          :isNew="true" />
        @endif

        <!-- 備考 -->
        <x-attendance.reason-field
          :disabled="$presenter->isCorrectionDisabled()"
          :reason="$presenter->displayReason()" />
      </table>

      @if ($correctionRequest)
      <p class="attendance-show-page__pending-message">
        ※承認待ちのため修正はできません。
      </p>
      @else
      <div class="attendance-show-page__button">
        <button class="attendance-show-page__submit-button" type="submit">
          修正
        </button>
      </div>
      @endif

    </form>
  </div>
</div>
@endsection