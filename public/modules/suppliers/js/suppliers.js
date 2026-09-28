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
            {extend: 'csv', text: '<i class="fa fa-file-text-o"></i> Export to CSV', className: 'supplier-dt-btn supplier-dt-csv'},
            {extend: 'excel', text: '<i class="fa fa-file-excel-o"></i> Export to Excel', className: 'supplier-dt-btn supplier-dt-excel'},
            {extend: 'colvis', text: '<i class="fa fa-columns"></i> Column Visibility', className: 'supplier-dt-btn supplier-dt-colvis'},
            {extend: 'pdf', text: '<i class="fa fa-file-pdf-o"></i> Export to PDF', className: 'supplier-dt-btn supplier-dt-pdf'},
            {extend: 'print', text: '<i class="fa fa-print"></i> Print', className: 'supplier-dt-btn supplier-dt-print'}
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
     * IS2235 / 2026-09-10:
     * Supplier List -> Ledger -> Payment -> Action dropdown.
     *
     * The Payments table is intentionally inside a horizontally scrollable
     * responsive shell. A normal Bootstrap dropdown therefore gets clipped by
     * the table overflow, especially for the last rows and the right-most Action
     * column. Keep the menu owned by the Bootstrap button, but temporarily move
     * the visible menu to <body> and position it against the viewport.
     *
     * This mirrors the stable Supplier List action-dropdown implementation:
     * - menu is never clipped by table/box overflow;
     * - opens above when there is not enough room below;
     * - remains anchored while the page/table scrolls;
     * - is clamped inside the viewport on small screens;
     * - redraw/filter/paging cleanup cannot leave orphan menus behind.
     */
    var SupplierPaymentDropdown = {
        selector: '.supplier-payment-actions',

        bind: function () {
            $(document).off('.supplierPaymentDropdown');
            $(window).off('.supplierPaymentDropdown');
            $('.supplier-payment-table-shell').off('.supplierPaymentDropdown');

            SupplierPaymentDropdown.closeAll();

            /*
             * S758: own the Payment Action toggle instead of relying on the
             * global Bootstrap/AdminLTE dropdown handler.  The payment table is
             * inside a responsive/overflow shell and the last rows were still
             * being clipped or immediately closed on some tenant layouts.
             * Detaching to body + a module-owned click handler makes the menu
             * deterministic for every row, including the bottom row.
             */
            $(document).on('click.supplierPaymentDropdown', '[data-supplier-payment-menu-toggle="1"]', function (event) {
                event.preventDefault();
                event.stopPropagation();

                var $dropdown = $(this).closest(SupplierPaymentDropdown.selector);
                if (!$dropdown.length) {
                    return;
                }

                var wasOpen = SupplierPaymentDropdown.isOpen($dropdown);
                SupplierPaymentDropdown.closeAll();

                if (wasOpen) {
                    return;
                }

                $dropdown.addClass('open show');
                $dropdown.children('.supplier-payment-action-toggle').attr('aria-expanded', 'true');
                SupplierPaymentDropdown.detach($dropdown);
            });

            $(document).on('click.supplierPaymentDropdown', function (event) {
                var $target = $(event.target);

                if ($target.closest(SupplierPaymentDropdown.selector).length
                    || $target.closest('.supplier-payment-action-menu-detached').length) {
                    return;
                }

                SupplierPaymentDropdown.closeAll();
            });

            $(document).on('keydown.supplierPaymentDropdown', function (event) {
                if (event.key === 'Escape' || event.keyCode === 27) {
                    SupplierPaymentDropdown.closeAll();
                }
            });

            var scheduleReposition = (function () {
                var frame = null;

                return function () {
                    if (frame !== null) {
                        return;
                    }

                    frame = window.requestAnimationFrame(function () {
                        frame = null;
                        SupplierPaymentDropdown.repositionDetachedMenus();
                    });
                };
            }());

            $(window).on('resize.supplierPaymentDropdown scroll.supplierPaymentDropdown', scheduleReposition);
            $('.supplier-payment-table-shell').on('scroll.supplierPaymentDropdown', scheduleReposition);

            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#supplier_payments_table')) {
                $('#supplier_payments_table')
                    .off('draw.dt.supplierPaymentDropdown preXhr.dt.supplierPaymentDropdown destroy.dt.supplierPaymentDropdown')
                    .on('draw.dt.supplierPaymentDropdown preXhr.dt.supplierPaymentDropdown destroy.dt.supplierPaymentDropdown', function () {
                        SupplierPaymentDropdown.closeAll();
                    });
            }
        },

        isOpen: function ($dropdown) {
            if (!$dropdown || !$dropdown.length) {
                return false;
            }

            var $menu = $dropdown.data('supplier-payment-detached-menu');

            return $dropdown.hasClass('open')
                || $dropdown.hasClass('show')
                || ($menu && $menu.length && $menu.hasClass('show'));
        },

        detach: function ($dropdown) {
            var $toggle = $dropdown.children('.supplier-payment-action-toggle');
            var $menu = $dropdown.children('.supplier-payment-action-menu');

            if (!$toggle.length || !$menu.length) {
                return;
            }

            if (!$menu.parent().is('body')) {
                $dropdown.data('supplier-payment-detached-menu', $menu);
                $menu.data('supplier-payment-owner', $dropdown.get(0));
                $menu
                    .addClass('supplier-payment-action-menu-detached show')
                    .appendTo(document.body);
            } else {
                $menu.addClass('show');
            }

            SupplierPaymentDropdown.position($dropdown);
        },

        position: function ($dropdown) {
            var $toggle = $dropdown.children('.supplier-payment-action-toggle');
            var $menu = $dropdown.data('supplier-payment-detached-menu');

            if (!$menu || !$menu.length) {
                $menu = $('body > .supplier-payment-action-menu-detached').filter(function () {
                    return $(this).data('supplier-payment-owner') === $dropdown.get(0);
                }).first();
            }

            if (!$toggle.length || !$menu.length || !$toggle.get(0) || !document.documentElement.contains($toggle.get(0))) {
                return;
            }

            var toggleRect = $toggle.get(0).getBoundingClientRect();
            var menuEl = $menu.get(0);
            var viewportWidth = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
            var viewportHeight = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
            var viewportGap = 8;
            var anchorGap = 4;
            var menuWidth = Math.min(210, Math.max(165, viewportWidth - (viewportGap * 2)));

            menuEl.style.setProperty('display', 'block', 'important');
            menuEl.style.setProperty('position', 'fixed', 'important');
            menuEl.style.setProperty('visibility', 'hidden', 'important');
            menuEl.style.setProperty('top', '0px', 'important');
            menuEl.style.setProperty('left', '0px', 'important');
            menuEl.style.setProperty('right', 'auto', 'important');
            menuEl.style.setProperty('width', menuWidth + 'px', 'important');
            menuEl.style.setProperty('min-width', menuWidth + 'px', 'important');
            menuEl.style.setProperty('max-width', menuWidth + 'px', 'important');
            menuEl.style.setProperty('max-height', 'none', 'important');
            menuEl.style.setProperty('overflow-x', 'hidden', 'important');
            menuEl.style.setProperty('overflow-y', 'visible', 'important');
            menuEl.style.setProperty('z-index', '99999', 'important');

            var naturalHeight = Math.max(menuEl.scrollHeight, menuEl.offsetHeight, 1);
            var availableBelow = Math.max(0, viewportHeight - toggleRect.bottom - anchorGap - viewportGap);
            var availableAbove = Math.max(0, toggleRect.top - anchorGap - viewportGap);
            var openBelow = availableBelow >= naturalHeight || availableBelow >= availableAbove;
            var availableOnChosenSide = openBelow ? availableBelow : availableAbove;
            var renderedHeight = Math.min(naturalHeight, Math.max(80, availableOnChosenSide));
            var top = openBelow
                ? toggleRect.bottom + anchorGap
                : toggleRect.top - anchorGap - renderedHeight;

            var left = toggleRect.right - menuWidth;
            if (left < viewportGap) {
                left = viewportGap;
            }
            if (left + menuWidth > viewportWidth - viewportGap) {
                left = viewportWidth - viewportGap - menuWidth;
            }

            menuEl.style.setProperty('top', Math.round(Math.max(viewportGap, top)) + 'px', 'important');
            menuEl.style.setProperty('left', Math.round(Math.max(viewportGap, left)) + 'px', 'important');
            menuEl.style.setProperty('right', 'auto', 'important');
            menuEl.style.setProperty('max-height', Math.round(renderedHeight) + 'px', 'important');
            menuEl.style.setProperty('overflow-y', naturalHeight > renderedHeight ? 'auto' : 'visible', 'important');
            menuEl.style.setProperty('visibility', 'visible', 'important');
        },

        repositionDetachedMenus: function () {
            $('body > .supplier-payment-action-menu-detached').each(function () {
                var $menu = $(this);
                var owner = $menu.data('supplier-payment-owner');

                if (!owner || !document.documentElement.contains(owner)) {
                    $menu.remove();
                    return;
                }

                var $dropdown = $(owner);
                if (!SupplierPaymentDropdown.isOpen($dropdown)) {
                    SupplierPaymentDropdown.restore($dropdown);
                    return;
                }

                SupplierPaymentDropdown.position($dropdown);
            });
        },

        closeAll: function () {
            $(SupplierPaymentDropdown.selector).each(function () {
                var $dropdown = $(this);
                $dropdown.removeClass('open show');
                $dropdown.children('.supplier-payment-action-toggle').attr('aria-expanded', 'false');
                SupplierPaymentDropdown.restore($dropdown);
            });

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
                .removeClass('supplier-payment-action-menu-detached show')
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


    function canonicalSupplierPaymentDate(value) {
        value = $.trim(String(value == null ? '' : value));
        if (!value) {
            return '';
        }

        var iso = value.match(/^(\d{4}-\d{2}-\d{2})/);
        if (iso) {
            return iso[1];
        }

        if (typeof moment === 'function') {
            var formats = [];
            if (typeof moment_date_format !== 'undefined' && moment_date_format) {
                formats.push(moment_date_format);
                formats.push(moment_date_format + ' HH:mm');
                formats.push(moment_date_format + ' h:mm A');
            }
            formats = formats.concat([
                'MM/DD/YYYY',
                'DD/MM/YYYY',
                'YYYY/MM/DD',
                'MMM D, YYYY',
                'D MMM YYYY'
            ]);

            for (var i = 0; i < formats.length; i += 1) {
                var parsed = moment(value, formats[i], true);
                if (parsed.isValid()) {
                    return parsed.format('YYYY-MM-DD');
                }
            }

            var lenient = moment(value);
            if (lenient.isValid()) {
                return lenient.format('YYYY-MM-DD');
            }
        }

        var nativeDate = new Date(value);
        if (!isNaN(nativeDate.getTime())) {
            var year = nativeDate.getFullYear();
            var month = String(nativeDate.getMonth() + 1).padStart(2, '0');
            var day = String(nativeDate.getDate()).padStart(2, '0');
            return year + '-' + month + '-' + day;
        }

        return '';
    }

    /*
     * S768 - Supplier Payments page -> Action -> Edit.
     *
     * Direct Purchase payments still use the ERP's standard payment editor so
     * document, cheque/card/bank fields and all existing host behaviour remain
     * available. That editor does not render a location input, however its
     * global Payment Method -> Accounting Account handler requires
     * #location_id or #pmt_location_id. The same modal also relied on the
     * host datepicker, which could leave Paid on visually blank on this page.
     *
     * The payment row already knows the canonical date/location/method/account.
     * Restore those values after the host modal has loaded, add a hidden
     * location context for the shared account loader, and use the browser-native
     * date field. No host/core file is modified.
     */
    function stabilizeHostSupplierPaymentEdit($modal, context) {
        var $form = $modal.find('form#transaction_payment_add_form').first();
        if (!$form.length) {
            return;
        }

        context = context || {};
        var $row = $form.find('.payment_row, .payment-row').first();
        if (!$row.length) {
            $row = $form;
        }

        var locationId = $.trim(String(context.locationId || ''));
        if (locationId && locationId !== '0') {
            var $location = $form.find('#pmt_location_id').first();
            if (!$location.length) {
                $location = $('<input>', {
                    type: 'hidden',
                    id: 'pmt_location_id',
                    'class': 'supplier-payment-edit-location-context'
                }).appendTo($row);
            }
            $location.val(locationId);
        }

        var accountId = $.trim(String(context.accountId || ''));
        var $account = $row.find('select.account_id, select[name="account_id"]').first();
        if ($account.length) {
            var $previous = $row.find('input.previous_account').first();
            if (!$previous.length) {
                $previous = $('<input>', {
                    type: 'hidden',
                    'class': 'previous_account'
                }).appendTo($row);
            }
            if (accountId && accountId !== '0') {
                $previous.val(accountId);
                if ($account.find('option[value="' + accountId + '"]').length) {
                    $account.val(accountId);
                    if ($account.hasClass('select2-hidden-accessible')) {
                        $account.trigger('change.select2');
                    }
                }
            }

            $account
                .off('change.supplierPaymentEditContext')
                .on('change.supplierPaymentEditContext', function () {
                    $previous.val($.trim(String($account.val() || '')));
                });
        }

        var method = $.trim(String(context.method || ''));
        var $method = $row.find('select.payment_types_dropdown, select[name="method"]').first();
        if ($method.length && method && $method.find('option[value="' + method + '"]').length) {
            $method.val(method);
            if ($method.hasClass('select2-hidden-accessible')) {
                $method.trigger('change.select2');
            }
        }

        var $paidOn = $form.find('input[name="paid_on"], #paid_on_date').first();
        if ($paidOn.length) {
            var paidOn = canonicalSupplierPaymentDate(context.paidOn || $paidOn.val());

            try {
                if ($paidOn.data('datepicker') || $paidOn.hasClass('hasDatepicker')) {
                    $paidOn.datepicker('destroy');
                }
            } catch (ignore) {}

            try {
                var picker = $paidOn.data('DateTimePicker');
                if (picker && typeof picker.destroy === 'function') {
                    picker.destroy();
                }
            } catch (ignore2) {}

            $paidOn
                .removeData('DateTimePicker')
                .removeClass('hasDatepicker')
                .removeAttr('readonly')
                .attr('type', 'date')
                .attr('autocomplete', 'off');

            if (paidOn) {
                $paidOn.val(paidOn);
            }
        }

        $form.attr('data-supplier-s768-stabilized', '1');
    }

    function applySupplierRootEditAccounts($form, html, preserveAccount) {
        var $account = $form.find('.supplier-payment-edit-account').first();
        if (!$account.length) {
            return;
        }

        var preserve = $.trim(String(preserveAccount || $account.val() || $account.data('current-account') || ''));
        $account.empty().append(html || '<option value="">Please select</option>');

        if (preserve && $account.find('option[value="' + preserve + '"]').length) {
            $account.val(preserve);
        } else {
            var firstValue = $.trim(String($account.find('option[value!=""]').first().val() || ''));
            $account.val(firstValue);
        }

        $account.prop('disabled', false);
        $account.data('current-account', $.trim(String($account.val() || '')));
        if ($account.hasClass('select2-hidden-accessible')) {
            $account.trigger('change.select2');
        } else {
            $account.trigger('change');
        }
    }

    function refreshSupplierRootEditAccounts($form, preserveAccount) {
        if (!$form || !$form.length) {
            return;
        }

        var locationId = $.trim(String($form.find('input[name="location_id"]').val() || $form.data('location-id') || ''));
        var method = $.trim(String($form.find('.supplier-payment-edit-method').val() || ''));
        var $account = $form.find('.supplier-payment-edit-account').first();
        var $help = $form.find('.supplier-payment-edit-account-help').first();

        if (!$account.length || !method) {
            return;
        }

        if (!locationId || locationId === '0') {
            $help.text('The business location could not be resolved. The current account selection has been kept.');
            $account.prop('disabled', false);
            return;
        }

        $help.text('Loading accounts for the selected payment method...');
        $account.prop('disabled', true);

        var primary = $.trim(String($form.data('account-endpoint') || '/finance/get-account-group-name-dp'));
        var legacy = $.trim(String($form.data('account-endpoint-legacy') || '/accounting-module/get-account-group-name-dp'));

        function request(url, fallbackAllowed) {
            return $.ajax({
                method: 'GET',
                url: url,
                data: {group_name: method, location_id: locationId},
                dataType: 'html',
                cache: false
            }).done(function (html) {
                applySupplierRootEditAccounts($form, html, preserveAccount);
                $help.text('');
            }).fail(function () {
                if (fallbackAllowed && legacy && legacy !== url) {
                    request(legacy, false);
                    return;
                }

                $account.prop('disabled', false);
                $help.text('Unable to refresh linked accounts. The current account selection has been kept.');
            });
        }

        request(primary, true);
    }

    function initSupplierRootPaymentEdit($modal) {
        var $form = $modal.find('.supplier-payment-root-edit-form').first();
        if (!$form.length) {
            return;
        }

        var currentAccount = $.trim(String($form.find('.supplier-payment-edit-account').val() || ''));
        refreshSupplierRootEditAccounts($form, currentAccount);
    }

    function loadSupplierPaymentModal(url, selector, context) {
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

                // Host inline scripts may initialise date/select widgets while
                // the HTML is inserted. Run S768 stabilisation on the next tick
                // so our canonical Supplier context wins deterministically.
                window.setTimeout(function () {
                    stabilizeHostSupplierPaymentEdit($modal, context || {});
                    initSupplierRootPaymentEdit($modal);
                }, 0);

                $modal
                    .off('hidden.bs.modal.supplierPaymentEditRefresh')
                    .on('hidden.bs.modal.supplierPaymentEditRefresh', function () {
                        if ($.fn.DataTable.isDataTable('#supplier_payments_table')) {
                            $('#supplier_payments_table').DataTable().ajax.reload(null, false);
                        }
                    });
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
                var url = $(this).data('url');
                SupplierPaymentDropdown.closeAll();
                loadSupplierPaymentModal(url, '.supplier-payment-view-modal');
            })
            .on('click.supplierPaymentActions', '.supplier-payment-edit', function (event) {
                event.preventDefault();
                var $link = $(this);
                var url = $link.data('url');
                var context = {
                    paidOn: $link.attr('data-paid-on') || '',
                    locationId: $link.attr('data-location-id') || '',
                    method: $link.attr('data-method') || '',
                    accountId: $link.attr('data-account-id') || ''
                };
                SupplierPaymentDropdown.closeAll();
                loadSupplierPaymentModal(url, '.supplier-payment-edit-modal', context);
            })
            .on('change.supplierPaymentActions', '.supplier-payment-root-edit-form .supplier-payment-edit-method', function () {
                var $form = $(this).closest('.supplier-payment-root-edit-form');
                var currentAccount = $.trim(String($form.find('.supplier-payment-edit-account').val() || ''));
                refreshSupplierRootEditAccounts($form, currentAccount);
            })
            .on('change.supplierPaymentActions', '.supplier-payment-root-edit-form .supplier-payment-edit-account', function () {
                $(this).data('current-account', $.trim(String($(this).val() || '')));
            })
            .on('submit.supplierPaymentActions', '.supplier-payment-root-edit-form', function (event) {
                event.preventDefault();

                var $form = $(this);
                if ($form.data('saving')) {
                    return;
                }

                var $button = $form.find('.supplier-payment-edit-save');
                var originalHtml = $button.html();
                var $error = $form.find('.supplier-payment-edit-error');
                $error.hide().empty();
                $form.data('saving', true);
                $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

                $.ajax({
                    url: $form.data('update-url') || $form.attr('action'),
                    method: 'POST',
                    data: $form.serialize(),
                    dataType: 'json'
                }).done(function (response) {
                    if (response && response.success === false) {
                        $error.text(response.msg || 'Unable to update the supplier payment.').show();
                        return;
                    }

                    $('.supplier-payment-edit-modal').modal('hide');
                    if ($.fn.DataTable.isDataTable('#supplier_payments_table')) {
                        $('#supplier_payments_table').DataTable().ajax.reload(null, false);
                    }
                }).fail(function (xhr) {
                    var message = (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.msg)) || 'Unable to update the supplier payment.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        var firstKey = Object.keys(errors)[0];
                        if (firstKey && errors[firstKey] && errors[firstKey][0]) {
                            message = errors[firstKey][0];
                        }
                    }
                    $error.text(message).show();
                }).always(function () {
                    $form.data('saving', false);
                    $button.prop('disabled', false).html(originalHtml);
                });
            })
            .on('click.supplierPaymentActions', '.supplier-payment-delete', function (event) {
                event.preventDefault();
                var url = $(this).data('url');
                SupplierPaymentDropdown.closeAll();
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
