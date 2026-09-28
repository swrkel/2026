{{-- Credit Sales behaviour — 8047. --}}
<script>
$(function () {
    var swDailyCreditSalesRequestSeq = 0;

    var swCreditSales = [];
    var swCsSeq = 0;

    function n(v) { return parseFloat(v) || 0; }
    function f2(v) { return n(v).toFixed(2); }
    function f3(v) { return n(v).toFixed(3); }
    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }

    /* IS2233: make the two long Credit Sale lists reliably searchable. */
    function swInitCreditSaleSearch($select) {
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

    swInitCreditSaleSearch($('#sw_cs_customer'));
    swInitCreditSaleSearch($('#sw_cs_product'));
    swInitCreditSaleSearch($('#sw_cs_vehicle_select'));

    // Customers and products do not depend on shift or location.
    $.get('{{ route('sw.settlements.customers') }}', function (rows) {
        var $sel = $('#sw_cs_customer');
        $.each(rows, function (i, r) {
            $sel.append($('<option>', { value: r.id, text: r.text }));
        });
        swInitCreditSaleSearch($sel);
        $sel.trigger('change.select2');
    });

    $.get('{{ route('sw.settlements.credit-products') }}', function (rows) {
        var $sel = $('#sw_cs_product');
        $.each(rows, function (i, r) {
            $sel.append($('<option>', {
                value: r.variation_id,
                text: (r.sku ? r.sku + '  ·  ' : '') + r.name,
                'data-product-id': r.product_id,
                'data-price': r.unit_price
            }));
        });
        swInitCreditSaleSearch($sel);
        $sel.trigger('change.select2');
    });

    // The vehicle dropdown follows the selected customer - from Customer References.
    // A separate manual field remains available exactly like the reference form.
    $('#sw_cs_customer').on('change', function () {
        var contactId = $(this).val();
        var $sel = $('#sw_cs_vehicle_select');

        $sel.empty();
        $('#sw_cs_vehicle').val('');

        if (!contactId) {
            $sel.append($('<option>', {
                value: '',
                text: '{{ __('sw::lang.choose_a_customer_first') }}'
            }));
            swInitCreditSaleSearch($sel);
            return;
        }

        $sel.append($('<option>', {
            value: '',
            text: '{{ __('messages.please_select') }}'
        }));

        $.get('{{ route('sw.settlements.customer-vehicles') }}', { contact_id: contactId },
            function (rows) {
                if ((rows || []).length) {
                    $.each(rows, function (i, v) {
                        $sel.append($('<option>', { value: v, text: v }));
                    });
                } else {
                    $sel.append($('<option>', {
                        value: '',
                        text: 'No references available',
                        disabled: true
                    }));
                }
                swInitCreditSaleSearch($sel);
            }
        ).fail(function () {
            swInitCreditSaleSearch($sel);
        });
    });

    $('#sw_cs_vehicle_select').on('change', function () {
        var selectedVehicle = $.trim($(this).val() || '');
        if (selectedVehicle) {
            $('#sw_cs_vehicle').val(selectedVehicle);
        }
    });

    /*
     | A vehicle number not on the list is saved to the customer.
     |
     | 8047: "User to add a new vehicle Number if not in the dropdown list. When
     | added, need to show instantly in the field." Written to Customer
     | References, so it is there next time and on every other screen too.
    */
    $('#sw_cs_save_vehicle').on('click', function () {
        var contactId = $('#sw_cs_customer').val();
        var vehicle = $.trim($('#sw_cs_vehicle').val());

        if (!contactId) { toastr.error('{{ __('sw::lang.choose_a_customer') }}'); return; }
        if (!vehicle) { toastr.error('{{ __('sw::lang.enter_a_vehicle_no') }}'); return; }

        $.post('{{ route('sw.settlements.add-vehicle') }}', {
            _token: $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val(),
            contact_id: contactId,
            vehicle_no: vehicle
        }, function (r) {
            if (r && r.success) {
                var $sel = $('#sw_cs_vehicle_select');
                if (!$sel.find('option').filter(function () {
                    return String($(this).val()).toLowerCase() === vehicle.toLowerCase();
                }).length) {
                    $sel.append($('<option>', { value: vehicle, text: vehicle }));
                }
                $sel.val(vehicle);
                swInitCreditSaleSearch($sel);
                $sel.val(vehicle).trigger('change.select2');
                toastr.success('{{ __('sw::lang.vehicle_saved') }}');
            }
        });
    });

    $('#sw_cs_product').on('change', function () {
        var $opt = $(this).find('option:selected');
        $('#sw_cs_price').val(f2($opt.data('price')));
        swCsRecalc();
    });

    /*
     | 8047, exactly:
     |     Before = Qty * Unit price
     |     After  = [Qty * Unit price] - [Qty * Unit Discount]
     |
     | The discount is PER UNIT. Applying it to the line total would give a
     | different answer on every quantity above one.
    */
    function swCsRecalc() {
        var qty = n($('#sw_cs_qty').val());
        var price = Math.max(0, n($('#sw_cs_price').val()));
        var disc = Math.max(0, n($('#sw_cs_disc').val()));

        // A unit discount can never exceed the unit price. The same clamp is
        // repeated server-side before the settlement is posted.
        var appliedUnitDiscount = Math.min(disc, price);
        var before = qty * price;
        var discountAmount = qty * appliedUnitDiscount;
        var after = before - discountAmount;

        $('#sw_cs_before').val(f2(before));
        $('#sw_cs_discount_amount').val(f2(discountAmount));
        $('#sw_cs_after').val(f2(after));
    }

    $('#sw_cs_qty, #sw_cs_price, #sw_cs_disc').on('input', swCsRecalc);

    $('#sw_cs_add').on('click', function () {
        var contactId = $('#sw_cs_customer').val();
        var $opt = $('#sw_cs_product').find('option:selected');

        if (!contactId) { toastr.error('{{ __('sw::lang.choose_a_customer') }}'); return; }
        if (!$('#sw_cs_product').val()) { toastr.error('{{ __('sw::lang.choose_a_product') }}'); return; }

        var qty = n($('#sw_cs_qty').val());
        if (qty <= 0) { toastr.error('{{ __('sw::lang.enter_a_quantity') }}'); return; }

        var price = Math.max(0, n($('#sw_cs_price').val()));
        var disc = Math.max(0, n($('#sw_cs_disc').val()));
        var appliedUnitDiscount = Math.min(disc, price);
        var before = qty * price;
        var discountAmount = qty * appliedUnitDiscount;

        swCreditSales.push({
            key: ++swCsSeq,
            contact_id: contactId,
            customer_name: $('#sw_cs_customer option:selected').text(),
            order_no: $('#sw_cs_order_no').val() || '',
            order_date: $('#sw_cs_order_date').val() || '',
            vehicle_no: $('#sw_cs_vehicle').val() || '',
            product_id: $opt.data('product-id'),
            variation_id: $('#sw_cs_product').val(),
            product_name: $opt.text(),
            quantity: qty,
            unit_price: price,
            unit_discount: appliedUnitDiscount,
            amount_before_discount: before,
            credit_discount_amount: discountAmount,
            amount: before - discountAmount,
            note: $('#sw_cs_note').val() || '',
            daily_credit_sale_id: null
        });

        swRenderCreditSales();

        /*
         | Customer, order number and order date STAY - 8047.
         |
         | One delivery note usually covers several products, and retyping the
         | customer for each line is the commonest thing to get wrong.
        */
        $('#sw_cs_product').val('').trigger('change.select2');
        $('#sw_cs_qty').val('0.000');
        $('#sw_cs_price, #sw_cs_before, #sw_cs_discount_amount, #sw_cs_after').val('');
        $('#sw_cs_disc').val('0.00');
    });

    $(document).on('click', '.sw-cs-remove', function () {
        var key = parseInt($(this).data('key'), 10);
        swCreditSales = swCreditSales.filter(function (l) { return l.key !== key; });
        swRenderCreditSales();
    });

    // Edit puts a line back into the form, and removes it from the table -
    // adding it again replaces it.
    $(document).on('click', '.sw-cs-edit', function () {
        var key = parseInt($(this).data('key'), 10);
        var line = swCreditSales.filter(function (l) { return l.key === key; })[0];

        if (!line) { return; }

        $('#sw_cs_customer').val(line.contact_id).trigger('change');
        $('#sw_cs_order_no').val(line.order_no);
        $('#sw_cs_order_date').val(line.order_date);
        $('#sw_cs_vehicle').val(line.vehicle_no);
        $('#sw_cs_vehicle_select').val(line.vehicle_no).trigger('change.select2');
        $('#sw_cs_product').val(line.variation_id).trigger('change.select2');
        $('#sw_cs_qty').val(line.quantity);
        $('#sw_cs_price').val(line.unit_price);
        $('#sw_cs_disc').val(line.unit_discount);
        $('#sw_cs_note').val(line.note);
        swCsRecalc();

        swCreditSales = swCreditSales.filter(function (l) { return l.key !== key; });
        swRenderCreditSales();
    });

    /*
     | Credit sales already on the Daily Credit Sales tab.
     |
     | 8047: autoload them for the operator and shifts being settled. Loaded as
     | editable lines rather than a read-only total, because 8047 gives the user
     | Edit and Delete over them.
    */
    window.swLoadDailyCreditSales = function (shiftIds, operatorId) {
        var requestSeq = ++swDailyCreditSalesRequestSeq;

        // Daily Credit Sales are authoritative for the CURRENT selected shift
        // set. Remove the previous shift's daily rows before loading the new
        // response; keep only manual credit rows entered on this settlement.
        swCreditSales = swCreditSales.filter(function (line) {
            return !line.daily_credit_sale_id;
        });
        swRenderCreditSales();

        if (!shiftIds || !shiftIds.length) {
            return $.Deferred().reject().promise();
        }

        var request = $.get('{{ route('sw.settlements.daily-credit-sales') }}', {
            shift_ids: shiftIds,
            pump_operator_id: operatorId
        });

        request.done(function (rows) {
            if (requestSeq !== swDailyCreditSalesRequestSeq) { return; }

            // Only those not already carried, so choosing shifts twice does not
            // double them.
            var have = {};
            $.each(swCreditSales, function (i, l) {
                if (l.daily_credit_sale_id) { have[l.daily_credit_sale_id] = true; }
            });

            $.each(rows, function (i, r) {
                if (have[r.daily_credit_sale_id]) { return; }

                swCreditSales.push({
                    key: ++swCsSeq,
                    contact_id: r.contact_id,
                    customer_name: r.customer_name || '',
                    order_no: r.order_no || '',
                    order_date: r.order_date || '',
                    vehicle_no: r.vehicle_no || '',
                    product_id: r.product_id || null,
                    variation_id: r.variation_id || null,
                    product_name: r.product_name || '',
                    quantity: n(r.quantity),
                    unit_price: n(r.unit_price),
                    unit_discount: n(r.unit_discount),
                    amount_before_discount: n(r.amount_before_discount || r.total_before_discount || r.amount),
                    credit_discount_amount: n(r.credit_discount_amount || r.total_discount),
                    amount: n(r.amount),
                    note: r.note || '',
                    daily_credit_sale_id: r.daily_credit_sale_id
                });
            });

            swRenderCreditSales();
        }).fail(function () {
            if (requestSeq !== swDailyCreditSalesRequestSeq) { return; }
            });

        return request;
    };

    function swRenderCreditSales() {
        var $body = $('#sw_cs_rows');

        if (!swCreditSales.length) {
            $body.html('<tr class="sw-cs-empty"><td colspan="13" class="text-center text-muted" '
                + 'style="padding:16px">{{ __('sw::lang.no_credit_sales_yet') }}</td></tr>');
            $('#sw_cs_hidden_inputs').empty();
            $('#sw_cs_total, #sw_cs_head_total').text('0.00');
            $('#sw_cs_total_input').val('0');
            if (typeof window.swRecalcPayments === 'function') { window.swRecalcPayments(); }
            $(document).trigger('sw:settlement-draft-changed');
            return;
        }

        var html = '';
        var hiddenHtml = '';
        var total = 0;

        $.each(swCreditSales, function (i, l) {
            total += l.amount;
            var base = 'credit_sales[' + i + ']';

            html += '<tr' + (l.daily_credit_sale_id ? ' class="sw-cs-from-daily"' : '') + '>'
                + '<td>' + esc(l.customer_name) + '</td>'
                + '<td>' + esc(l.order_no) + '</td>'
                + '<td>' + esc(l.order_date) + '</td>'
                + '<td>' + esc(l.vehicle_no) + '</td>'
                + '<td>' + esc(l.product_name) + '</td>'
                + '<td class="text-right">' + f3(l.quantity) + '</td>'
                + '<td class="text-right">' + f2(l.unit_price) + '</td>'
                + '<td class="text-right">' + f2(l.unit_discount) + '</td>'
                + '<td class="text-right">' + f2(l.amount_before_discount) + '</td>'
                + '<td class="text-right">' + f2(l.credit_discount_amount != null ? l.credit_discount_amount : (n(l.amount_before_discount) - n(l.amount))) + '</td>'
                + '<td class="text-right">' + f2(l.amount) + '</td>'
                + '<td>' + esc(l.note) + '</td>'
                + '<td class="text-center ws-nowrap">'
                +   '<button type="button" class="btn btn-default btn-xs sw-cs-edit" '
                +   'data-key="' + l.key + '"><i class="fa fa-pencil"></i></button> '
                +   '<button type="button" class="btn btn-danger btn-xs sw-cs-remove" '
                +   'data-key="' + l.key + '"><i class="fa fa-times"></i></button>'
                + '</td>'
                + '</tr>';

            hiddenHtml += '<input type="hidden" name="' + base + '[contact_id]" value="' + l.contact_id + '">'
                + '<input type="hidden" name="' + base + '[order_no]" value="' + esc(l.order_no) + '">'
                + '<input type="hidden" name="' + base + '[order_date]" value="' + esc(l.order_date) + '">'
                + '<input type="hidden" name="' + base + '[vehicle_no]" value="' + esc(l.vehicle_no) + '">'
                + '<input type="hidden" name="' + base + '[product_id]" value="' + (l.product_id || '') + '">'
                + '<input type="hidden" name="' + base + '[variation_id]" value="' + (l.variation_id || '') + '">'
                + '<input type="hidden" name="' + base + '[quantity]" value="' + l.quantity + '">'
                + '<input type="hidden" name="' + base + '[unit_price]" value="' + l.unit_price + '">'
                + '<input type="hidden" name="' + base + '[unit_discount]" value="' + l.unit_discount + '">'
                + '<input type="hidden" name="' + base + '[amount_before_discount]" value="' + l.amount_before_discount + '">'
                + '<input type="hidden" name="' + base + '[credit_discount_amount]" value="' + (l.credit_discount_amount != null ? l.credit_discount_amount : (n(l.amount_before_discount) - n(l.amount))) + '">'
                + '<input type="hidden" name="' + base + '[amount]" value="' + l.amount + '">'
                + '<input type="hidden" name="' + base + '[note]" value="' + esc(l.note) + '">'
                + '<input type="hidden" name="' + base + '[daily_credit_sale_id]" value="' + (l.daily_credit_sale_id || '') + '">';
        });

        $body.html(html);
        $('#sw_cs_hidden_inputs').html(hiddenHtml);
        $('#sw_cs_total').text(f2(total));
        $('#sw_cs_head_total').text(f2(total));
        $('#sw_cs_total_input').val(total.toFixed(2));

        if (typeof window.swRecalcPayments === 'function') { window.swRecalcPayments(); }
        $(document).trigger('sw:settlement-draft-changed');
    }

    $(document).on('sw:settlement-before-submit.swCreditSales', swRenderCreditSales);

    window.swSettlementDraftGetCreditSales = function () {
        return JSON.parse(JSON.stringify(swCreditSales));
    };

    window.swSettlementDraftSetCreditSales = function (rows) {
        swCreditSales = Array.isArray(rows) ? JSON.parse(JSON.stringify(rows)) : [];
        swCsSeq = 0;
        $.each(swCreditSales, function (i, row) {
            row.key = parseInt(row.key, 10) || (++swCsSeq);
            swCsSeq = Math.max(swCsSeq, row.key);
        });
        swRenderCreditSales();
    };

    window.swCreditSalesTotal = function () {
        var t = 0;
        $.each(swCreditSales, function (i, l) { t += l.amount; });
        return t;
    };

});
</script>
