<div class="modal-dialog" role="document">
    <div class="modal-content">
        @php
            if ($selected_cheque) {
                $controller_action = action('TransactionPaymentController@updateChequeTransaction', [
                    $account_transaction_id,
                ]);
                $method = 'put';
            } else {
                $method = 'post';
                $controller_action = action('TransactionPaymentController@postRefundPayment', $contact_id);
            }
        @endphp

        {!! Form::open([
    'url' => $controller_action,
    'method' => $method,
    'id' => 'pay_contact_due_form',
    'files' => true,
]) !!}


        {!! Form::hidden('contact_id', $contact_details->contact_id) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('lang_v1.refund_cheque_return')</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="well">
                        @if ($contact_details->type == 'customer')
                            <strong>@lang('lang_v1.customer'):
                        @else
                                <strong>@lang('lang_v1.supplier'):
                            @endif
                            </strong>{{ $contact_details->name }}<br>
                    </div>
                </div>
                <input type="hidden" name="type" value="advance_payment">
            </div>
            <div class="row payment_row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('amount', __('lang_v1.type') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-money"></i>
                            </span>
                            {!! Form::select(
    'type',
    ['refund' => __('lang_v1.refund'), 'refund_excess_payment' => __('lang_v1.refund_excess_payment'), 'cheque_return' => __('lang_v1.cheque_return')],
    $selected_cheque && $selected_cheque->type ? $selected_cheque->type : null,
    ['class' => 'form-control input_number', 'required', 'placeholder' => __('lang_v1.please_select'), 'id' => 'type'],
) !!}
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
                            {!! Form::text('amount', $selected_cheque && $selected_cheque->amount ? $selected_cheque->amount : null, [
    'class' => 'form-control input_number',
    'data-rule-min-value' => 0,
    'data-msg-min-value' => __('lang_v1.negative_value_not_allowed'),
    'required',
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
                            {!! Form::text('paid_on', $selected_cheque && $selected_cheque->paid_on ? $selected_cheque->paid_on : null, [
    'class' => 'form-control paid_on_date',
    'placeholder' => __('lang_v1.paid_on'),
    'id' => 'paid_on_date',
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
                            {!! Form::select(
    'method',
    $payment_types,
    $selected_cheque && $selected_cheque->method ? $selected_cheque->method : null,
    [
        'class' => 'form-control select2
                                                                          payment_types_dropdown_refund',
        'required',
        'style' => 'width:100%;',
        'id' => 'method',
    ],
) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-4 cheque_return_charges_div hide">
                    <div class="form-group">
                        {!! Form::label('cheque_bank', __('lang_v1.bank_account')) !!}
                        @php
                            $attributes = [
                                'class' => 'form-control select2 cheque_bank',
                            ];

                            if (empty($selected_cheque) || empty($selected_cheque->cheque_bank)) {
                                $attributes['placeholder'] = __('lang_v1.please_select');
                                $attributes['required'] = true;
                            }
                        @endphp

                        {!! Form::select('cheque_bank', $bank_accounts_dropdown, $selected_cheque->cheque_bank ?? null, $attributes) !!}

                        <input type="hidden" name="cheque_bank_s" value="{{ $selected_cheque->cheque_bank ?? null }}">
                    </div>

                </div>
                <div class="col-md-4 cheque_return_charges_div hide">
                    <div class="form-group">
                        {!! Form::label('cheque_number_return', __('lang_v1.cheque_number')) !!}
                        {!! Form::select(
    'cheque_number_return',
    $cheque_array,
    $selected_cheque && $selected_cheque->cheque_number ? $selected_cheque->cheque_number : null,
    [
        'class' => 'form-control select2
                                                            cheque_number_return',
        'required' => $selected_cheque == null,
        'placeholder' => __('lang_v1.please_select'),
    ],
) !!}
                    </div>
                    <input type="hidden" name="cheque_number_s" value="{{ $selected_cheque->cheque_number ?? null }}">
                </div>
                <div class="col-md-6 sale_invoice_bill_number_div hide">
                    <div class="form-group">
                        {!! Form::label('sale_invoice_bill_number', __('lang_v1.sale_invoice_bill_number')) !!}
                        {!! Form::select('sale_invoice_bill_number', $invoices, null, [
    'class' => 'form-control select2
                                                            sale_invoice_bill_number',
    'required',
    'placeholder' => __('lang_v1.please_select'),
]) !!}
                    </div>
                </div>


                @php

                    $business_id = request()->session()->get('user.business_id');

                    $pacakge_details = [];

                    $subscription = Modules\Superadmin\Entities\Subscription::active_subscription($business_id);
                    if (!empty($subscription)) {
                        $pacakge_details = $subscription->package_details;
                    }

                @endphp

                @if (!empty($pacakge_details['show_post_dated_cheque']))
                    <div class="col-md-6 text-center sale_invoice_bill_number_div hide">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('post_dated_cheque', '1', false, ['class' => 'input-icheck', 'id' => 'post_dated_cheque']) !!}
                                {{ __('account.post_dated_cheque') }}
                            </label>
                        </div>
                    </div>
                @endif

                <div class="clearfix"></div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('document', __('purchase.attach_document') . ':') !!}
                        {!! Form::file('document') !!}
                    </div>
                </div>

                <div class="col-md-6 account_id_div hide @if (empty($accounts)) hide @endif">
                    <div class="form-group">
                        {!! Form::label('account_id', __('lang_v1.payment_account') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-money"></i>
                            </span>
                            @php
                                $account_options = $accounts;
                                // Add bank accounts from Accounting Module for better selection
                                if (!empty($bank_accounts_dropdown)) {
                                    $account_options = $account_options->merge($bank_accounts_dropdown);
                                }
                            @endphp
                            {!! Form::select('account_id', $account_options, $customer_deposit_account_id, [
    'class' => 'form-control select2',
    'placeholder' => __('lang_v1.please_select'),
    'id' => 'account_id',
    'style' => 'width:100%;',
]) !!}
                        </div>
                        <small class="help-block">@lang('lang_v1.select_payment_account_from_accounting_module')</small>
                    </div>
                </div>

                <div class="clearfix"></div>

                @include('transaction_payment.refund_payment_type_details')
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('note', __('lang_v1.payment_note') . ':') !!}
                        {!! Form::textarea('note', null, ['class' => 'form-control', 'rows' => 3]) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
    $(document).ready(function () {
        if ($('#type').val() == 'cheque_return') {
            $('.cheque_div').removeClass('hide');
            $('.bank_name_div').removeClass('hide');
            $('.cheque_return_charges_div').removeClass('hide');
            $('.bank_name_text_div').removeClass('hide');
            $('#amount').attr('readonly', true);
            $('.payment_types_dropdown_refund').attr('disabled', true);
            $('.payment_types_dropdown_refund').val('bank_transfer').trigger('change');
        }

        // Preselect saved bank and cheque number when editing an existing cheque
        var presetBankId = $('input[name="cheque_bank_s"]').val();
        var presetChequeNo = $('input[name="cheque_number_s"]').val();
        if (presetBankId) {
            $('#cheque_bank').val(presetBankId).trigger('change');
        }
        // If bank dropdown already has options (no ajax needed), try to set cheque directly
        if (presetChequeNo) {
            $('#cheque_number_return').val(presetChequeNo).trigger('change');
        }
    })

    $('#pay_contact_due_form').validate();

    $('#payment_type').change(function () {
        if ($(this).val() == 'advance_payment') {
            $('.account_id_div').addClass('hide');
        } else {
            $('.account_id_div').removeClass('hide');
        }
    })

    $('.paid_on_date').datepicker('setDate', new Date());
    $('.transfer_date').datepicker('setDate', new Date());
    $('.cheque_date').datepicker('setDate', new Date());

    $('#type').change(function () {
        if ($(this).val() === 'refund') {
            $('.cheque_div').addClass('hide');
            $('.bank_name_div').addClass('hide');
            $('.cheque_return_charges_div').addClass('hide');
            $('.sale_invoice_bill_number_div').removeClass('hide');
            $('.bank_name_div').addClass('hide');
            $('.bank_name_text_div').addClass('hide');
            $('#amount').attr('readonly', false);
            $('.payment_types_dropdown_refund').attr('disabled', false);
        } else if ($(this).val() === 'refund_excess_payment') {
            $('.cheque_div').addClass('hide');
            $('.bank_name_div').addClass('hide');
            $('.cheque_return_charges_div').addClass('hide');
            $('.sale_invoice_bill_number_div').addClass('hide');
            $('.bank_name_div').addClass('hide');
            $('.bank_name_text_div').addClass('hide');
            $('#amount').attr('readonly', false);
            $('.payment_types_dropdown_refund').attr('disabled', false);
        } else if ($(this).val() === 'cheque_return') {
            $('.cheque_div').removeClass('hide');
            $('.bank_name_div').removeClass('hide');
            $('.cheque_return_charges_div').removeClass('hide');
            $('.sale_invoice_bill_number_div').addClass('hide');
            $('.bank_name_div').addClass('hide');
            $('.bank_name_text_div').removeClass('hide');
            $('#amount').attr('readonly', true);
            $('.payment_types_dropdown_refund').attr('disabled', true);
            $('.payment_types_dropdown_refund').val('bank_transfer').trigger('change');
        } else {
            $('.cheque_div').addClass('hide');
            $('.bank_name_div').addClass('hide');
            $('.cheque_return_charges_div').addClass('hide');
            $('.sale_invoice_bill_number_div').addClass('hide');
            $('.bank_name_div').addClass('hide');
            $('.bank_name_text_div').addClass('hide');
            $('#amount').attr('readonly', false);
            $('.payment_types_dropdown_refund').attr('disabled', false);
        }
    })

    $(document).on('click', '#amount', function () {
        $("#amount").val("");
    });

    $(document).on('change', '#cheque_number_return', function () {
        let payment_id = $(this).val();

        $.ajax({
            method: 'get',
            url: '/payments/get-payment-details-by-id/' + payment_id,
            data: {},
            success: function (result) {
                __write_number($('#amount'), result.amount);
                $('#bank_name_text').val(result.bank_name);

            },
        });
    });

    $(document).on('change', '#cheque_bank', function () {
        let bank_id = $('#cheque_bank').val();
        if (bank_id) {
            $.ajax({
                method: 'get',
                url: '/payments/get-cheque-dropdown-by-bank-id/' + bank_id + '/{{ $contact_details->id }}',
                data: {},
                contentType: 'html',
                success: function (result) {
                    console.log(result);

                    $('#cheque_number_return').empty().append(result);
                    // Apply preset cheque number if available after options load
                    var presetChequeNo = $('input[name="cheque_number_s"]').val();
                    if (presetChequeNo) {
                        $('#cheque_number_return').val(presetChequeNo).trigger('change');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error loading cheques for bank:', error);
                    $('#cheque_number_return').empty().append('<option value="">@lang("lang_v1.no_cheques_found")</option>');
                }
            });
        } else {
            $('#cheque_number_return').empty().append('<option value="">@lang("lang_v1.please_select_bank_first")</option>');
        }
    });
    $(document).on('change', '.payment_types_dropdown_refund', function () {
        var payment_type = $(this).val();
        var to_show = null;
        var cheque_field = null;
        $(this)
            .closest('.payment_row')
            .find('.payment_details_div_refund')
            .each(function () {
                if ($(this).attr('data-type') == 'cheque') {
                    cheque_field = $(this);
                    $('.bank_name_div').removeClass('hide');
                }
                if ($(this).attr('data-type') == payment_type) {
                    to_show = $(this);
                } else {
                    if (!$(this).hasClass('hide')) {
                        $(this).addClass('hide');
                    }
                }
            });
        if (to_show && to_show.hasClass('hide')) {
            to_show.removeClass('hide');
            to_show.find('input').filter(':visible:first').focus();
        }

        $('.bank_name_div').removeClass('hide');
        if (payment_type == 'bank_transfer' || payment_type == 'cheque' || payment_type == 'cash') {
            if ($('#type').val() == 'cheque_return') {
                $('.bank_name_div').addClass('hide');
            }
        }


        if ($('#type').val() != 'cheque_return') {
            if (payment_type == 'bank_transfer') {
                $.ajax({
                    method: 'get',
                    url: '/finance/get-account-group-name-dp',
                    data: {
                        group_name: 'Bank Account'
                    },
                    contentType: 'html',
                    success: function (result) {
                        $('#account_id').empty().append(result);
                        $('#cheque_bank').empty().append(result);
                    },
                    error: function (xhr, status, error) {
                        console.error('Error loading bank accounts:', error);
                        $('#account_id').empty().append('<option value="">@lang("lang_v1.no_bank_accounts_found")</option>');
                    }
                });
            }
            if (payment_type == 'cheque') {
                $.ajax({
                    method: 'get',
                    url: '/accounting-module/get-account-dp?type=' + payment_type,
                    data: {},
                    contentType: 'html',
                    success: function (result) {
                        $('#account_id').empty().append(result);
                        $('#account_id')
                            .val($('#account_id option:contains("Cheques in Hand")').val())
                            .trigger('change');
                    },
                });
            }
            if (payment_type == 'cash') {
                $('.bank_name_text_div').addClass('hide');
                $.ajax({
                    method: 'get',
                    url: '/accounting-module/get-account-dp?type=' + payment_type,
                    data: {},
                    contentType: 'html',
                    success: function (result) {
                        $('#account_id').empty().append(result);
                        $('#account_id')
                            .val($('#account_id option:contains("Cash")').val())
                            .trigger('change');
                    },
                });
            }
        }
    });


    $(document).on('change', '#amount', function () {
        $('button#submit_btn').prop('disabled', true);
        var amount = $(this).val();
        paid = parseFloat();
        var accid = $('.account_id').val(amount.replace(/,/g, ''));

        $.ajax({
            method: 'GET',
            url: '/finance/check-insufficient-balance-for-accounts',
            success: function (result) {
                var ids = result;

                if (ids.includes(accid)) {

                    $.ajax({
                        method: 'GET',
                        url: '/finance/get-account-balance/' + accid,
                        success: function (result) {

                            if (parseFloat(paid) > parseFloat(result.balance) || result
                                .balance == null) {
                                swal({
                                    title: 'Insufficient Balance',
                                    icon: "error",
                                    buttons: true,
                                    dangerMode: true,
                                })


                                $('button#submit_btn').prop('disabled', true);
                                return false;
                            } else {
                                $('button#submit_btn').prop('disabled', false);
                            }
                        }
                    });
                } else {
                    $('button#submit_btn').prop('disabled', false);
                }

            }
        });
    });

    $(document).on('change', '.account_id', function () {

        $('button#submit_btn').prop('disabled', true);

        var amount = $('#amount').val();

        var accid = parseInt($(this).val());
        var paid = parseFloat(amount.replace(/,/g, ''));

        $.ajax({
            method: 'GET',
            url: '/finance/check-insufficient-balance-for-accounts',
            success: function (result) {
                var ids = result;
                if (ids.includes(accid)) {

                    $.ajax({
                        method: 'GET',
                        url: '/finance/get-account-balance/' + accid,
                        success: function (result) {

                            if (parseFloat(paid) > parseFloat(result.balance) || result
                                .balance == null) {
                                swal({
                                    title: 'Insufficient Balance',
                                    icon: "error",
                                    buttons: true,
                                    dangerMode: true,
                                })

                                $('button#submit_btn').prop('disabled', true);
                                return false;
                            } else {
                                $('button#submit_btn').prop('disabled', false);
                            }
                        }
                    });
                } else {
                    $('button#submit_btn').prop('disabled', false);
                }

            }
        });

    });
</script>