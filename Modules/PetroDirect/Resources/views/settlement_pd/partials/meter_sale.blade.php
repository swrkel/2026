@php
$currency_precision = 2;
$discount_types = $discount_types ?? [];
$meter_sale_arr = [];
$direct_meter_sales = !empty($active_settlement)
    ? collect($active_settlement->meter_sales ?? [])
    : collect();
$first_meter_sale = $direct_meter_sales->first();
if (!empty($first_meter_sale)) {
    $meter_sale_arr = is_array($first_meter_sale)
        ? $first_meter_sale
        : $first_meter_sale->toArray();
}

@endphp
<br>
<div class="row" id="meter-sale-form-block">
    @include('petrodirect::settlement_pd.partials.meter_sale_form', ['meter_sale' => $meter_sale_arr])
</div>
<br>
<br>
<div class="row" id="meter_sale_table_wrap">
    <div class="col-md-12">

        <table class="table table-bordered table-striped" id="meter_sale_table">
            <thead>
                <tr>
                    <th>@lang('petrodirect::lang.code')</th>
                    <th>@lang('petrodirect::lang.products')</th>
                    <th>@lang('petrodirect::lang.pump')</th>
                    <th style="width: 10px;">@lang('petrodirect::lang.starting_meter')</th>
                    <th style="width: 10px;">@lang('petrodirect::lang.closing_meter')</th>
                    <th>@lang('petrodirect::lang.price')</th>
                    <th>@lang('petrodirect::lang.sold_qty')</th> {{-- Qty = Closing Meter- Starting Meter - Testing Qty --}}
                    <th style="width: 10px;">@lang('petrodirect::lang.discount_type')</th>
                    <th style="width: 6px;">@lang('petrodirect::lang.discount_value')</th>
                    <th style="width: 10px;">@lang('petrodirect::lang.testing_qty')</th>
                    <th>@lang('petrodirect::lang.total_qty')</th>
                    <th>@lang('petrodirect::lang.before_discount')</th>
                    <th>@lang('petrodirect::lang.after_discount')</th>
                    <th>@lang('petrodirect::lang.action')</th>
                </tr>
            </thead>
            <tbody>
                @php
                $final_total = 0.0;
                @endphp
                @foreach ($direct_meter_sales as $detail)
                @php
                $pump = $detail->pump;
                $product = $detail->product ?: ($pump ? App\Product::find($pump->product_id) : null);
                $quantity = (float) $detail->qty;
                $subTotal = (float) ($detail->sub_total ?? ($detail->price * $quantity));
                $withDiscount = (float) ($detail->discount_amount ?? $subTotal);
                $final_total += $withDiscount;
                $later_settlements = Modules\PetroDirect\Entities\MeterSale::petroDirectOwned()
                    ->where('business_id', $detail->business_id)
                    ->where('pump_id', $detail->pump_id)
                    ->where('id', '>', $detail->id)
                    ->where('settlement_no', '!=', $detail->settlement_no)
                    ->count();
                $can_edit = $later_settlements < 1 || !empty($pump?->bulk_tank);
                @endphp

                <tr>
                    <td>{{ $product?->sku ?? '-' }}</td>
                    <td>{{ $product?->name ?? '-' }}</td>
                    <td>{{ $pump?->pump_name ?? $pump?->pump_no ?? '-' }}</td>

                    <td>{{ number_format($detail->starting_meter, 3) }}</td>
                    <td>{{ number_format($detail->closing_meter, 3) }}</td>
                    <td>{{ number_format($detail->price, 2) }}</td>

                    <td>{{ number_format($quantity, 2) }}</td>

                    <td>{{ $discount_types[$detail->discount_type] ?? '-' }}</td>
                    <td>{{ number_format($detail->discount ?? 0, 2) }}</td>
                    <td>{{ number_format($detail->testing_qty ?? 0, 2) }}</td>

                    <td>{{ number_format($quantity + (float) ($detail->testing_qty ?? 0), 2) }}</td>
                    <td>{{ number_format($subTotal, 2) }}</td>
                    <td>{{ number_format($withDiscount, 2) }}</td>

                    <td>
                        @if($can_edit)
                            <button class="btn btn-xs btn-primary get_meter_sale_from" data-type="edit"
                                data-href="/petrodirect/settlement-pd/get-meter-sale-form/{{ $detail->id }}">
                                <i class="fa fa-edit"></i>
                            </button>
                            <button class="btn btn-xs btn-danger delete_meter_sale"
                                data-href="/petrodirect/settlement-pd/delete-meter-sale/{{ $detail->id }}">
                                <i class="fa fa-times"></i>
                            </button>
                        @endif
                    </td>
                </tr>
                @endforeach

            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6"></td>
                    <td><span class="product_summary"></span></td>
                    <td colspan="3" style="text-align: right; font-weight: bold;">@lang('petrodirect::lang.meter_sale_total')
                        :</td>
                    <td style="text-align: left; font-weight: bold;" class="meter_sale_total" id="footer_list_meter_sales_amount">
                        {{ number_format($final_total, $currency_precision) }}</td>
                </tr>
                <input type="hidden" value="{{ $final_total }}" name="meter_sale_total" id="meter_sale_total">
            </tfoot>
        </table>
    </div>
</div>
