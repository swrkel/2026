@extends('layouts.app')

@section('title', __('petrogeneral::lang.tank_management'))



@section('content')

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('petrogeneral::lang.petro')</a></li>
                    <li><span>@lang( 'petrogeneral::lang.list_tank_transfer')</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>


<!-- Main content -->
<section class="content main-content-inner">
    @if(!empty($message)) {!! $message !!} @endif
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            {{-- IS2001: contain the filter columns deterministically.

                 The panel collapsed to zero height and its contents spilled out
                 below it, where the next box covered them - which is why only the
                 labels showed and every input looked missing.

                 Earlier attempts used a trailing clearfix (IS1996) and then a .row
                 wrapper (IS1998). Both depend on the theme's own CSS behaving, and
                 this codebase's global stylesheets override with !important
                 liberally. overflow:hidden establishes a block formatting context,
                 which makes this element contain its floated children as a matter
                 of CSS layout rules rather than of stylesheet cooperation. It
                 cannot be overridden away by a later rule the way a clearfix can.

                 Safe for the dropdowns: select2 and daterangepicker both append
                 their popups to <body>, so nothing here clips them. --}}
            <div class="row" style="overflow: hidden !important; width: 100% !important; margin-left: 0 !important; margin-right: 0 !important;">
                
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', @format_date('first day of this month') . ' ~ ' . @format_date('last day of this month') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'date_range', 'readonly']); !!}
                    </div>
                </div>
                
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('petrogeneral::lang.location').':') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('petrogeneral::lang.all')]); !!}
                    </div>
                </div>
                
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('from_tank', __('petrogeneral::lang.from_tank').':') !!}
                        {!! Form::select('from_tank', $tank_numbers, null, ['class' => 'form-control select2', 'placeholder' => __('petrogeneral::lang.all')]); !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('to_tank', __('petrogeneral::lang.to_tank').':') !!}
                        {!! Form::select('to_tank', $tank_numbers, null, ['class' => 'form-control select2', 'placeholder' => __('petrogeneral::lang.all')]); !!}
                    </div>
                </div>
                
                <div class="clearfix"></div>
                
                 <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('product_id', __('petrogeneral::lang.product_id').':') !!}
                        {!! Form::select('product_id', $products, null, ['class' => 'form-control select2', 'placeholder' => __('petrogeneral::lang.all')]); !!}
                    </div>
                </div>

                {{--
                    IS1996: the filter panel collapsed and its contents spilled out
                    underneath, where the next box overlapped them - which is why
                    only the four labels were visible and the inputs appeared to be
                    missing entirely.

                    Every child of this component is a floated Bootstrap column and
                    there was no clearing element after the last one, so the panel
                    computed a height of zero. Same failure, and same remedy, as the
                    settlement clone-container in parcel 3-7.

                    The clearfix at the top of this block only clears the first row;
                    this one closes the panel after the final column.
                --}}
                <div class="clearfix"></div>
            </div>
            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary'])
    @slot('tool')
    
    <div class="box-tools pull-right">
        <button type="button" class="btn btn-primary btn-modal add_fuel_tank"
            data-href="{{ route('petrogeneral.tank_transfer.create') }}"
            data-container=".fuel_tank_modal">
            <i class="fa fa-plus"></i> @lang('messages.add')</button>
    </div>
    
    @endslot
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="tank_transfers_table">
            <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.date')</th>
                    <th>@lang('petrogeneral::lang.location')</th>
                    <th>@lang('petrogeneral::lang.transfer_no')</th>
                    <th>@lang('petrogeneral::lang.from_tank')</th>
                    <th>@lang('petrogeneral::lang.from_qty')</th>
                    <th>@lang('petrogeneral::lang.to_tank')</th>
                    <th>@lang('petrogeneral::lang.to_qty')</th>
                    <th>@lang('petrogeneral::lang.product')</th>
                    <th>@lang('petrogeneral::lang.transfer_qty')</th>
                    <th>@lang('petrogeneral::lang.user_added')</th>
                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

    <div class="modal fade fuel_tank_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
</section>
<!-- /.content -->

@endsection

@section('javascript')

{{-- IS2001: the save result. store() redirects back here with 'status' on
     success and on every refusal; $errors covers a validation failure that
     reaches Laravel's own handler. --}}
{{-- The flashed status is now shown on every Petro General page by
     \Modules\PetroGeneral\Http\Middleware\RenderPetroGeneralStatusMessage, so
     the block that used to render it here has been removed - keeping it would
     toast the same message twice, once from this view and once from the
     middleware's injected script.

     Same change already made in petro_settings/index.blade.php.

     $errors below is NOT covered by the middleware, which reads only
     session('status'). TankTransferController@store catches ValidationException
     and flashes through 'status', so this is a fallback for any validation
     failure that reaches Laravel's own handler instead. --}}

@if($errors->any())
<script type="text/javascript">
    $(document).ready(function () {
        toastr.error({!! json_encode(implode(' ', $errors->all())) !!});
    });
</script>
@endif

<script type="text/javascript">
    /*
     * IS2001: rebuilt so the grid cannot fail to initialise.
     *
     * THE FAULT
     *   The ajax data callback read
     *       $('input#date_range').data('daterangepicker').startDate.format(...)
     *   with no guard. DataTables runs that callback during its FIRST draw, as
     *   part of .DataTable(). If the daterangepicker is not on the element yet,
     *   .data(...) is undefined, reading .startDate throws, and initialisation
     *   aborts - leaving the bare <table> with no search box, no pagination and
     *   no "No data available" row. That is what the screenshots show, and it is
     *   also why tank_transfers_table.column(1).visible(false) never took effect
     *   and the Location column stayed visible.
     *
     *   The two initialisers also sat in separate <script> blocks, one inside
     *   document.ready and one not, so their order depended on parse timing.
     *
     * THE FIX
     *   One block. The daterangepicker is created FIRST, the grid second, and
     *   every read of the picker is guarded so the callback cannot throw.
     *
     *   The Location column is no longer hidden - the requirement lists LOCATION
     *   as a column of this table.
     */

    /*
     * IS2001: never raise a browser alert for a tank transfer grid.
     *
     * DataTables' default error mode is alert(). With serverSide:true it also
     * ABORTS the request in flight whenever a second one starts, and an aborted
     * XHR has an empty responseText - so a perfectly healthy pair of requests
     * produces "Invalid JSON response" in a modal while the surviving request
     * quietly delivers the data. That is why the grid fills in and the dialog
     * appears at the same time, and why the server logs stayed clean throughout:
     * nothing ever failed.
     *
     * 'none' routes the condition to DataTables' own error event instead. The
     * cause is written to the console for a developer and the operator sees a
     * grid, not a dialog quoting a URL at them.
     *
     * This is the pattern already used in this codebase by
     * Modules/CustomerStatements/Resources/views/partials/s633_permission_ui.blade.php
     * for the same reason.
     */
    if ($.fn && $.fn.dataTable && $.fn.dataTable.ext) {
        $.fn.dataTable.ext.errMode = 'none';
    }

    $(document).on('error.dt', function (event, settings, techNote, message) {
        if (window.console && console.warn) {
            console.warn('Tank transfers: a grid reported an error.', {
                table: settings && settings.nTable ? settings.nTable.id : null,
                techNote: techNote,
                message: message
            });
        }
    });

    $(document).ready(function () {

        var tank_transfers_table = null;

        /*
         * IS2001: suppress reloads until the first draw has finished.
         *
         * With serverSide:true, DataTables ABORTS the request in flight when a
         * second one starts. An aborted XHR carries an empty responseText, the
         * parse fails, and DataTables reports "Invalid JSON response" even though
         * both requests reached the server and succeeded - which is exactly what
         * the tenant log showed: draw=1 and draw=2 milliseconds apart with no
         * error logged for either.
         *
         * The second request came from this page's own setup: the filters are
         * bound to change, and select2 and the daterangepicker fire change while
         * initialising, right after the grid is built.
         */
        var grid_ready = false;
        var reload_timer = null;

        function reloadTankTransfers() {
            if (!grid_ready || tank_transfers_table === null) {
                return;
            }

            clearTimeout(reload_timer);
            reload_timer = setTimeout(function () {
                tank_transfers_table.ajax.reload(null, false);
            }, 250);
        }

        function tankTransferDateRange() {
            var $input = $('#date_range');

            if (!$input.length || !$input.val()) {
                return null;
            }

            var picker = $input.data('daterangepicker');

            if (!picker || !picker.startDate || !picker.endDate) {
                return null;
            }

            return {
                start: picker.startDate.format('YYYY-MM-DD'),
                end: picker.endDate.format('YYYY-MM-DD')
            };
        }

        if ($('#date_range').length === 1) {
            $('#date_range').daterangepicker(dateRangeSettings, function (start, end) {
                $('#date_range').val(
                    start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                );
                reloadTankTransfers();
            });

            $('#date_range').on('cancel.daterangepicker', function () {
                $('#date_range').val('');
                reloadTankTransfers();
            });
        }

        tank_transfers_table = $('#tank_transfers_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[0, 'desc']],
            initComplete: function () {
                grid_ready = true;
            },
            ajax: {
                url: '{{ route('petrogeneral.tank_transfer.index') }}',
                data: function (d) {
                    d.location_id = $('#location_id').val();
                    d.product_id = $('#product_id').val();
                    d.from_tank = $('#from_tank').val();
                    d.to_tank = $('#to_tank').val();

                    var range = tankTransferDateRange();

                    if (range !== null) {
                        d.start_date = range.start;
                        d.end_date = range.end;
                    }
                }
            },
            columns: [
                { data: 'date', name: 'date' },
                { data: 'location_name', name: 'business_locations.name' },
                { data: 'transfer_no', name: 'transfer_no' },
                { data: 't_from_name', name: 't_from.fuel_tank_number' },
                { data: 'from_qty', name: 'from_qty', searchable: false },
                { data: 't_to_name', name: 't_to.fuel_tank_number' },
                { data: 'to_qty', name: 'to_qty', searchable: false },
                { data: 'product_name', name: 'products.name' },
                { data: 'quantity', name: 'quantity' },
                { data: 'user_created', name: 'users.username' }
            ]
        });

        $('#date_range, #location_id, #from_tank, #to_tank, #product_id').on('change', reloadTankTransfers);
    });
</script>

@endsection