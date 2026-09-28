@php
    $payment_type = $payment_type ?? 'cheque';
    $hide_amount_column = $payment_type === 'pre_payments';
@endphp

@if($cheque_lists->count() > 0)

      @foreach ($cheque_lists as $item)
        <tr data-cheque-amount="{{ $item->amount }}">
          <td>
            {!! Form::checkbox('select_cheques[]', $item->id, false, ['class' => 'input-icheck select_cheques']) !!}
          </td>
          <td>{{ $item->customer_name ?? '' }}</td>
          <td>{{ $item->cheque_number }}</td>
          <td>
            @if(!empty($item->cheque_date) && $item->cheque_date != '0000-00-00')
              {{ @format_date($item->cheque_date) }}
            @endif
          </td>
          <td>{{ $item->bank_name }}</td>
          <td class="one_cheque_amount {{ $hide_amount_column ? 'hide' : '' }}" data-string="{{ $item->amount }}">
            {{ @num_format($item->amount) }}
          </td>
        </tr>
      @endforeach
    @else
      <tr>
        <td colspan="{{ $hide_amount_column ? 5 : 6 }}" class="text-center">
          <p>@lang('account.no_item_found')</p>
        </td>
      </tr>
    @endif


<script>
    $('.select_cheques').change(function() {
        var $pmt = $(this).closest('.payment_row, .payment-row');
        var $pmtAmt = $pmt.find('.payment-amount');
        if (!$pmtAmt.length) {
            $pmtAmt = $pmt.find('input[name="amount"], #amount').first();
        }
        var totalChequeValue = 0;

        $(this).closest('table').find('.select_cheques:checked').each(function() {
            var $tr = $(this).closest('tr');
            var cheque_value = parseFloat($tr.data('cheque-amount') || $tr.find('.one_cheque_amount').data('string'));

            totalChequeValue += cheque_value;
        });

        $pmtAmt.val(totalChequeValue);
    });
         
</script>
