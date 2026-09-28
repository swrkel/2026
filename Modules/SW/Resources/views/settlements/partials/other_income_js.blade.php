{{-- Other Income behaviour — 8045. --}}
<script>
$(function () {

    var swOtherIncome = [];
    var swOiSeq = 0;
    var swOiPriceUnlocked = false;

    function n(v) { return parseFloat(v) || 0; }
    function f2(v) { return n(v).toFixed(2); }
    function f3(v) { return n(v).toFixed(3); }

    // Services do not depend on location or shift, so they load once.
    $.get('{{ route('sw.settlements.services') }}', function (rows) {
        var $sel = $('#sw_oi_service');
        $.each(rows, function (i, r) {
            $sel.append($('<option>', {
                value: r.product_id,
                text: (r.sku ? r.sku + '  ·  ' : '') + r.name
            }));
        });
    });

    $('#sw_oi_service').on('change', function () {
        var productId = $(this).val();

        $('#sw_oi_amount').val('').prop('readonly', true);
        swOiPriceUnlocked = false;
        $(this).data('details', null);

        if (!productId) { return; }

        $.get('{{ route('sw.settlements.service-details') }}', { product_id: productId }, function (d) {
            if (!d || !d.found) { return; }

            $('#sw_oi_service').data('details', d);
            $('#sw_oi_amount').val(f2(d.amount));
        });
    });

    /*
     | The Edit button unlocks the amount.
     |
     | The button only exists for a permitted user - the server does not render
     | it otherwise - and the permission is checked again on save. Unlocking in
     | the browser is a convenience, not the control.
    */
    $('#sw_oi_edit_price').on('click', function () {
        var $amount = $('#sw_oi_amount');

        if (!$('#sw_oi_service').val()) {
            toastr.error('{{ __('sw::lang.choose_a_service') }}');
            return;
        }

        swOiPriceUnlocked = true;
        $amount.prop('readonly', false).focus().select();
        $(this).addClass('active');
    });

    $('#sw_oi_add').on('click', function () {
        var d = $('#sw_oi_service').data('details');

        if (!d || !$('#sw_oi_service').val()) {
            toastr.error('{{ __('sw::lang.choose_a_service') }}');
            return;
        }

        var qty = n($('#sw_oi_qty').val());

        if (qty <= 0) {
            toastr.error('{{ __('sw::lang.enter_a_quantity') }}');
            return;
        }

        var amount = n($('#sw_oi_amount').val());

        // Whether this line's price was changed from the service's own, and by
        // whom - a price that differs should be traceable to a person.
        var edited = swOiPriceUnlocked && Math.abs(amount - n(d.amount)) > 0.005;

        swOtherIncome.push({
            key: ++swOiSeq,
            product_id: d.product_id,
            name: d.name,
            sku: d.sku || '',
            details: $('#sw_oi_details').val() || '',
            quantity: qty,
            rate: amount,
            amount: qty * amount,
            price_edited: edited ? 1 : 0
        });

        swRenderOtherIncome();

        $('#sw_oi_service').val('').trigger('change.select2').data('details', null);
        $('#sw_oi_details').val('');
        $('#sw_oi_qty').val('1.000');
        $('#sw_oi_amount').val('').prop('readonly', true);
        $('#sw_oi_edit_price').removeClass('active');
        swOiPriceUnlocked = false;
    });

    $(document).on('click', '.sw-oi-remove', function () {
        var key = parseInt($(this).data('key'), 10);
        swOtherIncome = swOtherIncome.filter(function (l) { return l.key !== key; });
        swRenderOtherIncome();
    });

    function swRenderOtherIncome() {
        var $body = $('#sw_oi_rows');

        if (!swOtherIncome.length) {
            $body.html('<tr class="sw-oi-empty"><td colspan="7" class="text-center text-muted" '
                + 'style="padding:16px">{{ __('sw::lang.no_other_income_yet') }}</td></tr>');
            $('#sw_oi_hidden_inputs').empty();
            $('#sw_oi_total, #sw_oi_head_total').text('0.00');
            $('#sw_oi_total_input').val('0');
            if (typeof window.swRecalcPayments === 'function') { window.swRecalcPayments(); }
            $(document).trigger('sw:settlement-draft-changed');
            return;
        }

        var html = '';
        var hiddenHtml = '';
        var total = 0;

        $.each(swOtherIncome, function (i, l) {
            total += l.amount;
            var base = 'other_income[' + i + ']';

            html += '<tr>'
                + '<td>' + l.sku + '</td>'
                + '<td>' + l.name
                +   (l.price_edited
                        ? ' <i class="fa fa-pencil text-muted" title="{{ __('sw::lang.price_was_edited') }}"></i>'
                        : '')
                + '</td>'
                + '<td>' + $('<div>').text(l.details).html() + '</td>'
                + '<td class="text-right">' + f3(l.quantity) + '</td>'
                + '<td class="text-right">' + f2(l.rate) + '</td>'
                + '<td class="text-right">' + f2(l.amount) + '</td>'
                + '<td class="text-center">'
                +   '<button type="button" class="btn btn-danger btn-xs sw-oi-remove" '
                +   'data-key="' + l.key + '"><i class="fa fa-times"></i></button>'
                + '</td>'
                + '</tr>';

            hiddenHtml += '<input type="hidden" name="' + base + '[product_id]" value="' + l.product_id + '">'
                + '<input type="hidden" name="' + base + '[details]" value="' + $('<div>').text(l.details).html() + '">'
                + '<input type="hidden" name="' + base + '[quantity]" value="' + l.quantity + '">'
                + '<input type="hidden" name="' + base + '[rate]" value="' + l.rate + '">'
                + '<input type="hidden" name="' + base + '[amount]" value="' + l.amount + '">'
                + '<input type="hidden" name="' + base + '[price_edited]" value="' + l.price_edited + '">';
        });

        $body.html(html);
        $('#sw_oi_hidden_inputs').html(hiddenHtml);
        $('#sw_oi_total').text(f2(total));
        $('#sw_oi_head_total').text(f2(total));
        $('#sw_oi_total_input').val(total.toFixed(2));

        if (typeof window.swRecalcPayments === 'function') { window.swRecalcPayments(); }
        $(document).trigger('sw:settlement-draft-changed');
    }

    $(document).on('sw:settlement-before-submit.swOtherIncome', swRenderOtherIncome);

    window.swSettlementDraftGetOtherIncome = function () {
        return JSON.parse(JSON.stringify(swOtherIncome));
    };

    window.swSettlementDraftSetOtherIncome = function (rows) {
        swOtherIncome = Array.isArray(rows) ? JSON.parse(JSON.stringify(rows)) : [];
        swOiSeq = 0;
        $.each(swOtherIncome, function (i, row) {
            row.key = parseInt(row.key, 10) || (++swOiSeq);
            swOiSeq = Math.max(swOiSeq, row.key);
        });
        swRenderOtherIncome();
    };

    window.swOtherIncomeTotal = function () {
        var t = 0;
        $.each(swOtherIncome, function (i, l) { t += l.amount; });
        return t;
    };

});
</script>
