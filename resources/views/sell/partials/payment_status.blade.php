@if(($payment_status ?? '') === 'due')
    {{-- IS1485 due-status-orange: Due must be black text on orange background. --}}
    <a href="{{ action('TransactionPaymentController@show', [$id ?? 0])}}" class="view_payment_modal payment-status-label" data-orig-value="due" data-status-name="{{ __('lang_v1.due') }}"><span class="label" style="background-color:#ffa500 !important;color:#000 !important;">{{ __('lang_v1.due') }}</span></a>
@else
<a href="{{ action('TransactionPaymentController@show', [$id ?? 0])}}" class="view_payment_modal payment-status-label" data-orig-value="{{(!empty($payment_status)) ? $payment_status : 'Paid' }}" data-status-name="{{(!empty($payment_status)) ? __('lang_v1.' . $payment_status) : 'Paid' }}"><span class="label {{ (!empty($payment_status)) ? '' : 'bg-light-green' }} @payment_status($payment_status)">{{(!empty($payment_status)) ? __('lang_v1.' . $payment_status) : 'Paid' }}</span></a>
@if (isset($credit_transaction_ids) && count($credit_transaction_ids) > 0)
    <br>
    <a href="{{ action('TransactionPaymentController@show_credit_sales')}}?show_shortage=1&credit_transaction_ids={{ implode(',', ($credit_transaction_ids ?? [])) }}" class="view_payment_modal payment-status-label" data-orig-value="due" data-status-name="{{ __('lang_v1.due') }}"><span class="label @payment_status("due")">Credit Sales</span></a>
@endif
@endif
