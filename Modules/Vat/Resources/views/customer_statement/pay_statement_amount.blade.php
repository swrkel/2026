<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content vat-statement-payment-modal">
        {!! Form::open([
            'url' => action('\Modules\Vat\Http\Controllers\CustomerStatementController@payStatementAmount', [$statement->id]),
            'method' => 'post',
            'id' => 'pay_contact_due_form',
            'class' => 'vat-statement-payment-form',
            'files' => true,
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">
                <i class="fa fa-credit-card"></i> Pay Statement Amount
            </h4>
        </div>

        <div class="modal-body">
            <div class="vat-payment-summary">
                <div>
                    <span class="vat-payment-summary-label">Statement No</span>
                    <strong>{{ $statement_display_no }}</strong>
                </div>
                <div>
                    <span class="vat-payment-summary-label">Customer</span>
                    <strong>{{ optional($statement->contact)->name }}</strong>
                </div>
                <div>
                    <span class="vat-payment-summary-label">Statement Period</span>
                    <strong>{{ @format_date($statement->date_from) }} - {{ @format_date($statement->date_to) }}</strong>
                </div>
            </div>

            @if($is_paid)
                <div class="alert alert-success vat-payment-alert">
                    <i class="fa fa-check-circle"></i>
                    This VAT Statement has already been paid.
                </div>
            @elseif($has_payment)
                <div class="alert alert-warning vat-payment-alert">
                    <i class="fa fa-warning"></i>
                    A payment already exists for this VAT Statement. Delete that payment before adding another one.
                </div>
            @elseif($statement_amount <= 0)
                <div class="alert alert-warning vat-payment-alert">
                    <i class="fa fa-warning"></i>
                    This VAT Statement does not have a payable amount.
                </div>
            @else
                <div class="alert alert-info vat-payment-alert">
                    <i class="fa fa-lock"></i>
                    The Statement Amount is locked. The payment is posted using the original amount saved with this VAT Statement.
                </div>
            @endif

            <div class="row payment_row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('vat_statement_amount_display', 'Statement Amount:*') !!}
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-money"></i></span>
                            {!! Form::text(
                                'vat_statement_amount_display',
                                $statement_amount_display,
                                [
                                    'class' => 'form-control text-right vat-statement-amount-locked',
                                    'id' => 'vat_statement_amount_display',
                                    'readonly' => true,
                                    'aria-readonly' => 'true',
                                ]
                            ) !!}
                            <span class="input-group-addon vat-lock-addon"><i class="fa fa-lock"></i></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-location-arrow"></i></span>
                            {!! Form::select(
                                'location_id',
                                $business_locations,
                                $business_location_id,
                                [
                                    'class' => 'form-control select2 location_id',
                                    'required' => true,
                                    'style' => 'width:100%;',
                                    'placeholder' => __('lang_v1.please_select'),
                                ]
                            ) !!}
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('payment_ref_no', __('lang_v1.ref_no') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-link"></i></span>
                            {!! Form::text('payment_ref_no', $payment_ref_no, [
                                'class' => 'form-control',
                                'readonly' => true,
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="clearfix"></div>

                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('paid_on', __('lang_v1.paid_on') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                            {!! Form::text('paid_on', @format_datetime($payment_line->paid_on), [
                                'class' => 'form-control',
                                'id' => 'paid_on',
                                'readonly' => true,
                                'required' => true,
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('method', __('purchase.payment_method') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-money"></i></span>
                            {!! Form::select('method', $payment_types, $payment_line->method, [
                                'class' => 'form-control select2 payment_types_dropdown',
                                'required' => true,
                                'style' => 'width:100%;',
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('account_id', __('lang_v1.payment_account') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-university"></i></span>
                            {!! Form::select('account_id', $accounts, $default_account_id ?: null, [
                                'class' => 'form-control select2 account_id',
                                'required' => true,
                                'style' => 'width:100%;',
                                'placeholder' => __('lang_v1.please_select'),
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="clearfix"></div>

                @include('transaction_payment.payment_type_details')

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('document', __('purchase.attach_document') . ':') !!}
                        {!! Form::file('document', ['class' => 'form-control']) !!}
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('note', __('lang_v1.payment_note') . ':') !!}
                        {!! Form::textarea('note', null, [
                            'class' => 'form-control',
                            'rows' => 3,
                            'placeholder' => 'VAT Statement payment note',
                        ]) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary vat-save-statement-payment" data-locked="{{ $is_paid || $has_payment || $statement_amount <= 0 ? 1 : 0 }}" {{ $is_paid || $has_payment || $statement_amount <= 0 ? 'disabled' : '' }}>
                <i class="fa fa-save"></i> @lang('messages.save')
            </button>
            <button type="button" class="btn btn-default" data-dismiss="modal">
                @lang('messages.close')
            </button>
        </div>

        {!! Form::close() !!}
    </div>
</div>

<style>
.vat-statement-payment-modal .modal-header {
    border-bottom: 1px solid #e5e7eb;
}
.vat-payment-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 14px;
}
.vat-payment-summary > div {
    border: 1px solid #e4e7ec;
    border-radius: 8px;
    padding: 10px 12px;
    background: #fafbfc;
}
.vat-payment-summary-label {
    display: block;
    color: #667085;
    font-size: 12px;
    margin-bottom: 3px;
}
.vat-payment-alert {
    margin-bottom: 16px;
}
.vat-statement-amount-locked {
    background: #fff8e1 !important;
    font-weight: 700;
    cursor: not-allowed;
}
.vat-lock-addon {
    color: #9a6700;
    background: #fff3cd;
}
@media (max-width: 767px) {
    .vat-payment-summary {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
(function ($) {
    'use strict';

    var $modal = $('.pay_contact_due_modal');
    var $form = $modal.find('form.vat-statement-payment-form');

    if ($.fn.select2) {
        $modal.find('select.select2').each(function () {
            var $select = $(this);
            if ($select.data('select2')) {
                return;
            }
            $select.select2({
                width: '100%',
                dropdownParent: $modal
            });
        });
    }
    $modal.find('.payment_types_dropdown').trigger('change');

    $form.off('submit.s555VatStatementPayment').on('submit.s555VatStatementPayment', function (event) {
        event.preventDefault();

        if ($form.data('submitting')) {
            return;
        }

        if ($.fn.validate && !$form.valid()) {
            return;
        }

        $form.data('submitting', true);
        var $button = $form.find('.vat-save-statement-payment');
        $button.prop('disabled', true).attr('data-original-text', $button.html()).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function (result) {
            if (result.success) {
                toastr.success(result.msg);
                $modal.modal('hide');
                if (window.customer_statement_list_table && window.customer_statement_list_table.ajax) {
                    window.customer_statement_list_table.ajax.reload(null, false);
                }
            } else {
                toastr.error(result.msg || @json(__('messages.something_went_wrong')));
            }
        }).fail(function (xhr) {
            var response = xhr.responseJSON || {};
            var message = response.msg || response.message;
            if (!message && typeof xhr.responseText === 'string') {
                var text = $.trim(xhr.responseText);
                if (text && text.charAt(0) !== '<' && text.length <= 500) {
                    message = text;
                }
            }

            if (!message && response.errors) {
                var firstKey = Object.keys(response.errors)[0];
                if (firstKey) {
                    message = response.errors[firstKey][0];
                }
            }

            toastr.error(message || @json(__('messages.something_went_wrong')));
        }).always(function () {
            $form.data('submitting', false);
            var original = $button.attr('data-original-text');
            $button
                .prop('disabled', String($button.data('locked')) === '1')
                .html(original || '<i class="fa fa-save"></i> Save');
        });
    });

    $modal.find('.location_id').off('change.s555VatLocation').on('change.s555VatLocation', function () {
        var locationId = $(this).val();
        if (!locationId) {
            return;
        }

        $.get('/payments/get-payment-method-by-location-id/' + locationId, function (result) {
            if (result) {
                var $method = $modal.find('#method');
                $method.empty().append(result).trigger('change');
            }
        });
    });
})(jQuery);
</script>
