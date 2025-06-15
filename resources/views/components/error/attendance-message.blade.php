@props(['field', 'class' => '', 'preserve' => false])

@php
$preserve = filter_var($preserve, FILTER_VALIDATE_BOOLEAN);
@endphp

@error($field)
<div class="error-message {{ $class }}">{{ $message }}</div>
@enderror