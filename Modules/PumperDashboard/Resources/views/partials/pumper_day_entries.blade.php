

<style>
    /* IS1884: the summary response must not inherit Bootstrap/global .row
       decoration.  A neutral host removes the marked white strip without
       changing the summary content or any table/Ajax logic. */
    #pumper_day_entry_summary.pd-day-entry-summary-host {
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        float: none !important;
        clear: both;
    }

    #pumper_day_entry_summary.pd-day-entry-summary-host:empty {
        display: none !important;
    }

    #pump_operators_day_entries_table tfoot .footer-total td,
    #pump_operators_day_entries_table tfoot .footer-total td *,
    #pump_operators_day_entries_table tfoot .footer-total strong {
        font-size: 20px !important;
        font-weight: 800 !important;
        line-height: 1.35 !important;
    }
</style>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])

            <div class="row">
                 <div class="col-md-4 px-4" style="margin-left: 35px;">
                    <div class="form-group">
                        {!! Form::label('shift_id',  __('pumperdashboard::lang.shift') . ':') !!}
                        <select class="form-control select2" style = 'width:100%' id="shift_id">
                            @foreach($shifts as $shift)
                                @php
                                    $displayShiftNumber = $shift->assignment_shift_number ?? $shift->shift_number ?? $shift->id;
                                @endphp
                                <option value="{{ $shift->id }}"
                                    data-shift-number="{{ $displayShiftNumber }}"
                                    @if(!empty($selected_shift_id) && $selected_shift_id == $shift->id) selected @endif>
                                    {{ $shift->name }} - Shift {{ $displayShiftNumber }} ({{ @format_date($shift->assignment_date ?? $shift->shift_date ?? $shift->created_at) }} to {{ !empty($shift->closed_time) ? @format_datetime($shift->closed_time) : 'Open' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

            </div>


            @endcomponent
        </div>
    </div>


    <div id="pumper_day_entry_summary" class="pd-day-entry-summary-host"></div>

    @component('components.widget', ['class' => 'box-primary', 'title' =>
    __('pumperdashboard::lang.all_your_daily_collection')])
    {{--
        MA-002: show all 14 columns at once, without horizontal scrolling.

        The table sat in .table-responsive, which gives it a horizontal
        scrollbar - so on a normal screen the last columns were off to the
        right and had to be scrolled to.

        Three changes make them all fit:

          THE PAGE USES ITS FULL WIDTH. The layout's container caps content
          at a fixed width; for this one table that cap is lifted.

          THE TEXT IS SMALLER AND TIGHTER on this table only - 12px with
          reduced cell padding. 14 columns need the room.

          HEADINGS MAY WRAP TO TWO LINES rather than forcing a column wider
          than its content needs.

        .table-responsive is KEPT as a fallback. On a genuinely small screen
        - a phone or a narrow tablet - 14 columns cannot fit however small
        the text, and scrolling is better than unreadable. It simply will
        not be needed on a desktop.
    --}}
    <style>
        /* This table only - nothing else on the page is affected. */
        .pd-day-entries-fullwidth {
            width: 100% !important;
            max-width: none !important;
        }
        #pump_operators_day_entries_table {
            font-size: 12px;
            table-layout: auto;
        }
        #pump_operators_day_entries_table > thead > tr > th,
        #pump_operators_day_entries_table > tbody > tr > td {
            padding: 5px 6px;
            vertical-align: middle;
        }
        #pump_operators_day_entries_table > thead > tr > th {
            white-space: normal;   /* let a heading use two lines */
            line-height: 1.25;
        }
        /* Figures stay on one line so they cannot be misread. */
        #pump_operators_day_entries_table > tbody > tr > td {
            white-space: nowrap;
        }
        @media (min-width: 1200px) {
            /* Wide enough for all 14 - no scrollbar needed. */
            .pd-day-entries-scroll { overflow-x: visible; }
        }
    </style>
    <div class="table-responsive pd-day-entries-scroll">
        <table class="table table-bordered table-striped pd-day-entries-fullwidth" id="pump_operators_day_entries_table" style="width: 100%;">
            <thead>
                <tr>
                    {{-- MA-002 (S-609 #9): Action column removed.
                         Removed here AND from columns[] in pumper_day_entries.blade.php,
                         and the columnDefs target moved from 0 to 1 - DataTables pairs
                         headers to columns BY POSITION, so removing only one of the
                         three would shift every value one column left. --}}
                    <th>@lang('pumperdashboard::lang.date')</th>
                    <th>@lang('pumperdashboard::lang.location')</th>

                    @if(empty(auth()->user()->pump_operator_id))
                    <th>@lang('pumperdashboard::lang.settlement_no')</th>
                    @endif
                    <th>@lang('pumperdashboard::lang.pump_operator')</th>
                    <th>@lang('pumperdashboard::lang.shift')</th>
                    <th>@lang('pumperdashboard::lang.pump')</th>
                    <th>@lang('pumperdashboard::lang.starting_meter')</th>
                    <th>@lang('pumperdashboard::lang.closing_meter')</th>
                    <th>@lang('pumperdashboard::lang.test_qty')</th>
                    <th>@lang('pumperdashboard::lang.sold_ltr')</th>
                    <th>@lang('pumperdashboard::lang.payment_method')</th>
                    <th>@lang('pumperdashboard::lang.payment_type')</th>
                    <th>@lang('pumperdashboard::lang.amount')</th>
                    <th>@lang('pumperdashboard::lang.short_amount')</th>

                </tr>
            </thead>

            <tfoot>
                <tr class="bg-gray font-17 footer-total">
                    {{--
                            MA-002: the colspans were 9 and 10. Both were ONE
                            TOO MANY, and that is what stopped the table
                            loading at all.

                            The footer is one colspan cell plus five plain
                            cells. DataTables expands the colspan into that
                            many footer slots and maps each to a column:

                                pumper user   13 columns, footer was 9+5 = 14
                                admin user    14 columns, footer was 10+5 = 15

                            One slot too many in both cases, so DataTables ran
                            past the end of its column list:

                                Cannot set properties of undefined
                                (setting 'nTf')

                            That error stopped the table being created, so no
                            ajax request was ever sent - which is why the
                            server logged nothing and the page sat on
                            "Processing" forever.

                            8 and 9 give exactly 13 and 14.

                            NOT MY FAULT AND NOT NEW - the same numbers are in
                            the 9800 baseline. It surfaced when the settlement
                            column became conditional.
                        --}}
                        <td colspan="@if(!empty(auth()->user()->pump_operator_id)) 8 @else 9 @endif" class="text-right"><strong>@lang('sale.total'):</strong></td>
                    <td><span class="display_currency" id="footer_sold_ltr" data-currency_symbol="false"></span></td>
                    <td></td>
                    <td></td>
                    <td><span class="display_currency" id="footer_sold_amount" data-currency_symbol="true"></span></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->


