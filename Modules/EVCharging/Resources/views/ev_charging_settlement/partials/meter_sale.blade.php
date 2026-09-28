@php
    $currency_precision = 2;
    $meter_sale_arr = [];
    if (!empty($active_settlement) && request()->segment(2) == 'edit') {
        $meter_sale_arr = $active_settlement->meter_sales->toArray()[0];
    }

//     is settlement pd edit page
     if (empty($meter_sales) && request()->segment(4) == 'edit' && request()->segment(2) == 'settlement-pd') {
        $meter_sales = $active_settlement->meter_sales_pd;
    }

@endphp
<br>
<div class="row" id="meter-sale-form-block">
    @include('petro::settlement_pd.partials.meter_sale_form', ['meter_sale' => $meter_sale_arr])
</div>
<br>
<br>
<div class="row">
    <div class="col-md-12">

        <table class="table table-bordered table-striped" id="meter_sale_table">
            <thead>
                <tr>
                    <th>@lang('petro::lang.code')</th>
                    <th>@lang('petro::lang.products')</th>
                    <th>@lang('petro::lang.pump')</th>
                    <th style="width: 10px;">@lang('petro::lang.starting_meter')</th>
                    <th style="width: 10px;">@lang('petro::lang.closing_meter')</th>
                    <th>@lang('petro::lang.price')</th>
                    <th>@lang('petro::lang.sold_qty')</th> {{-- Qty = Closing Meter- Starting Meter - Testing Qty --}}
                    <th style="width: 10px;">@lang('petro::lang.discount_type')</th>
                    <th style="width: 6px;">@lang('petro::lang.discount_value')</th>
                    <th style="width: 10px;">@lang('petro::lang.testing_qty')</th>
                    <th>@lang('petro::lang.total_qty')</th>
                    <th>@lang('petro::lang.before_discount')</th>
                    <th>@lang('petro::lang.after_discount')</th>
                    <th>@lang('petro::lang.action')</th>
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
                                <td>{{$sale?->testing_qty}}</td>

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
                    <td colspan="3" style="text-align: right; font-weight: bold;">@lang('petro::lang.meter_sale_total')
                        :</td>
                    <td style="text-align: left; font-weight: bold;" class="meter_sale_total">
                        {{ number_format($final_total, $currency_precision) }}</td>
                </tr>
                <input type="hidden" value="{{ $final_total }}" name="meter_sale_total" id="meter_sale_total">
            </tfoot>
        </table>
    </div>
</div>

