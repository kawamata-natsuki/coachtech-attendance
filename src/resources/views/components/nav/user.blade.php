<ul class="header-menu">
  <li>
    <a class="header-menu__link" href="{{ route('user.attendance.record') }}">
      勤怠
    </a>
  </li>
  <li>
    <a class="header-menu__link" href="{{ route('user.attendance.index') }}">
      勤怠一覧
    </a>
  </li>
  <li>
    <a class="header-menu__link" href="{{ route('user.correction-request.index') }}">
      申請
    </a>
  </li>
  <li>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="header-menu__link">ログアウト</button>
    </form>
  </li>
</ul>