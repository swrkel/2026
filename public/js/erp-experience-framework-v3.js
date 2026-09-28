
/* ERP Experience Framework V3 - safe helpers for forms, filters and datatables */
(function (window, $) {
    'use strict';
    var EXF = window.EXF || {};
    EXF.version = '3.0.0';
    EXF.initSelect2 = function (context) {
        if (!$ || !$.fn || !$.fn.select2) return;
        $(context || document).find('.exf-select2, [data-exf-select2="true"]').each(function () {
            var $el = $(this);
            if ($el.data('select2')) return;
            $el.select2({ width: '100%' });
        });
    };
    EXF.initDateControls = function (context) {
        if (!$) return;
        var $ctx = $(context || document);
        if ($.fn.datepicker) {
            $ctx.find('[data-exf-date="true"]').each(function () {
                var $el = $(this);
                if ($el.data('datepicker')) return;
                $el.datepicker({ autoclose: true, format: $el.data('format') || 'yyyy-mm-dd' });
            });
        }
        if ($.fn.datetimepicker) {
            $ctx.find('[data-exf-datetime="true"]').each(function () {
                var $el = $(this);
                if ($el.data('DateTimePicker')) return;
                $el.datetimepicker({ format: $el.data('format') || 'YYYY-MM-DD HH:mm' });
            });
        }
    };
    EXF.initDataTables = function (context) {
        if (!$ || !$.fn || !$.fn.DataTable) return;
        $(context || document).find('table[data-exf-datatable="true"]').each(function () {
            var $table = $(this);
            if ($.fn.DataTable.isDataTable(this)) return;
            $table.DataTable({
                responsive: true,
                autoWidth: false,
                pageLength: parseInt($table.data('page-length') || 25, 10),
                dom: $table.data('dom') || 'Bfrtip',
                buttons: $table.data('buttons') || ['csv', 'excel', 'pdf', 'print', 'colvis']
            });
        });
        if (window.erpApplyApprovedToolbarColours) {
            setTimeout(window.erpApplyApprovedToolbarColours, 50);
        }
    };
    EXF.setLoading = function (target, state) {
        var $target = $(target);
        $target.toggleClass('is-loading', !!state);
    };
    EXF.init = function (context) {
        EXF.initSelect2(context);
        EXF.initDateControls(context);
        EXF.initDataTables(context);
    };
    window.EXF = EXF;
    $(document).ready(function () { EXF.init(document); });
    $(document).on('shown.bs.modal', function (e) { EXF.init(e.target); });
})(window, window.jQuery);
