<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/layouts/common.css') }}">
  @yield('css')
  <title>coachtech 勤怠管理アプリ</title>
</head>

<body>
  <header class="header">
    <nav class="header-nav">
      <div class="header-logo">
        <a href="/attendance">
          <img class="header-logo__image" src="/images/logo.png" alt="ロゴ">
        </a>
      </div>

      @auth
      @if(Auth::user()->hasVerifiedEmail())
      @includeWhen(Auth::user()->isAdmin(), 'components.nav.admin')
      @includeUnless(Auth::user()->isAdmin(), 'components.nav.user')
      @endif
      @endauth
    </nav>
  </header>

  <main>
    @yield('content')
  </main>
</body>

</html>