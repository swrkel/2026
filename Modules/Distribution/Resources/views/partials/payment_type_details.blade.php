{{--
    Distribution-owned payment type details.
    Keeps expected ERP payment field names while avoiding sale_pos partial dependency.
--}}
@php
    $row_index = $row_index ?? ($loop->index ?? 0);
    $payment = is_object($payment ?? null) ? $payment : (object)($payment ?? []);
    $method = old("payment.$row_index.method", $payment->method ?? 'cash');
@endphp
<div class="col-md-3 payment-type-field payment-card-details {{ $method == 'card' ? '' : 'hide' }}">
    <div class="form-group">
        {!! Form::label("card_number_$row_index", __('lang_v1.card_no') . ':') !!}
        {!! Form::text("payment[$row_index][card_number]", $payment->card_number ?? null, ['class' => 'form-control', 'id' => "card_number_$row_index"]) !!}
    </div>
</div>
<div class="col-md-3 payment-type-field payment-cheque-details {{ $method == 'cheque' ? '' : 'hide' }}">
    <div class="form-group">
        {!! Form::label("cheque_number_$row_index", __('lang_v1.cheque_no') . ':') !!}
        {!! Form::text("payment[$row_index][cheque_number]", $payment->cheque_number ?? null, ['class' => 'form-control', 'id' => "cheque_number_$row_index"]) !!}
    </div>
</div>
<div class="col-md-3 payment-type-field payment-bank-details {{ in_array($method, ['bank_transfer', 'direct_bank_deposit']) ? '' : 'hide' }}">
    <div class="form-group">
        {!! Form::label("bank_account_number_$row_index", __('lang_v1.bank_account_no') . ':') !!}
        {!! Form::text("payment[$row_index][bank_account_number]", $payment->bank_account_number ?? null, ['class' => 'form-control', 'id' => "bank_account_number_$row_index"]) !!}
    </div>
</div>
