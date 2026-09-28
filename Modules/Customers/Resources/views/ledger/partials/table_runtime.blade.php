<script>
(function (window, $) {
    'use strict';

    if (!$) { return; }

    function plainText(value) {
        return $('<div>').html(value == null ? '' : String(value)).text().replace(/\s+/g, ' ').trim();
    }

    function visibleColumnIndexes(table) {
        var indexes = [];
        table.columns().every(function (index) {
            if (this.visible()) { indexes.push(index); }
        });
        return indexes;
    }

    function exportMatrix(table) {
        var indexes = visibleColumnIndexes(table);
        var matrix = [];
        matrix.push(indexes.map(function (index) {
            return plainText($(table.column(index).header()).text());
        }));
        table.rows({search: 'applied', order: 'applied'}).every(function () {
            var node = this.node();
            if (node && $(node).hasClass('ledger-bill-details-row')) { return; }
            var row = this.data();
            matrix.push(indexes.map(function (index) {
                return plainText(Array.isArray(row) ? row[index] : row[table.column(index).dataSrc()]);
            }));
        });
        return matrix;
    }

    function csvCell(value) {
        return '"' + String(value == null ? '' : value).replace(/"/g, '""') + '"';
    }

    function downloadBlob(content, mime, filename) {
        var blob = new Blob([content], {type: mime});
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
    }

    function fallbackExport(table, action) {
        var matrix = exportMatrix(table);
        var stamp = new Date().toISOString().slice(0, 10);
        var base = 'customer-ledger-' + stamp;

        if (action === 'csv') {
            var csv = matrix.map(function (row) { return row.map(csvCell).join(','); }).join('\r\n');
            downloadBlob('\ufeff' + csv, 'text/csv;charset=utf-8', base + '.csv');
            return;
        }

        if (action === 'excel') {
            var rows = matrix.map(function (row) {
                return '<tr>' + row.map(function (cell) { return '<td>' + $('<div>').text(cell).html() + '</td>'; }).join('') + '</tr>';
            }).join('');
            var html = '<html><head><meta charset="utf-8"></head><body><table border="1">' + rows + '</table></body></html>';
            downloadBlob('\ufeff' + html, 'application/vnd.ms-excel;charset=utf-8', base + '.xls');
            return;
        }

        window.print();
    }

    function availableButtons() {
        return $.fn.dataTable && $.fn.dataTable.ext && $.fn.dataTable.ext.buttons
            ? $.fn.dataTable.ext.buttons
            : {};
    }

    function buttonConfig() {
        var available = availableButtons();
        var buttons = [];
        if (available.csvHtml5 || available.csv) {
            buttons.push({extend: available.csvHtml5 ? 'csvHtml5' : 'csv', title: 'Customer Ledger', exportOptions: {columns: ':visible'}});
        }
        if (available.excelHtml5 || available.excel) {
            buttons.push({extend: available.excelHtml5 ? 'excelHtml5' : 'excel', title: 'Customer Ledger', exportOptions: {columns: ':visible'}});
        }
        if (available.pdfHtml5 || available.pdf) {
            buttons.push({extend: available.pdfHtml5 ? 'pdfHtml5' : 'pdf', title: 'Customer Ledger', orientation: 'landscape', pageSize: 'A4', exportOptions: {columns: ':visible'}});
        }
        if (available.print) {
            buttons.push({extend: 'print', title: 'Customer Ledger', exportOptions: {columns: ':visible'}});
        }
        if (available.colvis) {
            buttons.push({extend: 'colvis', columns: ':visible'});
        }
        return buttons;
    }

    function installSideFilterOnce() {
        if (window.__customersLedgerSideFilterInstalled || !$.fn.dataTable) { return; }
        window.__customersLedgerSideFilterInstalled = true;

        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (!settings.nTable || settings.nTable.id !== 'customers_ledger_table') { return true; }

            /*
             * Keep the Entry filter state on THIS DataTable instance.
             * The Customers page can contain/reopen AJAX modals with the same element ids;
             * a global $('#customer_ledger_side_filter') lookup may therefore read an older
             * hidden modal and remain stuck on "all".  Table-local state avoids that.
             */
            var mode = settings._customerLedgerSideMode || 'all';
            if (mode === 'all') { return true; }

            var rowNode = settings.aoData && settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
            var side = rowNode ? String($(rowNode).attr('data-ledger-side') || '').toLowerCase() : '';

            /* Fallback to the actual Debit/Credit cells if a legacy row has no side attribute. */
            if (!side && Array.isArray(data)) {
                var debitText = plainText(data[5] || '').replace(/[^0-9.\-]/g, '');
                var creditText = plainText(data[6] || '').replace(/[^0-9.\-]/g, '');
                var debit = parseFloat(debitText || '0') || 0;
                var credit = parseFloat(creditText || '0') || 0;
                if (credit !== 0 && debit === 0) { side = 'credit'; }
                else if (debit !== 0 && credit === 0) { side = 'debit'; }
            }

            return side === mode;
        });
    }

    function openManualColumnVisibility(table, button) {
        $('#customer_ledger_manual_colvis').remove();
        var menu = $('<div id="customer_ledger_manual_colvis" class="customer-ledger-manual-colvis"></div>');
        table.columns().every(function (index) {
            var title = plainText($(this.header()).text()) || ('Column ' + (index + 1));
            var checkbox = $('<label><input type="checkbox"> <span></span></label>');
            checkbox.find('input').prop('checked', this.visible()).attr('data-column', index);
            checkbox.find('span').text(title);
            menu.append(checkbox);
        });
        $('body').append(menu);
        var offset = $(button).offset();
        menu.css({top: offset.top + $(button).outerHeight() + 5, left: Math.max(10, offset.left - 100)});
        menu.on('change', 'input[type=checkbox]', function () {
            table.column(parseInt($(this).attr('data-column'), 10)).visible(this.checked);
        });
        setTimeout(function () {
            $(document).one('click.customerLedgerColvis', function (e) {
                if (!$(e.target).closest('#customer_ledger_manual_colvis, [data-ledger-colvis]').length) {
                    $('#customer_ledger_manual_colvis').remove();
                }
            });
        }, 0);
    }

    function buildShareText(table, toolbar) {
        var name = toolbar.data('customer-name') || '';
        var code = toolbar.data('customer-code') || '';
        var period = toolbar.data('period') || '';
        var balance = toolbar.data('balance') || '0.00';
        var lines = [
            'Customer Ledger',
            (name + (code ? ' (' + code + ')' : '')).trim(),
            'Period: ' + period,
            'Balance Due: ' + balance,
            ''
        ];
        var count = 0;
        table.rows({search: 'applied', order: 'applied'}).every(function () {
            if (count >= 25) { return; }
            var row = this.data();
            if (!Array.isArray(row)) { return; }
            lines.push([
                plainText(row[1]),
                plainText(row[2]),
                'Debit ' + plainText(row[5]),
                'Credit ' + plainText(row[6]),
                'Balance ' + plainText(row[7])
            ].join(' | '));
            count++;
        });
        if (table.rows({search: 'applied'}).count() > count) {
            lines.push('... plus ' + (table.rows({search: 'applied'}).count() - count) + ' more transactions.');
        }
        return lines.join('\n');
    }

    function init(context) {
        var root = context ? $(context) : $(document);
        var tableEl = root.find('#customers_ledger_table').first();
        if (!tableEl.length) { tableEl = $('#customers_ledger_table').first(); }
        if (!tableEl.length || !$.fn.DataTable) { return; }

        installSideFilterOnce();

        if ($.fn.DataTable.isDataTable(tableEl[0])) {
            tableEl.DataTable().destroy();
        }

        var buttons = buttonConfig();
        var table = tableEl.DataTable({
            deferRender: true,
            autoWidth: false,
            stateSave: false,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, 250, 500, -1], [10, 25, 50, 100, 250, 500, 'All']],
            order: [[1, 'asc'], [0, 'asc']],
            dom: buttons.length ? 'Brtip' : 'rtip',
            buttons: buttons,
            columnDefs: [
                {targets: [5, 6, 7], className: 'text-right'},
                {targets: '_all', orderable: true}
            ]
        });

        var toolbar = root.find('#customer_ledger_toolbar').first();
        if (!toolbar.length) { toolbar = $('#customer_ledger_toolbar').first(); }

        root.off('.customerLedgerTable');
        root.on('input.customerLedgerTable', '#customer_ledger_universal_search', function () {
            table.search(this.value || '').draw();
        });
        root.on('change.customerLedgerTable', '#customer_ledger_page_length', function () {
            table.page.len(parseInt(this.value, 10)).draw();
        });
        root.on('change.customerLedgerTable', '#customer_ledger_side_filter', function () {
            var settings = table.settings()[0];
            if (settings) {
                settings._customerLedgerSideMode = String(this.value || 'all').toLowerCase();
            }
            table.draw();
        });
        root.on('click.customerLedgerTable', '[data-ledger-export]', function () {
            var action = $(this).data('ledger-export');
            var selector = {
                csv: '.buttons-csv, .buttons-csvHtml5',
                excel: '.buttons-excel, .buttons-excelHtml5',
                pdf: '.buttons-pdf, .buttons-pdfHtml5',
                print: '.buttons-print'
            }[action];
            try {
                if (selector && table.buttons && table.buttons(selector).count() > 0) {
                    table.button(selector).trigger();
                    return;
                }
            } catch (ignore) {}
            fallbackExport(table, action);
        });
        root.on('click.customerLedgerTable', '[data-ledger-colvis]', function (e) {
            e.preventDefault();
            try {
                if (table.buttons && table.buttons('.buttons-colvis').count() > 0) {
                    table.button('.buttons-colvis').trigger();
                    return;
                }
            } catch (ignore) {}
            openManualColumnVisibility(table, this);
        });
        root.on('click.customerLedgerTable', '[data-ledger-share]', function () {
            var mode = $(this).data('ledger-share');
            var text = buildShareText(table, toolbar);
            if (mode === 'email') {
                var email = String(toolbar.data('customer-email') || '').trim();
                var subject = 'Customer Ledger - ' + (toolbar.data('customer-name') || 'Customer');
                window.location.href = 'mailto:' + encodeURIComponent(email) + '?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(text);
                return;
            }
            var number = String(toolbar.data('customer-whatsapp') || '').replace(/[^0-9]/g, '');
            var url = number ? ('https://wa.me/' + number + '?text=' + encodeURIComponent(text)) : ('https://wa.me/?text=' + encodeURIComponent(text));
            window.open(url, '_blank', 'noopener');
        });

        root.off('click.customerLedgerBills').on('click.customerLedgerBills', '.customer-ledger-bills-btn', function (e) {
            e.preventDefault();
            var target = $(this).attr('data-target');
            if (target) { $(target).stop(true, true).slideToggle(120); }
        });

        var currentSettings = table.settings()[0];
        if (currentSettings) { currentSettings._customerLedgerSideMode = 'all'; }

        root.find('#customer_ledger_page_length').val('25');
        root.find('#customer_ledger_universal_search').val('');
        root.find('#customer_ledger_side_filter').val('all');
    }

    window.CustomersLedgerTable = {init: init};
})(window, window.jQuery);
</script>
