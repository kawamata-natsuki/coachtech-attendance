<form class="button-wrapper" method="POST" action="{{ route('user.attendances.store') }}">
  @csrf
  <button class="attendance-record-page__button  button--black" type="submit" name="action" value="clock_out">
    退勤
  </button>
  <button class="attendance-record-page__button  button--white" type="submit" name="action" value="break_start">
    休憩入
  </button>
</form>