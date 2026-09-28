<div class="modal-dialog" role="document" style="width: 65%;">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">{{$settlement->settlement_no}}</h4>
        </div>

        <div class="modal-body">
            @php
            $business_id = session()->get('user.business_id');
            $business_details = App\Business::find($business_id);
            $currency_precision = !empty($business_details->currency_precision) ? $business_details->currency_precision
            : 2;
            @endphp
            <div class="row">
                <div class="col-xs-12 text-center" style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
                    @lang('petrodirect::lang.payment_details')
                </div>
                <div class="">
                    <div class="col-md-12">
                        <table class="table table-bordered table-striped">
                            <tbody>
                                
                                <tr>
                                    <th colspan="8" class="text-red">@lang('petrodirect::lang.loan_payments' )</th>
                                </tr>
                                <tr>
                                    <th colspan="7">@lang('petrodirect::lang.loan_account')</th>
                                    <th>@lang('petrodirect::lang.amount')</th>
                                </tr>
                                @foreach ($settlement->loan_payments as $loan)
                                <tr>
                                    <td colspan="7">
                                        @php
                                        $loan_account = \App\Account::findOrFail($loan->loan_account);
                                        @endphp
                                        {{!empty($loan_account) ? $loan_account->name : ''}}
                                    </td>
                                    <td>{{number_format($loan->amount, $currency_precision)}}</td>
                                </tr>
                                @endforeach
                                <tr>
                                    <th colspan="7" class="text-right">
                                        @lang('petrodirect::lang.total')
                                    </th>
                                    <td>{{number_format($settlement->loan_payments->sum('amount'), $currency_precision)}}
                                    </td>
                                </tr>
                                
                                
                                <tr>
                                    <th colspan="8" class="text-red">@lang('petrodirect::lang.cash' )</th>
                                </tr>
                                @php
                                    // Use row id when present so multiple same-amount rows are all shown (fix: duplicate amounts disappearing)
                                    $cash_payments = $settlement->cash_payments->unique(function($p) {
                                        if (!empty($p->id)) {
                                            return 'id_' . $p->id;
                                        }
                                        return 'cust_'.($p->customer_id ?? '0').'_amt_'.($p->amount ?? '0').'_cp_'.($p->customer_payment_id ?? '0');
                                    });
                                @endphp
                                <tr>
                                    <th colspan="7">@lang('petrodirect::lang.customer')</th>
                                    <th>@lang('petrodirect::lang.amount')</th>
                                </tr>
                                @foreach ($cash_payments as $cash)
                                <tr>
                                    <td colspan="7">
                                        @php
                                        $cash_customer = \App\Contact::findOrFail($cash->customer_id);
                                        @endphp
                                        {{!empty($cash_customer) ? $cash_customer->name : ''}}
                                    </td>
                                    <td>{{number_format($cash->amount, $currency_precision)}}</td>
                                </tr>
                                @endforeach
                                <tr>
                                    <th colspan="7" class="text-right">
                                        @lang('petrodirect::lang.total')
                                    </th>
                                    <td>{{number_format($cash_payments->sum('amount'), $currency_precision)}}
                                    </td>
                                </tr>
                                
                                
                                
                                
                                
                                <tr>
                                    <th colspan="8" class="text-red">@lang('petrodirect::lang.cash_deposit' )</th>
                                </tr>
                                <tr>
                                    <th colspan="7">@lang('petrodirect::lang.bank')</th>
                                    <th>@lang('petrodirect::lang.amount')</th>
                                </tr>
                                @foreach ($settlement->cash_deposits as $cash)
                                <tr>
                                    <td colspan="7">
                                        @php
                                        $cash_customer = \App\Account::findOrFail($cash->bank_id);
                                        @endphp
                                        {{!empty($cash_customer) ? $cash_customer->name : ''}}
                                    </td>
                                    <td>{{number_format($cash->amount, $currency_precision)}}</td>
                                </tr>
                                @endforeach
                                <tr>
                                    <th colspan="7" class="text-right">
                                        @lang('petrodirect::lang.total')
                                    </th>
                                    <td>{{number_format($settlement->cash_deposits->sum('amount'), $currency_precision)}}
                                    </td>
                                </tr>
                                
                                
                                
                                <tr>
                                    <th colspan="8" class="text-red">@lang('petrodirect::lang.cards' )</th>
                                </tr>
                                @php
                                    // Use row id when present so multiple same-amount rows are all shown (fix: duplicate amounts disappearing)
                                    $card_payments = $settlement->card_payments->unique(function($p) {
                                        if (!empty($p->id)) {
                                            return 'id_' . $p->id;
                                        }
                                        return ($p->customer_id ?? '0').'|'.($p->card_number ?? '').'|'.($p->amount ?? '0').'|'.($p->customer_payment_id ?? '0');
                                    });
                                @endphp
                                <tr>
                                    <th colspan="3">@lang('petrodirect::lang.customer')</th>
                                    <th colspan="2">@lang('petrodirect::lang.card_number')</th>
                                    <th>@lang('petrodirect::lang.amount')</th>
                                </tr>
                                @foreach ($card_payments as $card) 
                                <tr>
                                    <td colspan="3">
                                        @php
                                        $card_customer = \App\Contact::findOrFail($card->customer_id);
                                        @endphp
                                        {{!empty($card_customer) ? $card_customer->name : ''}}
                                    </td>
                                    <td colspan="2">
                                        {{$card->card_number}}
                                    </td>
                                    <td>{{number_format($card->amount, $currency_precision)}}</td>
                                </tr>
                                @endforeach
                                <tr>
                                    <th colspan="7" class="text-right">
                                        @lang('petrodirect::lang.total')
                                    </th>
                                    <td>{{number_format($card_payments->sum('amount'), $currency_precision)}}
                                    </td>
                                </tr>
                                <tr>
                                    <th colspan="8" class="text-red">@lang('petrodirect::lang.cheques' )</th>
                                </tr>
                                <tr>
                                    <th colspan="2">@lang('petrodirect::lang.customer')</th>
                                    <th>@lang('petrodirect::lang.bank_name')</th>
                                    <th>@lang('petrodirect::lang.cheque_number')</th>
                                    <th>@lang('petrodirect::lang.cheque_date')</th>
                                    <th>@lang('petrodirect::lang.amount')</th>
                                </tr>
                                @foreach ($settlement->cheque_payments as $cheque)
                                <tr>
                                    <td colspan="2">
                                        @php
                                        $cheque_customer = \App\Contact::findOrFail($cheque->customer_id);
                                        @endphp
                                        {{!empty($cheque_customer) ? $cheque_customer->name : ''}}
                                    </td>
                                    <td>
                                        {{$cheque->bank_name}}
                                    </td>
                                    <td>
                                        {{$cheque->cheque_number}}
                                    </td>
                                    <td>
                                        {{$cheque->cheque_date}}
                                    </td>
                                    <td>{{number_format($cheque->amount, $currency_precision)}}</td>
                                </tr>
                                @endforeach
                                <tr>
                                    <th colspan="7" class="text-right">
                                        @lang('petrodirect::lang.total')
                                    </th>
                                    <td>{{number_format($settlement->cheque_payments->sum('amount'), $currency_precision)}}
                                    </td>
                                </tr>
                                <tr>
                                    <th colspan="8" class="text-red">@lang('petrodirect::lang.credit_sales' )</th>
                                </tr>
                                <tr>
                                    <th>@lang('petrodirect::lang.customer')</th>
                                    <th>@lang('petrodirect::lang.order_number')</th>
                                    <th>@lang('petrodirect::lang.order_date')</th>
                                    <th>@lang('petrodirect::lang.product')</th>
                                    <th>@lang('petrodirect::lang.qty')</th>
                                    <th>@lang('petrodirect::lang.sub_total' )</th>
                                    <th>@lang('petrodirect::lang.discount_total' )</th>
                                    <th>@lang('petrodirect::lang.total' )</th>
                                </tr>
                                @foreach ($settlement->credit_sale_payments as $credit_sale)
                                <tr>
                                    <td>
                                        @php
                                        $credit_sale_customer = \App\Contact::findOrFail($credit_sale->customer_id);
                                        $credit_sale_product = \App\Product::findOrFail($credit_sale->product_id);
                                        @endphp
                                        {{!empty($credit_sale_customer) ? $credit_sale_customer->name : ''}}
                                    </td>
                                    <td>
                                        {{$credit_sale->order_number}}
                                    </td>
                                    <td>
                                        {{$credit_sale->order_date}}
                                    </td>
                                    <td>
                                        {{!empty($credit_sale_product) ? $credit_sale_product->name : ''}}
                                    </td>
                                    <td>
                                        {{$credit_sale->qty}}
                                    </td>
                                    <td>{{number_format($credit_sale->amount, $currency_precision)}}</td>
                                    <td>{{number_format($credit_sale->total_discount, $currency_precision)}}</td>
                                    <td>{{number_format($credit_sale->amount-$credit_sale->total_discount, $currency_precision)}}</td>
                                </tr>
                                @endforeach
                                <tr>
                                    <th colspan="2">
                                        <button data-href="{{action('\Modules\PetroDirect\Http\Controllers\AddPaymentController@productPreview', [$settlement->id])}}" type="button" class="btn-modal btn btn-primary pull-left credit_sale_product_detail" data-container=".preview_settlement" id="product_preview_btn">Credit Sales Product details</button>
                                    </th>
                                    <th colspan="3" class="text-right">
                                        @lang('petrodirect::lang.total')
                                    </th>
                                    <td>{{number_format($settlement->credit_sale_payments()->sum('amount'), $currency_precision)}}
                                    </td>
                                    <td>{{number_format($settlement->credit_sale_payments()->sum('total_discount'), $currency_precision)}}
                                    </td>
                                    <td>{{number_format(($settlement->credit_sale_payments()->sum('amount')-$settlement->credit_sale_payments()->sum('total_discount')), $currency_precision)}}
                                    </td>
                                </tr>
                                <tr>
                                    <th colspan="8" class="text-red">@lang('petrodirect::lang.expense' )</th>
                                </tr>
                                <tr>
                                    <th>@lang('petrodirect::lang.expense_number' )</th>
                                    <th colspan="2">@lang('petrodirect::lang.reference_no' )</th>
                                    <th colspan="2">@lang('petrodirect::lang.reason')</th>
                                    <th>@lang('petrodirect::lang.amount')</th>
                                </tr>
                                @foreach ($settlement->expense_payments as $expense)
                                <tr>
                                    <td>
                                        {{$expense->expense_number}}
                                    </td>
                                    <td colspan="2">
                                        {{$expense->reference_no}}
                                    </td>
                                    <td colspan="2">
                                        {{$expense->reference_no}}
                                    </td>
                                    <td>{{number_format($expense->amount, $currency_precision)}}</td>
                                </tr>
                                @endforeach
                                <tr>
                                    <th colspan="7" class="text-right">
                                        @lang('petrodirect::lang.total')
                                    </th>
                                    <td>{{number_format($settlement->expense_payments->sum('amount'), $currency_precision)}}
                                    </td>
                                </tr>
                                <tr>
                                    <th colspan="8" class="text-red">@lang('petrodirect::lang.shortage' )</th>
                                </tr>
                                <tr>
                                    <th colspan="7"></th>
                                    <th>@lang('petrodirect::lang.amount' )</th>
                                </tr>
                                @foreach ($settlement->shortage_payments as $shortage)
                                <tr>
                                    <td colspan="7"></td>
                                    <td>{{number_format($shortage->amount, $currency_precision)}}</td>
                                </tr>
                                @endforeach

                                <tr>
                                    <th colspan="7" class="text-right">
                                        @lang('petrodirect::lang.total')
                                    </th>
                                    <td>{{number_format($settlement->shortage_payments->sum('amount'), $currency_precision)}}
                                    </td>
                                </tr>

                                <tr>
                                    <th colspan="8" class="text-red">@lang('petrodirect::lang.excess' )</th>
                                </tr>
                                <tr>
                                    <th colspan="7"></th>
                                    <th>@lang('petrodirect::lang.amount' )</th>
                                </tr>
                                @foreach ($settlement->excess_payments as $excess)
                                <tr>
                                    <td colspan="7"></td>
                                    <td>{{number_format($excess->amount, $currency_precision)}}</td>
                                </tr>
                                @endforeach

                                <tr>
                                    <th colspan="7" class="text-right">
                                        @lang('petrodirect::lang.total')
                                    </th>
                                    <td>{{number_format($settlement->excess_payments->sum('amount'), $currency_precision)}}
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th colspan="7" class="text-right">
                                       <b> @lang('petrodirect::lang.total')</b>
                                    </th>
                                    <td><b>{{number_format(($settlement->loan_payments->sum('amount')
                                        + $settlement->cash_payments->sum('amount')
                                        + $settlement->cash_deposits->sum('amount')
                                        + $settlement->card_payments->sum('amount')
                                        + $settlement->cheque_payments->sum('amount')
                                        + ($settlement->credit_sale_payments()->sum('amount')
                                            -$settlement->credit_sale_payments()->sum('total_discount')
                                        )
                                        + $settlement->expense_payments->sum('amount')
                                        + $settlement->shortage_payments->sum('amount')
                                        + $settlement->excess_payments->sum('amount')
                                    ), $currency_precision)}}</b>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>

    </div>
</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->