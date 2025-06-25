<ul class="header-menu">
  <li>
    <a class="header-menu__link" href="{{ route('user.attendances.record') }}">
      勤怠
    </a>
  </li>
  <li>
    <a class="header-menu__link" href="{{ route('user.attendances.index') }}">
      勤怠一覧
    </a>
  </li>
  <li>
    <a class="header-menu__link" href="{{ route('correction-requests.index') }}">
      申請
    </a>
  </li>
  <li>
    @if(Auth::check())
    <form id="logout-form" method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="header-menu__link" type="submit">
        ログアウト
      </button>
    </form>
    @else
    <a href="{{ route('login') }}" class="header-menu__link">
      ログイン
    </a>
    @endif
  </li>
</ul>