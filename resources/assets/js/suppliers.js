(function (window, document, $) {
    'use strict';

    if (!$) {
        return;
    }

    var SupplierTabsPerformance = window.SupplierTabsPerformance || {};
    var prefetchedUrls = {};

    function numberValue(value) {
        var parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function formatNumber(value, precision) {
        return numberValue(value).toLocaleString('en-US', {
            minimumFractionDigits: precision,
            maximumFractionDigits: precision
        });
    }

    function currencyRenderer(precision) {
        if ($.fn.dataTable && $.fn.dataTable.render && $.fn.dataTable.render.number) {
            return $.fn.dataTable.render.number(',', '.', precision, '');
        }

        return function (data, type) {
            if (type === 'sort' || type === 'type') {
                return numberValue(data);
            }
            return formatNumber(data, precision);
        };
    }

    function refreshCurrency($container) {
        if (typeof window.__currency_convert_recursively === 'function') {
            window.__currency_convert_recursively($container);
        }
    }

    SupplierTabsPerformance.initRemoteSelect = function ($select) {
        if (!$select || !$select.length || !$.fn.select2) {
            return;
        }

        var ajaxUrl = $select.data('ajax-url');
        if (!ajaxUrl) {
            return;
        }

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        $select.select2({
            width: '100%',
            placeholder: $select.attr('placeholder') || '',
            allowClear: String($select.data('allow-clear')) !== '0',
            minimumInputLength: 0,
            ajax: {
                url: ajaxUrl,
                dataType: 'json',
                delay: 150,
                cache: true,
                data: function (params) {
                    return {
                        q: params.term || '',
                        page: params.page || 1
                    };
                },
                processResults: function (data) {
                    return data;
                }
            }
        });
    };

    function initRemoteSelects() {
        $('.supplier-remote-select').each(function () {
            SupplierTabsPerformance.initRemoteSelect($(this));
        });
    }

    function supplierDataTableDom() {
        var hasButtons = !!($.fn.dataTable && $.fn.dataTable.Buttons);

        // Keep the toolbar directly beside the table. The DataTables `r` token creates
        // a processing row before the table in some Bootstrap/DataTables combinations and
        // was responsible for the large empty band shown on Supplier tab pages.
        return hasButtons
            ? "<'supplier-dt-toolbar'<'supplier-dt-buttons'B><'supplier-dt-controls'l f>>t<'supplier-dt-footer'i p>"
            : "<'supplier-dt-toolbar'<'supplier-dt-controls'l f>>t<'supplier-dt-footer'i p>";
    }

    function supplierDataTableButtons() {
        if (!($.fn.dataTable && $.fn.dataTable.Buttons)) {
            return [];
        }

        return [
            {extend: 'csv', text: '<i class="fa fa-file-text-o"></i> Export to CSV', className: 'supplier-dt-btn supplier-standard-action-btn supplier-export-csv'},
            {extend: 'excel', text: '<i class="fa fa-file-excel-o"></i> Export to Excel', className: 'supplier-dt-btn supplier-standard-action-btn supplier-export-excel'},
            {extend: 'colvis', text: '<i class="fa fa-columns"></i> Column Visibility', className: 'supplier-dt-btn supplier-standard-action-btn supplier-column-visibility-btn'},
            {extend: 'pdf', text: '<i class="fa fa-file-pdf-o"></i> Export to PDF', className: 'supplier-dt-btn supplier-standard-action-btn supplier-export-pdf'},
            {extend: 'print', text: '<i class="fa fa-print"></i> Print', className: 'supplier-dt-btn supplier-standard-action-btn supplier-print-standard'}
        ];
    }

    function supplierDataTableLanguage(searchPlaceholder) {
        var language = window.LANG || {};

        return {
            search: '',
            searchPlaceholder: searchPlaceholder || language.search || 'Search ...',
            lengthMenu: (language.show || 'Show') + ' _MENU_ ' + (language.entries || 'entries'),
            emptyTable: language.table_emptyTable || 'No data available in table',
            info: language.table_info || 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: language.table_infoEmpty || 'Showing 0 to 0 of 0 entries',
            loadingRecords: language.table_loadingRecords || 'Loading...',
            processing: language.table_processing || 'Processing...',
            zeroRecords: language.table_zeroRecords || 'No matching records found',
            paginate: {
                first: language.first || 'First',
                last: language.last || 'Last',
                next: language.next || 'Next',
                previous: language.previous || 'Previous'
            }
        };
    }

    function styleSupplierDataTableControls($table) {
        var tableId = String($table.attr('id') || 'supplier_table');
        var $wrapper = $table.closest('.dataTables_wrapper');
        var $host = $('#' + tableId + '_toolbar');
        var $toolbar = $wrapper.children('.supplier-dt-toolbar');

        // Keep DataTables controls outside the horizontally scrollable table shell. This
        // avoids the large blank band and keeps Search / Show entries visible on one row.
        if ($host.length && $toolbar.length && !$toolbar.parent().is($host)) {
            $host.empty().append($toolbar.detach());
        }

        var $controlRoot = $host.length ? $host : $wrapper;
        var $search = $controlRoot.find('.dataTables_filter input');
        var $length = $controlRoot.find('.dataTables_length select');

        $search
            .attr({
                placeholder: 'Search ...',
                'aria-label': 'Search table records',
                autocomplete: 'new-password',
                spellcheck: 'false',
                name: tableId + '_universal_search'
            })
            .addClass('form-control input-sm');
        $length
            .attr('aria-label', 'Number of records per page')
            .addClass('form-control input-sm');

        // Some browsers restore a previous numeric value into generic search inputs. Only
        // clear that browser-restored value on first initialization; never clear a search
        // that the user has entered during the current page session.
        if (!$table.data('supplier-search-initialized')) {
            var api = $.fn.DataTable.isDataTable($table[0]) ? $table.DataTable() : null;
            if (!api || !api.search()) {
                $search.val('');
            }
            $table.data('supplier-search-initialized', true);
        }

        // Recalculate after the toolbar is moved and after the tab becomes visible.
        if ($.fn.DataTable.isDataTable($table[0])) {
            window.requestAnimationFrame(function () {
                $table.DataTable().columns.adjust();
            });
        }
    }

    function updateLedgerSummary(summary) {
        summary = summary || {};

        var precision = parseInt($('#supplier_ledger_table').data('currency-precision'), 10);
        if (!Number.isFinite(precision)) {
            precision = 2;
        }

        $('#supplier_ledger_opening_balance')
            .attr('data-orig-value', numberValue(summary.opening_balance))
            .text(formatNumber(summary.opening_balance, precision));
        $('#supplier_ledger_total_debit')
            .attr('data-orig-value', numberValue(summary.debit_total))
            .text(formatNumber(summary.debit_total, precision));
        $('#supplier_ledger_total_credit')
            .attr('data-orig-value', numberValue(summary.credit_total))
            .text(formatNumber(summary.credit_total, precision));
        $('#supplier_ledger_balance')
            .attr('data-orig-value', numberValue(summary.balance))
            .text(formatNumber(summary.balance, precision));
        $('#supplier_ledger_record_count').text(parseInt(summary.record_count || 0, 10));
        $('#supplier_ledger_footer_debit').text(formatNumber(summary.debit_total, precision));
        $('#supplier_ledger_footer_credit').text(formatNumber(summary.credit_total, precision));
        $('#supplier_ledger_footer_balance').text(formatNumber(summary.balance, precision));
        refreshCurrency($('.supplier-ledger-summary'));
    }

    function initSupplierLedgerTable() {
        var $table = $('#supplier_ledger_table');
        if (!$table.length || !$.fn.DataTable || $.fn.DataTable.isDataTable($table[0])) {
            return;
        }

        var sourceUrl = $table.data('source-url');
        if (!sourceUrl) {
            return;
        }

        var precision = parseInt($table.data('currency-precision'), 10);
        if (!Number.isFinite(precision)) {
            precision = 2;
        }

        var table = $table.DataTable({
            processing: true,
            serverSide: true,
            stateSave: false,
            deferRender: true,
            searchDelay: 300,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, 250], [10, 25, 50, 100, 250]],
            order: [[1, 'asc']],
            responsive: false,
            autoWidth: false,
            // CH1 IS2119: Supplier Ledger column sizing.
            // Increase Type, Debit, Credit and Balance by a further 15% from
            // the latest IS2113 baseline. The remaining columns are reduced
            // proportionally so the full 11-column ledger remains within the page.
            columnDefs: [
                {targets: 0, width: '6.29%'},
                {targets: 1, width: '6.29%'},
                {targets: 2, width: '11.02%'},
                {targets: 3, width: '10.65%'},
                {targets: 4, width: '6.29%'},
                {targets: 5, width: '7.09%'},
                {targets: 6, width: '7.88%'},
                {targets: 7, width: '10.65%'},
                {targets: 8, width: '10.65%'},
                {targets: 9, width: '12.17%'},
                {targets: 10, width: '11.02%'}
            ],
            dom: supplierDataTableDom(),
            buttons: supplierDataTableButtons(),
            language: supplierDataTableLanguage('Search supplier ledger ...'),
            ajax: {
                url: sourceUrl,
                dataSrc: function (json) {
                    updateLedgerSummary(json.summary);
                    return json.data || [];
                }
            },
            columns: [
                {data: 'system_entered_date', name: 'system_entered_date'},
                {data: 'date', name: 'date'},
                {data: 'description', name: 'description'},
                {data: 'type', name: 'type'},
                {data: 'payment_status', name: 'payment_status'},
                {data: 'reference', name: 'reference'},
                {data: 'location', name: 'location'},
                {data: 'debit', name: 'debit', className: 'text-right', render: currencyRenderer(precision)},
                {data: 'credit', name: 'credit', className: 'text-right', render: currencyRenderer(precision)},
                {data: 'balance', name: 'balance', className: 'text-right', render: currencyRenderer(precision)},
                {data: 'payment_method', name: 'payment_method'}
            ],
            initComplete: function () {
                styleSupplierDataTableControls($table);
            }
        });

        table.on('draw.dt', function () {
            styleSupplierDataTableControls($table);
        });
    }

    function bindSupplierTableResize() {
        if ($(window).data('supplier-table-resize-bound')) {
            return;
        }

        $(window).data('supplier-table-resize-bound', true);
        var timer = null;
        $(window).on('resize.supplierTables', function () {
            window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                ['#supplier_ledger_table', '#supplier_payments_table', '#supplier_stock_report_table'].forEach(function (selector) {
                    if ($.fn.DataTable.isDataTable(selector)) {
                        $(selector).DataTable().columns.adjust();
                    }
                });
            }, 120);
        });
    }

    function initSupplierStockReportTable() {
        var $table = $('#supplier_stock_report_table');
        if (!$table.length || !$.fn.DataTable || $.fn.DataTable.isDataTable($table[0])) {
            return;
        }

        var sourceUrl = $table.data('source-url');
        if (!sourceUrl) {
            return;
        }

        $table.DataTable({
            processing: true,
            serverSide: true,
            stateSave: false,
            deferRender: true,
            searchDelay: 300,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, 250], [10, 25, 50, 100, 250]],
            order: [[3, 'desc']],
            responsive: false,
            autoWidth: false,
            dom: supplierDataTableDom(),
            buttons: supplierDataTableButtons(),
            language: supplierDataTableLanguage('Search supplier stock report ...'),
            ajax: sourceUrl,
            columns: [
                {data: 'product', name: 'p.name'},
                {data: 'quantity', name: 'purchase_lines.quantity', className: 'text-right', render: currencyRenderer(3)},
                {data: 'purchase_price_inc_tax', name: 'purchase_lines.purchase_price_inc_tax', className: 'text-right', render: currencyRenderer(2)},
                {data: 'transaction_date', name: 't.transaction_date'},
                {data: 'reference_no', name: 't.ref_no', orderable: true}
            ],
            initComplete: function () {
                styleSupplierDataTableControls($table);
            }
        });
    }

    function initSupplierPaymentsTable() {
        var $table = $('#supplier_payments_table');
        if (!$table.length || !$.fn.DataTable || $.fn.DataTable.isDataTable($table[0])) {
            return;
        }

        var sourceUrl = $table.data('source-url');
        if (!sourceUrl) {
            return;
        }

        var table = $table.DataTable({
            processing: true,
            serverSide: true,
            stateSave: false,
            deferRender: true,
            searchDelay: 300,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, 250], [10, 25, 50, 100, 250]],
            order: [[0, 'desc']],
            responsive: false,
            autoWidth: false,
            dom: supplierDataTableDom(),
            buttons: supplierDataTableButtons(),
            language: supplierDataTableLanguage('Search supplier payments ...'),
            ajax: {
                url: sourceUrl,
                data: function (request) {
                    request.supplier_id = $('#supplier_payment_filter').val() || '';
                }
            },
            columns: [
                {data: 'paid_on', name: 'transaction_payments.paid_on'},
                {data: 'supplier_name', name: 'c.name'},
                {data: 'reference_no', name: 'transaction_payments.payment_ref_no'},
                {data: 'amount', name: 'transaction_payments.amount', className: 'text-right', render: currencyRenderer(2)},
                {data: 'method', name: 'transaction_payments.method', defaultContent: '-'},
                {data: 'action', name: 'action', orderable: false, searchable: false, defaultContent: '-'}
            ],
            initComplete: function () {
                styleSupplierDataTableControls($table);
            }
        });

        $('#supplier_payment_filter').on('change', function () {
            table.ajax.reload(null, true);
        });
    }


    /*
     * IS1987: Supplier Payments -> Actions opened an empty box.
     *
     * The Edit / Delete / View entries were always rendered (see
     * Resources/views/payments/partials/actions.blade.php) - they were being
     * CLIPPED. The table sits in .supplier-full-table-shell, which sets
     * overflow-x: auto together with overflow-y: hidden, so a menu opening
     * downward out of the last table cell is cut off at the bottom edge.
     *
     * A previous fix (IS1967) lifted the overflow on .supplier-financial-table-wrap,
     * but that class only exists on the standalone Financial > Payments screen, so
     * the profile Payments tab was never covered. Lifting the overflow here is not
     * an option either: Actions is the LAST of six columns, so the shell genuinely
     * needs to scroll horizontally on narrow viewports.
     *
     * Instead this reuses the approach already proven on the Supplier list
     * (Resources/assets/js/suppliers/list/index.js): move the menu to <body> and
     * position it fixed, so no ancestor overflow can clip it.
     */
    var SupplierPaymentDropdown = {
        selector: '.supplier-payment-actions',

        bind: function () {
            $(document).off('.supplierPaymentDropdown');
            $(window).off('.supplierPaymentDropdown');

            SupplierPaymentDropdown.closeAll();

            $(document).on('show.bs.dropdown.supplierPaymentDropdown', SupplierPaymentDropdown.selector, function () {
                var $current = $(this);
                SupplierPaymentDropdown.restore($current);
                SupplierPaymentDropdown.closeAll($current);
            });

            $(document).on('shown.bs.dropdown.supplierPaymentDropdown', SupplierPaymentDropdown.selector, function () {
                SupplierPaymentDropdown.detach($(this));
            });

            $(document).on(
                'hide.bs.dropdown.supplierPaymentDropdown hidden.bs.dropdown.supplierPaymentDropdown',
                SupplierPaymentDropdown.selector,
                function () {
                    SupplierPaymentDropdown.restore($(this));
                }
            );

            // A detached menu is positioned against the viewport, so any movement
            // makes it stale. Closing is safer than leaving it over another row.
            $(window).on('resize.supplierPaymentDropdown scroll.supplierPaymentDropdown', function () {
                SupplierPaymentDropdown.closeAll();
            });

            // Server-side paging/search replaces every row. Any menu still attached
            // to <body> would outlive its owner.
            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#supplier_payments_table')) {
                $('#supplier_payments_table')
                    .off('draw.dt.supplierPaymentDropdown preXhr.dt.supplierPaymentDropdown')
                    .on('draw.dt.supplierPaymentDropdown preXhr.dt.supplierPaymentDropdown', function () {
                        SupplierPaymentDropdown.closeAll();
                    });
            }
        },

        detach: function ($dropdown) {
            var $toggle = $dropdown.children('.dropdown-toggle');
            var $menu = $dropdown.children('.supplier-payment-action-menu');

            if (!$toggle.length || !$menu.length || $menu.parent().is('body')) {
                return;
            }

            var toggleRect = $toggle.get(0).getBoundingClientRect();
            var viewportWidth = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
            var viewportHeight = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
            var gap = 10;
            var menuWidth = Math.min(200, Math.max(150, viewportWidth - (gap * 2)));

            $dropdown.data('supplier-payment-detached-menu', $menu);
            $menu.data('supplier-payment-owner', $dropdown.get(0));

            $menu
                .addClass('supplier-payment-action-menu-detached')
                .appendTo(document.body)
                .css({
                    display: 'block',
                    position: 'fixed',
                    visibility: 'hidden',
                    top: 0,
                    left: 0,
                    right: 'auto',
                    width: menuWidth,
                    minWidth: menuWidth,
                    maxWidth: menuWidth,
                    maxHeight: 'none'
                });

            var naturalHeight = Math.min(
                $menu.get(0).scrollHeight + 2,
                Math.max(120, viewportHeight - (gap * 2))
            );
            var availableBelow = Math.max(0, viewportHeight - toggleRect.bottom - gap);
            var availableAbove = Math.max(0, toggleRect.top - gap);
            var maxHeight;
            var top;

            if (naturalHeight <= availableBelow) {
                maxHeight = availableBelow;
                top = toggleRect.bottom + 4;
            } else if (naturalHeight <= availableAbove) {
                maxHeight = availableAbove;
                top = Math.max(gap, toggleRect.top - naturalHeight - 4);
            } else if (availableBelow >= availableAbove) {
                maxHeight = Math.max(120, availableBelow);
                top = toggleRect.bottom + 4;
            } else {
                maxHeight = Math.max(120, availableAbove);
                top = Math.max(gap, toggleRect.top - maxHeight - 4);
            }

            maxHeight = Math.min(maxHeight, viewportHeight - (gap * 2));

            // The markup is .dropdown-menu-right, so keep the menu's right edge
            // aligned to the toggle's right edge, then clamp into the viewport.
            var left = Math.min(
                Math.max(gap, toggleRect.right - menuWidth),
                Math.max(gap, viewportWidth - menuWidth - gap)
            );

            $menu.css({
                top: Math.round(top),
                left: Math.round(left),
                maxHeight: Math.round(maxHeight),
                overflowX: 'hidden',
                overflowY: naturalHeight > maxHeight ? 'auto' : 'visible',
                visibility: 'visible'
            });
        },

        closeAll: function ($except) {
            $(SupplierPaymentDropdown.selector).each(function () {
                var $dropdown = $(this);

                if ($except && $except.length && $dropdown.get(0) === $except.get(0)) {
                    return;
                }

                $dropdown.removeClass('open');
                $dropdown.children('.dropdown-toggle').attr('aria-expanded', 'false');
                SupplierPaymentDropdown.restore($dropdown);
            });

            // Defensive cleanup for a row that was redrawn while its menu was
            // attached directly to <body>.
            $('body > .supplier-payment-action-menu-detached').each(function () {
                var $menu = $(this);
                var owner = $menu.data('supplier-payment-owner');

                if (owner && document.documentElement.contains(owner)) {
                    SupplierPaymentDropdown.restore($(owner));
                } else {
                    $menu.remove();
                }
            });
        },

        restore: function ($dropdown) {
            var $menu = $dropdown.data('supplier-payment-detached-menu');

            if (!$menu || !$menu.length) {
                return;
            }

            $menu
                .removeClass('supplier-payment-action-menu-detached')
                .removeData('supplier-payment-owner')
                .removeAttr('style')
                .appendTo($dropdown);

            $dropdown.removeData('supplier-payment-detached-menu');
        }
    };

    function initSupplierPaymentDropdown() {
        if (!$('#supplier_payments_table').length) {
            return;
        }

        SupplierPaymentDropdown.bind();
    }

    function loadSupplierPaymentModal(url, selector) {
        var $modal = $(selector);
        if (!url || !$modal.length) {
            return;
        }

        $modal.html('<div class="modal-dialog"><div class="modal-content"><div class="modal-body text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div></div></div>');
        $modal.modal('show');

        $.ajax({url: url, method: 'GET', dataType: 'html'})
            .done(function (html) {
                $modal.html(html);
                refreshCurrency($modal);

                if ($.fn.select2) {
                    $modal.find('select.select2').each(function () {
                        var $select = $(this);
                        if (!$select.hasClass('select2-hidden-accessible')) {
                            $select.select2({
                                width: '100%',
                                dropdownParent: $modal
                            });
                        }
                    });
                }

                var $form = $modal.find('form#transaction_payment_add_form');
                if ($form.length && $.fn.validate && !$form.data('validator')) {
                    $form.validate();
                }
            })
            .fail(function (xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.msg) || 'Unable to load the payment details.';
                $modal.find('.modal-body').html('<div class="alert alert-danger">' + $('<div>').text(message).html() + '</div>');
            });
    }

    function initSupplierPaymentActions() {
        $(document)
            .off('click.supplierPaymentActions')
            .on('click.supplierPaymentActions', '.supplier-payment-view', function (event) {
                event.preventDefault();
                loadSupplierPaymentModal($(this).data('url'), '.supplier-payment-view-modal');
            })
            .on('click.supplierPaymentActions', '.supplier-payment-edit', function (event) {
                event.preventDefault();
                loadSupplierPaymentModal($(this).data('url'), '.supplier-payment-edit-modal');
            })
            .on('click.supplierPaymentActions', '.supplier-payment-delete', function (event) {
                event.preventDefault();
                var url = $(this).data('url');
                var confirmed = window.confirm((window.LANG && window.LANG.confirm_delete_payment) || 'This payment will be deleted. Continue?');

                if (!confirmed) {
                    return;
                }

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        _method: 'DELETE'
                    }
                }).done(function (response) {
                    if (response && response.success === false) {
                        window.alert(response.msg || 'The payment could not be deleted.');
                        return;
                    }

                    var table = $('#supplier_payments_table').DataTable();
                    table.ajax.reload(null, false);
                }).fail(function (xhr) {
                    var message = (xhr.responseJSON && xhr.responseJSON.msg) || 'The payment could not be deleted.';
                    window.alert(message);
                });
            });

        $('.supplier-payment-edit-modal')
            .off('hidden.bs.modal.supplierPaymentActions')
            .on('hidden.bs.modal.supplierPaymentActions', function () {
                if ($.fn.DataTable.isDataTable('#supplier_payments_table')) {
                    $('#supplier_payments_table').DataTable().ajax.reload(null, false);
                }
                $(this).empty();
            });

        $('.supplier-payment-view-modal')
            .off('hidden.bs.modal.supplierPaymentActions')
            .on('hidden.bs.modal.supplierPaymentActions', function () {
                $(this).empty();
            });
    }

    function prefetchDocument(url) {
        if (!url || prefetchedUrls[url]) {
            return;
        }

        try {
            var parsed = new URL(url, window.location.href);
            if (parsed.origin !== window.location.origin || parsed.href === window.location.href) {
                return;
            }

            prefetchedUrls[parsed.href] = true;
            var link = document.createElement('link');
            link.rel = 'prefetch';
            link.as = 'document';
            link.href = parsed.href;
            document.head.appendChild(link);
        } catch (error) {
            // Ignore invalid or non-navigation URLs.
        }
    }

    function initTabPrefetch() {
        var timer = null;

        $(document)
            .on('mouseenter focusin pointerdown touchstart', '.supplier-module-tabs a[href]', function (event) {
                var url = this.href;
                window.clearTimeout(timer);
                timer = window.setTimeout(function () {
                    prefetchDocument(url);
                }, event.type === 'mouseenter' ? 65 : 0);
            })
            .on('mouseleave focusout', '.supplier-module-tabs a[href]', function () {
                window.clearTimeout(timer);
            });
    }

    function initLegacyGlobalSearch() {
        var search = document.querySelector('.supplier-global-search');
        if (!search || search.dataset.supplierSearchBound === '1') {
            return;
        }

        search.dataset.supplierSearchBound = '1';
        search.addEventListener('keyup', function () {
            var value = this.value.toLowerCase();
            document.querySelectorAll('.supplier-standard-table tbody tr').forEach(function (row) {
                row.style.display = row.innerText.toLowerCase().indexOf(value) > -1 ? '' : 'none';
            });
        });
    }

    SupplierTabsPerformance.init = function () {
        initLegacyGlobalSearch();
        initRemoteSelects();
        initSupplierLedgerTable();
        initSupplierStockReportTable();
        initSupplierPaymentsTable();
        bindSupplierTableResize();
        initSupplierPaymentActions();
        initSupplierPaymentDropdown();
        initTabPrefetch();
    };

    window.SupplierTabsPerformance = SupplierTabsPerformance;

    $(SupplierTabsPerformance.init);
})(window, document, window.jQuery);
