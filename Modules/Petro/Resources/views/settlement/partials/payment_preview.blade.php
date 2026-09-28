@php
    $currency_precision = isset($currency_precision) ? (int) $currency_precision : 2;
    $money = function ($value) use ($currency_precision) {
        return number_format((float) $value, $currency_precision);
    };
    $qty = function ($value) {
        return number_format((float) $value, 3);
    };
@endphp

<div class="modal-dialog" role="document" style="width: 92%;">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">
                @if(!empty($settlement))
                    Settlement Preview - {{ $settlement->settlement_no ?: $settlement->id }}
                @else
                    Settlement Preview
                @endif
            </h4>
        </div>

        <div class="modal-body">
            @if(!empty($preview_error) || empty($settlement))
                <div class="alert alert-danger">
                    {{ $preview_error ?? __('messages.something_went_wrong') }}
                </div>
            @else
                @php
                    $meter_sales = $settlement->meter_sales ?? collect();
                    $other_sales = $settlement->other_sales ?? collect();
                    $other_incomes = $settlement->other_incomes ?? collect();
                    $customer_payments = $customer_payments_tab ?? collect();
                    $loan_payments = $settlement->loan_payments ?? collect();
                    $drawing_payments = $settlement->drawings_payments ?? collect();
                    $cash_payments = $settlement->cash_payments ?? collect();
                    $cash_deposits = $settlement->cash_deposits ?? collect();
                    $card_payments = $settlement->card_payments ?? collect();
                    $cheque_payments = $settlement->cheque_payments ?? collect();
                    $credit_sale_payments = $settlement->credit_sale_payments ?? collect();
                    $expense_payments = $settlement->expense_payments ?? collect();
                    $shortage_payments = $settlement->shortage_payments ?? collect();
                    $excess_payments = $settlement->excess_payments ?? collect();
                    $customer_loans = $settlement->customer_loans ?? collect();

                    $meter_total = 0;
                    foreach ($meter_sales as $item) {
                        $meter_line = isset($item->discount_amount) && $item->discount_amount !== null ? $item->discount_amount : (isset($item->sub_total) ? $item->sub_total : 0);
                        $meter_total += abs((float) $meter_line);
                    }

                    $other_sale_total = 0;
                    foreach ($other_sales as $item) {
                        $other_sale_total += abs((float) (isset($item->sub_total) ? $item->sub_total : 0) - (float) (isset($item->discount_amount) ? $item->discount_amount : 0));
                    }

                    $other_income_total = 0;
                    foreach ($other_incomes as $item) {
                        $other_income_total += abs((float) (isset($item->sub_total) ? $item->sub_total : 0));
                    }

                    $customer_payment_total = (float) $customer_payments->sum('sub_total');
                    $loan_payment_total = (float) $loan_payments->sum('amount');
                    $drawing_payment_total = (float) $drawing_payments->sum('amount');
                    $cash_payment_total = (float) $cash_payments->sum('amount');
                    $cash_deposit_total = (float) $cash_deposits->sum('amount');
                    $card_payment_total = (float) $card_payments->sum('amount');
                    $cheque_payment_total = (float) $cheque_payments->sum('amount');
                    $credit_sale_amount_total = (float) $credit_sale_payments->sum('amount');
                    $credit_sale_discount_total = (float) $credit_sale_payments->sum('total_discount');
                    $credit_sale_net_total = $credit_sale_amount_total - $credit_sale_discount_total;
                    $expense_payment_total = (float) $expense_payments->sum('amount');
                    $shortage_payment_total = (float) $shortage_payments->sum('amount');
                    $excess_payment_total = (float) $excess_payments->sum('amount');
                    $customer_loan_total = (float) $customer_loans->sum('amount');

                    $sales_total = $meter_total + $other_sale_total + $other_income_total + $customer_payment_total;
                    $paid_total = $loan_payment_total + $drawing_payment_total + $cash_payment_total + $cash_deposit_total + $card_payment_total + $cheque_payment_total + $credit_sale_net_total + $expense_payment_total + $shortage_payment_total + $excess_payment_total + $customer_loan_total;
                    $balance_total = $sales_total - $paid_total;
                @endphp

                <div class="row">
                    <div class="col-md-3"><strong>Settlement No:</strong> {{ $settlement->settlement_no ?: $settlement->id }}</div>
                    <div class="col-md-3"><strong>Shift No:</strong> {{ is_array($settlement->work_shift) ? implode(', ', $settlement->work_shift) : $settlement->work_shift }}</div>
                    <div class="col-md-3"><strong>Transaction Date:</strong> {{ $settlement->transaction_date ?? $settlement->date ?? '' }}</div>
                    <div class="col-md-3"><strong>Pump Operator:</strong> {{ $settlement->pump_operator_name ?? ($pump_operator->name ?? '') }}</div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-4"><strong>Sales Total:</strong> {{ $money($sales_total) }}</div>
                    <div class="col-md-4"><strong>Payments Total:</strong> {{ $money($paid_total) }}</div>
                    <div class="col-md-4"><strong>Balance:</strong> {{ $money($balance_total) }}</div>
                </div>

                <hr>

                <h4 class="text-red">@lang('petro::lang.meter_sale')</h4>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>@lang('petro::lang.code')</th>
                                <th>@lang('petro::lang.products')</th>
                                <th>@lang('petro::lang.pump')</th>
                                <th class="text-right">@lang('petro::lang.starting_meter')</th>
                                <th class="text-right">@lang('petro::lang.closing_meter')</th>
                                <th class="text-right">@lang('petro::lang.unit_price')</th>
                                <th class="text-right">@lang('petro::lang.sold_qty')</th>
                                <th class="text-right">@lang('petro::lang.testing_qty')</th>
                                <th class="text-right">@lang('petro::lang.total')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($meter_sales as $item)
                                @php
                                    $starting_meter = (float) ($item->starting_meter ?? 0);
                                    $closing_meter = (float) ($item->closing_meter ?? 0);
                                    $testing_qty = (float) ($item->testing_qty ?? 0);
                                    $sold_qty = ($closing_meter - $starting_meter) - $testing_qty;
                                    if ($sold_qty < 0) { $sold_qty = abs($sold_qty); }
                                    if (!empty($item->qty)) { $sold_qty = abs((float) $item->qty); }
                                    $line_total = isset($item->discount_amount) && $item->discount_amount !== null ? $item->discount_amount : ($item->sub_total ?? 0);
                                    $line_total = abs((float) $line_total);
                                @endphp
                                <tr>
                                    <td>{{ $product_skus[$item->product_id] ?? '' }}</td>
                                    <td>{{ $product_names[$item->product_id] ?? '' }}</td>
                                    <td>{{ $pump_names[$item->pump_id] ?? '' }}</td>
                                    <td class="text-right">{{ $qty($starting_meter) }}</td>
                                    <td class="text-right">{{ $qty($closing_meter) }}</td>
                                    <td class="text-right">{{ $money($item->price ?? 0) }}</td>
                                    <td class="text-right">{{ $qty($sold_qty) }}</td>
                                    <td class="text-right">{{ $qty($testing_qty) }}</td>
                                    <td class="text-right">{{ $money($line_total) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center">No meter sales added.</td></tr>
                            @endforelse
                            <tr><th colspan="8" class="text-right">@lang('petro::lang.total')</th><th class="text-right">{{ $money($meter_total) }}</th></tr>
                        </tbody>
                    </table>
                </div>

                <h4 class="text-red">@lang('petro::lang.other_sale')</h4>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead><tr><th>@lang('petro::lang.products')</th><th class="text-right">@lang('petro::lang.qty')</th><th class="text-right">@lang('petro::lang.total')</th></tr></thead>
                        <tbody>
                            @forelse($other_sales as $item)
                                @php $line_total = abs((float) ($item->sub_total ?? 0) - (float) ($item->discount_amount ?? 0)); @endphp
                                <tr><td>{{ $product_names[$item->product_id] ?? '' }}</td><td class="text-right">{{ $qty($item->qty ?? 0) }}</td><td class="text-right">{{ $money($line_total) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="text-center">No other sales added.</td></tr>
                            @endforelse
                            <tr><th colspan="2" class="text-right">@lang('petro::lang.total')</th><th class="text-right">{{ $money($other_sale_total) }}</th></tr>
                        </tbody>
                    </table>
                </div>

                <h4 class="text-red">@lang('petro::lang.other_income')</h4>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead><tr><th>@lang('petro::lang.service')</th><th>@lang('petro::lang.reason')</th><th class="text-right">@lang('petro::lang.total')</th></tr></thead>
                        <tbody>
                            @forelse($other_incomes as $item)
                                <tr><td>{{ $product_names[$item->product_id] ?? '' }}</td><td>{{ $item->reason ?? '' }}</td><td class="text-right">{{ $money(abs((float) ($item->sub_total ?? 0))) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="text-center">No other income added.</td></tr>
                            @endforelse
                            <tr><th colspan="2" class="text-right">@lang('petro::lang.total')</th><th class="text-right">{{ $money($other_income_total) }}</th></tr>
                        </tbody>
                    </table>
                </div>

                <h4 class="text-red">@lang('petro::lang.payment_details')</h4>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead><tr><th>Payment Type</th><th>Details</th><th class="text-right">Amount</th></tr></thead>
                        <tbody>
                            @foreach($cash_payments as $item)
                                <tr><td>@lang('petro::lang.cash')</td><td>{{ $contact_names[$item->customer_id] ?? '' }}</td><td class="text-right">{{ $money($item->amount ?? 0) }}</td></tr>
                            @endforeach
                            @foreach($cash_deposits as $item)
                                <tr><td>@lang('petro::lang.cash_deposit')</td><td>{{ $account_names[$item->bank_id] ?? '' }}</td><td class="text-right">{{ $money($item->amount ?? 0) }}</td></tr>
                            @endforeach
                            @foreach($card_payments as $item)
                                <tr><td>@lang('petro::lang.cards')</td><td>{{ $contact_names[$item->customer_id] ?? '' }} {{ !empty($item->card_number) ? ' / '.$item->card_number : '' }}</td><td class="text-right">{{ $money($item->amount ?? 0) }}</td></tr>
                            @endforeach
                            @foreach($cheque_payments as $item)
                                <tr><td>@lang('petro::lang.cheques')</td><td>{{ $contact_names[$item->customer_id] ?? '' }} {{ !empty($item->cheque_number) ? ' / '.$item->cheque_number : '' }}</td><td class="text-right">{{ $money($item->amount ?? 0) }}</td></tr>
                            @endforeach
                            @foreach($credit_sale_payments as $item)
                                @php $line_total = (float) ($item->amount ?? 0) - (float) ($item->total_discount ?? 0); @endphp
                                <tr><td>@lang('petro::lang.credit_sales')</td><td>{{ $contact_names[$item->customer_id] ?? '' }} {{ !empty($item->order_number) ? ' / '.$item->order_number : '' }}</td><td class="text-right">{{ $money($line_total) }}</td></tr>
                            @endforeach
                            @foreach($expense_payments as $item)
                                <tr><td>@lang('petro::lang.expense')</td><td>{{ $item->note ?? '' }}</td><td class="text-right">{{ $money($item->amount ?? 0) }}</td></tr>
                            @endforeach
                            @foreach($shortage_payments as $item)
                                <tr><td>@lang('petro::lang.shortage')</td><td>{{ $item->note ?? '' }}</td><td class="text-right">{{ $money($item->amount ?? 0) }}</td></tr>
                            @endforeach
                            @foreach($excess_payments as $item)
                                <tr><td>@lang('petro::lang.excess')</td><td>{{ $item->note ?? '' }}</td><td class="text-right">{{ $money($item->amount ?? 0) }}</td></tr>
                            @endforeach
                            @foreach($loan_payments as $item)
                                <tr><td>@lang('petro::lang.loan_payments')</td><td>{{ $account_names[$item->loan_account] ?? '' }}</td><td class="text-right">{{ $money($item->amount ?? 0) }}</td></tr>
                            @endforeach
                            @foreach($drawing_payments as $item)
                                <tr><td>@lang('petro::lang.drawing_payments')</td><td>{{ $account_names[$item->loan_account] ?? '' }}</td><td class="text-right">{{ $money($item->amount ?? 0) }}</td></tr>
                            @endforeach
                            @foreach($customer_loans as $item)
                                <tr><td>@lang('petro::lang.loan_to_customer')</td><td>{{ $contact_names[$item->customer_id] ?? '' }}</td><td class="text-right">{{ $money($item->amount ?? 0) }}</td></tr>
                            @endforeach
                            @if($paid_total == 0)
                                <tr><td colspan="3" class="text-center">No payment details added.</td></tr>
                            @endif
                            <tr><th colspan="2" class="text-right">@lang('petro::lang.total')</th><th class="text-right">{{ $money($paid_total) }}</th></tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="modal-footer">
            @if(!empty($settlement))
                <button type="button" id="confirm_settlement_preview_details" class="btn btn-primary pull-left" style="background-color: #007bff; color: #ffffff; border-color: #006fe6;">
                    Confirm all the entered details are correct
                </button>
            @endif
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>
