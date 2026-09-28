@extends('layouts.app')
@section('title', __('petropd::lang.user_activities'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('petropd::lang.user_activities') }}</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-4">
                <div class="form-group">
                    <div class="form-group">
                        {!! Form::label('date_range_filter', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'date_range_filter', 'readonly']); !!}
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('type', __('Type') . ':') !!}
                    {!! Form::select('type', $type, null, ['id' => 'type', 'class' => 'form-control select2', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('user', __('report.users') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-user"></i>
                        </span>
                        {!! Form::select('user', $users, null, ['id' => 'users', 'class' => 'form-control select2', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                </div>
            </div>
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped"
                        id="petro_pd_user_activity_table" width="100%">
                        <thead>
                            <tr>
                                <th>@lang('report.date_time')</th>
                                <th>@lang('report.username')</th>
                                <th>@lang('report.ref_no')</th>
                                <th>@lang('report.subject_id')</th>
                                <th>@lang('report.activity_type')</th>
                                <th>@lang('report.description')</th>
                            </tr>
                        </thead>
                        <tfoot></tfoot>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->
<div class="modal fade view_register" tabindex="-1" role="dialog"
    aria-labelledby="gridSystemModalLabel">
</div>

@endsection

@section('javascript')
<script>
    if ($('#date_range_filter').length == 1) {
        $('#date_range_filter').daterangepicker(dateRangeSettings, function(start, end) {
            $('#date_range_filter').val(
                start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
            );
        });
        $('#date_range_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#date_range_filter').val('');
        });
        $('#date_range_filter')
            .data('daterangepicker')
            .setStartDate(moment().startOf('month'));
        $('#date_range_filter')
            .data('daterangepicker')
            .setEndDate(moment().endOf('month'));
    }
</script>
<script>
    petro_pd_user_activity_table = $('#petro_pd_user_activity_table').DataTable({
        processing: true,
        serverSide: true,
        order: [[0, 'desc']],
        ajax: {
            url: '{{ action("\Modules\PetroPD\Http\Controllers\PetroPDController@getUserActivityReport") }}',
            data: function (d) {
                var user = $('#users').val();
                var type = $('#type').val() || 'All';
                var start = '';
                var end = '';
                start = $('#date_range_filter')
                    .data('daterangepicker')
                    .startDate.format('YYYY-MM-DD');
                end = $('#date_range_filter')
                    .data('daterangepicker')
                    .endDate.format('YYYY-MM-DD');

                d.user = user;
                d.type = type;
                d.startDate = start;
                d.endDate = end;
            },
        },
        columns: [
            { data: 'created_at', name: 'created_at' },
            { data: 'causer_id', name: 'causer_id' },
            { data: 'ref_no', name: 'ref_no' },
            { data: 'subject_id', name: 'subject_id' },
            { data: 'description', name: 'description' },
            { data: 'description_details', name: 'description_details' }
        ],
        buttons: [
            {
                extend: 'csv',
                text: '<i class="fa fa-file"></i> Export to CSV',
                className: 'btn btn-default btn-sm',
                title: 'User Activities - Petro PD',
                exportOptions: {
                    columns: function (idx, data, node) {
                        return $(node).is(':visible') && !$(node).hasClass('notexport') ? true : false;
                    },
                },
            },
            {
                extend: 'excel',
                text: '<i class="fa fa-file-excel-o"></i> Export to Excel',
                className: 'btn btn-default btn-sm',
                title: 'User Activities - Petro PD',
                exportOptions: {
                    columns: function (idx, data, node) {
                        return $(node).is(':visible') && !$(node).hasClass('notexport') ? true : false;
                    },
                },
            },
            {
                extend: 'colvis',
                text: '<i class="fa fa-columns"></i> Column Visibility',
                className: 'btn btn-default btn-sm',
                title: 'User Activities - Petro PD',
                exportOptions: {
                    columns: function (idx, data, node) {
                        return $(node).is(':visible') && !$(node).hasClass('notexport') ? true : false;
                    },
                },
            },
            {
                extend: 'pdf',
                text: '<i class="fa fa-file-pdf-o"></i> Export to PDF',
                className: 'btn btn-default btn-sm',
                title: 'User Activities - Petro PD',
                exportOptions: {
                    columns: function (idx, data, node) {
                        return $(node).is(':visible') && !$(node).hasClass('notexport') ? true : false;
                    },
                },
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print"></i> Print',
                className: 'btn btn-default btn-sm',
                title: 'User Activities - Petro PD',
                exportOptions: {
                    columns: function (idx, data, node) {
                        return $(node).is(':visible') && !$(node).hasClass('notexport') ? true : false;
                    },
                }
            }
        ],
        dom: "<'row'<'col-sm-3'l><'col-sm-6'B><'col-sm-3'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        lengthMenu: [10, 25, 50, 75, 100],
        fnDrawCallback: function (oSettings) {
        },
    });

    $('#users').change(function () {
        petro_pd_user_activity_table.ajax.reload();
    });
    $('#type').change(function () {
        petro_pd_user_activity_table.ajax.reload();
    });
    $('#date_range_filter').on('apply.daterangepicker', function () {
        petro_pd_user_activity_table.ajax.reload();
    });
</script>
@endsection
