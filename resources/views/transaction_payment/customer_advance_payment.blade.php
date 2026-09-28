<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open([
            'url' => action('TransactionPaymentController@postAdvancePayment', $contact_id),
            'method' => 'post',
            'id' => 'pay_contact_due_form',
            'files' => true,
        ]) !!}

        {!! Form::hidden('contact_id', $contact_details->id) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('lang_v1.advance_payment')</h4>
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
                        {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-location-arrow"></i>
                            </span>
                            {!! Form::select('location_id', $business_locations, $business_location_id, [
                                'class' => 'form-control select2 location_id',
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
                            {!! Form::text('amount', null, [
                                'class' => 'form-control input_number',
                                'data-rule-min-value' => 0,
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
                            {!! Form::text('paid_on', date('m/d/Y'), ['class' => 'form-control', 'readonly', 'required']) !!}
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
                            {!! Form::select('method', $payment_types, null, [
                                'class' => 'form-control select2 payment_types_dropdown',
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
                <div class="col-md-6 account_id_div">
                    <div class="form-group">
                        {!! Form::label('account_id', __('lang_v1.payment_account') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-money"></i>
                            </span>
                            {!! Form::select('account_id', $accounts, $customer_deposit_account_id, [
                                'class' => 'form-control select2 account_id',
                                'placeholder' => __('lang_v1.please_select'),
                                'id' => 'account_id',
                                'style' => 'width:100%;',
                            ]) !!}
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>

                @include('transaction_payment.advance_payment_type_details')

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
    var paymentActionFilter = @json($contact_details->type == 'customer' ? 'is_sale_enabled' : 'is_purchase_enabled');

    function removeUnsupportedPaymentOptions($selectEl) {
        if (!$selectEl || !$selectEl.length) return;
        $selectEl.find('option[value="credit_sale"], option[value="credit sales"], option[value="credit_sale_payment"], option[value="credit_purchase"], option[value="credit purchase"], option[value="pd_cheque"], option[value="post_dated_cheque"], option[value="post dated cheque"]').remove();
    }

    $('.payment_types_dropdown').trigger('change');
    $('#pay_contact_due_form').validate();
    removeUnsupportedPaymentOptions($('#method'));
    $('#payment_type').change(function() {
        if ($(this).val() == 'advance_payment') {
            $('.account_id_div').addClass('hide');
        } else {
            $('.account_id_div').removeClass('hide');
        }
    })

    $(document).on('change', '.location_id', function() {
        let location_id = $(this).val();
        $.ajax({
            method: 'get',
            url: "/payments/get-payment-method-by-location-id/" + location_id,
            data: {
                action: paymentActionFilter
            },
            contentType: 'html',
            success: function(result) {
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

    $(document).on('change', '.payment_types_dropdown', function() {
        var val = ($(this).val() || '').toString().toLowerCase().trim();
        var hasPdChequeOption = @json(!empty($pdChequePermissions['post_dated_cheque']) || !empty($pdChequePermissions['update_post_dated_cheque']));
        var isBankFamilyMethod = ['bank', 'bank_transfer', 'direct_bank_deposit'].includes(val);
        var isChequeMethod = val === 'cheque' || val === 'check' || val.indexOf('cheque') !== -1 || val.indexOf('check') !== -1;
        var supportsBankDetails = isBankFamilyMethod || isChequeMethod;

        $('.payment_details_div').addClass('hide');
        if (supportsBankDetails) {
            $('.payment_details_div[data-type="cheque"]').removeClass('hide');
        } else if (val) {
            $('.payment_details_div[data-type="' + val + '"]').removeClass('hide');
        }

        if (isChequeMethod) {
            $('.payment_details_div[data-type="cheque"]').removeClass('hide');
            $('.cheque_payment_details_only').removeClass('hide');
            if (typeof get_cheques_list === 'function') {
                get_cheques_list('cheque', $('.payment_row').first());
            }
        } else {
            $('.cheque_payment_details_only').addClass('hide');
        }

        if (hasPdChequeOption && isChequeMethod) {
            $('.pd_cheque_boxes').removeClass('hide');
        } else {
            $('.pd_cheque_boxes').addClass('hide');
            $('#post_dated_cheque, #update_post_dated_cheque').prop('checked', false).trigger('change');
            if ($.fn.iCheck) {
                $('#post_dated_cheque, #update_post_dated_cheque').iCheck('update');
            }
        }
    });

    $(document).on('change', '#update_post_dated_cheque', function() {
        console.log("update_post_dated_cheque");

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
                success: function(result) {
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
                success: function(result) {
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

    $(document).on('change', '#amount', function() {
        @if ($contact_details->type == 'supplier')
            $('button#submit_btn').prop('disabled', true);
            var amount = $(this).val();
            paid = parseFloat();
            var accid = $('.account_id').val(amount.replace(/,/g, ''));

            $.ajax({
                method: 'GET',
                url: '/finance/check-insufficient-balance-for-accounts',
                success: function(result) {
                    var ids = result;

                    if (ids.includes(accid)) {

                        $.ajax({
                            method: 'GET',
                            url: '/finance/get-account-balance/' + accid,
                            success: function(result) {

                                if (parseFloat(paid) > parseFloat(result.balance) ||
                                    result.balance == null) {
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
        @endif
    });

    $(document).on('click', '#amount', function() {
        $("#amount").val("");
    });

    $(document).on('change', '.account_id', function() {

        @if ($contact_details->type == 'supplier')
            // alert("checking");
            $('button#submit_btn').prop('disabled', true);

            var amount = $('#amount').val();

            var accid = parseInt($(this).val());
            var paid = parseFloat(amount.replace(/,/g, ''));

            $.ajax({
                method: 'GET',
                url: '/finance/check-insufficient-balance-for-accounts',
                success: function(result) {
                    var ids = result;
                    if (ids.includes(accid)) {

                        $.ajax({
                            method: 'GET',
                            url: '/finance/get-account-balance/' + accid,
                            success: function(result) {

                                if (parseFloat(paid) > parseFloat(result.balance) ||
                                    result.balance == null) {
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
        @endif

    });
</script>
