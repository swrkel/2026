<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">{{!empty($settlement) ? $settlement->settlement_no : ""}}</h4>
        </div>

        <div class="modal-body">

            @php
            $business_id = session()->get('user.business_id');
            $business_details = App\Business::find($business_id);
            $currency_precision = !empty($business_details->currency_precision) ? $business_details->currency_precision
            : 2;
            @endphp


            <style>
                .settlement_print_div table. {
                    border: 1px solid #222;
                    margin-top: 10px;
                    margin-bottom: 0px;
                }

                .settlement_print_div table.table-bordered>thead>tr>th {
                    border: 1px solid #222;
                }

                .settlement_print_div table.table-bordered>tbody>tr>td {
                    border: 1px solid #222;
                    font-size: 13px;
                }

                @media print {

                    .no-print,
                    .no-print * {
                        display: none !important;
                    }
                }
            </style>
            <div class="row">
                <div class="col-md-12 settlement_print_div">
                    <div class="col-xs-12 text-center">
                        <p style="font-size: 22px;" class="text-center"><strong>{{$business->name ?? ''}}</strong></p>
                        <p style="font-size: 16px;">@lang('petrodirect::lang.pump_operator_sale_report') 33</p>
                    </div>
                    <div class="col-xs-12 col-xs-12" style="border-top: 2px solid #222;">
                        <div class="col-md-8" style="width: 50%; float: left;">
                            @lang('petrodirect::lang.address') : {{ $pump_operator->address ?? '' }} <br>
                            @lang('petrodirect::lang.settlement_no') : {{ $settlement->settlement_no ?? '' }} <br>
                            @lang('petrodirect::lang.settlement_date') : {{ $settlement->transaction_date ?? '' }}
                        </div>
                        <div class="col-md-4" style="width: 50%; float: right;">
                            @lang('petrodirect::lang.pump_operator_name') : {{ $pump_operator->name ?? '' }}<br>
                            @lang('petrodirect::lang.print_date_and_time') : {{\Carbon::now()}}<br>
                            @if(!empty($settlement->work_shift))
                            @foreach ($settlement->work_shift as $work_shift)
                            @php
                            $work_shift_timing = \Modules\HR\Entities\WorkShift::where('id', $work_shift)->first();
                            @endphp
                            @if($work_shift_timing)
                            @lang('petrodirect::lang.shift_time_from') : {{$work_shift_timing->shift_form}}
                            @lang('petrodirect::lang.to') :
                            {{$work_shift_timing->shift_to}} <br>
                            @endif
                            @endforeach
                            @endif

                        </div>
                    </div>


                    <div class="clearfix"></div>
                    <br>
                    <div class="col-xs-12 text-center"
                        style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
                        @lang('petrodirect::lang.meter_sale')
                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr class="row-border">
                                        <th>@lang('petrodirect::lang.code' )</th>
                                        <th>@lang('petrodirect::lang.products' )</th>
                                        <th>@lang('petrodirect::lang.pump' )</th>
                                        <th>@lang('petrodirect::lang.starting_meter')</th>
                                        <th>@lang('petrodirect::lang.closing_meter')</th>
                                        <th>@lang('petrodirect::lang.unit_price')</th>
                                        <th>@lang('petrodirect::lang.sold_qty' )</th>
                                        <th>@lang('petrodirect::lang.testing_qty' )</th>
                                        <th>@lang('petrodirect::lang.total' )</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $final_total = 0.00;
                                    // Direct Settlement must never render Pumper Dashboard/Petro PD rows.
                                    $meter_sales = collect();
                                    @endphp
                                    @if (!empty($settlement))
                                    @foreach ($meter_sales as $sale)
                                    @foreach ($sale->details as $detail)
                                    @php
                                    $pump = $detail->pump;
                                    $product = $pump ? App\Product::find($pump->product_id) : null;

                                    $quantity = $detail->sold_qty;
                                    $subTotal = $detail->amount;
                                    $withDiscount = $detail->amount; // no discount model yet
                                    $final_total += $withDiscount;
                                    @endphp

                                    <tr>
                                        <td>{{ $product?->sku ?? '-' }}</td>
                                        <td>{{ $product?->name ?? '-' }}</td>
                                        <td>{{ $pump?->pump_no ?? '-' }}</td>

                                        <td>{{ number_format($detail->received_meter, 2) }}</td>
                                        <td>{{ number_format($detail->new_meter, 2) }}</td>
                                        <td>{{ number_format($detail->unit_price, 2) }}</td>

                                        <td>{{ number_format($quantity, 2) }}</td>

                                        <td>{{ number_format($sale->testing_qty ?? 0, 2) }}</td>

                                        <td>{{ number_format($subTotal, 2) }}</td>
                                    </tr>
                                    @endforeach
                                    @endforeach
                                    @if (($meter_sales->isEmpty() ?? true) && !empty($settlement->meter_sales))
                                    @foreach ($settlement->meter_sales as $item)
                                    @php
                                    $product = App\Product::where('id', $item->product_id)->first();
                                    $pump = Modules\PetroDirect\Entities\Pump::where('id', $item->pump_id)->first();
                                    $sold_qty = ($item->closing_meter - $item->starting_meter) - ($item->testing_qty ?? 0);
                                    if (!empty($pump) && $pump->bulk_sale_meter == 1) {
                                        $sold_qty = $item->qty ?? 0;
                                    }
                                    if ($sold_qty < 0) {
                                        $sold_qty = 0;
                                    }
                                    $meter_line_total = $item->discount_amount ?? $item->sub_total ?? 0;
                                    if ($meter_line_total < 0) {
                                        $meter_line_total = abs($meter_line_total);
                                    }
                                    $final_total += $meter_line_total;
                                    @endphp
                                    <tr>
                                        <td>{{ $product->sku ?? '-' }}</td>
                                        <td>{{ $product->name ?? '-' }}</td>
                                        <td>{{ $pump->pump_no ?? '-' }}</td>
                                        <td>{{ number_format($item->starting_meter, 2) }}</td>
                                        <td>{{ number_format($item->closing_meter, 2) }}</td>
                                        <td>{{ number_format($item->price, 2) }}</td>
                                        <td>{{ number_format($sold_qty, 2) }}</td>
                                        <td>{{ number_format($item->testing_qty ?? 0, 2) }}</td>
                                        <td>{{ number_format($meter_line_total, 2) }}</td>
                                    </tr>
                                    @endforeach
                                    @endif
                                    @endif
                                    <tr>
                                        <td colspan="8" style="text-align: right;"><b>@lang('petrodirect::lang.sub_total')</b></td>
                                        <td class="text-right">{{@num_format($final_total)}}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>


                    <div class="clearfix"></div>
                    <br>
                    <div class="col-xs-12 text-center"
                        style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
                        @lang('petrodirect::lang.other_sale')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('petrodirect::lang.code' )</th>
                                        <th>@lang('petrodirect::lang.products' )</th>
                                        <th class="text-right">@lang('petrodirect::lang.unit_price')</th>
                                        <th class="text-right">@lang('petrodirect::lang.sold_qty' )</th>
                                        <th class="text-right">@lang('petrodirect::lang.sub_total' )</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $other_sale_final_total = 0.00;
                                    @endphp
                                    @if (!empty($settlement))
                                    @foreach ($settlement->other_sales as $ot_item)
                                    @php
                                    $product = App\Product::where('id', $ot_item->product_id)->first();
                                    $ot_settlement = Modules\PetroDirect\Entities\Settlement::where('id',
                                    $ot_item->settlement_no)->first();
                                    if (!empty($ot_settlement) && str_contains($ot_settlement->settlement_no, 'SET-SW'))
                                    {
                                    $amount = $ot_item->sub_total;
                                    $other_sale_final_total = $other_sale_final_total + $ot_item->sub_total;
                                    }else{
                                    $amount = $ot_item->sub_total - $ot_item->discount_amount;
                                    $other_sale_final_total = $other_sale_final_total +
                                    $ot_item->sub_total-$ot_item->discount_amount;
                                    }
                                    @endphp
                                    <tr>
                                        <td>{{$product->sku}}</td>
                                        <td>{{$product->name}}</td>
                                        <td class="text-right">{{@num_format($ot_item->price)}}</td>
                                        <td class="text-right">{{@num_format($ot_item->qty)}}</td>
                                        <td class="text-right">{{@num_format($amount)}}</td>
                                    </tr>
                                    @endforeach
                                    @php
                                    $pump_operator_other_sales =
                                    \Modules\PetroDirect\Entities\PumpOperatorOtherSale::where('shift_id',
                                    $settlement->shift_id)->get();
                                    @endphp
                                    @foreach ($pump_operator_other_sales as $pump_operator_other_sale)
                                    @php
                                    $product = App\Product::where('id', $pump_operator_other_sale->product_id)->first();
                                    $other_sale_final_total = $other_sale_final_total +
                                    $pump_operator_other_sale->sub_total - $pump_operator_other_sale->discount_amount;
                                    @endphp
                                    <tr>
                                        <td>{{$product->sku}}</td>
                                        <td>{{$product->name}}</td>
                                        <td class="text-right">{{@num_format($pump_operator_other_sale->price)}}</td>
                                        <td class="text-right">{{@num_format($pump_operator_other_sale->qty)}}</td>
                                        <td class="text-right">{{@num_format($pump_operator_other_sale->sub_total -
                                            $pump_operator_other_sale->discount_amount)}}</td>
                                    </tr>
                                    @endforeach
                                    @endif
                                    <tr>
                                        <td colspan="4" style="text-align: right;"><b>@lang('petrodirect::lang.sub_total')</b></td>
                                        <td class="text-right">{{ @num_format($other_sale_final_total) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="clearfix"></div>
                    <br>
                    <div class="col-xs-12 text-center"
                        style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
                        @lang('petrodirect::lang.other_income')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('petrodirect::lang.service' )</th>
                                        <th>@lang('petrodirect::lang.qty' )</th>
                                        <th>@lang('petrodirect::lang.reason' )</th>
                                        <th>@lang('petrodirect::lang.sub_total' )</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $other_income_final_total = 0.00;
                                    @endphp
                                    @if (!empty($settlement))
                                    @foreach ($settlement->other_incomes as $other_income_item)
                                    @php
                                    $product = App\Product::where('id', $other_income_item->product_id)->first();
                                    $other_income_final_total = $other_income_final_total +
                                    $other_income_item->sub_total;
                                    @endphp
                                    <tr>
                                        <td>{{$product->name}}</td>
                                        <td>{{@num_format($other_income_item->qty)}}</td>
                                        <td>{{$other_income_item->reason}}</td>
                                        <td class="text-right">{{@num_format($other_income_item->sub_total)}}</td>
                                    </tr>
                                    @endforeach
                                    @endif
                                    <tr>
                                        <td colspan="3" style="text-align: right;">@lang('petrodirect::lang.sub_total')</td>
                                        <td class="text-right">{{@num_format($other_income_final_total)}}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>


                    <div class="clearfix"></div>
                    <br>
                    <div class="col-xs-12 text-center"
                        style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
                        @lang('petrodirect::lang.customer_payment')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('petrodirect::lang.customer' )</th>
                                        <th>@lang('petrodirect::lang.payment_method' )</th>
                                        <th>@lang('petrodirect::lang.amount' )</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $customer_payment_final_total = 0.00;
                                    @endphp
                                    @if (!empty($customer_payments_tab))
                                    @foreach ($customer_payments_tab as $customer_payment_item)
                                    @php
                                    $customer_name = App\Contact::where('id',
                                    $customer_payment_item->customer_id)->first();
                                    $customer_payment_final_total = $customer_payment_final_total +
                                    $customer_payment_item->sub_total;
                                    @endphp
                                    <tr>
                                        <td>{{!empty($customer_name) ? $customer_name->name : ''}}</td>
                                        <td>{{ucfirst($customer_payment_item->payment_method)}}</td>
                                        <td class="text-right">{{@num_format($customer_payment_item->sub_total)}}
                                        </td>
                                    </tr>
                                    @endforeach
                                    @endif
                                    <tr>
                                        <td colspan="2" style="text-align: right;">@lang('petrodirect::lang.sub_total')</td>
                                        <td class="text-right">{{@num_format($customer_payment_final_total)}}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>


                    <div class="clearfix"></div>
                    <br>
                    <div class="col-xs-12 text-center"
                        style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
                        @lang('petrodirect::lang.credit_sales')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('petrodirect::lang.cusotmer_name' )</th>
                                        <th class="text-right">@lang('petrodirect::lang.current_outstanding_before_sale' )</th>
                                        <th>@lang('petrodirect::lang.voucher_no' )</th>
                                        <th>@lang('petrodirect::lang.product_name' )</th>
                                        <th class="text-right">@lang('petrodirect::lang.qty' )</th>
                                        <th class="text-right">@lang('petrodirect::lang.unit_rate' )</th>
                                        <th class="text-right">@lang('petrodirect::lang.sub_total' )</th>
                                        <th class="text-right">@lang('petrodirect::lang.discount_total' )</th>
                                        <th class="text-right">@lang('petrodirect::lang.total' )</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $credit_sale_total = $settlement->credit_sale_payments->sum('amount');
                                    $credit_discount_total = $settlement->credit_sale_payments->sum('total_discount');
                                    @endphp
                                    @if(!empty($settlement->credit_sale_payments ))
                                    @foreach ($settlement->credit_sale_payments as $credit_sale_payment)
                                    @php
                                    $customer_name = App\Contact::where('id',
                                    $credit_sale_payment->customer_id)->first();
                                    $product = App\Product::where('id', $credit_sale_payment->product_id)->first();
                                    @endphp
                                    <tr>
                                        <td>{{!empty($customer_name) ? $customer_name->name : ''}}</td>
                                        <td class="text-right">{{@num_format($credit_sale_payment->outstanding)}}</td>
                                        <td>{{$credit_sale_payment->order_number}}</td>
                                        <td>{{!empty($product)? $product->name : '' }}</td>
                                        <td class="text-right">{{@num_format($credit_sale_payment->qty)}}</td>
                                        <td class="text-right">{{@num_format($credit_sale_payment->price)}}</td>
                                        <td class="text-right">
                                            {{@num_format($credit_sale_payment->amount)}}
                                        </td>
                                        <td class="text-right">
                                            {{@num_format($credit_sale_payment->total_discount)}}
                                        </td>
                                        <td class="text-right">
                                            {{@num_format(($credit_sale_payment->amount -
                                            $credit_sale_payment->total_discount))}}
                                        </td>
                                    </tr>
                                    @endforeach
                                    @endif
                                    <tr>
                                        <td colspan="7" style="text-align: right;"><b>@lang('petrodirect::lang.sub_total')</b></td>
                                        <td class="text-right">{{ @num_format($credit_discount_total) }}</td>
                                        <td class="text-right">{{ @num_format($credit_sale_total - $credit_discount_total) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="clearfix"></div>
                    <br>
                    <div class="col-xs-12 text-center"
                        style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
                        @lang('petrodirect::lang.expenses')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('petrodirect::lang.expense_category' )</th>
                                        <th>@lang('petrodirect::lang.reference_no' )</th>
                                        <th>@lang('petrodirect::lang.reason')</th>
                                        <th>@lang('petrodirect::lang.amount')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $expense_total = $settlement->expense_payments->sum('amount');
                                    @endphp
                                    @if(!empty($settlement->expense_payments))
                                    @foreach ($settlement->expense_payments as $expense_payment)
                                    @php
                                    $expense_category = App\ExpenseCategory::where('id',
                                    $expense_payment->category_id)->first();
                                    @endphp
                                    <tr>
                                        <td>{{!empty($expense_category) ? $expense_category->name : ''}}</td>
                                        <td>{{$expense_payment->reference_no}}</td>
                                        <td>{{$expense_payment->reason}}</td>
                                        <td class="text-right">{{@num_format($expense_payment->amount)}}</td>
                                    </tr>
                                    @endforeach
                                    @endif
                                    <tr>
                                        <td colspan="3" style="text-align: right;">@lang('petrodirect::lang.sub_total')</td>
                                        <td class="text-right">{{@num_format($expense_total)}}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <br>
                    <div class="col-xs-12 text-center"
                        style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
                        @lang('petrodirect::lang.customer_loans')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('petrodirect::lang.customer' )</th>
                                        <th>@lang('petrodirect::lang.note' )</th>
                                        <th>@lang('petrodirect::lang.amount' )</th>

                                    </tr>

                                </thead>
                                <tbody>
                                    @if(!empty($settlement->customer_loans))
                                    @foreach ($settlement->customer_loans as $customer_loans)
                                    @php
                                    $customer_name = App\Contact::where('id', $customer_loans->customer_id)->first();
                                    @endphp
                                    <tr>
                                        <td>{{$customer_name->name}}</td>
                                        <td>{{$customer_loans->note}}</td>
                                        <td>{{@num_format($customer_loans->amount) }}</td>

                                    </tr>
                                    @endforeach
                                    @endif
                                    <tr>
                                        <td colspan="2">@lang('petrodirect::lang.sub_total')</td>
                                        <td>{{@num_format($settlement->customer_loans->sum('amount'))}}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <br>
                    <div class="col-xs-12 text-center"
                        style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
                        @lang('petrodirect::lang.loan_payments')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('petrodirect::lang.loan_account' )</th>
                                        <th>@lang('petrodirect::lang.note' )</th>
                                        <th>@lang('petrodirect::lang.amount' )</th>

                                    </tr>

                                </thead>
                                <tbody>
                                    @php
                                    $loan_payments_total = $settlement->loan_payments->sum('amount');
                                    @endphp

                                    @if(!empty($settlement->loan_payments))
                                    @foreach ($settlement->loan_payments as $loan_payments)
                                    @php
                                    $loan_account = \App\Account::find($loan_payments->loan_account);
                                    @endphp
                                    <tr>
                                        <td>{{$loan_account->name}}</td>
                                        <td>{{$loan_payments->note}}</td>
                                        <td>{{@num_format($loan_payments->amount) }}</td>

                                    </tr>
                                    @endforeach
                                    @endif

                                    <tr>
                                        <td colspan="2">@lang('petrodirect::lang.sub_total')</td>
                                        <td>{{@num_format($loan_payments_total)}}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <br>
                    <div class="col-xs-12 text-center"
                        style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
                        @lang('petrodirect::lang.drawing_payments')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('petrodirect::lang.account' )</th>
                                        <th>@lang('petrodirect::lang.note' )</th>
                                        <th>@lang('petrodirect::lang.amount' )</th>

                                    </tr>

                                </thead>
                                <tbody>
                                    @php
                                    $drawings_payments_total = $settlement->drawings_payments->sum('amount');
                                    @endphp

                                    @if(!empty($settlement->drawings_payments))
                                    @foreach ($settlement->drawings_payments as $loan_payments)
                                    @php
                                    $loan_account = \App\Account::find($loan_payments->loan_account);
                                    @endphp
                                    <tr>
                                        <td>{{$loan_account->name}}</td>
                                        <td>{{$loan_payments->note}}</td>
                                        <td>{{@num_format($loan_payments->amount) }}</td>

                                    </tr>
                                    @endforeach
                                    @endif

                                    <tr>
                                        <td colspan="2">@lang('petrodirect::lang.sub_total')</td>
                                        <td>{{@num_format($drawings_payments_total)}}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>


                    <div class="clearfix"></div>
                    <br>
                    <div class="col-xs-12 text-center"
                        style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
                        @lang('petrodirect::lang.payment_details')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('petrodirect::lang.cash' )</th>
                                        <th>@lang('petrodirect::lang.cash_deposit' )</th>
                                        <th>@lang('petrodirect::lang.cards' )</th>
                                        <th>@lang('petrodirect::lang.cheques')</th>
                                        <th>@lang('petrodirect::lang.credit_sales')</th>
                                        <th>@lang('petrodirect::lang.expenses')</th>
                                        <th>@lang('petrodirect::lang.short')</th>
                                        <th>@lang('petrodirect::lang.excess')</th>
                                        <th>@lang('petrodirect::lang.total')</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <tr>
                                        <td>{{@num_format($settlement->cash_payments->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->cash_deposits->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->card_payments->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->cheque_payments->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->credit_sale_payments->sum('amount') -
                                            $settlement->credit_sale_payments->sum('total_discount') )}}
                                        </td>
                                        <td>{{@num_format($settlement->expense_payments->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->shortage_payments->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->excess_payments->sum('amount'))}}
                                        </td>
                                        <td class="text-right red-flag">
                                            {{ @num_format(
                                            $settlement->cash_deposits->sum('amount') +
                                            $settlement->cash_payments->sum('amount') +
                                            $settlement->card_payments->sum('amount') +
                                            $settlement->cheque_payments->sum('amount') +
                                            ($settlement->credit_sale_payments->sum('amount') -
                                            $settlement->credit_sale_payments->sum('total_discount')) +
                                            $settlement->expense_payments->sum('amount') +
                                            $settlement->shortage_payments->sum('amount') +
                                            $settlement->excess_payments->sum('amount')
                                            ) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Separate section for Loan and Customer Loan information (not part of paid amount) -->
                            @if($settlement->loan_payments->count() > 0 || $settlement->customer_loans->count() > 0)
                            <div class="col-xs-12" style="margin-top: 15px;">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th colspan="3" class="text-center bg-info">
                                                @lang('petrodirect::lang.additional_transactions')
                                                (@lang('petrodirect::lang.not_part_of_sales'))</th>
                                        </tr>
                                        <tr>
                                            <th>@lang('petrodirect::lang.transaction_type' )</th>
                                            <th>@lang('petrodirect::lang.amount' )</th>
                                            <th>@lang('petrodirect::lang.note')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($settlement->loan_payments->count() > 0)
                                        <tr>
                                            <td><strong>@lang('petrodirect::lang.loan_payments')</strong>
                                                (@lang('petrodirect::lang.borrowed_funds'))</td>
                                            <td>{{@num_format($settlement->loan_payments->sum('amount'))}}</td>
                                            <td class="text-muted">@lang('petrodirect::lang.money_borrowed_from_loans')</td>
                                        </tr>
                                        @endif

                                        @if($settlement->customer_loans->count() > 0)
                                        <tr>
                                            <td><strong>@lang('petrodirect::lang.customer_loans')</strong>
                                                (@lang('petrodirect::lang.loans_given'))</td>
                                            <td>{{@num_format($settlement->customer_loans->sum('amount'))}}</td>
                                            <td class="text-muted">@lang('petrodirect::lang.loans_given_to_customers')</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
