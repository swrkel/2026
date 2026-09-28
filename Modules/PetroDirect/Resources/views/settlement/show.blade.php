<div class="modal-dialog modal-xl petrodirect-settlement-view-modal" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">{{!empty($settlement) ? $settlement->settlement_no : ""}}</h4>
        </div>

        <div class="modal-body">

            @php
            $currency_precision = !empty($business) && !empty($business->currency_precision)
                ? $business->currency_precision
                : 2;
            $display_work_shifts = !empty($settlement->work_shift)
                ? (is_array($settlement->work_shift) ? $settlement->work_shift : [$settlement->work_shift])
                : [];
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

                /* IS1792: keep the complete Credit Sales table inside the View modal. */
                .petrodirect-settlement-view-modal {
                    width: calc(100% - 30px) !important;
                    max-width: 1500px;
                    margin-left: auto;
                    margin-right: auto;
                }

                .petrodirect-settlement-view-modal .modal-body {
                    overflow-x: hidden;
                }

                .petrodirect-credit-sales-table-wrap {
                    width: 100%;
                    max-width: 100%;
                    overflow-x: hidden;
                }

                .petrodirect-credit-sales-table {
                    width: 100% !important;
                    max-width: 100%;
                    min-width: 0 !important;
                    table-layout: fixed;
                    margin-bottom: 0;
                }

                .petrodirect-credit-sales-table > thead > tr > th,
                .petrodirect-credit-sales-table > tbody > tr > td {
                    padding: 7px 5px !important;
                    vertical-align: middle !important;
                    line-height: 1.25;
                }

                .petrodirect-credit-sales-table > thead > tr > th {
                    font-size: 11px !important;
                    white-space: normal !important;
                    overflow-wrap: anywhere;
                    word-break: normal;
                }

                .petrodirect-credit-sales-table > tbody > tr > td {
                    font-size: 12px !important;
                }

                .petrodirect-credit-sales-table .credit-customer,
                .petrodirect-credit-sales-table .credit-product,
                .petrodirect-credit-sales-table .credit-outstanding-heading {
                    white-space: normal !important;
                    overflow-wrap: anywhere;
                }

                .petrodirect-credit-sales-table .credit-number,
                .petrodirect-credit-sales-table .credit-voucher {
                    white-space: nowrap !important;
                }

                @media (max-width: 991px) {
                    .petrodirect-credit-sales-table-wrap {
                        overflow-x: auto;
                    }

                    .petrodirect-credit-sales-table {
                        min-width: 850px !important;
                    }
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
                        <p style="font-size: 22px;" class="text-center"><strong>{{!empty($business) ? $business->name : ""}}</strong></p>
                        <p style="font-size: 16px;">@lang('petrodirect::lang.pump_operator_sale_report')</p>
                    </div>
                    <div class="col-xs-12 col-xs-12" style="border-top: 2px solid #222;">
                        <div class="col-md-8" style="width: 50%; float: left;">
                            @lang('petrodirect::lang.address') : {{optional($pump_operator)->address ?? ''}} <br>
                            @lang('petrodirect::lang.settlement_no') : {{$settlement->settlement_no}} <br>
                            @lang('petrodirect::lang.settlement_date') : {{$settlement->transaction_date}}
                        </div>
                        <div class="col-md-4" style="width: 50%; float: right;">
                            @lang('petrodirect::lang.pump_operator_name') : {{optional($pump_operator)->name ?? ($settlement->pump_operator_name ?? '')}}<br>
                            @lang('petrodirect::lang.print_date_and_time') : {{\Carbon::now()}}<br>
                            @foreach ($display_work_shifts as $work_shift)
                            @php
                            $shift_name = $work_shift;
                            $shift_from = '';
                            $shift_to = '';
                            $work_shift_timing = is_numeric($work_shift)
                                ? \Modules\HR\Entities\WorkShift::where('id', $work_shift)->first()
                                : null;

                            if (!empty($work_shift_timing)) {
                                $shift_name = $work_shift_timing->shift_name;
                                $shift_from = $work_shift_timing->shift_form;
                                $shift_to = $work_shift_timing->shift_to;
                            }
                            @endphp
                            Shift No : {{$shift_name ?: 'N/A'}} <br>
                            @if(!empty($shift_from))
                            @lang('petrodirect::lang.shift_time_from') : {{$shift_from}}
                            @lang('petrodirect::lang.to') :
                            {{$shift_to}} <br>
                            @endif
                           @endforeach

                        </div>
                    </div>


                    <div class="clearfix"></div>
                    <br>
                    <div class="col-xs-12 text-center"
                        style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
                        @lang('petrodirect::lang.meter_sale')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
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
                                    @endphp
                                    @if (!empty($settlement))
                                    @foreach ($settlement->meter_sales as $item)
                                    @php
                                    $product = App\Product::where('id', $item->product_id)->first();
                                    $pump = Modules\PetroDirect\Entities\Pump::where('id', $item->pump_id)->first();

                                    // Match Meter Sales tab logic (avoid negative sign inversion on view):
                                    // Sold Qty = Closing - Starting - Testing (bulk meter uses stored qty).
                                    $sold_qty = ($item->closing_meter - $item->starting_meter) - ($item->testing_qty ?? 0);
                                    if (!empty($pump) && $pump->bulk_sale_meter == 1) {
                                        $sold_qty = $item->qty ?? 0;
                                    }
                                    if ($sold_qty < 0) {
                                        $sold_qty = 0;
                                    }

                                    // "discount_amount" is used as the after-discount total in the Meter Sales tab.
                                    $meter_line_total = $item->discount_amount ?? $item->sub_total ?? 0;
                                    if ($meter_line_total < 0) {
                                        $meter_line_total = abs($meter_line_total);
                                    }

                                    $final_total = $final_total + $meter_line_total;
                                    @endphp
                                    <tr>
                                        <td>{{optional($product)->sku ?? ''}}</td>
                                        <td>{{optional($product)->name ?? ''}}</td>
                                        <td>{{optional($pump)->pump_no ?? ''}}</td>
                                        <td>{{number_format($item->starting_meter,'3','.',',')}}</td>
                						<td>{{number_format($item->closing_meter,'3','.',',')}}</td>
                                        <td>{{@num_format($item->price)}}</td>
                                        <td>{{@num_format($sold_qty)}}</td>
                                        <td>{{@num_format($item->testing_qty)}}</td>
                                        <td class="text-right">{{@num_format($meter_line_total)}}</td>
                                    </tr>
                                    @endforeach
                                    @endif
                                    <tr>
                                        <td colspan="8" style="text-align: right;">@lang('petrodirect::lang.sub_total')</td>
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
                                        <th>@lang('petrodirect::lang.unit_price')</th>
                                        <th>@lang('petrodirect::lang.sold_qty' )</th>
                                        <th>@lang('petrodirect::lang.sub_total' )</th>
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
                                    // This view is scoped to Petro Direct settlements only. Do not
                                    // replace the active settlement object or pull Pumper/PetroPD sales.
                                    $amount = (float) ($ot_item->sub_total ?? 0) - (float) ($ot_item->discount_amount ?? 0);
                                    $other_sale_final_total += $amount;
                                    @endphp
                                    <tr>
                                        <td>{{optional($product)->sku ?? ''}}</td>
                                        <td>{{optional($product)->name ?? ''}}</td>
                                        <td>{{@num_format($ot_item->price)}}</td>
                                        <td>{{@num_format($ot_item->qty)}}</td>
                                        <td class="text-right">{{@num_format($amount)}}</td>
                                    </tr>
                                    @endforeach
                                    @endif
                                    <tr>
                                        <td colspan="4" style="text-align: right;">@lang('petrodirect::lang.sub_total')</td>
                                        <td class="text-right">{{@num_format($other_sale_final_total)}}</td>
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
                                        <td>{{optional($product)->name ?? ''}}</td>
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
                            <div class="petrodirect-credit-sales-table-wrap">
                                <table class="table table-striped petrodirect-credit-sales-table">
                                    <colgroup>
                                        <col style="width: 14%;">
                                        <col style="width: 15%;">
                                        <col style="width: 10%;">
                                        <col style="width: 14%;">
                                        <col style="width: 7%;">
                                        <col style="width: 9%;">
                                        <col style="width: 10%;">
                                        <col style="width: 11%;">
                                        <col style="width: 10%;">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th class="credit-customer">@lang('petrodirect::lang.cusotmer_name' )</th>
                                            <th class="credit-outstanding-heading">@lang('petrodirect::lang.current_outstanding_before_sale' )</th>
                                            <th>@lang('petrodirect::lang.voucher_no' )</th>
                                            <th class="credit-product">@lang('petrodirect::lang.product_name' )</th>
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
                                        $product = !empty($credit_sale_payment->product)
                                            ? $credit_sale_payment->product
                                            : App\Product::where('id', $credit_sale_payment->product_id)->first();
                                        @endphp
                                        <tr>
                                            <td class="credit-customer">{{!empty($customer_name) ? $customer_name->name : ''}}</td>
                                            <td class="text-right credit-number">{{@num_format($credit_sale_payment->outstanding)}}</td>
                                            <td class="credit-voucher">{{$credit_sale_payment->order_number}}</td>
                                            <td class="credit-product">{{!empty($product)? $product->name : '' }}</td>
                                            <td class="text-right credit-number">{{@num_format($credit_sale_payment->qty)}}</td>
                                            <td class="text-right credit-number">{{@num_format($credit_sale_payment->price)}}</td>
                                            <td class="text-right credit-number">
                                                {{@num_format($credit_sale_payment->amount)}}
                                            </td>
                                            <td class="text-right credit-number">
                                                {{@num_format($credit_sale_payment->total_discount)}}
                                            </td>
                                            <td class="text-right credit-number">
                                                {{@num_format(($credit_sale_payment->amount - $credit_sale_payment->total_discount))}}
                                            </td>
                                        </tr>
                                        @endforeach
                                        @endif
                                        <tr>
                                            <td colspan="6" class="text-right"><b>@lang('petrodirect::lang.sub_total')</b></td>
                                            <td class="text-right credit-number">
                                                {{@num_format(($credit_sale_total ))}}
                                            </td>
                                            <td class="text-right credit-number">
                                                {{@num_format(($credit_discount_total))}}
                                            </td>
                                            <td class="text-right credit-number">{{@num_format(($credit_sale_total - $credit_discount_total))}}</td>
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
                                                <td>{{optional($customer_name)->name ?? ''}}</td>
                                                <td>{{$customer_loans->note}}</td>
                                                <td>{{@num_format($customer_loans->amount) }}</td>
                                                
                                            </tr>
                                        @endforeach
                                    @endif
                                    <tr>
                                        <td colspan="2">@lang('petrodirect::lang.sub_total')</td>
                                        <td >{{@num_format($settlement->customer_loans->sum('amount'))}}</td>
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
                                                <td>{{optional($loan_account)->name ?? ''}}</td>
                                                <td>{{$loan_payments->note}}</td>
                                                <td>{{@num_format($loan_payments->amount) }}</td>
                                                
                                            </tr>
                                        @endforeach
                                    @endif
                                    
                                    <tr>
                                        <td colspan="2">@lang('petrodirect::lang.sub_total')</td>
                                        <td >{{@num_format($loan_payments_total)}}</td>
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
                                                <td>{{optional($loan_account)->name ?? ''}}</td>
                                                <td>{{$loan_payments->note}}</td>
                                                <td>{{@num_format($loan_payments->amount) }}</td>
                                                
                                            </tr>
                                        @endforeach
                                    @endif
                                    
                                    <tr>
                                        <td colspan="2">@lang('petrodirect::lang.sub_total')</td>
                                        <td >{{@num_format($drawings_payments_total)}}</td>
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
                                        <th>@lang('petrodirect::lang.loan_payments' )</th>
                                        <th>@lang('petrodirect::lang.cash' )</th>
                                        <th>@lang('petrodirect::lang.cash_deposit' )</th>
                                        <th>@lang('petrodirect::lang.cards' )</th>
                                        <th>@lang('petrodirect::lang.cheques')</th>
                                        <th>@lang('petrodirect::lang.credit_sales')</th>
                                        <th>@lang('petrodirect::lang.expenses')</th>
                                        <th>@lang('petrodirect::lang.short')</th>
                                        <th>@lang('petrodirect::lang.excess')</th>
                                        <th>@lang('petrodirect::lang.customer_loans')</th>
                                        <th>@lang('petrodirect::lang.total')</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <tr>
                                        <td>{{@num_format($settlement->loan_payments->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->cash_payments->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->cash_deposits->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->card_payments->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->cheque_payments->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->credit_sale_payments->sum('amount') - $settlement->credit_sale_payments->sum('total_discount') )}}
                                        </td>
                                        <td>{{@num_format($settlement->expense_payments->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->shortage_payments->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->excess_payments->sum('amount'))}}
                                        </td>
                                        <td>{{@num_format($settlement->customer_loans->sum('amount'))}}</td>
                                        <td class="text-right red-flag">
                                            {{ @num_format(
                                                @array_sum(@array($settlement->customer_loans->sum('amount'),
                                                $settlement->loan_payments->sum('amount'),
                                                $settlement->cash_deposits->sum('amount') ,
                                                $settlement->cash_payments->sum('amount') ,
                                                $settlement->card_payments->sum('amount') ,
                                                $settlement->cheque_payments->sum('amount') ,
                                                ($settlement->credit_sale_payments->sum('amount') - $settlement->credit_sale_payments->sum('total_discount')) +
                                                $settlement->expense_payments->sum('amount') ,
                                                $settlement->shortage_payments->sum('amount') ,
                                                $settlement->excess_payments->sum('amount')))
                                            ) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
