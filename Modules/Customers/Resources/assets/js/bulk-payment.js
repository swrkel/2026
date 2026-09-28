(function () {
    'use strict';

    var RUNTIME_VERSION = '20260728-v5-performance';

    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    onReady(function () {
        var page = document.querySelector('[data-customer-bulk-payment-page]');
        if (!page) {
            return;
        }
        if (page.getAttribute('data-bulk-runtime-bound') === RUNTIME_VERSION) {
            return;
        }
        page.setAttribute('data-bulk-runtime-bound', RUNTIME_VERSION);
        window.__customersBulkPaymentRuntimeLoaded = RUNTIME_VERSION;

        var customerSelect = page.querySelector('#bulk_customer_id');
        var paymentGroupSelect = page.querySelector('#bulk_payment_group_id');
        var accountSelect = page.querySelector('#bulk_account_id');
        var paymentAmountInput = page.querySelector('#bulk_payment_amount');
        var invoiceBody = page.querySelector('#bulk_invoice_rows');
        var selectAll = page.querySelector('#bulk_select_all');
        var submitButton = page.querySelector('#bulk_submit_button');
        var form = page.querySelector('#customer_bulk_payment_form');
        var tableStatus = page.querySelector('#bulk_table_status');
        var interestMode = page.querySelector('#bulk_interest_mode');
        var customerDataUrl = page.getAttribute('data-customer-url');
        var customerSummaryUrl = page.getAttribute('data-customer-summary-url');
        var customerInvoicesUrl = page.getAttribute('data-customer-invoices-url');
        var accountUrl = page.getAttribute('data-account-url');
        var interestAvailable = page.getAttribute('data-interest-enabled') === '1';
        var oldAccountId = page.getAttribute('data-old-account-id') || '';
        var isSubmitting = false;
        var customerLoadSequence = 0;
        var customerSummaryController = null;
        var customerInvoicesController = null;
        var preloadedAccounts = {};
        var accountMapElement = document.getElementById('bulk_payment_accounts_map');
        if (accountMapElement) {
            try {
                preloadedAccounts = JSON.parse(accountMapElement.textContent || '{}') || {};
            } catch (ignore) {
                preloadedAccounts = {};
            }
        }

        var totalDueValue = page.querySelector('#bulk_total_due_value');
        var paymentSummaryValue = page.querySelector('#bulk_payment_summary_value');
        var allocatedSummaryValue = page.querySelector('#bulk_allocated_summary_value');
        var unallocatedSummaryValue = page.querySelector('#bulk_unallocated_summary_value');
        var totalDueInput = page.querySelector('#bulk_total_due');
        var pointsInput = page.querySelector('#bulk_points');

        var cardFields = page.querySelectorAll('[data-method-field="card"]');
        var chequeFields = page.querySelectorAll('[data-method-field="cheque"]');
        var bankFields = page.querySelectorAll('[data-method-field="bank"]');
        var postDatedFields = page.querySelectorAll('[data-method-field="post-dated"]');

        function number(value) {
            var cleaned = String(value == null ? '' : value).replace(/,/g, '').trim();
            var parsed = parseFloat(cleaned);
            return Number.isFinite(parsed) ? parsed : 0;
        }

        function money(value) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(number(value));
        }

        function setText(element, value) {
            if (element) {
                element.textContent = value;
            }
        }

        function setLoading(message) {
            if (tableStatus) {
                tableStatus.innerHTML = '<span class="bulk-loading"><span class="bulk-spinner"></span>' + message + '</span>';
            }
        }

        function setStatus(message) {
            if (tableStatus) {
                tableStatus.textContent = message;
            }
        }

        function showError(message) {
            var existing = page.querySelector('.bulk-runtime-error');
            if (!existing) {
                existing = document.createElement('div');
                existing.className = 'bulk-alert bulk-alert-error bulk-runtime-error';
                existing.innerHTML = '<i class="fa fa-exclamation-triangle"></i><div class="bulk-runtime-error-text"></div>';
                page.insertBefore(existing, page.firstChild.nextSibling || page.firstChild);
            }
            var text = existing.querySelector('.bulk-runtime-error-text');
            if (text) text.textContent = message;

            if (window.toastr && typeof window.toastr.error === 'function') {
                window.toastr.error(message);
            }
        }

        function enhanceSelect(element) {
            if (!element || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
                return;
            }

            var $element = window.jQuery(element);
            try {
                if ($element.data('select2')) {
                    $element.select2('destroy');
                }
                $element.select2({
                    width: '100%',
                    dropdownParent: window.jQuery(page)
                });
            } catch (ignore) {
                // Native select remains fully functional.
            }
        }

        function refreshSelect(element) {
            if (window.jQuery && element) {
                window.jQuery(element).trigger('change.select2');
            }
        }

        function bindSelectChange(element, handler, namespace) {
            if (!element) {
                return;
            }

            // Select2 in this ERP emits jQuery change/select2 events. Native
            // addEventListener alone does not reliably receive those events.
            if (window.jQuery) {
                var $element = window.jQuery(element);
                var ns = '.customersBulkPayment_' + namespace;
                $element.off(ns);
                // Select2 fires both select2:select and change for one choice.
                // Listening to both started duplicate balance/invoice requests.
                // The jQuery change event is sufficient for Select2 and native
                // selects and keeps each customer selection to one request pair.
                $element.on('change' + ns, function () {
                    handler();
                });
                return;
            }

            element.addEventListener('change', handler);
        }

        function setMethodVisibility(method) {
            function toggle(collection, visible) {
                Array.prototype.forEach.call(collection, function (field) {
                    field.classList.toggle('is-hidden', !visible);
                    Array.prototype.forEach.call(field.querySelectorAll('input, select, textarea'), function (input) {
                        if (!visible) {
                            input.removeAttribute('required');
                        }
                    });
                });
            }

            toggle(cardFields, method === 'card');
            toggle(chequeFields, method === 'cheque');
            toggle(bankFields, method === 'bank_transfer' || method === 'cheque');
            toggle(postDatedFields, method === 'cheque');

            var chequeNumber = page.querySelector('#bulk_cheque_number');
            var chequeDate = page.querySelector('#bulk_cheque_date');
            var bankName = page.querySelector('#bulk_bank_name');
            if (method === 'cheque') {
                if (chequeNumber) chequeNumber.setAttribute('required', 'required');
                if (chequeDate) chequeDate.setAttribute('required', 'required');
                if (bankName) bankName.setAttribute('required', 'required');
            }
        }

        function selectedMethod() {
            if (!paymentGroupSelect || paymentGroupSelect.selectedIndex < 0) {
                return '';
            }
            var option = paymentGroupSelect.options[paymentGroupSelect.selectedIndex];
            return option ? (option.getAttribute('data-method') || '') : '';
        }

        function updateInterestVisibility() {
            var visible = interestAvailable && interestMode && interestMode.value === 'yes';
            Array.prototype.forEach.call(page.querySelectorAll('.bulk-interest-column'), function (column) {
                column.classList.toggle('is-hidden', !visible);
            });

            Array.prototype.forEach.call(page.querySelectorAll('.bulk-interest-input'), function (input) {
                var row = input.closest('.bulk-invoice-row');
                var checked = row && row.querySelector('.bulk-row-check') && row.querySelector('.bulk-row-check').checked;
                input.disabled = !visible || !checked;
                if (!visible) {
                    input.value = '0.00';
                }
            });
            recalculate();
        }

        function invoiceRows() {
            return Array.prototype.slice.call(page.querySelectorAll('.bulk-invoice-row'));
        }

        function recalculate() {
            var paymentAmount = number(paymentAmountInput ? paymentAmountInput.value : 0);
            var allocated = 0;
            var selectedCount = 0;

            invoiceRows().forEach(function (row) {
                var checkbox = row.querySelector('.bulk-row-check');
                var allocationInput = row.querySelector('.bulk-allocation-input');
                var interestInput = row.querySelector('.bulk-interest-input');
                var principal = checkbox && checkbox.checked ? number(allocationInput ? allocationInput.value : 0) : 0;
                var interest = checkbox && checkbox.checked && interestMode && interestMode.value === 'yes'
                    ? number(interestInput ? interestInput.value : 0)
                    : 0;
                var outstanding = number(row.getAttribute('data-outstanding'));

                if (principal > outstanding) {
                    principal = outstanding;
                    if (allocationInput) allocationInput.value = principal.toFixed(2);
                }

                if (checkbox && checkbox.checked) {
                    selectedCount += 1;
                }

                allocated += principal + interest;
                row.classList.toggle('is-selected', !!(checkbox && checkbox.checked));
                setText(row.querySelector('.bulk-row-total'), money(principal + interest));
            });

            var unallocated = paymentAmount - allocated;
            setText(paymentSummaryValue, money(paymentAmount));
            setText(allocatedSummaryValue, money(allocated));
            setText(unallocatedSummaryValue, money(unallocated));

            if (unallocatedSummaryValue) {
                unallocatedSummaryValue.style.color = unallocated < -0.005 ? '#b42318' : (unallocated > 0.005 ? '#b45309' : '#166534');
            }

            var valid = !!customerSelect.value && !!paymentGroupSelect.value && !!accountSelect.value && paymentAmount > 0 && unallocated >= -0.005;
            if (submitButton) {
                submitButton.disabled = !valid || isSubmitting;
            }

            if (selectAll) {
                var rows = invoiceRows();
                selectAll.checked = rows.length > 0 && rows.every(function (row) {
                    return row.querySelector('.bulk-row-check').checked;
                });
                selectAll.indeterminate = rows.some(function (row) {
                    return row.querySelector('.bulk-row-check').checked;
                }) && !selectAll.checked;
            }

            setStatus(invoiceRows().length + ' outstanding invoice' + (invoiceRows().length === 1 ? '' : 's') + ' • ' + selectedCount + ' selected');
        }

        function resetInvoiceTable() {
            if (invoiceBody) {
                invoiceBody.innerHTML = '<tr class="bulk-empty-row"><td colspan="10"><div class="bulk-empty-state"><i class="fa fa-user-circle-o"></i><strong>Select a customer</strong><span>Outstanding invoices will load instantly here.</span></div></td></tr>';
            }
            if (totalDueInput) totalDueInput.value = '';
            if (pointsInput) pointsInput.value = '';
            setText(totalDueValue, money(0));
            recalculate();
        }

        function fetchJson(url, controller) {
            return window.fetch(url, {
                method: 'GET',
                cache: 'no-store',
                signal: controller ? controller.signal : undefined,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json().catch(function () {
                    return { success: false, message: 'The server returned an invalid response.' };
                }).then(function (payload) {
                    if (!response.ok) {
                        throw new Error(payload.message || 'Unable to load the selected customer.');
                    }
                    return payload;
                });
            });
        }

        function loadCustomer() {
            var customerId = customerSelect ? customerSelect.value : '';
            customerLoadSequence += 1;
            var sequence = customerLoadSequence;

            if (customerSummaryController) customerSummaryController.abort();
            if (customerInvoicesController) customerInvoicesController.abort();
            customerSummaryController = typeof AbortController !== 'undefined' ? new AbortController() : null;
            customerInvoicesController = typeof AbortController !== 'undefined' ? new AbortController() : null;

            if (!customerId) {
                resetInvoiceTable();
                return;
            }

            if (totalDueInput) totalDueInput.value = 'Loading...';
            if (pointsInput) pointsInput.value = '';
            setText(totalDueValue, '...');
            setLoading('Loading outstanding invoices...');
            if (invoiceBody) {
                invoiceBody.innerHTML = '<tr><td colspan="10"><div class="bulk-empty-state"><span class="bulk-spinner"></span><strong>Loading outstanding invoices</strong><span>The current balance is loading separately.</span></div></td></tr>';
            }

            // New optimized endpoints: the balance and detailed rows load in
            // parallel. The user sees Total Due as soon as the small aggregate
            // response completes, without waiting for invoice HTML rendering.
            if (customerSummaryUrl && customerInvoicesUrl) {
                var summaryUrl = customerSummaryUrl.replace('__CUSTOMER__', encodeURIComponent(customerId));
                summaryUrl += (summaryUrl.indexOf('?') === -1 ? '?' : '&') + '_=' + Date.now();

                var invoicesUrl = customerInvoicesUrl.replace('__CUSTOMER__', encodeURIComponent(customerId));
                invoicesUrl += (invoicesUrl.indexOf('?') === -1 ? '?' : '&') +
                    'interest_enabled=' + (interestAvailable ? '1' : '0') + '&_=' + Date.now();

                fetchJson(summaryUrl, customerSummaryController).then(function (result) {
                    if (sequence !== customerLoadSequence || !result.success) return;
                    if (totalDueInput) totalDueInput.value = money(result.total_due);
                    if (pointsInput) pointsInput.value = number(result.points).toFixed(2);
                    setText(totalDueValue, money(result.total_due));
                }).catch(function (error) {
                    if (error && error.name === 'AbortError') return;
                    if (sequence !== customerLoadSequence) return;
                    if (totalDueInput) totalDueInput.value = '';
                    setText(totalDueValue, money(0));
                    showError(error.message || 'Unable to load the selected customer balance.');
                });

                fetchJson(invoicesUrl, customerInvoicesController).then(function (result) {
                    if (sequence !== customerLoadSequence) return;
                    if (!result.success) {
                        throw new Error(result.message || 'Unable to load customer invoices.');
                    }
                    if (invoiceBody) invoiceBody.innerHTML = result.invoice_html || '';
                    updateInterestVisibility();
                    recalculate();
                }).catch(function (error) {
                    if (error && error.name === 'AbortError') return;
                    if (sequence !== customerLoadSequence) return;
                    if (invoiceBody) {
                        invoiceBody.innerHTML = '<tr class="bulk-empty-row"><td colspan="10"><div class="bulk-empty-state"><i class="fa fa-exclamation-triangle"></i><strong>Unable to load outstanding invoices</strong><span>Please select the customer again.</span></div></td></tr>';
                    }
                    setStatus('Unable to load outstanding invoices');
                    showError(error.message || 'Unable to load customer invoices.');
                });
                return;
            }

            // Backward-compatible combined endpoint for older installations.
            var url = customerDataUrl.replace('__CUSTOMER__', encodeURIComponent(customerId));
            url += (url.indexOf('?') === -1 ? '?' : '&') + 'interest_enabled=' + (interestAvailable ? '1' : '0');
            url += '&_=' + Date.now();

            fetchJson(url, customerInvoicesController).then(function (result) {
                if (sequence !== customerLoadSequence) return;
                if (!result.success) {
                    throw new Error(result.message || 'Unable to load the selected customer.');
                }
                if (totalDueInput) totalDueInput.value = money(result.total_due);
                if (pointsInput) pointsInput.value = number(result.points).toFixed(2);
                setText(totalDueValue, money(result.total_due));
                if (invoiceBody) invoiceBody.innerHTML = result.invoice_html || '';
                updateInterestVisibility();
                recalculate();
            }).catch(function (error) {
                if (error && error.name === 'AbortError') return;
                if (sequence !== customerLoadSequence) return;
                resetInvoiceTable();
                showError(error.message || 'Unable to load customer invoices.');
            });
        }

        function populateAccounts(accounts, emptyMessage) {
            if (!accountSelect) {
                return 0;
            }

            var entries = Object.keys(accounts || {});
            accountSelect.innerHTML = '<option value="">Please select</option>';
            entries.forEach(function (id) {
                var option = document.createElement('option');
                option.value = id;
                option.textContent = accounts[id];
                if (String(id) === String(oldAccountId)) {
                    option.selected = true;
                }
                accountSelect.appendChild(option);
            });

            if (!entries.length && emptyMessage) {
                accountSelect.innerHTML = '<option value="">' + emptyMessage + '</option>';
            }

            accountSelect.disabled = false;
            refreshSelect(accountSelect);
            return entries.length;
        }

        function loadAccounts() {
            var groupId = paymentGroupSelect ? paymentGroupSelect.value : '';
            var method = selectedMethod();
            setMethodVisibility(method);

            if (!accountSelect) {
                return;
            }

            if (!groupId) {
                populateAccounts({}, 'Please select payment method first');
                recalculate();
                return;
            }

            // Accounts are preloaded by the Customers service so the dropdown
            // opens instantly. AJAX remains as a schema-safe refresh/fallback.
            if (Object.prototype.hasOwnProperty.call(preloadedAccounts, String(groupId))) {
                var preloadedCount = populateAccounts(preloadedAccounts[String(groupId)] || {}, 'No linked accounts found');
                recalculate();
                if (preloadedCount > 0) {
                    return;
                }
            }

            accountSelect.innerHTML = '<option value="">Loading accounts...</option>';
            accountSelect.disabled = true;
            refreshSelect(accountSelect);

            var url = accountUrl.replace('__GROUP__', encodeURIComponent(groupId));
            url += (url.indexOf('?') === -1 ? '?' : '&') + '_=' + Date.now();
            window.fetch(url, {
                method: 'GET',
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json().catch(function () {
                    return { success: false, message: 'Unable to load payment accounts.' };
                }).then(function (payload) {
                    if (!response.ok) {
                        throw new Error(payload.message || 'Unable to load payment accounts.');
                    }
                    return payload;
                });
            }).then(function (result) {
                if (!result.success) {
                    throw new Error(result.message || 'Unable to load payment accounts.');
                }
                preloadedAccounts[String(groupId)] = result.accounts || {};
                populateAccounts(result.accounts || {}, 'No linked accounts found');
                recalculate();
            }).catch(function (error) {
                populateAccounts({}, 'No linked accounts found');
                showError(error.message || 'Unable to load payment accounts.');
                recalculate();
            });
        }

        function remainingForRow(currentRow) {
            var paymentAmount = number(paymentAmountInput.value);
            var used = 0;
            invoiceRows().forEach(function (row) {
                if (row === currentRow) return;
                var check = row.querySelector('.bulk-row-check');
                if (!check || !check.checked) return;
                used += number(row.querySelector('.bulk-allocation-input').value);
                if (interestMode && interestMode.value === 'yes') {
                    used += number(row.querySelector('.bulk-interest-input').value);
                }
            });
            return Math.max(paymentAmount - used, 0);
        }

        function toggleRow(row, checked, autoFill) {
            var allocationInput = row.querySelector('.bulk-allocation-input');
            var interestInput = row.querySelector('.bulk-interest-input');
            var outstanding = number(row.getAttribute('data-outstanding'));

            if (allocationInput) {
                allocationInput.disabled = !checked;
                if (!checked) {
                    allocationInput.value = '';
                } else if (autoFill && number(allocationInput.value) <= 0) {
                    allocationInput.value = Math.min(outstanding, remainingForRow(row)).toFixed(2);
                }
            }

            if (interestInput) {
                interestInput.disabled = !checked || !(interestMode && interestMode.value === 'yes');
                if (!checked) interestInput.value = '0.00';
            }

            recalculate();
        }

        function payAllRows() {
            var rows = invoiceRows();
            if (!rows.length) return;

            if (number(paymentAmountInput.value) <= 0) {
                var totalOutstanding = rows.reduce(function (sum, row) {
                    return sum + number(row.getAttribute('data-outstanding'));
                }, 0);
                paymentAmountInput.value = totalOutstanding.toFixed(2);
            }

            var remaining = number(paymentAmountInput.value);
            rows.forEach(function (row) {
                var checkbox = row.querySelector('.bulk-row-check');
                var allocationInput = row.querySelector('.bulk-allocation-input');
                var outstanding = number(row.getAttribute('data-outstanding'));
                var allocation = Math.min(outstanding, Math.max(remaining, 0));

                checkbox.checked = allocation > 0;
                allocationInput.disabled = allocation <= 0;
                allocationInput.value = allocation > 0 ? allocation.toFixed(2) : '';
                remaining -= allocation;

                var interestInput = row.querySelector('.bulk-interest-input');
                if (interestInput) {
                    interestInput.disabled = allocation <= 0 || !(interestMode && interestMode.value === 'yes');
                    if (allocation <= 0) interestInput.value = '0.00';
                }
            });
            recalculate();
        }

        if (customerSelect) {
            enhanceSelect(customerSelect);
            bindSelectChange(customerSelect, loadCustomer, 'customer');
        }

        if (paymentGroupSelect) {
            enhanceSelect(paymentGroupSelect);
            bindSelectChange(paymentGroupSelect, loadAccounts, 'payment_method');
        }

        if (accountSelect) {
            enhanceSelect(accountSelect);
            bindSelectChange(accountSelect, recalculate, 'payment_account');
        }

        if (paymentAmountInput) {
            paymentAmountInput.addEventListener('input', recalculate);
            paymentAmountInput.addEventListener('change', recalculate);
        }

        if (interestMode) {
            enhanceSelect(interestMode);
            bindSelectChange(interestMode, updateInterestVisibility, 'interest');
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                if (selectAll.checked) {
                    payAllRows();
                } else {
                    invoiceRows().forEach(function (row) {
                        var checkbox = row.querySelector('.bulk-row-check');
                        checkbox.checked = false;
                        toggleRow(row, false, false);
                    });
                    recalculate();
                }
            });
        }

        if (invoiceBody) {
            invoiceBody.addEventListener('change', function (event) {
                var row = event.target.closest('.bulk-invoice-row');
                if (!row) return;

                if (event.target.classList.contains('bulk-row-check')) {
                    toggleRow(row, event.target.checked, true);
                    return;
                }
                recalculate();
            });

            invoiceBody.addEventListener('input', function (event) {
                if (event.target.classList.contains('bulk-allocation-input') || event.target.classList.contains('bulk-interest-input')) {
                    recalculate();
                }
            });
        }

        if (form) {
            form.addEventListener('submit', function (event) {
                recalculate();
                if (!customerSelect.value || !paymentGroupSelect.value || !accountSelect.value || number(paymentAmountInput.value) <= 0) {
                    event.preventDefault();
                    showError('Please complete Customer, Payment Method, Payment Account and Payment Amount.');
                    return;
                }

                var allocated = number((allocatedSummaryValue || {}).textContent);
                var paymentAmount = number(paymentAmountInput.value);
                if (allocated - paymentAmount > 0.005) {
                    event.preventDefault();
                    showError('Allocated amount cannot exceed the Payment Amount.');
                    return;
                }

                isSubmitting = true;
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="bulk-spinner"></span> Saving Bulk Payment...';
            });
        }

        setMethodVisibility(selectedMethod());
        updateInterestVisibility();
        recalculate();
        if (!customerSelect || !customerSelect.value) {
            setStatus('Select a customer to load outstanding invoices');
        }

        if (paymentGroupSelect && paymentGroupSelect.value) {
            loadAccounts();
        }
        if (customerSelect && customerSelect.value) {
            loadCustomer();
        }
    });
})();
