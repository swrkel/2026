(function ($, window) {
    'use strict';

    var config = window.PetroPdListAssignedOperators || {};
    var $table = $('#list_assigned_operators_table');
    if (!$table.length || !$.fn.DataTable) { return; }

    var reloadTimer;
    var settlementRequest;

    function fiscalRange(offset) {
        var month = parseInt(config.financialYearStartMonth, 10) || 1;
        var today = moment();
        var year = (today.month() + 1 >= month ? today.year() : today.year() - 1) + (offset || 0);
        var start = moment({year: year, month: month - 1, day: 1}).startOf('day');
        return {start: start, end: start.clone().add(1, 'year').subtract(1, 'day').endOf('day')};
    }

    function applyPreset(value) {
        var now = moment(), start, end;
        if (value === 'this_year') {
            start = now.clone().startOf('year'); end = now.clone().endOf('year');
        } else if (value === 'last_year') {
            start = now.clone().subtract(1, 'year').startOf('year'); end = now.clone().subtract(1, 'year').endOf('year');
        } else if (value === 'this_fy') {
            var thisFy = fiscalRange(0); start = thisFy.start; end = thisFy.end;
        } else if (value === 'last_fy') {
            var lastFy = fiscalRange(-1); start = lastFy.start; end = lastFy.end;
        } else {
            return;
        }
        $('#lao_start_date').val(start.format('YYYY-MM-DD'));
        $('#lao_end_date').val(end.format('YYYY-MM-DD'));
    }

    function filterData() {
        return {
            start_date: $('#lao_start_date').val(),
            end_date: $('#lao_end_date').val(),
            operator_id: $('#lao_operator_id').val(),
            pump_id: $('#lao_pump_id').val(),
            pump_status: $('#lao_pump_status').val(),
            shift_status: $('#lao_shift_status').val(),
            settlement_no: $('#lao_settlement_no').val()
        };
    }

    function requestData(d) {
        $.extend(d, filterData());
    }

    function formatAssignedDate(value, type) {
        if (!value) { return '-'; }
        if (type === 'sort' || type === 'type') { return value; }
        if (typeof moment !== 'undefined') {
            var fmt = typeof moment_date_format !== 'undefined' ? moment_date_format : 'DD/MM/YYYY';
            return moment(value, 'YYYY-MM-DD').format(fmt);
        }
        return value;
    }

    var table = $table.DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        scrollX: true,
        autoWidth: false,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 75, 100],
        order: [[0, 'desc']],
        ajax: {url: config.url, data: requestData},
        dom: "<'row'<'col-sm-12'tr>><'row'<'col-sm-5'l><'col-sm-7'p>><'row'<'col-sm-12'i>>",
        buttons: [
            {extend: 'csv', text: '<i class="fa fa-file-text-o"></i> CSV', className: 'btn btn-default btn-sm', title: config.title, exportOptions: {columns: ':visible'}},
            {extend: 'excel', text: '<i class="fa fa-file-excel-o"></i> Excel', className: 'btn btn-success btn-sm', title: config.title, exportOptions: {columns: ':visible'}},
            {extend: 'pdf', text: '<i class="fa fa-file-pdf-o"></i> PDF', className: 'btn btn-danger btn-sm', title: config.title, orientation: 'landscape', pageSize: 'A4', exportOptions: {columns: ':visible'}},
            {extend: 'print', text: '<i class="fa fa-print"></i> Print', className: 'btn btn-info btn-sm', title: config.title, exportOptions: {columns: ':visible'}},
            {extend: 'colvis', text: '<i class="fa fa-columns"></i> Column Visibility', className: 'btn btn-default btn-sm'}
        ],
        columns: [
            {data: 'assigned_date', name: 'assigned_date', render: formatAssignedDate},
            {data: 'operator_name', name: 'operator_name'},
            {data: 'assigned_pumps', name: 'pump_details', orderable: false},
            {data: 'pump_status', name: 'pump_details', orderable: false},
            {data: 'shift_status', name: 'shift_status'},
            {data: 'settlement_no', name: 'settlement_no'}
        ],
        drawCallback: function () { this.api().columns.adjust(); }
    });

    table.buttons().container().appendTo('#lao_buttons');

    function reloadTable(resetPage) {
        clearTimeout(reloadTimer);
        reloadTimer = setTimeout(function () {
            table.ajax.reload(null, resetPage !== false);
        }, 180);
    }

    function reloadSettlementOptions(keepValue) {
        var $select = $('#lao_settlement_no');
        var selected = keepValue === false ? '' : ($select.val() || '');
        var data = filterData();
        delete data.settlement_no;

        if (settlementRequest && settlementRequest.readyState !== 4) {
            settlementRequest.abort();
        }

        settlementRequest = $.get(config.settlementOptionsUrl, data)
            .done(function (response) {
                var options = ['<option value="">All Settlement Nos</option>'];
                $.each((response && response.settlements) || [], function (_, item) {
                    var value = $('<div>').text(item.id == null ? '' : item.id).html();
                    var text = $('<div>').text(item.text == null ? '' : item.text).html();
                    options.push('<option value="' + value + '">' + text + '</option>');
                });
                $select.html(options.join(''));
                if (selected && $select.find('option[value="' + String(selected).replace(/"/g, '\\"') + '"]').length) {
                    $select.val(selected);
                } else {
                    $select.val('');
                }
                $select.trigger('change.select2');
            });
    }

    $('.lao-select2').select2({width: '100%'});
    applyPreset($('#lao_date_preset').val() || 'this_year');
    reloadSettlementOptions(false);

    $('#lao_date_preset').on('change', function () {
        if (this.value !== 'custom') {
            applyPreset(this.value);
            reloadSettlementOptions(false);
            reloadTable(true);
        }
    });

    $('#lao_start_date, #lao_end_date').on('change', function () {
        $('#lao_date_preset').val('custom').trigger('change.select2');
        reloadSettlementOptions(false);
        reloadTable(true);
    });

    $('#lao_operator_id, #lao_pump_id, #lao_pump_status, #lao_shift_status').on('change', function () {
        reloadSettlementOptions(false);
        reloadTable(true);
    });

    $('#lao_settlement_no').on('change', function () {
        reloadTable(true);
    });

    $('#lao_search').on('input', function () {
        var value = this.value || '';
        clearTimeout(reloadTimer);
        reloadTimer = setTimeout(function () { table.search(value).draw(); }, 220);
    });

    $('#lao_reset_filters').on('click', function () {
        $('#lao_search').val('');
        $('#lao_operator_id, #lao_pump_id, #lao_pump_status, #lao_shift_status, #lao_settlement_no').val('').trigger('change.select2');
        $('#lao_date_preset').val('this_year').trigger('change.select2');
        applyPreset('this_year');
        table.search('');
        reloadSettlementOptions(false);
        reloadTable(true);
    });
})(jQuery, window);
