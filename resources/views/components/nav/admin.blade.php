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
    <button class="header-menu__link" type="submit" form="logout-form">
      ログアウト
    </button>
  </li>
</ul>

<form id="logout-form" method="POST" action="{{ route('logout') }}">
  @csrf
</form>