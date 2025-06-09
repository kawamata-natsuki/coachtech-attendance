<ul class="header-menu">
  <li>
    <a class="header-menu__link" href="{{ route('admin.attendance.index') }}">
      勤怠一覧
    </a>
  </li>
  <li>
    <a class="header-menu__link" href="{{ route('admin.staff.index') }}">
      スタッフ一覧
    </a>
  </li>
  <li>
    <a class="header-menu__link" href="{{ route('admin.correction-request.index') }}">
      申請一覧
    </a>
  </li>
  <li>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="header-menu__link">ログアウト</button>
    </form>
  </li>
</ul>