<form class="button-wrapper" method="POST" action="{{ route('user.attendances.store') }}">
  @csrf
  <button class="attendance-record-page__button button--black" type="submit" name="action" value="clock_in">
    出勤
  </button>
</form>