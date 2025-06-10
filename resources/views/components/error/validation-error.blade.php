@props(['field'])

<div class="form__error">
  @error($field)
  {{ $message }}
  @else
  &nbsp;
  @enderror
</div>