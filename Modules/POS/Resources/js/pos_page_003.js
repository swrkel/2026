(function ($) {
    'use strict';

    function money(value) {
        var n = parseFloat(value || 0);
        return n.toLocaleString(undefined, { minimumFractionDigits: 4, maximumFractionDigits: 4 });
    }

    function refreshCart(cart) {
        if (!cart) return;
        var tbody = $('#pos_cart_lines').empty();
        if (!cart.lines || cart.lines.length === 0) {
            tbody.append('<tr class="pos-empty-cart"><td colspan="5" class="text-center">Cart is empty</td></tr>');
        } else {
            $.each(cart.lines, function (_, line) {
                tbody.append('<tr data-line-id="' + line.id + '"><td>' + (line.product_id || '-') + '</td><td class="text-right">' + money(line.quantity) + '</td><td class="text-right">' + money(line.unit_price) + '</td><td class="text-right">' + money(line.line_total) + '</td><td><button class="btn btn-xs btn-danger pos-remove-line"><i class="fa fa-times"></i></button></td></tr>');
            });
        }
        $('#pos_summary_subtotal').text(money(cart.subtotal));
        $('#pos_summary_discount').text(money(cart.discount_amount));
        $('#pos_summary_tax').text(money(cart.tax_amount));
        $('#pos_summary_total').text(money(cart.total_amount));
        $('#pos_selected_customer_name').text(cart.customer_name || 'Walk-in Customer');
    }

    function loadProducts() {
        var q = $('#pos_product_search').val();
        $.ajax({
            url: window.POS_PAGE_003_ROUTES.productSearch,
            method: 'GET',
            dataType: 'json',
            cache: false,
            data: { q: q, category_id: $('#pos_category_filter').val(), brand_id: $('#pos_brand_filter').val() },
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (res) {
            var grid = $('#pos_product_grid').empty();
            var data = res.data || [];
            $('#pos_product_count').text(data.length);
            if (!data.length) {
                grid.append('<div class="pos-empty-state"><i class="fa fa-search"></i><p>No products found</p></div>');
                return;
            }
            $.each(data, function (_, p) {
                grid.append('<div class="pos-product-card" data-product-id="' + p.id + '" data-price="' + (p.price || 0) + '"><div class="name">' + p.name + '</div><div class="sku">' + (p.sku || '') + '</div><div class="price">' + money(p.price) + '</div><div class="stock">Stock: ' + money(p.stock) + '</div></div>');
            });
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Unable to load products. Please retry.';
            $('#pos_product_grid').html('<div class="pos-empty-state"><i class="fa fa-warning"></i><p>' + message + '</p></div>');
            $('#pos_product_count').text('0');
            if (window.console) console.error('POS workspace product search failed', xhr.status, xhr.responseText);
        });
    }

    var productTimer = null;
    $(document).on('keyup change', '#pos_product_search,#pos_category_filter,#pos_brand_filter', function () {
        clearTimeout(productTimer);
        productTimer = setTimeout(loadProducts, 250);
    });

    $(document).on('click', '.pos-product-card', function () {
        $.post(window.POS_PAGE_003_ROUTES.addLine, { _token: $('meta[name="csrf-token"]').attr('content'), product_id: $(this).data('product-id'), quantity: 1, unit_price: $(this).data('price') }, function (res) {
            if (res.cart) refreshCart(res.cart);
        });
    });

    $(document).on('keyup', '#pos_customer_search', function () {
        var q = $(this).val();
        if (q.length < 2) return;
        $.get(window.POS_PAGE_003_ROUTES.customerSearch, { q: q }, function (res) {
            var box = $('#pos_customer_results').empty();
            $.each(res.data || [], function (_, c) {
                box.append('<div class="pos-customer-result" data-id="' + c.id + '" data-name="' + c.name + '"><strong>' + c.name + '</strong><br><small>' + (c.mobile || '') + '</small></div>');
            });
        });
    });

    $(document).on('click', '.pos-customer-result', function () {
        $.post(window.POS_PAGE_003_ROUTES.setCustomer, { _token: $('meta[name="csrf-token"]').attr('content'), customer_id: $(this).data('id'), customer_name: $(this).data('name') }, function (res) {
            if (res.cart) refreshCart(res.cart);
            $('#pos_customer_results').empty();
        });
    });

    $(document).on('click', '#pos_walkin_btn', function () {
        $.post(window.POS_PAGE_003_ROUTES.setCustomer, { _token: $('meta[name="csrf-token"]').attr('content'), customer_id: '', customer_name: 'Walk-in Customer' }, function (res) {
            if (res.cart) refreshCart(res.cart);
        });
    });

    $(document).on('click', '#pos_pay_btn', function () { $('#pos_payment_modal').modal('show'); });
    $(document).on('click', '#pos_add_payment_line', function () {
        var row = $('#pos_payment_lines_table tbody tr:first').clone();
        row.find('input').val('');
        row.find('.pos-payment-amount').val('0.0000');
        $('#pos_payment_lines_table tbody').append(row);
    });
    $(document).on('click', '.pos-remove-payment-line', function () {
        if ($('#pos_payment_lines_table tbody tr').length > 1) $(this).closest('tr').remove();
    });
    $(document).on('click', '.pos-cart-hold', function () { $.post(window.POS_PAGE_003_ROUTES.hold, { _token: $('meta[name="csrf-token"]').attr('content'), note: $('#pos_sale_note').val() }, function () { location.reload(); }); });
    $(document).on('click', '.pos-quick-action[data-action="suspend"]', function () { $.post(window.POS_PAGE_003_ROUTES.suspend, { _token: $('meta[name="csrf-token"]').attr('content'), note: $('#pos_sale_note').val() }, function () { location.reload(); }); });
})(jQuery);
