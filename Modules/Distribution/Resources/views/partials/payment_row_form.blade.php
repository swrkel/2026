{{-- Distribution-owned payment row form. --}}
@php
    $row_index = $row_index ?? 0;
    $payment = is_object($payment ?? null) ? $payment : (object)($payment ?? []);
    $payment_line = $payment_line ?? [];
    $payment_types = $payment_types ?? ['cash' => __('lang_v1.cash'), 'card' => __('lang_v1.card'), 'cheque' => __('lang_v1.cheque'), 'bank_transfer' => __('lang_v1.bank_transfer')];
    $bank_group_accounts = $bank_group_accounts ?? [];
    $amount = $payment->amount ?? ($payment_line['amount'] ?? 0);
@endphp
<div class="row distribution-payment-row" data-row_index="{{ $row_index }}">
    <input type="hidden" class="payment_row_index" value="{{ $row_index }}">
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label("amount_$row_index", __('sale.amount') . ':*') !!}
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-money"></i></span>
                {!! Form::text("payment[$row_index][amount]", @num_format($amount), ['class' => 'form-control payment-amount input_number', 'required', 'id' => "amount_$row_index", 'placeholder' => __('sale.amount')]) !!}
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label("method_$row_index", __('lang_v1.payment_method') . ':*') !!}
            {!! Form::select("payment[$row_index][method]", $payment_types, $payment->method ?? 'cash', ['class' => 'form-control payment_types_dropdown select2', 'required', 'id' => "method_$row_index", 'style' => 'width:100%;', 'placeholder' => __('messages.please_select')]) !!}
        </div>
    </div>
    <div class="col-md-3 hide account_module">
        <div class="form-group">
            {!! Form::label("account_$row_index", __('lang_v1.bank_account') . ':') !!}
            {!! Form::select("payment[$row_index][account_id]", $bank_group_accounts, $payment->account_id ?? ($payment_line['account_id'] ?? null), ['class' => 'form-control account_id select2', 'placeholder' => __('lang_v1.please_select'), 'id' => "account_id_$row_index", 'style' => 'width:100%;']) !!}
        </div>
    </div>
    @include('distribution::partials.payment_type_details', ['payment' => $payment, 'row_index' => $row_index])
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label("note_$row_index", __('sale.payment_note') . ':') !!}
            {!! Form::textarea("payment[$row_index][note]", $payment->note ?? ($payment_line['note'] ?? null), ['class' => 'form-control', 'rows' => 3, 'id' => "note_$row_index"]) !!}
        </div>
    </div>
</div>
