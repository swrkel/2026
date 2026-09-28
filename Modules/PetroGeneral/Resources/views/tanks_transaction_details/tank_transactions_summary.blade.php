<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('transaction_summary_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('transaction_summary_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' =>
                    'form-control', 'id' => 'transaction_summary_date_range', 'readonly']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('transaction_summary_location_id', __('petrogeneral::lang.business_location') . ':') !!}
                    {!! Form::select('transaction_summary_location_id', $business_locations, null, ['class' =>
                    'form-control select2 daily_report_change',
                    'placeholder' => __('petrogeneral::lang.all'), 'id' => 'transaction_summary_location_id', 'style' =>
                    'width:100%']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('transaction_summary_product_id', __('petrogeneral::lang.products') . ':') !!}
                    {!! Form::select('transaction_summary_product_id', $products, null, ['class' => 'form-control
                    select2 daily_report_change',
                    'placeholder' => __('petrogeneral::lang.all'), 'id' => 'transaction_summary_product_id', 'style' =>
                    'width:100%']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('transaction_summary_tank_number', __('petrogeneral::lang.fuel_tank_number') . ':') !!}
                    {!! Form::select('transaction_summary_tank_number', $tank_numbers, null, ['class' => 'form-control
                    select2 daily_report_change',
                    'placeholder' => __('petrogeneral::lang.all'), 'id' => 'transaction_summary_tank_number', 'style' =>
                    'width:100%']); !!}
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __(
    'petrogeneral::lang.all_your_tank_transaction_summary')])
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="tank_transaction_summary_table" style="width:100%;">
            <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.transaction_date')</th>
                    <th>@lang('petrogeneral::lang.location')</th>
                    <th>@lang('petrogeneral::lang.fuel_tank_number')</th>
                    <th>@lang('petrogeneral::lang.product')</th>
                    {{-- MA-002: "Tank Starting Stock" is three words in a narrow column.
                         Left alone it breaks after "Tank" and leaves "Starting Stock" on
                         line two, which is the wider half. Breaking after "Tank Starting"
                         balances the two lines. --}}
                    <th>Tank Starting<br>Stock</th>
                    {{-- MA-002: break AFTER the slash so the two halves sit on their own
                         lines, rather than wherever the browser happens to run out of room. --}}
                    <th>@lang('petrogeneral::lang.total_purchase') /<br>@lang('petrogeneral::lang.transferred_in')</th>
                    <th>@lang('petrogeneral::lang.testing_in')</th>
                    <th>@lang('petrogeneral::lang.total_sold_qty') /<br>@lang('petrogeneral::lang.transferred_out')</th>
                    <th>@lang('petrogeneral::lang.balance_qty')</th>

                </tr>
            </thead>
            <tfoot>
                <tr class="bg-gray font-17 footer-total text-center">
                    <td colspan="5"><strong>@lang('petrogeneral::lang.total'):</strong></td>
                    <td><span class="display_currency" id="footer_total_purchase_qty"></span></td>
                    <td><span class="display_currency" id="footer_testing_qty"></span></td>
                    <td><span class="display_currency" id="footer_sold_qty"></span></td>
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
 * My earlier fix put white-space: nowrap on every th AND td here, to stop the
 * columns collapsing into a tangle while the table scrolled. That also stopped
 * the long headings wrapping, which is the opposite of what is wanted now:
 *
 *     Tank Starting Stock
 *     Total Purchase / Transferred In
 *     Total Sold Qty / Transferred Out
 *
 * Those three are the widest columns on the table and they were each being
 * held on one line, forcing the whole table wider than the page.
 *
 * So the rule is split: HEADINGS may wrap onto two lines, DATA still does not -
 * a figure broken across two lines is much harder to read than a heading.
 */
#tank_transaction_summary_table th,
#tank_transaction_details_table th {
    white-space: normal;      /* headings wrap */
    word-break: normal;
    overflow-wrap: break-word;
    line-height: 1.25;
    vertical-align: middle;
    padding: 5px 5px !important;
    font-size: 11px !important;
}

#tank_transaction_summary_table td,
#tank_transaction_details_table td {
    white-space: nowrap;      /* figures stay on one line */
    padding: 5px 5px !important;
    font-size: 11px !important;
}

/*
 * Column widths roughly halved. The three long headings get the narrowest
 * share, because once they wrap onto two lines they no longer need the room -
 * that is what lets the whole table fit inside the page.
 */
#tank_transaction_summary_table {
    table-layout: fixed;
    width: 100%;
}

#tank_transaction_summary_table th:nth-child(1),
#tank_transaction_summary_table td:nth-child(1) { width: 11%; }   /* Transaction Date */
#tank_transaction_summary_table th:nth-child(2),
#tank_transaction_summary_table td:nth-child(2) { width: 12%; }   /* Location */
#tank_transaction_summary_table th:nth-child(3),
#tank_transaction_summary_table td:nth-child(3) { width: 10%; }   /* Fuel Tank Number */
#tank_transaction_summary_table th:nth-child(4),
#tank_transaction_summary_table td:nth-child(4) { width: 11%; }   /* Product */
#tank_transaction_summary_table th:nth-child(5),
#tank_transaction_summary_table td:nth-child(5) { width: 11%; text-align: right; }   /* Tank Starting Stock */
#tank_transaction_summary_table th:nth-child(6),
#tank_transaction_summary_table td:nth-child(6) { width: 12%; text-align: right; }   /* Total Purchase / Transferred In */
#tank_transaction_summary_table th:nth-child(7),
#tank_transaction_summary_table td:nth-child(7) { width: 10%; text-align: right; }   /* Testing In */
#tank_transaction_summary_table th:nth-child(8),
#tank_transaction_summary_table td:nth-child(8) { width: 12%; text-align: right; }   /* Total Sold Qty / Transferred Out */
#tank_transaction_summary_table th:nth-child(9),
#tank_transaction_summary_table td:nth-child(9) { width: 11%; text-align: right; }   /* Balance Qty */
</style>
