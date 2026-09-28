@include('pumperdashboard::partials.pumper_dashboard_ui_standard')

@php
    $formatted_amount = is_numeric($payment->payment_amount)
        ? number_format((float) $payment->payment_amount, 2, '.', '')
        : $payment->payment_amount;
    $formatted_amount = rtrim(rtrim((string) $formatted_amount, '0'), '.');

    $payment_type_label = !empty($payment->payment_type)
        ? ucwords(str_replace('_', ' ', (string) $payment->payment_type))
        : '—';

    $operator_name = $payment->pump_operator_name ?? '';
    $location_name = $payment->location_name ?? '';
    $shift_number = !empty($payment->shift_id) ? $payment->shift_id : '—';
    $collection_no = !empty($payment->collection_form_no) ? $payment->collection_form_no : '—';
@endphp

<style>
    .pd-payment-edit-modal {
        width: 94%;
        max-width: 760px;
        margin: 30px auto;
    }

    .pd-payment-edit-modal .modal-content {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 18px 46px rgba(15, 23, 42, .20);
        background: #f7f9fc;
    }

    .pd-payment-edit-modal .pd-edit-header {
        position: relative;
        padding: 20px 24px;
        border: 0;
        background: linear-gradient(135deg, #1f4e79 0%, #2874a6 100%);
        color: #fff;
    }

    .pd-payment-edit-modal .pd-edit-header .close {
        position: absolute;
        top: 12px;
        right: 15px;
        width: 34px;
        height: 34px;
        margin: 0;
        border-radius: 50%;
        color: #fff;
        opacity: .95;
        text-shadow: none;
        font-size: 26px;
        line-height: 30px;
    }

    .pd-payment-edit-modal .pd-edit-header .close:hover,
    .pd-payment-edit-modal .pd-edit-header .close:focus {
        background: rgba(255, 255, 255, .14);
        opacity: 1;
    }

    .pd-payment-edit-modal .pd-edit-title-row {
        display: flex;
        align-items: center;
        gap: 14px;
        padding-right: 42px;
    }

    .pd-payment-edit-modal .pd-edit-title-icon {
        width: 46px;
        height: 46px;
        flex: 0 0 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 11px;
        background: rgba(255, 255, 255, .16);
        font-size: 21px;
    }

    .pd-payment-edit-modal .modal-title {
        margin: 0;
        color: #fff;
        font-size: 21px;
        font-weight: 800;
        line-height: 1.2;
    }

    .pd-payment-edit-modal .pd-edit-subtitle {
        margin-top: 4px;
        color: rgba(255, 255, 255, .86);
        font-size: 12px;
        font-weight: 600;
    }

    .pd-payment-edit-modal .modal-body {
        padding: 22px 24px 8px;
        background: #f7f9fc;
    }

    .pd-payment-edit-modal .pd-edit-feedback {
        display: none;
        margin: 0 0 16px;
        padding: 11px 14px;
        border-radius: 9px;
        font-weight: 700;
    }

    .pd-payment-edit-modal .pd-summary-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 18px;
    }

    .pd-payment-edit-modal .pd-summary-card {
        min-height: 68px;
        padding: 10px 12px;
        border: 1px solid #dde6ef;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 2px 7px rgba(15, 23, 42, .04);
    }

    .pd-payment-edit-modal .pd-summary-label {
        display: block;
        margin-bottom: 4px;
        color: #6b7280;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .055em;
        text-transform: uppercase;
    }

    .pd-payment-edit-modal .pd-summary-value {
        display: block;
        overflow: hidden;
        color: #1f2937;
        font-size: 14px;
        font-weight: 800;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .pd-payment-edit-modal .pd-edit-panel {
        margin-bottom: 14px;
        padding: 17px;
        border: 1px solid #dce5ef;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 3px 10px rgba(15, 23, 42, .045);
    }

    .pd-payment-edit-modal .pd-edit-panel-title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 14px;
        color: #1f4e79;
        font-size: 14px;
        font-weight: 800;
    }

    .pd-payment-edit-modal .form-group {
        margin-bottom: 16px;
    }

    .pd-payment-edit-modal .form-group:last-child {
        margin-bottom: 0;
    }

    .pd-payment-edit-modal label {
        margin-bottom: 7px;
        color: #374151;
        font-size: 12px;
        font-weight: 800;
    }

    .pd-payment-edit-modal .required-mark {
        color: #dc2626;
    }

    .pd-payment-edit-modal .form-control {
        min-height: 44px;
        border: 1px solid #cfd9e5;
        border-radius: 8px;
        box-shadow: none;
        font-size: 14px;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .pd-payment-edit-modal .form-control:focus {
        border-color: #2874a6;
        box-shadow: 0 0 0 3px rgba(40, 116, 166, .10);
    }

    .pd-payment-edit-modal textarea.form-control {
        min-height: 96px;
        resize: vertical;
    }

    .pd-payment-edit-modal .pd-amount-wrap {
        position: relative;
    }

    .pd-payment-edit-modal .pd-amount-input {
        padding-left: 12px;
        text-align: right;
        font-size: 17px;
        font-weight: 800;
    }

    .pd-payment-edit-modal .pd-help-text {
        margin: 6px 0 0;
        color: #6b7280;
        font-size: 11px;
    }

    .pd-payment-edit-modal .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 9px;
        padding: 14px 24px 20px;
        border: 0;
        background: #f7f9fc;
    }

    .pd-payment-edit-modal .modal-footer .btn {
        min-width: 112px;
        min-height: 42px;
        border-radius: 8px;
        font-weight: 800;
    }

    .pd-payment-edit-modal .pd-save-payment-btn {
        border-color: #2874a6;
        background: #2874a6;
        color: #fff !important;
    }

    .pd-payment-edit-modal .pd-save-payment-btn:hover,
    .pd-payment-edit-modal .pd-save-payment-btn:focus {
        border-color: #1f5f8b;
        background: #1f5f8b;
        color: #fff !important;
    }

    .pd-payment-edit-modal .pd-save-payment-btn[disabled] {
        cursor: wait;
        opacity: .72;
    }

    @media (max-width: 767px) {
        .pd-payment-edit-modal {
            width: calc(100% - 20px);
            margin: 10px auto;
        }

        .pd-payment-edit-modal .pd-summary-grid {
            grid-template-columns: 1fr;
        }

        .pd-payment-edit-modal .modal-body,
        .pd-payment-edit-modal .pd-edit-header,
        .pd-payment-edit-modal .modal-footer {
            padding-left: 16px;
            padding-right: 16px;
        }

        .pd-payment-edit-modal .modal-footer {
            display: block;
        }

        .pd-payment-edit-modal .modal-footer .btn {
            width: 100%;
            margin: 0 0 8px !important;
        }
    }
</style>

<div class="modal-dialog pd-payment-edit-modal pumper-ui-standard" role="document">
    <div class="modal-content">
        {!! Form::open([
            'url' => action('\Modules\\PumperDashboard\\Http\\Controllers\\PumpOperatorPaymentController@update', $payment->id),
            'method' => 'put',
            'id' => 'pd_payment_edit_form'
        ]) !!}

        <div class="modal-header pd-edit-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="@lang('messages.close')">
                <span aria-hidden="true">&times;</span>
            </button>

            <div class="pd-edit-title-row">
                <div class="pd-edit-title-icon">
                    <i class="fa fa-pencil-square-o"></i>
                </div>
                <div>
                    <h4 class="modal-title">@lang('pumperdashboard::lang.edit_payment')</h4>
                    <div class="pd-edit-subtitle">Payment Summary / Action / Edit</div>
                </div>
            </div>
        </div>

        <div class="modal-body">
            <div class="alert pd-edit-feedback" role="alert" aria-live="polite"></div>

            <div class="pd-summary-grid">
                <div class="pd-summary-card">
                    <span class="pd-summary-label">Pump Operator</span>
                    <span class="pd-summary-value" title="{{ $operator_name ?: '—' }}">
                        {{ $operator_name ?: '—' }}
                    </span>
                </div>

                <div class="pd-summary-card">
                    <span class="pd-summary-label">@lang('pumperdashboard::lang.location')</span>
                    <span class="pd-summary-value" title="{{ $location_name ?: '—' }}">
                        {{ $location_name ?: '—' }}
                    </span>
                </div>

                <div class="pd-summary-card">
                    <span class="pd-summary-label">Payment Type</span>
                    <span class="pd-summary-value">{{ $payment_type_label }}</span>
                </div>

                <div class="pd-summary-card">
                    <span class="pd-summary-label">Shift / Collection No.</span>
                    <span class="pd-summary-value">
                        {{ $shift_number }} / {{ $collection_no }}
                    </span>
                </div>
            </div>

            <div class="pd-edit-panel">
                <div class="pd-edit-panel-title">
                    <i class="fa fa-money"></i>
                    Payment Details
                </div>

                <div class="form-group">
                    {!! Form::label('payment_amount', __('pumperdashboard::lang.amount')) !!}
                    <span class="required-mark">*</span>

                    <div class="pd-amount-wrap">
                        {!! Form::text('payment_amount', $formatted_amount, [
                            'class' => 'form-control amount pd-amount-input',
                            'required',
                            'inputmode' => 'decimal',
                            'autocomplete' => 'off',
                            'placeholder' => __('pumperdashboard::lang.amount')
                        ]) !!}
                    </div>
                </div>

                <div class="form-group">
                    {!! Form::label('note', __('pumperdashboard::lang.note')) !!}
                    <span class="required-mark">*</span>

                    {!! Form::textarea('note', $payment->note, [
                        'class' => 'form-control note',
                        'required',
                        'rows' => 4,
                        'placeholder' => 'Enter the reason / note for this payment update'
                    ]) !!}
                    <p class="pd-help-text">
                        <i class="fa fa-info-circle"></i>
                        A note is compulsory when a payment is edited.
                    </p>
                </div>
            </div>

            @if(isset($payment->credit_sale_id))
                {!! Form::hidden('credit_sale_id', $payment->credit_sale_id) !!}
            @endif

            {!! Form::hidden('payment_type', $payment->payment_type) !!}
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">
                <i class="fa fa-times"></i> @lang('messages.close')
            </button>
            <button type="submit" class="btn btn-primary pd-save-payment-btn">
                <i class="fa fa-save"></i>
                <span class="pd-save-payment-text">@lang('messages.save')</span>
            </button>
        </div>

        {!! Form::close() !!}
    </div>
</div>

<script>
(function ($) {
    'use strict';

    var $form = $('#pd_payment_edit_form');
    var $feedback = $form.find('.pd-edit-feedback');
    var $saveButton = $form.find('.pd-save-payment-btn');
    var originalSaveHtml = $saveButton.html();

    function showFeedback(type, message) {
        $feedback
            .removeClass('alert-success alert-danger alert-warning')
            .addClass(type === 'success' ? 'alert-success' : 'alert-danger')
            .text(message || '')
            .stop(true, true)
            .fadeIn(120);
    }

    function setSubmitting(isSubmitting) {
        $saveButton.prop('disabled', isSubmitting);

        if (isSubmitting) {
            $saveButton.html(
                '<i class="fa fa-spinner fa-spin"></i> Updating...'
            );
        } else {
            $saveButton.html(originalSaveHtml);
        }
    }

    function getErrorMessage(xhr) {
        var response = xhr && xhr.responseJSON ? xhr.responseJSON : {};

        if (response.errors) {
            var messages = [];
            $.each(response.errors, function (field, fieldMessages) {
                if ($.isArray(fieldMessages)) {
                    messages = messages.concat(fieldMessages);
                } else if (fieldMessages) {
                    messages.push(fieldMessages);
                }
            });

            if (messages.length) {
                return messages.join(' ');
            }
        }

        return response.msg ||
            response.message ||
            @json(__('pumperdashboard::lang.payment_update_failed'));
    }

    $form.off('submit.pdPaymentEdit').on('submit.pdPaymentEdit', function (e) {
        e.preventDefault();

        if ($form.data('submitting')) {
            return;
        }

        $form.data('submitting', true);
        $feedback.hide().text('');
        setSubmitting(true);

        var paymentId = @json((string) $payment->id);
        var paymentType = @json((string) $payment->payment_type);

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .done(function (response) {
            if (!response || !response.success) {
                var failureMessage = response && response.msg
                    ? response.msg
                    : @json(__('pumperdashboard::lang.payment_update_failed'));

                showFeedback('error', failureMessage);

                if (typeof toastr !== 'undefined') {
                    toastr.error(failureMessage);
                }

                $form.data('submitting', false);
                setSubmitting(false);
                return;
            }

            var successMessage = response.msg ||
                @json(__('pumperdashboard::lang.payment_updated_successfully'));

            showFeedback('success', successMessage);

            if (typeof toastr !== 'undefined') {
                toastr.success(successMessage);
            }

            if (paymentType === 'cash' && typeof updateCashPaymentRow === 'function') {
                updateCashPaymentRow(paymentId, response.payment_amount, response.old_amount);
            }

            $(document).trigger('payment:updated', [response]);

            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#pump_operator_payment_table')) {
                $('#pump_operator_payment_table').DataTable().ajax.reload(null, false);
            }

            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#pump_operators_payment_summary_table')) {
                $('#pump_operators_payment_summary_table').DataTable().ajax.reload(null, false);
            }

            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#daily_collection_table')) {
                $('#daily_collection_table').DataTable().ajax.reload(null, false);
            }

            window.setTimeout(function () {
                $form.closest('.modal').modal('hide');
            }, 550);
        })
        .fail(function (xhr) {
            var errorMessage = getErrorMessage(xhr);

            showFeedback('error', errorMessage);

            if (typeof toastr !== 'undefined') {
                toastr.error(errorMessage);
            }

            $form.data('submitting', false);
            setSubmitting(false);
        });
    });
})(jQuery);
</script>
