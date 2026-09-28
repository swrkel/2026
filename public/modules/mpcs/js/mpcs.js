(function (window, document) {
    'use strict';

    var MPCS = window.MPCS = window.MPCS || {};
    MPCS.version = '1.2.0';
    MPCS.pages = MPCS.pages || {};
    MPCS.actions = MPCS.actions || {};
    MPCS.utils = MPCS.utils || {};

    MPCS.utils.notify = function (type, message) {
        var text = message || 'Something went wrong. Please try again.';
        if (window.toastr && typeof window.toastr[type] === 'function') {
            window.toastr[type](text);
            return;
        }
        window.alert(text);
    };

    MPCS.utils.numberValue = function (value) {
        if (value === null || value === undefined) {
            return null;
        }

        var normalized = String(value).replace(/,/g, '').trim();
        if (normalized === '') {
            return null;
        }

        var number = Number(normalized);
        return isFinite(number) ? number : null;
    };

    MPCS.utils.setBusy = function (button, busy, savingText) {
        if (!button) {
            return;
        }

        if (busy) {
            if (!button.getAttribute('data-mpcs-original-html')) {
                button.setAttribute('data-mpcs-original-html', button.innerHTML);
            }
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            if (savingText) {
                button.textContent = savingText;
            }
            return;
        }

        button.disabled = false;
        button.removeAttribute('aria-busy');
        var original = button.getAttribute('data-mpcs-original-html');
        if (original) {
            button.innerHTML = original;
        }
    };

    MPCS.utils.setStatus = function (message) {
        var status = document.getElementById('f17_save_status');
        if (status) {
            status.textContent = message || '';
        }
    };

    MPCS.registerPage = function (name, initializer) {
        if (!name || typeof initializer !== 'function') {
            return;
        }
        MPCS.pages[name] = initializer;
    };

    MPCS.registerPage('f17', function (config) {
        var form = document.getElementById('mpcs_f17_form');
        var button = document.getElementById('f17_save');

        if (!form || !button) {
            return;
        }

        var messages = config.messages || {};
        var submitting = false;

        function getJQuery() {
            return window.jQuery || null;
        }

        function getTableApi() {
            var $ = getJQuery();
            if (!$ || !$.fn || !$.fn.DataTable) {
                return null;
            }

            try {
                if (!$.fn.DataTable.isDataTable('#form_17_table')) {
                    return null;
                }
                return $('#form_17_table').DataTable();
            } catch (error) {
                console.warn('[MPCS:F17] DataTable API unavailable.', error);
                return null;
            }
        }

        function appendField(params, name, value) {
            if (!name) {
                return;
            }

            if (value === null || value === undefined) {
                value = '';
            }

            params.append(name, String(value));
        }

        function collectPayload(tableApi) {
            var params = new URLSearchParams();
            var formData = new FormData(form);

            formData.forEach(function (value, name) {
                if (name.indexOf('F17[') !== 0) {
                    appendField(params, name, value);
                }
            });

            var fields = [];

            if (tableApi) {
                try {
                    var nodes = tableApi.rows({page: 'current'}).nodes().toArray();
                    var $ = getJQuery();
                    if ($) {
                        fields = $(nodes).find('input[name], select[name], textarea[name]').toArray();
                    }
                } catch (error) {
                    console.warn('[MPCS:F17] Could not read DataTable rows.', error);
                }
            }

            if (!fields.length) {
                fields = Array.prototype.slice.call(
                    form.querySelectorAll('#form_17_table input[name], #form_17_table select[name], #form_17_table textarea[name]')
                );
            }

            fields.forEach(function (field) {
                if (!field || field.disabled || !field.name) {
                    return;
                }

                if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) {
                    return;
                }

                appendField(params, field.name, field.value);
            });

            return params;
        }

        function hasValidPrice(tableApi) {
            var fields = [];

            if (tableApi) {
                try {
                    var $ = getJQuery();
                    if ($) {
                        var nodes = tableApi.rows({page: 'current'}).nodes().toArray();
                        fields = $(nodes).find('.new_price_value').toArray();
                    }
                } catch (error) {
                    console.warn('[MPCS:F17] Could not inspect DataTable prices.', error);
                }
            }

            if (!fields.length) {
                fields = Array.prototype.slice.call(
                    form.querySelectorAll('#form_17_table .new_price_value')
                );
            }

            return fields.some(function (field) {
                var value = MPCS.utils.numberValue(field.value);
                return value !== null && value > 0;
            });
        }

        function validate(tableApi) {
            var dateField = document.getElementById('f17_date');
            var locationField = document.getElementById('location_id');

            if (!dateField || !dateField.value) {
                MPCS.utils.notify('error', messages.dateRequired || 'Please select a date.');
                if (dateField) {
                    dateField.focus();
                }
                return false;
            }

            if (!locationField || !locationField.value) {
                MPCS.utils.notify('error', messages.locationRequired || 'Please select a business location.');
                if (locationField) {
                    locationField.focus();
                }
                return false;
            }

            if (!hasValidPrice(tableApi)) {
                MPCS.utils.notify(
                    'error',
                    messages.priceRequired || 'Please enter at least one valid new price before saving.'
                );
                return false;
            }

            return true;
        }

        function parseResponse(response) {
            return response.text().then(function (text) {
                var payload = null;

                try {
                    payload = text ? JSON.parse(text) : {};
                } catch (error) {
                    payload = {
                        success: 0,
                        msg: text || ('HTTP ' + response.status)
                    };
                }

                if (!response.ok) {
                    var failure = new Error(payload.msg || ('HTTP ' + response.status));
                    failure.payload = payload;
                    throw failure;
                }

                return payload;
            });
        }

        function submitF17(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
                if (typeof event.stopImmediatePropagation === 'function') {
                    event.stopImmediatePropagation();
                }
            }

            if (submitting) {
                return false;
            }

            var tableApi = getTableApi();

            if (!validate(tableApi)) {
                return false;
            }

            var params = collectPayload(tableApi);
            var saveUrl = config.saveUrl || form.action;

            submitting = true;
            MPCS.utils.setBusy(button, true, messages.saving || 'Saving...');
            MPCS.utils.setStatus(messages.saving || 'Saving...');

            console.info('[MPCS:F17] Sending save request.', {
                url: saveUrl,
                rowFields: Array.from(params.keys()).filter(function (key) {
                    return key.indexOf('F17[') === 0;
                }).length
            });

            window.fetch(saveUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': config.csrfToken || ''
                },
                body: params.toString()
            })
                .then(parseResponse)
                .then(function (result) {
                    if (!result || Number(result.success) !== 1) {
                        throw new Error(result && result.msg ? result.msg : (messages.error || 'Save failed.'));
                    }

                    MPCS.utils.setStatus(result.msg || messages.saved || 'Saved successfully.');
                    MPCS.utils.notify('success', result.msg || messages.saved || 'Saved successfully.');

                    window.setTimeout(function () {
                        window.location.assign(config.redirectUrl || saveUrl);
                    }, 300);
                })
                .catch(function (error) {
                    console.error('[MPCS:F17] Save failed.', error);
                    MPCS.utils.notify('error', error.message || messages.error || 'Save failed.');
                    MPCS.utils.setStatus(error.message || messages.error || 'Save failed.');
                    submitting = false;
                    MPCS.utils.setBusy(button, false);
                });

            return false;
        }

        MPCS.actions.f17Save = submitF17;

        /*
         * Capture the click before global application handlers. This avoids unrelated
         * global scripts cancelling the F17 Save button.
         */
        document.addEventListener('click', function (event) {
            var target = event.target;
            var clicked = target && target.closest ? target.closest('#f17_save') : null;

            if (!clicked) {
                return;
            }

            submitF17(event);
        }, true);

        /*
         * Form submission remains a secondary path for keyboard Enter and native
         * requestSubmit() calls.
         */
        form.addEventListener('submit', function (event) {
            submitF17(event);
        }, true);

        button.setAttribute('data-mpcs-ready', '1');
        MPCS.utils.setStatus('');
        console.info('[MPCS:F17] Save handler ready. MPCS JS ' + MPCS.version);
    });

    MPCS.registerPage('f15', function (config) {
        var $ = window.jQuery;
        var dateInput = document.getElementById('form_15_date_range');
        var printButton = document.getElementById('printButton');
        var closeAlertButton = document.getElementById('f15_close_alert');
        var requestSerial = 0;
        var abortController = null;

        if (!dateInput) {
            return;
        }

        var idMap = {
            form_number: '15f_form_no',
            form_9a_number: 'form_9a_number',
            store_purchase_book_no: 'store_purchase_book_no',
            direct_purchase_book_no: 'direct_purchase_book_no',
            opening_f22_book_refs: 'opening_stock_f22_book',
            price_increment_form_numbers: 'price_increment_form_numbers',
            price_reduction_form_numbers: 'price_reduction_form_numbers'
        };

        var classKeys = [
            'store_purchase_previous', 'store_purchase_today', 'store_purchase_total',
            'direct_purchase_previous', 'direct_purchase_today', 'direct_purchase_total',
            'sub_total_previous', 'sub_total_today', 'sub_total_total',
            'total_purchase_previous', 'total_purchase_today', 'total_purchase_total',
            'price_increment_previous', 'price_increment_today', 'price_increment_total',
            'opening_stock_previous', 'opening_stock_today', 'opening_stock_total',
            'cash_previous', 'cash_today', 'cash_total',
            'card_previous', 'card_today', 'card_total',
            'credit_previous', 'credit_today', 'credit_total',
            'price_reduction_previous', 'price_reduction_today', 'price_reduction_total',
            'grand_total1_previous', 'grand_total1_today', 'grand_total1_total',
            'total_sale_previous', 'total_sale_today', 'total_sale_total',
            'balance_stock_previous', 'balance_stock_today', 'balance_stock_total',
            'grand_total2_previous', 'grand_total2_today', 'grand_total2_total'
        ];

        function setTextById(id, value) {
            var element = document.getElementById(id);
            if (element) {
                element.textContent = value === null || value === undefined ? '' : String(value);
            }
        }

        function setTextByClass(className, value) {
            var text = value === null || value === undefined ? '' : String(value);
            document.querySelectorAll('.' + className).forEach(function (element) {
                element.textContent = text;
            });
        }

        function updateResult(result) {
            Object.keys(idMap).forEach(function (key) {
                setTextById(idMap[key], result[key]);
            });
            classKeys.forEach(function (key) {
                setTextByClass(key, result[key]);
            });

            var locationSelect = document.getElementById('15_location_id');
            var locationName = 'All';
            if (locationSelect && locationSelect.value) {
                var selectedOption = locationSelect.options[locationSelect.selectedIndex];
                locationName = selectedOption ? selectedOption.text : 'All';
            }
            document.querySelectorAll('.f15_location_name').forEach(function (element) {
                element.textContent = locationName;
            });

            var page = document.getElementById('mpcs-page');
            if (page) {
                page.setAttribute('data-f15-monthly-reset', result.is_monthly_reset ? '1' : '0');
                page.setAttribute('data-f15-f22-reset', result.is_f22_reset ? '1' : '0');
            }
        }

        function selectedDate() {
            if ($ && $.fn && $.fn.daterangepicker) {
                var picker = $(dateInput).data('daterangepicker');
                if (picker && picker.startDate) {
                    return picker.startDate.format('YYYY-MM-DD');
                }
            }
            return String(dateInput.value || '').trim();
        }

        function setLoading(loading) {
            var table = document.getElementById('form_f15_table');
            if (!table) return;
            table.setAttribute('aria-busy', loading ? 'true' : 'false');
            table.classList.toggle('mpcs-loading', !!loading);
        }

        function loadData() {
            var date = selectedDate();
            if (!date) {
                return;
            }

            requestSerial += 1;
            var serial = requestSerial;
            if (abortController && typeof abortController.abort === 'function') {
                abortController.abort();
            }
            abortController = typeof window.AbortController === 'function'
                ? new window.AbortController()
                : null;

            var params = new URLSearchParams();
            params.set('start_date', date);
            var location = document.getElementById('15_location_id');
            if (location && location.value) {
                params.set('location_id', location.value);
            }

            setLoading(true);
            window.fetch((config.dataUrl || '/mpcs/get-15-setting-data') + '?' + params.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                signal: abortController ? abortController.signal : undefined
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('F15 request failed with HTTP ' + response.status);
                }
                return response.json();
            }).then(function (result) {
                if (serial !== requestSerial) {
                    return;
                }
                updateResult(result || {});
            }).catch(function (error) {
                if (error && error.name === 'AbortError') {
                    return;
                }
                if (serial === requestSerial) {
                    MPCS.utils.notify('error', config.errorMessage || 'Unable to load F15 data.');
                }
            }).finally(function () {
                if (serial === requestSerial) {
                    setLoading(false);
                }
            });
        }

        function initializeDatePicker() {
            if (!$ || !$.fn || !$.fn.daterangepicker || !window.moment) {
                dateInput.addEventListener('change', loadData, false);
                loadData();
                return;
            }

            $(dateInput).daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                autoUpdateInput: true,
                startDate: window.moment(),
                endDate: window.moment(),
                locale: {format: 'YYYY-MM-DD'},
                ranges: {
                    'Today': [window.moment(), window.moment()],
                    'Yesterday': [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                    'Custom Date Range': [window.moment().startOf('month'), window.moment().endOf('month')]
                }
            }, function (start, end, label) {
                if (label === 'Custom Date Range') {
                    var modal = document.querySelector('.custom_date_typing_modal');
                    if (modal && $) {
                        $(modal).modal('show');
                    }
                    return;
                }
                dateInput.value = start.format('YYYY-MM-DD');
                document.querySelectorAll('.print-date-text').forEach(function (element) {
                    element.textContent = start.format('YYYY-MM-DD');
                });
                loadData();
            });

            $(dateInput).off('change.mpcsF15').on('change.mpcsF15', loadData);
            loadData();
        }

        if (printButton) {
            printButton.addEventListener('click', function (event) {
                event.preventDefault();
                window.print();
            }, false);
        }

        if (closeAlertButton) {
            closeAlertButton.addEventListener('click', function () {
                var alert = document.getElementById('custom-alert');
                if (alert) alert.style.display = 'none';
            }, false);
        }

        window.setTimeout(function () {
            var alert = document.getElementById('custom-alert');
            if (alert) alert.style.display = 'none';
        }, 3000);

        var locationSelect = document.getElementById('15_location_id');
        if (locationSelect) {
            locationSelect.addEventListener('change', loadData, false);
        }

        initializeDatePicker();
    });

    MPCS.init = function () {
        var bootstrap = window.MPCS_BOOTSTRAP || {};
        var marker = document.querySelector('[data-mpcs-page]');
        var page = bootstrap.page || (marker ? marker.getAttribute('data-mpcs-page') : null);
        var initializer = page ? MPCS.pages[page] : null;

        if (typeof initializer === 'function') {
            initializer(bootstrap.config || {});
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', MPCS.init, {once: true});
    } else {
        MPCS.init();
    }
})(window, document);
