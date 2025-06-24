@props([
'reason' => '',
])

<tr class="attendance-show-page__table-row">
  <th class="attendance-show-page__table-head">
    備考
  </th>

  <td class="attendance-show-page__table-cell--textarea">
    <div class="attendance-show-page__textarea-wrapper">
      <textarea
        class="attendance-show-page__textarea"
        name="reason"
        id="reason">{{ old('reason') ?? $reason }}</textarea>
    </div>

    <div class="attendance-show-page__error-wrapper">
      <x-error.attendance-message field="reason" />
    </div>

  </td>
</tr>