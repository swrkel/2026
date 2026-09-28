(function () {
    'use strict';

    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function qsa(selector, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(selector));
    }

    function pad2(value) {
        return String(value).padStart(2, '0');
    }

    function nativeYmd(date) {
        return date.getFullYear() + '-' + pad2(date.getMonth() + 1) + '-' + pad2(date.getDate());
    }

    function bootEggDateFilters() {
        qsa('[data-egg-date-filter]').forEach(function (form) {
            if (form.getAttribute('data-egg-date-ready') === '1') {
                return;
            }
            form.setAttribute('data-egg-date-ready', '1');

            var rangeInput = qs('[data-egg-date-range]', form);
            var rangeValue = qs('[data-egg-range-value]', form);
            var fromInput = qs('[data-egg-from]', form);
            var toInput = qs('[data-egg-to]', form);
            var fyMonth = Math.max(0, Math.min(11, parseInt(form.getAttribute('data-fy-month') || '4', 10) - 1));

            if (!rangeInput || !fromInput || !toInput || !rangeValue) {
                return;
            }

            var jq = window.jQuery;
            var hasMoment = typeof window.moment !== 'undefined' || typeof moment !== 'undefined';
            var momentLib = hasMoment ? (window.moment || moment) : null;
            var hasPicker = !!(jq && jq.fn && jq.fn.daterangepicker && momentLib);

            function getMomentFormat() {
                if (typeof window.moment_date_format !== 'undefined' && window.moment_date_format) {
                    return window.moment_date_format;
                }
                if (typeof moment_date_format !== 'undefined' && moment_date_format) {
                    return moment_date_format;
                }
                return 'MM/DD/YYYY';
            }

            function initialMoment(value, fallback) {
                if (!momentLib) {
                    return null;
                }
                var parsed = value ? momentLib(value, 'YYYY-MM-DD', true) : null;
                return parsed && parsed.isValid() ? parsed : fallback.clone();
            }

            function financialYear(reference, offsetYears) {
                var year = reference.month() >= fyMonth ? reference.year() : reference.year() - 1;
                var start = momentLib([year + (offsetYears || 0), fyMonth, 1]).startOf('day');
                return [start, start.clone().add(1, 'year').subtract(1, 'day').endOf('day')];
            }

            function selectedRangeKey(label) {
                var map = {
                    'This Year': 'this_year',
                    'Last Year': 'last_year',
                    'This FY': 'this_fy',
                    'Last FY': 'last_fy',
                    'Custom': 'custom',
                    'Custom Range': 'custom',
                    'Custom Date Range': 'custom'
                };
                return map[label] || 'custom';
            }

            function applyDates(start, end, rangeKey, displayFormat) {
                if (!start || !end) {
                    return;
                }
                fromInput.value = start.format('YYYY-MM-DD');
                toInput.value = end.format('YYYY-MM-DD');
                rangeValue.value = rangeKey || 'custom';
                rangeInput.value = start.format(displayFormat) + ' ~ ' + end.format(displayFormat);
            }

            function parseTypedRange() {
                if (!momentLib) {
                    return true;
                }
                var pieces = String(rangeInput.value || '').split('~');
                if (pieces.length !== 2) {
                    return false;
                }
                var format = getMomentFormat();
                var accepted = [format, 'YYYY-MM-DD', 'MM/DD/YYYY', 'DD/MM/YYYY'];
                var start = momentLib(pieces[0].trim(), accepted, true);
                var end = momentLib(pieces[1].trim(), accepted, true);
                if (!start.isValid() || !end.isValid()) {
                    return false;
                }
                if (start.isAfter(end)) {
                    var swap = start;
                    start = end;
                    end = swap;
                }
                applyDates(start, end, 'custom', format);
                return true;
            }

            if (hasPicker) {
                var format = getMomentFormat();
                var now = momentLib();
                var start = initialMoment(fromInput.value, now.clone().startOf('year'));
                var end = initialMoment(toInput.value, now.clone().endOf('year'));
                var currentFy = financialYear(now, 0);
                var previousFy = financialYear(now, -1);

                var settings = {};
                if (typeof window.dateRangeSettings !== 'undefined' && window.dateRangeSettings) {
                    settings = jq.extend(true, {}, window.dateRangeSettings);
                } else if (typeof dateRangeSettings !== 'undefined' && dateRangeSettings) {
                    settings = jq.extend(true, {}, dateRangeSettings);
                }

                settings.startDate = start;
                settings.endDate = end;
                settings.autoUpdateInput = true;
                settings.alwaysShowCalendars = true;
                settings.showDropdowns = true;
                settings.ranges = {
                    'This Year': [now.clone().startOf('year'), now.clone().endOf('year')],
                    'Last Year': [now.clone().subtract(1, 'year').startOf('year'), now.clone().subtract(1, 'year').endOf('year')],
                    'This FY': currentFy,
                    'Last FY': previousFy
                };
                settings.locale = jq.extend(true, {}, settings.locale || {}, {
                    format: format,
                    separator: ' ~ ',
                    applyLabel: 'Apply',
                    cancelLabel: 'Clear',
                    customRangeLabel: 'Custom Date Range'
                });

                jq(rangeInput).daterangepicker(settings, function (pickedStart, pickedEnd, label) {
                    applyDates(pickedStart, pickedEnd, selectedRangeKey(label), format);
                });

                applyDates(start, end, rangeValue.value || 'this_year', format);

                jq(rangeInput).on('apply.daterangepicker', function (event, picker) {
                    applyDates(picker.startDate, picker.endDate, selectedRangeKey(picker.chosenLabel), format);
                });

                jq(rangeInput).on('cancel.daterangepicker', function () {
                    var defaultStart = momentLib().startOf('year');
                    var defaultEnd = momentLib().endOf('year');
                    applyDates(defaultStart, defaultEnd, 'this_year', format);
                    var picker = jq(rangeInput).data('daterangepicker');
                    if (picker) {
                        picker.setStartDate(defaultStart);
                        picker.setEndDate(defaultEnd);
                    }
                });

                rangeInput.addEventListener('change', function () {
                    parseTypedRange();
                });

                form.addEventListener('submit', function (event) {
                    if (!parseTypedRange()) {
                        event.preventDefault();
                        window.alert('Please enter a valid date range.');
                        rangeInput.focus();
                    }
                });
            } else {
                // Safe fallback when the host ERP has not loaded daterangepicker.
                form.addEventListener('submit', function () {
                    var pieces = String(rangeInput.value || '').split('~');
                    if (pieces.length === 2) {
                        fromInput.value = pieces[0].trim();
                        toInput.value = pieces[1].trim();
                        rangeValue.value = 'custom';
                    }
                });
            }

            // Follow the ERP convention: location/store dropdowns are searchable when Select2 exists.
            if (jq && jq.fn && jq.fn.select2) {
                jq(form).find('.egg-system-select').each(function () {
                    var select = jq(this);
                    if (!select.hasClass('select2-hidden-accessible')) {
                        select.select2({width: '100%', minimumResultsForSearch: 0});
                    }
                });
            }
        });
    }

    document.addEventListener('input', function (event) {
        if (event.target.matches('[data-egg-search]')) {
            var value = event.target.value.toLowerCase();
            qsa('[data-egg-table] tbody tr').forEach(function (row) {
                row.style.display = row.innerText.toLowerCase().indexOf(value) >= 0 ? '' : 'none';
            });
        }

        if (event.target.matches('[data-egg-total],[data-egg-loss]')) {
            var totalNode = qs('[data-egg-total]');
            var total = parseInt(totalNode ? totalNode.value || 0 : 0, 10);
            var loss = qsa('[data-egg-loss]').reduce(function (sum, input) {
                return sum + parseInt(input.value || 0, 10);
            }, 0);
            var good = qs('[data-egg-good]');
            if (good) {
                good.value = Math.max(0, total - loss);
            }
        }
    });

    document.addEventListener('click', function (event) {
        var addLine = event.target.closest('[data-add-line]');
        if (addLine) {
            var template = qs('template[data-line-template]');
            var body = qs('[data-lines]');
            if (!template || !body) {
                return;
            }
            var index = body.querySelectorAll('tr').length;
            body.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index));
            return;
        }

        var removeLine = event.target.closest('[data-remove-line]');
        if (removeLine) {
            var row = removeLine.closest('tr');
            if (row) {
                row.remove();
            }
            return;
        }

        var columns = event.target.closest('[data-egg-columns]');
        if (columns) {
            var table = qs('[data-egg-table]');
            if (!table) {
                return;
            }
            var headers = qsa('thead th', table);
            var labels = headers.map(function (header, index) {
                return (index + 1) + '. ' + header.innerText;
            }).join('\n');
            var response = window.prompt('Enter column numbers to hide/show, comma separated:\n' + labels);
            if (!response) {
                return;
            }
            response.split(',').map(function (part) {
                return parseInt(part.trim(), 10) - 1;
            }).filter(function (index) {
                return index >= 0;
            }).forEach(function (index) {
                qsa('tr', table).forEach(function (row) {
                    var cell = row.children[index];
                    if (cell) {
                        cell.style.display = cell.style.display === 'none' ? '' : 'none';
                    }
                });
            });
        }
    });

    document.addEventListener('click', async function (event) {
        var button = event.target.closest('[data-egg-share]');
        if (!button) {
            return;
        }

        var box = button.closest('[data-egg-share-box]');
        var channel = button.getAttribute('data-egg-share');
        var recipient = null;

        if (channel === 'email') {
            recipient = window.prompt('Email address:');
        }
        if (channel === 'sms' || channel === 'whatsapp') {
            recipient = window.prompt('Mobile number:');
        }
        if (channel !== 'link' && !recipient) {
            return;
        }

        var params = new URLSearchParams(window.location.search);
        var csrf = qs('meta[name="csrf-token"]');
        var token = csrf ? csrf.content : '';
        var from = qs('[data-egg-from]');
        var to = qs('[data-egg-to]');
        var location = qs('[name="location_id"]');
        var store = qs('[name="store_id"]');

        var payload = {
            resource_type: box.dataset.resourceType,
            channel: channel,
            to: recipient,
            parameters: {
                from: params.get('from') || (from ? from.value : null),
                to: params.get('to') || (to ? to.value : null),
                location_id: params.get('location_id') || (location ? location.value : 'all'),
                store_id: params.get('store_id') || (store ? store.value : 'all')
            }
        };

        var response = await fetch(box.dataset.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify(payload)
        });
        var data = await response.json();

        if (!response.ok) {
            window.alert(data.message || 'Unable to share report.');
            return;
        }
        if (channel === 'whatsapp' && data.whatsapp_url) {
            window.open(data.whatsapp_url, '_blank');
            return;
        }
        if (channel === 'link') {
            if (navigator.clipboard) {
                await navigator.clipboard.writeText(data.url);
            }
            window.alert('Secure report link copied.');
        } else {
            window.alert('Report sharing request completed.');
        }
    });

    window.eggExportTable = function (type) {
        var table = qs('[data-egg-table]');
        if (!table) {
            return;
        }
        var rows = qsa('tr', table).filter(function (row) {
            return row.style.display !== 'none';
        }).map(function (row) {
            return qsa('th,td', row).filter(function (cell) {
                return cell.style.display !== 'none';
            }).map(function (cell) {
                return '"' + cell.innerText.replaceAll('"', '""') + '"';
            }).join(',');
        });
        var blob = new Blob([rows.join('\n')], {type: type === 'xls' ? 'application/vnd.ms-excel' : 'text/csv'});
        var anchor = document.createElement('a');
        anchor.href = URL.createObjectURL(blob);
        anchor.download = 'egg-report.' + (type === 'xls' ? 'xls' : 'csv');
        anchor.click();
        URL.revokeObjectURL(anchor.href);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootEggDateFilters);
    } else {
        bootEggDateFilters();
    }
})();
