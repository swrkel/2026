/*
 |------------------------------------------------------------------------------
 | Direct Settlement - Meter Sale (Add / Update)
 |------------------------------------------------------------------------------
 |
 | THE SINGLE OWNER of the Add and Update handlers for Direct Settlement meter
 | sales. Nothing else may bind to `.btn_meter_sale` or `.btn_update_meter_sale`.
 |
 | WHY THIS FILE EXISTS
 |
 | The same two buttons were wired up in more than one place, and the code was
 | spread across a 2,700-line view and a 1,700-line shared script, so it was not
 | possible to tell from either file which copy actually ran.
 |
 | The measured state before this change - counting only bindings that are NOT
 | inside a comment block:
 |
 |     app.js              Add: 0   Update: 1   (line 1333)
 |     create.blade.php    Add: 1   Update: 1   (inline, ran .off() first)
 |     edit.blade.php      Add: 0   Update: 0
 |
 | app.js also carries a commented-out Add handler (284-485) and a second
 | commented-out Update handler (1390-1518). They are dead, but they are the
 | reason the file reads as though several handlers compete: only one does.
 |
 | So the real problem was not double-firing. It was DIVERGENCE:
 |
 |   * the Create screen ran the inline Update handler, because the inline block
 |     called .off('click', '.btn_update_meter_sale') before binding, discarding
 |     the app.js one;
 |   * the Edit screen, which has no inline block, ran the app.js Update handler
 |     instead - a different implementation of the same action.
 |
 | Two screens, one button, two behaviours, and no way to see that from either
 | file. This file ends that by holding one implementation in one place.
 |
 | WHICH IMPLEMENTATION
 |   The Create screen's, verbatim. It is the one that has actually been running
 |   in production, so Create behaviour is unchanged by the move.
 |
 | SCOPE OF THIS CHANGE
 |   Loaded by the Create screen only, which makes this a pure refactor with no
 |   behaviour change anywhere. Pointing the Edit screen at this file as well -
 |   and deleting app.js:1333 - is the obvious next step and is what finally
 |   removes the divergence, but it CHANGES what Edit does today and must be
 |   tested on a real settlement first. See FIX_NOTES.
 |
 | RULES
 |   * Load AFTER app.js: this calls helpers defined there
 |     (refresh_settlement_totals, calculate_payment_tab_total, __number_f,
 |     formatNumber, updateTotalSoldQty, sum_table_col).
 |   * Self-guarding: does nothing unless #meter_sale_table is on the page, so
 |     including it on an unrelated screen is harmless.
 |   * It calls .off() for both selectors before binding, so it is idempotent
 |     and always wins regardless of load order.
 |   * The `_pd` variants belong to Petro PD and are deliberately NOT handled
 |     here.
 |
 | IS1939.
 */
(function () {
    /*
     |--------------------------------------------------------------------------
     | Ownership guard - do not weaken this.
     |--------------------------------------------------------------------------
     |
     | These five class names are NOT unique to Petro Direct. Eight modules
     | render buttons with the same names:
     |
     |     PetroDirect, Petro, PetroGeneral, PetroPD, PumperDashboard,
     |     SettlementSW, Vat, EVCharging
     |
     | and the handlers below are delegated from `document`, so they would act on
     | any of those modules' buttons if this file ever ran on their pages - and
     | post them to /petrodirect/settlement/save-meter-sale.
     |
     | The guard used to be `if (!$('#meter_sale_table').length) return;`. That id
     | is not unique either - SettlementSW, PetroPD and DailyCollectionSW all use
     | it - so it did not actually identify a Petro Direct page. Nothing was
     | broken by that only because this file happens to be loaded by just two
     | views. That is a property of the script tags, not of this file, and a
     | single copied <script> line would have been enough to break four modules.
     |
     | The page must now say so explicitly. Both settlement/create.blade.php and
     | settlement/edit.blade.php set the flag immediately before loading this
     | file; nothing else does, and nothing else can do so by accident.
     */
    if (!window.PETRODIRECT_DIRECT_SETTLEMENT) return;
    if (!$('#meter_sale_table').length) return;

    var $doc = $(document);
    var saveMeterSaleUrl = '/petrodirect/settlement/save-meter-sale';

    function getCurrentShiftIdValue() {
        var value = $('#shift_number').val();
        return Array.isArray(value) ? (value || []).join(',') : (value || '').toString();
    }

    function getCurrentDirectShiftNumber() {
        return typeof window.getSelectedDirectSettlementShiftNumber === 'function'
            ? window.getSelectedDirectSettlementShiftNumber()
            : '';
    }

    // Shared: apply server result (table_html + DataTable.reload + totals). Used by both Add and Update.
    function applyMeterSaleSuccessResult(result, pump_id) {
        $('.meter_sale_fields').val('');
        $('.testing_qty').val(0);
        $('#pump_no').removeAttr('data-selected-pump-id data-selected-pump-text data-selected-pump-context');
        $('#meter_sale_selected_pump_id, #meter_sale_selected_pump_text, #meter_sale_selected_pump_context').val('');
        window.__directSettlementPumpSyncing = true;
        try {
            $('#pump_no').val('').trigger('change');
        } finally {
            window.__directSettlementPumpSyncing = false;
        }

        // S676: once a pump is added to this Direct Settlement it must not be
        // selectable again. Prefer the authoritative server-filtered list; the
        // fallback removes the just-added option immediately for older responses.
        if (result && result.pump_nos && typeof updatePumpDropdown === 'function') {
            updatePumpDropdown(result.pump_nos, { preserveSelection: false });
        } else if (pump_id) {
            $('#pump_no option[value="' + pump_id + '"]').remove();
            $('#pump_no').val('').trigger('change.select2');
        }

        var tableHtml = (result && result.table_html) ? result.table_html : '';
        if (typeof tableHtml === 'string' && tableHtml.length > 0) {
            var $wrap = $('<div>').append($.parseHTML(tableHtml));
            var $newTable = $wrap.find('table#meter_sale_table');
            if ($newTable.length) {
                var newTbody = $newTable.children('tbody').html();
                var newTfoot = $newTable.children('tfoot').html();
                if (newTbody != null) $('#meter_sale_table').children('tbody').html(newTbody);
                if (newTfoot != null) $('#meter_sale_table').children('tfoot').html(newTfoot);
                var $totalInput = $newTable.find('input#meter_sale_total');
                if ($totalInput.length && $totalInput.val()) {
                    $('#meter_sale_total').val($totalInput.val());
                    if (typeof calculate_payment_tab_total === 'function') calculate_payment_tab_total();
                }

                // Show the static table immediately (no loading spinner) and hide the DataTable wrapper
                $('#outside_meter_sale_table').hide();
                $('#meter_sale_table').show();
            }
        }

        // Silently sync the DataTable in the background so it stays up-to-date if re-shown
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#pump_operator_meter_sale_table')) {
            $('#pump_operator_meter_sale_table').DataTable().ajax.reload(null, false);
        }
        if ($('#meter_sale_table').length && typeof sum_table_col === 'function') {
            var total = sum_table_col($('#meter_sale_table'), 'discount_amount');
            if (!isNaN(total)) {
                $('#meter_sale_total').val(total);
                if (typeof calculate_payment_tab_total === 'function') calculate_payment_tab_total();
            }
        }
        if (typeof refresh_settlement_totals === 'function') refresh_settlement_totals();
        if (typeof updateTotalSoldQty === 'function') updateTotalSoldQty();
    }

    /*
     * Namespaced. An unnamespaced .off('click', '.btn_update_meter_sale') removes
     * EVERY delegated click handler for that selector, including the ones the
     * other seven modules bind for their own buttons. It only needed to be that
     * blunt while app.js still carried a rival copy; that copy is now deleted, so
     * this removes nothing but its own previous binding and stays idempotent.
     */
    /*
     * Unnamespaced .off() on purpose: app.js is deliberately NOT modified by this
     * change (another developer is working in it), so its legacy handler for this
     * selector is still bound and has to be displaced.
     *
     * This is safe here ONLY because of the ownership flag above. It cannot run
     * on another module's page, so it cannot remove another module's handler.
     * If the flag is ever weakened, namespace these two calls again.
     */
    $doc.off('click', '.btn_update_meter_sale');
    $doc.on('click.pdDirectMeterSaleUpdate', '.btn_update_meter_sale', function () {
        var url = $(this).data('href');
        var tr = (typeof __meter_sale_edit_tr !== 'undefined' && __meter_sale_edit_tr) ? __meter_sale_edit_tr : $(this).closest('tr');
        var is_edit = $('#is_edit').val() || 0;
        var pump_id = $('#pump_no').val();
        var form_start = $('#pump_starting_meter').val();
        var form_close = $('#pump_closing_meter').val();
        var form_price = $('#meter_sale_unit_price').val();
        var testing_qty = $('#testing_qty').val() || 0;
        // Sold Qty field is already chargeable (Closing - Starting - Testing); do not subtract testing again
        var sold_qty = parseFloat($('#sold_qty').val()) || 0;
        var total_qty = sold_qty + (parseFloat(testing_qty) || 0);
        var meter_sale_discount = $('#meter_sale_discount').val() || 0;
        var meter_sale_discount_type = $('#meter_sale_discount_type').val() || 'fixed';
        var price = (typeof price !== 'undefined') ? price : parseFloat($('#meter_sale_unit_price').val() || 0);
        var sub_total = parseFloat(sold_qty) * parseFloat(price);
        var meter_sale_discount_amount = sub_total - (typeof calculate_discount === 'function' ? calculate_discount(meter_sale_discount_type, meter_sale_discount, sub_total) : 0);

        $.ajax({
            method: 'post',
            url: url,
            dataType: 'json',
            data: {
                pump_id: pump_id,
                starting_meter: form_start,
                closing_meter: form_close,
                product_id: $('#meter_sale_product_id').val() || (typeof product_id !== 'undefined' ? product_id : ''),
                price: form_price,
                qty: sold_qty,
                discount: meter_sale_discount,
                discount_type: meter_sale_discount_type,
                discount_amount: meter_sale_discount_amount,
                testing_qty: testing_qty,
                sub_total: sub_total,
                is_edit: is_edit,
                is_from_pumper: $('#is_from_pumper').val() || 0,
                assignment_id: $('#assignment_id').val() || 0,
                pumper_entry_id: $('#pumper_entry_id').val() || 0,
                mechanical_last_meter: $('#mechanical_last_meter').val() || '',
                mechanical_digital_last_meter: $('#mechanical_digital_last_meter').val() || '',
                mechanical_meter_difference: $('#mechanical_meter_difference').val() || '',
                shift_id: getCurrentShiftIdValue()
            },
            success: function (result) {
                if (!result || !result.success) {
                    toastr.error((result && result.msg) ? result.msg : 'Update failed');
                    return;
                }
                toastr.success(result.msg);
                if (typeof __meter_sale_edit_tr !== 'undefined') __meter_sale_edit_tr = null;
                applyMeterSaleSuccessResult(result, pump_id);
            }
        });
    });

    // LA-1091 urgent correction: one authoritative Meter Sale Add handler.
    // It uses a relative URL so the same code works on central and tenant domains.
    // Displaces app.js's legacy Add handler. Same reasoning as Update above.
    $doc.off('click', '.btn_meter_sale');
    $doc.on('click.la1091_meter_sale_add', '.btn_meter_sale', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();

        var $button = $(this);
        if ($button.data('meterSaleSubmitting') || window.isMeterSaleSubmitting) {
            return false;
        }

        function numberValue(value) {
            var parsed = parseFloat((value == null ? '' : value).toString().replace(/,/g, ''));
            return isNaN(parsed) ? 0 : parsed;
        }

        function resetMeterSaleSubmitState() {
            window.isMeterSaleSubmitting = false;
            $button.removeData('meterSaleSubmitting')
                .prop('disabled', false)
                .removeAttr('disabled')
                .removeClass('disabled');

            if (typeof window.refreshPetroMeterSaleAddState === 'function') {
                window.refreshPetroMeterSaleAddState();
            }
        }

        var location_id = $('#location_id').val();
        var pump_operator_id = $('#pump_operator_id').val();
        var pump_id = $('#pump_no').val();
        var starting_meter = numberValue($('#pump_starting_meter').val());
        var closing_meter = numberValue($('#pump_closing_meter').val());
        var testing_qty = Math.abs(numberValue($('#testing_qty').val()));
        var sold_qty = Math.abs(numberValue($('#sold_qty').val()));
        var selected_price = Math.abs(numberValue($('#meter_sale_unit_price').val()));
        var selected_product_id = $('#meter_sale_product_id').val()
            || (typeof product_id !== 'undefined' ? product_id : '');
        var meter_sale_discount = Math.abs(numberValue($('#meter_sale_discount').val()));
        var meter_sale_discount_type = $('#meter_sale_discount_type').val() || 'fixed';
        var sub_total = sold_qty * selected_price;
        var discount_value = 0;
        var shift_id = getCurrentShiftIdValue();
        var isBulkSaleMeter = String($('#bulk_sale_meter').val() || '0') === '1';

        if (meter_sale_discount_type === 'percentage') {
            discount_value = sub_total * (meter_sale_discount / 100);
        } else {
            discount_value = meter_sale_discount;
        }
        var meter_sale_discount_amount = Math.max(0, sub_total - discount_value);

        if (!$('#meter_sale_discount_type').val()) {
            $('#meter_sale_discount_type').val('fixed').trigger('change.select2');
        }

        if (!location_id) {
            toastr.error('Please select the Business Location first.');
            resetMeterSaleSubmitState();
            return false;
        }
        if (!pump_operator_id) {
            toastr.error('Please select the Pump Operator first.');
            resetMeterSaleSubmitState();
            return false;
        }
        if (!shift_id) {
            toastr.error('Please select or enter the Shift No first.');
            resetMeterSaleSubmitState();
            return false;
        }
        if (!pump_id) {
            toastr.error('Please select the Pump No first.');
            resetMeterSaleSubmitState();
            return false;
        }
        if (!isBulkSaleMeter && closing_meter < starting_meter) {
            toastr.error('Pump Closing Meter must be greater than or equal to the Starting Meter.');
            resetMeterSaleSubmitState();
            return false;
        }
        if (selected_price <= 0) {
            toastr.error('The selected pump price is not loaded yet. Please select the pump again.');
            resetMeterSaleSubmitState();
            return false;
        }
        if (sold_qty <= 0) {
            toastr.error('Sold Qty must be greater than zero.');
            resetMeterSaleSubmitState();
            return false;
        }

        $button.data('meterSaleSubmitting', true).prop('disabled', true).addClass('disabled');
        window.isMeterSaleSubmitting = true;

        $.ajax({
            method: 'POST',
            url: saveMeterSaleUrl,
            dataType: 'json',
            timeout: 60000,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || ''
            },
            data: {
                _token: $('meta[name="csrf-token"]').attr('content') || '',
                active_settlement_id: $('#active_settlement_id').val(),
                settlement_no: $('#settlement_no').val(),
                location_id: location_id,
                pump_operator_id: pump_operator_id,
                transaction_date: $('#transaction_date').val(),
                work_shift: $('#work_shift').val(),
                direct_shift_number: getCurrentDirectShiftNumber(),
                note: $('#note').val(),
                pump_id: pump_id,
                starting_meter: starting_meter,
                closing_meter: isBulkSaleMeter ? '' : closing_meter,
                product_id: selected_product_id,
                price: selected_price,
                qty: sold_qty,
                discount: meter_sale_discount,
                discount_type: meter_sale_discount_type,
                discount_amount: meter_sale_discount_amount,
                testing_qty: testing_qty,
                sub_total: sub_total,
                is_edit: $('#is_edit').val() || 0,
                is_from_pumper: $('#is_from_pumper').val() || 0,
                assignment_id: $('#assignment_id').val() || 0,
                pumper_entry_id: $('#pumper_entry_id').val() || 0,
                mechanical_last_meter: $('#mechanical_last_meter').val() || '',
                mechanical_digital_last_meter: $('#mechanical_digital_last_meter').val() || '',
                mechanical_meter_difference: $('#mechanical_meter_difference').val() || '',
                shift_id: shift_id
            },
            success: function (result) {
                if (!result || !result.success) {
                    toastr.error((result && (result.msg || result.message))
                        ? (result.msg || result.message)
                        : 'Unable to add the meter sale.');
                    return;
                }

                toastr.success(result.msg || 'Meter sale added successfully.');
                $('#active_settlement_id').val(result.settlement_id || 0);
                if (result.settlement_no) {
                    $('#settlement_no').val(result.settlement_no);
                }
                if (pump_operator_id && result.settlement_id) {
                    window.__directSettlementByOperator = window.__directSettlementByOperator || {};
                    window.__directSettlementByOperator[pump_operator_id] = result.settlement_id;
                }
                if (result.settlement_id && window.history && window.history.replaceState) {
                    var currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('view_settlement_id', result.settlement_id);
                    window.history.replaceState({}, '', currentUrl.toString());
                }

                applyMeterSaleSuccessResult(result, pump_id);
            },
            error: function (xhr) {
                var response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                var message = response && (response.msg || response.message)
                    ? (response.msg || response.message)
                    : 'Unable to add the meter sale. Please check the entered details and try again.';

                if (xhr && xhr.status === 419) {
                    message = 'The session has expired. Please refresh the page and try again.';
                } else if (xhr && xhr.status === 404) {
                    message = 'The Meter Sale save route is unavailable. Please clear the Laravel route cache.';
                }

                toastr.error(message);
                if (window.console && console.error) {
                    console.error('LA-1091 Meter Sale Add failed', xhr);
                }
            },
            complete: resetMeterSaleSubmitState
        });

        return false;
    });



    /* ------------------------------------------------------------------
     * IS1939: edit-form load, cancel and delete.
     *
     * These were bound in BOTH app.js and settlement/create.blade.php. The
     * view's copies won on Create because they ran .off() first; the Edit
     * screen only ever had app.js's, so the two screens behaved differently.
     * Both copies are now deleted and this is the only one.
     * ------------------------------------------------------------------ */
    function updateCancelMeterForm(url, data) {
        $.ajax({
            method: 'get',
            url: url,
            data: data,
            success: function (result) {
                if (result.success) {
                    $('#meter-sale-form-block').html(result.html);
                    $('#pump_no').select2();
                    $('#meter_sale_discount_type').select2();
                } else {
                    toastr.error(result.msg);
                }
            }
        });
    }

    $doc.off('click', '.get_meter_sale_from')
        .on('click.petro_create_meter_edit', '.get_meter_sale_from', function () {
            window.__meter_sale_edit_tr = $(this).closest('tr');
            updateCancelMeterForm($(this).data('href'), { action_type: 'edit' });
        });

    $doc.off('click', '.btn_meter_sale_cancel')
        .on('click.petro_create_meter_cancel', '.btn_meter_sale_cancel', function () {
            updateCancelMeterForm($(this).data('href'), { action_type: 'cancel' });
        });

    $doc.off('click', '.delete_meter_sale')
        .on('click.petro_create_meter_delete', '.delete_meter_sale', function () {
            var url = $(this).data('href');
            var tr = $(this).closest('tr');
            var is_edit = $('#is_edit').val() || 0;

            $.ajax({
                method: 'delete',
                url: url,
                data: {
                    is_edit: is_edit,
                    shift_id: (function () {
                        var value = $('#shift_number').val();
                        return Array.isArray(value) ? (value || []).join(',') : (value || '').toString();
                    })()
                },
                success: function (result) {
                    if (!result || !result.success) {
                        toastr.error((result && result.msg) ? result.msg : 'Delete failed');
                        return;
                    }

                    toastr.success(result.msg);
                    tr.remove();

                    var currentTotal = parseFloat(($('#meter_sale_total').val() || '0').toString().replace(/,/g, '')) || 0;
                    var removedAmount = parseFloat(result.amount || 0) || 0;
                    var meterSaleTotal = currentTotal - removedAmount;
                    $('#meter_sale_total').val(meterSaleTotal);
                    $('.meter_sale_total').text(__number_f(meterSaleTotal, false, false, __currency_precision));

                    if (result.pump_id && result.pump_name && $('#pump_no option[value="' + result.pump_id + '"]').length === 0) {
                        $('#pump_no').append('<option value="' + result.pump_id + '">' + result.pump_name + '</option>');
                    }

                    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#pump_operator_meter_sale_table')) {
                        $('#pump_operator_meter_sale_table').DataTable().ajax.reload(null, false);
                    }

                    if (typeof calculate_payment_tab_total === 'function') calculate_payment_tab_total();
                    if (typeof updateTotalSoldQty === 'function') updateTotalSoldQty();
                }
            });
        });

    $(function () {
        if (typeof window.refreshPetroMeterSaleAddState === 'function') {
            window.refreshPetroMeterSaleAddState();
            setTimeout(window.refreshPetroMeterSaleAddState, 100);
            setTimeout(window.refreshPetroMeterSaleAddState, 500);
        } else {
            $('.btn_meter_sale').prop('disabled', false).removeAttr('disabled').removeClass('disabled');
        }
    });
}) ();