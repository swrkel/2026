{{-- Meter Sales behaviour — 8043. --}}
<script>
$(function () {

    var swMeterLines = [];   // rows held here until the settlement is saved
    var swMeterSeq = 0;
    var swPumpChoices = []; // complete location/operator pump list from the server
    var swPumpLoadSeq = 0;  // prevents an older AJAX response restoring stale pumps
    var swMeterShiftContextKey = null; // exact selected SW Shift set that owns the current meter rows

    function n(v) { return parseFloat(v) || 0; }
    function f2(v) { return n(v).toFixed(2); }
    function f3(v) { return n(v).toFixed(3); }

    function swMeterContextKey(shiftIds) {
        return (shiftIds || [])
            .map(function (id) { return String(id || '').trim(); })
            .filter(function (id) { return id !== ''; })
            .sort(function (a, b) { return parseInt(a, 10) - parseInt(b, 10); })
            .join(',');
    }

    /*
     | A Meter Sale row belongs to the exact selected SW Shift set. If the user
     | changes shifts/operator/location after entering pumps, carrying those rows
     | forward would recreate the cross-shift preview problem we are preventing.
     | Clear only the Meter Sale draft rows; the new shift will load its own pump
     | choices immediately afterwards.
    */
    window.swSettlementMeterShiftContextChanged = function (shiftIds) {
        var nextKey = swMeterContextKey(shiftIds);

        // First authoritative shift selection (including draft restore): adopt it
        // without deleting the draft that was restored for that same selection.
        if (swMeterShiftContextKey === null) {
            swMeterShiftContextKey = nextKey;
            return;
        }

        if (nextKey === swMeterShiftContextKey) {
            return;
        }

        swMeterShiftContextKey = nextKey;
        if (!swMeterLines.length) {
            return;
        }

        swMeterLines = [];
        swRenderMeterLines();
        if (window.toastr) {
            toastr.info('Meter Sale rows were cleared because the selected SW Shift changed.');
        }
    };

    // IS2201: Pump No is a real searchable/scrollable Select2, not a plain
    // dropdown. minimumResultsForSearch=0 keeps the search box visible even
    // when the location has only a few pumps.
    function swInitPumpSearch() {
        var $sel = $('#sw_ms_pump');
        if (!$sel.length || !$.fn.select2) { return; }

        if ($sel.hasClass('select2-hidden-accessible')) {
            $sel.select2('destroy');
        }

        $sel.select2({
            width: '100%',
            minimumResultsForSearch: 0,
            dropdownAutoWidth: false,
            placeholder: '{{ __('messages.please_select') }}',
            allowClear: true
        });
    }

    swInitPumpSearch();

    /*
     | S736 - one Meter Sales line per pump.
     |
     | Keep the server's complete pump list in memory and render the Select2
     | from that list after excluding pumps already present in swMeterLines.
     | Removing a line therefore makes its pump available again immediately,
     | without another server request. Pump IDs are compared as strings because
     | Select2 values are strings even when the API returns integer IDs.
    */
    function swUsedPumpIds() {
        var used = {};
        $.each(swMeterLines, function (i, line) {
            var id = String((line && line.pump_id) || '');
            if (id) { used[id] = true; }
        });
        return used;
    }

    function swRenderPumpChoices(preferredId) {
        var $sel = $('#sw_ms_pump');
        if (!$sel.length) { return; }

        var used = swUsedPumpIds();
        var current = preferredId !== undefined
            ? String(preferredId || '')
            : String($sel.val() || '');

        // A pump becomes unavailable the moment its line is added.
        if (current && used[current]) { current = ''; }

        if ($sel.hasClass('select2-hidden-accessible') && $.fn.select2) {
            $sel.select2('destroy');
        }

        $sel.empty().append($('<option>', {
            value: '',
            text: '{{ __('messages.please_select') }}'
        }));

        var seen = {};
        $.each(swPumpChoices || [], function (i, r) {
            if (!r || r.id === undefined || r.id === null) { return; }

            var id = String(r.id);
            if (!id || seen[id] || used[id]) { return; }
            seen[id] = true;

            $sel.append($('<option>', {
                value: id,
                text: (r.pump_no || ('Pump #' + id))
                    + (r.product_name ? '  ·  ' + r.product_name : '')
            }));
        });

        if (current && $sel.find('option[value="' + current.replace(/"/g, '\\"') + '"]').length) {
            $sel.val(current);
        } else {
            $sel.val('');
        }

        swInitPumpSearch();
        $sel.prop('disabled', false).trigger('change.select2');
    }

    /*
     | The pump list follows the location.
     |
     | Pumps belong to a location, so choosing a different one must not leave
     | the previous location's pumps on offer - a settlement posted against the
     | wrong pump is very hard to unpick afterwards.
    */
    window.swLoadPumps = function (locationId) {
        var $sel = $('#sw_ms_pump');
        var loadSeq = ++swPumpLoadSeq;

        // Do not leave details from the previous location/operator attached to
        // the newly rebuilt Select2.
        $sel.prop('disabled', true).data('details', null);
        swPumpChoices = [];
        swRenderPumpChoices('');
        $sel.prop('disabled', true);
        $('#sw_ms_start, #sw_ms_close, #sw_ms_price').val('');
        $('#sw_ms_qty').val('0.000');

        if (!locationId) {
            $sel.prop('disabled', false).trigger('change.select2');
            return;
        }

        $.get('{{ route('sw.settlements.pumps') }}', {
            location_id: locationId,
            operator_id: $('#sw_st_operator').val() || '',
            shift_ids: $('#sw_st_shifts').val() || []
        })
            .done(function (rows) {
                if (loadSeq !== swPumpLoadSeq) { return; }
                swPumpChoices = Array.isArray(rows) ? rows : [];
            })
            .fail(function () {
                if (loadSeq !== swPumpLoadSeq) { return; }
                swPumpChoices = [];
            })
            .always(function () {
                if (loadSeq !== swPumpLoadSeq) { return; }
                swRenderPumpChoices('');
            });
    };

    // Choosing a pump fills in its starting meter and price.
    $('#sw_ms_pump').on('change', function () {
        var pumpId = $(this).val();

        $('#sw_ms_start, #sw_ms_price').val('');
        $('#sw_ms_close, #sw_ms_qty').val('');

        if (!pumpId) { return; }

        $.get('{{ route('sw.settlements.pump-details') }}', {
            pump_id: pumpId,
            location_id: $('#sw_st_location').val(),
            operator_id: $('#sw_st_operator').val() || '',
            shift_ids: $('#sw_st_shifts').val() || []
        }, function (d) {
            if (!d || !d.found) { return; }

            $('#sw_ms_pump').data('details', d);
            $('#sw_ms_start').val(f3(d.starting_meter));
            $('#sw_ms_price').val(f2(d.unit_price));
            swRecalcEntry();
        });
    });

    /*
     | Sold quantity is closing minus starting.
     |
     | Testing fuel is pumped and returned, so it passes the meter without being
     | sold - it is subtracted for the AMOUNT but the sold figure keeps it, which
     | is how the paper form reads.
    */
    function swRecalcEntry() {
        var start = n($('#sw_ms_start').val());
        var close = n($('#sw_ms_close').val());
        var qty = close - start;

        if (qty < 0) { qty = 0; }

        $('#sw_ms_qty').val(f3(qty));
    }

    $('#sw_ms_close, #sw_ms_start').on('input', swRecalcEntry);

    $('#sw_ms_add').on('click', function () {
        var $pump = $('#sw_ms_pump');
        var d = $pump.data('details');
        var pumpId = String($pump.val() || '');

        if (!d || !pumpId) {
            toastr.error('{{ __('sw::lang.choose_a_pump') }}');
            return;
        }

        // Defence in depth: the dropdown already hides used pumps, but reject a
        // duplicate here too in case an old browser/Select2 instance retained a
        // stale option while the page was being refreshed.
        if (swUsedPumpIds()[pumpId]) {
            toastr.error('{{ __('sw::lang.pump_already_added') }}');
            swRenderPumpChoices('');
            return;
        }

        var start = n($('#sw_ms_start').val());
        var close = n($('#sw_ms_close').val());

        if (close <= start) {
            toastr.error('{{ __('sw::lang.closing_below_starting') }}');
            return;
        }

        var testing = n($('#sw_ms_testing').val());
        var soldQty = close - start;
        var totalQty = soldQty - testing;

        if (totalQty < 0) { totalQty = 0; }

        var price = n($('#sw_ms_price').val());
        var before = totalQty * price;

        // Percentage applies per litre, not to the whole line.
        var discType = $('#sw_ms_disc_type').val();
        var discVal = n($('#sw_ms_disc').val());
        var discount = 0;

        if (discType === 'fixed') {
            // "Fixed" is the fixed discount for this sale line, not a
            // per-litre discount. Multiplying it by quantity was the cause of
            // values such as 36,875.00 - (875 x 100) = -50,625.00.
            discount = discVal;
        } else if (discType === 'percentage') {
            discount = before * (discVal / 100);
        }

        var after = before - discount;

        swMeterLines.push({
            key: ++swMeterSeq,
            pump_id: pumpId,
            pump_no: d.pump_no,
            product_id: d.product_id,
            product_name: d.product_name || '',
            product_code: d.product_code || '',
            opening_meter: start,
            closing_meter: close,
            rate: price,
            sold_qty: soldQty,
            discount_type: discType || 'fixed',
            discount_value: discVal,
            testing_qty: testing,
            quantity: totalQty,
            amount_before_discount: before,
            amount: after
        });

        swRenderMeterLines();

        // swRenderMeterLines() also refreshes the pump dropdown, so the pump
        // just added disappears immediately and cannot be selected twice.

        // The pump is cleared but the discount settings stay: consecutive pumps
        // on one shift usually carry the same terms.
        $('#sw_ms_pump').val('').trigger('change.select2');
        $('#sw_ms_pump').data('details', null);
        $('#sw_ms_start, #sw_ms_close, #sw_ms_price').val('');
        $('#sw_ms_qty').val('0.000');
        $('#sw_ms_testing').val('0.000');
    });

    $(document).on('click', '.sw-ms-remove', function () {
        var key = parseInt($(this).data('key'), 10);
        swMeterLines = swMeterLines.filter(function (l) { return l.key !== key; });
        // Re-rendering restores the removed pump to the dropdown from the cached
        // server list, while every pump still in the table remains excluded.
        swRenderMeterLines();
    });

    function swRenderMeterLines() {
        var $body = $('#sw_ms_rows');

        if (!swMeterLines.length) {
            $body.html('<tr class="sw-ms-empty"><td colspan="14" class="text-center text-muted" '
                + 'style="padding:16px">{{ __('sw::lang.no_meter_lines_yet') }}</td></tr>');
            $('#sw_ms_hidden_inputs').empty();
            swRenderPumpChoices();
            $('#sw_ms_total, #sw_meter_head_total').text('0.00');
            $('#sw_ms_product_totals').html('');
            $('#sw_meter_total_input').val('0');
            $(document).trigger('sw:settlement-draft-changed');
            return;
        }

        var html = '';
        var hiddenHtml = '';
        var total = 0;
        var byProduct = {};

        $.each(swMeterLines, function (i, l) {
            total += l.amount;

            var p = l.product_name || '—';
            byProduct[p] = (byProduct[p] || 0) + l.quantity;

            var base = 'lines[' + i + ']';

            html += '<tr>'
                + '<td>' + (l.product_code || '') + '</td>'
                + '<td>' + l.product_name + '</td>'
                + '<td>' + l.pump_no + '</td>'
                + '<td class="text-right">' + f3(l.opening_meter) + '</td>'
                + '<td class="text-right">' + f3(l.closing_meter) + '</td>'
                + '<td class="text-right">' + f2(l.rate) + '</td>'
                + '<td class="text-right">' + f3(l.sold_qty) + '</td>'
                + '<td>' + (l.discount_value ? l.discount_type : '') + '</td>'
                + '<td class="text-right">' + f2(l.discount_value) + '</td>'
                + '<td class="text-right">' + f3(l.testing_qty) + '</td>'
                + '<td class="text-right">' + f3(l.quantity) + '</td>'
                + '<td class="text-right">' + f2(l.amount_before_discount) + '</td>'
                + '<td class="text-right">' + f2(l.amount) + '</td>'
                + '<td class="text-center">'
                +   '<button type="button" class="btn btn-danger btn-xs sw-ms-remove" '
                +   'data-key="' + l.key + '"><i class="fa fa-times"></i></button>'
                + '</td>'
                + '</tr>';

            // Posted from a dedicated container outside the table. The RATE
            // travels with the line because a later fuel-price change must not
            // restate what was already sold.
            hiddenHtml += '<input type="hidden" name="' + base + '[pump_id]" value="' + l.pump_id + '">'
                + '<input type="hidden" name="' + base + '[product_id]" value="' + l.product_id + '">'
                + '<input type="hidden" name="' + base + '[opening_meter]" value="' + l.opening_meter + '">'
                + '<input type="hidden" name="' + base + '[closing_meter]" value="' + l.closing_meter + '">'
                + '<input type="hidden" name="' + base + '[testing_qty]" value="' + l.testing_qty + '">'
                + '<input type="hidden" name="' + base + '[quantity]" value="' + l.quantity + '">'
                + '<input type="hidden" name="' + base + '[rate]" value="' + l.rate + '">'
                + '<input type="hidden" name="' + base + '[discount_type]" value="' + l.discount_type + '">'
                + '<input type="hidden" name="' + base + '[discount_value]" value="' + l.discount_value + '">'
                + '<input type="hidden" name="' + base + '[amount_before_discount]" value="' + l.amount_before_discount + '">'
                + '<input type="hidden" name="' + base + '[amount]" value="' + l.amount + '">';
        });

        $body.html(html);
        $('#sw_ms_hidden_inputs').html(hiddenHtml);
        swRenderPumpChoices();

        // Quantity per product, as on the paper form: an operator checks litres
        // by fuel, not by pump.
        var summary = [];
        $.each(byProduct, function (name, qty) {
            summary.push('<span class="sw-meter-product-total">' + name + ' = ' + f3(qty) + '</span>');
        });

        // Keep the footer compact and horizontal.  The previous <br> after
        // every product made the total row unnecessarily tall and squeezed the
        // amount into the last narrow column.
        $('#sw_ms_product_totals').html(summary.join('<span class="sw-meter-total-sep"> &nbsp;|&nbsp; </span>'));
        $('#sw_ms_total').text(f2(total));
        $('#sw_meter_head_total').text(f2(total));
        $('#sw_meter_total_input').val(total.toFixed(2));

        if (typeof window.swRecalcPayments === 'function') {
            window.swRecalcPayments();
        }
        $(document).trigger('sw:settlement-draft-changed');
    }

    // Rebuild submission fields immediately before Save as a final guard
    // against a stale DOM after draft restore or asynchronous shift loading.
    $(document).on('sw:settlement-before-submit.swMeterSales', swRenderMeterLines);

    window.swSettlementDraftGetMeterLines = function () {
        return JSON.parse(JSON.stringify(swMeterLines));
    };

    window.swSettlementDraftSetMeterLines = function (rows) {
        swMeterLines = Array.isArray(rows) ? JSON.parse(JSON.stringify(rows)) : [];

        // Old autosaved drafts created before S736 may contain the same pump more
        // than once. Keep the first line only so restoring a stale draft cannot
        // re-introduce a duplicate that the new dropdown correctly forbids.
        var draftPumpIds = {};
        swMeterLines = swMeterLines.filter(function (row) {
            var id = String((row && row.pump_id) || '');
            if (!id) { return true; }
            if (draftPumpIds[id]) { return false; }
            draftPumpIds[id] = true;
            return true;
        });

        swMeterSeq = 0;
        $.each(swMeterLines, function (i, row) {
            // Recalculate old browser drafts with the corrected fixed-discount
            // rule so a stale draft cannot restore the former negative amount.
            var before = n(row.quantity) * n(row.rate);
            var discount = row.discount_type === 'percentage'
                ? before * (n(row.discount_value) / 100)
                : n(row.discount_value);
            row.amount_before_discount = before;
            row.amount = before - discount;
            row.key = parseInt(row.key, 10) || (++swMeterSeq);
            swMeterSeq = Math.max(swMeterSeq, row.key);
        });
        swRenderMeterLines();
    };

    window.swMeterSalesTotal = function () {
        var t = 0;
        $.each(swMeterLines, function (i, l) { t += l.amount; });
        return t;
    };

});
</script>
