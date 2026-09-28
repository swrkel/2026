<script>
(function ($) {
    'use strict';

    var dataUrl = @json(url('/mpcs/F15-New/data'));
    var printUrl = @json(url('/mpcs/F15-New/print'));
    var saveUrl = @json(url('/mpcs/F15-New/save'));
    var precision = Math.max(0, parseInt(@json($currencyPrecision), 10) || 2);
    var request = null;

    function number(value) {
        if (typeof value === 'string') {
            value = value.replace(/,/g, '').trim();
        }
        var parsed = parseFloat(value);
        return isFinite(parsed) ? parsed : 0;
    }

    function format(value) {
        return number(value).toLocaleString(undefined, {
            minimumFractionDigits: precision,
            maximumFractionDigits: precision
        });
    }

    function row(key) {
        return $('#f15_daily_table tr[data-row-key="' + key + '"]');
    }

    function setCell($cell, value) {
        value = number(value);
        $cell.attr('data-value', value);
        var $input = $cell.find('input');
        if ($input.length) {
            $input.val(value.toFixed(precision));
        } else {
            $cell.find('span').text(format(value));
            if (!$cell.find('span').length) {
                $cell.text(format(value));
            }
        }
    }

    function cellValue(key, type) {
        var $cell = row(key).find('.f15-' + type);
        var $input = $cell.find('input');
        return $input.length ? number($input.val()) : number($cell.attr('data-value'));
    }

    function setToday(key, value) {
        setCell(row(key).find('.f15-today'), value);
    }

    /*
     * IS2029: opening stock is a BALANCE, not a flow.
     *
     * This added Previous Day to Today for EVERY row, which is right for
     * purchases and sales - they accumulate - but wrong for rows 9 and 10. With
     * both columns holding the same governing F22 balance of 995,913.64, the
     * As of Today column showed 1,991,827.28: the balance counted twice, and no
     * real stock figure.
     *
     * The server already resolves this correctly (F15DailyReportService treats
     * these keys as balances and sends total = the balance), but this function
     * ran afterwards and overwrote it - which is why the IS2009 fix appeared not
     * to work. The recalculation has to know the rule too, because it also runs
     * when the user edits a manual field and no server round trip happens.
     *
     * section_grand_total and the rows derived from it are included because they
     * CONTAIN the opening stock; summing them would reintroduce the doubling one
     * level up. That is visible in the same screenshot: row 11 showed
     * 4,004,439.64 + 1,828,683.64 = 5,833,123.28.
     */
    var F15_BALANCE_KEYS = [
        'oil_opening_stock',
        'gas_opening_stock',
        'section_grand_total',
        'balance_stock_sale_price',
        'grand_total'
    ];

    function isBalanceRow(key) {
        return F15_BALANCE_KEYS.indexOf(key) !== -1;
    }

    /*
     * IS2029: rows whose "As of Today" repeats the PREVIOUS DAY figure.
     *
     * Kept in step with F15DailyReportService - this function also runs when a
     * manual field is edited, with no server round trip, so the rule has to
     * exist in both places or an edited report would disagree with a freshly
     * loaded one.
     */
    var F15_LEADING_PREVIOUS_KEYS = [
        'oil_opening_stock',
        'gas_opening_stock',
        'section_grand_total'
    ];

    function setTotal(key) {
        if (isBalanceRow(key)) {
            if (F15_LEADING_PREVIOUS_KEYS.indexOf(key) !== -1) {
                setCell(row(key).find('.f15-total'), cellValue(key, 'previous'));

                return;
            }

            // Today already carries the balance in force on the selected date;
            // fall back to Previous Day when there is no F22 for today.
            var todayValue = cellValue(key, 'today');
            var balance = todayValue !== 0 ? todayValue : cellValue(key, 'previous');

            setCell(row(key).find('.f15-total'), balance);

            return;
        }

        setCell(row(key).find('.f15-total'), cellValue(key, 'previous') + cellValue(key, 'today'));
    }

    function sumToday(keys) {
        return keys.reduce(function (sum, key) { return sum + cellValue(key, 'today'); }, 0);
    }

    function recalculate() {
        setToday('purchase_subtotal', sumToday(['f18_oil_purchase', 'f18_gas_purchase', 'oil_purchase', 'gas_purchase']));
        setToday('purchases_total', sumToday(['purchase_subtotal', 'price_increment', 'changes_addition']));
        setToday('section_grand_total', sumToday(['purchases_total', 'oil_opening_stock', 'gas_opening_stock']));
        setToday('sales_subtotal', sumToday(['oil_cash_sale', 'gas_cash_sale', 'oil_credit_sale', 'gas_credit_sale']));
        setToday('total_sale', sumToday(['sales_subtotal', 'changes_deduction', 'price_reduction', 'damaged', 'others', 'total_return']));
        /*
         * IS2029: row 23 = row 11 - row 22, matching F15DailyReportService.
         *
         * The rule lives in both places on purpose: this runs when a manual
         * field is edited, with no server round trip. If one copy is changed the
         * other must be too, or an edited report would disagree with a freshly
         * loaded one.
         */
        setToday(
            'balance_stock_sale_price',
            cellValue('section_grand_total', 'today') - cellValue('total_sale', 'today')
        );
        setToday('grand_total', cellValue('balance_stock_sale_price', 'today') + cellValue('total_sale', 'today'));

        $('#f15_daily_table tr[data-row-key]').each(function () {
            setTotal($(this).attr('data-row-key'));
        });

        /*
         * IS2029: row 24 "As of Today" = row 22 + row 23 of the same column.
         *
         * Set AFTER the loop above, because it reads the As of Today figures of
         * rows 22 and 23 - both of which the loop has just written. Running it
         * inside the loop would read whichever values happened to be there at
         * the time.
         *
         * Matches F15DailyReportService; this path runs when a manual field is
         * edited, with no server round trip.
         */
        setCell(
            row('grand_total').find('.f15-total'),
            cellValue('total_sale', 'total') + cellValue('balance_stock_sale_price', 'total')
        );
    }

    function setBusy(busy) {
        $('#f15_daily_print_area').attr('aria-busy', busy ? 'true' : 'false');
        $('#f15_daily_load, #f15_daily_save').prop('disabled', !!busy);
    }

    function notify(success, message) {
        if (window.toastr) {
            success ? toastr.success(message) : toastr.error(message);
        } else {
            alert(message);
        }
    }

    function render(report) {
        report = report || {};
        $('#f15_location_name').text(report.location_name || 'Business Location');
        $('#f15_report_date').text(report.date || $('#f15_daily_date').val());
        $('#f15_form_no').text(report.form_no || '-');
        $('#f15_reset_note').toggle(!!report.is_f22_reset);

        (report.rows || []).forEach(function (item) {
            if (item.type !== 'row' || !item.key) { return; }
            var $row = row(item.key);
            setCell($row.find('.f15-previous'), item.previous);
            setCell($row.find('.f15-today'), item.today);
            setCell($row.find('.f15-total'), item.total);
        });

        $('#f15_notes').val(report.notes || '');
        $('#f15_prepared_by').val(report.prepared_by || $('#f15_prepared_by').val());
        $('#f15_prepared_date').val(report.prepared_date || report.date || '');
        $('#f15_checked_by').val(report.checked_by || '');
        $('#f15_checked_date').val(report.checked_date || '');
        $('#f15_approved_by').val(report.approved_by || '');
        $('#f15_approved_date').val(report.approved_date || '');
        recalculate();
    }

    function loadReport() {
        var date = $('#f15_daily_date').val();
        var locationId = $('#f15_daily_location').val();
        if (!date || !locationId) {
            notify(false, 'Please select the business location and date.');
            return;
        }

        if (request && request.readyState !== 4) {
            request.abort();
        }

        setBusy(true);
        request = $.ajax({
            url: dataUrl,
            method: 'GET',
            dataType: 'json',
            cache: false,
            data: { date: date, location_id: locationId }
        }).done(function (response) {
            if (response.success) {
                render(response.data);
            } else {
                notify(false, response.msg || 'Unable to load the F15 Daily Report.');
            }
        }).fail(function (xhr, status) {
            if (status === 'abort') { return; }
            var message = xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.msg);
            notify(false, message || 'Unable to load the F15 Daily Report.');
        }).always(function () {
            setBusy(false);
        });
    }

    function saveReport() {
        var payload = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            date: $('#f15_daily_date').val(),
            location_id: $('#f15_daily_location').val(),
            changes_addition: cellValue('changes_addition', 'today'),
            changes_deduction: cellValue('changes_deduction', 'today'),
            damaged: cellValue('damaged', 'today'),
            others: cellValue('others', 'today'),
            total_return: cellValue('total_return', 'today'),
            notes: $('#f15_notes').val(),
            prepared_by: $('#f15_prepared_by').val(),
            prepared_date: $('#f15_prepared_date').val(),
            checked_by: $('#f15_checked_by').val(),
            checked_date: $('#f15_checked_date').val(),
            approved_by: $('#f15_approved_by').val(),
            approved_date: $('#f15_approved_date').val()
        };

        setBusy(true);
        $.ajax({
            url: saveUrl,
            method: 'POST',
            dataType: 'json',
            data: payload
        }).done(function (response) {
            if (response.success) {
                render(response.data);
                notify(true, response.msg || 'F15 Daily Report saved successfully.');
            } else {
                notify(false, response.msg || 'Unable to save the F15 Daily Report.');
            }
        }).fail(function (xhr) {
            var json = xhr.responseJSON || {};
            var message = json.msg || json.message;
            if (!message && json.errors) {
                message = Object.keys(json.errors).map(function (key) {
                    return json.errors[key][0];
                }).join('\n');
            }
            notify(false, message || 'Unable to save the F15 Daily Report.');
        }).always(function () {
            setBusy(false);
        });
    }

    $(document).on('input change', '.f15-manual-input', recalculate);
    $('#f15_daily_load').on('click', loadReport);
    $('#f15_daily_save').on('click', saveReport);
    $('#f15_daily_location, #f15_daily_date').on('change', loadReport);
    /*
     * IS2029: print via the standalone page rather than window.print().
     *
     * Printing the working screen meant hiding the theme around the report with
     * @media print rules, and that never became reliable - the tab strip and
     * sidebar kept appearing, and hiding the page wholesale produced a blank
     * sheet. The dedicated page has no theme to hide.
     *
     * The date and location currently on screen are passed through, so the
     * printed report matches what is being viewed.
     */
    $('#f15_daily_print, #f15_header_print, #f15_bottom_print').on('click', function () {
        var date = $('#f15_daily_date').val();
        var locationId = $('#f15_daily_location').val();

        if (!date || !locationId) {
            notify(false, 'Choose a date and location before printing.');
            return;
        }

        window.open(
            printUrl + '?date=' + encodeURIComponent(date) + '&location_id=' + encodeURIComponent(locationId),
            '_blank'
        );
    });

    $(function () {
        if ($.fn.select2) {
            $('#f15_daily_location').select2({ width: '100%' });
        }
        loadReport();
    });
})(jQuery);
</script>
