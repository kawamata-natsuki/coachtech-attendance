<form class="button-wrapper" method="POST" action="{{ route('user.attendances.store') }}">
  @csrf
  <button class="attendance-record-page__button  button--white" type="submit" name="action" value="break_end">
    休憩戻
  </button>
</form>