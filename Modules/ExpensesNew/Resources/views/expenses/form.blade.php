@extends('expensesnew::layouts.app', ['heading'=>$expense->id ? 'Edit Expense' : 'Add Expense'])
@section('module_content')
@php
    $selectedCategoryId = (string) old('category_id', $expense->category_id);
    $selectedLinkedAccount = $categoryAccountMap[$selectedCategoryId] ?? null;
    $firstLocationId = (string) ($locations->keys()->first() ?? '');
    $selectedLocationId = (string) old('location_id', $expense->location_id ?: $firstLocationId);
    $locationPaymentMethods = $paymentMethodsByLocation[$selectedLocationId] ?? [];
    $defaultPaymentMethod = array_key_first($locationPaymentMethods) ?: '';
    $selectedPaymentMethod = (string) old('payment_method', $expense->payment_method ?: $defaultPaymentMethod);

    // IS2341: Add AND Edit must show only methods currently Active + enabled
    // for Expenses. A historical method that has since been disabled must not
    // be re-injected into the selectable dropdown.
    if ($selectedPaymentMethod === '' || !array_key_exists($selectedPaymentMethod, $locationPaymentMethods)) {
        $selectedPaymentMethod = $defaultPaymentMethod;
    }

    $locationAccountingMap = $paymentAccountingModuleMapByLocation[$selectedLocationId] ?? [];
    $linkedAccountingModule = $locationAccountingMap[$selectedPaymentMethod] ?? null;
    $selectedFinanceAccountId = (string) old('bank_account_id', $expense->bank_account_id);
    $selectedPaymentAccounts = $paymentAccountOptionsByLocation[$selectedLocationId][$selectedPaymentMethod] ?? [];
    $selectedPayeeId = (string) old(
        'payee_id',
        $expense->id ? $expense->payee_id : ($defaultPayeeId ?? null)
    );
@endphp
<form method="post"
      enctype="multipart/form-data"
      action="{{ $expense->id ? route('expensesnew.expenses.update', $expense->id) : route('expensesnew.expenses.store') }}">
    @csrf
    @if($expense->id) @method('PUT') @endif

    <div class="expnew-card">
        <div class="row">
            <div class="col-md-3">
                <label>Date</label>
                <input type="date"
                       name="expense_date"
                       class="form-control"
                       value="{{ old('expense_date', optional($expense->expense_date)->format('Y-m-d') ?: date('Y-m-d')) }}"
                       required>
            </div>

            <div class="col-md-3">
                <label>Location</label>
                <select id="expnew_location_id" name="location_id" class="form-control expnew-select2" required>
                    <option value="">Please select</option>
                    @foreach($locations as $id => $name)
                        <option value="{{ $id }}" @selected($selectedLocationId === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label>Expense Category</label>
                <select id="expnew_category_id" name="category_id" class="form-control expnew-select2" required>
                    <option value="">Please select</option>
                    @foreach($categories as $id => $name)
                        <option value="{{ $id }}" @selected($selectedCategoryId === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{--
                MA-002 (S-621 #2): the three category-driven fields.

                Each is hidden until the chosen category has the matching
                checkbox ticked, and its options are loaded at the same moment
                from one request - see categories/{id}/requirements.

                They are rendered here rather than injected by script so that a
                saved value survives a validation failure: old() repopulates
                them exactly like any other field.
            --}}
            <div class="col-md-4 expnew-cat-field" id="expnew_vat_category_wrap" style="display:none;">
                <label for="expnew_vat_category_id">VAT Category</label>
                <select id="expnew_vat_category_id" name="vat_category_id" class="form-control">
                    <option value="">Select</option>
                </select>
            </div>

            <div class="col-md-4 expnew-cat-field" id="expnew_sub_category_wrap" style="display:none;">
                <label for="expnew_sub_category_id">Sub Category</label>
                <select id="expnew_sub_category_id" name="sub_category_id" class="form-control">
                    <option value="">Select</option>
                </select>
            </div>

            <div class="col-md-4 expnew-cat-field" id="expnew_employee_wrap" style="display:none;">
                <label for="expnew_employee_id">Employee</label>
                <select id="expnew_employee_id" name="employee_id" class="form-control">
                    <option value="">Select</option>
                </select>
            </div>


            <div class="col-md-3">
                <label>Payee</label>
                <select id="expnew_payee_id" name="payee_id" class="form-control expnew-select2">
                    <option value="">Please select</option>
                    @foreach($payees as $id => $name)
                        <option value="{{ $id }}" @selected($selectedPayeeId === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label>Expense Account</label>
                <select id="expnew_expense_account_id"
                        name="expense_account_id"
                        class="form-control expnew-select2"
                        required
                        @disabled(!$selectedLinkedAccount)>
                    @if($selectedLinkedAccount)
                        <option value="{{ $selectedLinkedAccount['id'] }}" selected>{{ $selectedLinkedAccount['name'] }}</option>
                    @else
                        <option value="" selected>Select an expense category first</option>
                    @endif
                </select>
                <small id="expnew_account_link_message"
                       class="expnew-linked-account-note expnew-helper-text {{ $selectedCategoryId && !$selectedLinkedAccount ? 'text-danger' : '' }}">
                    @if($selectedLinkedAccount)
                        Only the expense account linked to this category is available.
                    @elseif($selectedCategoryId)
                        Link an expense account in Categories before saving this expense.
                    @else
                        The expense account is controlled by the selected category.
                    @endif
                </small>
            </div>

            <div class="col-md-3">
                <label>Paid Amount</label>
                <input name="paid_amount" id="expnew_paid_amount" class="form-control expnew-money" value="{{ old('paid_amount', $expense->paid_amount ?? $expense->total_amount ?? 0) }}" required>
            </div>

            <div class="col-md-3">
                <label>Payment Method</label>
                <select id="expnew_payment_method" name="payment_method" class="form-control expnew-select2" required>
                    @forelse($locationPaymentMethods as $methodKey => $methodLabel)
                        <option value="{{ $methodKey }}" @selected($selectedPaymentMethod === (string) $methodKey)>{{ $methodLabel }}</option>
                    @empty
                        <option value="">No Expense payment method is assigned</option>
                    @endforelse
                </select>
            </div>

            <div class="col-md-3 expnew-payment-detail" id="expnew_reference_no_wrap">
                <label>Reference No</label>
                <input name="reference_no" class="form-control" value="{{ old('reference_no', $expense->reference_no) }}">
            </div>

            <div class="col-md-3 expnew-payment-detail" id="expnew_cheque_no_wrap">
                <label>Cheque No</label>
                <input name="cheque_no" class="form-control" value="{{ old('cheque_no', $expense->cheque_no) }}">
            </div>

            <div class="col-md-3 expnew-payment-detail" id="expnew_card_no_wrap">
                <label>Card No</label>
                <input name="card_no" class="form-control" value="{{ old('card_no', $expense->card_no) }}">
            </div>

            <div class="col-md-3">
                <label>Attachments</label>
                <input type="file" name="attachments[]" class="form-control" multiple>
            </div>

            <div class="col-md-3">
                <label>Accounting Module</label>
                <select id="expnew_accounting_account_id"
                        name="bank_account_id"
                        class="form-control expnew-select2"
                        required
                        @disabled(empty($selectedPaymentAccounts))>
                    <option value="">Please select</option>
                    @foreach($selectedPaymentAccounts as $accountOption)
                        <option value="{{ $accountOption['id'] }}"
                                @selected($selectedFinanceAccountId === (string) $accountOption['id'])>
                            {{ $accountOption['name'] }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden"
                       id="expnew_accounting_module"
                       name="accounting_module"
                       value="{{ $linkedAccountingModule }}">
                <small id="expnew_accounting_account_message"
                       class="{{ empty($selectedPaymentAccounts) ? 'text-danger' : '' }} expnew-linked-account-note expnew-helper-text">
                    @if(empty($selectedPaymentAccounts))
                        No related account is configured for the selected payment method.
                    @else
                        Select one of the related accounts for this payment method.
                    @endif
                </small>
            </div>

            {{-- The SW shift this expense belongs to.

                 @includeIf, not @include: a site without SW installed gets
                 nothing at all rather than an error, which is the enable /
                 disable behaviour asked for in S-697.

                 It binds to the location field, so the shifts offered are the
                 ones open at whichever location is chosen. --}}
            @php
                $expnewSelectedSwShift = old('sw_shift_no');
                if (! $expnewSelectedSwShift && $expense->exists && $expense->relationLoaded('payments')) {
                    $expnewSelectedSwShift = optional($expense->payments->first())->sw_shift_no;
                }
            @endphp
            @includeIf('sw::partials.shift_field', [
                'bindLocation' => 'select[name="location_id"]',
                'selected' => $expnewSelectedSwShift,
            ])

            <div class="col-md-3">
                {{-- MA-002 (Issue 3): Applicable Tax - None or VAT. --}}
                <label>Applicable Tax</label>
                <select name="applicable_tax" class="form-control expnew-select2">
                    <option value="none"
                        @selected(old('applicable_tax', $expense->applicable_tax ?? 'none') === 'none')>None</option>
                    <option value="vat"
                        @selected(old('applicable_tax', $expense->applicable_tax ?? 'none') === 'vat')>VAT</option>
                </select>
            </div>

            <div class="col-md-3">
                {{-- MA-002 (Issue 3): VAT Invoice - Yes or No.
                     The hidden field guarantees a value is posted when the
                     box is unchecked, so clearing it on edit is saved. --}}
                <label>VAT Invoice</label>
                <div class="checkbox">
                    <input type="hidden" name="vat_invoice" value="0">
                    <label>
                        <input type="checkbox"
                               name="vat_invoice"
                               value="1"
                               @checked((bool) old('vat_invoice', $expense->vat_invoice ?? 0))>
                        Yes
                    </label>
                </div>
            </div>

            <div class="col-md-12">
                <label>Notes</label>
                <textarea name="notes" class="form-control">{{ old('notes', $expense->notes) }}</textarea>
            </div>
        </div>
    </div>

    @if($expense->id && $expense->attachments && $expense->attachments->count())
        <div class="expnew-card">
            <h4>Attachments</h4>
            <table class="table table-bordered">
                <tbody>
                @foreach($expense->attachments as $attachment)
                    <tr>
                        <td>{{ $attachment->file_name }}</td>
                        <td>{{ number_format($attachment->file_size / 1024, 2) }} KB</td>
                        <td class="text-right">
                            <a class="btn btn-xs btn-info" target="_blank" href="{{ asset('storage/' . $attachment->file_path) }}">View</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="expnew-card expnew-footer-actions">
        <button class="btn btn-primary expnew-save-btn">Save Expense</button>
        <a href="{{ route('expensesnew.expenses.index') }}" class="btn btn-default">Cancel</a>
    </div>
</form>

<style>
    /* Keep every Expense Category reachable in one dropdown with a visible vertical scrollbar. */
    #select2-expnew_category_id-results {
        max-height: 320px !important;
        overflow-y: auto !important;
    }

    /* JS reveals only the fields relevant to the selected payment method. */
    .expnew-payment-detail { display: none; }
</style>

<script>
(function () {
    function initialise(attempt) {
        if (!window.jQuery) {
            if (attempt < 40) {
                window.setTimeout(function () { initialise(attempt + 1); }, 50);
            }
            return;
        }

        jQuery(function ($) {
        var categoryAccountMap = @json($categoryAccountMap);
        var categoryDefaultPayeeMap = @json($categoryDefaultPayeeMap);
        var defaultExpensePayeeId = @json($defaultPayeeId ?? null);
        var paymentMethodsByLocation = @json($paymentMethodsByLocation);
        var paymentAccountingModuleMapByLocation = @json($paymentAccountingModuleMapByLocation);
        var paymentAccountOptionsByLocation = @json($paymentAccountOptionsByLocation);
        var existingPaymentAccount = @json($existingPaymentAccount ?? null);
        var existingAccountingModule = @json($linkedAccountingModule ?? null);
        var initialFinanceAccountId = @json($selectedFinanceAccountId);
        var $category = $('#expnew_category_id');

        /*
         * MA-002 (S-621 #2): show and populate the category-driven fields.
         *
         * One request per category change returns all three flags and their
         * options together, rather than three separate calls.
         *
         * The value selected before a validation failure is preserved: the
         * server re-renders these selects with old(), and the code below
         * restores that choice once the options arrive.
         */
        /*
         * MA-002: built from the NAMED route with a placeholder id, then
         * substituted. Hand-writing the path would break if the module prefix
         * ever changes - and the prefix is applied outside this route file, so
         * it is not something this view can see.
         */
        var expnewCatRequirementsUrl = "{{ route('expensesnew.categories.requirements', ['id' => '__ID__']) }}";
        var expnewPreselected = {
            vat_category_id: @json(old('vat_category_id', $expense->vat_category_id ?? '')),
            sub_category_id: @json(old('sub_category_id', $expense->sub_category_id ?? '')),
            employee_id: @json(old('employee_id', $expense->employee_id ?? ''))
        };

        function expnewFillSelect(id, options, selected) {
            var $select = $('#' + id);
            if (!$select.length) { return; }

            $select.empty().append($('<option/>').val('').text('Select'));

            $.each(options || {}, function (value, label) {
                $select.append($('<option/>').val(value).text(label));
            });

            if (selected) { $select.val(String(selected)); }
        }

        function expnewApplyCategoryRequirements() {
            var categoryId = $category.val();

            if (!categoryId) {
                $('.expnew-cat-field').hide();
                return;
            }

            $.ajax({
                url: expnewCatRequirementsUrl.replace('__ID__', categoryId),
                method: 'GET',
                dataType: 'json'
            }).done(function (result) {
                if (!result || !result.success) { return; }

                $('#expnew_vat_category_wrap').toggle(!!result.vat_input_claimed);
                $('#expnew_sub_category_wrap').toggle(!!result.is_sub_category);
                $('#expnew_employee_wrap').toggle(!!result.is_employee);

                if (result.vat_input_claimed) {
                    expnewFillSelect('expnew_vat_category_id', result.vat_categories, expnewPreselected.vat_category_id);
                }
                if (result.is_sub_category) {
                    expnewFillSelect('expnew_sub_category_id', result.sub_categories, expnewPreselected.sub_category_id);
                }
                if (result.is_employee) {
                    expnewFillSelect('expnew_employee_id', result.employees, expnewPreselected.employee_id);
                }
            }).fail(function () {
                // A failed lookup must not block the expense being saved -
                // the fields simply stay hidden.
                $('.expnew-cat-field').hide();
            });
        }

        $category.on('change', expnewApplyCategoryRequirements);

        // Run once on load so an EDIT form arrives with its fields already
        // visible and filled.
        expnewApplyCategoryRequirements();
        var $account = $('#expnew_expense_account_id');
        var $payee = $('#expnew_payee_id');
        var $location = $('#expnew_location_id');
        var $paymentMethod = $('#expnew_payment_method');
        var $accountingAccount = $('#expnew_accounting_account_id');
        var $accountingModule = $('#expnew_accounting_module');
        var $accountingAccountMessage = $('#expnew_accounting_account_message');
        var $accountMessage = $('#expnew_account_link_message');
        var $referenceWrap = $('#expnew_reference_no_wrap');
        var $chequeWrap = $('#expnew_cheque_no_wrap');
        var $cardWrap = $('#expnew_card_no_wrap');

        function initialiseSelect2($elements) {
            if (!$.fn.select2) {
                return;
            }

            $elements.each(function () {
                var $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                $select.select2({
                    width: '100%',
                    allowClear: !$select.prop('required'),
                    placeholder: 'Please select',
                    minimumResultsForSearch: 0
                });
            });
        }

        function applyCategoryLinks(changePayee) {
            var categoryId = String($category.val() || '');
            var linkedAccount = categoryAccountMap[categoryId] || null;

            if ($account.hasClass('select2-hidden-accessible')) {
                $account.select2('destroy');
            }
            $account.empty();

            if (linkedAccount && linkedAccount.id) {
                $account.append(new Option(linkedAccount.name, linkedAccount.id, true, true));
                $account.prop('disabled', false);
                $accountMessage
                    .removeClass('text-danger text-muted')
                    .text('Only the expense account linked to this category is available.');
            } else {
                var message = categoryId
                    ? 'No expense account linked to this category'
                    : 'Select an expense category first';
                $account.append(new Option(message, '', true, true));
                $account.prop('disabled', true);
                $accountMessage
                    .removeClass('text-muted text-danger')
                    .addClass(categoryId ? 'text-danger' : '')
                    .text(categoryId
                        ? 'Link an expense account in Categories before saving this expense.'
                        : 'The expense account is controlled by the selected category.');
            }

            initialiseSelect2($account);
            $account.trigger('change');

            if (changePayee) {
                var defaultPayeeId = categoryDefaultPayeeMap[categoryId] || defaultExpensePayeeId || '';
                $payee.val(defaultPayeeId).trigger('change');
            }
        }

        function currentAccountingMap() {
            return paymentAccountingModuleMapByLocation[String($location.val() || '')] || {};
        }

        function currentAccountOptions() {
            return paymentAccountOptionsByLocation[String($location.val() || '')] || {};
        }

        function applyPaymentDetailVisibility(clearHiddenValues) {
            var method = String($paymentMethod.val() || '');
            var moduleValue = currentAccountingMap()[method] || '';
            var showReference = moduleValue === 'card' || moduleValue === 'cheque' || moduleValue === 'bank';
            var showCheque = moduleValue === 'cheque';
            var showCard = moduleValue === 'card';

            $referenceWrap.toggle(showReference);
            $chequeWrap.toggle(showCheque);
            $cardWrap.toggle(showCard);

            if (clearHiddenValues) {
                if (!showReference) { $referenceWrap.find('input').val(''); }
                if (!showCheque) { $chequeWrap.find('input').val(''); }
                if (!showCard) { $cardWrap.find('input').val(''); }
            }
        }

        function applyAccountingAccounts(preserveSelection) {
            var paymentMethod = String($paymentMethod.val() || '');
            var moduleValue = currentAccountingMap()[paymentMethod] || (preserveSelection ? (existingAccountingModule || '') : '');
            var options = currentAccountOptions()[paymentMethod] || [];
            var selectedValue = preserveSelection
                ? String($accountingAccount.val() || initialFinanceAccountId || '')
                : '';

            if (!options.length && preserveSelection && existingPaymentAccount
                && String(existingPaymentAccount.id || '') === selectedValue) {
                options = [existingPaymentAccount];
            }

            $accountingModule.val(moduleValue);

            if ($accountingAccount.hasClass('select2-hidden-accessible')) {
                $accountingAccount.select2('destroy');
            }
            $accountingAccount.empty();
            $accountingAccount.append(new Option('Please select', '', false, false));

            options.forEach(function (option) {
                var value = String(option.id || '');
                var selected = value !== '' && value === selectedValue;
                $accountingAccount.append(new Option(option.name, value, selected, selected));
            });

            if (!selectedValue && options.length === 1) {
                $accountingAccount.val(String(options[0].id));
            }

            var hasOptions = options.length > 0;
            $accountingAccount.prop('disabled', !hasOptions);
            $accountingAccountMessage
                .toggleClass('text-danger', !hasOptions)
                .toggleClass('text-muted', false)
                .text(hasOptions
                    ? 'Select one of the related accounts for this payment method.'
                    : 'No related account is configured for the selected payment method.');

            initialiseSelect2($accountingAccount);
            $accountingAccount.trigger('change');
        }

        function refreshPaymentMethodsForLocation(preserveCurrent) {
            var locationId = String($location.val() || '');
            var methods = paymentMethodsByLocation[locationId] || {};
            var current = preserveCurrent ? String($paymentMethod.val() || '') : '';

            if ($paymentMethod.hasClass('select2-hidden-accessible')) {
                $paymentMethod.select2('destroy');
            }
            $paymentMethod.empty();

            $.each(methods, function (value, label) {
                $paymentMethod.append(new Option(label, value, false, value === current));
            });

            if (!current || !Object.prototype.hasOwnProperty.call(methods, current)) {
                var first = Object.keys(methods)[0] || '';
                $paymentMethod.val(first);
            }

            if (!Object.keys(methods).length) {
                $paymentMethod.append(new Option('No Expense payment method is assigned', '', true, true));
            }

            initialiseSelect2($paymentMethod);
            applyPaymentDetailVisibility(false);
            applyAccountingAccounts(false);
        }

        initialiseSelect2($('.expnew-select2'));

        $location.on('change.expnewPaymentOptions', function () {
            initialFinanceAccountId = '';
            refreshPaymentMethodsForLocation(false);
        });

        $category.on('change.expnewCategoryAccount', function () {
            applyCategoryLinks(true);
        });
        $paymentMethod.on('change.expnewAccountingModule', function () {
            applyPaymentDetailVisibility(true);
            applyAccountingAccounts(false);
        });

        applyCategoryLinks(false);
        applyPaymentDetailVisibility(false);
        applyAccountingAccounts(true);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initialise(0); });
    } else {
        initialise(0);
    }
})();

</script>
@endsection
