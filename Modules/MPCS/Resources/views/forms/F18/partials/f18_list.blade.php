@php
    $business_name = request()->session()->get('business.name');
    $all_locs      = $business_locations->toArray();
    $reports_footer = \App\System::where('key', 'admin_reports_footer')->first();
@endphp

<style>
    /* ── Purple export/print buttons ── */
    #f18-list-table_wrapper .dt-buttons .btn {
        background-color: #6f42c1 !important;
        border-color:     #6f42c1 !important;
        color:            #fff    !important;
        font-size: 12px;
        padding: 4px 10px;
        margin-right: 3px;
        border-radius: 3px;
    }
    #f18-list-table_wrapper .dt-buttons .btn:hover {
        background-color: #5a2d9c !important;
    }

    /* ── Filter labels ── */
    .f18-list-filters label {
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 2px;
        display: block;
    }
    .f18-list-filters .form-control { height: 30px; font-size: 12px; }

    /* ── Page header ── */
    .f18-list-top {
        text-align: center;
        padding: 8px 0 4px;
        border-bottom: 1px solid #e5e5e5;
        margin-bottom: 10px;
    }
    .f18-list-top h4 { font-size: 18px; font-weight: 700; margin: 0 0 2px; }
    .f18-list-top .date-range-label { font-size: 13px; color: #555; }

    /* ── Standard report footer ── */
    #f18_page_footer {
        margin-top: 30px;
        width: 100%;
        text-align: left;
        font-size: 12px;
        color: #333;
        padding: 10px 0 0 10px;
        border-top: 1px solid #eee;
    }
    @media print {
        #f18_page_footer {
            bottom: 0;
            left: 0;
            right: 0;
            margin-top: 0;
            page-break-inside: avoid;
        }
        .no-print { display: none !important; }
    }
</style>

{{-- ── Page header: business name + date range ── --}}
<div class="f18-list-top">
    <h4>{{ $business_name }}</h4>
    <div class="date-range-label" id="f18-list-date-range-label"></div>
</div>

{{-- ── Filters (4 filters only, per spec) ── --}}
<div class="row f18-list-filters no-print" style="padding: 4px 12px 8px;">

    {{-- Date Range --}}
    <div class="col-md-3">
        <div class="form-group">
            <label>Date Range</label>
            <input type="text" id="f18_list_date_range" class="form-control"
                   placeholder="Select date range" readonly>
        </div>
    </div>

    {{-- Business Location -- auto-loads business name as default --}}
    <div class="col-md-3">
        <div class="form-group">
            <label>Business Location</label>
            <select id="f18_filter_location_id" class="form-control" style="width:100%;">
                <option value="" selected>{{ $business_name }}</option>
                @foreach($all_locs as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- F 18 Number (Select2 — type & auto filter) --}}
    <div class="col-md-2">
        <div class="form-group">
            <label>F 18 Number</label>
            <select id="f18_filter_form_no" class="form-control" style="width:100%;">
                <option value="">All</option>
                @foreach($f18_numbers as $no)
                    <option value="{{ $no }}">{{ $no }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- User (Select2 — type & auto filter) --}}
    <div class="col-md-2">
        <div class="form-group">
            <label>User</label>
            <select id="f18_filter_user" class="form-control" style="width:100%;">
                <option value="">All Users</option>
                @foreach($f18_users as $uid => $uname)
                    <option value="{{ $uid }}">{{ $uname }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Apply / Reset --}}
    <div class="col-md-2" style="display:flex; align-items:flex-end; padding-bottom:15px; gap:6px;">
        <button type="button" id="f18_list_filter_btn" class="btn btn-primary btn-sm">
            <i class="fa fa-filter"></i> Filter
        </button>
        <button type="button" id="f18_list_reset_btn" class="btn btn-default btn-sm">
            <i class="fa fa-refresh"></i> Reset
        </button>
    </div>
</div>

{{-- ── DataTable (8 columns) ── --}}
<div style="padding: 0 12px;">
    <table id="f18-list-table" class="table table-bordered table-striped table-hover"
           style="width:100%; font-size:12px;">
        <thead>
            <tr>
                <th class="notexport">Action</th>
                <th>Date</th>
                <th>Business Location</th>
                <th>Issued Location</th>
                <th>Received Location</th>
                <th>F 18 Number</th>
                <th>Products</th>
                <th>User</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

{{-- ── Standard report footer (system standard) ── --}}
@if (!empty($reports_footer) && !empty($reports_footer->value))
    <div id="f18_page_footer">
        {!! $reports_footer->value !!}
    </div>
@endif

<script type="text/javascript">
$(document).ready(function () {

    // ─── Date range picker (same presets as F16A) ──────────────────────────────
    var startDate = moment().startOf('month');
    var endDate   = moment();

    function updateDateLabel(start, end) {
        var label = start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD');
        $('#f18-list-date-range-label').text(label);
    }
    updateDateLabel(startDate, endDate);

    if ($.fn.daterangepicker) {
        var drpOpts = {
            startDate: startDate,
            endDate:   endDate,
            showCustomRangeLabel: true,
            ranges: {
                'Today':     [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Custom Date Range': [moment().startOf('month'), moment().endOf('month')],
            },
            locale: { format: 'YYYY-MM-DD', customRangeLabel: 'Custom Range' }
        };

        var drpCallback = function (start, end, label) {
            if (label === 'Custom Date Range') {
                $('#target_custom_date_input').val('f18_list_date_range');
                
                // Reset to previous value to avoid jumps
                var prevVal = $('#f18_list_date_range').val();
                if (prevVal) {
                    var parts = prevVal.split(' - ');
                    if (parts.length === 2) {
                        $('#f18_list_date_range').data('daterangepicker').setStartDate(moment(parts[0]));
                        $('#f18_list_date_range').data('daterangepicker').setEndDate(moment(parts[1]));
                    }
                }
                
                $('#f18_list_date_range').data('custom-range-active', true);
                $('.custom_date_typing_modal').modal('show');
            } else {
                updateDateLabel(start, end);
            }
        };

        $('#f18_list_date_range').daterangepicker(drpOpts, drpCallback);

        $('#f18_list_date_range').on('apply.daterangepicker', function(ev, picker) {
            if ($(this).data('custom-range-active')) {
                $(this).removeData('custom-range-active');
                return;
            }
            updateDateLabel(picker.startDate, picker.endDate);
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        });

        $('#f18_list_date_range').val(
            startDate.format('YYYY-MM-DD') + ' - ' + endDate.format('YYYY-MM-DD')
        );
    }

    var listSelect2Inited = false;

    function initListSelect2() {
        if (!$.fn.select2 || listSelect2Inited) return;
        $('#f18_filter_form_no').select2({
            width: '100%',
            allowClear: true,
            placeholder: 'All',
            minimumResultsForSearch: 0
        });
        $('#f18_filter_user').select2({
            width: '100%',
            allowClear: true,
            placeholder: 'All Users',
            minimumResultsForSearch: 0
        });
        $('#f18_filter_location_id').select2({
            width: '100%',
            allowClear: true,
            placeholder: '{{ $business_name }}'
        });
        listSelect2Inited = true;
    }

    // ─── Export options: exclude columns with 'notexport' class ────────────────
    var exportOptions = {
        columns: function (idx, data, node) {
            var table = $(node).closest('table').DataTable();
            return table.column(idx).visible() && !$(node).hasClass('notexport');
        }
    };

    // ─── DataTable ─────────────────────────────────────────────────────────────
    var table = $('#f18-list-table').DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: "{{ route('F18.list_data') }}",
            data: function (d) {
                var rangeVal = $('#f18_list_date_range').val() || '';
                var parts    = rangeVal.split(' - ');
                d.start_date  = parts[0] || '';
                d.end_date    = parts[1] || '';
                d.location_id = $('#f18_filter_location_id').val();
                d.form_no     = $('#f18_filter_form_no').val();
                d.user_id     = $('#f18_filter_user').val();
            }
        },
        // System standard layout: Buttons (top center), Entries (left), Search (right)
        dom: '<"row margin-bottom-20 text-center"<"col-sm-12"B><"col-sm-5 text-align-start"f><"col-sm-7"l> r>tip',
        buttons: [
            {
                extend: 'colvis',
                className: 'btn btn-sm btn-default',
            },
            {
                extend: 'csv',
                footer: true,
                text: '<i class="fa fa-file-text-o"></i> CSV',
                className: 'btn btn-sm',
                exportOptions: exportOptions
            },
            {
                extend: 'excel',
                footer: true,
                text: '<i class="fa fa-file-excel-o"></i> Excel',
                className: 'btn btn-sm',
                exportOptions: exportOptions
            },
            {
                extend: 'pdf',
                footer: true,
                text: '<i class="fa fa-file-pdf-o"></i> PDF',
                className: 'btn btn-sm',
                exportOptions: exportOptions
            }
        ],
        // 8 columns matching PDF spec exactly
        columns: [
            { data: 'action',             name: 'action',                   orderable: false, searchable: false },
            { data: 'form_date',          name: 'form_f18_headers.form_date' },
            { data: 'business_name',      name: 'business.name' },
            { data: 'from_location_name', name: 'loc_from.name' },
            { data: 'to_location_name',   name: 'pfx_to.transferred_locations', orderable: false, searchable: false },
            { data: 'form_no',            name: 'form_f18_headers.form_no' },
            { data: 'product_names',      name: 'products.name',            orderable: false },
            { data: 'created_by_name',    name: 'creator.first_name' }
        ],
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
        pageLength: 25,
        order: [[1, 'desc']],
        language: {
            processing: '<i class="fa fa-spinner fa-spin"></i> Loading...',
            emptyTable: 'No F18 forms found for the selected filters.'
        }
    });

    // ─── Filter button ─────────────────────────────────────────────────────────
    $('#f18_list_filter_btn').on('click', function () {
        var rangeVal = $('#f18_list_date_range').val() || '';
        var parts    = rangeVal.split(' - ');
        if (parts[0] && parts[1]) {
            updateDateLabel(moment(parts[0]), moment(parts[1]));
        }
        table.ajax.reload();
    });

    // ─── Reset button ──────────────────────────────────────────────────────────
    $('#f18_list_reset_btn').on('click', function () {
        startDate = moment().startOf('month');
        endDate   = moment();
        if ($('#f18_list_date_range').data('daterangepicker')) {
            $('#f18_list_date_range').data('daterangepicker').setStartDate(startDate);
            $('#f18_list_date_range').data('daterangepicker').setEndDate(endDate);
            $('#f18_list_date_range').val(
                startDate.format('YYYY-MM-DD') + ' - ' + endDate.format('YYYY-MM-DD')
            );
        }
        $('#f18_filter_location_id').val('').trigger('change');
        $('#f18_filter_form_no').val(null).trigger('change');
        $('#f18_filter_user').val(null).trigger('change');
        updateDateLabel(startDate, endDate);
        
        // Clear universal search and column searches, then reload
        table.search('');
        table.columns().search('');
        table.ajax.reload();
    });

    $('a[href="#f18_list_tab"]').on('shown.bs.tab', function () {
        initListSelect2();
        table.ajax.reload(null, false);
    });
});
</script>
