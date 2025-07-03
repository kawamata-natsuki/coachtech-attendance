@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff/index.css') }}">
@endsection

@section('title', 'スタッフ一覧')

@section('content')
<div class="staff-index-page">
  <div class="staff-index-page__container">
    <h1 class="staff-index-page__heading content__heading">
      <span class="staff-index-page__heading-text">
        スタッフ一覧
      </span>
    </h1>

    <!-- 名前・メールアドレス・月次勤怠　詳細ボタン -->
    <div class="staff-index-page__table-wrapper">
      <table class="staff-index-page__table">
        <thead>
          <tr>
            <th class="staff-index-page__table-head">
              名前
            </th>
            <th class="staff-index-page__table-head">
              メールアドレス
            </th>
            <th class="staff-index-page__table-head">
              月次勤怠
            </th>
          </tr>
        </thead>

        <tbody>
          @foreach ($users as $user)
          <tr>
            <td class="staff-index-page__table-cell">
              {{ $user->name }}
            </td>

            <td class="staff-index-page__table-cell">
              {{ $user->email }}
            </td>

            <td class="staff-index-page__table-cell">
              <a class="staff-index-page__table-link" href="{{ route('admin.attendances.staff', ['id' => $user->id]) }}">
                詳細
              </a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection