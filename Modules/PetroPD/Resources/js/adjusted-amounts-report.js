(function ($, window, document) {
    'use strict';

    var config = window.PetroPdAdjustedAmountsReport || {};
    var $table = $('#petropd_adjusted_amounts_table');

    if (!$table.length || !$.fn.DataTable) {
        return;
    }

    var precision = parseInt(config.precision, 10);
    if (isNaN(precision)) {
        precision = 2;
    }

    function number(value) {
        var parsed = parseFloat(value || 0);
        return isNaN(parsed) ? 0 : parsed;
    }

    function formatMoney(value) {
        return number(value).toLocaleString(undefined, {
            minimumFractionDigits: precision,
            maximumFractionDigits: precision,
            useGrouping: true
        });
    }

    function setText(selector, value) {
        $(selector).text(value);
    }

    function updateSummary(summary) {
        summary = summary || {};
        setText('#ppd_kpi_requests', parseInt(summary.request_count || 0, 10).toLocaleString());
        setText('#ppd_kpi_applied_items', parseInt(summary.applied_items || 0, 10).toLocaleString());
        setText('#ppd_kpi_increase', formatMoney(summary.total_increase));
        setText('#ppd_kpi_decrease', formatMoney(summary.total_decrease));
        setText('#ppd_pending_count', parseInt(summary.pending_requests || 0, 10).toLocaleString());
        setText('#ppd_rejected_count', parseInt(summary.rejected_requests || 0, 10).toLocaleString());
        setText('#ppd_net_change', formatMoney(summary.net_change));
        setText('#ppd_footer_current', formatMoney(summary.current_total));
        setText('#ppd_footer_requested', formatMoney(summary.requested_total));
        setText('#ppd_footer_applied', formatMoney(summary.applied_total));
        setText('#ppd_footer_difference', (number(summary.net_change) > 0 ? '+' : '') + formatMoney(summary.net_change));
    }

    function fiscalRange(yearOffset) {
        var month = parseInt(config.financialYearStartMonth, 10) || 1;
        var today = moment();
        var startYear = today.month() + 1 >= month ? today.year() : today.year() - 1;
        startYear += yearOffset || 0;
        var start = moment({year: startYear, month: month - 1, day: 1}).startOf('day');
        return {start: start, end: start.clone().add(1, 'year').subtract(1, 'day').endOf('day')};
    }

    function applyPreset(value) {
        var start;
        var end;
        var now = moment();

        if (value === 'this_year') {
            start = now.clone().startOf('year');
            end = now.clone().endOf('year');
        } else if (value === 'last_year') {
            start = now.clone().subtract(1, 'year').startOf('year');
            end = now.clone().subtract(1, 'year').endOf('year');
        } else if (value === 'this_fy') {
            var thisFy = fiscalRange(0);
            start = thisFy.start;
            end = thisFy.end;
        } else if (value === 'last_fy') {
            var lastFy = fiscalRange(-1);
            start = lastFy.start;
            end = lastFy.end;
        } else {
            return;
        }

        $('#ppd_start_date').val(start.format('YYYY-MM-DD'));
        $('#ppd_end_date').val(end.format('YYYY-MM-DD'));
    }

    function requestData(d) {
        d.date_basis = $('#ppd_date_basis').val();
        d.start_date = $('#ppd_start_date').val();
        d.end_date = $('#ppd_end_date').val();
        d.location_id = $('#ppd_location_id').val();
        d.pump_operator_id = $('#ppd_pump_operator_id').val();
        d.settlement_no = $('#ppd_settlement_no').val();
        d.payment_method = $('#ppd_payment_method').val();
        d.status = $('#ppd_status').val();
        d.requested_by = $('#ppd_requested_by').val();
        d.processed_by = $('#ppd_processed_by').val();
        d.amount_direction = $('#ppd_amount_direction').val();
    }

    var table = $table.DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        scrollX: true,
        autoWidth: false,
        order: [[1, 'desc']],
        pageLength: 25,
        lengthMenu: [10, 25, 50, 75, 100],
        ajax: {
            url: config.url,
            data: requestData
        },
        dom: "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-5'l><'col-sm-7'p>>" +
             "<'row'<'col-sm-12'i>>",
        buttons: [
            {extend: 'csv', text: '<i class="fa fa-file-text-o"></i> CSV', className: 'btn btn-default btn-sm', title: config.title, exportOptions: {columns: ':visible'}},
            {extend: 'excel', text: '<i class="fa fa-file-excel-o"></i> Excel', className: 'btn btn-success btn-sm', title: config.title, exportOptions: {columns: ':visible'}},
            {extend: 'pdf', text: '<i class="fa fa-file-pdf-o"></i> PDF', className: 'btn btn-danger btn-sm', title: config.title, orientation: 'landscape', pageSize: 'A3', exportOptions: {columns: ':visible'}},
            {extend: 'print', text: '<i class="fa fa-print"></i> Print', className: 'btn btn-info btn-sm', title: config.title, exportOptions: {columns: ':visible'}},
            {extend: 'colvis', text: '<i class="fa fa-columns"></i> Column Visibility', className: 'btn btn-default btn-sm', columns: ':not(.always-visible)'}
        ],
        columns: [
            {data: 'request_reference', name: 'request_id', searchable: false},
            {data: 'requested_at', name: 'r.requested_at'},
            {data: 'settlement_date', name: 's.transaction_date'},
            {data: 'settlement_no', name: 'r.settlement_no'},
            {data: 'shift_numbers', name: 'shift_numbers', orderable: false, searchable: false},
            {data: 'location_name', name: 'bl.name'},
            {data: 'pump_operator_name', name: 'po.name'},
            {data: 'payment_method', name: 'i.payment_method'},
            {data: 'current_amount', name: 'i.current_amount', className: 'text-right'},
            {data: 'requested_amount', name: 'i.requested_amount', className: 'text-right'},
            {data: 'applied_amount', name: 'i.applied_amount', className: 'text-right'},
            {data: 'difference_amount', name: 'difference_amount', className: 'text-right'},
            {data: 'reason', name: 'i.reason', orderable: false},
            {data: 'status', name: 'r.status'},
            {data: 'requested_by_name', name: 'requested_by_name', orderable: false},
            {data: 'processed_by_name', name: 'processed_by_name', orderable: false},
            {data: 'processed_at', name: 'processed_at'},
            {data: 'rejection_reason', name: 'r.rejection_reason', orderable: false, visible: false}
        ],
        drawCallback: function () {
            this.api().columns.adjust();
        }
    });

    table.buttons().container().appendTo('#ppd_adjusted_amounts_buttons');

    $table.on('xhr.dt', function (e, settings, json) {
        if (json && json.summary) {
            updateSummary(json.summary);
        }
    });

    var reloadTimer;
    function reloadSoon() {
        clearTimeout(reloadTimer);
        reloadTimer = setTimeout(function () {
            table.ajax.reload(null, true);
        }, 220);
    }

    $('#ppd_report_search').on('input', function () {
        var value = this.value || '';
        clearTimeout(reloadTimer);
        reloadTimer = setTimeout(function () {
            table.search(value).draw();
        }, 250);
    });

    $('.ppd-report-filter').on('change', reloadSoon);

    $('#ppd_date_preset').on('change', function () {
        applyPreset(this.value);
        if (this.value !== 'custom') {
            reloadSoon();
        }
    });

    $('#ppd_start_date, #ppd_end_date').on('change', function () {
        $('#ppd_date_preset').val('custom').trigger('change.select2');
        reloadSoon();
    });

    $('#ppd_reset_filters').on('click', function () {
        $('#ppd_report_search').val('');
        $('.ppd-report-filter').val('').trigger('change.select2');
        $('#ppd_date_basis').val('requested').trigger('change.select2');
        $('#ppd_date_preset').val('this_year').trigger('change.select2');
        applyPreset('this_year');
        table.search('').draw();
    });

    $('.ppd-select2').select2({width: '100%'});
    applyPreset($('#ppd_date_preset').val() || 'this_year');
})(jQuery, window, document);
