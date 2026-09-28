<div class="pos-bill">


    <!-- Business Name -->
    <div class="bill-title">
        {{ request()->session()->get('business.name') }}
    </div>


    <!-- Bill Type -->
    <div class="bill-sub-title">
        @lang('petro::lang.daily_voucher')
    </div>


    <hr>


    <!-- Voucher Info -->
    <div class="info-row">
        <span>@lang('petro::lang.date')</span> {{ $daily_voucher->transaction_date }}
    </div>
    <div class="info-row">
        <span>@lang('petro::lang.bill_no')</span> {{ $daily_voucher->daily_vouchers_no }}
    </div>
    <div class="info-row">
        <span>@lang('petro::lang.order_no')</span> {{ $daily_voucher->voucher_order_number }}
    </div>
    <div class="info-row">
        <span>@lang('petro::lang.customer_name')</span> {{ $daily_voucher->customer_name }}
    </div>
    <div class="info-row">
        <span>@lang('petro::lang.vehicle_no')</span> {{ $daily_voucher->reference }}
    </div>


    <hr>


    <!-- Items -->
    <table class="pos-table">
        <thead>
            <tr>
                <th>@lang('petro::lang.product')</th>
                <th class="right">@lang('petro::lang.unit_price')</th>
                <th class="right">@lang('petro::lang.qty')</th>
                <th class="right">@lang('petro::lang.sub_total')</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($daily_voucher_items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td class="right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="right">{{ $item->qty }}</td>
                    <td class="right">{{ number_format($item->sub_total, 2) }}</td>
                </tr>
            @endforeach


            <tr>
                <td colspan="4">
                    <hr class="pos-hr">
                </td>
            </tr>
            <tr class="total-row">
                <td colspan="3" class="right">@lang('petro::lang.total_amount')</td>
                <td class="right">{{ number_format($daily_voucher->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>


    <hr>


    <div class="footer">
        Thank You
    </div>


</div>