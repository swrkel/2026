@php
$currency_precision = 2;
$meter_sale_arr = [];
if (!empty($active_settlement) && request()->segment(2) == 'edit') {
$meter_sale_arr = $active_settlement->meter_sales->toArray()[0];
}

// is settlement pd edit page
if (empty($meter_sales) && request()->segment(4) == 'edit' && request()->segment(2) == 'settlement-pd') {
$meter_sales = $active_settlement->meter_sales_pd;
}

@endphp
<br>
<div class="row" id="meter-sale-form-block">
    @include('petrogeneral::settlement_pd.partials.meter_sale_form', ['meter_sale' => $meter_sale_arr])
</div>
<br>
<br>
<div class="row" id="meter_sale_table_wrap" style="display: none;">
    <div class="col-md-12">

        <table class="table table-bordered table-striped" id="meter_sale_table">
            <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.code')</th>
                    <th>@lang('petrogeneral::lang.products')</th>
                    <th>@lang('petrogeneral::lang.pump')</th>
                    <th style="width: 10px;">@lang('petrogeneral::lang.starting_meter')</th>
                    <th style="width: 10px;">@lang('petrogeneral::lang.closing_meter')</th>
                    <th>@lang('petrogeneral::lang.price')</th>
                    <th>@lang('petrogeneral::lang.sold_qty')</th> {{-- Qty = Closing Meter- Starting Meter - Testing Qty --}}
                    <th style="width: 10px;">@lang('petrogeneral::lang.discount_type')</th>
                    <th style="width: 6px;">@lang('petrogeneral::lang.discount_value')</th>
                    <th style="width: 10px;">@lang('petrogeneral::lang.testing_qty')</th>
                    <th>@lang('petrogeneral::lang.total_qty')</th>
                    <th>@lang('petrogeneral::lang.before_discount')</th>
                    <th>@lang('petrogeneral::lang.after_discount')</th>
                    <th>@lang('petrogeneral::lang.action')</th>
                </tr>
            </thead>
            <tbody>
                @php
                $final_total = 0.0;
                @endphp
                {{-- @if (!empty($active_settlement)) --}}
                @if (!empty($meter_sales))
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

                    <td>-</td>
                    <td>0.00</td>
                    <td>{{ number_format($sale->testing_qty ?? 0, 2) }}</td>

                    <td>{{ number_format($quantity, 2) }}</td>
                    <td>{{ number_format($subTotal, 2) }}</td>
                    <td>{{ number_format($withDiscount, 2) }}</td>

                    <td>
                        <button class="btn btn-xs btn-primary" disabled>
                            <i class="fa fa-lock"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
                @endforeach

                @endif

            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6"></td>
                    <td><span class="product_summary"></span></td>
                    <td colspan="3" style="text-align: right; font-weight: bold;">@lang('petrogeneral::lang.meter_sale_total')
                        :</td>
                    <td style="text-align: left; font-weight: bold;" class="meter_sale_total" id="footer_list_meter_sales_amount">
                        {{ number_format($final_total, $currency_precision) }}</td>
                </tr>
                <input type="hidden" value="{{ $final_total }}" name="meter_sale_total" id="meter_sale_total">
            </tfoot>
        </table>
    </div>
</div>

<div class="table-responsive 1234" id="outside_meter_sale_table" style="display: none;">
    <table class="table table-bordered table-striped" id="pump_operator_meter_sale_table" style="width: 100%;">
        <thead>
            <tr>
                <th>@lang('petrogeneral::lang.code' )</th>
                <th>@lang('petrogeneral::lang.products' )</th>
                <th>@lang('petrogeneral::lang.pump' )</th>
                <th style="width: 10px;">@lang('petrogeneral::lang.starting_meter')</th>
                <th style="width: 10px;">@lang('petrogeneral::lang.closing_meter')</th>
                <th>@lang('petrogeneral::lang.price')</th>
                <th>@lang('petrogeneral::lang.sold_qty' )</th> {{-- Qty = Closing Meter- Starting Meter - Testing Qty --}}
                <th style="width: 10px;">@lang('petrogeneral::lang.discount_type' )</th>
                <th style="width: 6px;">@lang('petrogeneral::lang.discount_value' )</th>
                <th style="width: 10px;">@lang('petrogeneral::lang.testing_qty' )</th>
                <th>@lang('petrogeneral::lang.total_qty' )</th>
                <th>@lang('petrogeneral::lang.before_discount' )</th>
                <th>@lang('petrogeneral::lang.after_discount' )</th>
                <th>@lang('petrogeneral::lang.action' )</th>
            </tr>
        </thead>


        <tfoot>
            <tr>
                <td colspan="6"></td>
                <td><span class="product_summary"></span></td>
                <td colspan="3" style="text-align: right; font-weight: bold;">@lang('petrogeneral::lang.meter_sale_total')
                    :</td>
                <td style="text-align: left; font-weight: bold;" class="meter_sale_total" id="footer_list_meter_sales_amount">
                    {{ number_format($final_total, $currency_precision) }}</td>
            </tr>
            <input type="hidden" value="{{ $final_total }}" name="meter_sale_total" id="meter_sale_total">
        </tfoot>
    </table>
</div>