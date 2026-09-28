(function (window, document) {
    'use strict';

    var cfg = window.ERP_GLOBAL_DATE_RANGE_V3 || {};
    if (!cfg.enabled || !window.jQuery || !window.moment) return;

    var path = window.location.pathname || '/';
    if ((cfg.excludedPathPrefixes || []).some(function (prefix) {
        return prefix && (path === prefix || path.indexOf(prefix + '/') === 0);
    })) return;

    var $ = window.jQuery;
    if (!$.fn || !$.fn.daterangepicker) return;

    var original = $.fn.daterangepicker;
    var format = cfg.displayFormat || 'DD/MM/YYYY';
    var separator = cfg.valueSeparator || ' - ';
    var fyMonth = Number.isInteger(cfg.financialYearStartMonth) ? cfg.financialYearStartMonth : 3;
    var labels = cfg.labels || {};
    var presets = cfg.presets || {};

    function financialYear(offset) {
        var now = moment();
        var year = now.month() < fyMonth ? now.year() - 1 : now.year();
        year += offset || 0;
        return [
            moment({ year: year, month: fyMonth, day: 1 }).startOf('day'),
            moment({ year: year + 1, month: fyMonth, day: 1 }).subtract(1, 'day').endOf('day')
        ];
    }

    function ranges() {
        var r = {};
        function add(flag, text, start, end) {
            if (flag !== false) r[text] = [start, end];
        }
        add(presets.today, labels.today || 'Today', moment().startOf('day'), moment().endOf('day'));
        add(presets.yesterday, labels.yesterday || 'Yesterday', moment().subtract(1, 'day').startOf('day'), moment().subtract(1, 'day').endOf('day'));
        add(presets.last7Days, labels.last7Days || 'Last 7 Days', moment().subtract(6, 'days').startOf('day'), moment().endOf('day'));
        add(presets.last30Days, labels.last30Days || 'Last 30 Days', moment().subtract(29, 'days').startOf('day'), moment().endOf('day'));
        add(presets.thisMonth, labels.thisMonth || 'This Month', moment().startOf('month'), moment().endOf('month'));
        add(presets.lastMonth, labels.lastMonth || 'Last Month', moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month'));
        add(presets.thisMonthLastYear, labels.thisMonthLastYear || 'This month last year', moment().subtract(1, 'year').startOf('month'), moment().subtract(1, 'year').endOf('month'));
        add(presets.thisYear, labels.thisYear || 'This Year', moment().startOf('year'), moment().endOf('year'));
        add(presets.lastYear, labels.lastYear || 'Last Year', moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year'));
        var currentFy = financialYear(0), lastFy = financialYear(-1);
        add(presets.thisFinancialYear, labels.thisFinancialYear || 'Current financial year', currentFy[0], currentFy[1]);
        add(presets.lastFinancialYear, labels.lastFinancialYear || 'Last financial year', lastFy[0], lastFy[1]);
        return r;
    }

    function mergeOptions(options) {
        options = $.extend(true, {}, options || {});
        options.ranges = $.extend({}, ranges(), options.ranges || {});
        options.alwaysShowCalendars = true;
        options.showDropdowns = options.showDropdowns !== false;
        options.autoApply = false;
        options.locale = $.extend({}, options.locale || {}, {
            format: format,
            separator: separator,
            customRangeLabel: cfg.customRangeLabel || 'Custom Date Range',
            applyLabel: labels.apply || 'Apply',
            cancelLabel: labels.close || 'Close'
        });
        return options;
    }

    $.fn.daterangepicker = function (options, callback) {
        var result = original.call(this, mergeOptions(options), callback);
        this.each(function () {
            install($(this));
        });
        return result;
    };
    $.fn.daterangepicker.prototype = original.prototype;

    function makeDigitGroup(prefix, value) {
        var digits = value.replace(/\D/g, '');
        var html = '';
        for (var i = 0; i < digits.length; i++) {
            html += '<input class="erp-dr-digit" data-group="' + prefix + '" data-index="' + i + '" inputmode="numeric" maxlength="1" value="' + digits.charAt(i) + '" aria-label="' + prefix + ' digit ' + (i + 1) + '">';
        }
        return html;
    }

    function buildModal() {
        if ($('#erp-global-custom-range-modal').length) return $('#erp-global-custom-range-modal');
        var html = '' +
        '<div id="erp-global-custom-range-modal" class="erp-dr-modal" aria-hidden="true">' +
          '<div class="erp-dr-backdrop"></div>' +
          '<div class="erp-dr-dialog" role="dialog" aria-modal="true">' +
            '<div class="erp-dr-header"><h3>' + (labels.modalTitle || 'Select Custom Date Range: Date / Month / Year') + '</h3><button type="button" class="erp-dr-x" aria-label="Close">&times;</button></div>' +
            '<div class="erp-dr-body">' +
              '<div class="erp-dr-section" data-side="from"><h4>' + (labels.from || 'From') + '</h4>' +
                '<div class="erp-dr-fields"><div><label>' + (labels.date || 'Date') + '</label><div class="erp-dr-boxes erp-dr-from-day"></div></div><div><label>' + (labels.month || 'Month') + '</label><div class="erp-dr-boxes erp-dr-from-month"></div></div><div><label>' + (labels.year || 'Year') + '</label><div class="erp-dr-boxes erp-dr-from-year"></div></div></div>' +
              '</div>' +
              '<div class="erp-dr-divider"></div>' +
              '<div class="erp-dr-section" data-side="to"><h4>' + (labels.to || 'To') + '</h4>' +
                '<div class="erp-dr-fields"><div><label>' + (labels.date || 'Date') + '</label><div class="erp-dr-boxes erp-dr-to-day"></div></div><div><label>' + (labels.month || 'Month') + '</label><div class="erp-dr-boxes erp-dr-to-month"></div></div><div><label>' + (labels.year || 'Year') + '</label><div class="erp-dr-boxes erp-dr-to-year"></div></div></div>' +
              '</div>' +
              '<div class="erp-dr-error" aria-live="polite"></div>' +
            '</div>' +
            '<div class="erp-dr-footer"><button type="button" class="erp-dr-close">' + (labels.close || 'Close') + '</button><button type="button" class="erp-dr-apply">' + (labels.apply || 'Apply') + '</button></div>' +
          '</div>' +
        '</div>';
        $('body').append(html);
        return $('#erp-global-custom-range-modal');
    }

    function fillModal($modal, start, end) {
        var values = {
            '.erp-dr-from-day': start.format('DD'), '.erp-dr-from-month': start.format('MM'), '.erp-dr-from-year': start.format('YYYY'),
            '.erp-dr-to-day': end.format('DD'), '.erp-dr-to-month': end.format('MM'), '.erp-dr-to-year': end.format('YYYY')
        };
        $.each(values, function (selector, value) {
            var group = selector.replace('.erp-dr-', '');
            $modal.find(selector).html(makeDigitGroup(group, value));
        });
    }

    function readGroup($modal, selector) {
        var out = '';
        $modal.find(selector + ' .erp-dr-digit').each(function () { out += this.value || ''; });
        return out;
    }

    function modalDate($modal, side) {
        var d = readGroup($modal, '.erp-dr-' + side + '-day');
        var m = readGroup($modal, '.erp-dr-' + side + '-month');
        var y = readGroup($modal, '.erp-dr-' + side + '-year');
        return moment(d + '/' + m + '/' + y, 'DD/MM/YYYY', true);
    }

    function install($input) {
        var picker = $input.data('daterangepicker');
        if (!picker || $input.data('erp-custom-modal-installed')) return;
        $input.data('erp-custom-modal-installed', true);

        picker.container.off('click.erpCustomModal').on('click.erpCustomModal', '.ranges li', function (e) {
            var text = $(this).text().trim();
            if (text !== (cfg.customRangeLabel || 'Custom Date Range')) return;
            e.preventDefault();
            e.stopImmediatePropagation();

            var $modal = buildModal();
            fillModal($modal, picker.startDate.clone(), picker.endDate.clone());
            $modal.data('picker', picker).data('input', $input).addClass('is-open').attr('aria-hidden', 'false');
            picker.hide();
            $('body').addClass('erp-dr-modal-open');
            setTimeout(function () { $modal.find('.erp-dr-digit').first().focus().select(); }, 30);
        });
    }

    $(document)
        .off('.erpGlobalDateRangeV3')
        .on('input.erpGlobalDateRangeV3', '#erp-global-custom-range-modal .erp-dr-digit', function () {
            this.value = String(this.value || '').replace(/\D/g, '').slice(-1);
            if (this.value) {
                var $all = $('#erp-global-custom-range-modal .erp-dr-digit');
                var i = $all.index(this);
                if (i >= 0 && i < $all.length - 1) $all.eq(i + 1).focus().select();
            }
        })
        .on('keydown.erpGlobalDateRangeV3', '#erp-global-custom-range-modal .erp-dr-digit', function (e) {
            var $all = $('#erp-global-custom-range-modal .erp-dr-digit');
            var i = $all.index(this);
            if (e.key === 'Backspace' && !this.value && i > 0) {
                e.preventDefault();
                $all.eq(i - 1).focus().val('').select();
            } else if (e.key === 'ArrowLeft' && i > 0) {
                e.preventDefault(); $all.eq(i - 1).focus().select();
            } else if (e.key === 'ArrowRight' && i < $all.length - 1) {
                e.preventDefault(); $all.eq(i + 1).focus().select();
            } else if (e.key === 'Enter') {
                e.preventDefault(); $('#erp-global-custom-range-modal .erp-dr-apply').click();
            }
        })
        .on('click.erpGlobalDateRangeV3', '#erp-global-custom-range-modal .erp-dr-x, #erp-global-custom-range-modal .erp-dr-close, #erp-global-custom-range-modal .erp-dr-backdrop', function () {
            $('#erp-global-custom-range-modal').removeClass('is-open').attr('aria-hidden', 'true');
            $('body').removeClass('erp-dr-modal-open');
        })
        .on('click.erpGlobalDateRangeV3', '#erp-global-custom-range-modal .erp-dr-apply', function () {
            var $modal = $('#erp-global-custom-range-modal');
            var start = modalDate($modal, 'from');
            var end = modalDate($modal, 'to');
            var $error = $modal.find('.erp-dr-error').text('');
            if (!start.isValid() || !end.isValid()) { $error.text(labels.invalidDate || 'Please enter a valid date.'); return; }
            if (end.isBefore(start, 'day')) { $error.text(labels.invalidRange || 'The To date cannot be earlier than the From date.'); return; }

            var picker = $modal.data('picker');
            var $input = $modal.data('input');
            picker.setStartDate(start);
            picker.setEndDate(end);
            $input.val(start.format(format) + separator + end.format(format));
            picker.chosenLabel = cfg.customRangeLabel || 'Custom Date Range';
            $modal.removeClass('is-open').attr('aria-hidden', 'true');
            $('body').removeClass('erp-dr-modal-open');
            picker.clickApply();
            $input.trigger('change');
        });

    $(function () {
        $('input').each(function () { install($(this)); });
        var observer = new MutationObserver(function () {
            $('input').each(function () { install($(this)); });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    });
})(window, document);
