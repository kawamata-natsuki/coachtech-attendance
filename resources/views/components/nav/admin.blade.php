<ul class="header-menu">
  <li>
    <a class="header-menu__link" href="{{ route('admin.attendances.index') }}">
      勤怠一覧
    </a>
  </li>
  <li>
    <a class="header-menu__link" href="{{ route('admin.staff.index') }}">
      スタッフ一覧
    </a>
  </li>
  <li>
    <a class="header-menu__link" href="{{ route('correction-requests.index') }}">
      申請一覧
    </a>
  </li>
  <li>
    @if(Auth::guard('admin')->check())
    <form id="logout-form" method="POST" action="{{ route('admin.logout') }}">
      @csrf
      <button class="header-menu__link" type="submit">
        ログアウト
      </button>
    </form>
    @else
    <a href="{{ route('admin.login') }}" class="header-menu__link">
      ログイン
    </a>
    @endif
  </li>
</ul>