<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open([
    'url' => action('TransactionPaymentController@postPayContactDue'),
    'method' => 'post',
    'id' => 'pay_contact_due_form',
    'files' => true,
]) !!}

        {{-- Always post contacts.id; the controller also accepts legacy contact codes. --}}
        {!! Form::hidden('contact_id', $contact_details->contact_id) !!}
        {!! Form::hidden('due_payment_type', $due_payment_type) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('purchase.add_payment')</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                @if ($due_payment_type == 'purchase')
                    <div class="col-md-6">
                        <div class="well">
                            <strong>@lang('purchase.supplier'): </strong>{{ $contact_details->name }}<br>
                            <strong>@lang('business.business'):
                            </strong>{{ $contact_details->supplier_business_name }}<br><br>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="well">
                            <strong>@lang('report.total_purchase'): </strong><span class="display_currency"
                                data-currency_symbol="true">{{ $contact_details->total_purchase }}</span><br>
                            <strong>@lang('contact.total_paid'): </strong><span class="display_currency"
                                data-currency_symbol="true">{{ $contact_details->total_paid }}</span><br>
                            <strong>@lang('contact.total_purchase_due'): </strong><span class="display_currency"
                                data-currency_symbol="true">{{ $contact_details->total_purchase - $contact_details->total_paid }}</span><br>
                            @if (!empty($contact_details->opening_balance) || $contact_details->opening_balance != '0.00')
                                <strong>@lang('lang_v1.opening_balance'): </strong>
                                <span class="display_currency" data-currency_symbol="true">
                                    {{ $contact_details->opening_balance }}</span><br>
                                <strong>@lang('lang_v1.opening_balance_due'): </strong>
                                <span class="display_currency" data-currency_symbol="true">
                                    {{ $ob_due }}</span>
                            @endif
                        </div>
                    </div>
                @elseif($due_payment_type == 'purchase_return')
                    <div class="col-md-6">
                        <div class="well">
                            <strong>@lang('purchase.supplier'): </strong>{{ $contact_details->name }}<br>
                            <strong>@lang('business.business'):
                            </strong>{{ $contact_details->supplier_business_name }}<br><br>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="well">
                            <strong>@lang('lang_v1.total_purchase_return'): </strong><span class="display_currency"
                                data-currency_symbol="true">{{ $contact_details->total_purchase_return }}</span><br>
                            <strong>@lang('lang_v1.total_purchase_return_paid'): </strong><span class="display_currency"
                                data-currency_symbol="true">{{ $contact_details->total_return_paid }}</span><br>
                            <strong>@lang('lang_v1.total_purchase_return_due'): </strong><span class="display_currency"
                                data-currency_symbol="true">{{ $contact_details->total_purchase_return - $contact_details->total_return_paid }}</span>
                        </div>
                    </div>
                @elseif(in_array($due_payment_type, ['sell']))
                    <div class="col-md-6">
                        <div class="well">
                            <strong>@lang('sale.customer_name'): </strong>{{ $contact_details->name }}<br>
                            <br><br>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="well">
                            <strong>@lang('report.total_sell'): </strong><span class="display_currency"
                                data-currency_symbol="true">{{ $contact_details->total_invoice }}</span><br>
                            <strong>@lang('contact.total_paid'): </strong><span class="display_currency"
                                data-currency_symbol="true">{{ $contact_details->total_paid }}</span><br>
                            <strong>@lang('contact.total_sale_due'): </strong><span class="display_currency"
                                data-currency_symbol="true">{{ $contact_details->total_invoice - $contact_details->total_paid }}</span><br>
                            @if (!empty($contact_details->opening_balance) || $contact_details->opening_balance != '0.00')
                                <strong>@lang('lang_v1.opening_balance'): </strong>
                                <span class="display_currency" data-currency_symbol="true">
                                    {{ $contact_details->opening_balance }}</span><br>
                                <strong>@lang('lang_v1.opening_balance_due'): </strong>
                                <span class="display_currency" data-currency_symbol="true">
                                    {{ $ob_due }}</span>
                            @endif
                        </div>
                    </div>
                @elseif(in_array($due_payment_type, ['sell_return']))
                    <div class="col-md-6">
                        <div class="well">
                            <strong>@lang('sale.customer_name'): </strong>{{ $contact_details->name }}<br>
                            <br><br>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="well">
                            <strong>@lang('lang_v1.total_sell_return'): </strong><span class="display_currency"
                                data-currency_symbol="true">{{ $contact_details->total_sell_return }}</span><br>
                            <strong>@lang('lang_v1.total_sell_return_paid'): </strong><span class="display_currency"
                                data-currency_symbol="true">{{ $contact_details->total_return_paid }}</span><br>
                            <strong>@lang('lang_v1.total_sell_return_due'): </strong><span class="display_currency"
                                data-currency_symbol="true">{{ $contact_details->total_sell_return - $contact_details->total_return_paid }}</span>
                        </div>
                    </div>
                @endif
            </div>
            <div class="row payment_row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-location-arrow"></i>
                            </span>
                            {!! Form::select('location_id', $business_locations, $business_location_id, [
    'class' => 'form-control
                                          select2 location_id',
    'required',
    'style' => 'width:100%;',
    'placeholder' => __('lang_v1.please_select'),
]) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('payment_ref_no', __('lang_v1.ref_no') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-link"></i>
                            </span>
                            {!! Form::text('payment_ref_no', $payment_ref_no, [
    'class' => 'form-control
                                           payment_ref_no',
    'readonly',
    'style' => 'width:100%;',
    'placeholder' => __('lang_v1.ref_no'),
]) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('amount', __('sale.amount') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-money"></i>
                            </span>
                            {!! Form::text('amount', $amount_formated, [
    'class' => 'form-control input_number',
    'data-rule-min-value' => 0,
    'data-rule-max-value' => $amount_formated,
    'data-msg-max-value' => __('contact.greater_value_not_allowed'),
    'data-msg-min-value' => __('lang_v1.negative_value_not_allowed'),
    'required',
    'placeholder' => 'Amount',
]) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('paid_on', __('lang_v1.paid_on') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </span>
                            {!! Form::text('paid_on', @format_datetime($payment_line->paid_on), [
    'class' => 'form-control',
    'readonly',
    'required',
]) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('method', __('purchase.payment_method') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-money"></i>
                            </span>
                            {!! Form::select('method', $payment_types, $payment_line->method, [
    'class' => 'form-control select2
                                          payment_types_dropdown',
    'required',
    'style' => 'width:100%;',
    'id' => 'method',
]) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-12 row pd_cheque_boxes hide">
                    {{-- Hidden inputs so backend always receives 0 when unchecked (checkboxes don't submit when unchecked) --}}
                    <input type="hidden" name="post_dated_cheque" id="post_dated_cheque_hidden" value="0">
                    <input type="hidden" name="update_post_dated_cheque" id="update_post_dated_cheque_hidden" value="0">
                    <div class="col-md-4 text-left ">
                        @if (!empty($pdChequePermissions['post_dated_cheque']))
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('post_dated_cheque', '1', false, ['class' => 'input-icheck', 'id' => 'post_dated_cheque']) !!} {{ __('account.post_dated_cheque') }}
                                </label>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6 text-left">
                        @if (!empty($pdChequePermissions['update_post_dated_cheque']))
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('update_post_dated_cheque', '1', false, [
                                        'class' => 'input-icheck',
                                        'id' => 'update_post_dated_cheque',
                                    ]) !!} {{ __('account.update_post_dated_cheque') }}
                                </label>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('document', __('purchase.attach_document') . ':') !!}
                        {!! Form::file('document') !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('account_id', __('lang_v1.payment_account') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-money"></i>
                            </span>

                            {!! Form::select('account_id', $accounts, !empty($payment_line->account_id) ? $payment_line->account_id : '', [
    'class' => 'form-control select2 account_id',
    'id' => 'account_id',
    'style' => 'width:100%;',
]) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    {!! Form::label('shift_number', __('petro::lang.shift_number') . ':') !!}

                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-anchor"></i>
                        </span>

                        {!! Form::select(
    'shift_number',
    array_combine($dailyCashShiftNumbers, $dailyCashShiftNumbers),
    'Please select',
    ['class' => 'form-control select2 shift_number', 'id' => 'shift_number', 'style' => 'width:100%;'],
) !!}
                    </div>
                </div>


                <div class="clearfix"></div>

                @include('transaction_payment.payment_type_details')

                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('note', __('lang_v1.payment_note') . ':') !!}
                        {!! Form::textarea('note', $payment_line->note, ['class' => 'form-control', 'rows' => 3]) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary submit_btn" id="submit_btn">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
    var paymentActionFilter = @json($due_payment_type == 'purchase' ? 'is_purchase_enabled' : 'is_sale_enabled');

    function removeUnsupportedPaymentOptions($selectEl) {
        if (!$selectEl || !$selectEl.length) return;
        $selectEl.find('option[value="credit_sale"], option[value="credit sales"], option[value="credit_sale_payment"], option[value="credit_purchase"], option[value="credit purchase"], option[value="pd_cheque"], option[value="post_dated_cheque"], option[value="post dated cheque"]').remove();

        /*
         * IS2012: Cheque and Credit are not settlement methods for paying a
         * supplier's due, so they are removed here alongside the credit and
         * post-dated options this function already dropped.
         *
         * Done in the dropdown rather than in payment_types() because that helper
         * feeds every payment screen in the application - a sale, an expense and a
         * purchase all legitimately offer Cheque. Only this modal is affected.
         *
         * It runs again after a location change, because changing location
         * refetches the list from the server.
         */
        $selectEl.find('option[value="cheque"], option[value="credit"]').remove();

        // If the removed option was the one selected, fall back to the first
        // remaining method rather than leaving the field blank and unsubmittable.
        if (!$selectEl.find('option:selected').length || $selectEl.val() === null) {
            $selectEl.find('option:eq(0)').prop('selected', true);
            $selectEl.trigger('change.select2');
        }
    }

    /*
     * IS2012: initialise the Paid on picker HERE, in the modal's own markup.
     *
     * This view never initialised it. It relied on the binding in app.js, which
     * runs in the AJAX success callback and attaches the calendar with
     *     widgetParent: $payDueModal.find('.modal-content')
     * That only positions correctly when .modal-content is itself positioned,
     * which it is not until erp-global-modal-system.js has added its class - so
     * the calendar could open detached from the field, and a click on a date
     * landed on whatever was underneath instead of on the picker.
     *
     * Doing it here removes every one of those dependencies: this script is part
     * of the modal HTML, so it runs the moment the modal is inserted, whether or
     * not any module asset has been published.
     *
     * The field is readonly - the picker is the only way to set it - so the
     * chosen value is written back explicitly on dp.change.
     */
    /*
     |--------------------------------------------------------------------------
     | LA-1191: the Paid on field uses the BROWSER's own date picker.
     |--------------------------------------------------------------------------
     |
     | WHY THE APPROACH CHANGED
     |
     |   This field has now been fixed five times - IS1987, LA-1167, IS2012,
     |   LA-1183 and this - and reported broken again each time. Every one of
     |   those kept bootstrap-datetimepicker and corrected one thing about how it
     |   was being driven: where the widget was anchored, which parent it was
     |   given, whether app.js re-bound it afterwards, whether an orphaned widget
     |   was left in the DOM, whether the value was written back on change.
     |
     |   Each fix was sound and each was overtaken. The last one got the calendar
     |   into the right place and it was still dead to clicks, which says the
     |   remaining fault is in the widget's own event handling inside an
     |   ajax-loaded modal under this theme - and that is not something the
     |   calling code can reliably correct.
     |
     |   The Finance module hit exactly this on its deposit forms: six library
     |   fixes, then the calendar was handed to the browser (IS2045) and the
     |   problem ended. Same decision here, for the same reason. There is nothing
     |   to position, nothing to clip, no plugin that might load late, no second
     |   widget to go stale, and no click handling of ours to get wrong.
     |
     | WHAT IS PRESERVED
     |
     |   The field still SUBMITS in the business date format. The visible native
     |   input does not post; a hidden input carrying the original name does,
     |   written on every change. postPayContactDue() therefore receives exactly
     |   what it received before and no server code changes.
     */
    function convertPaidOnToNativeInput() {
        var $form = $('#pay_contact_due_form');

        /*
         * IS2064: this function was converting its OWN hidden partner field.
         *
         * It is called three times - inline, on a setTimeout, and again on
         * shown.bs.modal - and each call did this:
         *
         *   1. found the first input[name="paid_on"]
         *   2. inserted a HIDDEN input also carrying name="paid_on"
         *   3. removed `name` from the field it found
         *   4. marked THAT field as bound
         *
         * After call 1 the only element still named paid_on is the hidden one it
         * just created. So call 2 selected that hidden input, found it unbound,
         * and converted it into a second VISIBLE date control - and call 3 did
         * the same again. Three calls, three date boxes, which is exactly what
         * was reported.
         *
         * The bound flag was never wrong; it was simply on a different element
         * each time, so it could never prevent anything.
         *
         * Two changes fix it:
         *
         *   - a FORM-level guard, so the conversion happens once no matter how
         *     often this runs or which element is found;
         *   - the selector now ignores hidden inputs, so the partner field can
         *     never be mistaken for the visible control even if the flag is lost.
         */
        if ($form.data('nativePaidOnDone')) {
            return;
        }

        var $field = $form.find('input[name="paid_on"]').not('[type="hidden"]').first();

        if (!$field.length) {
            return;
        }

        if ($field.data('nativePaidOnBound')) {
            return;
        }

        $form.data('nativePaidOnDone', true);

        var input = $field[0];

        /*
         * Clear whatever the library left behind. app.js binds its own picker in
         * the same ajax callback, and its widget would otherwise sit over the
         * native control - the orphan that made the last attempt look fixed
         * while staying unclickable.
         */
        var existing = $field.data('DateTimePicker');

        if (existing && typeof existing.destroy === 'function') {
            try { existing.destroy(); } catch (e) {}
        }

        $field.removeData('DateTimePicker');
        $('body').children('.bootstrap-datetimepicker-widget').remove();
        $('.pay_contact_due_modal').find('.bootstrap-datetimepicker-widget').remove();

        var dateFormat = (typeof moment_date_format !== 'undefined' && moment_date_format)
            ? moment_date_format
            : 'MM/DD/YYYY';
        var timeFormat = (typeof moment_time_format !== 'undefined' && moment_time_format)
            ? moment_time_format
            : 'HH:mm';
        var fullFormat = dateFormat + ' ' + timeFormat;

        // The hidden field keeps the original name, so the request body is
        // unchanged and the server needs no adjustment.
        var $hidden = $('<input>', { type: 'hidden', name: $field.attr('name') });

        var current = $.trim(String($field.val() || ''));
        var seeded = null;

        if (current && typeof moment === 'function') {
            seeded = moment(current, fullFormat, true);

            if (!seeded.isValid()) {
                seeded = moment(current);           // lenient second attempt
            }

            if (!seeded.isValid()) {
                seeded = null;
            }
        }

        if (!seeded && typeof moment === 'function') {
            seeded = moment();                      // default to now, as before
        }

        $hidden.val(seeded ? seeded.format(fullFormat) : current);
        $field.after($hidden);

        $field
            .removeAttr('name')                     // so it cannot post
            .removeAttr('readonly')                 // the browser control needs it writable
            .attr('type', 'datetime-local')
            .val(seeded ? seeded.format('YYYY-MM-DDTHH:mm') : '');

        $field.on('change.nativePaidOn input.nativePaidOn', function () {
            var raw = $.trim(String($field.val() || ''));

            if (!raw || typeof moment !== 'function') {
                return;                             // never wipe a good stored value
            }

            var picked = moment(raw, 'YYYY-MM-DDTHH:mm', true);

            if (picked.isValid()) {
                $hidden.val(picked.format(fullFormat));
            }
        });

        // The calendar addon opens the browser picker where that is allowed.
        $('#pay_contact_due_form').find('.input-group-addon, .input-group-btn')
            .off('click.nativePaidOn')
            .on('click.nativePaidOn', function () {
                if (typeof input.showPicker === 'function') {
                    try { input.showPicker(); return; } catch (e) {}
                }

                $field.trigger('focus');
            });

        $field.data('nativePaidOnBound', true);
    }

    /*
     * Called three times on purpose.
     *
     * Once now, so the field is usable immediately. Again on the next tick,
     * because app.js binds its own picker a few lines LATER in this same ajax
     * success callback - that ordering is what defeated the previous attempt.
     * And once more when the modal is shown, for the case where it is displayed
     * later still.
     *
     * Calling it repeatedly is safe: it returns at once if the field has already
     * been converted.
     */
    convertPaidOnToNativeInput();

    window.setTimeout(convertPaidOnToNativeInput, 0);

    $('.pay_contact_due_modal')
        .off('shown.bs.modal.nativePaidOn')
        .on('shown.bs.modal.nativePaidOn', function () {
            convertPaidOnToNativeInput();
        });

    $('.payment_types_dropdown').trigger('change');
    $('#pay_contact_due_form').validate();
    $(".select2").select2();
    removeUnsupportedPaymentOptions($('#method'));
    
    // Ensure payment type details are shown correctly on page load
    $('.payment_types_dropdown').each(function() {
        $(this).trigger('change');
    });
    var supplierDueBalanceAlertShown = false;

    function checkSupplierDueInsufficientBalance() {
        @if ($due_payment_type == 'sell_return' || $contact_details->type == 'supplier')
            var amount = parseFloat(($('#amount').val() || '').replace(/,/g, ''));
            var accountId = parseInt($('.account_id').val(), 10);
            var paymentMethod = (($('.payment_types_dropdown').val() || '') + '').toLowerCase();

            if (!accountId || isNaN(amount) || amount <= 0) {
                supplierDueBalanceAlertShown = false;
                $('button#submit_btn').prop('disabled', false);
                return;
            }

            $.ajax({
                method: 'GET',
                url: '/finance/check-insufficient-balance-for-accounts',
                success: function(result) {
                    var ids = result || [];
                    var usesCashAccount = ids.includes(accountId);
                    var isCashMethod = paymentMethod === 'cash';

                    if (!usesCashAccount && !isCashMethod) {
                        supplierDueBalanceAlertShown = false;
                        $('button#submit_btn').prop('disabled', false);
                        return;
                    }

                    $.ajax({
                        method: 'GET',
                        url: '/finance/get-account-balance/' + accountId,
                        success: function(balanceResult) {
                            if (parseFloat(balanceResult.balance) < amount || balanceResult.balance == null) {
                                if (!supplierDueBalanceAlertShown) {
                                    supplierDueBalanceAlertShown = true;
                                    toastr.error('Insufficient Balance');
                                }

                                $('button#submit_btn').prop('disabled', true);
                                return false;
                            }

                            supplierDueBalanceAlertShown = false;
                            $('button#submit_btn').prop('disabled', false);
                        }
                    });
                }
            });
        @endif
    }

    $(document).on('change', '.location_id', function () {
        let location_id = $(this).val();
        $.ajax({
            method: 'get',
            url: "/payments/get-payment-method-by-location-id/" + location_id,
            data: {
                action: paymentActionFilter
            },
            contentType: 'html',
            success: function (result) {
                if (result) {
                    $('#method').empty().append(result);
                    removeUnsupportedPaymentOptions($('#method'));
                    $('#method option:eq(0)').prop('selected', 'selected');
                    $('.payment_types_dropdown').trigger('change');
                }
            },
        });
    });

    $('.location_id').trigger('change');

    $(document).on('change', '.payment_types_dropdown', function () {
        var val = ($(this).val() || '').toString().toLowerCase().trim();
        var location_id = $('.location_id').val();
        var accounting_module = $("#account_id");
        var hasPdChequeOption = @json(!empty($pdChequePermissions['post_dated_cheque']) || !empty($pdChequePermissions['update_post_dated_cheque']));
        var isBankFamilyMethod = ['bank', 'bank_transfer', 'direct_bank_deposit'].includes(val);
        var isChequeMethod = val === 'cheque' || val === 'check' || val.indexOf('cheque') !== -1 || val.indexOf('check') !== -1;
        var supportsBankDetails = isBankFamilyMethod || isChequeMethod;
        
        // Show/hide payment detail blocks
        $('.payment_details_div').addClass('hide');
        if (supportsBankDetails) {
            $('.add_payment_bank_details').removeClass('hide');
        } else if (val) {
            $('.payment_details_div[data-type="' + val + '"]').removeClass('hide');
        }
        if (isChequeMethod) {
            // Keep cheque fields visible (bank name, cheque number, cheque date)
            // and additionally show selectable cheque list when available.
            $('.add_payment_bank_details').removeClass('hide');
            $('.cheque_payment_details_only').removeClass('hide');
            if (typeof get_cheques_list === 'function') {
                get_cheques_list('cheque', $('.payment_row').first());
            }
        } else {
            $('.cheque_payment_details_only').addClass('hide');
        }
        
        // Supplier payments use Bank as well as Cheque for PD cheque entries.
        var isPdChequePaymentMethod = supportsBankDetails;
        if (hasPdChequeOption && isPdChequePaymentMethod) {
            $('.pd_cheque_boxes').removeClass('hide');
        } else {
            $('.pd_cheque_boxes').addClass('hide');
            $('#post_dated_cheque, #update_post_dated_cheque').prop('checked', false).trigger('change');
            if ($.fn.iCheck) {
                $('#post_dated_cheque, #update_post_dated_cheque').iCheck('update');
            }
        }

        // Update accounting module dropdown based on payment method
        if (val) {
            $.ajax({
                method: 'get',
                url: '/finance/get-account-group-name-dp',
                data: {
                    group_name: val,
                    location_id: location_id
                },
                contentType: 'html',
                success: function(result) {
                    accounting_module.empty().append(result);
                    accounting_module.attr('required', true);
                    accounting_module.trigger('change');
                },
            });
        } else {
            checkSupplierDueInsufficientBalance();
        }
    });

    $(document).on('change', '#update_post_dated_cheque', function () {
        console.log("update_post_dated_cheque");

        if ($(this).is(':checked')) {
            $('#post_dated_cheque').prop('checked', true).trigger('change');
            if ($.fn.iCheck) {
                $('#post_dated_cheque').iCheck('update');
            }
        }

        var payment_type = $(".payment_types_dropdown").val();
        var location_id = $('#location_id').val();
        var accounting_module = $("#account_id");
        var previous_acc_id = parseInt($('.previous_account').val());
        accounting_module.attr('required', true);
        accounting_module.empty();
        if ($(this).is(':checked')) {
            $.ajax({
                method: 'get',
                url: '/finance/get-account-group-name-dp',
                data: {
                    group_name: payment_type,
                    location_id: location_id
                },
                contentType: 'html',
                success: function (result) {
                    accounting_module.empty().append(result);
                    accounting_module.attr('required', true);
                    accounting_module.val(accounting_module.find('option:first').val());
                    if (previous_acc_id) {
                        accounting_module.val(previous_acc_id).change();
                    }
                },
            });
        } else {
            $.ajax({
                method: 'get',
                url: '/finance/get-account-group-name-dp',
                data: {
                    group_name: payment_type,
                    location_id: location_id
                },
                contentType: 'html',
                success: function (result) {
                    accounting_module.empty().append(result);
                    accounting_module.attr('required', true);
                    accounting_module.val(accounting_module.find('option:first').val());
                    if (previous_acc_id) {
                        accounting_module.val(previous_acc_id).change();
                    }
                },
            });
        }
    });

    $(document).on('change', '#amount', checkSupplierDueInsufficientBalance);

    $(document).on('change', '.account_id', function () {
        checkSupplierDueInsufficientBalance();
    });

    $(document).on('click', '#amount', function () {
        $("#amount").val("");
    });
</script>
