@extends('layouts.app')
@section('title', __('membership::lang.membership_activities'))

@section('content')

<section class="content-header">
    <h1>{{ __('membership::lang.membership_activities')}}</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('date_range_filter', __('report.date_range') . ':') !!}
                    {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'date_range_filter', 'readonly']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('type', __('Type') . ':') !!}
                    {!! Form::select('type', $type, null, ['id' => 'type', 'class' => 'form-control select2', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3">
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
                    <table class="table table-bordered table-striped" id="membership_activity_table" width="100%">
                        <thead>
                            <tr>
                                <th>@lang('report.date_time')</th>
                                <th>@lang('report.username')</th>
                                <th>@lang('membership::lang.member_info')</th>
                                <th>@lang('report.activity_type')</th>
                                <th>@lang('report.description')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        // Initialize date range picker
        $('#date_range_filter').daterangepicker({
            singleDatePicker: false,
            showDropdowns: true,
            locale: {
                format: 'YYYY-MM-DD',
            },
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, function(start, end, label) {
            membership_activity_table.ajax.reload();
        });

        // Initialize DataTable
        var membership_activity_table = $('#membership_activity_table').DataTable({
            processing: true,
            serverSide: true,
            order: [[0, 'desc']],
            ajax: {
                url: '{{ url("membership/members/activities") }}',
                data: function(d) {
                    d.user = $('#users').val();
                    d.type = $('#type').find(":selected").val();
                    if ($('#date_range_filter').data('daterangepicker')) {
                        d.startDate = $('#date_range_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                        d.endDate = $('#date_range_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    }
                },
            },
            columns: [
                { data: 'created_at', name: 'created_at' },
                { data: 'causer_id', name: 'causer_id' },
                { data: 'member_info', name: 'member_info', orderable: false, searchable: false },
                { data: 'description', name: 'description' },
                { data: 'description_details', name: 'description_details', orderable: false, searchable: false }
            ],
            buttons: [
                {
                    extend: 'csv',
                    text: '<i class="fa fa-file"></i> Export to CSV',
                    className: 'btn btn-default btn-sm',
                    title: 'Membership Activity Report',
                    exportOptions: {
                        columns: function(idx, data, node) {
                            return $(node).is(':visible') && !$(node).hasClass('notexport') ? true : false;
                        },
                    },
                },
                {
                    extend: 'excel',
                    text: '<i class="fa fa-file-excel-o"></i> Export to Excel',
                    className: 'btn btn-default btn-sm',
                    title: 'Membership Activity Report',
                    exportOptions: {
                        columns: function(idx, data, node) {
                            return $(node).is(':visible') && !$(node).hasClass('notexport') ? true : false;
                        },
                    },
                },
                {
                    extend: 'pdf',
                    text: '<i class="fa fa-file-pdf-o"></i> Export to PDF',
                    className: 'btn btn-default btn-sm',
                    title: 'Membership Activity Report',
                    exportOptions: {
                        columns: function(idx, data, node) {
                            return $(node).is(':visible') && !$(node).hasClass('notexport') ? true : false;
                        },
                    },
                },
                {
                    extend: 'print',
                    text: '<i class="fa fa-print"></i> Print',
                    className: 'btn btn-default btn-sm',
                    title: 'Membership Activity Report',
                    exportOptions: {
                        columns: function(idx, data, node) {
                            return $(node).is(':visible') && !$(node).hasClass('notexport') ? true : false;
                        },
                    }
                }
            ],
            dom: "<'row'<'col-sm-3'l><'col-sm-6'B><'col-sm-3'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            lengthMenu: [10, 25, 50, 75, 100],
        });

        // Filter change handlers
        $('#users, #type').change(function() {
            membership_activity_table.ajax.reload();
        });

        $('#date_range_filter').on('apply.daterangepicker', function() {
            membership_activity_table.ajax.reload();
        });
    });
</script>
@endsection
