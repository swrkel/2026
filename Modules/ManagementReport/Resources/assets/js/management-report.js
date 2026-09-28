(function () {
    'use strict';

    function all(selector, context) {
        return Array.prototype.slice.call((context || document).querySelectorAll(selector));
    }

    function updateSelectionCount() {
        var count = all('input[name="sections[]"]:checked').length;
        all('[data-mgmt-selected-count]').forEach(function (node) { node.textContent = count; });
    }

    function initSectionSelectorCollapse() {
        all('[data-mgmt-section-panel]').forEach(function (panel) {
            var toggle = panel.querySelector('[data-mgmt-section-toggle]');
            var content = panel.querySelector('[data-mgmt-section-content]');
            var help = panel.querySelector('[data-mgmt-section-toggle-help]');
            if (!toggle || !content) return;

            function setExpanded(expanded) {
                toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                content.hidden = !expanded;
                panel.classList.toggle('is-collapsed', !expanded);
                panel.classList.toggle('is-expanded', expanded);
                if (help) {
                    help.textContent = expanded
                        ? 'Click here to collapse sections'
                        : 'Click here to expand and view sections';
                }
            }

            // The selector is intentionally collapsed on every new page load.
            setExpanded(false);

            toggle.addEventListener('click', function () {
                setExpanded(toggle.getAttribute('aria-expanded') !== 'true');
            });
        });
    }

    function setAllSections(checked) {
        all('input[name="sections[]"]').forEach(function (input) { input.checked = checked; });
        updateSelectionCount();
        var form = document.getElementById('mgmt-report-form');
        if (form) form.dispatchEvent(new CustomEvent('mgmt:refresh-live-report'));
    }

    function formPayload(form) {
        return new FormData(form);
    }

    function showPreviewError(host, message) {
        host.innerHTML = '<div class="alert alert-danger mgmt-alert"><strong>Unable to prepare preview.</strong><br>' + String(message || 'Please check the report scope and try again.') + '</div>';
    }

    function initPreview() {
        var form = document.getElementById('mgmt-report-form');
        var host = document.getElementById('mgmt-report-preview');
        var loading = document.getElementById('mgmt-preview-loading');
        if (!form || !host) return;

        var previewUrl = form.getAttribute('data-preview-url');
        if (!previewUrl) return;

        var timer = null;
        var activeController = null;
        var sequence = 0;

        function refreshLiveReport(options) {
            options = options || {};
            if (!all('input[name="sections[]"]:checked').length) {
                if (activeController) activeController.abort();
                showPreviewError(host, 'Select at least one report section.');
                return;
            }

            sequence += 1;
            var requestSequence = sequence;
            if (activeController) activeController.abort();
            activeController = typeof AbortController !== 'undefined' ? new AbortController() : null;

            if (loading) loading.hidden = false;
            host.setAttribute('aria-busy', 'true');

            var requestOptions = {
                method: 'POST',
                body: formPayload(form),
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
            };
            if (activeController) requestOptions.signal = activeController.signal;

            fetch(previewUrl, requestOptions)
                .then(function (response) {
                    return response.json().then(function (body) {
                        if (!response.ok) throw new Error(body.message || Object.values(body.errors || {}).join(' '));
                        return body;
                    });
                })
                .then(function (body) {
                    if (requestSequence !== sequence) return;
                    host.innerHTML = body.html;
                    host.setAttribute('data-initial-preview', '1');
                    if (options.scroll === true) {
                        host.scrollIntoView({behavior: 'smooth', block: 'start'});
                    }
                })
                .catch(function (error) {
                    if (error && error.name === 'AbortError') return;
                    if (requestSequence === sequence) showPreviewError(host, error.message);
                })
                .finally(function () {
                    if (requestSequence !== sequence) return;
                    if (loading) loading.hidden = true;
                    host.setAttribute('aria-busy', 'false');
                });
        }

        function scheduleLiveReport(delay, options) {
            window.clearTimeout(timer);
            timer = window.setTimeout(function () { refreshLiveReport(options); }, typeof delay === 'number' ? delay : 350);
        }

        form.addEventListener('change', function (event) {
            var target = event.target;
            if (!target) return;
            if (target.matches('select[name="location_id"], select[name="store_id"], select[name="shift_id"], input[name="sections[]"]')) {
                scheduleLiveReport(250);
            }
        });

        form.addEventListener('mgmt:refresh-live-report', function () {
            scheduleLiveReport(100);
        });

        if (!host.hasAttribute('data-initial-preview')) {
            scheduleLiveReport(0);
        }

        window.ManagementReportLivePreview = {
            refresh: function () { scheduleLiveReport(0); }
        };
    }

    function initShare() {
        var modal = document.getElementById('mgmt-share-modal');
        var form = document.getElementById('mgmt-share-form');
        if (!modal || !form) return;

        all('[data-mgmt-open-share]').forEach(function (button) {
            button.addEventListener('click', function () { modal.hidden = false; document.body.style.overflow = 'hidden'; });
        });
        all('[data-mgmt-close-share]', modal).forEach(function (button) {
            button.addEventListener('click', function () { modal.hidden = true; document.body.style.overflow = ''; });
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var raw = document.getElementById('mgmt-recipient-input').value || '';
            var recipients = raw.split(/\r?\n|,/).map(function (value) { return value.trim(); }).filter(Boolean);
            var result = document.getElementById('mgmt-share-result');
            if (!recipients.length) {
                result.innerHTML = '<div class="alert alert-danger">Please enter at least one recipient.</div>';
                return;
            }

            var data = new FormData(form);
            data.delete('recipients[]');
            recipients.forEach(function (recipient) { data.append('recipients[]', recipient); });
            var submit = form.querySelector('[type="submit"]');
            submit.disabled = true;
            result.innerHTML = '<div class="alert alert-info"><i class="fa fa-spinner fa-spin"></i> Preparing delivery...</div>';

            fetch(form.action, {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
            })
            .then(function (response) {
                return response.json().then(function (body) {
                    if (!response.ok) throw new Error(body.message || Object.values(body.errors || {}).join(' '));
                    return body;
                });
            })
            .then(function (body) {
                var links = '<a target="_blank" href="' + body.public_url + '">Open secure report link</a>';
                if (body.launch_url) links += ' &nbsp; <a target="_blank" href="' + body.launch_url + '">Open WhatsApp</a>';
                result.innerHTML = '<div class="alert alert-success">' + body.message + '<br>' + links + '</div>';
                if (body.launch_url) window.open(body.launch_url, '_blank', 'noopener');
            })
            .catch(function (error) { result.innerHTML = '<div class="alert alert-danger">' + error.message + '</div>'; })
            .finally(function () { submit.disabled = false; });
        });
    }

    function keepAllFiltersNative() {
        all('select.mgmt-native-all-filter').forEach(function (select) {
            var $select = window.jQuery ? window.jQuery(select) : null;

            // Remove any Select2 instance created by an older cached module
            // script or by a later global page initializer. These filters are
            // intentionally native so their selected "All" text cannot vanish.
            if ($select && window.jQuery.fn && window.jQuery.fn.select2 &&
                ($select.hasClass('select2-hidden-accessible') || $select.data('select2'))) {
                try { $select.select2('destroy'); } catch (error) {}
            }

            select.classList.remove('select2-hidden-accessible');
            select.removeAttribute('aria-hidden');
            select.removeAttribute('tabindex');
            select.style.display = '';

            if (!select.value) {
                var defaultOption = select.querySelector('option[value="0"], option[value="__all__"], option[value=""]');
                if (defaultOption) {
                    defaultOption.selected = true;
                    select.value = defaultOption.value;
                }
            }

            // Remove only a stale Select2 container that belongs to this select.
            var next = select.nextElementSibling;
            if (next && next.classList.contains('select2-container')) {
                next.parentNode.removeChild(next);
            }
        });
    }

    function managementFinancialYear(offset) {
        var config = window.ERP_GLOBAL_DATE_RANGE_V3 || {};
        var month = Number.isInteger(config.financialYearStartMonth) ? config.financialYearStartMonth : 3;
        var now = window.moment();
        var year = now.month() < month ? now.year() - 1 : now.year();
        year += offset || 0;
        return [
            window.moment({year: year, month: month, day: 1}).startOf('day'),
            window.moment({year: year + 1, month: month, day: 1}).subtract(1, 'day').endOf('day')
        ];
    }

    function managementStandardRanges() {
        var currentFy = managementFinancialYear(0);
        var lastFy = managementFinancialYear(-1);
        return {
            'Today': [window.moment().startOf('day'), window.moment().endOf('day')],
            'Yesterday': [window.moment().subtract(1, 'day').startOf('day'), window.moment().subtract(1, 'day').endOf('day')],
            'Last 7 Days': [window.moment().subtract(6, 'days').startOf('day'), window.moment().endOf('day')],
            'Last 30 Days': [window.moment().subtract(29, 'days').startOf('day'), window.moment().endOf('day')],
            'This Month': [window.moment().startOf('month'), window.moment().endOf('month')],
            'Last Month': [window.moment().subtract(1, 'month').startOf('month'), window.moment().subtract(1, 'month').endOf('month')],
            'This Month Last Year': [window.moment().subtract(1, 'year').startOf('month'), window.moment().subtract(1, 'year').endOf('month')],
            'This Year': [window.moment().startOf('year'), window.moment().endOf('year')],
            'Last Year': [window.moment().subtract(1, 'year').startOf('year'), window.moment().subtract(1, 'year').endOf('year')],
            'This Financial Year': currentFy,
            'Last Financial Year': lastFy
        };
    }

    function initManagementDateRange() {
        var input = document.getElementById('mgmt-report-date-range');
        var startField = document.getElementById('mgmt-report-start-date');
        var endField = document.getElementById('mgmt-report-end-date');
        if (!input || !startField || !endField) return;

        if (!window.jQuery || !window.moment || !window.jQuery.fn || !window.jQuery.fn.daterangepicker) {
            input.readOnly = false;
            input.value = startField.value + ' - ' + endField.value;
            return;
        }

        var $input = window.jQuery(input);
        var config = window.ERP_GLOBAL_DATE_RANGE_V3 || {};
        var displayFormat = config.displayFormat || window.moment_date_format || 'DD/MM/YYYY';
        var separator = config.valueSeparator || ' - ';
        var start = window.moment(input.getAttribute('data-start-date') || startField.value, 'YYYY-MM-DD', true);
        var end = window.moment(input.getAttribute('data-end-date') || endField.value, 'YYYY-MM-DD', true);

        if (!start.isValid()) start = window.moment().startOf('day');
        if (!end.isValid()) end = start.clone().endOf('day');

        function syncDates(rangeStart, rangeEnd) {
            startField.value = rangeStart.format('YYYY-MM-DD');
            endField.value = rangeEnd.format('YYYY-MM-DD');
            $input.val(rangeStart.format(displayFormat) + separator + rangeEnd.format(displayFormat));
        }

        if ($input.data('daterangepicker')) {
            try { $input.data('daterangepicker').remove(); } catch (error) {}
            $input.removeData('daterangepicker');
        }

        var options = {
            startDate: start,
            endDate: end,
            autoUpdateInput: true,
            alwaysShowCalendars: true,
            showDropdowns: true,
            autoApply: false,
            opens: 'right',
            locale: {
                format: displayFormat,
                separator: separator,
                customRangeLabel: config.customRangeLabel || 'Custom Date Range',
                applyLabel: (config.labels && config.labels.apply) || 'Apply',
                cancelLabel: (config.labels && config.labels.close) || 'Close'
            }
        };

        // The latest application layout globally injects these same standard
        // ranges. This fallback keeps the module functional in layouts where
        // that global wrapper has not been loaded.
        if (!window.ERP_GLOBAL_DATE_RANGE_V3) {
            options.ranges = managementStandardRanges();
        }

        $input.daterangepicker(options, function (rangeStart, rangeEnd) {
            syncDates(rangeStart, rangeEnd);
        });

        $input.off('.mgmtDateRange')
            .on('apply.daterangepicker.mgmtDateRange', function (event, picker) {
                syncDates(picker.startDate, picker.endDate);
                var form = document.getElementById('mgmt-report-form');
                if (form) form.dispatchEvent(new CustomEvent('mgmt:refresh-live-report'));
            })
            .on('cancel.daterangepicker.mgmtDateRange', function (event, picker) {
                syncDates(picker.startDate, picker.endDate);
                var form = document.getElementById('mgmt-report-form');
                if (form) form.dispatchEvent(new CustomEvent('mgmt:refresh-live-report'));
            });

        var picker = $input.data('daterangepicker');
        if (picker) syncDates(picker.startDate, picker.endDate);

        var form = document.getElementById('mgmt-report-form');
        if (form) {
            form.addEventListener('submit', function () {
                var currentPicker = $input.data('daterangepicker');
                if (currentPicker) syncDates(currentPicker.startDate, currentPicker.endDate);
            });
        }
    }

    function initScopeFilters() {
        keepAllFiltersNative();

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            window.jQuery('.mgmt-searchable').each(function () {
                var $select = window.jQuery(this);
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                $select.select2({ width: '100%', allowClear: false });
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        all('input[name="sections[]"]').forEach(function (input) { input.addEventListener('change', updateSelectionCount); });
        all('[data-mgmt-select-all]').forEach(function (button) { button.addEventListener('click', function () { setAllSections(true); }); });
        all('[data-mgmt-clear-all]').forEach(function (button) { button.addEventListener('click', function () { setAllSections(false); }); });
        updateSelectionCount();
        initSectionSelectorCollapse();
        initPreview();
        initShare();
        initManagementDateRange();
        initScopeFilters();
    });

    window.addEventListener('load', function () {
        keepAllFiltersNative();
        window.setTimeout(keepAllFiltersNative, 250);
        window.setTimeout(keepAllFiltersNative, 1000);
    });
})();
