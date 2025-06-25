@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/shared/correction-requests/index.css') }}">
@endsection

@section('title', '申請一覧')

@section('content')
<div class="correction-request-index-page">
  <div class="correction-request-index-page__container">
    <h1 class="correction-request-index-page__heading content__heading">
      <span class="correction-request-index-page__heading-text">
        申請一覧
      </span>
    </h1>

    <div class="correction-request-index-page__tabs">
      <a href="{{ route('correction-requests.index', ['status' => 'pending']) }}"
        class="correction-request-index-page__tab {{ $status === 'pending' ? 'active' : '' }}">
        承認待ち
      </a>
      <a href="{{ route('correction-requests.index', ['status' => 'approved']) }}"
        class="correction-request-index-page__tab {{ $status === 'approved' ? 'active' : '' }}">
        承認済み
      </a>
    </div>

    <table class="correction-request-index-page__table">
      <thead>
        <tr>
          <th class="correction-request-index-page__table-head">
            状態
          </th>
          <th class="correction-request-index-page__table-head">
            名前
          </th>
          <th class="correction-request-index-page__table-head">
            対象日時
          </th>
          <th class="correction-request-index-page__table-head">
            申請理由
          </th>
          <th class="correction-request-index-page__table-head">
            申請日時
          </th>
          <th class="correction-request-index-page__table-head">
            詳細
          </th>
        </tr>
      </thead>

      <tbody>
        @foreach ($requests as $correctionRequest)
        <tr>
          <td class="correction-request-index-page__table-cell">
            {{ $correctionRequest->approval_status->label() }}
          </td>

          <td class="correction-request-index-page__table-cell">
            <div class="correction-request-index-page__table-cell--name" title="{{ $correctionRequest->user->name }}">
              {{ $correctionRequest->user->name }}
            </div>
          </td>

          <td class="correction-request-index-page__table-cell">
            {{ $correctionRequest->work_date->format('Y/m/d') }}
          </td>

          <td class="correction-request-index-page__table-cell">
            <div class="correction-request-index-page__table-cell--reason" title="{{ $correctionRequest->reason }}">
              {{ $correctionRequest->reason }}
            </div>
          </td>

          <td class="correction-request-index-page__table-cell">
            {{ $correctionRequest->created_at->format('Y/m/d') }}
          </td>

          <!-- 詳細ボタン -->
          <td class="correction-request-index-page__table-cell">
            @if (auth('admin')->check())
            <a class="correction-request-index-page__table-link"
              href="{{ route('admin.correction-requests.show', $correctionRequest->id) }}">
              詳細
            </a>
            @elseif (auth('web')->check())
            <a class="correction-request-index-page__table-link"
              href="{{ route('attendances.show', [
              'id' => $correctionRequest->attendance_id,
              'request_id' => $correctionRequest->id,
              ]) }}">
              詳細
            </a>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>

  </div>
</div>

@endsection