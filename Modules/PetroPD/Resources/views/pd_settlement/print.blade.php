<!-- app css -->


<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<link rel="stylesheet" href="{{ asset('css/app.css?v='.$asset_v) }}">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
<title>@lang('petropd::lang.print_settlement')</title>

@php
$business_id = session()->get('user.business_id');
$settlementLookups = $settlementLookups ?? [];
$business_details = ($settlementLookups['business'] ?? $business);
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

		.payment-details-section {
			break-inside: avoid;
			page-break-inside: avoid;
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
                        <p style="font-size: 16px;">@lang('petropd::lang.pump_operator_sale_report')</p>

                        @if (str_contains($settlement->settlement_no, 'SET-SW'))
                            <a style=" border-radius: 0px !important; float: right; margin-bottom: 10px;" class="btn btn-success btn-sm btn-flat pull-right  no-print"
                            href="{{ route('settlement-sw.create') }}">@lang('petropd::lang.back_to_settlement_sw')</a>
                        @elseif (str_starts_with($settlement->settlement_no, 'PDST'))
                            <a style=" border-radius: 0px !important; float: right; margin-bottom: 10px;" class="btn btn-success btn-sm btn-flat pull-right no-print back-to-settlement"
                            href="{{ route('petropd.pd-settlement', ['after_finalize' => 1, '_ts' => now()->timestamp]) }}">@lang('petropd::lang.back_to_settlement')</a>
                        @else
                            <a style=" border-radius: 0px !important; float: right; margin-bottom: 10px;" class="btn btn-success btn-sm btn-flat pull-right no-print back-to-settlement"
                            href="{{ url('/petropd/settlement-pd/create') }}">@lang('petropd::lang.back_to_settlement')</a>
                        @endif

                    </div>

                    <div class="clearfix"></div>

                    <div class="col-xs-12 col-xs-12" style="border-top: 2px solid #222;">
                        <div class="col-md-8" style="width: 50%; float: left;">
                            @lang('petropd::lang.address') : {{$pump_operator->address}} <br>
                            @lang('petropd::lang.settlement_no') : {{$settlement->settlement_no}} <br>
                            @lang('petropd::lang.settlement_date') : {{$settlement->transaction_date}}
                        </div>
                        <div class="col-md-4" style="width: 50%; float: right;">
                            @lang('petropd::lang.pump_operator_name') : {{$pump_operator->name}}<br>
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
                                            /*
                                             * MA-002 (LA-1137): the de-duplication key must NOT
                                             * include quantity or amount.
                                             *
                                             * This section merges two sources - the close-pump
                                             * details (meter_sales_pd) and the settlement's own
                                             * meter_sales - and they compute sold quantity
                                             * differently for the SAME physical reading:
                                             *
                                             *   close-pump detail : sold_qty as recorded, with the
                                             *                       testing litres already taken off
                                             *   meter_sales       : (closing - starting) - testing_qty,
                                             *                       and testing_qty is empty on these
                                             *                       rows
                                             *
                                             * On the reported settlement that gave 133 from one and
                                             * 140 from the other for pump D1. Different key, so the
                                             * dedup missed and the same reading printed twice - once
                                             * with testing and once without - and the Sub Total came
                                             * out at exactly double.
                                             *
                                             * A pump reading is identified by the pump and its
                                             * start/end meters at a given price. Two rows sharing
                                             * those ARE the same reading, whatever quantity each
                                             * source calculated, so the key is built from those
                                             * alone.
                                             */
                                            $key = implode('|', [
                                                (int) ($detail->pump_id ?? ($pump->id ?? 0)),
                                                number_format((float) ($detail->received_meter ?? 0), 3, '.', ''),
                                                number_format((float) ($detail->new_meter ?? 0), 3, '.', ''),
                                                number_format((float) ($detail->unit_price ?? 0), 4, '.', ''),
                                            ]);

                                            if (isset($seen_meter_rows[$key])) {
                                                continue;
                                            }

                                            $seen_meter_rows[$key] = true;
                                            $meter_rows[] = [
                                                'sku' => $product?->sku ?? ($detail->product_code ?? $detail->sku ?? '-'),
                                                'name' => $product?->name ?? ($detail->product_name ?? $detail->name ?? '-'),
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

                                    // IS1814-7: Always append manual/additional Meter Sales.
                                    // Previously these rows were shown only when there were no close-pump
                                    // rows, so a finalized settlement containing both sources printed an
                                    // incomplete Meter Sales section.
                                    if (!empty($settlement) && !empty($settlement->meter_sales)) {
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
                                            // MA-002 (LA-1137): same key shape as the close-pump
                                            // loop above - pump and meter readings only - so a
                                            // reading already added there is recognised here.
                                            $key = implode('|', [
                                                (int) ($item->pump_id ?? 0),
                                                number_format((float) ($item->starting_meter ?? 0), 3, '.', ''),
                                                number_format((float) ($item->closing_meter ?? 0), 3, '.', ''),
                                                number_format((float) ($item->price ?? 0), 4, '.', ''),
                                            ]);

                                            if (isset($seen_meter_rows[$key])) {
                                                continue;
                                            }

                                            $seen_meter_rows[$key] = true;
                                            $meter_rows[] = [
                                                'sku' => $product->sku ?? ($item->product_code ?? $item->sku ?? '-'),
                                                'name' => $product->name ?? ($item->product_name ?? $item->name ?? '-'),
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
                                    if ($ot_settlement && str_contains($ot_settlement->settlement_no, 'SET-SW')) {
                                        $amount = $ot_item->sub_total;
                                        $other_sale_final_total = $other_sale_final_total + $ot_item->sub_total;
                                    } else {
                                        $amount = $ot_item->sub_total - ($ot_item->discount_amount ?? 0);
                                        $other_sale_final_total = $other_sale_final_total + $ot_item->sub_total - ($ot_item->discount_amount ?? 0);
                                    }
                                    @endphp
                                    <tr>
                                        <td>{{$product->sku ?? ''}}</td>
                                        <td>{{$product->name ?? ''}}</td>
                                        <td>{{@num_format($ot_item->price)}}</td>
                                        <td>{{@num_format($ot_item->qty)}}</td>
                                        <td class="text-right">{{@num_format($amount)}}</td>
                                    </tr>
                                    @endforeach
                                        @if (!empty($shift_ids))
                                            @php
                                                $pump_operator_other_sales = ($settlementLookups['operator_other_sales'] ?? collect());
                                            @endphp
                                            @foreach ($pump_operator_other_sales as $pump_operator_other_sale)
                                                @php
                                                    $product = ($settlementLookups['products'] ?? collect())->get($pump_operator_other_sale->product_id);
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
                                        <td colspan="4" style="text-align: right;">@lang('petropd::lang.sub_total')</td>
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
                            <table class="table table-striped">
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
                                        <td>{{ !empty($customer_name) ? $customer_name->name : ($credit_sale_payment->customer_name ?? '-') }}</td>
                                        <td>{{@num_format($credit_sale_payment->outstanding)}}</td>
                                        <td>{{$credit_sale_payment->order_number}}</td>
                                        <td>{{ !empty($product) ? $product->name : ($credit_sale_payment->product_name ?? '-') }}</td>
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
                                        <td colspan="6" style="text-align: right;"><b>@lang('petropd::lang.sub_total')</b>
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

                    {{--
                        IS2048: the Cash and Credit Cards detail sections are shown
                        on the settlement PRINT but hidden in the finalize PREVIEW.

                        The preview already lists the same payments in the Payment
                        Summary at the bottom of that screen, so repeating them here
                        was duplication. The print opened from List PD Settlement has
                        no such summary, so it keeps them.

                        Both screens render THIS file - payment_preview.blade.php
                        @includes it - so the two sections are gated on a flag the
                        preview sets. Anything else including this report is
                        unaffected, because the flag is absent and the sections show
                        as before.
                    --}}
                    @if (empty($hide_cash_card_sections))

                    {{--
                        LA-1169 #2: cash and card payment DETAILS on the printout.

                        Before this, the report showed only a single combined figure
                        for cards in the Payment Details row, and nothing at all for
                        cash - so there was no way to see which payments made up the
                        totals. These two sections list them line by line, in the
                        same table style as the sections above.

                        Both read the relations already loaded for this settlement
                        ($settlement->cash_payments / ->card_payments), which are the
                        same shift-filtered sets the totals are calculated from, so a
                        detail line can never disagree with the total beneath it.
                    --}}
                    <div class="clearfix"></div>
                    <br>
                    <div class="col-xs-12 text-center"
                        style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
                        @lang('petropd::lang.cash')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('petropd::lang.cusotmer_name')</th>
                                        <th>@lang('petropd::lang.collection_form_no')</th>
                                        <th>@lang('petropd::lang.note')</th>
                                        <th class="text-right">@lang('petropd::lang.amount')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    /*
                                     |------------------------------------------------------
                                     | Each cash payment counted ONCE.
                                     |------------------------------------------------------
                                     |
                                     | Reported: the reconfirm form showed the cash total
                                     | doubled - 189,929.68 appearing as twice that.
                                     |
                                     | The stored rows are correct. Settlement 6 holds
                                     | exactly three, and they sum to the right figure:
                                     |
                                     |     36,607.68 + 97,767.00 + 55,555.00 = 189,929.68
                                     |
                                     | So the duplication is in what reaches this view, not
                                     | in the data. The relation can be loaded more than
                                     | once when the settlement is rebuilt during finalise,
                                     | and a row appearing twice is both listed twice and
                                     | counted twice.
                                     |
                                     | unique('id') keeps one entry per payment. Where the
                                     | collection was already clean this changes nothing at
                                     | all - three distinct rows stay three.
                                     |
                                     | The total is derived from the SAME filtered list that
                                     | is printed, so the table and its total can no longer
                                     | disagree with each other.
                                     */
                                    $pd_cash_payments = ($settlement->cash_payments ?? collect())->unique('id')->values();
                                    $pd_cash_total = $pd_cash_payments->unique('id')->sum('amount');
                                    @endphp
                                    @forelse ($pd_cash_payments as $cash_payment)
                                    @php
                                    $cash_contact = ($settlementLookups['contacts'] ?? collect())->get($cash_payment->customer_id);
                                    @endphp
                                    <tr>
                                        <td>{{ !empty($cash_contact) ? $cash_contact->name : '' }}</td>
                                        <td>{{ $cash_payment->collection_form_no ?? '' }}</td>
                                        <td>{{ $cash_payment->note ?? '' }}</td>
                                        <td class="text-right">{{ @num_format($cash_payment->amount) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center">-</td>
                                    </tr>
                                    @endforelse
                                    <tr>
                                        <td colspan="3" style="text-align: right;">@lang('petropd::lang.sub_total')</td>
                                        <td class="text-right">{{ @num_format($pd_cash_total) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="clearfix"></div>
                    <br>
                    <div class="col-xs-12 text-center"
                        style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
                        @lang('petropd::lang.credit_cards')
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>@lang('petropd::lang.cusotmer_name')</th>
                                        <th>@lang('petropd::lang.card_type')</th>
                                        <th>@lang('petropd::lang.card_number')</th>
                                        <th>@lang('petropd::lang.slip_no')</th>
                                        <th class="text-right">@lang('petropd::lang.amount')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $pd_card_payments = $settlement->card_payments ?? collect();
                                    $pd_card_total = $pd_card_payments->unique('id')->sum('amount');
                                    @endphp
                                    @forelse ($pd_card_payments as $card_payment)
                                    @php
                                    $card_contact = ($settlementLookups['contacts'] ?? collect())->get($card_payment->customer_id);
                                    @endphp
                                    <tr>
                                        <td>{{ !empty($card_contact) ? $card_contact->name : '' }}</td>
                                        <td>{{ $card_payment->card_type ?? '' }}</td>
                                        <td>{{ $card_payment->card_number ?? '' }}</td>
                                        <td>{{ $card_payment->slip_no ?? '' }}</td>
                                        <td class="text-right">{{ @num_format($card_payment->amount) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center">-</td>
                                    </tr>
                                    @endforelse
                                    <tr>
                                        <td colspan="4" style="text-align: right;">@lang('petropd::lang.sub_total')</td>
                                        <td class="text-right">{{ @num_format($pd_card_total) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>


                    @endif

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
                                        <td >{{@num_format($loan_payments_total)}}</td>
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
                                        <td >{{@num_format($drawings_payments_total)}}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>


                    <div class="clearfix"></div>
                    <br>
                    <div class="col-xs-12 text-center payment-details-section"
                        style="font-weight: bold; maring-bottom: -10px; font-size: 18px;">
                        @lang('petropd::lang.payment_details')
                    </div>
                    <div class="row payment-details-section">
                        <div class="col-md-12">
                            <table class="table table-striped payment-details-table">
                                <thead>
                                    <tr>
                                        <th>@lang('petropd::lang.loan_payments' )</th>
                                        <th>@lang('petropd::lang.cash' )</th>
                                        <th>@lang('petropd::lang.cash_deposit' )</th>
                                        <th>@lang('petropd::lang.cards' )</th>
                                        <th>@lang('petropd::lang.cheques')</th>
                                        <th>@lang('petropd::lang.credit_sales')</th>
                                        <th>@lang('petropd::lang.expenses')</th>
                                        <th>@lang('petropd::lang.short')</th>
                                        <th>@lang('petropd::lang.excess')</th>
                                        <th>@lang('petropd::lang.customer_loans')</th>
                                        <th>@lang('petropd::lang.total')</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <tr>
                                        <td>{{ @num_format($settlement->loan_payments->unique('id')->sum('amount')) }}</td>
                                        <td>{{ @num_format($final_cash_amount ?? 0) }}</td>
                                        <td>{{ @num_format($settlement->cash_deposits->unique('id')->sum('amount')) }}</td>
                                        {{--
                                            Uses $final_card_amount, which falls back to the
                                            pumper's card payments before the settlement is
                                            saved - see AddPaymentController. Summing the
                                            relation directly gives 0.00 at reconfirmation
                                            stage, because the settlement rows do not exist
                                            until it is committed.

                                            The ?? keeps the old behaviour anywhere the
                                            variable is not supplied.
                                        --}}
                                        <td>{{ @num_format($final_card_amount ?? $settlement->card_payments->unique('id')->sum('amount')) }}</td>
                                        <td>{{ @num_format($settlement->cheque_payments->unique('id')->sum('amount')) }}</td>
                                        <td>{{ @num_format($settlement->credit_sale_payments->unique('id')->sum('amount') - $settlement->credit_sale_payments->unique('id')->sum('total_discount')) }}</td>
                                        <td>{{ @num_format($settlement->expense_payments->unique('id')->sum('amount')) }}</td>
                                        <td>{{ @num_format($settlement->shortage_payments->unique('id')->sum('amount')) }}</td>
                                        <td>{{ @num_format($settlement->excess_payments->unique('id')->sum('amount')) }}</td>
                                        <td>{{ @num_format($settlement->customer_loans->unique('id')->sum('amount')) }}</td>
                                        <td class="text-right red-flag payment-details-total">
                                            {{ @num_format(
                                                @array_sum(@array($settlement->customer_loans->unique('id')->sum('amount'),
                                                $settlement->cash_deposits->unique('id')->sum('amount'),
                                                $final_cash_amount ?? 0,
                                                $final_card_amount ?? $settlement->card_payments->unique('id')->sum('amount'),
                                                $settlement->cheque_payments->unique('id')->sum('amount'),
                                                ($settlement->credit_sale_payments->unique('id')->sum('amount') - $settlement->credit_sale_payments->unique('id')->sum('total_discount')) +
                                                $settlement->expense_payments->unique('id')->sum('amount'),
                                                $settlement->shortage_payments->unique('id')->sum('amount'),
                                                $settlement->excess_payments->unique('id')->sum('amount')))
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
                href="{{ route('settlement-sw.create') }}">@lang('petropd::lang.back_to_settlement_sw')</a>
            @elseif (str_starts_with($settlement->settlement_no, 'PDST'))
                <a style=" border-radius: 0px !important; float: right; margin-bottom: 10px;" class="btn btn-success btn-sm btn-flat pull-right no-print back-to-settlement"
                href="{{ route('petropd.pd-settlement', ['after_finalize' => 1, '_ts' => now()->timestamp]) }}">@lang('petropd::lang.back_to_settlement')</a>
            @else
                <a style=" border-radius: 0px !important; float: right; margin-bottom: 10px;" class="btn btn-success btn-sm btn-flat pull-right no-print back-to-settlement"
                href="{{ url('/petropd/settlement-pd/create') }}">@lang('petropd::lang.back_to_settlement')</a>
            @endif
    	</div>

</div>

@if (request()->boolean('auto_print'))
    <script id="petropd-finalize-auto-print">
        (function () {
            var previewOpened = false;

            function openPrintPreview() {
                if (previewOpened) {
                    return;
                }

                previewOpened = true;
                window.focus();

                // Allow the browser one rendering frame for fonts, tables and print CSS.
                setTimeout(function () {
                    window.print();
                }, 350);
            }

            if (document.readyState === 'complete') {
                openPrintPreview();
            } else {
                window.addEventListener('load', openPrintPreview, { once: true });
            }
        })();
    </script>
@endif
