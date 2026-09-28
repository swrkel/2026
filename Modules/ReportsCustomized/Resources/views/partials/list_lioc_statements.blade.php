@component('components.filters', ['title' => __('report.filters')])
<div class="row">
    <div class="col-md-6 col-sm-12">
        <div class="form-group">
            {!! Form::label('lioc_list_date_range', __('report.date_range') . ':') !!}
            {!! Form::text('lioc_list_date_range', null, [
                'placeholder' => __('lang_v1.select_a_date_range'),
                'class'       => 'form-control',
                'id'          => 'lioc_list_date_range',
                'readonly'
            ]) !!}
        </div>
    </div>
    <div class="col-md-6 col-sm-12">
        <div class="form-group">
            {!! Form::label('lioc_list_statement_no', 'Bill ref / Statement:') !!}
            {!! Form::select('lioc_list_statement_no', $statement_nos ?? ['' => __('lang_v1.all')], null, [
                'class'       => 'form-control select2',
                'id'          => 'lioc_list_statement_no',
                'style'       => 'width:100%',
            ]) !!}
        </div>
    </div>
</div>
@endcomponent

@component('components.widget', ['class' => 'box-primary'])
<div class="row" style="margin-top: 10px;">
    <div class="col-md-12">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="lioc_list_table" style="width:100%;">
                <thead>
                    <tr>
                        <th>@lang('messages.action')</th>
                        <th>Saved at</th>
                        <th>Period</th>
                        <th>Bill ref</th>
                        <th>Total amount</th>
                        <th>Lines</th>
                        <th>Added by</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endcomponent

<script>
$(document).ready(function () {

    if ($.fn.select2) {
        $('#lioc_list_statement_no').select2({ width: '100%', allowClear: true, placeholder: @json(__('lang_v1.all')) });
    }

    $('#lioc_list_date_range').daterangepicker(
        $.extend(true, {}, dateRangeSettings, {
            startDate: moment().startOf('month'),
            endDate:   moment().endOf('month')
        }),
        function (start, end) {
            $('#lioc_list_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            reloadLiocListTable();
        }
    );
    $('#lioc_list_date_range').val(
        moment().startOf('month').format(moment_date_format) + ' ~ ' +
        moment().endOf('month').format(moment_date_format)
    );

    var lioc_list_table = $('#lioc_list_table').DataTable({
        processing: true,
        serverSide: true,
        columnDefs: [
            { targets: 0, orderable: false, searchable: false, className: 'notexport text-nowrap' }
        ],
        dom: '<"row"<"col-sm-12 text-center"B>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-5"i><"col-sm-7"p>>',
        buttons: [
            {
                extend: 'csv',
                text: '<i class="fa fa-file"></i> Export to CSV',
                className: 'btn btn-default btn-sm',
                title: 'LIOC Statements',
                exportOptions: { columns: ':visible:not(.notexport)' }
            },
            {
                extend: 'excel',
                text: '<i class="fa fa-file-excel-o"></i> Export to Excel',
                className: 'btn btn-default btn-sm',
                title: 'LIOC Statements',
                exportOptions: { columns: ':visible:not(.notexport)' }
            },
            {
                extend: 'colvis',
                text: '<i class="fa fa-columns"></i> Column Visibility',
                className: 'btn btn-default btn-sm'
            },
            {
                extend: 'pdf',
                text: '<i class="fa fa-file-pdf-o"></i> Export to PDF',
                className: 'btn btn-default btn-sm',
                title: 'LIOC Statements',
                exportOptions: { columns: ':visible:not(.notexport)' }
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print"></i> Print',
                className: 'btn btn-default btn-sm',
                title: 'LIOC Statements',
                exportOptions: { columns: ':visible:not(.notexport)' }
            }
        ],
        ajax: {
            url: '{{ route("reportscustomized.list-statements") }}',
            data: function (d) {
                var dateRange = $('#lioc_list_date_range').val().split(' ~ ');
                d.start_date         = dateRange[0] ? moment(dateRange[0], moment_date_format).format('YYYY-MM-DD') : '';
                d.end_date           = dateRange[1] ? moment(dateRange[1], moment_date_format).format('YYYY-MM-DD') : '';
                d.statement_no       = $('#lioc_list_statement_no').val();
            }
        },
        columns: [
            { data: 'action',           name: 'action',           orderable: false, searchable: false },
            { data: 'created_at',       name: 'saved_lioc_statements.created_at' },
            { data: 'period_display',   name: 'saved_lioc_statements.period_display' },
            { data: 'bill_ref_display', name: 'saved_lioc_statements.bill_ref_display' },
            { data: 'total_amount',     name: 'saved_lioc_statements.total_amount' },
            { data: 'line_count',       name: 'saved_lioc_statements.line_count' },
            { data: 'added_by',         name: 'added_by', orderable: false }
        ]
    });

    function reloadLiocListTable() {
        lioc_list_table.ajax.reload();
    }

    $('#lioc_list_statement_no').on('change', function () {
        reloadLiocListTable();
    });

    $('#lioc_list_date_range').on('apply.daterangepicker', function () {
        reloadLiocListTable();
    });

    window.reloadLiocSavedList = reloadLiocListTable;
});
</script>
