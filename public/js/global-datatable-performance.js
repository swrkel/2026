(function (window, $) {
    'use strict';

    if (!$ || !$.fn || !$.fn.dataTable) return;

    $.extend(true, $.fn.dataTable.defaults, {
        deferRender: true,
        searchDelay: Number(window.GLOBAL_DATATABLE_SEARCH_DELAY || 350),
        processing: true,
        stateDuration: 0
    });

    window.initLazyDataTable = function (selector, options) {
        var element = document.querySelector(selector);
        if (!element) return null;
        if ($.fn.DataTable.isDataTable(element)) return $(element).DataTable();
        return $(element).DataTable(options || {});
    };

    window.initDataTableOnTab = function (tabSelector, tableSelector, options) {
        $(document).off('shown.bs.tab.gpo', tabSelector).on('shown.bs.tab.gpo', tabSelector, function () {
            var table = window.initLazyDataTable(tableSelector, options);
            if (table && table.columns) table.columns.adjust();
        });
    };
})(window, window.jQuery);
