<script type="text/javascript">
var petropdPaymentSummaryTotalsUrl = "{{ route('petropd.pump-operator-payments.totals') }}";

/* S282-PD-PAYMENT-SUMMARY-TOTALS-021
 * Exact live Payment Summary total updater.
 * The PD Operators page has its own DataTable initializer in index.blade.php.
 * This helper updates the footer from the current draw immediately, then asks
 * the server totals endpoint for the full filtered total. It never leaves the
 * footer at Rs 0.00 when visible rows contain amounts.
 */
function petropdPaymentSummaryParseAmount(value) {
    if (value === null || typeof value === 'undefined') {
        return 0;
    }
    var text = String(value).replace(/<[^>]*>/g, ' ');
    text = text.replace(/Rs\.?/gi, '').replace(/,/g, '').replace(/[^0-9.\-]/g, '');
    var number = parseFloat(text);
    return isNaN(number) ? 0 : number;
}

function petropdPaymentSummaryFormatAmount(amount) {
    amount = parseFloat(amount || 0) || 0;
    if (typeof __currency_trans_from_en === 'function') {
        return __currency_trans_from_en(amount, true);
    }
    return 'Rs ' + amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function petropdPaymentSummaryBreakdownTotal(breakdown) {
    breakdown = breakdown || {};
    var keys = ['Cash', 'cash', 'Card', 'Cards', 'card', 'cards', 'Credit', 'credit', 'Cheque', 'Cheques', 'cheque', 'cheques'];
    var seen = {};
    var total = 0;
    $.each(keys, function (i, key) {
        if (typeof breakdown[key] === 'undefined') {
            return;
        }
        var normalized = String(key).toLowerCase();
        if (normalized === 'cards') normalized = 'card';
        if (normalized === 'cheques') normalized = 'cheque';
        if (seen[normalized]) {
            return;
        }
        seen[normalized] = true;
        total += petropdPaymentSummaryParseAmount(breakdown[key]);
    });
    return total;
}

function petropdPaymentSummarySetFooter(total, breakdown) {
    total = petropdPaymentSummaryParseAmount(total || 0);
    breakdown = breakdown || {};

    var breakdownTotal = petropdPaymentSummaryBreakdownTotal(breakdown);
    if (total <= 0 && breakdownTotal > 0) {
        total = breakdownTotal;
    }

    $('#footer_payment_summary_amount')
        .attr('data-orig-value', total)
        .removeClass('display_currency')
        .text(petropdPaymentSummaryFormatAmount(total));

    var rows = [
        ['Cash', breakdown.Cash || breakdown.cash || 0],
        ['Cards', breakdown.Card || breakdown.Cards || breakdown.card || breakdown.cards || 0],
        ['Credit', breakdown.Credit || breakdown.credit || 0],
        ['Cheques', breakdown.Cheque || breakdown.Cheques || breakdown.cheque || breakdown.cheques || 0],
        ['Shortage', breakdown.Shortage || breakdown.shortage || 0],
        ['Excess', breakdown.Excess || breakdown.excess || 0]
    ];

    var html = [];
    $.each(rows, function (index, item) {
        html.push('<span class="pd-summary-breakdown-item"><strong>' + item[0] + ':</strong> ' + petropdPaymentSummaryFormatAmount(item[1]) + '</span>');
    });
    $('#footer_payment_summary_breakdown').html(html.join(''));
}

function petropdPaymentSummaryVisibleTotals(tableSelector) {
    var total = 0;
    var breakdown = {Cash: 0, Card: 0, Credit: 0, Cheque: 0, Shortage: 0, Excess: 0};
    var selector = tableSelector || '#pump_operators_payment_summary_table';
    var $table = $(selector);

    // First preference: DataTables row data. This avoids accidentally reading
    // dates/shift numbers from the DOM and showing Rs 0.00 in the footer.
    if ($.fn.DataTable && $.fn.DataTable.isDataTable(selector)) {
        try {
            var api = $table.DataTable();
            api.rows({ search: 'applied' }).every(function () {
                var data = this.data() || {};
                var amount = petropdPaymentSummaryParseAmount(data.amount || data.payment_amount || 0);
                if (amount <= 0) {
                    return;
                }
                total += amount;
                var typeText = String(data.payment_type || data.payment_method || '').toLowerCase();
                if (typeText.indexOf('credit') !== -1) {
                    breakdown.Credit += amount;
                } else if (typeText.indexOf('cheque') !== -1 || typeText.indexOf('check') !== -1) {
                    breakdown.Cheque += amount;
                } else if (typeText.indexOf('card') !== -1) {
                    breakdown.Card += amount;
                } else if (typeText.indexOf('short') !== -1) {
                    breakdown.Shortage += amount;
                } else if (typeText.indexOf('excess') !== -1) {
                    breakdown.Excess += amount;
                } else {
                    breakdown.Cash += amount;
                }
            });
            if (total > 0) {
                return {total: total, breakdown: breakdown};
            }
        } catch (e) {}
    }

    // Fallback: scan only the Amount column/currency spans in rendered rows.
    $table.find('tbody tr').each(function () {
        var $row = $(this);
        if ($row.find('td').length < 2 || $row.find('td.dataTables_empty').length) {
            return;
        }
        var amount = 0;
        var $amount = $row.find('td.pd-col-amount span.amount, td.pd-col-amount span.display_currency, span.amount, span.display_currency.amount').last();
        if ($amount.length) {
            amount = petropdPaymentSummaryParseAmount($amount.attr('data-orig-value') || $amount.text());
        }
        if (amount <= 0) {
            return;
        }
        total += amount;
        var rowText = $row.text().toLowerCase();
        if (rowText.indexOf('credit') !== -1) {
            breakdown.Credit += amount;
        } else if (rowText.indexOf('cheque') !== -1 || rowText.indexOf('check') !== -1) {
            breakdown.Cheque += amount;
        } else if (rowText.indexOf('card') !== -1) {
            breakdown.Card += amount;
        } else if (rowText.indexOf('short') !== -1) {
            breakdown.Shortage += amount;
        } else if (rowText.indexOf('excess') !== -1) {
            breakdown.Excess += amount;
        } else {
            breakdown.Cash += amount;
        }
    });

    return {total: total, breakdown: breakdown};
}
function petropdPaymentSummaryBuildFilterPayload() {
    var payload = {
        shift_id: $('#payment_summary_shift_id').val(),
        location_id: $('#payment_summary_location_id').val(),
        pump_operator_id: $('#payment_summary_pump_operators').val(),
        payment_method: $('#payment_summary_payment_method').val(),
        customer_id: $('#payment_summary_customer').val(),
        slip_no: $('#payment_summary_slip_no').val(),
        order_no: $('#payment_summary_order_no').val(),
        date_range: $('#payment_summary_date_range').val()
    };

    var drp = $('#payment_summary_date_range').data('daterangepicker');
    if (drp && drp.startDate && drp.endDate) {
        payload.start_date = drp.startDate.format('YYYY-MM-DD');
        payload.end_date = drp.endDate.format('YYYY-MM-DD');
    }

    return payload;
}

function petropdPaymentSummaryRefreshFooter(settings) {
    var json = settings && settings.json ? settings.json : {};
    var visible = petropdPaymentSummaryVisibleTotals('#pump_operators_payment_summary_table');
    var serverTotal = petropdPaymentSummaryParseAmount(json.payment_summary_total || 0);
    var serverBreakdown = json.payment_summary_breakdown || {};

    if (serverTotal > 0) {
        petropdPaymentSummarySetFooter(serverTotal, serverBreakdown);
    } else {
        petropdPaymentSummarySetFooter(visible.total, visible.breakdown);
    }

    var totalsUrl = $('#pump_operators_payment_summary_table').data('totals-url') || (typeof petropdPaymentSummaryTotalsUrl !== 'undefined' ? petropdPaymentSummaryTotalsUrl : '');
    if (totalsUrl) {
        $.ajax({
            url: totalsUrl,
            data: petropdPaymentSummaryBuildFilterPayload(),
            dataType: 'json',
            success: function (response) {
                response = response || {};
                var ajaxBreakdown = response.payment_summary_breakdown || {};
                var ajaxTotal = petropdPaymentSummaryParseAmount(response.payment_summary_total || 0);
                var ajaxBreakdownTotal = petropdPaymentSummaryBreakdownTotal(ajaxBreakdown);
                if (ajaxTotal <= 0 && ajaxBreakdownTotal > 0) {
                    ajaxTotal = ajaxBreakdownTotal;
                }
                if (ajaxTotal > 0 || ajaxBreakdownTotal > 0) {
                    petropdPaymentSummarySetFooter(ajaxTotal, ajaxBreakdown);
                } else if (visible.total > 0) {
                    petropdPaymentSummarySetFooter(visible.total, visible.breakdown);
                }
            }
        });
    }
}

/* PETROPD_PAYMENT_SUMMARY_DATE_FAST_EMBED_018 */


/* PETROPD_PAYMENT_SUMMARY_DATE_FAST_018
 * Payment Summary tab may be rendered after the main page script has already run.
 * This initializer makes the Date Range value visible immediately and then binds
 * daterangepicker as soon as the plugin is available. It is safe to run multiple times.
 */
(function ($) {
    'use strict';

    window.petropdInitPaymentSummaryDateRangeFast = function () {
        var $input = $('#payment_summary_date_range');
        if (!$input.length) {
            return false;
        }

        var todayText = (typeof moment !== 'undefined' && typeof moment_date_format !== 'undefined')
            ? moment().format(moment_date_format) + ' ~ ' + moment().format(moment_date_format)
            : $input.val();

        if (!$input.val() || $.trim($input.val()) === '~' || $.trim($input.val()) === '-' || $.trim($input.val()) === '') {
            $input.val(todayText);
        }

        if (!$input.data('petropd-fast-date-visible')) {
            $input.data('petropd-fast-date-visible', true);
            $input.removeClass('date-range-loading').prop('readonly', true);
        }

        if ($.fn.daterangepicker && typeof dateRangeSettings !== 'undefined' && !$input.data('daterangepicker')) {
            $input.daterangepicker(dateRangeSettings, function (start, end) {
                $input.val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                $input.trigger('change');
            });

            $input.on('cancel.daterangepicker.petropdFastDate', function () {
                $input.val('');
                $input.trigger('change');
            });

            $input.data('daterangepicker').setStartDate(moment().startOf('day'));
            $input.data('daterangepicker').setEndDate(moment().endOf('day'));
            $input.val(todayText);
        }

        return true;
    };

    $(document).ready(function () {
        window.petropdInitPaymentSummaryDateRangeFast();

        var attempts = 0;
        var timer = setInterval(function () {
            attempts++;
            if (window.petropdInitPaymentSummaryDateRangeFast() || attempts >= 60) {
                clearInterval(timer);
            }
        }, 100);
    });

    $(document).on('click shown.bs.tab shown.bs.modal', function () {
        setTimeout(window.petropdInitPaymentSummaryDateRangeFast, 10);
        setTimeout(window.petropdInitPaymentSummaryDateRangeFast, 150);
    });
})(jQuery);



(function ($) {
    'use strict';

    window.initPetroPDPaymentSummaryTable = function (options) {
        options = options || {};
        var selector = options.selector || '#pump_operators_payment_summary_table';
        var $table = $(selector);

        if (!$table.length) {
            return;
        }

        // PETROPD_PAYMENT_SUMMARY_DATE_FAST_CALL_018: ensure Date Range is ready before Ajax/table load.
        if (typeof window.petropdInitPaymentSummaryDateRangeFast === 'function') {
            window.petropdInitPaymentSummaryDateRangeFast();
        }

        if ($.fn.DataTable.isDataTable(selector)) {
            $table.DataTable().destroy();
        }

        var ajaxUrl = options.ajaxUrl || "{{ route('petropd.pump-operator-payments.index', ['only_pumper' => true]) }}";
        var includeExtraColumns = options.includeExtraColumns === true;

        var columns = [
            /*
             |------------------------------------------------------------------
             | Seven columns are HIDDEN, not removed.
             |------------------------------------------------------------------
             |
             | Collection Form No, Slip No, Date, Time, Order No, Note and Edited
             | By now appear in the Details popup instead of taking table width.
             |
             | visible:false rather than deleting the definitions, because
             | DataTables only searches columns it knows about. Removing them
             | would have satisfied "take them off the table" while silently
             | breaking "should still be able to search by them" - and the search
             | is the part users would miss.
             |
             | So the server still selects and filters on every one of these; they
             | simply are not drawn. Typing a slip number, an order number or a
             | collection form number in the search box still finds its row.
             */
            { data: 'action', name: 'action', width: '45px', orderable: false, searchable: false, className: 'pd-col-action' },
            /*
             | Date and Time in ONE column, time on the line below.
             |
             | Width raised from 92px to 138px (+150%) - the date was being clipped
             | and could not be read. Dropping the separate Time column more than
             | pays for the extra width, so the table is narrower overall.
             |
             | name stays 'date' so ordering and searching still work against the
             | database column rather than the combined markup.
             */
            { data: 'date', name: 'date', className: 'pd-col-date', visible: false },
            { data: 'location_name', name: 'business_locations.name' },
            { data: 'time', name: 'time', visible: false },
            { data: 'pump_operator_name', name: 'pump_operators.name', width: '92px' },
            { data: 'shift_number', name: 'shift_number', width: '6px', className: 'text-center pd-col-shift' },
            /*
             | Width halved from 82px to 41px, as requested.
             |
             | data points at the rendered column so a long reference becomes a
             | button; name stays as the real column so SEARCHING and ordering
             | still work against the database value, not the HTML.
             */
            { data: 'collection_form_no_display', name: 'collection_form_no', visible: false },
            { data: 'payment_type', name: 'payment_type', width: '43px', className: 'pd-col-payment-type' },
            { data: 'customer_name_display', name: 'customer_name', width: '270px', className: 'pd-col-customer text-center' },
            { data: 'slip_no', name: 'slip_no', className: 'pd-col-slip text-center', visible: true }, // PETROPD-SLIP-NO-20260821: user requires Slip No in the table as well as Details.
            { data: 'order_number', name: 'order_number', className: 'pd-col-order text-center', visible: false },
            { data: 'amount', name: 'amount', className: 'text-right pd-col-amount', width: '102px' }
        ];

        if (includeExtraColumns) {
            columns.push({ data: 'note', name: 'note', orderable: false, searchable: false, className: 'text-center pd-col-note', visible: false });
            columns.push({ data: 'edited_by', name: 'edited_by', className: 'pd-col-edited-by', visible: false });
        }

        var table = $table.DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[1, 'desc']],
            autoWidth: true,
            ajax: {
                url: ajaxUrl,
                data: function (d) {
                    d.shift_id = $('#payment_summary_shift_id').val();
                    d.location_id = $('#payment_summary_location_id').val();
                    d.pump_operator_id = $('#payment_summary_pump_operators').val();
                    d.payment_method = $('#payment_summary_payment_method').val();
                    d.customer_id = $('#payment_summary_customer').val();
                    d.slip_no = $('#payment_summary_slip_no').val();
                    d.order_no = $('#payment_summary_order_no').val();
                    d.date_range = $('#payment_summary_date_range').val();
                }
            },
            columnDefs: [
                { targets: '_all', defaultContent: '' },
                { targets: 0, orderable: false, searchable: false }
            ],
            columns: columns,
            fnDrawCallback: function (settings) {
                petropdPaymentSummaryRefreshFooter(settings);
                __currency_convert_recursively($table);
                setTimeout(function(){ petropdPaymentSummaryRefreshFooter(settings); }, 250);
            }});

        $table.on('preXhr.dt', function () {
            $('#footer_payment_summary_amount').text('Loading...');
            $('#footer_payment_summary_breakdown').html('');
        });

        $('#payment_summary_location_id, #payment_summary_pump_operators, #payment_summary_shift_id, #payment_summary_payment_method, #payment_summary_customer, #payment_summary_slip_no, #payment_summary_order_no, #payment_summary_date_range')
            .off('change.petropdPaymentSummary keyup.petropdPaymentSummary')
            .on('change.petropdPaymentSummary keyup.petropdPaymentSummary', function () {
                table.ajax.reload();
            });

        // IS1666: bind only the search input generated for this table. The old
        // document-wide selector also matched hidden tables and sent searches to them.
        var $paymentSummarySearch = $(table.table().container()).find('.dataTables_filter input');
        $paymentSummarySearch
            .attr('placeholder', 'Search ...')
            .off('.petropdPaymentSummarySearch')
            .on('input.petropdPaymentSummarySearch search.petropdPaymentSummarySearch', function () {
                var value = String($(this).val() || '').trim();
                if (table.search() !== value) table.search(value).draw();
            });

        $('.select2').select2({ width: '100%' });

        return table;
    };
})(jQuery);
</script>


<script>
$(document).off('click.pdPaymentSummaryDropdownEdit027', 'a.pd-payment-edit-link')
    .on('click.pdPaymentSummaryDropdownEdit027', 'a.pd-payment-edit-link', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var href = $(this).data('href') || $(this).attr('href');
        var container = $(this).data('container') || '.view_modal';

        if (!href || $(this).closest('li').hasClass('disabled')) {
            return false;
        }

        $.ajax({
            url: href,
            dataType: 'html',
            success: function(result) {
                if ($(container).length === 0) {
                    $('body').append('<div class="modal fade view_modal" tabindex="-1" role="dialog" aria-hidden="true"></div>');
                    container = '.view_modal';
                }
                $(container).html(result).modal('show');
            },
            error: function(xhr) {
                var message = xhr.responseText || 'Unable to open Edit form.';
                if (typeof toastr !== 'undefined') {
                    toastr.error(message);
                } else {
                    alert(message);
                }
            }
        });

        return false;
    });

/*
 * MA-002 (IS-1929): let the Action dropdown escape its cell.
 *
 * Every cell in this table carries overflow:hidden so long values ellipsis
 * rather than stretching the column - which also clips this menu inside its
 * own 170px cell. The CSS lifts that for the action cell using :has(), but
 * :has() is unsupported in older browsers, so the class is also applied here.
 *
 * Bound with a namespace and cleared on close, so the cell reverts to its
 * normal clipping the moment the menu is dismissed.
 */
$(document)
    .off('shown.bs.dropdown.pdAction hidden.bs.dropdown.pdAction', '.pd-payment-action-dropdown')
    .on('shown.bs.dropdown.pdAction', '.pd-payment-action-dropdown', function () {
        $(this).closest('td').addClass('pd-action-open');
    })
    .on('hidden.bs.dropdown.pdAction', '.pd-payment-action-dropdown', function () {
        $(this).closest('td').removeClass('pd-action-open');
    });
</script>

