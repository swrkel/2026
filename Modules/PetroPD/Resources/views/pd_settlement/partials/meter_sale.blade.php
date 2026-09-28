@php
    $currency_precision = 2;
    $meter_sale_arr = [];
    if (!empty($active_settlement) && request()->segment(2) == 'edit' && !empty($active_settlement->meter_sales) && $active_settlement->meter_sales->count()) {
        $meter_sale_arr = $active_settlement->meter_sales->first()->toArray();
    }

    $display_meter_sales = $display_meter_sales ?? collect();

    $petroPdUser = auth()->user();
    $petroPdIsSuperadmin = $petroPdUser && $petroPdUser->can('superadmin');
    $canEditMeterSale = $petroPdUser && (
        $petroPdIsSuperadmin
        || ($petroPdUser->can('petro_pd.edit_settlement') && $petroPdUser->can('petro_pd.manual_entry'))
    );
    $canDeleteMeterSale = $petroPdUser && (
        $petroPdIsSuperadmin
        || ($petroPdUser->can('petro_pd.delete_settlement') && $petroPdUser->can('petro_pd.manual_entry'))
    );

@endphp

<style id="s411-petro-pd-meter-sale-single-line">
    /* IS1799: This partial is loaded only on PetroPD Settlement meter-sale
       screens. Reduce that page footer by exactly 50% without changing the
       global application footer used by other modules. */
    body .main-footer {
        font-size: 50% !important;
    }
    body .main-footer * {
        font-size: inherit !important;
    }

    /* S411-3: Compact Meter Sales entry form; no horizontal form slider. */
    #meter-sale-form-block.pd-meter-sale-form-scroll {
        margin-left: 0;
        margin-right: 0;
    }
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        gap: 8px 10px;
        overflow-x: visible !important;
        padding-bottom: 4px;
        width: 100%;
    }
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > [class*="col-md-"] {
        float: none !important;
        padding-left: 3px;
        padding-right: 3px;
        margin: 0;
        flex: 0 0 150px;
        max-width: 150px;
        order: 1;
    }
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pump_starting_meter_div,
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pump_closing_meter_div {
        flex-basis: 150px;
        max-width: 150px;
    }
    /* S411-4: requested width reductions in Meter Sales entry row. */
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-sold,
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-testing {
        flex-basis: 60px;
        max-width: 60px;
    }
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-unit-price {
        flex-basis: 75px;
        max-width: 75px;
    }
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-discount-type {
        flex-basis: 75px;
        max-width: 75px;
    }
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-discount {
        flex-basis: 90px;
        max-width: 90px;
    }
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-discount-type select,
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-discount-type .select2-container {
        width: 75px !important;
    }

    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-pump { order: 1; }
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pump_starting_meter_div { order: 2; }
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pump_closing_meter_div { order: 3; }
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-sold { order: 4; }
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-unit-price { order: 5; }
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-testing { order: 6; }
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-discount-type { order: 7; }
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-discount { order: 8; }
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-manual { order: 9; }
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-add-action,
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-edit-actions { order: 10; }
    /* S411-5: Keep action buttons on the same entry row, after Discount Value. */
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-manual,
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-add-action,
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-edit-actions {
        flex: 0 0 auto;
        max-width: none;
        width: auto;
        clear: none !important;
        align-self: flex-start;
        padding-top: 27px;
        margin-top: 0 !important;
    }
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-manual {
        flex-basis: 112px;
        margin-left: 8px;
    }
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-add-action,
    #meter-sale-form-block.pd-meter-sale-form-scroll > .col-md-12 > .pd-ms-edit-actions {
        margin-left: 0 !important;
    }
    #meter-sale-form-block.pd-meter-sale-form-scroll .btn-manual-entry,
    #meter-sale-form-block.pd-meter-sale-form-scroll .btn_meter_sale_pd,
    #meter-sale-form-block.pd-meter-sale-form-scroll .btn_meter_sale_cancel,
    #meter-sale-form-block.pd-meter-sale-form-scroll .btn_update_meter_sale_pd {
        margin-top: 0 !important;
        min-height: 38px;
    }
    #meter-sale-form-block.pd-meter-sale-form-scroll label {
        white-space: normal;
        font-size: 12px;
        line-height: 1.15;
        min-height: 28px;
        margin-bottom: 4px;
    }
    #meter-sale-form-block.pd-meter-sale-form-scroll .form-control,
    #meter-sale-form-block.pd-meter-sale-form-scroll .select2-container,
    #meter-sale-form-block.pd-meter-sale-form-scroll .btn {
        font-size: 12px;
    }
    #meter-sale-form-block.pd-meter-sale-form-scroll .form-control {
        padding-left: 6px;
        padding-right: 6px;
    }
    /* IS1776: Full-width Meter Sales table for PetroPD Edit/Edit No Change.
       The table owns 100% of the settlement tab. DataTables and global
       table-responsive rules are not allowed to crop the right-hand columns. */
    .pd-meter-sale-table-row,
    .pd-meter-sale-table-row > .col-md-12 {
        width: 100%;
        max-width: 100%;
        clear: both;
        float: none;
    }

    .pd-meter-sale-table-row {
        margin-top: 6px;
    }

    .pd-meter-sale-table-wrap {
        position: relative;
        display: block;
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: auto !important;
        overflow-y: visible !important;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 4px;
    }

    #meter_sale_table_wrapper,
    #meter_sale_table_wrapper .dataTables_scroll,
    #meter_sale_table_wrapper .dataTables_scrollHead,
    #meter_sale_table_wrapper .dataTables_scrollHeadInner,
    #meter_sale_table_wrapper .dataTables_scrollBody {
        width: 100% !important;
        max-width: 100% !important;
    }

    #meter_sale_table_wrapper {
        margin: 0 !important;
        overflow: visible !important;
    }

    #meter_sale_table_wrapper > .row {
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    #meter_sale_table.pd-meter-sale-nowrap-table,
    #meter_sale_table_wrapper table#meter_sale_table {
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        table-layout: fixed !important;
        margin: 0 !important;
    }

    #meter_sale_table.pd-meter-sale-nowrap-table th,
    #meter_sale_table.pd-meter-sale-nowrap-table td {
        box-sizing: border-box;
        vertical-align: middle !important;
        padding: 6px 3px !important;
        line-height: 1.18 !important;
        border-color: #e6e9ee !important;
    }

    #meter_sale_table.pd-meter-sale-nowrap-table th {
        white-space: normal !important;
        overflow-wrap: anywhere;
        word-break: normal;
        text-align: center !important;
        font-size: 9.975px !important;
        font-weight: 700;
    }

    #meter_sale_table.pd-meter-sale-nowrap-table td {
        white-space: nowrap !important;
        text-align: right;
        font-size: 11.025px !important;
    }

    #meter_sale_table.pd-meter-sale-nowrap-table td:nth-child(1),
    #meter_sale_table.pd-meter-sale-nowrap-table td:nth-child(2),
    #meter_sale_table.pd-meter-sale-nowrap-table td:nth-child(3),
    #meter_sale_table.pd-meter-sale-nowrap-table td:nth-child(8),
    #meter_sale_table.pd-meter-sale-nowrap-table td:nth-child(14) {
        text-align: left;
    }

    #meter_sale_table.pd-meter-sale-nowrap-table td:nth-child(2),
    #meter_sale_table.pd-meter-sale-nowrap-table td:nth-child(8) {
        white-space: normal !important;
        overflow-wrap: anywhere;
    }

    #meter_sale_table.pd-meter-sale-nowrap-table th:nth-child(14),
    #meter_sale_table.pd-meter-sale-nowrap-table td:nth-child(14) {
        text-align: center !important;
    }

    #meter_sale_table.pd-meter-sale-nowrap-table td:nth-child(14) .btn {
        min-width: 0;
        max-width: 100%;
        padding: 4px 5px !important;
        font-size: 9.975px !important;
        line-height: 1.1;
        white-space: nowrap;
    }

    #meter_sale_table.pd-meter-sale-nowrap-table tfoot td {
        font-size: 11.025px !important;
        white-space: normal !important;
    }

    @media (max-width: 1199px) {
        #meter_sale_table.pd-meter-sale-nowrap-table,
        #meter_sale_table_wrapper table#meter_sale_table {
            min-width: 1080px !important;
        }
    }
</style>

<div class="row pd-meter-sale-form-scroll" id="meter-sale-form-block">
    @include('petropd::pd_settlement.partials.meter_sale_form', [
        'meter_sale' => $meter_sale_arr,
        'manual_entry_permission' => $manual_entry_permission ?? auth()->user()->can('petro_pd.manual_entry'),
    ])
</div>
<div class="row pd-meter-sale-table-row">
    <div class="col-md-12">

        <div class="table-responsive pd-meter-sale-table-wrap">
        <table class="table table-bordered table-striped pd-meter-sale-nowrap-table" id="meter_sale_table">
            <colgroup>
                <col style="width: 7%;">
                <col style="width: 10%;">
                <col style="width: 6%;">
                <col style="width: 8%;">
                <col style="width: 8%;">
                <col style="width: 6%;">
                <col style="width: 6%;">
                <col style="width: 7%;">
                <col style="width: 7%;">
                <col style="width: 6%;">
                <col style="width: 6%;">
                <col style="width: 8%;">
                <col style="width: 8%;">
                <col style="width: 7%;">
            </colgroup>
            <thead>
                <tr>
                    <th>@lang('petropd::lang.code')</th>
                    <th>@lang('petropd::lang.products')</th>
                    <th>@lang('petropd::lang.pump')</th>
                    <th>@lang('petropd::lang.starting_meter')</th>
                    <th>@lang('petropd::lang.closing_meter')</th>
                    <th>@lang('petropd::lang.price')</th>
                    <th>@lang('petropd::lang.sold_qty')</th> {{-- Qty = Closing Meter- Starting Meter - Testing Qty --}}
                    <th>@lang('petropd::lang.discount_type')</th>
                    <th>@lang('petropd::lang.discount_value')</th>
                    <th>@lang('petropd::lang.testing_qty')</th>
                    <th>@lang('petropd::lang.total_qty')</th>
                    <th>@lang('petropd::lang.before_discount')</th>
                    <th>@lang('petropd::lang.after_discount')</th>
                    <th>@lang('petropd::lang.action')</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $final_total = 0.0;
                @endphp
                @foreach ($display_meter_sales as $item)
                    @php
                        $quantity = $item->quantity;
                        $subTotal = $item->sub_total;
                        $withDiscount = $item->discount_amount;
                        $final_total += $withDiscount;
                    @endphp

                    <tr>
                        <td>{{ $item->sku }}</td>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->pump_no }}</td>
                        <td>{{ number_format($item->starting_meter, 2) }}</td>
                        <td>{{ number_format($item->closing_meter, 2) }}</td>
                        <td>{{ number_format($item->price, 2) }}</td>
                        <td>{{ number_format($quantity, 2) }}</td>
                        <td>{{ $item->discount_type }}</td>
                        <td>{{ number_format($item->discount, 2) }}</td>
                        <td>{{ number_format($item->testing_qty, 2) }}</td>
                        <td>{{ number_format($item->total_qty ?? ($quantity + (float) $item->testing_qty), 2) }}</td>
                        <td>{{ number_format($subTotal, 2) }}</td>
                        <td>{{ number_format($withDiscount, 2) }}</td>
                        <td>
                            @if ($canEditMeterSale && !empty($item->form_url))
                                <button type="button" class="btn btn-xs btn-primary petropd-meter-sale-edit"
                                    data-href="{{ $item->form_url }}">Edit</button>
                            @endif
                        </td>
                    </tr>
                @endforeach

            </tbody>
            <tfoot>
                <tr>
                    <td colspan="11"><span class="product_summary"></span></td>
                    <td style="text-align: right; font-weight: bold;">@lang('petropd::lang.meter_sale_total'):</td>
                    <td style="text-align: right; font-weight: bold;" class="meter_sale_total">
                        {{ number_format($final_total, $currency_precision) }}</td>
                    <td></td>
                </tr>
                <input type="hidden" value="{{ $final_total }}" name="meter_sale_total" id="meter_sale_total">
            </tfoot>
        </table>
        </div>
    </div>
</div>

{{-- Meter-sale JavaScript is loaded from create.blade.php inside the page javascript section. --}}


