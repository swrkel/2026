@extends('distribution::layouts.app')
@section('title', __('distribution::lang.vehicle_meters'))

<style>
    .select2 {
        width: 100% !important;
    }
</style>
@section('content')
    <section class="content">
        <div class="row">
            <div class="col-md-12">

                {{-- Filters --}}
                @component('distribution::components.filters', ['title' => __('report.filters')])
                    <div class="row">
                        <div class="col-md-2">
                            {!! Form::label('vehicle_id', 'Vehicle') !!}
                            {!! Form::select('vehicle_id', $vehicles ?? [], null, [
                                'class' => 'form-control select2',
                                'placeholder' => 'All',
                            ]) !!}
                        </div>
                        <div class="col-md-2">
                            {!! Form::label('daily_summary_sheet_no', 'Daily Summary Sheet No') !!}
                            {!! Form::text('daily_summary_sheet_no', null, ['class' => 'form-control', 'placeholder' => 'All']) !!}
                        </div>
                        <div class="col-md-2">
                            {!! Form::label('sales_rep_id', 'Sales Rep') !!}
                            {!! Form::select('sales_rep_id', $sales_reps ?? [], null, [
                                'class' => 'form-control select2',
                                'placeholder' => 'All',
                            ]) !!}
                        </div>
                        <div class="col-md-2">
                            {!! Form::label('route_id', 'Route') !!}
                            {!! Form::select('route_id', $routes ?? [], null, ['class' => 'form-control select2', 'placeholder' => 'All']) !!}
                        </div>
                        <div class="col-md-2">
                            {!! Form::label('added_by', 'Added By') !!}
                            {!! Form::select('added_by', $users ?? [], null, ['class' => 'form-control select2', 'placeholder' => 'All']) !!}
                        </div>
                        <div class="col-md-2">
                            {!! Form::label('date_range', 'Date Range') !!}
                            {!! Form::text('date_range', null, [
                                'class' => 'form-control',
                                'readonly',
                                'id' => 'vehicle_meters_date_range',
                                'placeholder' => 'All',
                            ]) !!}
                        </div>
                    </div>
                @endcomponent


                {{-- Table --}}
                @component('distribution::components.widget', ['class' => 'box-primary', 'title' => 'Vehicle Meters'])
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="vehicle_meters_table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>Date</th>
                                    <th>Vehicle Number</th>
                                    <th>Daily Summary Sheet No.</th>
                                    <th>Starting Meter</th>
                                    <th>Closing Meter</th>
                                    <th>Sales Rep</th>
                                    <th>Route</th>
                                    <th>Added By</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                @endcomponent

            </div>
        </div>
    </section>

    {{-- Modal to show Daily Summary Sheet --}}
    <div class="modal fade" id="dailySummaryModal" tabindex="-1" role="dialog"></div>
@endsection
@section('javascript')

    <script>
        $(document).ready(function() {
            let vehicleMetersTable = $('#vehicle_meters_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/distribution/vehicle-meters',
                    data: function(d) {
                        d.vehicle_id = $('select[name=vehicle_id]').val();
                        d.daily_summary_sheet_no = $('input[name=daily_summary_sheet_no]')
                            .val();
                        d.sales_rep_id = $('select[name=sales_rep_id]').val();
                        d.route_id = $('select[name=route_id]').val();
                        d.added_by = $('select[name=added_by]').val();
                        d.date_range = $('#vehicle_meters_date_range').val();
                    }
                },
                columns: [{
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'date'
                    },
                    {
                        data: 'vehicle_no'
                    },
                    {
                        data: 'daily_summary_sheet_no'
                    },
                    {
                        data: 'starting_meter'
                    },
                    {
                        data: 'closing_meter'
                    },
                    {
                        data: 'sales_rep'
                    },
                    {
                        data: 'route'
                    },
                    {
                        data: 'added_by_name'
                    }
                ]
            });

            // Reload table on filter change
            $('.select2, #vehicle_meters_date_range, input[name=daily_summary_sheet_no]').on(
                'change keyup',
                function() {
                    vehicleMetersTable.ajax.reload();
                });

            // View Daily Summary modal
            $(document).on('click', '.view_summary', function() {
                let id = $(this).data('id');
                $.ajax({
                    url: '/distribution/vehicle-meters/' + id + '/view',
                    method: 'GET',
                    success: function(result) {
                        $('#dailySummaryModal').html(result).modal('show');
                    }
                });
            });

            // Initialize Select2
            $('.select2').select2({
                width: '100%'
            });

            // Date Range Picker

            if ($('#vehicle_meters_date_range').length == 1) {
                $('#vehicle_meters_date_range').daterangepicker(dateRangeSettings, function(start, end) {
                    $('#vehicle_meters_date_range').val(
                        start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                    );
                    vehicleMetersTable.ajax.reload();
                });

                $('#vehicle_meters_date_range').on('cancel.daterangepicker', function(ev, picker) {
                    $('#vehicle_meters_date_range').val('');
                    vehicleMetersTable.ajax.reload();
                });

                $('#vehicle_meters_date_range').data('daterangepicker').setStartDate(moment().startOf('month'));
                $('#vehicle_meters_date_range').data('daterangepicker').setEndDate(moment().endOf('month'));
            }
        });
    </script>
@endsection
