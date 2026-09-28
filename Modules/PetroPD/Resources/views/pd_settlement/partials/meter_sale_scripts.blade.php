<script>
    function formatPetroPdTestingQty(value) {
        var numeric = parseFloat(String(value == null ? 0 : value).replace(/,/g, ''));
        if (isNaN(numeric)) { numeric = 0; }
        if (typeof __number_f === 'function') {
            return __number_f(numeric, false, false, 3);
        }
        return numeric.toFixed(3);
    }

    window.petroPdManualEntryPermission = @json(!empty($manual_entry_permission) ? (bool) $manual_entry_permission : auth()->user()->can('petro_pd.manual_entry'));
    window.petroPdCanEditMeterSale = @json($canEditMeterSale ?? false);
    window.petroPdCanDeleteMeterSale = @json($canDeleteMeterSale ?? false);
    const manualEntryUrl = "{{ route('petropd.get-manual-entry-meter-sales') }}";
    let petroPdMeterSaleLoadSequence = 0;
    let petroPdMeterSaleLoadRequest = null;
    let petroPdMeterSaleLoadedContext = '';
    let petroPdMeterSaleLoadingContext = '';

    function petroPdCurrentMeterSaleContext() {
        return [
            String($('#pump_operator_id').val() || ''),
            String($('#shift_number').val() || $('#shift_id').val() || ''),
            String($('#active_settlement_id').val() || ''),
            String($('#settlement_no').val() || '')
        ].join('|');
    }

    window.petroPdRefreshManualEntryButton = function () {
        const $btn = $('#btn_manual_entry');
        if (!$btn.length) return;

        const can = window.petroPdManualEntryPermission === true;
        const shiftOk = !!($('#shift_id').val() || $('#shift_number').val());
        const opOk = !!$('#pump_operator_id').val();
        const active = can && shiftOk && opOk;

        $btn.removeClass('btn-success btn-default');
        if (!can) {
            $btn.addClass('btn-default').prop('disabled', true);
            return;
        }
        $btn.prop('disabled', !active);
        $btn.addClass(active ? 'btn-success' : 'btn-default');
    };

    window.petroPdClearMeterSaleTable = function () {
        $('#meter_sale_table tbody').empty();
        $('#meter_sale_total').val('0.00');
        $('.meter_sale_total').text('0.00');

        if (typeof window.petroPdRenderShiftPaymentDetails === 'function') {
            window.petroPdRenderShiftPaymentDetails([]);
        }

        if (typeof calculate_payment_tab_total === 'function') {
            calculate_payment_tab_total();
        }
    };

    window.petroPdRenderShiftPaymentDetails = function (details) {
        const $section = $('#petropd_shift_payment_details');
        const $tbody = $('#petropd_shift_payment_details_body');
        if (!$section.length || !$tbody.length) {
            return;
        }

        const rows = Array.isArray(details) ? details : [];
        $tbody.empty();

        if (!rows.length) {
            $section.hide();
            return;
        }

        rows.forEach(function (payment) {
            const rawAmount = payment.net_amount != null
                ? payment.net_amount
                : payment.payment_amount;
            const amount = parseFloat(rawAmount) || 0;
            const formattedAmount = typeof __number_f === 'function'
                ? __number_f(amount, false, false, {{ (int) ($currency_precision ?? 2) }})
                : amount.toFixed({{ (int) ($currency_precision ?? 2) }});
            const paymentType = String(payment.payment_type || '-')
                .replace(/_/g, ' ')
                .replace(/\b\w/g, function (letter) { return letter.toUpperCase(); });

            $('<tr>')
                .append($('<td>').text(payment.collection_form_no || '-'))
                .append($('<td>').text(paymentType))
                .append($('<td>').addClass('text-right').text(formattedAmount))
                .append($('<td>').text(payment.transaction_date || payment.created_at || ''))
                .appendTo($tbody);
        });

        $section.show();
    };

    /**
     * Fills the static meter sale table (same rows as Manual Entry click) for the current shift/operator.
     */
    window.petroPdRunManualEntryTableLoad = function (opts) {
        opts = opts || {};
        const silent = !!opts.silent;
        const force = opts.force === true;
        const pump_operator_id = $('#pump_operator_id').val();
        const shift_id = $('#shift_number').val() || $('#shift_id').val();

        if (!pump_operator_id || !shift_id) {
            petroPdMeterSaleLoadSequence += 1;
            if (petroPdMeterSaleLoadRequest && petroPdMeterSaleLoadRequest.readyState !== 4) {
                petroPdMeterSaleLoadRequest.abort();
            }
            petroPdMeterSaleLoadRequest = null;
            petroPdMeterSaleLoadedContext = '';
            petroPdMeterSaleLoadingContext = '';
            window.petroPdClearMeterSaleTable();
            if (!silent) {
                toastr.error('Please select a Pump Operator and Shift Number first.');
            }
            return;
        }

        const requestContext = petroPdCurrentMeterSaleContext();
        if (
            !force &&
            (
                petroPdMeterSaleLoadedContext === requestContext ||
                petroPdMeterSaleLoadingContext === requestContext
            )
        ) {
            return;
        }

        if (petroPdMeterSaleLoadRequest && petroPdMeterSaleLoadRequest.readyState !== 4) {
            petroPdMeterSaleLoadRequest.abort();
        }

        const $existingBody = $('#meter_sale_table tbody');
        const hadExistingRows = $existingBody.children('tr').length > 0 &&
            $existingBody.find('.petropd-meter-loading-row').length === 0;
        if (!hadExistingRows) {
            $existingBody.empty().append(
                '<tr class="petropd-meter-loading-row"><td colspan="14" class="text-center">' +
                '<i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>'
            );
        }

        const requestSequence = ++petroPdMeterSaleLoadSequence;
        petroPdMeterSaleLoadingContext = requestContext;

        petroPdMeterSaleLoadRequest = $.ajax({
            method: 'GET',
            url: manualEntryUrl,
            dataType: 'json',
            data: {
                pump_operator_id: pump_operator_id,
                shift_id: shift_id,
                active_settlement_id: $('#active_settlement_id').val(),
                settlement_no: $('#settlement_no').val()
            }
        }).done(function (res) {
            if (
                requestSequence !== petroPdMeterSaleLoadSequence ||
                requestContext !== petroPdCurrentMeterSaleContext()
            ) {
                return;
            }

            if (!res || !res.success) {
                if (!hadExistingRows) {
                    window.petroPdClearMeterSaleTable();
                }
                if (!silent) toastr.error('Failed to load meter sale data.');
                return;
            }

            petroPdMeterSaleLoadedContext = requestContext;
            window.petroPdRenderShiftPaymentDetails(res.payment_details);

            const $tbody = $('#meter_sale_table tbody');
            $tbody.empty();

            let total = 0;
            const seenRows = {};
            window.petroPdManualEntryRows = {};
            (Array.isArray(res.rows) ? res.rows : []).forEach(function (row) {
                const normalizedStartingMeter = (parseFloat(
                    String(row.starting_meter == null ? 0 : row.starting_meter).replace(/,/g, '')
                ) || 0).toFixed(2);
                const normalizedClosingMeter = (parseFloat(
                    String(row.closing_meter == null ? 0 : row.closing_meter).replace(/,/g, '')
                ) || 0).toFixed(2);
                const normalizedTestingQty = parseFloat(
                    String(row.testing_qty == null ? 0 : row.testing_qty).replace(/,/g, '')
                ) || 0;
                const normalizedEffectiveClosingMeter = (
                    parseFloat(normalizedClosingMeter) - normalizedTestingQty
                ).toFixed(2);
                const normalizedPumpId = String(row.pump_id || row.pump || '');
                const rowKey = [
                    normalizedPumpId,
                    normalizedStartingMeter,
                    normalizedEffectiveClosingMeter
                ].join('|');
                if (seenRows[rowKey]) {
                    return;
                }
                seenRows[rowKey] = true;

                window.petroPdManualEntryRows[rowKey] = row;

                var formId = row.sale_id || row.form_load_id;
                var formUrl = formId
                    ? `/petropd/settlement-pd/get-meter-sale-form/${formId}?meter_sale_source=pump_operator&detail_id=${row.id}`
                    : '';
                var editBtn = '';
                if (window.petroPdCanEditMeterSale) {
                    editBtn = formId
                        ? `<button type="button" class="btn btn-xs btn-primary petropd-meter-sale-edit" data-href="${formUrl}">Edit</button>`
                        : `<button type="button" class="btn btn-xs btn-primary petropd-meter-sale-edit" data-row-key="${rowKey}">Edit</button>`;
                }

                var cancelBtn = '';
                if (window.petroPdCanDeleteMeterSale && row.meter_sale_id) {
                    cancelBtn = `<button type="button" class="btn btn-xs btn-danger petropd-meter-sale-cancel" data-href="/petropd/settlement-pd/delete-meter-sale/${row.meter_sale_id}"><i class="fa fa-times"></i></button>`;
                }

                $tbody.append(`<tr data-petro-pd-row-key="${rowKey}"
                    data-petro-pd-pump-id="${normalizedPumpId}"
                    data-petro-pd-starting-meter="${normalizedStartingMeter}"
                    data-petro-pd-effective-closing-meter="${normalizedEffectiveClosingMeter}">
                    <td>${row.sku}</td>
                    <td>${row.product}</td>
                    <td>${row.pump}</td>
                    <td>${row.starting_meter}</td>
                    <td>${row.closing_meter}</td>
                    <td>${row.unit_price}</td>
                    <td>${row.sold_qty}</td>
                    <td>${row.discount_type}</td>
                    <td>${row.discount}</td>
                    <td>${formatPetroPdTestingQty(row.testing_qty)}</td>
                    <td>${row.total_qty}</td>
                    <td>${row.before_discount}</td>
                    <td>${row.after_discount}</td>
                    <td>${editBtn} ${cancelBtn}</td>
                </tr>`);
                total += parseFloat(row.amount_raw) || 0;
            });

            $('#meter_sale_total').val(total.toFixed(2));
            $('.meter_sale_total').text(total.toFixed(2));
            if (typeof calculate_payment_tab_total === 'function') {
                calculate_payment_tab_total();
            }
        }).fail(function (xhr) {
            if (
                (xhr && xhr.statusText === 'abort') ||
                requestSequence !== petroPdMeterSaleLoadSequence ||
                requestContext !== petroPdCurrentMeterSaleContext()
            ) {
                return;
            }

            if (!hadExistingRows) {
                window.petroPdClearMeterSaleTable();
            }
            if (!silent) { toastr.error('Unable to load meter sale data.'); }
            if (window.console && console.error) {
                console.error('PetroPD meter-sale autoload failed', xhr && xhr.status, xhr && xhr.responseText);
            }
        }).always(function () {
            if (requestSequence === petroPdMeterSaleLoadSequence) {
                petroPdMeterSaleLoadRequest = null;
                petroPdMeterSaleLoadingContext = '';
            }
        });
    };

    // IS1821: This bootstrap is deliberately inside the small meter-sale
    // partial so it still runs if an unrelated statement in the much larger
    // settlement page script fails. The loader de-duplicates identical
    // contexts, so delayed initialization cannot restart an active request.
    $(function () {
        var lastContext = '';

        function loadSelectedShiftIndependently() {
            var operatorId = $('#pump_operator_id').val();
            var shiftId = $('#shift_number').val() || $('#shift_id').val();
            if (!operatorId || !shiftId) return;

            var context = petroPdCurrentMeterSaleContext();
            if (context !== lastContext) {
                lastContext = context;
                window.petroPdRunManualEntryTableLoad({ silent: true });
            }
        }

        loadSelectedShiftIndependently();
        window.setTimeout(loadSelectedShiftIndependently, 500);
        window.setTimeout(loadSelectedShiftIndependently, 1500);

        document.addEventListener('petropd:main-tab-shown', function (event) {
            var target = event && event.detail ? event.detail.target : '';
            if (target === '#meter_sale_tab' || target === '#other_sale_tab' || target === '#payment_tab') {
                loadSelectedShiftIndependently();
            }
        });
    });

    $(document).on('click', '#btn_manual_entry', function () {
        if ($(this).prop('disabled')) {
            return false;
        }
        window.petroPdManualEntryActivated = true;
        $('#pump_closing_meter').prop('disabled', false).prop('readonly', false).removeAttr('disabled readonly');
        $('#pump_id_pd').trigger('change');
        window.petroPdRunManualEntryTableLoad({ silent: false, force: true });
    });

    function petroPdInitMeterSaleFormAfterAjax() {
        var $pump = $('#pump_id_pd, #pump_no_pd').first();
        if ($pump.length) {
            if ($pump.hasClass('select2-hidden-accessible')) {
                $pump.select2('destroy');
            }
            $pump.addClass('select2').select2({ width: '100%' });
        }
        var $disc = $('#meter_sale_discount_type');
        if ($disc.length) {
            if ($disc.hasClass('select2-hidden-accessible')) {
                $disc.select2('destroy');
            }
            $disc.select2({ width: '100%' });
        }
        if ($('input[name="is_edit"]').length) {
            $('#pump_closing_meter')
                .prop('disabled', false)
                .prop('readonly', false)
                .removeAttr('disabled readonly');
        }
    }

    window.petroPdLoadMeterSaleForm = function (url) {
        $.ajax({
            method: 'get',
            url: url,
            data: {
                action_type: 'edit',
                source: 'petro_pd',
                active_settlement_id: $('#active_settlement_id').val(),
                settlement_no: $('#settlement_no').val()
            },
            success: function (result) {
                if (result.success && result.html) {
                    $('#meter-sale-form-block').html(result.html);
                    petroPdInitMeterSaleFormAfterAjax();
                } else {
                    toastr.error(result.msg || 'Unable to load meter sale form.');
                }
            },
            error: function () {
                toastr.error('Unable to load meter sale form.');
            }
        });
    };

    window.petroPdResetMeterSaleForm = function () {
        var $formBlock = $('#meter-sale-form-block');
        var $pump = $('#pump_id_pd, #pump_no_pd').first();

        if ($pump.length) {
            $pump.val('').trigger('change.select2');
        }

        $('#pump_starting_meter').val('');
        $('#pump_closing_meter')
            .val('')
            .prop('disabled', false)
            .prop('readonly', true);
        $('#sold_qty').val('');
        $('#meter_sale_unit_price').val('');
        $('#testing_qty').val('0.00').prop('readonly', false);
        $('#meter_sale_discount_type').val('').trigger('change.select2');
        $('#meter_sale_discount').val('0.00');
        $('#bulk_sale_meter').val(0);
        $('#is_from_pumper').val(0);
        $('#assignment_id').val(0);
        $('#pumper_entry_id').val(0);
        $('input[name="is_edit"]').remove();

        $('.btn_meter_sale_cancel, .btn_update_meter_sale_pd').closest('.col-md-2, .col-md-1').remove();

        if (!$('.btn_meter_sale_pd').length && $formBlock.length) {
            $formBlock.children('.col-md-12').append(
                '<div class="col-md-1 pull-right"><button type="button" class="btn btn-primary btn_meter_sale_pd" style="margin-top: 23px;">{{ __("messages.add") }}</button></div>'
            );
        } else {
            $('.btn_meter_sale_pd').text('{{ __("messages.add") }}').prop('disabled', false).removeClass('disabled');
        }

        if (typeof window.petroPdRefreshManualEntryButton === 'function') {
            window.petroPdRefreshManualEntryButton();
        }
    };

    window.petroPdPopulateMeterSaleFormFromRow = function (row) {
        if (!row) return;

        var $pump = $('#pump_id_pd, #pump_no_pd').first();
        if ($pump.length && row.pump_id) {
            if ($pump.find('option[value="' + row.pump_id + '"]').length === 0) {
                $pump.append('<option value="' + row.pump_id + '">' + $('<div>').text(row.pump || row.pump_id).html() + '</option>');
            }
            $pump.val(row.pump_id).trigger('change.select2');
        }

        $('#pump_starting_meter').val(row.starting_meter);
        $('#pump_closing_meter')
            .val(row.closing_meter)
            .prop('disabled', false)
            .prop('readonly', false)
            .removeAttr('disabled readonly');
        $('#sold_qty').val(row.sold_qty);
        $('#meter_sale_unit_price').val(row.unit_price);
        $('#testing_qty').val(row.testing_qty || '0.00');
        $('#meter_sale_discount_type').val(row.discount_type && row.discount_type !== '-' ? row.discount_type : 'fixed').trigger('change.select2');
        $('#meter_sale_discount').val(row.discount && row.discount !== '-' ? row.discount : '0.00');
        $('#bulk_sale_meter').val(0);
        $('#is_from_pumper').val(1);

        if ($('.btn_meter_sale_pd').length) {
            $('.btn_meter_sale_pd').text("{{ __('messages.update') }}");
        }

        $('html, body').animate({
            scrollTop: $('#meter-sale-form-block').offset().top - 80
        }, 200);
    };

    $(document).on('click', '.petropd-meter-sale-edit', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        if (!window.petroPdCanEditMeterSale) {
            return false;
        }
        var url = $(this).data('href');
        if (!url) {
            var rowKey = $(this).data('row-key');
            window.petroPdPopulateMeterSaleFormFromRow((window.petroPdManualEntryRows || {})[rowKey]);
            return false;
        }
        window.petroPdLoadMeterSaleForm(url);
        return false;
    });

    $(document).on('click', '.petropd-meter-sale-cancel', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        if (!window.petroPdCanDeleteMeterSale) {
            return false;
        }
        var url = $(this).data('href');
        if (!url) return false;
        var $tr = $(this).closest('tr');
        var is_edit = $('input[name="is_edit"]').val() || $('#is_edit').val() || 0;
        $.ajax({
            method: 'delete',
            url: url,
            data: { is_edit: is_edit },
            success: function (result) {
                if (!result || !result.success) {
                    return toastr.error(result && result.msg ? result.msg : 'Delete failed');
                }
                toastr.success(result.msg || 'Removed.');
                if ($tr.length && $tr.closest('#meter_sale_table').length) {
                    $tr.remove();
                }
                var prev = parseFloat($('#meter_sale_total').val() || 0) || 0;
                var sub = parseFloat(result.amount || 0) || 0;
                var next = Math.max(0, prev - sub);
                $('#meter_sale_total').val(next);
                $('.meter_sale_total').text(typeof __number_f === 'function'
                    ? __number_f(next, false, false, typeof __currency_precision !== 'undefined' ? __currency_precision : 2)
                    : next.toFixed(2));
                if (result.pump_id && result.pump_name) {
                    var sel = $('#pump_id_pd, #pump_no_pd').first();
                    if (sel.length && sel.find('option[value="' + result.pump_id + '"]').length === 0) {
                        sel.append('<option value="' + result.pump_id + '">' + $('<div>').text(result.pump_name).html() + '</option>');
                    }
                }
                if (typeof window.petroPdRunManualEntryTableLoad === 'function') {
                    window.petroPdRunManualEntryTableLoad({ silent: true, force: true });
                }
                if (typeof calculate_payment_tab_total === 'function') {
                    calculate_payment_tab_total();
                }
            },
            error: function () {
                toastr.error('Request failed.');
            }
        });
        return false;
    });

    $(document).off('click', '.btn_meter_sale_cancel')
        .on('click.petropd_meter_sale_form_cancel', '.btn_meter_sale_cancel', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            $.ajax({
                method: 'get',
                url: $(this).data('href'),
                data: { action_type: 'cancel', source: 'petro_pd' },
                success: function (result) {
                    if (result.success && result.html) {
                        $('#meter-sale-form-block').html(result.html);
                        petroPdInitMeterSaleFormAfterAjax();
                    } else {
                        window.petroPdResetMeterSaleForm();
                    }
                },
                error: function () {
                    window.petroPdResetMeterSaleForm();
                }
            });

            return false;
        });

    $(document).off('click', '.btn_update_meter_sale_pd')
        .on('click.petropd_meter_sale_form_update', '.btn_update_meter_sale_pd', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            var $btn = $(this);
            var soldQty = parseFloat($('#sold_qty').val()) || 0;
            var unitPrice = parseFloat(($('#meter_sale_unit_price').val() || '0').toString().replace(/,/g, '')) || 0;
            var discount = $('#meter_sale_discount').val() || 0;
            var discountType = $('#meter_sale_discount_type').val() || 'fixed';
            var subTotal = soldQty * unitPrice;
            var discountValue = typeof calculate_discount === 'function'
                ? calculate_discount(discountType, discount, subTotal)
                : (discountType === 'percentage' ? (subTotal * (parseFloat(discount) || 0) / 100) : (parseFloat(discount) || 0));
            var discountAmount = subTotal - discountValue;

            $btn.prop('disabled', true).addClass('disabled');

            $.ajax({
                method: 'post',
                url: $btn.data('href'),
                data: {
                    source: 'petro_pd',
                    pump_id: $('#pump_id_pd, #pump_no_pd').first().val(),
                    starting_meter: $('#pump_starting_meter').val(),
                    closing_meter: $('#pump_closing_meter').val(),
                    price: unitPrice,
                    qty: soldQty,
                    discount: discount,
                    discount_type: discountType,
                    discount_amount: discountAmount,
                    testing_qty: $('#testing_qty').val() || 0,
                    sub_total: subTotal,
                    active_settlement_id: $('#active_settlement_id').val(),
                    settlement_no: $('#settlement_no').val(),
                    is_edit: $('input[name="is_edit"]').val() || $('#is_edit').val() || 1
                },
                success: function (result) {
                    if (!result || !result.success) {
                        toastr.error(result && result.msg ? result.msg : 'Update failed.');
                        return;
                    }

                    toastr.success(result.msg || 'Updated.');
                    if (typeof window.petroPdRunManualEntryTableLoad === 'function') {
                        window.petroPdRunManualEntryTableLoad({ silent: true, force: true });
                    }
                    if (typeof window.petroPdResetMeterSaleForm === 'function') {
                        window.petroPdResetMeterSaleForm();
                    }
                },
                complete: function () {
                    $btn.prop('disabled', false).removeClass('disabled');
                }
            });

            return false;
        });
</script>
