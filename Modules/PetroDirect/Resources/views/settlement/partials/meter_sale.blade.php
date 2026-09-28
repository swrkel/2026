@php
 $currency_precision = 2;
 $meter_sale_arr = [];
 if (!empty($active_settlement) && request()->segment(3) == 'edit') {
     $first_meter_sale = collect($active_settlement->meter_sales ?? [])->first();
     $meter_sale_arr = !empty($first_meter_sale)
         ? (is_array($first_meter_sale) ? $first_meter_sale : $first_meter_sale->toArray())
         : [];
 }
 @endphp

<style>
    /*
     * MA 003: compact Direct Settlement Meter Sales table.
     * Width values are starting widths only. table-layout:auto and no maximum
     * width allow numeric columns to expand when longer values are displayed.
     */
    .direct-meter-sale-table-wrap {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 6px;
    }

    #meter_sale_table,
    #pump_operator_meter_sale_table {
        width: 100% !important;
        min-width: 1120px;
        table-layout: auto !important;
        margin-bottom: 0;
    }

    #meter_sale_table th,
    #meter_sale_table td,
    #pump_operator_meter_sale_table th,
    #pump_operator_meter_sale_table td {
        min-width: 70px;
        vertical-align: middle !important;
    }

    #meter_sale_table thead th,
    #pump_operator_meter_sale_table thead th {
        /*
         * Headings: 1pt smaller again - was calc(1em - 1.5pt).
         * History: 1em -> -1pt (MA-002) -> -1.5pt (MA-002) -> -2.5pt here.
         */
        font-size: calc(1em - 2.5pt);
        line-height: 1.2;
        white-space: normal;
        overflow-wrap: normal;
        word-break: normal;
    }

    /*
     * Table DATA, 1pt smaller.
     *
     * The body cells had no font-size of their own, so they were inheriting the
     * table default - which is why only the headings had ever been reduced.
     * Stated as calc(1em - 1pt) so it tracks the theme's base size rather than
     * fixing a pixel value that would not scale.
     *
     * tfoot is included so the totals row matches the rows above it.
     */
    #meter_sale_table tbody td,
    #meter_sale_table tfoot td,
    #meter_sale_table tfoot th,
    #pump_operator_meter_sale_table tbody td,
    #pump_operator_meter_sale_table tfoot td,
    #pump_operator_meter_sale_table tfoot th {
        font-size: calc(1em - 1pt);
        line-height: 1.25;
    }

    /*
     * Inputs inside the rows follow the cell size, otherwise a typed value would
     * look larger than the text beside it.
     */
    #meter_sale_table tbody td .form-control,
    #pump_operator_meter_sale_table tbody td .form-control {
        font-size: calc(1em - 1pt);
    }

    #meter_sale_table .pd-meter-product-col,
    #pump_operator_meter_sale_table .pd-meter-product-col {
        min-width: 150px;
    }

    #meter_sale_table .pd-meter-pump-col,
    #pump_operator_meter_sale_table .pd-meter-pump-col {
        width: 41px;
        min-width: 41px;
    }

    #meter_sale_table .pd-meter-reading-col,
    #pump_operator_meter_sale_table .pd-meter-reading-col {
        width: 74px;
        min-width: 74px;
    }

    #meter_sale_table .pd-meter-sold-col,
    #pump_operator_meter_sale_table .pd-meter-sold-col {
        width: 41px;
        min-width: 41px;
    }

    #meter_sale_table .pd-meter-compact-col,
    #pump_operator_meter_sale_table .pd-meter-compact-col {
        width: 66px;
        min-width: 66px;
    }

    #meter_sale_table td.pd-meter-pump-col,
    #meter_sale_table td.pd-meter-reading-col,
    #meter_sale_table td.pd-meter-sold-col,
    #meter_sale_table td.pd-meter-compact-col,
    #pump_operator_meter_sale_table td.pd-meter-pump-col,
    #pump_operator_meter_sale_table td.pd-meter-reading-col,
    #pump_operator_meter_sale_table td.pd-meter-sold-col,
    #pump_operator_meter_sale_table td.pd-meter-compact-col {
        white-space: nowrap;
    }

    /*
        MA-002: each of these columns now has its OWN width class.

        Starting and Closing Meter shared pd-meter-reading-col, and Discount
        Value, Testing Qty, Before Discount and After Discount all shared
        pd-meter-compact-col - so they could not be reduced by different
        amounts. Discount Type had no width class at all.

        The reductions are taken from the widths that were actually in the
        file, not from guessed figures:

            Starting Meter    74px  -30%  ->  52px
            Closing Meter     74px  -30%  ->  52px
            Discount Type     none  -30%  ->  63px  (90px is its natural
                                                     width at this font)
            Discount Value    66px  -40%  ->  40px
            Testing Qty       66px  -40%  ->  40px
            Before Discount   66px  -25%  ->  50px
            After Discount    66px  -25%  ->  50px

        The headings wrap to two rows, so the narrower columns still read
        clearly.
    */
    #meter_sale_table .pd-meter-start-col,
    #pump_operator_meter_sale_table .pd-meter-start-col,
    #meter_sale_table .pd-meter-close-col,
    #pump_operator_meter_sale_table .pd-meter-close-col {
        width: 52px;
        min-width: 52px;
    }
    #meter_sale_table .pd-meter-disctype-col,
    #pump_operator_meter_sale_table .pd-meter-disctype-col {
        width: 63px;
        min-width: 63px;
    }
    #meter_sale_table .pd-meter-discval-col,
    #pump_operator_meter_sale_table .pd-meter-discval-col,
    #meter_sale_table .pd-meter-testing-col,
    #pump_operator_meter_sale_table .pd-meter-testing-col {
        width: 40px;
        min-width: 40px;
    }
    #meter_sale_table .pd-meter-before-col,
    #pump_operator_meter_sale_table .pd-meter-before-col,
    #meter_sale_table .pd-meter-after-col,
    #pump_operator_meter_sale_table .pd-meter-after-col {
        width: 50px;
        min-width: 50px;
    }
    /*
        MA-002: the product totals lay out ACROSS the footer.

        The script builds them as "Product = Qty<br>Product = Qty". The <br>
        is what forced them to stack, so each entry is turned into an
        inline-block instead - they then flow side by side and wrap only when
        the row is genuinely full.

        Left-aligned and allowed to wrap, so a long product name is readable
        rather than clipped.
    */
    #meter_sale_table .pd-meter-summary-cell,
    #pump_operator_meter_sale_table .pd-meter-summary-cell {
        text-align: left;
        white-space: normal;
        line-height: 1.4;
    }
    #meter_sale_table .pd-meter-summary-cell .product_summary,
    #pump_operator_meter_sale_table .pd-meter-summary-cell .product_summary {
        display: block;
    }
    /* Each "Product = Qty" becomes an inline chip rather than its own line. */
    #meter_sale_table .pd-meter-summary-cell .product_summary br,
    #pump_operator_meter_sale_table .pd-meter-summary-cell .product_summary br {
        display: none;
    }
    #meter_sale_table .pd-meter-summary-cell .pd-summary-item,
    #pump_operator_meter_sale_table .pd-meter-summary-cell .pd-summary-item {
        display: inline-block;
        margin-right: 18px;
        white-space: nowrap;
    }

    /* Two-line headings must not be forced onto one line. */
    #meter_sale_table thead th,
    #pump_operator_meter_sale_table thead th {
        white-space: normal;
        line-height: 1.15;
        vertical-align: bottom;
    }

    #meter_sale_table .pd-meter-action-col,
    #pump_operator_meter_sale_table .pd-meter-action-col {
        min-width: 78px;
        white-space: nowrap;
    }

    .pd-meter-product-name,
    .pd-meter-product-code {
        display: block;
    }

    .pd-meter-product-code {
        margin-top: 4px;
        padding-top: 3px;
        border-top: 1px solid #d8dde3;
        color: #667085;
        font-size: 0.9em;
        line-height: 1.2;
    }
</style>
<br>
<div class="row" id="meter-sale-form-block">
	@include('petrodirect::settlement.partials.meter_sale_form', ['meter_sale'=>$meter_sale_arr])
</div>
<br>
<br>
<div class="row">
	<div class="col-md-12">
        <div class="direct-meter-sale-table-wrap">
		<table class="table table-bordered table-striped" id="meter_sale_table">
			<thead>
				<tr>
					<th class="pd-meter-product-col">@lang('petrodirect::lang.products' )</th>
					<th class="pd-meter-pump-col">@lang('petrodirect::lang.pump' )</th>
					<th class="text-right pd-meter-start-col">{!! str_replace(' ', '<br>', e(__('petrodirect::lang.starting_meter'))) !!}</th>
					<th class="text-right pd-meter-close-col">{!! str_replace(' ', '<br>', e(__('petrodirect::lang.closing_meter'))) !!}</th>
					<th class="text-right">@lang('petrodirect::lang.price')</th>
					<th class="text-right pd-meter-sold-col">{!! str_replace(' ', '<br>', e(__('petrodirect::lang.sold_qty'))) !!}</th> {{-- Qty = Closing Meter - Starting Meter - Testing Qty --}}
					<th class="pd-meter-disctype-col">{!! str_replace(' ', '<br>', e(__('petrodirect::lang.discount_type'))) !!}</th>
					<th class="text-right pd-meter-discval-col">{!! str_replace(' ', '<br>', e(__('petrodirect::lang.discount_value'))) !!}</th>
					<th class="text-right pd-meter-testing-col">{!! str_replace(' ', '<br>', e(__('petrodirect::lang.testing_qty'))) !!}</th>
					<th class="text-right pd-meter-compact-col">@lang('petrodirect::lang.total_qty' )</th>
					<th class="text-right pd-meter-before-col">{!! str_replace(' ', '<br>', e(__('petrodirect::lang.before_discount'))) !!}</th>
					<th class="text-right pd-meter-after-col">{!! str_replace(' ', '<br>', e(__('petrodirect::lang.after_discount'))) !!}</th>
					<th class="pd-meter-action-col">@lang('petrodirect::lang.action' )</th>
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
            				$pump = Modules\PetroDirect\Entities\Pump::where('id', $item->pump_id)->first();
            				$later_settlements = Modules\PetroDirect\Entities\MeterSale::where('business_id', $item->business_id)
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
        	        				<td class="pd-meter-product-col">
                                <span class="product_name pd-meter-product-name">{{$product->name}}</span>
                                <span class="pd-meter-product-code">{{$product->sku}}</span>
                            </td>
        					<td class="pd-meter-pump-col">{{$pump->pump_no}}</td>
        					<td class="text-right pd-meter-start-col">{{number_format($item->starting_meter, $meeter_precision, '.', ',')}}</td>
        					<td class="text-right pd-meter-close-col">{{number_format($item->closing_meter, $meeter_precision, '.', ',')}}</td>
        					<td class="text-right">{{number_format($item->price, $currency_precision)}}</td>
        					<td class="text-right pd-meter-sold-col"><span class="sold_qty">{{number_format($qauntity, $meeter_precision)}}</span></td>
        					<td class="pd-meter-disctype-col">{{ isset($discount_types[$item->discount_type]) ? $discount_types[$item->discount_type] : ''}}</td>
        					<td class="text-right pd-meter-discval-col">{{number_format($item->discount, $currency_precision)}}</td>
        					<td class="text-right pd-meter-testing-col">{{number_format($item->testing_qty, $currency_precision)}}</td>
        					<td class="text-right pd-meter-compact-col">{{number_format(($item->testing_qty+$qauntity), $meeter_precision)}}</td>
        					<td class="text-right pd-meter-before-col">{{number_format($subTotal, $currency_precision)}}</td>
                            <td class="text-right pd-meter-after-col"><span class="display_currency discount_amount" data-orig-value="{{ $withDiscount }}">{{number_format($withDiscount, $currency_precision)}}</span></td>
        					<td class="pd-meter-action-col">
                                @if($can_edit_meter_sale)
									<button class="btn btn-xs btn-primary get_meter_sale_from" data-type="edit" data-href="/petrodirect/settlement/get-meter-sale-form/{{$item->id}}"><i class="fa fa-edit"></i></button>
            					    <button class="btn btn-xs btn-danger delete_meter_sale" data-href="/petrodirect/settlement/delete-meter-sale/{{$item->id}}"><i class="fa fa-times"></i></button>
            					@endif
        					</td>
        				</tr>
    				@endforeach
				@endif
			</tbody>
			<tfoot>
				<tr>
				    {{--
				        MA-002: the product totals now run ACROSS the footer.

				        They were in a single cell in the Sold Qty column, which is
				        41px wide - so every product wrapped onto several lines and
				        the footer grew very tall.

				        The cell now spans the six columns to the left, which were
				        empty, and the entries lay out side by side rather than
				        stacking. The Meter Sale Total keeps its own place on the
				        right, so the row still totals to the same columns.
				    --}}
				    <td colspan="6" class="pd-meter-summary-cell"><span class="product_summary"></span></td>
					<td colspan="5" style="text-align: right; font-weight: bold;">
                        @lang('petrodirect::lang.meter_sale_total'):
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
            <input type="hidden" value="{{ $final_total }}" name="meter_sale_total" id="meter_sale_total">
        </div>
	</div>
</div>

<div class="direct-meter-sale-table-wrap" id="outside_meter_sale_table" style="display: none;">
    <table class="table table-bordered table-striped" id="pump_operator_meter_sale_table"
        style="width: 100%;">
        <thead>
            <tr>
                    <th class="pd-meter-product-col">@lang('petrodirect::lang.products' )</th>
					<th class="pd-meter-pump-col">@lang('petrodirect::lang.pump' )</th>
					<th class="text-right pd-meter-start-col">{!! str_replace(' ', '<br>', e(__('petrodirect::lang.starting_meter'))) !!}</th>
					<th class="text-right pd-meter-close-col">{!! str_replace(' ', '<br>', e(__('petrodirect::lang.closing_meter'))) !!}</th>
					<th class="text-right">@lang('petrodirect::lang.price')</th>
					<th class="text-right pd-meter-sold-col">{!! str_replace(' ', '<br>', e(__('petrodirect::lang.sold_qty'))) !!}</th>
					<th class="pd-meter-disctype-col">{!! str_replace(' ', '<br>', e(__('petrodirect::lang.discount_type'))) !!}</th>
					<th class="text-right pd-meter-discval-col">{!! str_replace(' ', '<br>', e(__('petrodirect::lang.discount_value'))) !!}</th>
					<th class="text-right pd-meter-testing-col">{!! str_replace(' ', '<br>', e(__('petrodirect::lang.testing_qty'))) !!}</th>
					<th class="text-right pd-meter-compact-col">@lang('petrodirect::lang.total_qty' )</th>
					<th class="text-right pd-meter-before-col">{!! str_replace(' ', '<br>', e(__('petrodirect::lang.before_discount'))) !!}</th>
					<th class="text-right pd-meter-after-col">{!! str_replace(' ', '<br>', e(__('petrodirect::lang.after_discount'))) !!}</th>
					<th class="pd-meter-action-col">@lang('petrodirect::lang.action' )</th>
            </tr>
        </thead>


			<tfoot>
				<tr>
				    {{--
				        MA-002: the product totals now run ACROSS the footer.

				        They were in a single cell in the Sold Qty column, which is
				        41px wide - so every product wrapped onto several lines and
				        the footer grew very tall.

				        The cell now spans the six columns to the left, which were
				        empty, and the entries lay out side by side rather than
				        stacking. The Meter Sale Total keeps its own place on the
				        right, so the row still totals to the same columns.
				    --}}
				    <td colspan="6" class="pd-meter-summary-cell"><span class="product_summary"></span></td>
					<td colspan="5" style="text-align: right; font-weight: bold;">
                        @lang('petrodirect::lang.meter_sale_total'):
                    </td>
					<td style="text-align: right; font-weight: bold;" class="meter_sale_total">
                        {{ number_format($final_total, $currency_precision) }}
                    </td>
                    <td></td>
				</tr>
			</tfoot>
    </table>
</div>
