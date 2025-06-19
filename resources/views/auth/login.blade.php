@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/register.css') }}">
@endsection

@section('title', '会員登録')

@section('content')
<div class="register-page">
  <div class="register-page__container">
    <h1 class="register-page__heading content__heading">
      会員登録
    </h1>

    <div class="register-page__content">
      <form class="register-page__form" action="/register" method="post" novalidate>
        @csrf

        <div class="register-page__form-section">
          <!-- 名前 -->
          <div class="register-page__form-group form-group">
            <label class="register-page__label form__label" for="name">名前</label>
            <input class="form__input register-page__input" type="text" name="name" id="name" value="{{ old('name') }}"
              placeholder="例：山田　太郎">
            <x-error.auth-message field="name" preserve />
          </div>

          <!-- メールアドレス -->
          <div class="register-page__form-group form-group">
            <label class="register-page__label form__label" for="email">メールアドレス</label>
            <input class="form__input register-page__input" type="email" name="email" id="email"
              value="{{ old('email') }}" placeholder="例：user@example.com">
            <x-error.auth-message field="email" preserve />
          </div>

          <!-- パスワード -->
          <div class="register-page__form-group form-group">
            <label class="register-page__label form__label" for="password">パスワード</label>
            <input class="form__input register-page__input" type="password" name="password" id="password"
              placeholder="8文字以上のパスワードを入力">
            <x-error.auth-message field="password" preserve />
          </div>

          <!-- 確認用パスワード -->
          <div class="register-page__form-group form-group">
            <label class="register-page__label form__label" for="password_confirmation">パスワード確認</label>
            <input class="form__input register-page__input" type="password" name="password_confirmation"
              id="password_confirmation" placeholder="もう一度パスワードを入力">
            <x-error.auth-message field="password_confirmation" preserve />
          </div>
        </div>

        <!-- 送信ボタン -->
        <div class="register-page__button">
          <button class="auth-button" type="submit">登録する</button>
        </div>
      </form>

      <!-- リンク -->
      <div class="register-page__link">
        <a href="/login" class="register-page__link--login">ログインはこちら</a>
      </div>
    </div>
  </div>
</div>
@endsection