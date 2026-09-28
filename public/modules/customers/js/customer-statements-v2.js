(function ($) {
    'use strict';

    var root = $('.customer-statements-v2');
    if (!root.length) return;

    var displayFormat = window.moment_date_format || 'MM/DD/YYYY';
    var financialYearStartMonth = 3; // April (0-based). Change here if required.

    function initSelect2(scope) {
        if (!$.fn.select2) return;
        (scope || root).find('select.select2').each(function () {
            var $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
            $el.select2({width: '100%', allowClear: true, placeholder: $el.attr('placeholder') || 'Please Select'});
        });
    }

    function financialYear(offset) {
        var now = moment();
        var startYear = now.month() < financialYearStartMonth ? now.year() - 1 : now.year();
        startYear += offset || 0;
        return [
            moment({year: startYear, month: financialYearStartMonth, day: 1}).startOf('day'),
            moment({year: startYear + 1, month: financialYearStartMonth, day: 1}).subtract(1, 'day').endOf('day')
        ];
    }

    function standardRanges() {
        var currentFy = financialYear(0);
        var lastFy = financialYear(-1);
        return {
            'Today': [moment().startOf('day'), moment().endOf('day')],
            'Yesterday': [moment().subtract(1, 'day').startOf('day'), moment().subtract(1, 'day').endOf('day')],
            'Last 7 Days': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
            'Last 30 Days': [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'This month last year': [moment().subtract(1, 'year').startOf('month'), moment().subtract(1, 'year').endOf('month')],
            'This Year': [moment().startOf('year'), moment().endOf('year')],
            'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
            'Current financial year': currentFy,
            'Last financial year': lastFy
        };
    }

    function updateHiddenDates(start, end) {
        $('#cs-v2-start-date').val(start.format('YYYY-MM-DD'));
        $('#cs-v2-end-date').val(end.format('YYYY-MM-DD'));
    }

    function makeDigits(value, groupName) {
        return String(value).split('').map(function (digit, index) {
            return '<input type="text" inputmode="numeric" maxlength="1" class="cs-typed-date-digit" data-group="' + groupName + '" data-index="' + index + '" value="' + digit + '">';
        }).join('');
    }

    function ensureTypedDateModal() {
        var $modal = $('#cs-v2-custom-date-modal');
        if ($modal.length) return $modal;

        $('body').append(
            '<div id="cs-v2-custom-date-modal" class="cs-typed-date-modal" aria-hidden="true">' +
                '<div class="cs-typed-date-backdrop"></div>' +
                '<div class="cs-typed-date-dialog">' +
                    '<div class="cs-typed-date-header">' +
                        '<h3>Select Custom Date Range: Date / Month / Year</h3>' +
                        '<button type="button" class="cs-typed-date-x">&times;</button>' +
                    '</div>' +
                    '<div class="cs-typed-date-body">' +
                        '<div class="cs-typed-date-section">' +
                            '<h4>From</h4>' +
                            '<div class="cs-typed-date-fields">' +
                                '<div><label>Date</label><div class="cs-typed-date-boxes cs-from-day"></div></div>' +
                                '<div><label>Month</label><div class="cs-typed-date-boxes cs-from-month"></div></div>' +
                                '<div><label>Year</label><div class="cs-typed-date-boxes cs-from-year"></div></div>' +
                            '</div>' +
                        '</div>' +
                        '<hr>' +
                        '<div class="cs-typed-date-section">' +
                            '<h4>To</h4>' +
                            '<div class="cs-typed-date-fields">' +
                                '<div><label>Date</label><div class="cs-typed-date-boxes cs-to-day"></div></div>' +
                                '<div><label>Month</label><div class="cs-typed-date-boxes cs-to-month"></div></div>' +
                                '<div><label>Year</label><div class="cs-typed-date-boxes cs-to-year"></div></div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="cs-typed-date-error"></div>' +
                    '</div>' +
                    '<div class="cs-typed-date-footer">' +
                        '<button type="button" class="btn btn-default cs-typed-date-close">Close</button>' +
                        '<button type="button" class="btn btn-primary cs-typed-date-apply">Apply</button>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );
        return $('#cs-v2-custom-date-modal');
    }

    function fillTypedDateModal(start, end) {
        var $modal = ensureTypedDateModal();
        $modal.find('.cs-from-day').html(makeDigits(start.format('DD'), 'from-day'));
        $modal.find('.cs-from-month').html(makeDigits(start.format('MM'), 'from-month'));
        $modal.find('.cs-from-year').html(makeDigits(start.format('YYYY'), 'from-year'));
        $modal.find('.cs-to-day').html(makeDigits(end.format('DD'), 'to-day'));
        $modal.find('.cs-to-month').html(makeDigits(end.format('MM'), 'to-month'));
        $modal.find('.cs-to-year').html(makeDigits(end.format('YYYY'), 'to-year'));
        $modal.find('.cs-typed-date-error').text('');
        return $modal;
    }

    function groupValue($modal, selector) {
        var value = '';
        $modal.find(selector + ' .cs-typed-date-digit').each(function () { value += $(this).val(); });
        return value;
    }

    function readTypedDate($modal, side) {
        return moment(
            groupValue($modal, '.cs-' + side + '-day') + '/' +
            groupValue($modal, '.cs-' + side + '-month') + '/' +
            groupValue($modal, '.cs-' + side + '-year'),
            'DD/MM/YYYY',
            true
        );
    }

    function openTypedDateModal($input) {
        var picker = $input.data('daterangepicker');
        var start = picker ? picker.startDate.clone() : moment();
        var end = picker ? picker.endDate.clone() : moment();
        var $modal = fillTypedDateModal(start, end);
        $modal.data('target', $input).addClass('is-open').attr('aria-hidden', 'false');
        $('body').addClass('cs-typed-date-open');
        if (picker) picker.hide();
        setTimeout(function () { $modal.find('.cs-typed-date-digit').first().focus().select(); }, 30);
    }

    function appendTypedRangeOption($input) {
        var picker = $input.data('daterangepicker');
        if (!picker || picker.container.find('.cs-custom-date-range-option').length) return;

        var $item = $('<li class="cs-custom-date-range-option">Custom Date Range</li>');
        var $customRange = picker.container.find('.ranges li').filter(function () {
            return $.trim($(this).text()) === (picker.locale.customRangeLabel || 'Custom Range');
        }).first();

        if ($customRange.length) $item.insertBefore($customRange);
        else picker.container.find('.ranges ul').append($item);

        $item.on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openTypedDateModal($input);
        });
    }

    function initSingleRange($input) {
        if ($input.data('cs-standard-range-ready')) return;

        var options = {
            autoUpdateInput: true,
            alwaysShowCalendars: true,
            showDropdowns: true,
            autoApply: false,
            locale: {
                format: displayFormat,
                separator: ' - ',
                customRangeLabel: 'Custom Range',
                applyLabel: 'Apply',
                cancelLabel: 'Cancel'
            },
            ranges: standardRanges()
        };

        $input.daterangepicker(options, function (start, end) {
            updateHiddenDates(start, end);
        });

        $input.data('cs-standard-range-ready', true);
        var picker = $input.data('daterangepicker');
        updateHiddenDates(picker.startDate, picker.endDate);
        appendTypedRangeOption($input);
        $input.on('show.daterangepicker', function () { appendTypedRangeOption($input); });
    }

    function initRanges() {
        if (!$.fn.daterangepicker || typeof moment === 'undefined') return;
        initSingleRange($('#cs-v2-date-range'));
        $('.cs-v2-range').each(function () { initSingleRange($(this)); });
    }

    $(document)
        .off('.csTypedDate')
        .on('input.csTypedDate', '#cs-v2-custom-date-modal .cs-typed-date-digit', function () {
            this.value = String(this.value || '').replace(/\D/g, '').slice(-1);
            if (!this.value) return;
            var $digits = $('#cs-v2-custom-date-modal .cs-typed-date-digit');
            var index = $digits.index(this);
            if (index < $digits.length - 1) $digits.eq(index + 1).focus().select();
        })
        .on('keydown.csTypedDate', '#cs-v2-custom-date-modal .cs-typed-date-digit', function (e) {
            var $digits = $('#cs-v2-custom-date-modal .cs-typed-date-digit');
            var index = $digits.index(this);
            if (e.key === 'Backspace' && !this.value && index > 0) {
                e.preventDefault();
                $digits.eq(index - 1).focus().val('').select();
            } else if (e.key === 'ArrowLeft' && index > 0) {
                e.preventDefault(); $digits.eq(index - 1).focus().select();
            } else if (e.key === 'ArrowRight' && index < $digits.length - 1) {
                e.preventDefault(); $digits.eq(index + 1).focus().select();
            } else if (e.key === 'Enter') {
                e.preventDefault(); $('#cs-v2-custom-date-modal .cs-typed-date-apply').trigger('click');
            }
        })
        .on('click.csTypedDate', '#cs-v2-custom-date-modal .cs-typed-date-x, #cs-v2-custom-date-modal .cs-typed-date-close, #cs-v2-custom-date-modal .cs-typed-date-backdrop', function () {
            $('#cs-v2-custom-date-modal').removeClass('is-open').attr('aria-hidden', 'true');
            $('body').removeClass('cs-typed-date-open');
        })
        .on('click.csTypedDate', '#cs-v2-custom-date-modal .cs-typed-date-apply', function () {
            var $modal = $('#cs-v2-custom-date-modal');
            var start = readTypedDate($modal, 'from');
            var end = readTypedDate($modal, 'to');
            var $error = $modal.find('.cs-typed-date-error').text('');

            if (!start.isValid() || !end.isValid()) {
                $error.text('Please enter valid From and To dates.');
                return;
            }
            if (end.isBefore(start, 'day')) {
                $error.text('The To date cannot be earlier than the From date.');
                return;
            }

            var $input = $modal.data('target');
            var picker = $input.data('daterangepicker');
            if (picker) {
                picker.setStartDate(start);
                picker.setEndDate(end);
                picker.chosenLabel = 'Custom Date Range';
            }
            $input.val(start.format(displayFormat) + ' - ' + end.format(displayFormat)).trigger('change');
            if ($input.is('#cs-v2-date-range')) updateHiddenDates(start, end);
            $modal.removeClass('is-open').attr('aria-hidden', 'true');
            $('body').removeClass('cs-typed-date-open');
            if (picker) $input.trigger('apply.daterangepicker', [picker]);
        });

    $('#cs-v2-customer').on('change', function () {
        var id = $(this).val(), url = root.data('next-date-url');
        if (!id || !url) return;
        $.get(url, {customer_id:id}).done(function (r) {
            var $m = $('.cs-v2-min-date-message');
            if (r.minimum_start_date) $m.text('For this customer, the next statement must start on or after '+r.minimum_start_date+'.').show();
            else $m.hide();
        });
    });

    $('#cs-v2-create-form').on('submit', function (e) {
        e.preventDefault();
        var form = this;
        if (!form.checkValidity()) { form.reportValidity(); return; }
        var $button = $('#cs-v2-save').prop('disabled', true);
        $.ajax({url: window.CustomerStatementsV2.saveUrl, method:'POST', data:$(form).serialize(), headers:{'X-CSRF-TOKEN':window.CustomerStatementsV2.csrf}})
            .done(function (r) {
                if (r && Number(r.success) === 1) { toastr.success(r.msg || 'Statement saved successfully.'); $(form).trigger('reset'); initSelect2(root); }
                else toastr.error((r && r.msg) || 'Unable to save statement.');
            }).fail(function (xhr) { toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Unable to save statement.'); })
            .always(function () { $button.prop('disabled', false); });
    });

    initSelect2(root);
    initRanges();
    $('a[data-toggle="tab"]').on('shown.bs.tab', function () { initSelect2(root); $(window).trigger('resize'); });
})(jQuery);

/* Stage 014: functional statement and payment list wiring. */
(function ($) {
    'use strict';
    var root = $('.customer-statements-v2');
    if (!root.length || !window.CustomerStatementsV2) return;

    function rangeParts(selector) {
        var $input = $(selector), picker = $input.data('daterangepicker');
        if (!picker) return {start: '', end: ''};
        return {start: picker.startDate.format('YYYY-MM-DD'), end: picker.endDate.format('YYYY-MM-DD')};
    }

    function reload(table) {
        if (table && table.ajax) table.ajax.reload(null, false);
    }

    var statementTable = null;
    var paymentTable = null;

    function initStatementTable() {
        if (!$.fn.DataTable || !$('#cs-v2-list-table').length || statementTable) return;
        statementTable = $('#cs-v2-list-table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            pageLength: 25,
            ajax: {
                url: window.CustomerStatementsV2.listUrl,
                data: function (d) {
                    var statementRange = rangeParts('#cs-v2-statement-range');
                    var printedRange = rangeParts('#cs-v2-printed-range');
                    d.location_id = $('#cs-v2-list-location').val();
                    d.customer_id = $('#cs-v2-list-customer').val();
                    d.customer_type = $('#cs-v2-list-type').val();
                    d.start_date = statementRange.start;
                    d.end_date = statementRange.end;
                    d.printed_start = printedRange.start;
                    d.printed_end = printedRange.end;
                    d.search_term = $('#cs-v2-list-search').val();
                }
            },
            columns: [
                {data:'action', name:'action', orderable:false, searchable:false},
                {data:'print_date', name:'customer_statements.print_date'},
                {data:'date_from', name:'customer_statements.date_from'},
                {data:'date_to', name:'customer_statements.date_to'},
                {data:'customer', name:'contacts.name'},
                {data:'statement_no', name:'customer_statements.statement_no'},
                {data:'amount', name:'amount', orderable:false, searchable:false},
                {data:'payment_status', name:'payment_status', orderable:false, searchable:false},
                {data:'username', name:'u.username', orderable:false},
                {data:'description', name:'description', orderable:false, searchable:false}
            ],
            drawCallback: function () {
                if (typeof __currency_convert_recursively === 'function') __currency_convert_recursively($('#cs-v2-list-table'));
            }
        });

        $('#cs-v2-list-location,#cs-v2-list-customer,#cs-v2-list-type').on('change', function(){ reload(statementTable); });
        $('#cs-v2-statement-range,#cs-v2-printed-range').on('apply.daterangepicker change', function(){ reload(statementTable); });
        var timer;
        $('#cs-v2-list-search').on('input', function(){ clearTimeout(timer); timer=setTimeout(function(){ reload(statementTable); },250); });
    }

    function initPaymentTable() {
        if (!$.fn.DataTable || !$('#cs-v2-payments-table').length || paymentTable) return;
        paymentTable = $('#cs-v2-payments-table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            pageLength: 25,
            ajax: {
                url: window.CustomerStatementsV2.paymentsUrl,
                data: function (d) {
                    var r = rangeParts('#cs-v2-payment-range');
                    d.customer_id = $('#cs-v2-payment-customer').val();
                    d.start_date = r.start;
                    d.end_date = r.end;
                    d.payment_method = $('#cs-v2-payment-method').val();
                    d.statement_no = $('#cs-v2-payment-statement-no').val();
                    d.search_term = $('#cs-v2-payment-search').val();
                }
            },
            columns: [
                {data:'action', name:'action', orderable:false, searchable:false},
                {data:'print_date', name:'customer_statements.print_date'},
                {data:'date_from', name:'customer_statements.date_from'},
                {data:'date_to', name:'customer_statements.date_to'},
                {data:'customer', name:'contacts.name'},
                {data:'statement_no', name:'customer_statements.statement_no'},
                {data:'amount', name:'amount', orderable:false, searchable:false},
                {data:'payment_status', name:'payment_status', orderable:false, searchable:false},
                {data:'username', name:'username', orderable:false},
                {data:'description', name:'description', orderable:false, searchable:false}
            ],
            drawCallback: function () {
                if (typeof __currency_convert_recursively === 'function') __currency_convert_recursively($('#cs-v2-payments-table'));
            }
        });
        $('#cs-v2-payment-customer,#cs-v2-payment-method').on('change', function(){ reload(paymentTable); });
        $('#cs-v2-payment-range').on('apply.daterangepicker change', function(){ reload(paymentTable); });
        var timer;
        $('#cs-v2-payment-statement-no,#cs-v2-payment-search').on('input', function(){ clearTimeout(timer); timer=setTimeout(function(){ reload(paymentTable); },250); });
    }

    $(function(){ initStatementTable(); initPaymentTable(); });
    $('a[data-toggle="tab"]').on('shown.bs.tab.csStage014', function(){ initStatementTable(); initPaymentTable(); });
})(jQuery);
