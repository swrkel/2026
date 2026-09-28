@php
 $currency_precision = 2;
 $meter_sale_arr = [];
 if(!empty($active_settlement) && request()->segment(3)=='edit')
 	$meter_sale_arr = $active_settlement->meter_sales->toArray()[0];
 @endphp

<style>
    /* IS1770: keep all fourteen Meter Sales columns available. */
    .direct-meter-sale-table-wrap,
    .petro-meter-sale-table-scroll {
        display: block;
        width: 100%;
        max-width: 100%;
        overflow-x: auto !important;
        overflow-y: visible !important;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 6px;
    }

    #meter_sale_table,
    #pump_operator_meter_sale_table,
    .petro-meter-sale-wide-table {
        width: 100% !important;
        min-width: 1420px !important;
        table-layout: auto !important;
        margin-bottom: 0 !important;
    }

    #meter_sale_table th,
    #meter_sale_table td,
    #pump_operator_meter_sale_table th,
    #pump_operator_meter_sale_table td {
        min-width: 82px;
        width: auto !important;
        white-space: normal !important;
        overflow-wrap: anywhere;
        vertical-align: middle !important;
    }

    #meter_sale_table th:nth-child(2),
    #meter_sale_table td:nth-child(2),
    #pump_operator_meter_sale_table th:nth-child(2),
    #pump_operator_meter_sale_table td:nth-child(2) {
        min-width: 150px;
    }

    #meter_sale_table th:last-child,
    #meter_sale_table td:last-child,
    #pump_operator_meter_sale_table th:last-child,
    #pump_operator_meter_sale_table td:last-child {
        min-width: 78px;
        white-space: nowrap !important;
    }
</style>
<br>
<div class="row" id="meter-sale-form-block">
	@include('petro::settlement.partials.meter_sale_form', ['meter_sale'=>$meter_sale_arr])
</div>
<br>
<br>
<div class="row">
	<div class="col-md-12">
		<div class="table-responsive petro-meter-sale-table-scroll" aria-label="@lang('petro::lang.meter_sale')">
		<table class="table table-bordered table-striped petro-meter-sale-wide-table" id="meter_sale_table">
			<thead>
				<tr>
					<th>@lang('petro::lang.code' )</th>
					<th>@lang('petro::lang.products' )</th>
					<th>@lang('petro::lang.pump' )</th>
					<th class="text-right" style="width: 10px;">@lang('petro::lang.starting_meter')</th>
					<th class="text-right" style="width: 10px;">@lang('petro::lang.closing_meter')</th>
					<th class="text-right">@lang('petro::lang.price')</th>
					<th class="text-right">@lang('petro::lang.sold_qty' )</th> {{-- Qty = Closing Meter- Starting Meter - Testing Qty --}}
					<th style="width: 10px;">@lang('petro::lang.discount_type' )</th>
					<th class="text-right" style="width: 6px;">@lang('petro::lang.discount_value' )</th>
					<th class="text-right" style="width: 10px;">@lang('petro::lang.testing_qty' )</th>
					<th class="text-right">@lang('petro::lang.total_qty' )</th>
					<th class="text-right">@lang('petro::lang.before_discount' )</th>
					<th class="text-right">@lang('petro::lang.after_discount' )</th>
					<th>@lang('petro::lang.action' )</th>
				</tr>
			</thead>
			<tbody>
				@php
    				$final_total = 0.00;
    				if (
    empty($active_settlement)
    && request()->segment(2) == 'settlement'
) {
    $final_total = 0;
}
				@endphp
				@if (!empty($active_settlement))
					@php
						$display_meter_sales = collect($active_settlement->meter_sales)->unique(function ($item) {
							return implode('|', [
								$item->settlement_no,
								$item->shift_id,
								$item->pump_id,
								$item->starting_meter,
								$item->closing_meter,
								$item->qty,
								$item->price,
								$item->discount,
								$item->discount_type,
							]);
						});
					@endphp
					@foreach ($display_meter_sales as $item)
        				@php
            				$product = App\Product::where('id', $item->product_id)->first();
            				$pump = Modules\Petro\Entities\Pump::where('id', $item->pump_id)->first();
            				$later_settlements = Modules\Petro\Entities\MeterSale::where('business_id', $item->business_id)
                                ->where('pump_id', $item->pump_id)
                                ->where('id', '>', $item->id)
                                ->where('settlement_no', '!=', $item->settlement_no)
                                ->count();
                            $can_edit_meter_sale = $later_settlements < 1 || !empty($pump->bulk_tank);
        				@endphp
        				<tr>
                            @php
                                $qauntity = $item->closing_meter - $item->starting_meter - $item->testing_qty;
                                
                                if($pump->bulk_sale_meter == 1){
                                    $qauntity = $item->qty;
                                }
                                
                                $subTotal = $item->price * $qauntity;
                                $discountVal = (float) ($item->discount ?? 0);
                                $discountAmount = (isset($item->discount_type) && $item->discount_type === 'percentage') ? $subTotal * ($discountVal / 100) : $discountVal;
                                $withDiscount = max(0, $subTotal - $discountAmount);
                                $final_total += $withDiscount;
                            @endphp
        					<td>{{$product->sku}}</td>
        					<td><span class="product_name">{{$product->name}}</span></td>
        					
        					<td>{{$pump->pump_no}}</td>
        					<td class="text-right">{{number_format($item->starting_meter, $meeter_precision, '.', ',')}}</td>
        					<td class="text-right">{{number_format($item->closing_meter, $meeter_precision, '.', ',')}}</td>
        					<td class="text-right">{{number_format($item->price, $currency_precision)}}</td>
        					
        					<td class="text-right"><span class="sold_qty">{{number_format($qauntity, $meeter_precision)}}</span></td>
        					
        					<td>{{ isset($discount_types[$item->discount_type]) ? $discount_types[$item->discount_type] : ''}}</td>
        					
        					<td class="text-right">{{number_format($item->discount, $currency_precision)}}</td>
        					<td class="text-right">{{number_format($item->testing_qty, $currency_precision)}}</td>
        					<td class="text-right">{{number_format(($item->testing_qty+$qauntity), $meeter_precision)}}</td>
        					<td class="text-right">{{number_format($subTotal, $currency_precision)}}</td>
                            <td class="text-right"><span class="display_currency discount_amount" data-orig-value="{{ $withDiscount }}">{{number_format($withDiscount, $currency_precision)}}</span></td>
        					<td>
                                @if($can_edit_meter_sale)
									<button class="btn btn-xs btn-primary get_meter_sale_from" data-type="edit" data-href="/petro/settlement/get-meter-sale-form/{{$item->id}}"><i class="fa fa-edit"></i></button>
            					    <button class="btn btn-xs btn-danger delete_meter_sale" data-href="/petro/settlement/delete-meter-sale/{{$item->id}}"><i class="fa fa-times"></i></button>
            					@endif
        					</td>
        				</tr>
    				@endforeach
				@endif
			</tbody>
			<tfoot>
				<tr>
				    <td colspan="6"></td>
				    <td><span class="product_summary"></span></td>
					<td colspan="5" style="text-align: right; font-weight: bold;">
                        @lang('petro::lang.meter_sale_total'):
                    </td>
					<td style="text-align: right; font-weight: bold;" class="meter_sale_total">
						@if($final_total > 0)
                            {{ number_format($final_total, $currency_precision) }}
                        @else
                            0.00
                        @endif
					</td>
                    <td></td>
				</tr>
			</tfoot>
		</table>
            <input type="hidden" value="{{ abs($final_total) }}" name="meter_sale_total" id="meter_sale_total">
		</div>
	</div>
</div>

<div class="table-responsive petro-meter-sale-table-scroll 1234" id="outside_meter_sale_table"  style="display: none;">
    <table class="table table-bordered table-striped petro-meter-sale-wide-table" id="pump_operator_meter_sale_table"
        style="width: 100%;">
        <thead>
            <tr>
                    <th>@lang('petro::lang.code' )</th>
					<th>@lang('petro::lang.products' )</th>
					<th>@lang('petro::lang.pump' )</th>
					<th class="text-right" style="width: 10px;">@lang('petro::lang.starting_meter')</th>
					<th class="text-right" style="width: 10px;">@lang('petro::lang.closing_meter')</th>
					<th class="text-right">@lang('petro::lang.price')</th>
					<th class="text-right">@lang('petro::lang.sold_qty' )</th>
					<th style="width: 10px;">@lang('petro::lang.discount_type' )</th>
					<th class="text-right" style="width: 6px;">@lang('petro::lang.discount_value' )</th>
					<th class="text-right" style="width: 10px;">@lang('petro::lang.testing_qty' )</th>
					<th class="text-right">@lang('petro::lang.total_qty' )</th>
					<th class="text-right">@lang('petro::lang.before_discount' )</th>
					<th class="text-right">@lang('petro::lang.after_discount' )</th>
					<th>@lang('petro::lang.action' )</th>
            </tr>
        </thead>


			<tfoot>
				<tr>
				    <td colspan="6"></td>
				    <td><span class="product_summary"></span></td>
					<td colspan="5" style="text-align: right; font-weight: bold;">
                        @lang('petro::lang.meter_sale_total'):
                    </td>
					<td style="text-align: right; font-weight: bold;" class="meter_sale_total">
                        {{ number_format(abs($final_total), $currency_precision) }}
                    </td>
                    <td></td>
				</tr>
			</tfoot>
    </table>
</div>
