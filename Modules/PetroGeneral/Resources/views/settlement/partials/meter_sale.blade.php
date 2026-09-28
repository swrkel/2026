@php
 $currency_precision = 2;
 $meter_sale_arr = [];
 if(!empty($active_settlement) && request()->segment(3)=='edit')
 	$meter_sale_arr = $active_settlement->meter_sales->toArray()[0];
 @endphp
<br>
<div class="row" id="meter-sale-form-block">
	@include('petrogeneral::settlement.partials.meter_sale_form', ['meter_sale'=>$meter_sale_arr])
</div>
<br>
<br>
<div class="row">
	<div class="col-md-12">
		<table class="table table-bordered table-striped" id="meter_sale_table">
			<thead>
				<tr>
					<th>@lang('petrogeneral::lang.code' )</th>
					<th>@lang('petrogeneral::lang.products' )</th>
					<th>@lang('petrogeneral::lang.pump' )</th>
					<th class="text-right" style="width: 10px;">@lang('petrogeneral::lang.starting_meter')</th>
					<th class="text-right" style="width: 10px;">@lang('petrogeneral::lang.closing_meter')</th>
					<th class="text-right">@lang('petrogeneral::lang.price')</th>
					<th class="text-right">@lang('petrogeneral::lang.sold_qty' )</th> {{-- Qty = Closing Meter- Starting Meter - Testing Qty --}}
					<th style="width: 10px;">@lang('petrogeneral::lang.discount_type' )</th>
					<th class="text-right" style="width: 6px;">@lang('petrogeneral::lang.discount_value' )</th>
					<th class="text-right" style="width: 10px;">@lang('petrogeneral::lang.testing_qty' )</th>
					<th class="text-right">@lang('petrogeneral::lang.total_qty' )</th>
					<th class="text-right">@lang('petrogeneral::lang.before_discount' )</th>
					<th class="text-right">@lang('petrogeneral::lang.after_discount' )</th>
					<th>@lang('petrogeneral::lang.action' )</th>
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
            				$pump = Modules\PetroGeneral\Entities\Pump::where('id', $item->pump_id)->first();
            				$later_settlements = Modules\PetroGeneral\Entities\MeterSale::where('business_id', $item->business_id)
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
									<button class="btn btn-xs btn-primary get_meter_sale_from" data-type="edit" data-href="/petro-general/settlement/get-meter-sale-form/{{$item->id}}"><i class="fa fa-edit"></i></button>
            					    <button class="btn btn-xs btn-danger delete_meter_sale" data-href="/petro-general/settlement/delete-meter-sale/{{$item->id}}"><i class="fa fa-times"></i></button>
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
					<td colspan="3" style="text-align: right; font-weight: bold;">@lang('petrogeneral::lang.meter_sale_total')
						:</td>
					<td style="text-align: right; font-weight: bold;" class="meter_sale_total">
						
						@if($final_total > 0)
    {{number_format($final_total, $currency_precision)}}
@else
    0.00
@endif
						</td>
				</tr>
				<input type="hidden" value="{{$final_total}}" name="meter_sale_total" id="meter_sale_total">
			</tfoot>
		</table>
	</div>
</div>

<div class="table-responsive 1234" id="outside_meter_sale_table"  style="display: none;">
    <table class="table table-bordered table-striped" id="pump_operator_meter_sale_table"
        style="width: 100%;">
        <thead>
            <tr>
                    <th>@lang('petrogeneral::lang.code' )</th>
					<th>@lang('petrogeneral::lang.products' )</th>
					<th>@lang('petrogeneral::lang.pump' )</th>
					<th class="text-right" style="width: 10px;">@lang('petrogeneral::lang.starting_meter')</th>
					<th class="text-right" style="width: 10px;">@lang('petrogeneral::lang.closing_meter')</th>
					<th class="text-right">@lang('petrogeneral::lang.price')</th>
					<th class="text-right">@lang('petrogeneral::lang.sold_qty' )</th>
					<th style="width: 10px;">@lang('petrogeneral::lang.discount_type' )</th>
					<th class="text-right" style="width: 6px;">@lang('petrogeneral::lang.discount_value' )</th>
					<th class="text-right" style="width: 10px;">@lang('petrogeneral::lang.testing_qty' )</th>
					<th class="text-right">@lang('petrogeneral::lang.total_qty' )</th>
					<th class="text-right">@lang('petrogeneral::lang.before_discount' )</th>
					<th class="text-right">@lang('petrogeneral::lang.after_discount' )</th>
					<th>@lang('petrogeneral::lang.action' )</th>
            </tr>
        </thead>


			<tfoot>
				<tr>
				    <td colspan="6"></td>
				    <td><span class="product_summary"></span></td>
					<td colspan="3" style="text-align: right; font-weight: bold;">@lang('petrogeneral::lang.meter_sale_total')
						:</td>
					<td style="text-align: right; font-weight: bold;" class="meter_sale_total">
						{{number_format( $final_total, $currency_precision)}}</td>
				</tr>
				<input type="hidden" value="{{$final_total}}" name="meter_sale_total" id="meter_sale_total">
			</tfoot>
    </table>
</div>
