<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('petrogeneral::lang.all_your_list_pumps')])
    @slot('tool')
    <div class="box-tools pull-right">
            <button type="button" class="btn  btn-primary btn-modal"
                data-href="{{action('\Modules\PetroGeneral\Http\Controllers\PumpController@create')}}"
                data-container=".fuel_tank_modal">
                <i class="fa fa-plus"></i> @lang('messages.add')</button>
            <a class="btn  btn-danger"
               href="{{action('\Modules\PetroGeneral\Http\Controllers\PumpController@importPumps')}}">
                <i class="fa fa-download "></i> @lang('petrogeneral::lang.import')</a>
        
    </div>
    <div class="clearfix"></div>
    @endslot
    <div class="table-responsive pump-horizontal-scroll">
        
        <style>
/*
 * S681: column widths reduced by 25%, so the table fits without a horizontal
 * scrollbar on a normal screen.
 *
 * The table was pinned to min-width: 1800px, which is what forced the scrollbar
 * regardless of the display. 1800 x 0.75 = 1350px.
 *
 * Two things make that reduction possible rather than merely squeezing the
 * columns:
 *
 *   - Edit and Delete were loose buttons side by side, which made the Action
 *     column very wide. They are now one Action dropdown (see PumpController),
 *     recovering most of the space on its own.
 *   - Headings wrap instead of forcing each column to the width of its longest
 *     word.
 *
 * The wrapper still scrolls if a narrow screen needs it - the scrollbar is no
 * longer guaranteed, just available.
 */
.pump-horizontal-scroll{
    overflow-x:auto !important;
    width:100%;
}

.pump-horizontal-scroll table{
    min-width:1350px !important;
}

#list_pumps_table th{
    white-space:normal;
    vertical-align:middle;
    font-size:12px;
    padding:6px 5px;
}

#list_pumps_table td{
    font-size:12px;
    padding:6px 5px;
    vertical-align:middle;
}

/*
 * The Action menu must escape the scrolling wrapper.
 *
 * overflow-x: auto on the wrapper clips anything overflowing it - including an
 * open dropdown. position: fixed lifts the menu out of that box so it is not
 * cut off at the table edge, which is the same fault seen on the MPCS and
 * Supplier lists.
 */
#list_pumps_table .btn-group{ position:static; }

#list_pumps_table .dropdown-menu{
    position:absolute;
    z-index:1060;
}
</style>

        <table class="table table-bordered table-striped" id="list_pumps_table" width="100%">
            <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.date')</th>
                    <th>@lang('petrogeneral::lang.location')</th>
                    <th>@lang('petrogeneral::lang.transaction_date')</th>
                    <th>@lang('petrogeneral::lang.pump_no')</th>
                    <th>@lang('petrogeneral::lang.pump_name')</th>
                    <th>@lang('petrogeneral::lang.pump_starting_meter')</th>
                    <th>@lang('petrogeneral::lang.pump_current_meter')</th>
                    <th>@lang('petrogeneral::lang.product_name')</th>
                    <th>@lang('petrogeneral::lang.fuel_tank')</th>
                    <th class="notexport">@lang('messages.action')</th>

                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

    <div class="modal fade fuel_tank_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->