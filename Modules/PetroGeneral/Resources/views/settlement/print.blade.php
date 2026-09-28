<!-- app css -->


<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<link rel="stylesheet" href="{{ asset('css/app.css?v='.$asset_v) }}">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
<title>@lang('petrogeneral::lang.print_settlement')</title>

@php
    $business_id = session()->get('user.business_id');
    $business_details = App\Business::find($business_id);
    $currency_precision = !empty($business_details->currency_precision) ? $business_details->currency_precision : 2;
@endphp


<style>
    .settlement_print_div table. {
        border: 1px solid #222;
        margin-top: 10px;
        margin-bottom: 0px;
    }

    .settlement_print_div table.table-bordered>thead>tr>th {
        border: 1px solid #222;
    ;
    }

    .settlement_print_div table.table-bordered>tbody>tr>td {
        border: 1px solid #222;
        font-size: 13px;
    }

    .settlement_print_div {
        max-width: 100%;
        width: 100% !important;
    }

    .payment-details-table {
        width: 100%;
        table-layout: auto;
        font-size: 11px;
    }

    .payment-details-table th,
    .payment-details-table td {
        white-space: nowrap;
        padding: 4px 5px;
    }

    .payment-details-table .payment-details-total {
        min-width: 78px;
        text-align: right;
    }

    @media print {

        .no-print,
        .no-print * {
            display: none !important;
        }

        .settlement_print_div {
            max-width: 100%;
            width: 100% !important;
        }
    }
</style>
<div class="container settlement_print_div">

    <div class="col-xs-12 text-center">
        <p style="font-size: 22px;" class="text-center"><strong>{{!empty($business) ? $business->name : ""}}</strong></p>
        <p style="font-size: 16px;">@lang('petrogeneral::lang.pump_operator_sale_report')</p>

        @if (str_contains($settlement->settlement_no, 'SET-SW'))
            <a style=" border-radius: 0px !important; float: right; margin-bottom: 10px;" class="btn btn-success btn-sm btn-flat pull-right  no-print"
               href="{{ route('settlement-sw.create') }}">@lang('petrogeneral::lang.back_to_settlement_sw')</a>
        @else
            <a style=" border-radius: 0px !important; float: right; margin-bottom: 10px;" class="btn btn-success btn-sm btn-flat pull-right  no-print"
               href="{{action('\Modules\PetroGeneral\Http\Controllers\SettlementController@create')}}">@lang('petrogeneral::lang.back_to_settlement')</a>
        @endif

    </div>

    <div class="clearfix"></div>

    <div class="col-xs-12 col-xs-12" style="border-top: 2px solid #222;">
        <div class="col-md-8" style="width: 50%; float: left;">
            @lang('petrogeneral::lang.address') : {{$pump_operator->address}} <br>
            @lang('petrogeneral::lang.settlement_no') : {{$settlement->settlement_no}} <br>
            @lang('petrogeneral::lang.settlement_date') : {{$settlement->transaction_date}}
        </div>
        <div class="col-md-4" style="width: 50%; float: right;">
            @lang('petrogeneral::lang.pump_operator_name') : {{$pump_operator->name}}<br>
            @lang('petrogeneral::lang.print_date_and_time') : {{\Carbon::now()}}<br>
            @if(!empty($settlement->work_shift))
                @foreach ((array)$settlement->work_shift as $work_shift)
                    @php
                        $shift_name = 'N/A';
                        $shift_from = '';
                        $shift_to_time = ''; // Renamed to avoid loop variable conflict if any

                        $work_shift_timing = null;
                        // Check if it looks like an ID (numeric)
                        if (is_numeric($work_shift)) {
                            $work_shift_timing = \Modules\HR\Entities\WorkShift::where('id', $work_shift)->first();
                        }

                        if ($work_shift_timing) {
                            $shift_name = $work_shift_timing->shift_name;
                            $shift_from = $work_shift_timing->shift_form;
                            $shift_to_time = $work_shift_timing->shift_to;
                        } else {
                            // Fallback: If not found or not numeric, use the value as the name
                            // This handles the case where the name is stored directly
                            $shift_name = $work_shift;
                        }
                    @endphp
                    Shift No : {{$shift_name}} <br>
                    @if(!empty($shift_from))
                        @lang('petrogeneral::lang.shift_time_from') : {{$shift_from}}
                        @lang('petrogeneral::lang.to') : {{$shift_to_time}} <br>
                    @endif
                @endforeach
            @endif

        </div>
    </div>


    <div class="clearfix"></div>
    <br>
    <div class="col-xs-12 text-center"
         style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
        @lang('petrogeneral::lang.meter_sale')
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped">
                <thead>
                <tr class="row-border">
                    <th>@lang('petrogeneral::lang.code' )</th>
                    <th>@lang('petrogeneral::lang.products' )</th>
                    <th>@lang('petrogeneral::lang.pump' )</th>
                    <th>@lang('petrogeneral::lang.starting_meter')</th>
                    <th>@lang('petrogeneral::lang.closing_meter')</th>
                    <th>@lang('petrogeneral::lang.unit_price')</th>
                    <th>@lang('petrogeneral::lang.sold_qty' )</th>
                    <th>@lang('petrogeneral::lang.testing_qty' )</th>
                    <th>@lang('petrogeneral::lang.total' )</th>
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
                            $pump = Modules\PetroGeneral\Entities\Pump::where('id', $item->pump_id)->first();

                            // Match Meter Sales tab logic (avoid negative sign inversion on print):
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
                            <td>{{$product->sku}}</td>
                            <td>{{$product->name}}</td>
                            <td>{{$pump->pump_no}}</td>
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
                    <td colspan="8" style="text-align: right;">@lang('petrogeneral::lang.sub_total')</td>
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
        @lang('petrogeneral::lang.other_sale')
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.code' )</th>
                    <th>@lang('petrogeneral::lang.products' )</th>
                    <th>@lang('petrogeneral::lang.unit_price')</th>
                    <th>@lang('petrogeneral::lang.sold_qty' )</th>
                    <th>@lang('petrogeneral::lang.sub_total' )</th>
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
                            $line_settlement = Modules\PetroGeneral\Entities\Settlement::where('id', $ot_item->settlement_no)->first();
                            if (!empty($line_settlement) && str_contains($line_settlement->settlement_no, 'SET-SW')) {
                                $amount = $ot_item->sub_total;
                                $other_sale_final_total = $other_sale_final_total + $ot_item->sub_total;
                            }else{
                                $amount = $ot_item->sub_total - $ot_item->discount_amount;
                                $other_sale_final_total = $other_sale_final_total + $ot_item->sub_total-$ot_item->discount_amount;
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
                    @if (!empty($print_pump_operator_other_sales))
                        @php
                            $pump_operator_other_sales = $print_pump_operator_other_sales;
                        @endphp
                        @foreach ($pump_operator_other_sales as $pump_operator_other_sale)
                            @php
                                $product = App\Product::where('id', $pump_operator_other_sale->product_id)->first();
                                $other_sale_final_total = $other_sale_final_total + $pump_operator_other_sale->sub_total - $pump_operator_other_sale->discount_amount;
                            @endphp
                            <tr>
                                <td>{{$product->sku}}</td>
                                <td>{{$product->name}}</td>
                                <td>{{@num_format($pump_operator_other_sale->price)}}</td>
                                <td>{{@num_format($pump_operator_other_sale->qty)}}</td>
                                <td class="text-right">{{@num_format($pump_operator_other_sale->sub_total - $pump_operator_other_sale->discount_amount)}}</td>
                            </tr>
                        @endforeach
                    @endif
                @endif
                <tr>
                    <td colspan="4" style="text-align: right;">@lang('petrogeneral::lang.sub_total')</td>
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
        @lang('petrogeneral::lang.other_income')
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.service' )</th>
                    <th>@lang('petrogeneral::lang.qty' )</th>
                    <th>@lang('petrogeneral::lang.reason' )</th>
                    <th>@lang('petrogeneral::lang.sub_total' )</th>
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
                    <td colspan="3" style="text-align: right;">@lang('petrogeneral::lang.sub_total')</td>
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
        @lang('petrogeneral::lang.customer_payment')
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.customer' )</th>
                    <th>@lang('petrogeneral::lang.payment_method' )</th>
                    <th>@lang('petrogeneral::lang.amount' )</th>
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
                    <td colspan="2" style="text-align: right;">@lang('petrogeneral::lang.sub_total')</td>
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
        @lang('petrogeneral::lang.credit_sales')
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.cusotmer_name' )</th>
                    <th>@lang('petrogeneral::lang.current_outstanding_before_sale' )</th>
                    <th>@lang('petrogeneral::lang.voucher_no' )</th>
                    <th>@lang('petrogeneral::lang.product_name' )</th>
                    <th>@lang('petrogeneral::lang.qty' )</th>
                    <th>@lang('petrogeneral::lang.unit_rate' )</th>
                    <th>@lang('petrogeneral::lang.sub_total' )</th>
                    <th>@lang('petrogeneral::lang.discount_total' )</th>
                    <th>@lang('petrogeneral::lang.total' )</th>
                </tr>
                </thead>
                <tbody>
                @php
                    $credit_sale_total = $settlement->credit_sale_payments->sum('amount');
                    $credit_discount_total = $settlement->credit_sale_payments->sum('total_discount');
                    // Ensure credit sale totals pull even if records were saved with numeric settlement_no
                    $credit_sale_total = $settlement->credit_sale_payments->sum('amount');
                    $credit_discount_total = $settlement->credit_sale_payments->sum('total_discount');
                    if ($credit_sale_total == 0 && $credit_discount_total == 0) {
                        $credit_sale_total = \Modules\PetroGeneral\Entities\SettlementCreditSalePayment::whereIn('settlement_no', [$settlement->settlement_no, $settlement->id])->sum('amount');
                        $credit_discount_total = \Modules\PetroGeneral\Entities\SettlementCreditSalePayment::whereIn('settlement_no', [$settlement->settlement_no, $settlement->id])->sum('total_discount');
                    }
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
                                {{@num_format(($credit_sale_payment->amount - $credit_sale_payment->total_discount))}}
                            </td>
                        </tr>
                    @endforeach
                @endif
                <tr>
                    <td colspan="6" style="text-align: right;"><b>@lang('petrogeneral::lang.sub_total')</b>
                    </td>
                    <td>
                        {{@num_format(($credit_sale_total ))}}
                    </td>
                    <td>
                        {{@num_format(($credit_discount_total))}}
                    </td>
                    <td class="text-right">{{@num_format(($credit_sale_total - $credit_discount_total))}}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="clearfix"></div>
    <br>
    <div class="col-xs-12 text-center"
         style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
        @lang('petrogeneral::lang.expenses')
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.expense_category' )</th>
                    <th>@lang('petrogeneral::lang.reference_no' )</th>
                    <th>@lang('petrogeneral::lang.reason')</th>
                    <th>@lang('petrogeneral::lang.amount')</th>
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
                    <td colspan="3" style="text-align: right;">@lang('petrogeneral::lang.sub_total')</td>
                    <td class="text-right">{{@num_format($expense_total)}}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <br>
    <div class="col-xs-12 text-center"
         style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
        @lang('petrogeneral::lang.loan_payments')
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.loan_account' )</th>
                    <th>@lang('petrogeneral::lang.note' )</th>
                    <th>@lang('petrogeneral::lang.amount' )</th>

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
                    <td colspan="2">@lang('petrogeneral::lang.sub_total')</td>
                    <td >{{@num_format($loan_payments_total)}}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <br>
    <div class="col-xs-12 text-center"
         style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
        @lang('petrogeneral::lang.drawing_payments')
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.account' )</th>
                    <th>@lang('petrogeneral::lang.note' )</th>
                    <th>@lang('petrogeneral::lang.amount' )</th>

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
                    <td colspan="2">@lang('petrogeneral::lang.sub_total')</td>
                    <td >{{@num_format($drawings_payments_total)}}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    @php
        $settlement_keys_for_sum = [$settlement->id, $settlement->settlement_no];
        $real_time_shift_ids = [];

        if (!empty($shift_ids)) {
            $real_time_shift_ids = is_array($shift_ids) ? $shift_ids : explode(',', $shift_ids);
        }

        $real_time_shift_ids = array_values(array_filter(array_map('intval', (array) $real_time_shift_ids), function ($shift_id) {
            return $shift_id > 0;
        }));

        if (empty($real_time_shift_ids)) {
            $real_time_shift_ids = \Modules\PetroGeneral\Entities\PumpOperatorAssignment::where('business_id', $settlement->business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where('settlement_id', $settlement->id)
                ->pluck('shift_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        $real_time_payments_query = \Modules\PetroGeneral\Entities\PumpOperatorPayment::where('business_id', $settlement->business_id)
            ->where('pump_operator_id', $settlement->pump_operator_id)
            ->whereIn('payment_type', ['cash', 'card', 'pos', 'cheque', 'credit'])
            ->where(function ($q) use ($settlement_keys_for_sum, $real_time_shift_ids) {
                if (!empty($real_time_shift_ids)) {
                    $q->whereIn('shift_id', $real_time_shift_ids);
                }

                $q->orWhereIn('settlement_no', array_map('strval', $settlement_keys_for_sum));
            });

        if (empty($real_time_shift_ids)) {
            $real_time_payments_query->where('created_at', '>=', $settlement->created_at);
        }

        $real_time_payment_totals = $real_time_payments_query
            ->select('payment_type', \Illuminate\Support\Facades\DB::raw('SUM(CAST(payment_amount AS DECIMAL(15,2))) as total'))
            ->groupBy('payment_type')
            ->pluck('total', 'payment_type');

        $final_cash_amount = $settlement->cash_payments->sum('amount');
        if ($final_cash_amount == 0) {
            $final_cash_amount = \Modules\PetroGeneral\Entities\SettlementCashPayment::whereIn('settlement_no', $settlement_keys_for_sum)->sum('amount');
        }
        $final_cash_amount = max((float) $final_cash_amount, (float) ($real_time_payment_totals['cash'] ?? 0));

        $cash_deposit_total = $settlement->cash_deposits->sum('amount');
        if ($cash_deposit_total == 0) {
            $cash_deposit_total = \Modules\PetroGeneral\Entities\SettlementCashDeposit::whereIn('settlement_no', $settlement_keys_for_sum)->sum('amount');
        }

        $card_total = $settlement->card_payments->sum('amount');
        if ($card_total == 0) {
            $card_total = \Modules\PetroGeneral\Entities\SettlementCardPayment::whereIn('settlement_no', $settlement_keys_for_sum)->sum('amount');
        }
        $card_total = max(
            (float) $card_total,
            (float) ($real_time_payment_totals['card'] ?? 0) + (float) ($real_time_payment_totals['pos'] ?? 0)
        );

        $cheque_total = $settlement->cheque_payments->sum('amount');
        if ($cheque_total == 0) {
            $cheque_total = \Modules\PetroGeneral\Entities\SettlementChequePayment::whereIn('settlement_no', $settlement_keys_for_sum)->sum('amount');
        }
        $cheque_total = max((float) $cheque_total, (float) ($real_time_payment_totals['cheque'] ?? 0));

        $loan_payments_total = $settlement->loan_payments->sum('amount');
        if ($loan_payments_total == 0) {
            $loan_payments_total = \Modules\PetroGeneral\Entities\SettlementLoanPayment::whereIn('settlement_no', $settlement_keys_for_sum)->sum('amount');
        }

        $customer_loans_total = $settlement->customer_loans->sum('amount');
        if ($customer_loans_total == 0) {
            $customer_loans_total = \Modules\PetroGeneral\Entities\SettlementCustomerLoan::whereIn('settlement_no', $settlement_keys_for_sum)->sum('amount');
        }

        $expense_total = $settlement->expense_payments->sum('amount');
        if ($expense_total == 0) {
            $expense_total = \Modules\PetroGeneral\Entities\SettlementExpensePayment::whereIn('settlement_no', $settlement_keys_for_sum)->sum('amount');
        }

        $shortage_total = $settlement->shortage_payments->sum('amount');
        if ($shortage_total == 0) {
            $shortage_total = \Modules\PetroGeneral\Entities\SettlementShortagePayment::whereIn('settlement_no', $settlement_keys_for_sum)->sum('amount');
        }

        $excess_total = $settlement->excess_payments->sum('amount');
        if ($excess_total == 0) {
            $excess_total = \Modules\PetroGeneral\Entities\SettlementExcessPayment::whereIn('settlement_no', $settlement_keys_for_sum)->sum('amount');
        }

        $credit_amount_total = $settlement->credit_sale_payments->sum('amount');
        $credit_discount_total = $settlement->credit_sale_payments->sum('total_discount');
        if ($credit_amount_total == 0 && $credit_discount_total == 0) {
            $credit_amount_total = \Modules\PetroGeneral\Entities\SettlementCreditSalePayment::whereIn('settlement_no', $settlement_keys_for_sum)->sum('amount');
            $credit_discount_total = \Modules\PetroGeneral\Entities\SettlementCreditSalePayment::whereIn('settlement_no', $settlement_keys_for_sum)->sum('total_discount');
        }
        $credit_net_total = $credit_amount_total - $credit_discount_total;
        $credit_net_total = max((float) $credit_net_total, (float) ($real_time_payment_totals['credit'] ?? 0));
    @endphp

    <div class="clearfix"></div>
    <br>
    <div class="col-xs-12 text-center payment-details-section"
         style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
        @lang('petrogeneral::lang.payment_details')
    </div>
    <div class="row payment-details-section">
        <div class="col-md-12">
            <table class="table table-striped payment-details-table">
                <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.loan_payments' )</th>
                    <th>@lang('petrogeneral::lang.cash' )</th>
                    <th>@lang('petrogeneral::lang.cash_deposit' )</th>
                    <th>@lang('petrogeneral::lang.cards' )</th>
                    <th>@lang('petrogeneral::lang.cheques')</th>
                    <th>@lang('petrogeneral::lang.credit_sales')</th>
                    <th>@lang('petrogeneral::lang.expenses')</th>
                    <th>@lang('petrogeneral::lang.short')</th>
                    <th>@lang('petrogeneral::lang.excess')</th>
                    <th>@lang('petrogeneral::lang.customer_loans')</th>
                    <th>@lang('petrogeneral::lang.total')</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>{{ @num_format($loan_payments_total) }}</td>
                    <td>{{ @num_format($final_cash_amount) }}</td>
                    <td>{{ @num_format($cash_deposit_total) }}</td>
                    <td>{{ @num_format($card_total) }}</td>
                    <td>{{ @num_format($cheque_total) }}</td>
                    <td>{{ @num_format($credit_net_total) }}</td>
                    <td>{{ @num_format($expense_total) }}</td>
                    <td>{{ @num_format($shortage_total) }}</td>
                    <td>{{ @num_format($excess_total) }}</td>
                    <td>{{ @num_format($customer_loans_total) }}</td>
                    <td class="text-right red-flag payment-details-total">
                        {{ @num_format(
                            @array_sum(@array(
                                $customer_loans_total,
                                $cash_deposit_total,
                                $final_cash_amount,
                                $card_total,
                                $cheque_total,
                                ($credit_net_total) + $expense_total,
                                $shortage_total,
                                $excess_total
                            ))
                        ) }}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>


    <br>
    <div class="col-xs-12 text-center no-print"
         style="font-weight: bold; margin-bottom: 20px !important; margin-top: 10px !important; ">
        @if (str_contains($settlement->settlement_no, 'SET-SW'))
            <a style=" border-radius: 0px !important; float: right; margin-bottom: 10px;" class="btn btn-success btn-sm btn-flat pull-right  no-print"
               href="{{ route('settlement-sw.create') }}">@lang('petrogeneral::lang.back_to_settlement_sw')</a>
        @else
            <a style=" border-radius: 0px !important; float: right; margin-bottom: 10px;" class="btn btn-success btn-sm btn-flat pull-right  no-print"
               href="{{action('\Modules\PetroGeneral\Http\Controllers\SettlementController@create')}}">@lang('petrogeneral::lang.back_to_settlement')</a>
        @endif
    </div>


</div>
