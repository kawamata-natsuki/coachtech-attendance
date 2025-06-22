@props(['field', 'class' => '', 'preserve' => false])

@php
$preserve = filter_var($preserve, FILTER_VALIDATE_BOOLEAN);
$dotField = str_replace(['[', ']'], ['.', ''], $field);
@endphp

@if ($errors->has($field) || $errors->has($dotField))
<div class="error-message {{ $class }}">
  {{ $errors->first($field) ?? $errors->first($dotField) }}
</div>
@endif