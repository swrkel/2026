{{-- Other Sales behaviour — 8044. --}}
<script>
$(function () {

    var swOtherSales = [];
    var swOsSeq = 0;

    function n(v) { return parseFloat(v) || 0; }
    function f2(v) { return n(v).toFixed(2); }
    function f3(v) { return n(v).toFixed(3); }

    /*
     | IS2233: the markup already carried sw-select2, but this partial never
     | actually initialised Select2.  Initialise it locally so Store/Product
     | always have a visible type-and-filter search without changing global UI.
    */
    function swInitOtherSaleSearch($select) {
        if (!$.fn.select2 || !$select.length) { return; }

        var current = $select.val();
        if ($select.hasClass('select2-hidden-accessible')) {
            try { $select.select2('destroy'); } catch (ignore) {}
        }

        $select.select2({
            width: '100%',
            allowClear: true,
            minimumResultsForSearch: 0,
            dropdownAutoWidth: false,
            placeholder: '{{ __('messages.please_select') }}'
        });

        if (current !== null && current !== undefined && current !== '') {
            $select.val(current).trigger('change.select2');
        }
    }

    swInitOtherSaleSearch($('#sw_os_store'));
    swInitOtherSaleSearch($('#sw_os_product'));

    // The store list follows the location.
    window.swLoadStores = function (locationId) {
        var $sel = $('#sw_os_store');
        $sel.html('<option value="">{{ __('messages.please_select') }}</option>');

        if (!locationId) { return; }

        $.get('{{ route('sw.settlements.stores') }}', { location_id: locationId }, function (rows) {
            $.each(rows, function (i, r) {
                $sel.append($('<option>', { value: r.id, text: r.name }));
            });

            swInitOtherSaleSearch($sel);

            // One store: choose it. 8044.
            if (rows.length === 1) {
                $sel.val(rows[0].id).trigger('change');
            }
        });
    };

    $('#sw_os_store').on('change', function () {
        var $sel = $('#sw_os_product');
        $sel.html('<option value="">{{ __('messages.please_select') }}</option>');
        $('#sw_os_stock, #sw_os_price').val('');

        var storeId = $(this).val();
        if (!storeId) { return; }

        $.get('{{ route('sw.settlements.store-products') }}', {
            store_id: storeId,
            location_id: $('#sw_st_location').val()
        }, function (rows) {
            $.each(rows, function (i, r) {
                $sel.append($('<option>', {
                    value: r.variation_id,
                    text: (r.sku ? r.sku + '  ·  ' : '') + r.name
                }));
            });

            // Re-bind after replacing the option list. Search matches both SKU
            // and product name because both are part of the option text.
            swInitOtherSaleSearch($sel);
            $sel.trigger('change.select2');
        });
    });

    // Choosing a product fills in its stock and price.
    $('#sw_os_product').on('change', function () {
        var variationId = $(this).val();

        $('#sw_os_stock, #sw_os_price').val('');
        $(this).data('details', null);

        if (!variationId) { return; }

        $.get('{{ route('sw.settlements.product-details') }}', {
            variation_id: variationId,
            location_id: $('#sw_st_location').val()
        }, function (d) {
            if (!d || !d.found) { return; }

            $('#sw_os_product').data('details', d);
            $('#sw_os_stock').val(f3(d.balance_stock));
            $('#sw_os_price').val(f2(d.price));
        });
    });

    $('#sw_os_add').on('click', function () {
        var d = $('#sw_os_product').data('details');

        if (!d || !$('#sw_os_product').val()) {
            toastr.error('{{ __('sw::lang.choose_a_product') }}');
            return;
        }

        var qty = n($('#sw_os_qty').val());

        if (qty <= 0) {
            toastr.error('{{ __('sw::lang.enter_a_quantity') }}');
            return;
        }

        /*
         | Selling more than the balance stock WARNS but does not refuse.
         |
         | A forecourt shop sells what is on the shelf, and a stock figure that
         | has drifted should not stop the takings being recorded. The operator
         | is told; the settlement still reflects what happened.
        */
        if (qty > n(d.balance_stock)) {
            toastr.warning('{{ __('sw::lang.qty_above_stock') }}');
        }

        var price = n($('#sw_os_price').val());
        var before = qty * price;

        var discType = $('#sw_os_disc_type').val();
        var discVal = n($('#sw_os_disc').val());
        var discount = 0;

        if (discType === 'fixed') {
            // Fixed means one discount amount for this sale line. Do not
            // multiply the entered discount by the quantity.
            discount = discVal;
        } else if (discType === 'percentage') {
            discount = before * (discVal / 100);
        }

        swOtherSales.push({
            key: ++swOsSeq,
            store_id: $('#sw_os_store').val(),
            store_name: $('#sw_os_store option:selected').text(),
            product_id: d.product_id,
            variation_id: d.variation_id,
            name: d.name,
            sku: d.sku || '',
            balance_stock: d.balance_stock,
            quantity: qty,
            rate: price,
            discount_type: discType || 'fixed',
            discount_value: discVal,
            amount_before_discount: before,
            amount: before - discount
        });

        swRenderOtherSales();

        // The store stays: consecutive items usually come from the same one.
        $('#sw_os_product').val('').trigger('change.select2').data('details', null);
        $('#sw_os_stock, #sw_os_price').val('');
        $('#sw_os_qty').val('0.000');
        $('#sw_os_disc').val('0.000');
    });

    $(document).on('click', '.sw-os-remove', function () {
        var key = parseInt($(this).data('key'), 10);
        swOtherSales = swOtherSales.filter(function (l) { return l.key !== key; });
        swRenderOtherSales();
    });

    function swRenderOtherSales() {
        var $body = $('#sw_os_rows');

        if (!swOtherSales.length) {
            $body.html('<tr class="sw-os-empty"><td colspan="10" class="text-center text-muted" '
                + 'style="padding:16px">{{ __('sw::lang.no_other_sales_yet') }}</td></tr>');
            $('#sw_os_hidden_inputs').empty();
            $('#sw_os_total, #sw_os_head_total').text('0.00');
            $('#sw_os_total_input').val('0');
            if (typeof window.swRecalcPayments === 'function') { window.swRecalcPayments(); }
            $(document).trigger('sw:settlement-draft-changed');
            return;
        }

        var html = '';
        var hiddenHtml = '';
        var total = 0;

        $.each(swOtherSales, function (i, l) {
            total += l.amount;
            var base = 'other_sales[' + i + ']';

            html += '<tr>'
                + '<td>' + l.sku + '</td>'
                + '<td>' + l.name + '</td>'
                + '<td>' + l.store_name + '</td>'
                + '<td class="text-right">' + f3(l.quantity) + '</td>'
                + '<td class="text-right">' + f2(l.rate) + '</td>'
                + '<td>' + (l.discount_value ? l.discount_type : '') + '</td>'
                + '<td class="text-right">' + f2(l.discount_value) + '</td>'
                + '<td class="text-right">' + f2(l.amount_before_discount) + '</td>'
                + '<td class="text-right">' + f2(l.amount) + '</td>'
                + '<td class="text-center">'
                +   '<button type="button" class="btn btn-danger btn-xs sw-os-remove" '
                +   'data-key="' + l.key + '"><i class="fa fa-times"></i></button>'
                + '</td>'
                + '</tr>';

            // The rate travels with the line, as on the meter lines.
            hiddenHtml += '<input type="hidden" name="' + base + '[store_id]" value="' + l.store_id + '">'
                + '<input type="hidden" name="' + base + '[product_id]" value="' + l.product_id + '">'
                + '<input type="hidden" name="' + base + '[variation_id]" value="' + l.variation_id + '">'
                + '<input type="hidden" name="' + base + '[balance_stock]" value="' + l.balance_stock + '">'
                + '<input type="hidden" name="' + base + '[quantity]" value="' + l.quantity + '">'
                + '<input type="hidden" name="' + base + '[rate]" value="' + l.rate + '">'
                + '<input type="hidden" name="' + base + '[discount_type]" value="' + l.discount_type + '">'
                + '<input type="hidden" name="' + base + '[discount_value]" value="' + l.discount_value + '">'
                + '<input type="hidden" name="' + base + '[amount_before_discount]" value="' + l.amount_before_discount + '">'
                + '<input type="hidden" name="' + base + '[amount]" value="' + l.amount + '">';
        });

        $body.html(html);
        $('#sw_os_hidden_inputs').html(hiddenHtml);
        $('#sw_os_total').text(f2(total));
        $('#sw_os_head_total').text(f2(total));
        $('#sw_os_total_input').val(total.toFixed(2));

        if (typeof window.swRecalcPayments === 'function') { window.swRecalcPayments(); }
        $(document).trigger('sw:settlement-draft-changed');
    }

    $(document).on('sw:settlement-before-submit.swOtherSales', swRenderOtherSales);

    window.swSettlementDraftGetOtherSales = function () {
        return JSON.parse(JSON.stringify(swOtherSales));
    };

    window.swSettlementDraftSetOtherSales = function (rows) {
        swOtherSales = Array.isArray(rows) ? JSON.parse(JSON.stringify(rows)) : [];
        swOsSeq = 0;
        $.each(swOtherSales, function (i, row) {
            // Old localStorage drafts may contain the former per-quantity fixed
            // discount result. Rebuild the line amount on restore.
            var before = n(row.quantity) * n(row.rate);
            var discount = row.discount_type === 'percentage'
                ? before * (n(row.discount_value) / 100)
                : n(row.discount_value);
            row.amount_before_discount = before;
            row.amount = before - discount;
            row.key = parseInt(row.key, 10) || (++swOsSeq);
            swOsSeq = Math.max(swOsSeq, row.key);
        });
        swRenderOtherSales();
    };

    window.swOtherSalesTotal = function () {
        var t = 0;
        $.each(swOtherSales, function (i, l) { t += l.amount; });
        return t;
    };

});
</script>
