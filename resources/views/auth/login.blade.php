@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
@endsection

@section('title', 'ログイン')

@section('content')
<div class="login-page">
  <div class="login-page__container">
    <h1 class="login-page__heading content__heading">
      ログイン
    </h1>

    <div class="login-page__content">
      <form class="login-page__form" action="/login" method="post" novalidate>
        @csrf

        <!-- ログイン失敗時のエラー -->
        <div class="login-page__form-group">
          <x-error.message field="name" />
        </div>

        <div class="login-page__form-section">
          <!-- メールアドレス -->
          <div class="login-page__form-group form-group">
            <label class="login-page__label form__label" for="email">メールアドレス</label>
            <input class="login-page__input form__input" type="email" name="email" id="email" value="{{ old('email') }}"
              placeholder="例：user@example.com">
            <x-error.message field="email" preserve />
          </div>

          <!-- パスワード -->
          <div class="login-page__form-group form-group">
            <label class="login-page__label form__label" for="password">パスワード</label>
            <input class="login-page__input form__input" type="password" name="password" id="password"
              placeholder="8文字以上のパスワードを入力">
            <x-error.message field="password" preserve />
          </div>
        </div>

        <!-- 送信ボタン -->
        <div class="login-page__button">
          <button class="auth-button" type="submit">ログインする</button>
        </div>
      </form>

      <!-- リンク -->
      <div class="login-page__link">
        <a href="/register" class="login-page__link--register">会員登録はこちら</a>
      </div>
    </div>
  </div>
</div>
@endsection