@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
@endsection

@section('title', 'ログイン')

@php($isAdmin = \App\Helpers\AuthHelper::isAdminLoginPage())

@section('content')
<div class="login-page">
  <div class="login-page__container">
    <h1 class="login-page__heading content__heading">
      {{ $isAdmin ? '管理者ログイン' : 'ログイン' }}
    </h1>

    <div class="login-page__content">
      <form class="login-page__form"
        action="{{ $isAdmin 
        ? route('admin.login.store') 
        : route('user.login.store') 
        }}"
        method="post"
        novalidate>
        @csrf

        <!-- ログイン失敗時のエラー -->
        <div class="login-page__form-group">
          <x-error.auth-message field="login" />
        </div>

        <div class="login-page__form-section">
          <!-- メールアドレス -->
          <div class="login-page__form-group form-group">
            <label class="login-page__label form__label" for="email">
              メールアドレス
            </label>
            <input class="login-page__input form__input"
              type="email"
              name="email"
              id="email"
              value="{{ old('email') }}"
              placeholder="例：user@example.com">
            <x-error.auth-message field="email" preserve />
          </div>

          <!-- パスワード -->
          <div class="login-page__form-group form-group">
            <label class="login-page__label form__label" for="password">
              パスワード
            </label>
            <input class="login-page__input form__input"
              type="password"
              name="password"
              id="password"
              placeholder="8文字以上のパスワードを入力">
            <x-error.auth-message field="password" preserve />
          </div>
        </div>

        <!-- 送信ボタン -->
        <div class="login-page__button">
          <button class="auth-button" type="submit">
            {{ $isAdmin ? '管理者ログインする' : 'ログインする'}}
          </button>
        </div>
        <input
          type="hidden"
          name="login_type"
          value="{{ $isAdmin ? 'admin' : 'user' }}">
      </form>

      <!-- 会員登録リンク（一般ユーザーのみ） -->
      @unless($isAdmin)
      <div class="login-page__link">
        <a class="login-page__link--register" href="{{ route('register') }}">
          会員登録はこちら
        </a>
      </div>
      @endunless
    </div>
  </div>
</div>
@endsection