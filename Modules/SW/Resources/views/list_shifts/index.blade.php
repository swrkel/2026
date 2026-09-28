@extends('sw::layouts.app', [
    'title' => __('sw::lang.list_sw_shifts'),
    'heading' => __('sw::lang.list_sw_shifts'),
    'subheading' => __('sw::lang.list_sw_shifts_subtitle'),
])

@section('sw_content')

<div class="sw-card sw-list-filter-card">
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label>Business Location</label>
                <select id="sw_ls_location" class="form-control sw-list-filter-select" style="width:100%">
                    @foreach($business_locations as $id => $name)
                        <option value="{{ $id }}" @selected((int) $default_location === (int) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Date Range</label>
                <input type="text" id="sw_ls_date_range" class="form-control" readonly placeholder="All Dates">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Operator</label>
                <select id="sw_ls_operator" class="form-control sw-list-filter-select" style="width:100%">
                    <option value="">All</option>
                    @foreach($operators as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Shift No</label>
                <select id="sw_ls_shift" class="form-control sw-list-filter-select" style="width:100%">
                    <option value="">All</option>
                    @foreach($shifts as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label>Pump Nos</label>
                <select id="sw_ls_pump" class="form-control sw-list-filter-select" style="width:100%">
                    <option value="">All</option>
                    @foreach($pumps as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Payment Method</label>
                <select id="sw_ls_payment_method" class="form-control sw-list-filter-select" style="width:100%">
                    <option value="">All</option>
                    @foreach($payment_methods as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Cheque No</label>
                <select id="sw_ls_cheque_no" class="form-control sw-list-filter-select" style="width:100%">
                    <option value="">All</option>
                    @foreach($cheque_numbers as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Settlement No</label>
                <select id="sw_ls_settlement_no" class="form-control sw-list-filter-select" style="width:100%">
                    <option value="">All</option>
                    @foreach($settlement_numbers as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                </select>
            </div>
        </div>
    </div>
</div>

<div id="sw_ls_error" class="alert alert-danger" style="display:none;margin-bottom:12px"></div>

<div class="sw-card">
    <div class="sw-report-heading">
        <div><strong>Business Location:</strong> <span id="sw_ls_meta_location">—</span></div>
        <div><strong>Date Range:</strong> <span id="sw_ls_meta_date">All Dates</span></div>
    </div>

    <div id="sw_ls_toolbar" class="sw-function-toolbar">
        <div id="sw_ls_dt_buttons"></div>
        <div class="sw-toolbar-spacer"></div>
        <label class="sw-inline-control">Rows
            <select id="sw_ls_page_length" class="form-control input-sm">
                <option value="10">10</option><option value="25" selected>25</option><option value="50">50</option>
                <option value="100">100</option><option value="-1">All</option>
            </select>
        </label>
        <label class="sw-inline-control sw-search-control">Search
            <input type="search" id="sw_ls_search" class="form-control input-sm" placeholder="Universal Search">
        </label>
    </div>

    <div class="table-responsive sw-list-shifts-table-wrap">
        <table class="table table-bordered table-striped" id="sw_list_shifts_table" style="width:100%">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Location</th>
                    <th>Date</th>
                    <th>Operator</th>
                    <th>Shift<br>No</th>
                    <th>Pump<br>Nos</th>
                    <th class="text-right">Cash</th>
                    <th class="text-right">Cards</th>
                    <th class="text-right">Credit<br>Sales</th>
                    <th class="text-right">Cheques</th>
                    <th class="text-right">Total<br>Amount</th>
                    <th>Settlement<br>No</th>
                    <th class="text-right">Settlement<br>Amount</th>
                </tr>
            </thead>
            <tfoot>
                <tr class="bg-gray footer-total">
                    <th><strong>Total</strong></th>
                    <th></th><th></th><th></th><th></th><th></th>
                    <th class="text-right" id="sw_ls_total_cash"></th>
                    <th class="text-right" id="sw_ls_total_cards"></th>
                    <th class="text-right" id="sw_ls_total_credit"></th>
                    <th class="text-right" id="sw_ls_total_cheques"></th>
                    <th class="text-right" id="sw_ls_total_amount"></th>
                    <th></th>
                    <th class="text-right" id="sw_ls_total_settlement"></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection

@push('css')
<style>
.sw-list-filter-card .form-group{margin-bottom:12px}
.sw-report-heading{display:flex;gap:30px;flex-wrap:wrap;align-items:center;padding:0 0 12px;font-size:14px;color:#172033}
.sw-function-toolbar{display:flex;align-items:center;gap:7px;flex-wrap:wrap;border-top:1px solid #edf1f5;border-bottom:1px solid #edf1f5;padding:10px 0;margin-bottom:12px}
.sw-function-toolbar .btn{color:#fff;font-weight:600}
.sw-function-toolbar .btn-default{color:#333}
.sw-toolbar-spacer{flex:1 1 20px}
.sw-inline-control{display:flex;align-items:center;gap:6px;margin:0;font-size:12px;white-space:nowrap}
.sw-inline-control select{width:78px}
.sw-search-control input{width:190px}
#sw_list_shifts_table th,#sw_list_shifts_table td{vertical-align:middle;font-size:10.5px;line-height:1.25;padding:8.75px 5px!important;white-space:normal;word-break:normal}
#sw_list_shifts_table th{font-family:Calibri, Arial, sans-serif;font-size:14px;text-align:center;line-height:1.15;white-space:normal!important}
#sw_list_shifts_table .text-right{font-variant-numeric:tabular-nums;white-space:nowrap}
/* IS2245: DataTables must never hide a shift-list column. Fit the complete
   table on normal desktop screens and provide a horizontal fallback only when
   the viewport is genuinely too narrow. */
.sw-list-shifts-table-wrap{width:100%;max-width:100%;overflow-x:auto!important;overflow-y:visible!important}
.sw-shift-action-floating{
    position:absolute!important;
    z-index:2147483000!important;
    display:block!important;
    min-width:132px;
    margin:0!important;
    box-shadow:0 8px 24px rgba(15,23,42,.18);
}
.sw-shift-action-floating>li>a{padding:8px 14px;white-space:nowrap}
#sw_list_shifts_table_wrapper{width:100%;max-width:100%}
#sw_list_shifts_table{width:100%!important;table-layout:fixed}
#sw_list_shifts_table_wrapper table.dataTable{width:100%!important}
#sw_list_shifts_table th:nth-child(1){width:6.65%}
#sw_list_shifts_table th:nth-child(2){width:8%}
/* IS2249: Date is 5% wider than its previous 7% allocation. */
#sw_list_shifts_table th:nth-child(3){width:7.35%}
#sw_list_shifts_table th:nth-child(4){width:8%}
#sw_list_shifts_table th:nth-child(5){width:7%}
#sw_list_shifts_table th:nth-child(6){width:7%}
#sw_list_shifts_table th:nth-child(7),#sw_list_shifts_table th:nth-child(8),#sw_list_shifts_table th:nth-child(9),#sw_list_shifts_table th:nth-child(10){width:7%}
#sw_list_shifts_table th:nth-child(11){width:9%}
#sw_list_shifts_table th:nth-child(12){width:9%}
#sw_list_shifts_table th:nth-child(13){width:10%}
@media(max-width:1199px){#sw_list_shifts_table{min-width:1180px!important;table-layout:auto}#sw_list_shifts_table th,#sw_list_shifts_table td{white-space:nowrap}}
#sw_ls_dt_buttons .dt-buttons{display:flex;gap:7px;flex-wrap:wrap;margin:0}
#sw_ls_dt_buttons .dt-button{margin:0!important;border-radius:4px!important}
#sw_list_shifts_table_wrapper .dataTables_filter,#sw_list_shifts_table_wrapper .dataTables_length{display:none}
@media(max-width:767px){.sw-toolbar-spacer{display:none}.sw-inline-control,.sw-search-control{width:100%}.sw-search-control input{width:100%}}
</style>
@endpush

@push('javascript')
<script>
$(function () {
    var table;
    var swStartDate = '';
    var swEndDate = '';
    var exportColumns = [1,2,3,4,5,6,7,8,9,10,11,12];

    function initSelect2() {
        if ($.fn.select2) {
            $('.sw-list-filter-select').each(function () {
                var $el = $(this);
                if (!$el.hasClass('select2-hidden-accessible')) {
                    $el.select2({width:'100%'});
                }
            });
        }
    }

    function initDateRange() {
        var $input = $('#sw_ls_date_range');
        if (!$.fn.daterangepicker) return;

        var settings = (typeof dateRangeSettings !== 'undefined') ? $.extend(true, {}, dateRangeSettings) : {};
        settings.autoUpdateInput = false;
        if (!settings.locale) settings.locale = {};
        settings.locale.cancelLabel = settings.locale.cancelLabel || 'Clear';

        $input.daterangepicker(settings);
        $input.on('apply.daterangepicker', function (ev, picker) {
            var fmt = (typeof moment_date_format !== 'undefined') ? moment_date_format : 'MM/DD/YYYY';
            swStartDate = picker.startDate.format('YYYY-MM-DD');
            swEndDate = picker.endDate.format('YYYY-MM-DD');
            $(this).val(picker.startDate.format(fmt) + ' - ' + picker.endDate.format(fmt)).trigger('change');
        });
        $input.on('cancel.daterangepicker', function () {
            swStartDate = '';
            swEndDate = '';
            $(this).val('').trigger('change');
        });
    }

    function metaLocation() {
        return $('#sw_ls_location option:selected').text() || 'All Business Locations';
    }
    function metaDate() {
        return $('#sw_ls_date_range').val() || 'All Dates';
    }
    function updateMeta() {
        $('#sw_ls_meta_location').text(metaLocation());
        $('#sw_ls_meta_date').text(metaDate());
    }
    function reportMessage() {
        return 'Business Location: ' + metaLocation() + '\nDate Range: ' + metaDate();
    }
    function money(v) {
        var n = parseFloat(String(v == null ? 0 : v).replace(/<[^>]*>/g, '').replace(/,/g, ''));
        return isNaN(n) ? 0 : n;
    }
    function strip(v) {
        return $('<div>').html(v == null ? '' : v).text().replace(/\s+/g, ' ').trim();
    }

    /*
     * 14 Sep 2026 root fix: the visible report does NOT make an AJAX request.
     * Rows were loaded with the normal authenticated tenant page request, so a
     * second request cannot lose tenant/session context or fail with tn/7.
     */
    var swListRows = {!! $list_rows_json !!};
    if (!$.isArray(swListRows)) swListRows = [];
    $('#sw_ls_error').hide().text('');

    function swArrayHas(list, value) {
        if (!value) return true;
        list = $.isArray(list) ? list : [];
        value = String(value);
        for (var i = 0; i < list.length; i++) {
            if (String(list[i]) === value) return true;
        }
        return false;
    }

    $.fn.dataTable.ext.search.push(function (settings, searchData, dataIndex, rowData) {
        if (!settings.nTable || settings.nTable.id !== 'sw_list_shifts_table') return true;

        var row = rowData;
        if (!row && settings.aoData && settings.aoData[dataIndex]) {
            row = settings.aoData[dataIndex]._aData;
        }
        if (!row || $.isArray(row)) return true;

        var locationId = $('#sw_ls_location').val() || '';
        var operatorId = $('#sw_ls_operator').val() || '';
        var shiftId = $('#sw_ls_shift').val() || '';
        var pumpId = $('#sw_ls_pump').val() || '';
        var paymentMethod = String($('#sw_ls_payment_method').val() || '').toLowerCase();
        var chequeNo = $('#sw_ls_cheque_no').val() || '';
        var settlementNo = $('#sw_ls_settlement_no').val() || '';

        if (locationId && String(row._location_id || '') !== String(locationId)) return false;
        if (shiftId && String(row.shift_id || '') !== String(shiftId)) return false;
        if (operatorId && !swArrayHas(row._operator_ids, operatorId)) return false;
        if (pumpId && !swArrayHas(row._pump_ids, pumpId)) return false;
        if (paymentMethod && !swArrayHas(row._payment_methods, paymentMethod)) return false;
        if (chequeNo && !swArrayHas(row._cheque_nos, chequeNo)) return false;
        if (settlementNo && String(row._settlement_no || '') !== String(settlementNo)) return false;

        var rowDate = String(row._shift_date || '');
        if (swStartDate && (!rowDate || rowDate < swStartDate)) return false;
        if (swEndDate && (!rowDate || rowDate > swEndDate)) return false;

        return true;
    });

    table = $('#sw_list_shifts_table').DataTable({
        data: swListRows,
        processing: false,
        serverSide: false,
        deferRender: true,
        autoWidth: false,
        responsive: false,
        pageLength: 25,
        lengthMenu: [[10,25,50,100,-1],[10,25,50,100,'All']],
        dom: 'Brtip',
        // PHP already returns shift_date DESC, id DESC. Keeping the initial
        // DataTables order empty preserves that actual chronological order.
        order: [],
        columns: [
            {data:'action', orderable:false, searchable:false},
            {data:'location'},
            {data:'date'},
            {data:'operator'},
            {data:'shift_no'},
            {data:'pump_nos'},
            {data:'cash', className:'text-right'},
            {data:'cards', className:'text-right'},
            {data:'credit_sales', className:'text-right'},
            {data:'cheques', className:'text-right'},
            {data:'total_amount', className:'text-right'},
            {data:'settlement_no'},
            {data:'settlement_amount', className:'text-right'}
        ],
        buttons: [
            {extend:'excelHtml5', text:'<i class="fa fa-file-excel-o"></i> Export to Excel', className:'btn btn-success', title:'List SW Shifts', messageTop:reportMessage, footer:true, exportOptions:{columns:exportColumns}},
            {extend:'csvHtml5', text:'<i class="fa fa-file-text-o"></i> Export to CSV', className:'btn btn-info', title:'List SW Shifts', messageTop:reportMessage, footer:true, exportOptions:{columns:exportColumns}},
            {extend:'colvis', text:'<i class="fa fa-columns"></i> Column Visibility', className:'btn btn-default'},
            {extend:'pdfHtml5', text:'<i class="fa fa-file-pdf-o"></i> PDF', className:'btn btn-danger', title:'List SW Shifts', messageTop:reportMessage, footer:true, orientation:'landscape', pageSize:'A3', exportOptions:{columns:exportColumns}},
            {text:'<i class="fa fa-envelope"></i> Email', className:'btn btn-warning', action:function(){
                var subject = 'List SW Shifts - ' + metaLocation() + ' - ' + metaDate();
                window.location.href = 'mailto:?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(buildShareText());
            }},
            {text:'<i class="fa fa-whatsapp"></i> WhatsApp', className:'btn btn-success', action:function(){
                window.open('https://wa.me/?text=' + encodeURIComponent(buildShareText()), '_blank', 'noopener');
            }},
            {extend:'print', text:'<i class="fa fa-print"></i> Print', className:'btn btn-primary', title:'List SW Shifts', messageTop:reportMessage, footer:true, exportOptions:{columns:exportColumns}}
        ],
        initComplete: function () {
            var api = this.api();
            setTimeout(function () { api.columns.adjust(); }, 0);
        },
        footerCallback: function () {
            var api = this.api();
            function sumColumn(index) {
                return api.column(index, {search:'applied'}).data().reduce(function (a,b) { return a + money(b); }, 0);
            }

            $('#sw_ls_total_cash').text(sumColumn(6).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}));
            $('#sw_ls_total_cards').text(sumColumn(7).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}));
            $('#sw_ls_total_credit').text(sumColumn(8).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}));
            $('#sw_ls_total_cheques').text(sumColumn(9).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}));
            $('#sw_ls_total_amount').text(sumColumn(10).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}));

            // A settlement can cover several shifts. Count its amount once in
            // the footer instead of duplicating the same settlement total once
            // for every shift row it contains.
            var seen = {};
            var settlementTotal = 0;
            api.rows({search:'applied'}).data().each(function (row) {
                var id = parseInt(row.settlement_id || 0, 10);
                if (id > 0 && !seen[id]) {
                    settlementTotal += money(row.settlement_amount_raw);
                    seen[id] = true;
                }
            });
            $('#sw_ls_total_settlement').text(settlementTotal.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}));
        }
    });

    /*
     * IS2256: Bootstrap dropdowns inside .table-responsive are clipped by the
     * scroll container, making View/Print look "hidden". Float the action menu on <body> while it is open so it is not clipped,
     * but keep its coordinates anchored immediately BELOW the clicked Action
     * button. Then put it back before redraw/close.
     */
    function swPositionActionMenu($group, $menu) {
        if (!$group.length || !$menu.length || !$group[0]) return;

        var offset = $group.offset();
        var menuWidth = Math.max($menu.outerWidth() || 132, 132);
        var left = offset ? offset.left : 0;
        var top = offset ? (offset.top + $group.outerHeight() + 2) : 0;
        var viewportRight = $(window).scrollLeft() + $(window).width() - 8;

        if (left + menuWidth > viewportRight) {
            left = Math.max($(window).scrollLeft() + 8, viewportRight - menuWidth);
        }

        // IS2257: never flip above the row. View/Print must open immediately
        // BELOW the Action button. Body-level absolute positioning escapes the
        // DataTables responsive container without losing that visual relationship.
        $menu.css({
            top: Math.max(0, top) + 'px',
            left: Math.max(0, left) + 'px'
        });
    }

    function swRestoreActionMenu($group) {
        var $menu = $('body > .sw-shift-action-floating[data-sw-shift-owner="' + ($group.data('sw-shift-owner') || '') + '"]');
        if ($menu.length) {
            $menu.removeClass('sw-shift-action-floating')
                .removeAttr('data-sw-shift-owner')
                .removeAttr('style')
                .appendTo($group);
        }
    }

    $(document).on('show.bs.dropdown', '#sw_list_shifts_table .sw-shift-actions', function () {
        var $group = $(this);
        var owner = 'swshift' + Date.now() + Math.floor(Math.random() * 10000);
        var $menu = $group.children('.sw-shift-action-menu');

        $group.data('sw-shift-owner', owner);
        $menu.attr('data-sw-shift-owner', owner)
            .appendTo('body')
            .addClass('sw-shift-action-floating');

        swPositionActionMenu($group, $menu);
    });

    $(document).on('shown.bs.dropdown', '#sw_list_shifts_table .sw-shift-actions', function () {
        var $group = $(this);
        var owner = $group.data('sw-shift-owner') || '';
        swPositionActionMenu($group, $('body > .sw-shift-action-floating[data-sw-shift-owner="' + owner + '"]'));
    });

    $(document).on('hide.bs.dropdown', '#sw_list_shifts_table .sw-shift-actions', function () {
        swRestoreActionMenu($(this));
    });

    $('#sw_list_shifts_table').on('preDraw.dt', function () {
        $('#sw_list_shifts_table .sw-shift-actions').each(function () {
            swRestoreActionMenu($(this));
        });
        $('body > .sw-shift-action-floating').remove();
    });

    $(window).on('resize.swShiftAction scroll.swShiftAction', function () {
        var $menu = $('body > .sw-shift-action-floating');
        if (!$menu.length) return;
        var owner = $menu.attr('data-sw-shift-owner');
        var $group = $('#sw_list_shifts_table .sw-shift-actions').filter(function () {
            return String($(this).data('sw-shift-owner') || '') === String(owner || '');
        }).first();
        swPositionActionMenu($group, $menu);
    });

    table.buttons().container().appendTo('#sw_ls_dt_buttons');
    initSelect2();
    initDateRange();
    updateMeta();

    $('#sw_ls_location,#sw_ls_operator,#sw_ls_shift,#sw_ls_pump,#sw_ls_payment_method,#sw_ls_cheque_no,#sw_ls_settlement_no,#sw_ls_date_range')
        .on('change', function () { updateMeta(); table.draw(); });

    $('#sw_ls_search').on('input', function () { table.search(this.value).draw(); });
    $('#sw_ls_page_length').on('change', function () { table.page.len(parseInt(this.value,10)).draw(); });

    $(window).on('resize.swListShifts', function () {
        if (table) { table.columns.adjust(); }
    });


    function buildShareText() {
        var lines = ['List SW Shifts', reportMessage(), ''];
        lines.push('Location | Date | Operator | Shift No | Pump Nos | Cash | Cards | Credit Sales | Cheques | Total Amount | Settlement No | Settlement Amount');
        var data = table.rows({search:'applied'}).data();
        var max = Math.min(data.length, 40);
        for (var i=0; i<max; i++) {
            var r = data[i];
            lines.push([
                strip(r.location), strip(r.date), strip(r.operator), strip(r.shift_no), strip(r.pump_nos),
                strip(r.cash), strip(r.cards), strip(r.credit_sales), strip(r.cheques), strip(r.total_amount),
                strip(r.settlement_no), strip(r.settlement_amount)
            ].join(' | '));
        }
        if (data.length > max) lines.push('', 'Showing first ' + max + ' of ' + data.length + ' filtered rows.');
        lines.push('', 'Totals: Cash ' + $('#sw_ls_total_cash').text()
            + ', Cards ' + $('#sw_ls_total_cards').text()
            + ', Credit Sales ' + $('#sw_ls_total_credit').text()
            + ', Cheques ' + $('#sw_ls_total_cheques').text()
            + ', Total Amount ' + $('#sw_ls_total_amount').text()
            + ', Settlement Amount ' + $('#sw_ls_total_settlement').text());
        return lines.join('\n');
    }


});
</script>
@endpush
