{{-- Payments behaviour — 8048 + IS2230. --}}
<script>
$(function () {

    var swPayments = [];
    var swPaySeq = 0;
    var swPayType = null;
    var swAccountRequest = null;
    var swAccountRequestSeq = 0;
    var swExpenseCategoryRequest = null;
    var swExpenseCategoryPrimaryXhr = null;
    var swExpenseCategoryRequestSeq = 0;
    var swExpenseCategoryRefreshedAt = 0;
    var swExpenseOpeningAfterRefresh = false;
    var swPaymentSummaryRequestSeq = 0;

    function n(v) { return parseFloat(v) || 0; }
    function f2(v) { return n(v).toFixed(2); }
    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }

    /*
     | IS2230: every payment type declares exactly which selectors it needs.
     | This keeps the UI, validation and saved row in agreement.
    */
    var swPayNeeds = {
        cash:             { customer: false, account: false, expenseCategory: false },
        cash_deposit:     { customer: false, account: true,  expenseCategory: false },
        card:             { customer: false, account: true,  expenseCategory: false },
        cheque:           { customer: true,  account: true,  expenseCategory: false },
        expense:          { customer: false, account: true,  expenseCategory: true  },
        shortage:         { customer: false, account: false, expenseCategory: false },
        excess:           { customer: false, account: false, expenseCategory: false },
        credit_sale:      { customer: false, account: false, expenseCategory: false },
        loan_payment:     { customer: false, account: true,  expenseCategory: false },
        owners_drawing:   { customer: false, account: true,  expenseCategory: false },
        loan_to_customer: { customer: true,  account: false, expenseCategory: false }
    };

    var swPayLabels = {};
    $('.sw-pay-btn').each(function () {
        swPayLabels[$(this).data('type')] = $.trim($(this).text());
    });

    /*
     | S728 - payment type buttons must always remain usable.
     |
     | The settlement body starts hidden and is revealed after a CLOSED shift is
     | selected.  Binding click handlers directly to the current button nodes made
     | this fragile when the section was restored/re-rendered by draft/shift logic
     | or when another page script replaced one of the controls.  Register one
     | delegated handler immediately, before Select2/AJAX initialisation, so Cash,
     | Cards, Credit Sales, Shortage and Excess (and the remaining payment types)
     | always open from the live DOM.
     */
    function swActivatePaymentType($button) {
        if (!$button || !$button.length) { return; }

        swPayType = String($button.data('type') || '');
        if (!swPayType) { return; }

        if (!swPayLabels[swPayType]) {
            swPayLabels[swPayType] = $.trim($button.text());
        }

        $('.sw-pay-btn').removeClass('is-active').attr({'aria-pressed': 'false', 'aria-selected': 'false'});
        $button.addClass('is-active').attr({'aria-pressed': 'true', 'aria-selected': 'true'});

        var needs = swPayNeeds[swPayType] || {
            customer: false, account: false, expenseCategory: false
        };
        var detailedCreditSale = swPayType === 'credit_sale';

        $('#sw_pay_account_label').text(
            swPayType === 'loan_payment'
                ? '{{ __('sw::lang.bank') }}:'
                : '{{ __('sw::lang.account') }}:'
        );

        // Settlement accepts CLOSED shifts only, so Cash shown here is the
        // authoritative Daily Cash collected while the shift was OPEN.  Add,
        // edit and remove Cash at SW Payments -> Daily Cash while the source
        // shift is OPEN; once closed it is intentionally read-only here.
        var cashReadOnly = swPayType === 'cash';
        $('#sw_sec_credit_sales').toggle(detailedCreditSale);
        $('.sw-pay-entry').toggle(!detailedCreditSale && !cashReadOnly);
        $('#sw_cash_source_notice').toggle(cashReadOnly);

        // Physically render only the currently selected payment type.  Rows for
        // every other type remain only in the submission array/hidden inputs and
        // never exist in the visible table at the same time.
        swRenderActivePaymentRows();

        $('.sw-pay-field[data-for="customer"]').toggle(!detailedCreditSale && !!needs.customer);
        $('.sw-pay-field[data-for="account"]').toggle(!detailedCreditSale && !!needs.account);
        $('.sw-pay-field[data-for="expense_category"]').toggle(!detailedCreditSale && !!needs.expenseCategory);

        // Never allow an old hidden value to leak into another payment type.
        if (!needs.customer) {
            $('#sw_pay_customer').val('').trigger('change.select2');
        }
        if (!needs.expenseCategory) {
            $('#sw_pay_expense_category').val('').trigger('change.select2');
        }

        if (detailedCreditSale) {
            // The embedded Credit Sales section is collapsed/hidden until this
            // payment type is selected. Keep it visibly open once selected.
            $('#sw_sec_credit_sales').show();
            $('#sw_body_credit_sales').addClass('in').show();
            $('#sw_sec_credit_sales .sw-section-head').removeClass('collapsed')
                .attr('aria-expanded', 'true');
            setTimeout(function () { $('#sw_cs_customer').focus(); }, 0);
            return;
        }

        // Reset the embedded credit-sale section when another type is chosen.
        $('#sw_body_credit_sales').removeClass('in').hide();
        $('#sw_sec_credit_sales .sw-section-head').addClass('collapsed')
            .attr('aria-expanded', 'false');

        if (swPayType === 'expense') {
            swRefreshExpenseCategories();
        } else {
            swLoadRelatedAccounts();
        }

        setTimeout(function () { $('#sw_pay_amount').focus(); }, 0);
    }

    /*
     | One authoritative payment-tab handler.
     |
     | Bind on the SW Payments section itself so this handler runs before any
     | document-level Bootstrap/AdminLTE/global tab handler.  Stop propagation
     | after handling the click; this prevents a second legacy handler from
     | immediately undoing the selected payment type.
     */
    $('#sw_sec_payments')
        .off('click.swSettlementPaymentType', '.sw-pay-btn')
        .on('click.swSettlementPaymentType', '.sw-pay-btn', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            var $button = $(this);
            $button.prop('disabled', false)
                .removeAttr('disabled aria-disabled')
                .removeClass('disabled');

            swActivatePaymentType($button);
            return false;
        });

    // ALL SW payment tabs are selectable.  No legacy/global script is allowed
    // to leave one of these eleven controls disabled or visually unavailable.
    function swKeepAllPaymentTypesOpen() {
        $('#sw_sec_payments .sw-pay-btn')
            .prop('disabled', false)
            .removeAttr('disabled aria-disabled')
            .removeClass('disabled')
            .css({'pointer-events': 'auto', 'opacity': 1});
    }
    swKeepAllPaymentTypesOpen();
    $(document).off('.swPaymentTabsAlwaysOpen')
        .on('sw:settlement-payment-summary-loaded.swPaymentTabsAlwaysOpen sw:settlement-shift-loaded.swPaymentTabsAlwaysOpen', swKeepAllPaymentTypesOpen);

    // If a global theme/plugin later reapplies a disabled class/attribute, remove
    // only that state immediately.  Active/inactive styling is otherwise left
    // untouched.
    if (window.MutationObserver && document.getElementById('sw_sec_payments')) {
        new MutationObserver(function (mutations) {
            $.each(mutations || [], function (i, mutation) {
                var $target = $(mutation.target);
                if (!$target.hasClass('sw-pay-btn')) { return; }
                if ($target.prop('disabled') || $target.hasClass('disabled') || $target.attr('aria-disabled') === 'true') {
                    $target.prop('disabled', false)
                        .removeAttr('disabled aria-disabled')
                        .removeClass('disabled');
                }
            });
        }).observe(document.getElementById('sw_sec_payments'), {
            subtree: true,
            attributes: true,
            attributeFilter: ['disabled', 'aria-disabled', 'class']
        });
    }

    function swInitSearchSelect($select) {
        if (!$.fn.select2 || !$select.length) { return; }

        // Do not stack Select2 wrappers if the partial is re-rendered.
        if ($select.hasClass('select2-hidden-accessible')) {
            try { $select.select2('destroy'); } catch (ignore) {}
        }

        $select.select2({
            width: '100%',
            allowClear: true,
            minimumResultsForSearch: 0,
            placeholder: '{{ __('messages.please_select') }}'
        });
    }

    swInitSearchSelect($('#sw_pay_customer'));
    swInitSearchSelect($('#sw_pay_account'));
    swInitSearchSelect($('#sw_pay_expense_category'));

    /* Customers are business-scoped already. Select2 supplies type-and-filter. */
    $.get('{{ route('sw.settlements.customers') }}', function (rows) {
        var $sel = $('#sw_pay_customer');
        $sel.find('option:not(:first)').remove();
        $.each(rows || [], function (i, r) {
            $sel.append($('<option>', { value: r.id, text: r.text }));
        });
        $sel.trigger('change.select2');
    }).fail(function () {
        toastr.error('{{ __('sw::lang.could_not_load_customers') }}');
    });

    /*
     | Related accounts only.
     |
     | The old code loaded every Finance account once. IS2230 requires the
     | account list to follow the selected payment type, so this request is made
     | when the type changes (and again for an Expense category change).
    */
    function swLoadRelatedAccounts() {
        var needs = swPayNeeds[swPayType] || {};
        var $sel = $('#sw_pay_account');

        if (swAccountRequest && swAccountRequest.readyState !== 4) {
            swAccountRequest.abort();
        }

        $sel.empty().append($('<option>', {
            value: '',
            text: needs.account ? '{{ __('sw::lang.loading') }}' : '{{ __('messages.please_select') }}'
        })).prop('disabled', !!needs.account);

        if (!needs.account || !swPayType) {
            $sel.prop('disabled', false).trigger('change');
            return;
        }

        var requestSeq = ++swAccountRequestSeq;
        swAccountRequest = $.get('{{ route('sw.settlements.payment-accounts') }}', {
            payment_type: swPayType,
            expense_category_id: $('#sw_pay_expense_category').val() || ''
        }, function (rows) {
            if (requestSeq !== swAccountRequestSeq) { return; }

            $sel.empty().append($('<option>', {
                value: '', text: '{{ __('messages.please_select') }}'
            }));

            $.each(rows || [], function (i, r) {
                $sel.append($('<option>', { value: r.id, text: r.text }));
            });

            if (!(rows || []).length) {
                $sel.append($('<option>', {
                    value: '',
                    text: '{{ __('sw::lang.no_related_accounts') }}',
                    disabled: true
                }));
            }

            $sel.prop('disabled', false).trigger('change');
        }).fail(function (xhr, status) {
            if (status === 'abort' || requestSeq !== swAccountRequestSeq) { return; }

            $sel.empty().append($('<option>', {
                value: '', text: '{{ __('sw::lang.could_not_load_accounts') }}'
            })).prop('disabled', false).trigger('change');
        });
    }

    /*
     * IS2267: Expense Categories are refreshed from SW's live tenant-scoped
     * endpoint. Expenses New is standalone and writes its category master to
     * expnew_categories; the old core expense_categories feed is NOT the same
     * data source and must not be used as a fallback.
     *
     * The Blade's initially-rendered options and this AJAX endpoint now use the
     * same ExpenseCategoryLookupService, so a refresh cannot swap a correct
     * Expenses New list for a legacy list with unrelated IDs.
     */
    function swApplyExpenseCategoryRows(rows, current, requestSeq) {
        if (requestSeq !== swExpenseCategoryRequestSeq) { return; }

        var $sel = $('#sw_pay_expense_category');
        if (!$sel.length) { return; }

        var $fresh = $('<select>');
        $fresh.append($('<option>', {
            value: '', text: '{{ __('messages.please_select') }}'
        }));

        $.each(rows || [], function (i, r) {
            if (r && r.id != null) {
                $fresh.append($('<option>', { value: r.id, text: r.text || r.name || '' }));
            }
        });

        if ($sel.hasClass('select2-hidden-accessible')) {
            try { $sel.select2('destroy'); } catch (ignore) {}
        }

        $sel.empty().append($fresh.children());

        // IS2257: merge any options already present in the rendered settlement
        // instead of throwing them away. This protects tenants where Expenses New
        // uses the same business category list but an older endpoint shape.
        $sel.data('sw-live-category-count', $sel.find('option').length - 1);

        if (current && $sel.find('option').filter(function () {
            return String(this.value) === current;
        }).length) {
            $sel.val(current);
        } else {
            $sel.val('');
        }

        swInitSearchSelect($sel);
        $sel.trigger('change.select2');
        swExpenseCategoryRefreshedAt = Date.now();

        if (swPayType === 'expense') {
            swLoadRelatedAccounts();
        }
    }

    function swAbortExpenseCategoryRequests() {
        if (swExpenseCategoryPrimaryXhr && swExpenseCategoryPrimaryXhr.readyState !== 4) {
            swExpenseCategoryPrimaryXhr.abort();
        }
    }

    function swRefreshExpenseCategories() {
        var $sel = $('#sw_pay_expense_category');
        if (!$sel.length) { return null; }

        var current = String($sel.val() || '');

        /*
         * IS2263 - there must be only ONE live category refresh at a time.
         *
         * The previous build had two select2:opening handlers. Each opening
         * started a second request that aborted the first one, then the aborted
         * request reopened Select2 before the fresh list was applied. That race
         * is why a newly-created Expenses New category could still be missing.
         */
        swAbortExpenseCategoryRequests();

        var requestSeq = ++swExpenseCategoryRequestSeq;
        var gate = $.Deferred();
        swExpenseCategoryRequest = gate.promise();

        /*
         * The SW endpoint reads Expenses New's expnew_categories directly from
         * the CURRENT tenant database. It is the only authoritative live path.
         */
        swExpenseCategoryPrimaryXhr = $.ajax({
            url: '{{ route('sw.settlements.expense-categories') }}',
            method: 'GET',
            dataType: 'json',
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache'
            },
            data: {
                location_id: $('#sw_st_location').val() || '',
                _: Date.now()
            }
        }).done(function (rows) {
            if (requestSeq !== swExpenseCategoryRequestSeq) {
                gate.reject();
                return;
            }

            swApplyExpenseCategoryRows(rows, current, requestSeq);
            gate.resolve();
        }).fail(function (xhr, status) {
            if (status === 'abort' || requestSeq !== swExpenseCategoryRequestSeq) {
                gate.reject();
                return;
            }

            /*
             * Do NOT fall back to /expense-categories/get-drop-down. That
             * endpoint serves the legacy expense_categories master, while the
             * category the user just added through Expenses New lives in
             * expnew_categories. On a temporary AJAX failure keep the already
             * rendered correct list instead of replacing it with wrong data.
             */
            gate.reject();

        });

        return swExpenseCategoryRequest;
    }

    // Load the authoritative list at page start. The existing Blade options
    // remain visible until the live request succeeds, so a temporary network
    // failure cannot blank the field.
    swRefreshExpenseCategories();

    /*
     * IS2260: make the Expense Category dropdown authoritative at the instant
     * it opens. Earlier code refreshed on page/window events, but Select2 could
     * render its cached option list before that asynchronous request completed.
     * Hold one opening until the tenant DB refresh finishes, then reopen once.
     */
    $(document).off('select2:opening.swExpenseCategories', '#sw_pay_expense_category')
        .on('select2:opening.swExpenseCategories', '#sw_pay_expense_category', function (e) {
            if (swPayType !== 'expense') { return; }

            if (swExpenseOpeningAfterRefresh) {
                swExpenseOpeningAfterRefresh = false;
                return;
            }

            /*
             * IS2263 - always verify the category list at the instant the user
             * opens it. A category may have been added in Expenses New only a
             * moment earlier, so even a sub-second cached list is not accepted.
             */
            var $sel = $(this);
            var request = swRefreshExpenseCategories();
            var openingSeq = swExpenseCategoryRequestSeq;
            if (!request || typeof request.always !== 'function') { return; }

            e.preventDefault();
            request.always(function () {
                // An older aborted refresh must never reopen the picker.
                if (openingSeq !== swExpenseCategoryRequestSeq) {
                    return;
                }
                if (!$sel.closest('html').length || !$sel.hasClass('select2-hidden-accessible')) {
                    return;
                }
                swExpenseOpeningAfterRefresh = true;
                $sel.select2('open');
            });
        });

    // Category creation is commonly done in another tab/window. Refresh as the
    // user comes back to the settlement.
    $(window).off('focus.swExpenseCategories').on('focus.swExpenseCategories', function () {
        if (swPayType === 'expense') {
            swRefreshExpenseCategories();
        }
    });

    // If the user navigates to Expense Categories in the SAME browser tab and
    // returns with Back, browsers may restore this page from the back/forward
    // cache without running document-ready again. pageshow fixes that case.
    $(window).off('pageshow.swExpenseCategories').on('pageshow.swExpenseCategories', function (event) {
        var original = event.originalEvent || event;
        if (original && original.persisted) {
            swRefreshExpenseCategories();
        }
    });

    // Some browsers do not emit window focus when switching mobile/desktop
    // tabs, but they do change document visibility. Refresh when the settlement
    // becomes visible again while Expenses is selected.
    $(document).off('visibilitychange.swExpenseCategories')
        .on('visibilitychange.swExpenseCategories', function () {
            if (!document.hidden && swPayType === 'expense') {
                swRefreshExpenseCategories();
            }
        });

    // S728: payment type activation is delegated near the top of this script.
    // Keeping a second direct binding here would execute every click twice.

    $('#sw_pay_expense_category').on('change', function () {
        if (swPayType === 'expense') {
            swLoadRelatedAccounts();
        }
    });

    $('#sw_pay_add').on('click', function () {
        if (!swPayType) {
            toastr.error('{{ __('sw::lang.choose_a_payment_type') }}');
            return;
        }

        // Cash on a CLOSED shift is always reloaded from Daily Cash. This is a
        // server rule as well, so never let a stale/global click handler create
        // a manual Cash row that appears in Balance but will be ignored on Save.
        if (swPayType === 'cash') {
            toastr.error('{{ __('sw::lang.closed_shift_cash_locked') }}');
            return;
        }

        var amount = n($('#sw_pay_amount').val());
        // IS2249: a negative Balance is settled by an Excess entry carrying
        // the same minus sign (example: -11003.13). Other payment types remain
        // positive-only so existing validation is not weakened.
        if ((swPayType === 'excess' && Math.abs(amount) < 0.0000001)
            || (swPayType !== 'excess' && amount <= 0)) {
            toastr.error('{{ __('sw::lang.enter_an_amount') }}');
            return;
        }

        var needs = swPayNeeds[swPayType] || {};
        var accountId = $('#sw_pay_account').val();
        var customerId = $('#sw_pay_customer').val();
        var expenseCategoryId = $('#sw_pay_expense_category').val();

        if (needs.expenseCategory && !expenseCategoryId) {
            toastr.error('{{ __('sw::lang.choose_an_expense_category') }}');
            return;
        }

        if (needs.account && !accountId) {
            toastr.error(
                swPayType === 'loan_payment'
                    ? '{{ __('sw::lang.choose_a_bank') }}'
                    : '{{ __('sw::lang.choose_an_account') }}'
            );
            return;
        }

        if (needs.customer && !customerId) {
            toastr.error('{{ __('sw::lang.choose_a_customer') }}');
            return;
        }

        swPayments.push({
            key: ++swPaySeq,
            source: 'manual',
            source_key: '',
            payment_method: swPayType,
            label: swPayLabels[swPayType] || swPayType,
            contact_id: needs.customer ? customerId : '',
            customer_name: needs.customer ? $('#sw_pay_customer option:selected').text() : '',
            account_id: needs.account ? accountId : '',
            account_name: needs.account ? $('#sw_pay_account option:selected').text() : '',
            expense_category_id: needs.expenseCategory ? expenseCategoryId : '',
            expense_category_name: needs.expenseCategory
                ? $('#sw_pay_expense_category option:selected').text() : '',
            amount: amount,
            // sw_collections has a generic reference column but no expense-
            // category column on older tenants. Preserve the chosen category
            // there without requiring a migration.
            reference: needs.expenseCategory && expenseCategoryId
                ? 'expense_category:' + expenseCategoryId : '',
            note: $('#sw_pay_note').val() || ''
        });

        swRenderPayments();

        // The type stays selected: several entries of one type in a row is normal.
        $('#sw_pay_amount, #sw_pay_note').val('');
    });

    $(document).on('click', '.sw-pay-remove', function () {
        var key = parseInt($(this).data('key'), 10);
        var row = null;

        $.each(swPayments, function (i, p) {
            if (p.key === key) { row = p; return false; }
        });

        // Daily Shift Payment rows are source records. Once the shift is CLOSED
        // they are immutable in Settlement. Edit/delete them on SW Payments while
        // the shift is OPEN; never let a client-only delete hide a locked row.
        if (row && row.source === 'daily') {
            toastr.error('{{ __('sw::lang.closed_shift_cash_locked') }}');
            return;
        }

        swPayments = swPayments.filter(function (p) { return p.key !== key; });
        swRenderPayments();
    });

    function swRenderActivePaymentRows() {
        var detailedCreditSale = swPayType === 'credit_sale';
        var $body = $('#sw_pay_rows');

        $('#sw_pay_table_wrap').toggle(!!swPayType && !detailedCreditSale);

        if (!swPayType || detailedCreditSale) {
            $body.empty();
            $('#sw_pay_total').text('0.00');
            return;
        }

        var rows = swPayments.filter(function (p) {
            return String(p.payment_method || '') === String(swPayType);
        });

        var subtotal = 0;
        var html = '';

        $.each(rows, function (i, p) {
            subtotal += n(p.amount);

            var actionHtml;
            if (p.source === 'daily') {
                actionHtml = '<span class="label label-default sw-pay-locked" '
                    + 'title="{{ __('sw::lang.closed_shift_cash_locked') }}">'
                    + '<i class="fa fa-lock"></i></span>';
            } else {
                actionHtml = '<button type="button" class="btn btn-danger btn-xs sw-pay-remove" '
                    + 'data-key="' + p.key + '"><i class="fa fa-times"></i></button>';
            }

            html += '<tr data-payment-type="' + esc(swPayType) + '" data-payment-source="' + esc(p.source || 'manual') + '">'
                + '<td>' + esc(p.label) + '</td>'
                + '<td>' + esc(p.customer_name) + '</td>'
                + '<td>' + esc(p.account_name) + '</td>'
                + '<td>' + esc(p.expense_category_name || '') + '</td>'
                + '<td class="text-right">' + f2(p.amount) + '</td>'
                + '<td>' + esc(p.note) + '</td>'
                + '<td class="text-center">' + actionHtml + '</td>'
                + '</tr>';
        });

        if (!rows.length) {
            html = '<tr class="sw-pay-empty"><td colspan="7" class="text-center text-muted" '
                + 'style="padding:16px">{{ __('sw::lang.no_payments_yet') }}</td></tr>';
        }

        $body.html(html);
        $('#sw_pay_total').text(f2(subtotal));
    }

    function swRenderPayments(options) {
        options = options || {};

        var $hidden = $('#sw_pay_hidden_inputs');
        var hiddenHtml = '';

        /*
         | HARD ISOLATION: the visible table is rendered separately from form
         | submission.  Every payment remains in swPayments/hidden inputs, while
         | #sw_pay_rows receives ONLY rows matching swPayType.
         */
        $.each(swPayments, function (i, p) {
            var method = String(p.payment_method || '');
            var base = 'payments[' + i + ']';

            hiddenHtml += '<input type="hidden" name="' + base + '[payment_method]" value="' + esc(method) + '">'
                + '<input type="hidden" name="' + base + '[contact_id]" value="' + esc(p.contact_id) + '">'
                + '<input type="hidden" name="' + base + '[account_id]" value="' + esc(p.account_id) + '">'
                + '<input type="hidden" name="' + base + '[expense_category_id]" value="' + esc(p.expense_category_id || '') + '">'
                + '<input type="hidden" name="' + base + '[amount]" value="' + p.amount + '">'
                + '<input type="hidden" name="' + base + '[reference]" value="' + esc(p.reference || '') + '">'
                + '<input type="hidden" name="' + base + '[note]" value="' + esc(p.note) + '">';
        });
        $hidden.html(hiddenHtml);

        swRenderActivePaymentRows();
        swRecalcPayments();

        if (options.notify !== false) {
            $(document).trigger('sw:settlement-draft-changed');
        }
    }

    // Settlement Preview must show every payment row, including the
    // authoritative locked Daily Shift rows. Draft autosave intentionally keeps
    // only manual rows, so Preview needs its own complete getter.
    window.swSettlementPreviewGetPayments = function () {
        return JSON.parse(JSON.stringify(swPayments));
    };

    window.swSettlementPreviewRemovePayment = function (key) {
        key = parseInt(key, 10);
        var row = swPayments.filter(function (payment) { return payment.key === key; })[0];
        if (!row || row.source === 'daily') {
            return false;
        }
        swPayments = swPayments.filter(function (payment) { return payment.key !== key; });
        swRenderPayments();
        return true;
    };

    window.swSettlementDraftGetPayments = function () {
        // Daily rows are reloaded authoritatively from the selected shifts.
        // Persist only rows entered manually on this settlement screen. Cash is
        // never manual at Settlement and must not survive in an older draft.
        return JSON.parse(JSON.stringify(swPayments.filter(function (row) {
            return row.source !== 'daily'
                && String(row.payment_method || '').toLowerCase() !== 'cash';
        })));
    };

    window.swSettlementDraftSetPayments = function (rows) {
        var daily = swPayments.filter(function (row) { return row.source === 'daily'; });
        var manual = Array.isArray(rows) ? JSON.parse(JSON.stringify(rows)) : [];
        manual = manual.filter(function (row) {
            return String((row && row.payment_method) || '').toLowerCase() !== 'cash';
        });

        swPaySeq = 0;
        $.each(daily.concat(manual), function (i, row) {
            row.key = parseInt(row.key, 10) || (++swPaySeq);
            swPaySeq = Math.max(swPaySeq, row.key);
        });

        swPayments = daily.concat(manual);
        swRenderPayments();
    };

    /* Total Amount, Total Paid, Balance — 8048. */
    window.swRecalcPayments = function () {
        var sold = 0;

        /*
         | IS2240: Credit Sales are a settlement PAYMENT / receivable allocation,
         | not a second sale. Meter/Other Sale/Other Income already make up the
         | day's physical sales. Adding swCreditSalesTotal here counted the same
         | sale twice (e.g. 52,731.90 + 51,675.60 = 104,407.50).
        */
        $.each(['swMeterSalesTotal', 'swOtherSalesTotal', 'swOtherIncomeTotal'],
            function (i, fn) {
                if (typeof window[fn] === 'function') { sold += n(window[fn]()); }
            });

        var paid = 0;
        $.each(swPayments, function (i, p) { paid += p.amount; });

        // Credit Sales settle part of the day's sales through Accounts
        // Receivable. They reduce Balance/Total Paid, but must never be added to
        // Total Sales itself (IS2240).
        if (typeof window.swCreditSalesTotal === 'function') {
            paid += n(window.swCreditSalesTotal());
        }

        var balance = sold - paid;

        $('#sw_total_amount').text(f2(sold));
        $('#sw_total_paid').text(f2(paid));
        $('#sw_pay_head_total').text(f2(paid));

        var $bal = $('#sw_balance');
        $bal.text(f2(balance)).removeClass('is-negative is-positive');

        if (Math.abs(balance) >= 0.005) {
            $bal.addClass(balance < 0 ? 'is-negative' : 'is-positive');
        }

        /*
         | S731/S730: Save Settlement is a balance-controlled action.
         |
         | The old create page showed the button as soon as a shift was chosen,
         | even while a non-zero balance remained. Keep the row/cancel action
         | available, but expose the actual submit button only when there is at
         | least one selected shift and the displayed settlement balance is zero.
         */
        if (typeof window.swUpdateSettlementSaveState === 'function') {
            window.swUpdateSettlementSaveState(balance);
        }
    };

    /*
     | IS2201: preload payments entered through SW Payments.
     | Only automatic daily rows are replaced when the selected shifts change.
    */
    function swApplyRecordedPayments(rows) {
        swPayments = swPayments.filter(function (p) {
            // Drop every prior source row and every stale/manual Cash row. The
            // fresh response below is the one authoritative closed-shift feed.
            return p.source !== 'daily'
                && String(p.payment_method || '').toLowerCase() !== 'cash';
        });

        $.each(rows || [], function (i, r) {
            var method = String(r.payment_method || '');
            var amount = n(r.amount);

            if (!method
                || (method === 'excess' && Math.abs(amount) < 0.0000001)
                || (method !== 'excess' && amount <= 0)) { return; }

            swPayments.push({
                key: ++swPaySeq,
                source: 'daily',
                source_key: String(r.source_key || ''),
                payment_method: method,
                label: swPayLabels[method] || method,
                contact_id: r.contact_id || '',
                customer_name: r.customer_name || '',
                account_id: r.account_id || '',
                account_name: r.account_name || '',
                expense_category_id: r.expense_category_id || '',
                expense_category_name: r.expense_category_name || '',
                amount: amount,
                reference: r.reference || '',
                note: r.note || ''
            });
        });

        swRenderPayments();
    }

    $(document).on('sw:settlement-before-submit.swPayments', function () {
        swPayments = swPayments.filter(function (row) {
            return String(row.payment_method || '').toLowerCase() !== 'cash'
                || row.source === 'daily';
        });
        swRenderPayments({ notify: false });
    });

    /* The summary bar follows the operator and shifts. */
    window.swLoadPaymentSummary = function (shiftIds, operatorId) {
        var requestSeq = ++swPaymentSummaryRequestSeq;
        if (!operatorId || !shiftIds || !shiftIds.length) {
            swApplyRecordedPayments([]);
            return $.Deferred().reject().promise();
        }

        var request = $.get('{{ route('sw.settlements.payment-summary') }}', {
            shift_ids: shiftIds || [],
            pump_operator_id: operatorId
        });

        request.done(function (d) {
            if (requestSeq !== swPaymentSummaryRequestSeq || !d) { return; }

            $('#sw_cur_short').text(f2(d.current_short));
            $('#sw_cur_excess').text(f2(d.current_excess));
            $('#sw_daily_cash').text(f2(d.daily_cash));
            $('#sw_daily_credit').text(f2(d.daily_credit_sales));
            $('#sw_commission').text(f2(d.commission));

            swApplyRecordedPayments(d.recorded_payments || []);

            swRecalcPayments();
            swKeepAllPaymentTypesOpen();
            $(document).trigger('sw:settlement-payment-summary-loaded');
        }).fail(function () {
            if (requestSeq !== swPaymentSummaryRequestSeq) { return; }
            });

        return request;
    };

    // Default to Cash as the first visible payment tab unless draft restoration
    // selects another type a moment later. This makes the payment area immediately
    // understandable and avoids an unselected table state.
    if (!swPayType) {
        var $defaultPay = $('.sw-pay-btn[data-type="cash"]').first();
        if ($defaultPay.length) { swActivatePaymentType($defaultPay); }
    }

    swRenderPayments({ notify: false });

});
</script>
