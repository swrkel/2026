<!-- Main content -->

<section class="content">

    <div class="row">

        <div class="col-md-12">

            @component('components.filters', ['title' => __('report.filters')])

            <div class="col-md-3">

                <div class="form-group">

                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}

                    {!! Form::select('report_location_id', $business_locations, null, ['class' => 'form-control

                    select2',

                    'placeholder' => __('petrogeneral::lang.all'), 'id' => 'report_location_id', 'style' => 'width:100%']) !!}

                </div>

            </div>

            <div class="col-md-3">

                <div class="form-group">

                    {!! Form::label('tank_id', __('petrogeneral::lang.tanks') . ':') !!}

                    {!! Form::select('report_tank_id', $tanks, null, ['class' => 'form-control select2', 'placeholder'

                    => __('petrogeneral::lang.all'), 'id' => 'report_tank_id', 'style' => 'width:100%']) !!}

                </div>

            </div>

            <div class="col-md-3">

                <div class="form-group">

                    {!! Form::label('products', __('petrogeneral::lang.products') . ':') !!}

                    {!! Form::select('report_product_id', $products, null, ['class' => 'form-control select2',

                    'placeholder'

                    => __('petrogeneral::lang.all'), 'id' => 'report_product_id', 'style' => 'width:100%']) !!}

                </div>

            </div>

            <div class="col-md-3">

                <div class="form-group">

                    {!! Form::label('daily_report_date_range', __('report.date_range') . ':') !!}

                    {!! Form::text('report_date_range', @format_date('first day of this month') . ' ~ ' .

                    @format_date('last

                    day of this month') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' =>

                    'form-control', 'id' => 'report_date_range', 'readonly']) !!}

                </div>

            </div>

            @endcomponent

        </div>

    </div>



    {{-- Reclaims the empty margins either side - see .pg-dip-report-wide above. --}}
    <div class="pg-dip-report-wide">

    @component('components.widget', ['class' => 'box-primary', 'title' => __('petrogeneral::lang.dip_report')])

    @slot('tool')

    <button type="button" id="addNewDipBtn" class="btn  btn-primary btn-modal pull-right"

    data-href="{{action('\Modules\PetroGeneral\Http\Controllers\DipManagementController@addNewDip')}}"

    data-container=".dip_modal">

    <i class="fa fa-thermometer"></i> @lang('petrogeneral::lang.add_dip')</button>

    

    @endslot

    <div class="col-md-12">

        {{--
            The Date Range / location line is pulled UP into the empty band that
            sat between the "Dip Report" heading and this row.

            The gap came from two things stacked: this row's own margin-top of
            14px, and the h5 beside it, whose default top margin pushed the whole
            row down. Both are removed - the negative margin closes the remaining
            space left by the widget header.

            Font raised by 2 points on both halves of the line.
        --}}
        <div class="row" style="margin-top: -22px;">

            <div class="col-md-5 text-red" style="margin-top: 0; font-size: calc(1em + 2pt);">

                <b>@lang('petrogeneral::lang.date_range'): <span class="report_from_date"></span> @lang('petrogeneral::lang.to') <span

                        class="report_to_date"></span> </b>

            </div>

            <div class="col-md-7">

                <div class="text-center pull-left">

                    <h5 style="font-weight: bold; margin-top: 0; font-size: calc(1em + 2pt);">{{request()->session()->get('business.name')}} <br>

                        <span class="report_location_name">@lang('petrogeneral::lang.all')</span></h5>

                </div>

            </div>

        </div>

        <div class="row" style="margin-top: 15px;">
            <!--<div class="col-md-3"><b>@lang('petrogeneral::lang.tank'): <span class="report_tank"></span></b></div>-->
            <!--<div class="col-md-3"><b>@lang('petrogeneral::lang.product'): <span class="report_product"></span></b></div>-->
            <!--<div class="col-md-2"><b>@lang('petrogeneral::lang.total_loss'): <span class="report_total_loss"></span></b></div>-->
            <div class="col-md-2" style="margin-left: 21cm;color: green;font-weight: bold;font-size: x-large; white-space: nowrap;"><b>Excess:</b> <span class="report_total_excess"></span></div>
            <div class="col-md-2" style="margin-left: 21cm;color: red;font-weight: bold;font-size: x-large; white-space: nowrap;"><b>Shortage:</b> <span class="report_net_difference"></span></div>
        </div>


        <div class="row" style="margin-top: 20px;">

            <div class="table-responsive">

<style>
/*
 |-----------------------------------------------------------------------------
 | Dip Report column widths and alignment.
 |-----------------------------------------------------------------------------
 |
 | The 12 visible columns total 100%, so space taken from one is given to
 | another rather than left as a gap. Starting point was an even 8.33% each.
 |
 | Latest adjustment:
 |   Add Dip No          4.17% -> 8.34%    (+100%)
 |   Daily Report Date   5.83% -> 8.33%    (matched to Date)
 |   Tank               16.66% -> 11.66%   (-30%)
 |   Dip Reading         4.17% -> 8.34%    (+100%)
 |   Dip Qty             4.17% -> 8.34%    (+100%)
 |   Difference Value      15% -> 10.5%    (-30%)
 |
 | Those changes together asked for 5.51% more than a row holds, so Location and
 | Product each give up half of it. They carry free text that wraps cleanly, so
 | they absorb the difference without losing anything - and the row still totals
 | 100%, which is what keeps the columns aligned.
 */
/*
 |-----------------------------------------------------------------------------
 | Use the empty margins either side of the page.
 |-----------------------------------------------------------------------------
 |
 | The report sat inside the theme's centred content column, leaving wide unused
 | bands left and right while the table itself was cramped - Current Qty was
 | breaking into four lines.
 |
 | The negative margins pull the report out into those bands and the padding puts
 | a small gutter back, so the table gains real width without touching the theme's
 | own stylesheet or affecting any other page. Scoped to the dip report wrapper.
 */
.pg-dip-report-wide {
    margin-left: -3%;
    margin-right: -3%;
    padding-left: 8px;
    padding-right: 8px;
}

/* On a small screen there are no side bands to reclaim - leave it alone. */
@media (max-width: 991px) {
    .pg-dip-report-wide {
        margin-left: 0;
        margin-right: 0;
    }
}

/*
 | Calibri for this table.
 |
 | Nothing in this module set a font before - the table simply inherited the
 | theme's. Calibri ships with Windows and Office but not with macOS, Linux or
 | most phones, so it needs fallbacks or those users get the browser default.
 |
 |   Calibri  - the requested font, used wherever it is installed
 |   Carlito  - metric-compatible open clone; identical spacing, common on Linux
 |   Segoe UI - Windows fallback if Calibri is somehow absent
 |   Helvetica Neue / Arial - macOS and general fallback
 |
 | Because they are metric-compatible, the column widths hold whichever is used.
 */
#dip_report_table,
#dip_report_table th,
#dip_report_table td {
    font-family: Calibri, Carlito, "Segoe UI", "Helvetica Neue", Arial, sans-serif;
}

#dip_report_table { table-layout: fixed; width: 100%; }

#dip_report_table .dr-c-action     { width: 4.5% !important; }
#dip_report_table .dr-c-dipno      { width: 6% !important; }   /* +100% */
#dip_report_table .dr-c-date       { width: 10% !important; }
#dip_report_table .dr-c-reportdate { width: 8.5% !important; }   /* matched to Date */
#dip_report_table .dr-c-loc        { width: 8.5% !important; }
#dip_report_table .dr-c-tank       { width: 10.5% !important; }  /* -30% */
#dip_report_table .dr-c-product    { width: 8.5% !important; }
#dip_report_table .dr-c-dipreading { width: 7% !important; }   /* +100% */
#dip_report_table .dr-c-dipqty     { width: 7% !important; }   /* +100% */
#dip_report_table .dr-c-currentqty { width: 8% !important; }
#dip_report_table .dr-c-diff       { width: 9% !important; }
#dip_report_table .dr-c-diffval    { width: 12.5% !important; }   /* -30% */

/*
 | Headings that must break onto two rows.
 |
 | Wrapping is forced by CSS rather than a <br> in the markup, so the headings
 | stay correct in every language - a <br> placed for English would fall in the
 | wrong spot elsewhere.
 */
/*
 | !important throughout this block.
 |
 | DataTables measures the table after it draws and writes an inline
 | style="width: NNNpx" onto every <th>. An inline style beats a class, so the
 | widths and the wrapping set here were being discarded the moment the table
 | rendered.
 */

/*
 |-----------------------------------------------------------------------------
 | Headings.
 |-----------------------------------------------------------------------------
 |
 | Three problems fixed together:
 |
 |  1. ACTION and ADD DIP NO were unreadable. DataTables draws its sort arrows in
 |     the top-right of each heading cell, and the right-aligned heading text ran
 |     straight underneath them. Headings are centred now, with padding on both
 |     sides reserving room for the arrow, so text and arrow never overlap.
 |
 |  2. DIFFERENCE VALUE was cut off mid-word. Every heading now wraps, so a long
 |     one uses a second line instead of being clipped.
 |
 |  3. Headings sat on the bottom edge while wrapped ones rose above them.
 |     vertical-align: middle keeps the whole row visually level.
 */
#dip_report_table th,
#dip_report_table th.dr-wrap {
    text-align: center !important;
    vertical-align: middle !important;
    white-space: normal !important;
    word-break: normal !important;
    overflow-wrap: break-word !important;
    line-height: 1.3 !important;
    font-size: 9px;    /* headings one point smaller than the data */
    padding: 8px 16px 8px 8px !important;   /* right padding clears the sort arrow */
    height: 44px;
}

/* Keep the sort arrows off the text rather than on top of it. */
#dip_report_table thead th.sorting:after,
#dip_report_table thead th.sorting_asc:after,
#dip_report_table thead th.sorting_desc:after,
#dip_report_table thead th.sorting:before,
#dip_report_table thead th.sorting_asc:before,
#dip_report_table thead th.sorting_desc:before {
    right: 3px !important;
    opacity: 0.4;
}

/*
 | Numeric columns right aligned - headings and cells together, so the heading
 | sits over its own figures.
 |
 | Daily Report Date is included in the "show the data" fix: the cell was clipping
 | its date. It wraps instead of being cut off.
 */
/*
 | Figures right aligned so the decimal points line up down each column.
 |
 | Only the CELLS - the headings above stay centred. Right-aligning the headings
 | is what pushed their text under the sort arrows in the first place.
 */
#dip_report_table td.dr-c-dipno,
#dip_report_table td.dr-c-dipreading,
#dip_report_table td.dr-c-dipqty,
#dip_report_table td.dr-c-currentqty,
#dip_report_table td.dr-c-diff,
#dip_report_table td.dr-c-diffval {
    text-align: right !important;
}

/* Dates and the action button read better centred. */
#dip_report_table td.dr-c-action,
#dip_report_table td.dr-c-date,
#dip_report_table td.dr-c-reportdate {
    text-align: center !important;
}

/*
 | Dates must not break mid-year - "11/03/2 026" was the result of wrapping
 | inside the value. They keep their column and stay on one line.
 */
#dip_report_table td.dr-c-date,
#dip_report_table td.dr-c-reportdate {
    white-space: nowrap !important;
}

#dip_report_table td {
    white-space: normal !important;
    word-break: break-word !important;
    font-size: 10px;   /* reduced a further point - was 11px, originally 12px */
}

/* Totals row follows the same alignment as the figures above it. */
#dip_report_table tfoot td { text-align: right; font-weight: bold; }
</style>

                <table class="table table-bordered table-striped" id="dip_report_table">

                    <thead>

                        <tr>
                            
                            <th class="dr-c-action">@lang('petrogeneral::lang.action')</th>

                            <th class="dr-c-dipno dr-wrap">@lang('petrogeneral::lang.add_dip_no')</th>
                            
                            <th class="dr-c-date">@lang('petrogeneral::lang.date')</th>

                            <th class="dr-c-reportdate dr-wrap">@lang('petrogeneral::lang.daily_report_date')</th>

                            <th class="dr-c-loc">@lang('petrogeneral::lang.location')</th>

                            <th class="dr-c-tank">@lang('petrogeneral::lang.tank')</th>

                            <th class="dr-c-product">@lang('petrogeneral::lang.product')</th>

                            <th class="dr-c-dipreading dr-wrap">@lang('petrogeneral::lang.dip_reading')</th>

                            <th class="dr-c-dipqty">@lang('petrogeneral::lang.dip_qty')</th>

                            <th class="dr-c-currentqty dr-wrap">@lang('petrogeneral::lang.current_qty')</th>

                            <th class="dr-c-diff">@lang('petrogeneral::lang.differnece')</th>
                            
                            <th class="dr-c-diffval">@lang('petrogeneral::lang.difference_value')</th>

                        </tr>

                    </thead>

                    
                    <tfoot>                        

                        <tr class="footer_total">

                            <td colspan="10" style="text-align: right; font-weight: bold;">@lang('petrogeneral::lang.total')

                                :</td>

                            <td style="text-align: left; font-weight: bold;" class="difference_total display_currency"></td>

                            <td style="text-align: left; font-weight: bold;" class="difference_value_total display_currency final-total"></td>

                        </tr>                        

                    </tfoot>
                   

                </table>

            </div>

        </div>

    </div>

    @endcomponent

    </div>{{-- /.pg-dip-report-wide --}}



    <div class="modal fade settlement_modal" role="dialog" aria-labelledby="gridSystemModalLabel">

    </div>

</section>

<!-- /.content -->