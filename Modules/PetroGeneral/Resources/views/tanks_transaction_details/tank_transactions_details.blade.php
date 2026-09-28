<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('transaction_details_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('transaction_details_date_range', null, ['placeholder' =>
                        __('lang_v1.select_a_date_range'), 'class' =>
                        'form-control', 'id' => 'transaction_details_date_range', 'readonly']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('transaction_details_location_id', __('petrogeneral::lang.business_location') . ':') !!}
                        {!! Form::select('transaction_details_location_id', $business_locations, null, ['class' =>
                        'form-control select2 daily_report_change',
                        'placeholder' => __('petrogeneral::lang.all'), 'id' => 'transaction_details_location_id', 'style' =>
                        'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('transaction_details_tank_number', __('petrogeneral::lang.fuel_tank_number') . ':') !!}
                        {!! Form::select('transaction_details_tank_number', $tank_numbers, null, ['class' => 'form-control
                        select2 daily_report_change',
                        'placeholder' => __('petrogeneral::lang.all'), 'id' => 'transaction_details_tank_number', 'style' =>
                        'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('transaction_details_product_id', __('petrogeneral::lang.products') . ':') !!}
                        {!! Form::select('transaction_details_product_id', $products, null, ['class' => 'form-control
                        select2 daily_report_change',
                        'placeholder' => __('petrogeneral::lang.all'), 'id' => 'transaction_details_product_id', 'style' =>
                        'width:100%']); !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('transaction_details_settlement_id', __('petrogeneral::lang.settlment_nos') . ':') !!}
                        {!! Form::select('transaction_details_settlement_id', $settlements, null, ['class' => 'form-control
                        select2 daily_report_change',
                        'placeholder' => __('petrogeneral::lang.all'), 'id' => 'transaction_details_settlement_id', 'style' =>
                        'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('transaction_details_purhcase_no', __('petrogeneral::lang.purhcase_no') . ':') !!}
                        {!! Form::select('transaction_details_purhcase_no', $purhcase_nos, null, ['class' => 'form-control
                        select2 daily_report_change',
                        'placeholder' => __('petrogeneral::lang.all'), 'id' => 'transaction_details_purhcase_no', 'style' =>
                        'width:100%']); !!}
                    </div>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __(
    'petrogeneral::lang.all_your_tank_transaction_details')])
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="tank_transaction_details_table" style="width:100%;">
            <thead>
                <tr>
                    <th class="tt-c-dip">@lang('petrogeneral::lang.date_and_time')</th>
                    <th class="tt-c-loc">@lang('petrogeneral::lang.location')</th>
                    <th class="tt-c-txdate">Transaction<br>Date</th>
                    <th class="tt-c-tank">Fuel Tank<br>Number</th>
                    <th class="tt-c-product">@lang('petrogeneral::lang.product')</th>
                    <th class="tt-c-type">@lang('petrogeneral::lang.type')</th>
                    <th class="tt-c-ref">{{-- MA-002: shown as "Ref No"; full name on hover --}}<button type="button" class="tt-head-btn" title="@lang('petrogeneral::lang.settlement_purchase_invoice_no') / @lang('petrogeneral::lang.transfer_no')">Ref No</button></th>
                    <th class="tt-c-start">Starting<br>Qty</th>
                    <th class="tt-c-in">{{-- MA-002: shown as "Received" --}}<button type="button" class="tt-head-btn" title="@lang('petrogeneral::lang.purchase_qty') / @lang('petrogeneral::lang.transferred_in')">Received</button></th>
                    <th class="tt-c-testing">{{-- MA-002: renamed to "Testing". --}}
                        <button type="button" class="tt-head-btn"
                                title="@lang('petrogeneral::lang.testing_qty')">Testing</button></th> <!-- new column -->
                    <th class="tt-c-out">{{-- MA-002: shown as "Out" --}}<button type="button" class="tt-head-btn" title="@lang('petrogeneral::lang.sold_qty') / @lang('petrogeneral::lang.transferred_out')">Out</button></th>
                    <th class="tt-c-balance">Balance<br>Qty</th>
                </tr>
            </thead>
            <tfoot>
                <tr class="bg-gray font-17 footer-total text-center">
                    <td colspan="8"><strong>@lang('petrogeneral::lang.total'):</strong></td>
                    <td><span class="display_currency" id="footer_transaction_total_purchase_qty"></span></td>
                    <td><span class="display_currency" id="footer_transaction_total_testing_qty"></span></td> <!-- new footer -->
                    <td><span class="display_currency" id="footer_transaction_sold_qty"></span></td>
                </tr>
            </tfoot>
        </table>

    </div>
    @endcomponent

</section>
<!-- /.content -->

<style>
/*
 * MA-002 (IS-1910): keep these tables inside the screen.
 *
 * Both already sat in a .table-responsive wrapper, which is why this looked
 * like it should already work. In Bootstrap 3 that class only applies its
 * overflow rule BELOW 768px:
 *
 *     @media screen and (max-width: 767px) { .table-responsive { overflow-x: auto; } }
 *
 * On a desktop it does nothing at all, so a table wider than its container
 * simply spills past the right edge with no scrollbar - exactly what the
 * screenshots show. These tables carry 12 and 15 columns, so they are always
 * wider than the page.
 *
 * The rules below apply the overflow at every width, and clear the clipping
 * on the surrounding boxes that would otherwise cut the scrollbar off.
 * Scoped to these two table ids so no other screen is affected.
 */
#tank_transaction_summary_table_wrapper,
#tank_transaction_details_table_wrapper,
#tank_transaction_summary_table_wrapper .table-responsive,
#tank_transaction_details_table_wrapper .table-responsive {
    overflow-x: auto !important;
    overflow-y: visible !important;
    width: 100%;
    -webkit-overflow-scrolling: touch;
}

/* The AdminLTE box around the table clips the scrollbar without this. */
#tank_transaction_summary_table_wrapper,
#tank_transaction_details_table_wrapper {
    max-width: 100%;
}

/*
 * MA-002: HEADINGS WRAP, DATA DOES NOT.
 *
 * My earlier fix put white-space: nowrap on every th AND td, to stop the
 * columns collapsing while the table scrolled. That is also what stopped the
 * long headings wrapping - the opposite of what is wanted here.
 *
 * Split: headings may take two lines, figures stay on one. A quantity broken
 * across two lines is much harder to read than a heading, and these are fuel
 * volumes people scan down a column.
 */
#tank_transaction_summary_table th,
#tank_transaction_details_table th {
    white-space: normal;
    word-break: normal;
    overflow-wrap: break-word;
    line-height: 1.25;
    vertical-align: middle;
    padding: 4px 4px !important;
    font-size: 10.5px !important;
}

/*
 * IS1959: CELL TEXT WRAPS - IT MUST NOT OVERLAP THE NEXT COLUMN.
 *
 * These cells were white-space: nowrap under table-layout: fixed. A fixed
 * layout gives each column a set width, and nowrap forbids breaking the line,
 * so any value longer than its column simply painted over the one beside it -
 * "Purchase Reference No 000105" in the Type column ran straight across Ref No
 * and made both unreadable.
 *
 * Long values now wrap onto a second or third line instead, which is what was
 * asked for. overflow-wrap: break-word handles normal spaced text; word-break
 * covers unbroken strings like reference numbers that have nowhere to break.
 *
 * Numeric columns keep their single line further down: a quantity split across
 * two lines is far harder to scan than a wrapped label.
 */
#tank_transaction_summary_table td,
#tank_transaction_details_table td {
    white-space: normal;
    overflow-wrap: break-word;
    word-break: break-word;
    vertical-align: middle;
    line-height: 1.25;
    padding: 4px 4px !important;
    font-size: 10.5px !important;
}

/*
 * IS1959: keep figures on one line.
 *
 * .tt-num is applied by the column definitions to the quantity columns, so the
 * wrapping above applies to labels and references while numbers stay intact.
 * If a figure is ever too wide for its column it shrinks rather than breaking.
 */
#tank_transaction_summary_table td.tt-num,
#tank_transaction_details_table td.tt-num {
    white-space: nowrap;
}

/*
 * Twelve columns on this table - three more than the Summary - so the widths
 * are tighter. The long headings get the narrowest share BECAUSE they now wrap:
 * once on two lines they no longer need the room, which is what lets the whole
 * table sit inside the page.
 *
 * Column 2 (Location) is hidden by the DataTables config, but it still counts
 * as a column, so it keeps a width entry to stop everything after it shifting.
 */
#tank_transaction_details_table {
    table-layout: fixed;
    width: 100%;
}

/*
 * MA-002: widths are set by CLASS, not nth-child, and that matters here.
 *
 * The Location column is hidden by the DataTables config, and DataTables does
 * not merely hide a column - it REMOVES it from the DOM. So nth-child(3) would
 * point at the third VISIBLE column, not the third in this markup, and every
 * width after the hidden one would land on the wrong column.
 *
 * A class travels with its own <th>, so it stays correct whatever DataTables
 * shows or hides. The eleven visible widths add to 100%.
 */
/* =====================================================================
   MA-002: column widths reduced as requested.

       Transaction Date   -40%      8%  ->  4.8%
       Fuel Tank Number   -50%      8%  ->  4.0%
       Ref No             -70%     11%  ->  3.3%
       Starting Qty       -20%      9%  ->  7.2%
       Received           -40%     10%  ->  6.0%
       Testing            -40%      8%  ->  4.8%
       Out                -50%     10%  ->  5.0%
       Product            +30%      8%  -> 10.4%

   Those reductions free 26.5% of the row. Left unallocated the browser
   would ignore the whole colgroup and size by content, which is exactly
   what these rules exist to prevent - so the slack is given to the four
   columns that carry real text: Product, Type, Dip Date and Balance.

   The row still totals exactly 100%.
   ===================================================================== */
/* =====================================================================
   IS1959 (layout request): column widths adjusted as asked.

       Transaction Date   -30%    4.8%  ->  3.36%
       Fuel Tank Number   -30%    4.0%  ->  2.80%
       Type               +60%   16.6%  -> 26.56%
       Ref No             +40%    3.3%  ->  4.62%
       Starting Qty       -30%    7.2%  ->  5.04%
       Balance Qty        -30%   11.7%  ->  8.19%

   Those six changes leave the row 2.97% over 100%. The widths must total
   exactly 100 or the browser discards the whole set and falls back to
   sizing by content, which is precisely what these rules exist to stop.

   The surplus is taken from PRODUCT (22.3% -> 19.33%) because it was the
   only wide column not named in the request, and it has the most room to
   give. Every requested change is applied exactly as specified.

   Row totals 100.00%.
   ===================================================================== */
/*
 * 8029 column width changes. The visible columns still total 100.00%, so the
 * space taken from one column is given to another rather than left as a gap:
 *
 *   Dip Date & Time    14.30% -> 11.44%   (-20%)
 *   Transaction Date    3.36% ->  2.69%   (-20%)
 *   Starting Qty        5.04% ->  6.05%   (+20%)
 *   Balance Qty         8.19% ->  9.83%   (+20%)
 *   Purchase Ref No     4.62% ->  5.50%   (now a compact "Click Here" button)
 *
 * tt-c-loc is hidden by DataTables, so its width plays no part in the total.
 */
.tt-c-dip      { width: 11.44%; }
.tt-c-loc      { width: 8%; }    /* hidden by DataTables - width ignored */
.tt-c-txdate   { width: 2.69%; }
.tt-c-tank     { width: 2.8%; }
.tt-c-product  { width: 19.33%; }
.tt-c-type     { width: 26.56%; }
.tt-c-ref      { width: 5.5%; text-align: center; }
.tt-c-start    { width: 6.05%; text-align: right; }
.tt-c-in       { width: 6%;    text-align: right; }
.tt-c-testing  { width: 4.8%;  text-align: right; }
.tt-c-out      { width: 5%;    text-align: right; }
.tt-c-balance  { width: 9.83%; text-align: right; }

/*
 * 8029: the Purchase Ref No cell holds a button, so it is centred and must not
 * wrap - the type column beside it was being pushed into by long references.
 */
.tt-ref-btn {
    white-space: nowrap;
    padding: 2px 8px;
    font-size: 11px;
}

/*
 * IS1959 (layout request): quantity CELLS right aligned, not just headings.
 *
 * The .tt-c-* rules above sit on the <th> only, so the headings were right
 * aligned while the figures beneath them stayed left. .tt-num is applied to
 * the quantity columns by the DataTables column definitions, so it lands on
 * every <td> as well as the <th> - which is what actually moves the numbers.
 *
 * The footer totals are right aligned to match, so each total sits directly
 * under its column.
 */
#tank_transaction_details_table td.tt-num,
#tank_transaction_details_table th.tt-num {
    text-align: right !important;
}

#tank_transaction_details_table tfoot td {
    text-align: right !important;
}

#tank_transaction_details_table tfoot td[colspan] {
    text-align: right !important;
    padding-right: 10px !important;
}

/* MA-002: the abbreviated headings. A real <button> so it is reachable by
   keyboard, with title= for the hover text - no plugin needed, and it
   still reads correctly if JavaScript is unavailable. */
.tt-head-btn {
    background: #eef2f7;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    padding: 2px 9px;
    font-size: 12px;
    font-weight: 700;
    color: #0f172a;
    cursor: help;
    line-height: 1.4;
    white-space: nowrap;
}

.tt-head-btn:hover,
.tt-head-btn:focus {
    background: #dbe7f3;
    outline: none;
}
</style>
