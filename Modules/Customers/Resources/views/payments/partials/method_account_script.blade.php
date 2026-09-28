{{--
 |==============================================================================
 | Shared payment method / account behaviour for the Customers action popups
 |==============================================================================
 |
 | S637-5. Extracted verbatim from payments/form.blade.php so the Advance
 | Payment popup can use it too.
 |
 | WHY IT HAD TO BE SHARED
 |   advance_payments/form.blade.php had no script at all. formData() returns
 |   'accountOptions' => [] on purpose - the account list is meant to be loaded
 |   after a method is chosen - so with no script the Advance Payment account
 |   dropdown only ever contained "Please Select" and could never be satisfied.
 |
 | WHAT IT DOES
 |   * repopulates .customers-payment-account from
 |     customers.payments.accounts_by_method whenever .customers-payment-method
 |     changes;
 |   * shows and requires the Cheque No / Bank / Cheque Date fields for the
 |     methods that need them;
 |   * wires the date pickers.
 |
 | The including form must provide: .customers-payment-method,
 | .customers-payment-account, .customers-bank-cheque-fields,
 | .customers-bank-field, .customers-native-date, .customers-open-datepicker.
 --}}
<script>
(function ($) {
    var accountMap = @json($paymentAccountMap ?? []);
    var accountUrl = @json(route('customers.payments.accounts_by_method'));
    var $modal = $('.customer_modal:visible').length ? $('.customer_modal:visible') : $('.customer_modal');
    var $method = $modal.find('.customers-payment-method');
    var $account = $modal.find('.customers-payment-account');

    // IS2067: responses for methods missing from the pre-built map, so a second
    // selection of the same method does not repeat the request.
    var fetchedAccounts = {};

    function openCustomerNativeDatePicker($input) {
        if (!$input || !$input.length) {
            return;
        }

        var input = $input.get(0);
        $input.trigger('focus');

        // IS1771: Native date controls are reliable inside the high-z-index
        // Customer action modal and do not depend on a Bootstrap datepicker
        // dropdown that can be hidden behind or clipped by the modal.
        if (input && typeof input.showPicker === 'function') {
            try {
                input.showPicker();
                return;
            } catch (ignore) {}
        }

        $input.trigger('click');
    }

    function cleanDuplicateDropdowns() {
        // FIX 005: This modal must show one real dropdown only.
        // Some ERP layouts initialise Select2 globally after the modal is loaded;
        // that was leaving both the Select2 box and the native select visible.
        // We keep the native select for reliable browser dropdown behaviour and
        // remove only duplicate Select2 containers inside this payment form.
        $modal.find('.customers-payment-method, .customers-payment-account').each(function () {
            var $select = $(this);
            if ($.fn.select2 && $select.data('select2')) {
                try { $select.select2('destroy'); } catch (e) {}
            }
            $select.removeClass('select2-hidden-accessible').removeAttr('data-select2-id aria-hidden tabindex');
            $select.siblings('.select2, .select2-container').remove();
            $select.css({display: 'block', visibility: 'visible', opacity: 1});
        });
    }

    function refreshSelect2($element) {
        cleanDuplicateDropdowns();
    }

    function renderAccounts(accounts) {
        $account.empty().append(new Option('Please Select', ''));

        $.each(accounts || {}, function (id, name) {
            $account.append(new Option(name, id, false, false));
        });

        // S410-2: keep the dropdown and all linked account options available,
        // but do not auto-select any account. User must select it.
        $account.val('').trigger('change');
        refreshSelect2($account);
    }

    function isBankChequeMethod(method) {
        return $.inArray(String(method || '').toLowerCase(), ['bank', 'cheque', 'bank_transfer', 'direct_bank_deposit', 'bank_deposit']) !== -1;
    }

    function toggleBankChequeFields(method) {
        var required = isBankChequeMethod(method);
        var $fields = $modal.find('.customers-bank-cheque-fields');
        var $inputs = $modal.find('.customers-bank-field');

        $fields.toggle(required);
        $inputs.prop('required', required);
        if (!required) {
            $inputs.val('');
        }
    }

    function updateAccounts(method) {
        method = method || '';
        toggleBankChequeFields(method);

        if (!method) {
            renderAccounts({});
            return;
        }

        /*
         * IS2067: fill from the pre-built map, with NO network call.
         *
         * The map now arrives with the modal (see paymentAccountMap in
         * CustomerPaymentActionService), so the accounts for the chosen method
         * are already in memory. Rendering straight from it is instant and works
         * with no connection at all.
         *
         * Two things were removed from this path:
         *
         *   - the renderAccounts({}) that ran FIRST and blanked the dropdown.
         *     Even when the data was available locally, the list emptied and then
         *     refilled - a visible flicker, and on a slow link it stayed empty
         *     until the request came back.
         *
         *   - the ajax call that fired EVERY time, even when the map already had
         *     the answer, and re-rendered the same list a second time.
         */
        if (accountMap && accountMap[method]) {
            renderAccounts(accountMap[method]);
            return;
        }

        // Anything the map does not cover - a method added after this page was
        // rendered - still resolves, just not instantly.
        if (fetchedAccounts[method]) {
            renderAccounts(fetchedAccounts[method]);
            return;
        }

        renderAccounts({});

        $.ajax({
            url: accountUrl,
            data: {method: method},
            type: 'GET',
            dataType: 'json',
            cache: false
        }).done(function (response) {
            if (response && response.success) {
                // Remembered, so re-selecting this method is instant too.
                fetchedAccounts[method] = response.accounts || {};
                renderAccounts(fetchedAccounts[method]);
            }
        });
    }

    cleanDuplicateDropdowns();

    $modal.off('click.customersOpenDatepicker keydown.customersOpenDatepicker', '.customers-open-datepicker')
        .on('click.customersOpenDatepicker keydown.customersOpenDatepicker', '.customers-open-datepicker', function (event) {
            if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') {
                return;
            }
            event.preventDefault();
            var $input = $(this).siblings('input[type="date"]').first();
            openCustomerNativeDatePicker($input);
        });

    $method.off('change.customersPaymentAccount').on('change.customersPaymentAccount', function () {
        updateAccounts($(this).val());
    });

    updateAccounts($method.val() || '');
    setTimeout(cleanDuplicateDropdowns, 100);
    setTimeout(cleanDuplicateDropdowns, 500);


    /*
     |--------------------------------------------------------------------------
     | 8049: Tab moves to the next FIELD
     |--------------------------------------------------------------------------
     |
     | Tab was not landing where the user expected. Two things were in the way:
     |
     |   * the calendar buttons beside the two date inputs carried tabindex="0",
     |     so tabbing out of a field stopped on an icon before reaching the next
     |     input. They are now tabindex="-1" - still clickable, no longer a stop.
     |
     |   * cleanDuplicateDropdowns() strips the tabindex Select2 puts on the two
     |     native selects, but only at load, +100ms and +500ms. A Select2 pass
     |     that runs later re-applies tabindex="-1" and Tab silently skips both
     |     dropdowns again.
     |
     | Rather than keep chasing whatever re-applies it, focus is moved
     | explicitly. The order is the DOM order of the form's own visible, enabled
     | controls, which is the order they are read on screen.
     |
     | TRADE-OFF, deliberate: on a native date input the browser normally uses
     | Tab to step through day/month/year. That is overridden here, so Tab
     | leaves the whole field. It is what the ticket asks for - "jump to the next
     | field" - and the segments are still reachable with the arrow keys, or by
     | typing the digits, which advances automatically.
     */
    function customersTabbableFields($form) {
        return $form.find('input, select, textarea, button')
            .filter(':visible')
            .filter(function () {
                var $field = $(this);
                if ($field.is(':disabled') || $field.attr('type') === 'hidden') {
                    return false;
                }
                // The calendar buttons are mouse affordances, not fields.
                return !$field.hasClass('customers-open-datepicker');
            });
    }

    $modal.find('form').off('keydown.customersTabOrder').on('keydown.customersTabOrder', function (event) {
        if (event.key !== 'Tab' && event.keyCode !== 9) {
            return;
        }

        var $fields = customersTabbableFields($(this));
        var currentIndex = $fields.index(event.target);

        if (currentIndex === -1) {
            return;
        }

        var nextIndex = event.shiftKey ? currentIndex - 1 : currentIndex + 1;

        // At either end, hand back to the browser so focus can leave the form
        // normally instead of being trapped.
        if (nextIndex < 0 || nextIndex >= $fields.length) {
            return;
        }

        event.preventDefault();
        $fields.eq(nextIndex).trigger('focus');
    });

    /*
     |--------------------------------------------------------------------------
     | 8049: typing in Amount replaces the value, it does not append
     |--------------------------------------------------------------------------
     |
     | Amount arrives pre-filled with the full due, and selecting it on focus is
     | the wanted behaviour. The reported problem was what happened next: typing
     | appended to the existing figure instead of replacing it, so 5,000.00 plus
     | a typed 200 read 5,000.00200.
     |
     | The selection was being lost before the keystroke arrived. A click places
     | the caret after focus has selected, and the numeric formatting applied to
     | .input_number re-sets the value and puts the caret at the end.
     |
     | So the outcome is not left to the selection surviving. The field is
     | flagged "pristine" while it still holds the value it was given, and the
     | first character typed clears it. Whatever moved the caret, the first
     | keystroke starts a fresh number.
     |
     | Once the user has typed anything the flag is off, so editing what they
     | just entered - backspace, extra digits, caret placement - behaves
     | normally. Tab, Enter and Escape leave the value untouched, so tabbing
     | straight through keeps the full due, which is the common case.
     */
    var $amountField = $modal.find('.customers-amount-input, input[name="amount"]').first();
    var amountIsPristine = false;

    if ($amountField.length) {
        $amountField.off('.customersAmountReplace');

        $amountField.on('focus.customersAmountReplace', function () {
            amountIsPristine = true;
            var field = this;
            // Deferred: some numeric formatters re-write the value on focus,
            // which would drop a selection made in the same tick.
            window.setTimeout(function () {
                try { field.select(); } catch (ignore) {}
            }, 0);
        });

        // Keep the selection visible when focus came from a click.
        $amountField.on('mouseup.customersAmountReplace', function (event) {
            if (amountIsPristine) {
                event.preventDefault();
            }
        });

        $amountField.on('keydown.customersAmountReplace', function (event) {
            if (!amountIsPristine) {
                return;
            }

            // Let shortcuts such as Ctrl+A / Ctrl+C through untouched.
            if (event.ctrlKey || event.metaKey || event.altKey) {
                return;
            }

            var key = event.key;

            // Navigating away, or moving the caret on purpose, is not editing.
            if (key === 'Tab' || key === 'Enter' || key === 'Escape' ||
                key === 'ArrowLeft' || key === 'ArrowRight' ||
                key === 'ArrowUp' || key === 'ArrowDown' ||
                key === 'Home' || key === 'End' || key === 'Shift') {
                if (key !== 'Shift') {
                    amountIsPristine = false;
                }
                return;
            }

            // A single printable character, or a delete, starts the new value.
            if ((key && key.length === 1) || key === 'Backspace' || key === 'Delete') {
                amountIsPristine = false;
                $(this).val('');
            }
        });

        $amountField.on('blur.customersAmountReplace', function () {
            amountIsPristine = false;
        });
    }

    $modal.find('form').off('submit.customersPaymentRequired').on('submit.customersPaymentRequired', function (e) {
        var missing = [];
        if (!$method.val()) { missing.push('Payment Method'); }
        if (!$account.val()) { missing.push('Payment Account'); }
        if (isBankChequeMethod($method.val())) {
            if (!$.trim($modal.find('[name="cheque_number"]').val())) { missing.push('Cheque No'); }
            if (!$.trim($modal.find('[name="bank_name"]').val())) { missing.push('Bank'); }
            if (!$.trim($modal.find('[name="cheque_date"]').val())) { missing.push('Cheque Date'); }
        }
        if (!$.trim($modal.find('[name="amount"]').val())) { missing.push('Amount'); }
        if (!$.trim($modal.find('[name="payment_date"]').val())) { missing.push('Transaction Date'); }

        if (missing.length) {
            e.preventDefault();
            alert('Please select / enter: ' + missing.join(', '));
            return false;
        }
    });
})(jQuery);
</script>
