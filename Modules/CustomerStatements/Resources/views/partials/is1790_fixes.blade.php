<!-- IS1790 preserved: existing table fit and Statement Settings select fixes. -->
<style id="customer-statements-is1790-style">
    #customer_statement_table_wrapper,
    #customer_statement_list_table_wrapper,
    #customer_statement_table_wrapper .dataTables_scroll,
    #customer_statement_list_table_wrapper .dataTables_scroll,
    #customer_statement_table_wrapper .dataTables_scrollHead,
    #customer_statement_list_table_wrapper .dataTables_scrollHead,
    #customer_statement_table_wrapper .dataTables_scrollBody,
    #customer_statement_list_table_wrapper .dataTables_scrollBody {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        overflow-x: visible !important;
    }

    #customer_statement_table,
    #customer_statement_list_table,
    #customer_statement_table_wrapper table,
    #customer_statement_list_table_wrapper table {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        table-layout: fixed !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    #customer_statement_table th,
    #customer_statement_table td,
    #customer_statement_list_table th,
    #customer_statement_list_table td {
        box-sizing: border-box !important;
        white-space: normal !important;
        word-break: normal !important;
        overflow-wrap: anywhere !important;
        vertical-align: middle !important;
        padding: 4px 3px !important;
        line-height: 1.18 !important;
    }

    #customer_statement_table th,
    #customer_statement_table td { font-size: 13.5px !important; }

    #customer_statement_list_table th,
    #customer_statement_list_table td { font-size: 15px !important; }

    #customer_statement_table th:nth-child(1),  #customer_statement_table td:nth-child(1)  { width: 6% !important; }
    #customer_statement_table th:nth-child(2),  #customer_statement_table td:nth-child(2)  { width: 5% !important; }
    #customer_statement_table th:nth-child(3),  #customer_statement_table td:nth-child(3)  { width: 8% !important; }
    #customer_statement_table th:nth-child(4),  #customer_statement_table td:nth-child(4)  { width: 8% !important; }
    #customer_statement_table th:nth-child(5),  #customer_statement_table td:nth-child(5)  { width: 7% !important; }
    #customer_statement_table th:nth-child(6),  #customer_statement_table td:nth-child(6)  { width: 7% !important; }
    #customer_statement_table th:nth-child(7),  #customer_statement_table td:nth-child(7)  { width: 6% !important; }
    #customer_statement_table th:nth-child(8),  #customer_statement_table td:nth-child(8)  { width: 6% !important; }
    #customer_statement_table th:nth-child(9),  #customer_statement_table td:nth-child(9)  { width: 7% !important; }
    #customer_statement_table th:nth-child(10), #customer_statement_table td:nth-child(10) { width: 7% !important; }
    #customer_statement_table th:nth-child(11), #customer_statement_table td:nth-child(11) { width: 8% !important; }
    #customer_statement_table th:nth-child(12), #customer_statement_table td:nth-child(12) { width: 4% !important; }
    #customer_statement_table th:nth-child(13), #customer_statement_table td:nth-child(13) { width: 6% !important; }
    #customer_statement_table th:nth-child(14), #customer_statement_table td:nth-child(14) { width: 7% !important; }
    #customer_statement_table th:nth-child(15), #customer_statement_table td:nth-child(15) { width: 8% !important; }

    /* S605 order: Action, Statement No, Date Printed, Date From, Date To, Customer, Amount, Status, Added By, Description. */
    #customer_statement_list_table th:nth-child(1),  #customer_statement_list_table td:nth-child(1)  { width: 8% !important; }
    #customer_statement_list_table th:nth-child(2),  #customer_statement_list_table td:nth-child(2)  { width: 8% !important; }
    #customer_statement_list_table th:nth-child(3),  #customer_statement_list_table td:nth-child(3)  { width: 8% !important; }
    #customer_statement_list_table th:nth-child(4),  #customer_statement_list_table td:nth-child(4)  { width: 8% !important; }
    #customer_statement_list_table th:nth-child(5),  #customer_statement_list_table td:nth-child(5)  { width: 8% !important; }
    #customer_statement_list_table th:nth-child(6),  #customer_statement_list_table td:nth-child(6)  { width: 14% !important; }
    #customer_statement_list_table th:nth-child(7),  #customer_statement_list_table td:nth-child(7)  { width: 11% !important; }
    #customer_statement_list_table th:nth-child(8),  #customer_statement_list_table td:nth-child(8)  { width: 10% !important; }
    #customer_statement_list_table th:nth-child(9),  #customer_statement_list_table td:nth-child(9)  { width: 10% !important; }
    #customer_statement_list_table th:nth-child(10), #customer_statement_list_table td:nth-child(10) { width: 15% !important; }

    #customer_statement_table_wrapper .dataTables_length,
    #customer_statement_table_wrapper .dataTables_filter,
    #customer_statement_table_wrapper .dataTables_info,
    #customer_statement_table_wrapper .dataTables_paginate,
    #customer_statement_list_table_wrapper .dataTables_length,
    #customer_statement_list_table_wrapper .dataTables_filter,
    #customer_statement_list_table_wrapper .dataTables_info,
    #customer_statement_list_table_wrapper .dataTables_paginate {
        font-size: 15px !important;
    }

    #customer_statement_table .btn,
    #customer_statement_list_table .btn {
        font-size: 15px !important;
    }

    .customer-statement-logo-form select.is1790-native-setting-select {
        display: block !important;
        width: 100% !important;
        height: 34px !important;
        padding: 6px 12px !important;
        opacity: 1 !important;
        visibility: visible !important;
        pointer-events: auto !important;
        position: static !important;
        z-index: auto !important;
    }

    .customer-statement-logo-form select.is1790-native-setting-select + .select2-container {
        display: none !important;
    }

    @media (max-width: 1199px) {
        #customer_statement_table th,
        #customer_statement_table td {
            font-size: 12px !important;
            padding: 3px 2px !important;
        }

        #customer_statement_list_table th,
        #customer_statement_list_table td {
            font-size: 13.5px !important;
            padding: 3px 2px !important;
        }
    }

    /* =================================================================
       IS2011 sizing for the customer statement bill list.
       ================================================================= */

    /* Qty column, 15% narrower. The figures are short; the heading was
       setting the width. */
    #customer_statement_table th:nth-child(12),
    #customer_statement_table td:nth-child(12) {
        max-width: 68px !important;
        width: 68px !important;
        white-space: normal !important;
    }

    /* The Action column holds one small button. Pinning it small is what
       stops it running into Date beside it - the "Click" rename shortens
       the button, this stops the column claiming the spare room back. */
    #customer_statement_table th:nth-child(1),
    #customer_statement_table td:nth-child(1) {
        width: 1% !important;
        white-space: nowrap !important;
    }

    /* Page 10% wider.
       The workspace sits inside the theme's centred content column, so the
       table is squeezed well before the screen runs out. This widens the
       container by 10% and re-centres it with a negative margin, so the
       extra width is taken evenly from both margins rather than pushing
       the page sideways and creating a horizontal scrollbar. */
    .customer-statement-is1638-tabs {
        width: 110% !important;
        max-width: 110% !important;
        margin-left: -5% !important;
        margin-right: -5% !important;
    }

    @media (max-width: 1200px) {
        /* Below this the widening would push content off-screen. */
        .customer-statement-is1638-tabs {
            width: 100% !important;
            max-width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
    }
</style>
<script>
(function ($) {
    'use strict';

    if (!$) {
        return;
    }

    var tableSelectors = '#customer_statement_table, #customer_statement_list_table';
    var resizeTimer = null;

    function fitCustomerStatementTables() {
        $(tableSelectors).each(function () {
            var table = this;
            var $table = $(table);

            $table.css({
                width: '100%',
                maxWidth: '100%',
                minWidth: '0',
                tableLayout: 'fixed'
            });

            if ($.fn.DataTable && $.fn.DataTable.isDataTable(table)) {
                try {
                    var dataTable = $table.DataTable();
                    dataTable.columns.adjust();

                    if (dataTable.responsive && typeof dataTable.responsive.recalc === 'function') {
                        dataTable.responsive.recalc();
                    }
                } catch (error) {}
            }
        });
    }

    var s605ListColumnOrderApplied = false;

    function remapSorting(sort, order) {
        if (!Array.isArray(sort)) {
            return sort;
        }

        return sort.map(function (item) {
            if (!Array.isArray(item) || item.length < 2) {
                return item;
            }

            var oldIndex = parseInt(item[0], 10);
            var newIndex = order.indexOf(oldIndex);
            return [newIndex >= 0 ? newIndex : oldIndex, item[1]];
        });
    }

    function reorderHeaderCells($row, order) {
        var cells = $row.children('th, td').toArray();
        if (cells.length !== order.length) {
            return false;
        }

        order.forEach(function (oldIndex) {
            $row.append(cells[oldIndex]);
        });

        return true;
    }

    function moveStatementNumberNextToAction() {
        var selector = '#customer_statement_list_table';
        if (
            s605ListColumnOrderApplied
            || !$.fn.DataTable
            || !$.fn.DataTable.isDataTable(selector)
        ) {
            return;
        }

        var $table = $(selector);
        var dataTable = $table.DataTable();
        var settings = dataTable.settings()[0];
        var dataSources = (settings.aoColumns || []).map(function (column) {
            return column.mData;
        });
        var statementIndex = dataSources.indexOf('statement_no');

        if (statementIndex < 0 || statementIndex === 1) {
            s605ListColumnOrderApplied = statementIndex === 1;
            return;
        }

        var order = [0, statementIndex];
        dataSources.forEach(function (_, index) {
            if (index !== 0 && index !== statementIndex) {
                order.push(index);
            }
        });

        var init = $.extend(true, {}, settings.oInit || {});
        var optionName = Array.isArray(init.columns)
            ? 'columns'
            : (Array.isArray(init.aoColumns) ? 'aoColumns' : '');

        if (!optionName || init[optionName].length !== order.length) {
            return;
        }

        init[optionName] = order.map(function (oldIndex) {
            return init[optionName][oldIndex];
        });
        init.order = remapSorting(init.order, order);
        init.aaSorting = remapSorting(init.aaSorting, order);

        dataTable.destroy();

        if (!reorderHeaderCells($table.find('thead tr').first(), order)) {
            return;
        }

        window.customer_statement_list_table = $table.DataTable(init);
        s605ListColumnOrderApplied = true;
        window.setTimeout(fitCustomerStatementTables, 0);
    }

    function scheduleS605ListColumnOrder() {
        [0, 180, 500].forEach(function (delay) {
            window.setTimeout(moveStatementNumberNextToAction, delay);
        });
    }

    function useNativeSettingSelects(context) {
        var $scope = context ? $(context) : $(document.body);

        $scope.find(
            '.customer-statement-logo-form select[name="alignment"], ' +
            '.customer-statement-logo-form select[name="text_position"]'
        ).each(function () {
            var $select = $(this);

            try {
                if ($.fn.select2 && $select.data('select2')) {
                    $select.select2('destroy');
                }
            } catch (error) {}

            $select.next('.select2-container').remove();
            $select.siblings('.select2-container').remove();
            $select
                .removeClass('select2 select2-hidden-accessible customer-statement-select2')
                .addClass('is1790-native-setting-select form-control')
                .removeAttr('aria-hidden tabindex')
                .prop('disabled', false)
                .show();
        });
    }

    function scheduleModalSelectFix(context) {
        [0, 120, 300, 650].forEach(function (delay) {
            window.setTimeout(function () {
                useNativeSettingSelects(context);
            }, delay);
        });
    }

    $(function () {
        fitCustomerStatementTables();
        useNativeSettingSelects(document.body);
        window.setTimeout(fitCustomerStatementTables, 250);
        window.setTimeout(fitCustomerStatementTables, 800);
        scheduleS605ListColumnOrder();
    });

    $(document)
        .off('draw.dt.is1790', tableSelectors)
        .on('draw.dt.is1790', tableSelectors, function () {
            window.setTimeout(fitCustomerStatementTables, 0);
        })
        .off('shown.bs.tab.is1790')
        .on('shown.bs.tab.is1790', 'a[href="#customer_statements"], a[href="#list_customer_statements"]', function () {
            window.setTimeout(fitCustomerStatementTables, 0);
            window.setTimeout(fitCustomerStatementTables, 180);
            if ($(this).attr('href') === '#list_customer_statements') {
                scheduleS605ListColumnOrder();
            }
        })
        .off('shown.bs.modal.is1790')
        .on('shown.bs.modal.is1790', '.customer_statement_modal, .view_modal, .modal', function () {
            scheduleModalSelectFix(this);
        });

    $(window)
        .off('resize.is1790CustomerStatements')
        .on('resize.is1790CustomerStatements', function () {
            window.clearTimeout(resizeTimer);
            resizeTimer = window.setTimeout(fitCustomerStatementTables, 120);
        });

    if (window.MutationObserver) {
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                Array.prototype.forEach.call(mutation.addedNodes || [], function (node) {
                    if (!node || node.nodeType !== 1) {
                        return;
                    }

                    var $node = $(node);
                    if ($node.is('.customer-statement-logo-form') || $node.find('.customer-statement-logo-form').length) {
                        scheduleModalSelectFix(node);
                    }

                    if ($node.is(tableSelectors) || $node.find(tableSelectors).length) {
                        window.setTimeout(fitCustomerStatementTables, 0);
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }
})(window.jQuery);
</script>
