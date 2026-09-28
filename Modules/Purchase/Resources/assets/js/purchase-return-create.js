(function ($) {
    'use strict';

    var cfg = window.PurchaseReturnConfig || {};
    var routes = cfg.routes || {};
    var currencyPrecision = Number(cfg.currencyPrecision || 2);
    var quantityPrecision = Number(cfg.quantityPrecision || 3);
    var searchTimer = null;
    var selectedPurchase = null;

    function money(value) {
        return Number(value || 0).toLocaleString(undefined, {
            minimumFractionDigits: currencyPrecision,
            maximumFractionDigits: currencyPrecision
        });
    }

    function quantity(value) {
        return Number(value || 0).toLocaleString(undefined, {
            minimumFractionDigits: quantityPrecision,
            maximumFractionDigits: quantityPrecision
        });
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function showAlert(message, isError) {
        var alert = $('#purchase_return_alert');
        alert.removeClass('alert-danger alert-success')
            .addClass(isError ? 'alert-danger' : 'alert-success')
            .text(message)
            .prop('hidden', false);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function hideResults() {
        $('#return_purchase_results').prop('hidden', true).empty();
    }

    function searchPurchases() {
        var term = $.trim($('#return_purchase_search').val());
        var data = {
            q: term,
            supplier_id: $('#return_supplier_filter').val() || '',
            location_id: $('#return_location_filter').val() || ''
        };
        $('#return_purchase_results').prop('hidden', false).html('<div class="purchase-search-option">Searching...</div>');

        $.getJSON(routes.purchase_search, data).done(function (response) {
            var results = response.results || [];
            if (!results.length) {
                $('#return_purchase_results').html('<div class="purchase-search-option">No received purchases found.</div>');
                return;
            }
            var html = results.map(function (item) {
                return '<div class="purchase-search-option" data-purchase-id="' + Number(item.id) + '">' +
                    '<strong>' + escapeHtml(item.text) + '</strong>' +
                    '<small>' + escapeHtml(item.location_name || '') + ' &nbsp; Total: ' + money(item.final_total) + '</small>' +
                    '</div>';
            }).join('');
            $('#return_purchase_results').html(html);
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) || 'Unable to search purchases.';
            $('#return_purchase_results').html('<div class="purchase-search-option">' + escapeHtml(message) + '</div>');
        });
    }

    function purchaseUrl(id) {
        return String(routes.purchase || '').replace('__ID__', id);
    }

    function selectPurchase(id) {
        hideResults();
        $('#purchase_return_lines_body').html('<tr><td colspan="10" class="purchase-empty-state">Loading purchase products...</td></tr>');

        $.getJSON(purchaseUrl(id)).done(function (response) {
            selectedPurchase = response.purchase || null;
            if (!selectedPurchase) {
                showAlert('The selected purchase could not be loaded.', true);
                return;
            }

            $('#return_purchase_id').val(selectedPurchase.id);
            $('#return_contact_id').val(selectedPurchase.supplier_id);
            $('#return_location_id').val(selectedPurchase.location_id);
            $('#return_store_id').val(selectedPurchase.store_id || '');
            $('#return_supplier_filter').val(String(selectedPurchase.supplier_id)).trigger('change.select2');
            $('#return_location_filter').val(String(selectedPurchase.location_id)).trigger('change.select2');
            $('#return_purchase_search').val(selectedPurchase.number + ' — ' + selectedPurchase.supplier_name);
            $('#selected_purchase_note').html(
                '<strong>Selected Purchase:</strong> ' + escapeHtml(selectedPurchase.number) +
                ' &nbsp; <strong>Supplier:</strong> ' + escapeHtml(selectedPurchase.supplier_name) +
                ' &nbsp; <strong>Location:</strong> ' + escapeHtml(selectedPurchase.location_name) +
                (selectedPurchase.store_name ? ' / ' + escapeHtml(selectedPurchase.store_name) : '')
            );
            renderLines(selectedPurchase.lines || []);
        }).fail(function (xhr) {
            selectedPurchase = null;
            var message = (xhr.responseJSON && xhr.responseJSON.message) || 'Unable to load the selected purchase.';
            showAlert(message, true);
            $('#purchase_return_lines_body').html('<tr><td colspan="10" class="purchase-empty-state">' + escapeHtml(message) + '</td></tr>');
        });
    }

    function renderLines(lines) {
        if (!lines.length) {
            $('#purchase_return_lines_body').html('<tr><td colspan="10" class="purchase-empty-state">All quantities from this purchase have already been returned.</td></tr>');
            $('#return_line_counter').text('0 product lines');
            recalculate();
            return;
        }

        var html = lines.map(function (line, index) {
            var product = escapeHtml(line.product_name || 'Product');
            if (line.variation_name) {
                product += '<small style="display:block;color:#64748b">' + escapeHtml(line.variation_name) + '</small>';
            }
            return '<tr class="return-line-row" data-unit-cost="' + Number(line.purchase_price_inc_tax || 0) + '" data-max="' + Number(line.available_quantity || 0) + '">' +
                '<td class="text-center">' + (index + 1) + '</td>' +
                '<td class="product-cell"><strong>' + product + '</strong>' +
                    '<input type="hidden" name="lines[' + index + '][purchase_line_id]" value="' + Number(line.purchase_line_id) + '"></td>' +
                '<td>' + escapeHtml(line.sku || '-') + '</td>' +
                '<td class="text-center">' + escapeHtml(line.unit_name || '-') + '</td>' +
                '<td class="amount">' + quantity(line.purchased_quantity) + '</td>' +
                '<td class="amount">' + quantity(line.already_returned) + '</td>' +
                '<td class="amount">' + quantity(line.available_quantity) + '</td>' +
                '<td><input type="number" class="form-control return-qty" name="lines[' + index + '][quantity]" min="0" max="' + Number(line.available_quantity) + '" step="' + (line.allow_decimal ? '0.001' : '1') + '" value="0"></td>' +
                '<td class="amount">' + money(line.purchase_price_inc_tax) + '</td>' +
                '<td class="amount return-line-total">0.00</td>' +
                '</tr>';
        }).join('');

        $('#purchase_return_lines_body').html(html);
        $('#return_line_counter').text(lines.length + ' product line' + (lines.length === 1 ? '' : 's'));
        recalculate();
    }

    function recalculate() {
        var subtotal = 0;
        $('.return-line-row').each(function () {
            var row = $(this);
            var max = Number(row.data('max') || 0);
            var input = row.find('.return-qty');
            var qty = Number(input.val() || 0);
            if (qty < 0) qty = 0;
            if (qty > max) {
                qty = max;
                input.val(max);
            }
            var total = qty * Number(row.data('unit-cost') || 0);
            subtotal += total;
            row.find('.return-line-total').text(money(total));
        });
        var tax = Math.max(0, Number($('#return_tax_amount').val() || 0));
        $('#purchase_return_total').text(money(subtotal + tax));
        return subtotal + tax;
    }

    $(function () {
        if ($.fn.select2) {
            $('.purchase-workspace .select2').select2({ width: '100%' });
        }

        $('#return_purchase_search').on('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(searchPurchases, 250);
        }).on('focus', function () {
            if ($.trim($(this).val()).length >= 1) searchPurchases();
        });
        $('#return_purchase_search_button').on('click', searchPurchases);
        $('#return_supplier_filter, #return_location_filter').on('change', function () {
            if (!selectedPurchase) return;
            var supplier = $('#return_supplier_filter').val();
            var location = $('#return_location_filter').val();
            if ((supplier && String(supplier) !== String(selectedPurchase.supplier_id)) ||
                (location && String(location) !== String(selectedPurchase.location_id))) {
                selectedPurchase = null;
                $('#return_purchase_id, #return_contact_id, #return_location_id, #return_store_id').val('');
                $('#return_purchase_search').val('');
                $('#selected_purchase_note').text('No original purchase has been selected.');
                $('#purchase_return_lines_body').html('<tr><td colspan="10" class="purchase-empty-state">Select an original purchase to load the returnable products.</td></tr>');
                recalculate();
            }
        });
        $(document).on('click', '.purchase-search-option[data-purchase-id]', function () {
            selectPurchase($(this).data('purchase-id'));
        });
        $(document).on('click', function (event) {
            if (!$(event.target).closest('.purchase-search-shell').length) hideResults();
        });
        $(document).on('input change', '.return-qty, #return_tax_amount', recalculate);

        if (Number(cfg.oldPurchaseId || 0) > 0) {
            selectPurchase(Number(cfg.oldPurchaseId));
        }

        $('#purchase_return_form').on('submit', function (event) {
            event.preventDefault();
            var form = this;
            var totalQty = 0;
            $('.return-qty').each(function () { totalQty += Math.max(0, Number($(this).val() || 0)); });
            if (!$('#return_purchase_id').val()) {
                showAlert('Please select the original purchase.', true);
                return;
            }
            if (totalQty <= 0) {
                showAlert('Enter a return quantity for at least one product.', true);
                return;
            }

            var button = $('#save_purchase_return');
            button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
            var data = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (body) {
                    if (!response.ok) throw new Error(body.message || 'Unable to save the purchase return.');
                    return body;
                });
            }).then(function (body) {
                window.location.href = body.redirect_url || routes.index;
            }).catch(function (error) {
                showAlert(error.message || 'Unable to save the purchase return.', true);
                button.prop('disabled', false).html('<i class="fa fa-save"></i> Save Purchase Return');
            });
        });
    });
})(jQuery);
