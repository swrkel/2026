<div class="modal-dialog modal-xl petropd-settlement-view-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">{{!empty($settlement) ? $settlement->settlement_no : ""}}</h4>
        </div>

        <div class="modal-body">

            @php
            $business_id = session()->get('user.business_id');
            $settlementLookups = $settlementLookups ?? [];
            $business_details = ($settlementLookups['business'] ?? $business);
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

                /* IS1773: Bootstrap 3 does not provide a reliable modal-xl width.
                   Give the settlement view enough usable width while keeping it inside
                   the browser viewport. */
                .settlement_modal .petropd-settlement-view-dialog {
                    width: calc(100% - 30px);
                    max-width: 1500px;
                    margin: 15px auto;
                }

                .settlement_modal .petropd-settlement-view-dialog .modal-body {
                    overflow-x: hidden;
                }

                .petropd-credit-sales-view-wrap {
                    width: 100%;
                    max-width: 100%;
                    overflow-x: visible;
                }

                #petropd_credit_sales_view {
                    width: 100% !important;
                    max-width: 100% !important;
                    table-layout: fixed !important;
                    margin-bottom: 0;
                }

                #petropd_credit_sales_view th,
                #petropd_credit_sales_view td {
                    padding: 7px 5px !important;
                    font-size: 11px !important;
                    line-height: 1.18 !important;
                    vertical-align: middle !important;
                    white-space: normal !important;
                    word-break: normal !important;
                    overflow-wrap: anywhere;
                }

                #petropd_credit_sales_view th {
                    text-align: center;
                    font-weight: 700;
                }

                #petropd_credit_sales_view td:nth-child(2),
                #petropd_credit_sales_view td:nth-child(5),
                #petropd_credit_sales_view td:nth-child(6),
                #petropd_credit_sales_view td:nth-child(7),
                #petropd_credit_sales_view td:nth-child(8),
                #petropd_credit_sales_view td:nth-child(9) {
                    text-align: right;
                    white-space: nowrap !important;
                }

                #petropd_credit_sales_view td:nth-child(3) {
                    text-align: center;
                }

                #petropd_credit_sales_view .petropd-credit-subtotal-label {
                    text-align: right !important;
                    white-space: nowrap !important;
                }

                @media (max-width: 991px) {
                    .settlement_modal .petropd-settlement-view-dialog {
                        width: calc(100% - 16px);
                        margin: 8px auto;
                    }

                    .petropd-credit-sales-view-wrap {
                        overflow-x: auto;
                        -webkit-overflow-scrolling: touch;
                    }

                    #petropd_credit_sales_view {
                        min-width: 900px;
                    }
                }

                @media print {

                    .no-print,
                    .no-print * {
                        display: none !important;
                    }

                    .settlement_modal .petropd-settlement-view-dialog {
                        width: 100% !important;
                        max-width: none !important;
                        margin: 0 !important;
                    }

                    #petropd_credit_sales_view th,
                    #petropd_credit_sales_view td {
                        font-size: 9px !important;
                        padding: 4px 3px !important;
                    }
                }
            </style>
            <div class="row">
                <div class="col-md-12 settlement_print_div">
                    <div class="col-xs-12 text-center">
                        <p style="font-size: 22px;" class="text-center"><strong>{{$business->name ?? ''}}</strong></p>
                        <p style="font-size: 16px;">@lang('petropd::lang.pump_operator_sale_report') 33</p>
                    </div>
                    <div class="col-xs-12 col-xs-12" style="border-top: 2px solid #222;">
                        <div class="col-md-8" style="width: 50%; float: left;">
                            @lang('petropd::lang.address') : {{$pump_operator->address}} <br>
                            @lang('petropd::lang.settlement_no') : {{$settlement->settlement_no}} <br>
                            @lang('petropd::lang.settlement_date') : {{$settlement->transaction_date}}
                        </div>
                        <div class="col-md-4" style="width: 50%; float: right;">
                            @lang('petropd::lang.pump_operator_name') : {{$pump_operator->name}}
                            @if(!empty($pump_operator))
                                <span class="label label-success no-print" style="margin-left: 6px;">
                                    <i class="fa fa-check"></i> Reconfirmed
                                </span>
                            @endif
                            <br>
                            @lang('petropd::lang.print_date_and_time') : {{\Carbon::now()}}<br>
                            @if(!empty($settlement->work_shift))
                            @foreach ($settlement->work_shift as $work_shift)
                            @php
                            $work_shift_timing = ($settlementLookups['work_shifts'] ?? collect())->get($work_shift);
                            @endphp
                            @if($work_shift_timing)
                            @lang('petropd::lang.shift_time_from') : {{$work_shift_timing->shift_form}}
                            @lang('petropd::lang.to') :
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
                        @lang('petropd::lang.meter_sale')
                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr class="row-border">
                                        <th>@lang('petropd::lang.code' )</th>
                                        <th>@lang('petropd::lang.products' )</th>
                                        <th>@lang('petropd::lang.pump' )</th>
                                        <th>@lang('petropd::lang.starting_meter')</th>
                                        <th>@lang('petropd::lang.closing_meter')</th>
                                        <th>@lang('petropd::lang.unit_price')</th>
                                        <th>@lang('petropd::lang.sold_qty' )</th>
                                        <th>@lang('petropd::lang.testing_qty' )</th>
                                        <th>@lang('petropd::lang.total' )</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $final_total = 0.00;
                                    $meter_rows = [];
                                    $seen_meter_rows = [];
                                    $pd_meter_sales = !empty($settlement) && !empty($settlement->meter_sales_pd) ? $settlement->meter_sales_pd : collect();

                                    foreach ($pd_meter_sales as $sale) {
                                        foreach (($sale->details ?? collect()) as $detail) {
                                            $pump = $detail->pump ?: ($settlementLookups['pumps'] ?? collect())->get($detail->pump_id);
                                            $product = $pump ? ($settlementLookups['products'] ?? collect())->get($pump->product_id) : null;
                                            $quantity = (float) ($detail->sold_qty ?? 0);
                                            $amount = (float) ($detail->amount ?? 0);
                                            $key = implode('|', [
                                                (int) ($detail->pump_id ?? ($pump->id ?? 0)),
                                                number_format((float) ($detail->received_meter ?? 0), 3, '.', ''),
                                                number_format((float) ($detail->new_meter ?? 0), 3, '.', ''),
                                                number_format((float) ($detail->unit_price ?? 0), 4, '.', ''),
                                                number_format($quantity, 3, '.', ''),
                                                number_format($amount, 4, '.', ''),
                                            ]);

                                            if (isset($seen_meter_rows[$key])) {
                                                continue;
                                            }

                                            $seen_meter_rows[$key] = true;
                                            $meter_rows[] = [
                                                'sku' => $product?->sku ?? '-',
                                                'name' => $product?->name ?? '-',
                                                'pump_no' => $pump?->pump_no ?? '-',
                                                'starting_meter' => (float) ($detail->received_meter ?? 0),
                                                'closing_meter' => (float) ($detail->new_meter ?? 0),
                                                'unit_price' => (float) ($detail->unit_price ?? 0),
                                                'sold_qty' => $quantity,
                                                'testing_qty' => (float) ($sale->testing_qty ?? 0),
                                                'amount' => $amount,
                                            ];
                                            $final_total += $amount;
                                        }
                                    }

                                    if (empty($meter_rows) && !empty($settlement) && !empty($settlement->meter_sales)) {
                                        foreach ($settlement->meter_sales as $item) {
                                            $product = ($settlementLookups['products'] ?? collect())->get($item->product_id);
                                            $pump = ($settlementLookups['pumps'] ?? collect())->get($item->pump_id);
                                            $sold_qty = ((float) $item->closing_meter - (float) $item->starting_meter) - (float) ($item->testing_qty ?? 0);
                                            if (!empty($pump) && $pump->bulk_sale_meter == 1) {
                                                $sold_qty = (float) ($item->qty ?? 0);
                                            }
                                            if ($sold_qty < 0) {
                                                $sold_qty = 0;
                                            }
                                            $amount = (float) ($item->discount_amount ?? $item->sub_total ?? 0);
                                            if ($amount < 0) {
                                                $amount = abs($amount);
                                            }
                                            $key = implode('|', [
                                                (int) ($item->pump_id ?? 0),
                                                number_format((float) ($item->starting_meter ?? 0), 3, '.', ''),
                                                number_format((float) ($item->closing_meter ?? 0), 3, '.', ''),
                                                number_format((float) ($item->price ?? 0), 4, '.', ''),
                                                number_format($sold_qty, 3, '.', ''),
                                                number_format($amount, 4, '.', ''),
                                            ]);

                                            if (isset($seen_meter_rows[$key])) {
                                                continue;
                                            }

                                            $seen_meter_rows[$key] = true;
                                            $meter_rows[] = [
                                                'sku' => $product->sku ?? '-',
                                                'name' => $product->name ?? '-',
                                                'pump_no' => $pump->pump_no ?? '-',
                                                'starting_meter' => (float) ($item->starting_meter ?? 0),
                                                'closing_meter' => (float) ($item->closing_meter ?? 0),
                                                'unit_price' => (float) ($item->price ?? 0),
                                                'sold_qty' => $sold_qty,
                                                'testing_qty' => (float) ($item->testing_qty ?? 0),
                                                'amount' => $amount,
                                            ];
                                            $final_total += $amount;
                                        }
                                    }
                                    @endphp
                                    @foreach ($meter_rows as $meter_row)
                                    <tr>
                                        <td>{{ $meter_row['sku'] }}</td>
                                        <td>{{ $meter_row['name'] }}</td>
                                        <td>{{ $meter_row['pump_no'] }}</td>
                                        <td>{{ number_format($meter_row['starting_meter'], 2) }}</td>
                                        <td>{{ number_format($meter_row['closing_meter'], 2) }}</td>
                                        <td>{{ number_format($meter_row['unit_price'], 2) }}</td>
                                        <td>{{ number_format($meter_row['sold_qty'], 2) }}</td>
                                        <td>{{ number_format($meter_row['testing_qty'], 2) }}</td>
                                        <td>{{ number_format($meter_row['amount'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="8" style="text-align: right;">@lang('petropd::lang.sub_total')</td>
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
                        @lang('petropd::lang.other_sale')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('petropd::lang.code' )</th>
                                        <th>@lang('petropd::lang.products' )</th>
                                        <th>@lang('petropd::lang.unit_price')</th>
                                        <th>@lang('petropd::lang.sold_qty' )</th>
                                        <th>@lang('petropd::lang.sub_total' )</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $other_sale_final_total = 0.00;
                                    @endphp
                                    @if (!empty($settlement))
                                    @foreach ($settlement->other_sales as $ot_item)
                                    @php
                                    $product = ($settlementLookups['products'] ?? collect())->get($ot_item->product_id);
                                    $ot_settlement = ($settlementLookups['settlements'] ?? collect())->get($ot_item->settlement_no);
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
                                        <td>{{@num_format($ot_item->price)}}</td>
                                        <td>{{@num_format($ot_item->qty)}}</td>
                                        <td class="text-right">{{@num_format($amount)}}</td>
                                    </tr>
                                    @endforeach
                                    @php
                                    $pump_operator_other_sales =
                                    ($settlementLookups['operator_other_sales_by_shift'] ?? collect())->get($settlement->shift_id, collect());
                                    @endphp
                                    @foreach ($pump_operator_other_sales as $pump_operator_other_sale)
                                    @php
                                    $product = ($settlementLookups['products'] ?? collect())->get($pump_operator_other_sale->product_id);
                                    $other_sale_final_total = $other_sale_final_total +
                                    $pump_operator_other_sale->sub_total - $pump_operator_other_sale->discount_amount;
                                    @endphp
                                    <tr>
                                        <td>{{$product->sku}}</td>
                                        <td>{{$product->name}}</td>
                                        <td>{{@num_format($pump_operator_other_sale->price)}}</td>
                                        <td>{{@num_format($pump_operator_other_sale->qty)}}</td>
                                        <td class="text-right">{{@num_format($pump_operator_other_sale->sub_total -
                                            $pump_operator_other_sale->discount_amount)}}</td>
                                    </tr>
                                    @endforeach
                                    @endif
                                    <tr>
                                        <td colspan="4" style="text-align: right;"><b>@lang('petropd::lang.sub_total')</b></td>
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
                        @lang('petropd::lang.other_income')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('petropd::lang.service' )</th>
                                        <th>@lang('petropd::lang.qty' )</th>
                                        <th>@lang('petropd::lang.reason' )</th>
                                        <th>@lang('petropd::lang.sub_total' )</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $other_income_final_total = 0.00;
                                    @endphp
                                    @if (!empty($settlement))
                                    @foreach ($settlement->other_incomes as $other_income_item)
                                    @php
                                    $product = ($settlementLookups['products'] ?? collect())->get($other_income_item->product_id);
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
                                        <td colspan="3" style="text-align: right;">@lang('petropd::lang.sub_total')</td>
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
                        @lang('petropd::lang.customer_payment')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('petropd::lang.customer' )</th>
                                        <th>@lang('petropd::lang.payment_method' )</th>
                                        <th>@lang('petropd::lang.amount' )</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $customer_payment_final_total = 0.00;
                                    @endphp
                                    @if (!empty($customer_payments_tab))
                                    @foreach ($customer_payments_tab as $customer_payment_item)
                                    @php
                                    $customer_name = ($settlementLookups['contacts'] ?? collect())->get($customer_payment_item->customer_id);
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
                                        <td colspan="2" style="text-align: right;">@lang('petropd::lang.sub_total')</td>
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
                        @lang('petropd::lang.credit_sales')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive petropd-credit-sales-view-wrap">
                            <table class="table table-striped" id="petropd_credit_sales_view">
                                <colgroup>
                                    <col style="width: 13%;">
                                    <col style="width: 14%;">
                                    <col style="width: 8%;">
                                    <col style="width: 14%;">
                                    <col style="width: 6%;">
                                    <col style="width: 8%;">
                                    <col style="width: 11%;">
                                    <col style="width: 11%;">
                                    <col style="width: 15%;">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>@lang('petropd::lang.cusotmer_name' )</th>
                                        <th>@lang('petropd::lang.current_outstanding_before_sale' )</th>
                                        <th>@lang('petropd::lang.voucher_no' )</th>
                                        <th>@lang('petropd::lang.product_name' )</th>
                                        <th>@lang('petropd::lang.qty' )</th>
                                        <th>@lang('petropd::lang.unit_rate' )</th>
                                        <th>@lang('petropd::lang.sub_total' )</th>
                                        <th>@lang('petropd::lang.discount_total' )</th>
                                        <th>@lang('petropd::lang.total' )</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $credit_sale_total = $settlement->credit_sale_payments->unique('id')->sum('amount');
                                    $credit_discount_total = $settlement->credit_sale_payments->unique('id')->sum('total_discount');
                                    @endphp
                                    @if(!empty($settlement->credit_sale_payments ))
                                    @foreach ($settlement->credit_sale_payments as $credit_sale_payment)
                                    @php
                                    $customer_name = ($settlementLookups['contacts'] ?? collect())->get($credit_sale_payment->customer_id);
                                    $product = ($settlementLookups['products'] ?? collect())->get($credit_sale_payment->product_id);
                                    @endphp
                                    <tr>
                                        <td>{{!empty($customer_name) ? $customer_name->name : ''}}</td>
                                        <td>{{@num_format($credit_sale_payment->outstanding)}}</td>
                                        <td>{{$credit_sale_payment->order_number}}</td>
                                        <td>{{!empty($product)? $product->name : '' }}</td>
                                        <td>{{@num_format($credit_sale_payment->qty)}}</td>
                                        <td>{{@num_format($credit_sale_payment->price)}}</td>
                                        <td>
                                            {{@num_format($credit_sale_payment->amount)}}
                                        </td>
                                        <td>
                                            {{@num_format($credit_sale_payment->total_discount)}}
                                        </td>
                                        <td>
                                            {{@num_format(($credit_sale_payment->amount -
                                            $credit_sale_payment->total_discount))}}
                                        </td>
                                    </tr>
                                    @endforeach
                                    @endif
                                    <tr>
                                        <td colspan="6" class="petropd-credit-subtotal-label"><b>@lang('petropd::lang.sub_total')</b>
                                        </td>
                                        <td class="text-right">{{ @num_format($credit_sale_total) }}</td>
                                        <td class="text-right">{{ @num_format($credit_discount_total) }}</td>
                                        <td class="text-right">{{ @num_format($credit_sale_total -
                                            $credit_discount_total) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix"></div>
                    <br>
                    <div class="col-xs-12 text-center"
                        style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
                        @lang('petropd::lang.expenses')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('petropd::lang.expense_category' )</th>
                                        <th>@lang('petropd::lang.reference_no' )</th>
                                        <th>@lang('petropd::lang.reason')</th>
                                        <th>@lang('petropd::lang.amount')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $expense_total = $settlement->expense_payments->unique('id')->sum('amount');
                                    @endphp
                                    @if(!empty($settlement->expense_payments))
                                    @foreach ($settlement->expense_payments as $expense_payment)
                                    @php
                                    $expense_category = ($settlementLookups['expense_categories'] ?? collect())->get($expense_payment->category_id);
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
                                        <td colspan="3" style="text-align: right;">@lang('petropd::lang.sub_total')</td>
                                        <td class="text-right">{{@num_format($expense_total)}}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <br>
                    <div class="col-xs-12 text-center"
                        style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
                        @lang('petropd::lang.customer_loans')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('petropd::lang.customer' )</th>
                                        <th>@lang('petropd::lang.note' )</th>
                                        <th>@lang('petropd::lang.amount' )</th>

                                    </tr>

                                </thead>
                                <tbody>
                                    @if(!empty($settlement->customer_loans))
                                    @foreach ($settlement->customer_loans as $customer_loans)
                                    @php
                                    $customer_name = ($settlementLookups['contacts'] ?? collect())->get($customer_loans->customer_id);
                                    @endphp
                                    <tr>
                                        <td>{{$customer_name->name}}</td>
                                        <td>{{$customer_loans->note}}</td>
                                        <td>{{@num_format($customer_loans->amount) }}</td>

                                    </tr>
                                    @endforeach
                                    @endif
                                    <tr>
                                        <td colspan="2">@lang('petropd::lang.sub_total')</td>
                                        <td>{{@num_format($settlement->customer_loans->unique('id')->sum('amount'))}}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <br>
                    <div class="col-xs-12 text-center"
                        style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
                        @lang('petropd::lang.loan_payments')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('petropd::lang.loan_account' )</th>
                                        <th>@lang('petropd::lang.note' )</th>
                                        <th>@lang('petropd::lang.amount' )</th>

                                    </tr>

                                </thead>
                                <tbody>
                                    @php
                                    $loan_payments_total = $settlement->loan_payments->unique('id')->sum('amount');
                                    @endphp

                                    @if(!empty($settlement->loan_payments))
                                    @foreach ($settlement->loan_payments as $loan_payments)
                                    @php
                                    $loan_account = ($settlementLookups['accounts'] ?? collect())->get($loan_payments->loan_account);
                                    @endphp
                                    <tr>
                                        <td>{{$loan_account->name}}</td>
                                        <td>{{$loan_payments->note}}</td>
                                        <td>{{@num_format($loan_payments->amount) }}</td>

                                    </tr>
                                    @endforeach
                                    @endif

                                    <tr>
                                        <td colspan="2">@lang('petropd::lang.sub_total')</td>
                                        <td>{{@num_format($loan_payments_total)}}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <br>
                    <div class="col-xs-12 text-center"
                        style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
                        @lang('petropd::lang.drawing_payments')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('petropd::lang.account' )</th>
                                        <th>@lang('petropd::lang.note' )</th>
                                        <th>@lang('petropd::lang.amount' )</th>

                                    </tr>

                                </thead>
                                <tbody>
                                    @php
                                    $drawings_payments_total = $settlement->drawings_payments->unique('id')->sum('amount');
                                    @endphp

                                    @if(!empty($settlement->drawings_payments))
                                    @foreach ($settlement->drawings_payments as $loan_payments)
                                    @php
                                    $loan_account = ($settlementLookups['accounts'] ?? collect())->get($loan_payments->loan_account);
                                    @endphp
                                    <tr>
                                        <td>{{$loan_account->name}}</td>
                                        <td>{{$loan_payments->note}}</td>
                                        <td>{{@num_format($loan_payments->amount) }}</td>

                                    </tr>
                                    @endforeach
                                    @endif

                                    <tr>
                                        <td colspan="2">@lang('petropd::lang.sub_total')</td>
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
                        @lang('petropd::lang.payment_details')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('petropd::lang.cash' )</th>
                                        <th>@lang('petropd::lang.cash_deposit' )</th>
                                        <th>@lang('petropd::lang.cards' )</th>
                                        <th>@lang('petropd::lang.cheques')</th>
                                        <th>@lang('petropd::lang.credit_sales')</th>
                                        <th>@lang('petropd::lang.expenses')</th>
                                        <th>@lang('petropd::lang.short')</th>
                                        <th>@lang('petropd::lang.excess')</th>
                                        <th>@lang('petropd::lang.total')</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <tr>
                                        <td>{{@num_format($settlement->cash_payments->unique('id')->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->cash_deposits->unique('id')->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->card_payments->unique('id')->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->cheque_payments->unique('id')->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->credit_sale_payments->unique('id')->sum('amount') -
                                            $settlement->credit_sale_payments->unique('id')->sum('total_discount') )}}
                                        </td>
                                        <td>{{@num_format($settlement->expense_payments->unique('id')->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->shortage_payments->unique('id')->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->excess_payments->unique('id')->sum('amount'))}}
                                        </td>
                                        <td class="text-right red-flag">
                                            {{ @num_format(
                                            $settlement->cash_deposits->unique('id')->sum('amount') +
                                            $settlement->cash_payments->unique('id')->sum('amount') +
                                            $settlement->card_payments->unique('id')->sum('amount') +
                                            $settlement->cheque_payments->unique('id')->sum('amount') +
                                            ($settlement->credit_sale_payments->unique('id')->sum('amount') -
                                            $settlement->credit_sale_payments->unique('id')->sum('total_discount')) +
                                            $settlement->expense_payments->unique('id')->sum('amount') +
                                            $settlement->shortage_payments->unique('id')->sum('amount') +
                                            $settlement->excess_payments->unique('id')->sum('amount')
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
                                                @lang('petropd::lang.additional_transactions')
                                                (@lang('petropd::lang.not_part_of_sales'))</th>
                                        </tr>
                                        <tr>
                                            <th>@lang('petropd::lang.transaction_type' )</th>
                                            <th>@lang('petropd::lang.amount' )</th>
                                            <th>@lang('petropd::lang.note')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($settlement->loan_payments->count() > 0)
                                        <tr>
                                            <td><strong>@lang('petropd::lang.loan_payments')</strong>
                                                (@lang('petropd::lang.borrowed_funds'))</td>
                                            <td>{{@num_format($settlement->loan_payments->unique('id')->sum('amount'))}}</td>
                                            <td class="text-muted">@lang('petropd::lang.money_borrowed_from_loans')</td>
                                        </tr>
                                        @endif

                                        @if($settlement->customer_loans->count() > 0)
                                        <tr>
                                            <td><strong>@lang('petropd::lang.customer_loans')</strong>
                                                (@lang('petropd::lang.loans_given'))</td>
                                            <td>{{@num_format($settlement->customer_loans->unique('id')->sum('amount'))}}</td>
                                            <td class="text-muted">@lang('petropd::lang.loans_given_to_customers')</td>
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
