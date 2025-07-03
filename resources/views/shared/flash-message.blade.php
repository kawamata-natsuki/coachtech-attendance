<div class="flash-message__wrapper">
  @if (session('success'))
  <div class="flash-message flash-message--success">
    {{ session('success') }}
  </div>
  @endif

  @if (session('error'))
  <div class="flash-message flash-message--error">
    {{ session('error') }}
  </div>
  @endif
</div>