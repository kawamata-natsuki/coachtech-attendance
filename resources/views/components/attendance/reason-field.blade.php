@props([
'disabled' => false,
'reason' => '',
])

<tr class="attendance-show-page__table-row">
  <th class="attendance-show-page__table-head">
    備考
  </th>
  <td class="attendance-show-page__table-cell--textarea">
    @if ($disabled)
    <div class="attendance-show-page__text">
      {{ $reason }}
    </div>
    @else
    <textarea class="attendance-show-page__textarea" name="reason" id="reason">{{ $reason }}</textarea>
    <x-error.attendance-message field="reason" />
    @endif
  </td>
</tr>