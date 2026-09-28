(function ($) {
    'use strict';

    var csrf = (window.PCN_ROUTES && window.PCN_ROUTES.csrf) || $('meta[name="csrf-token"]').attr('content');

    function escapeHtml(value) {
        return $('<div/>').text(value == null ? '' : String(value)).html();
    }

    function notify(type, message) {
        if (window.toastr && typeof window.toastr[type] === 'function') {
            window.toastr[type](message);
            return;
        }
        if (type === 'error') {
            window.alert(message);
        }
    }

    function errorMessage(xhr, fallback) {
        if (xhr && xhr.responseJSON) {
            if (xhr.responseJSON.message) return xhr.responseJSON.message;
            if (xhr.responseJSON.errors) {
                var firstKey = Object.keys(xhr.responseJSON.errors)[0];
                if (firstKey && xhr.responseJSON.errors[firstKey][0]) return xhr.responseJSON.errors[firstKey][0];
            }
        }
        return fallback;
    }

    function number(value, decimals) {
        var parsed = parseFloat(value);
        return Number.isFinite(parsed) ? parsed.toFixed(decimals) : (0).toFixed(decimals);
    }

    function calculatePrice(entered, basis, taxRate) {
        entered = parseFloat(entered || 0);
        taxRate = parseFloat(taxRate || 0);
        var factor = 1 + (taxRate / 100);
        if (basis === 'inc_tax') {
            return {ex: factor > 0 ? entered / factor : entered, inc: entered};
        }
        return {ex: entered, inc: entered * factor};
    }

    function calculateProfit(purchaseEx, sellEx) {
        purchaseEx = parseFloat(purchaseEx || 0);
        sellEx = parseFloat(sellEx || 0);
        return purchaseEx > 0 ? ((sellEx - purchaseEx) / purchaseEx) * 100 : null;
    }

    function selectedLocations() {
        return ($('#pcn_location_ids').val() || [])
            .map(function (id) { return parseInt(id, 10); })
            .filter(Boolean);
    }

    function initializeSelect2() {
        if (!$.fn.select2) return;
        $('.pcn-module .select2').each(function () {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({width: '100%'});
            }
        });
    }

    function dataTableButtons() {
        if (!$.fn.dataTable || !$.fn.dataTable.ext || !$.fn.dataTable.ext.buttons) return [];
        var available = $.fn.dataTable.ext.buttons;
        var buttons = [];
        if (available.csvHtml5 || available.csv) buttons.push({extend: available.csvHtml5 ? 'csvHtml5' : 'csv', text: '<i class="fa fa-file-text-o"></i> CSV', className: 'btn btn-success btn-sm'});
        if (available.excelHtml5 || available.excel) buttons.push({extend: available.excelHtml5 ? 'excelHtml5' : 'excel', text: '<i class="fa fa-file-excel-o"></i> Excel', className: 'btn btn-info btn-sm'});
        if (available.pdfHtml5 || available.pdf) buttons.push({extend: available.pdfHtml5 ? 'pdfHtml5' : 'pdf', text: '<i class="fa fa-file-pdf-o"></i> PDF', className: 'btn btn-danger btn-sm'});
        if (available.print) buttons.push({extend: 'print', text: '<i class="fa fa-print"></i> Print', className: 'btn btn-primary btn-sm'});
        if (available.colvis) buttons.push({extend: 'colvis', text: '<i class="fa fa-columns"></i> Column Visibility', className: 'btn btn-warning btn-sm'});
        return buttons;
    }

    function baseDataTableOptions(buttons) {
        return {
            processing: true,
            serverSide: true,
            stateSave: true,
            responsive: false,
            scrollX: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom: buttons.length ? '<"pcn-dt-toolbar"Bf>rt<"pcn-dt-footer"lip>' : '<"pcn-dt-toolbar"f>rt<"pcn-dt-footer"lip>',
            buttons: buttons,
            language: {search: '', searchPlaceholder: 'Search records...'}
        };
    }

    function initChangesList() {
        var $table = $('#pcn_changes_table');
        if (!$table.length || !$.fn.DataTable) return;
        var buttons = dataTableButtons();
        var options = baseDataTableOptions(buttons);
        options.order = [[9, 'desc']];
        options.ajax = {
            url: $table.data('url'),
            data: function (d) {
                d.status = $('#pcn_filter_status').val();
                d.location_id = $('#pcn_filter_location').val();
                d.start_date = $('#pcn_filter_start_date').val();
                d.end_date = $('#pcn_filter_end_date').val();
            }
        };
        options.columns = [
            {data: 'action', name: 'action', orderable: false, searchable: false},
            {data: 'reference_no', name: 'pcn_price_changes.reference_no'},
            {data: 'title', name: 'pcn_price_changes.title'},
            {data: 'location_names', name: 'location_names', orderable: false, searchable: false, defaultContent: '-'},
            {data: 'lines_count', name: 'lines_count', orderable: false, searchable: false, className: 'text-center'},
            {data: 'status', name: 'pcn_price_changes.status'},
            {data: 'application_scope', name: 'pcn_price_changes.application_scope'},
            {data: 'effective_at', name: 'pcn_price_changes.effective_at'},
            {data: 'created_by_name', name: 'creator.first_name', defaultContent: '-'},
            {data: 'created_at', name: 'pcn_price_changes.created_at'}
        ];
        var table = $table.DataTable(options);

        $('#pcn_filter_status,#pcn_filter_location,#pcn_filter_start_date,#pcn_filter_end_date').on('change', function () { table.ajax.reload(); });
        $('#pcn_filter_reset').on('click', function () {
            $('#pcn_filter_status,#pcn_filter_location').val('').trigger('change.select2');
            $('#pcn_filter_start_date,#pcn_filter_end_date').val('');
            table.search('').ajax.reload();
        });
    }

    function initApprovalList() {
        var $table = $('#pcn_approvals_table');
        if (!$table.length || !$.fn.DataTable) return;
        var buttons = dataTableButtons();
        var options = baseDataTableOptions(buttons);
        options.order = [[8, 'asc']];
        options.ajax = {url: $table.data('url'), data: function (d) { d.location_id = $('#pcn_approval_location').val(); }};
        options.columns = [
            {data: 'action', name: 'action', orderable: false, searchable: false},
            {data: 'reference_no', name: 'pcn_price_changes.reference_no'},
            {data: 'title', name: 'pcn_price_changes.title'},
            {data: 'location_names', name: 'location_names', orderable: false, searchable: false},
            {data: 'lines_count', name: 'lines_count', orderable: false, searchable: false, className: 'text-center'},
            {data: 'application_scope', name: 'pcn_price_changes.application_scope'},
            {data: 'effective_at', name: 'pcn_price_changes.effective_at'},
            {data: 'submitted_by_name', name: 'submitter.first_name'},
            {data: 'submitted_at', name: 'pcn_price_changes.submitted_at'}
        ];
        var table = $table.DataTable(options);
        $('#pcn_approval_location').on('change', function () { table.ajax.reload(); });
        $('#pcn_approval_reset').on('click', function () { $('#pcn_approval_location').val('').trigger('change.select2'); table.search('').ajax.reload(); });
    }

    function initHistoryList() {
        var $table = $('#pcn_history_table');
        if (!$table.length || !$.fn.DataTable) return;
        var buttons = dataTableButtons();
        var options = baseDataTableOptions(buttons);
        options.order = [[13, 'desc']];
        options.ajax = {
            url: $table.data('url'),
            data: function (d) {
                d.status = $('#pcn_history_status').val();
                d.location_id = $('#pcn_history_location').val();
                d.start_date = $('#pcn_history_start_date').val();
                d.end_date = $('#pcn_history_end_date').val();
            }
        };
        options.columns = [
            {data: 'action', name: 'action', orderable: false, searchable: false},
            {data: 'reference_no', name: 'pcn_price_changes.reference_no'},
            {data: 'title', name: 'pcn_price_changes.title'},
            {data: 'location_names', name: 'location_names', orderable: false, searchable: false},
            {data: 'lines_count', name: 'lines_count', orderable: false, searchable: false, className: 'text-center'},
            {data: 'status', name: 'pcn_price_changes.status'},
            {data: 'application_scope', name: 'pcn_price_changes.application_scope'},
            {data: 'application_attempts', name: 'pcn_price_changes.application_attempts', className: 'text-center'},
            {data: 'creator_name', name: 'creator.first_name'},
            {data: 'submitted_at', name: 'pcn_price_changes.submitted_at'},
            {data: 'approver_name', name: 'approver.first_name'},
            {data: 'approved_at', name: 'pcn_price_changes.approved_at'},
            {data: 'applier_name', name: 'applier.first_name'},
            {data: 'applied_at', name: 'pcn_price_changes.applied_at'}
        ];
        var table = $table.DataTable(options);
        $('#pcn_history_status,#pcn_history_location,#pcn_history_start_date,#pcn_history_end_date').on('change', function () { table.ajax.reload(); });
        $('#pcn_history_reset').on('click', function () {
            $('#pcn_history_status,#pcn_history_location').val('').trigger('change.select2');
            $('#pcn_history_start_date,#pcn_history_end_date').val('');
            table.search('').ajax.reload();
        });
    }

    function workflowInput(action, defaultMessage) {
        if (action === 'reject') {
            var reason = window.prompt(defaultMessage || 'Enter the rejection reason.');
            if (reason === null) return null;
            reason = reason.trim();
            if (!reason) { notify('error', 'Enter the rejection reason.'); return null; }
            return {reason: reason};
        }
        if (action === 'approve') {
            var notes = window.prompt('Approval notes (optional):', '');
            if (notes === null) return null;
            return {notes: notes};
        }
        if (action === 'cancel') {
            var cancelReason = window.prompt('Cancellation reason (optional):', '');
            if (cancelReason === null) return null;
            return {reason: cancelReason};
        }
        if (!window.confirm(defaultMessage || 'Continue?')) return null;
        return {};
    }

    function runWorkflow($trigger) {
        var payload = workflowInput(String($trigger.data('action') || ''), String($trigger.data('message') || ''));
        if (payload === null) return;
        payload._token = csrf;
        $trigger.addClass('disabled').attr('aria-disabled', 'true');
        $.ajax({url: $trigger.data('url'), method: 'POST', data: payload})
            .done(function (response) {
                notify('success', response.message || 'Action completed successfully.');
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else if ($.fn.DataTable) {
                    $.fn.dataTable.tables({visible: true, api: true}).ajax.reload(null, false);
                } else {
                    window.location.reload();
                }
            })
            .fail(function (xhr) { notify('error', errorMessage(xhr, 'Unable to complete the workflow action.')); })
            .always(function () { $trigger.removeClass('disabled').removeAttr('aria-disabled'); });
    }

    function initWorkflowActions() {
        $(document).on('click', '.pcn-workflow-action', function (event) {
            event.preventDefault();
            if ($(this).hasClass('disabled')) return;
            runWorkflow($(this));
        });

        $(document).on('click', '.pcn-delete-draft', function (event) {
            event.preventDefault();
            var $trigger = $(this);
            if (!window.confirm('Delete draft ' + ($trigger.data('reference') || '') + '? This cannot be undone.')) return;
            $.ajax({url: $trigger.data('url'), method: 'POST', data: {_method: 'DELETE', _token: csrf}})
                .done(function (response) {
                    notify('success', response.message || 'Draft deleted.');
                    if ($.fn.DataTable) $.fn.dataTable.tables({visible: true, api: true}).ajax.reload(null, false);
                })
                .fail(function (xhr) { notify('error', errorMessage(xhr, 'Unable to delete the draft.')); });
        });
    }

    function initForm() {
        var $form = $('#pcn_price_change_form');
        var $config = $('#pcn_form_config');
        if (!$form.length || !$config.length) return;

        var lines = [];
        try { lines = JSON.parse($('#pcn_existing_lines').text() || '[]'); } catch (e) { lines = []; }
        var searchTimer = null;
        var searchUrl = $config.data('search-url');
        var detailsTemplate = $config.data('details-template');

        function updateLineState() {
            var count = $('#pcn_lines_table tbody tr').length;
            $('#pcn_line_count').text(count);
            $('#pcn_lines_empty').toggleClass('hidden', count > 0);
            $('#pcn_lines_table').toggleClass('hidden', count === 0);
        }

        function isLocationScope() {
            return $('#pcn_application_scope').val() === 'location_price_groups';
        }

        function updateScopeMode() {
            var locationScope = isLocationScope();
            $('#pcn_lines_table tbody tr').each(function () {
                var $row = $(this);
                var $purchase = $row.find('.pcn-new-purchase');
                var $basis = $row.find('.pcn-purchase-basis');
                if (locationScope) {
                    $purchase.val('').prop('disabled', true);
                    $basis.prop('disabled', true);
                    $row.find('.pcn-new-purchase-preview').text('Business purchase price unchanged');
                } else {
                    $purchase.prop('disabled', false);
                    $basis.prop('disabled', false);
                    updateCalculated($row);
                }
            });
            serializeLines();
        }

        function serializeLines() {
            var output = [];
            $('#pcn_lines_table tbody tr').each(function () {
                var $row = $(this);
                output.push({
                    variation_id: parseInt($row.data('variation-id'), 10),
                    sell_price_basis: $row.find('.pcn-sell-basis').val(),
                    new_sell_price: $row.find('.pcn-new-sell').val(),
                    purchase_price_basis: isLocationScope() ? 'inc_tax' : $row.find('.pcn-purchase-basis').val(),
                    new_purchase_price: isLocationScope() ? '' : $row.find('.pcn-new-purchase').val()
                });
            });
            $('#pcn_lines_json').val(JSON.stringify(output));
            updateLineState();
            return output;
        }

        function updateCalculated($row) {
            var tax = parseFloat($row.data('tax-rate') || 0);
            var sell = calculatePrice($row.find('.pcn-new-sell').val(), $row.find('.pcn-sell-basis').val(), tax);
            var purchaseInput = isLocationScope() ? '' : $row.find('.pcn-new-purchase').val();
            var purchase = purchaseInput === ''
                ? {ex: parseFloat($row.data('current-purchase-ex') || 0)}
                : calculatePrice(purchaseInput, $row.find('.pcn-purchase-basis').val(), tax);
            var profit = calculateProfit(purchase.ex, sell.ex);
            $row.find('.pcn-new-sell-preview').text(number(sell.ex, 8) + ' / ' + number(sell.inc, 8));
            if (purchaseInput === '') {
                $row.find('.pcn-new-purchase-preview').text(isLocationScope() ? 'Business purchase price unchanged' : 'Uses current purchase price');
            } else {
                $row.find('.pcn-new-purchase-preview').text(number(purchase.ex, 8) + ' / ' + number(purchase.inc, 8));
            }
            $row.find('.pcn-profit-preview').text(profit === null ? '-' : number(profit, 4) + '%');
            serializeLines();
        }

        function addLine(item) {
            if (!item || !item.variation_id) return;
            if ($('#pcn_lines_table tbody tr[data-variation-id="' + item.variation_id + '"]').length) {
                notify('warning', 'This product variation is already in the draft.');
                return;
            }
            var newSell = item.new_sell_price;
            if (newSell === undefined || newSell === null || newSell === '') newSell = item.current_sell_price_inc_tax;
            var purchaseValue = item.new_purchase_price == null ? '' : item.new_purchase_price;
            var row = '<tr data-variation-id="' + parseInt(item.variation_id, 10) + '" data-tax-rate="' + parseFloat(item.tax_rate || 0) + '" data-current-purchase-ex="' + parseFloat(item.current_purchase_price_ex_tax || 0) + '">' +
                '<td><strong>' + escapeHtml(item.product_name || '') + '</strong><br><small class="text-muted">' + escapeHtml(item.variation_name || '') + '</small></td>' +
                '<td>' + escapeHtml(item.sku || '-') + '</td>' +
                '<td class="text-right">' + number(item.stock_quantity, 4) + '</td>' +
                '<td>' + escapeHtml(item.tax_name || 'No Tax') + '<br><small>' + number(item.tax_rate, 4) + '%</small></td>' +
                '<td class="text-right pcn-price-pair">' + number(item.current_purchase_price_ex_tax, 8) + '<br>' + number(item.current_purchase_price_inc_tax, 8) + '</td>' +
                '<td class="text-right pcn-price-pair">' + number(item.current_sell_price_ex_tax, 8) + '<br>' + number(item.current_sell_price_inc_tax, 8) + '</td>' +
                '<td><input type="number" min="0" step="0.00000001" class="form-control input-sm pcn-price-input pcn-new-purchase" value="' + escapeHtml(purchaseValue) + '"><select class="form-control input-sm pcn-basis-select pcn-purchase-basis"><option value="inc_tax" ' + ((item.purchase_price_basis || 'inc_tax') === 'inc_tax' ? 'selected' : '') + '>Inclusive</option><option value="ex_tax" ' + (item.purchase_price_basis === 'ex_tax' ? 'selected' : '') + '>Exclusive</option></select><small class="text-muted pcn-new-purchase-preview"></small></td>' +
                '<td><input type="number" min="0" step="0.00000001" required class="form-control input-sm pcn-price-input pcn-new-sell" value="' + escapeHtml(newSell) + '"><select class="form-control input-sm pcn-basis-select pcn-sell-basis"><option value="inc_tax" ' + ((item.sell_price_basis || 'inc_tax') === 'inc_tax' ? 'selected' : '') + '>Inclusive</option><option value="ex_tax" ' + (item.sell_price_basis === 'ex_tax' ? 'selected' : '') + '>Exclusive</option></select><small class="text-muted pcn-new-sell-preview"></small></td>' +
                '<td class="text-right pcn-profit-preview">-</td>' +
                '<td class="text-center"><button type="button" class="btn btn-danger btn-xs pcn-remove-line" title="Remove"><i class="fa fa-trash"></i></button></td></tr>';
            var $row = $(row).appendTo('#pcn_lines_table tbody');
            updateCalculated($row);
            updateScopeMode();
        }

        function loadDetails(variationId) {
            var locations = selectedLocations();
            if (!locations.length) {
                notify('warning', 'Select at least one business location first.');
                return;
            }
            $.get(detailsTemplate.replace('__ID__', variationId), {location_ids: locations})
                .done(function (response) { addLine(response.data); })
                .fail(function (xhr) { notify('error', errorMessage(xhr, 'Unable to load product prices.')); });
        }

        $('#pcn_product_search').on('input', function () {
            var term = $(this).val();
            clearTimeout(searchTimer);
            if (term.trim().length < 2) {
                $('#pcn_product_search_results').addClass('hidden').empty();
                return;
            }
            searchTimer = setTimeout(function () {
                $.get(searchUrl, {q: term}).done(function (response) {
                    var html = '';
                    (response.results || []).forEach(function (item) {
                        html += '<button type="button" class="pcn-search-result" data-id="' + parseInt(item.id, 10) + '"><span class="pcn-search-result-icon"><i class="fa fa-cube"></i></span><strong>' + escapeHtml(item.text) + '</strong><i class="fa fa-plus"></i></button>';
                    });
                    if (!html) html = '<div class="pcn-search-no-result">No matching product or variation found.</div>';
                    $('#pcn_product_search_results').html(html).removeClass('hidden');
                }).fail(function (xhr) { notify('error', errorMessage(xhr, 'Unable to search products.')); });
            }, 250);
        });

        $(document).on('click', '.pcn-search-result[data-id]', function () {
            loadDetails($(this).data('id'));
            $('#pcn_product_search').val('');
            $('#pcn_product_search_results').addClass('hidden').empty();
        });
        $(document).on('click', function (event) {
            if (!$(event.target).closest('.pcn-product-search-wrap').length) $('#pcn_product_search_results').addClass('hidden');
        });
        $(document).on('click', '.pcn-remove-line', function () { $(this).closest('tr').remove(); serializeLines(); });
        $(document).on('input change', '.pcn-new-sell,.pcn-new-purchase,.pcn-sell-basis,.pcn-purchase-basis', function () { updateCalculated($(this).closest('tr')); });
        $('#pcn_application_scope').on('change', updateScopeMode);
        $('#pcn_location_ids').on('change', function () {
            if ($('#pcn_lines_table tbody tr').length) notify('info', 'Existing price and stock values are snapshots. Remove and re-add a line to refresh it for changed locations.');
        });

        lines.forEach(addLine);
        updateLineState();
        updateScopeMode();

        $form.on('submit', function (event) {
            if (!selectedLocations().length) {
                event.preventDefault();
                notify('error', 'Select at least one business location.');
                return;
            }
            if (!serializeLines().length) {
                event.preventDefault();
                notify('error', 'Add at least one product price line.');
                return;
            }
            $('#pcn_save_draft').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        });
    }

    $(function () {
        initializeSelect2();
        initChangesList();
        initApprovalList();
        initHistoryList();
        initWorkflowActions();
        initForm();
    });
})(jQuery);
