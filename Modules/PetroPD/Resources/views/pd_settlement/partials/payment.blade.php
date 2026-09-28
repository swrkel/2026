{{--
    MA-002: the Payment tab's detail text, one point larger.

    RELATIVE, NOT A FIXED NUMBER. The base size is a business setting -
    $__erp_font_size in the layout - so writing a literal 15px here would
    ignore that setting and could make the tab SMALLER for anyone whose base
    is larger.

    calc(1em + 1px) adds exactly one point to whatever the base happens to be.

    Scoped to #payment_tab, so nothing else on the settlement screen changes.
    The one existing inline size in this partial - the brown 17px total - is
    left as it is, since it was set deliberately.
--}}
<style>
    #payment_tab,
    #payment_tab .form-control,
    #payment_tab table,
    #payment_tab td,
    #payment_tab th,
    #payment_tab label,
    #payment_tab span,
    #payment_tab div {
        font-size: calc(1em + 1px);
    }
    /* Nested elements would otherwise compound the increase. */
    #payment_tab div div,
    #payment_tab td span,
    #payment_tab th span {
        font-size: inherit;
    }
</style>
@php
    $add_payment_settlement_no = !empty($active_settlement) ? $active_settlement->settlement_no : $settlement_no;
    // PETROPD-PAYTOTAL-001: Use the same total for Payment tab and Payment to Finalize modal.
    // This prevents the modal from showing an older/different settlement total.
    $pd_payment_due_total = (float) $payment_meter_sale_total
        + (float) $pump_other_sale_final_total
        + (float) $payment_other_income_total
        + (float) $payment_customer_payment_total;
@endphp

<div class="row">
    <div class="col-md-12" style="margin-top: 20px;">
        <div class="col-md-4"></div>
        <div class="col-md-4">
            <div class="row">
                <div class="col-md-8" style="font-weight: bold; text-align: left;">
                    @lang('petropd::lang.meter_sale_total') :
                </div>
                <div class="col-md-4" style="font-weight: bold; text-align: right;">
                    <span
                        class="payment_meter_sale_total">{{ number_format($payment_meter_sale_total, $currency_precision) }}</span>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-8" style="font-weight: bold; text-align: left;">
                    @lang('petropd::lang.other_sale_total') :
                </div>
                <div class="col-md-4" style="font-weight: bold; text-align: right;">
                    <span
                        class="payment_other_sale_total">{{ number_format($pump_other_sale_final_total, $currency_precision) }}</span>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-8" style="font-weight: bold; text-align: left;">
                    @lang('petropd::lang.other_income_total') :
                </div>
                <div class="col-md-4" style="font-weight: bold; text-align: right;">
                    <span
                        class="payment_other_income_total">{{ number_format($payment_other_income_total, $currency_precision) }}</span>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-8" style="font-weight: bold; text-align: left;">
                    @lang('petropd::lang.customer_payment_total') :
                </div>
                <div class="col-md-4" style="font-weight: bold; text-align: right;">
                    <span
                        class="payment_customer_payment_total">{{ number_format($payment_customer_payment_total, $currency_precision) }}</span>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-8" style="font-weight: bold; text-align: left;">
                    @lang('petropd::lang.settlement_no') :
                </div>
                <div class="col-md-4" style="font-weight: bold; text-align: right;">
                    <span class="settlement_no">
                        {{ !empty($active_settlement) ? $active_settlement->settlement_no : $settlement_no }}
                    </span>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-8" style="font-weight: bold; text-align: left;">
                    @lang('petropd::lang.shift_number') :
                </div>
                <div class="col-md-4" style="font-weight: bold; text-align: right;">
                    <span class="shift_number">
                        {{ !empty($shift_id) ? $shift_id : $show_shift_no  ?? '' }}</span>
                </div>
            </div>
            <br>
        </div>
        <div class="col-md-4"></div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="pull-right" style="padding-right: 10px; font-size : 17px; color: brown;">
            <strong>@lang('purchase.payment_due'):</strong> <span
                id="payment_due">{{ number_format($pd_payment_due_total, $currency_precision) }}</span>
        </div>
    </div>
    <br>
    <br>
    <div class="col-md-12">
        <button type="button" id="add_payment" data-container=".add_payment"
            data-href="{{ route('petropd.add-payment.create') . '?' . http_build_query(['settlement_no' => $add_payment_settlement_no, 'type' => 'settlement_pd', 'source' => 'petropd', 'shift_ids' => $shift_id ?? null, 'pump_operator_id' => $pump_operator_id ?? null, 'active_settlement_id' => !empty($active_settlement) ? $active_settlement->id : null, 'pd_payment_due_total' => $pd_payment_due_total, 'no_change' => request()->no_change]) }}"
            class="btn btn-primary pull-right petropd-payment-finalize-trigger" data-petropd-payment-finalize="1">@lang('petropd::lang.payment_to_finalize')</button>
    </div>
</div>
<script>
/*
 * PETROPD-PAYMENT-FINALIZE-OPEN-001
 * Payment to Finalize is intentionally loaded by a page-scoped handler instead
 * of the global .btn-modal handler. Several global modal/tab handlers can be
 * present in the ERP layout; using one authoritative loader prevents duplicate
 * interception and guarantees that the Add Payment form opens in PetroPD.
 */
(function (window, document, $) {
    'use strict';

    if (!$) {
        return;
    }

    var finalizeRequest = null;

    function notifyPaymentFinalizeError(message) {
        var text = message || @json(__('messages.something_went_wrong'));

        if (window.toastr && typeof window.toastr.error === 'function') {
            window.toastr.error(text);
            return;
        }

        if (window.swal) {
            window.swal({
                title: @json(__('messages.error')),
                text: text,
                icon: 'error'
            });
            return;
        }

        window.alert(text);
    }

    function readPaymentFinalizeError(xhr) {
        if (xhr && xhr.responseJSON) {
            return xhr.responseJSON.msg || xhr.responseJSON.message || '';
        }

        var responseText = xhr && xhr.responseText ? String(xhr.responseText) : '';
        if (!responseText) {
            return '';
        }

        try {
            var parsed = JSON.parse(responseText);
            return parsed.msg || parsed.message || '';
        } catch (ignore) {}

        var titleMatch = responseText.match(/<title[^>]*>([^<]+)<\/title>/i);
        return titleMatch && titleMatch[1] ? titleMatch[1].trim() : '';
    }

    function buildPaymentFinalizeUrl($button) {
        var currentHref = $button.attr('data-href');
        if (!currentHref) {
            return '';
        }

        try {
            var urlObj = new URL(currentHref, window.location.origin);
            var paymentDueText = ($('#payment_due').text() || '0').toString().replace(/,/g, '').trim();
            var paymentDueValue = parseFloat(paymentDueText);
            var settlementNo = ($('#settlement_no').val() || $('.settlement_no').first().text() || '').toString().trim();
            var shiftId = ($('#shift_number').val() || $('#shift_id').val() || '').toString().trim();
            var operatorId = ($('#pump_operator_id').val() || '').toString().trim();
            var activeSettlementId = ($('#active_settlement_id').val() || '').toString().trim();
            var transactionDate = ($('#transaction_date').val() || $('.transaction_date').first().val() || '').toString().trim();

            if (!isNaN(paymentDueValue)) {
                urlObj.searchParams.set('pd_payment_due_total', paymentDueValue.toFixed({{ (int) ($currency_precision ?? 2) }}));
            }
            if (settlementNo) urlObj.searchParams.set('settlement_no', settlementNo);
            if (shiftId) {
                urlObj.searchParams.set('shift_id', shiftId);
                urlObj.searchParams.set('shift_ids', shiftId);
            }
            if (operatorId) urlObj.searchParams.set('pump_operator_id', operatorId);
            if (activeSettlementId) urlObj.searchParams.set('active_settlement_id', activeSettlementId);
            if (transactionDate) urlObj.searchParams.set('transaction_date', transactionDate);

            urlObj.searchParams.set('type', 'settlement_pd');
            urlObj.searchParams.set('source', 'petropd');

            // IS1771: Payment to Finalize is a normal final save.  The previous
            // code forced no_change=1 for every request, which made the server
            // skip credit-sale transaction/customer-ledger posting.  Preserve
            // no_change only when the user actually opened Edit - No Change.
            var isNoChangeEdit = @json(!empty(request()->no_change));
            if (isNoChangeEdit) {
                urlObj.searchParams.set('no_change', '1');
            } else {
                urlObj.searchParams.delete('no_change');
            }

            var normalized = urlObj.pathname + urlObj.search;
            $button.attr('data-href', normalized);
            return normalized;
        } catch (error) {
            window.console && console.error('Failed to build PetroPD Payment to Finalize URL:', error);
            return currentHref;
        }
    }

    window.petroPdSyncPaymentFinalizeTotal = function () {
        var $button = $('#add_payment');
        if ($button.length) {
            buildPaymentFinalizeUrl($button);
        }
    };

    function openPaymentFinalize($button) {
        if ($button.prop('disabled') || $button.hasClass('disabled')) {
            notifyPaymentFinalizeError(@json(__('petropd::lang.please_select_shift')));
            return;
        }

        var url = buildPaymentFinalizeUrl($button);
        var $modal = $('.add_payment').first();

        if (!url || !$modal.length) {
            notifyPaymentFinalizeError(@json(__('messages.something_went_wrong')));
            return;
        }

        if (finalizeRequest && finalizeRequest.readyState !== 4) {
            return;
        }

        var originalHtml = $button.html();
        $button.prop('disabled', true)
            .addClass('disabled petropd-payment-finalize-loading')
            .attr('aria-busy', 'true')
            .html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> ' + originalHtml);

        finalizeRequest = $.ajax({
            url: url,
            type: 'GET',
            dataType: 'html',
            cache: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).done(function (html) {
            if (!html || !String(html).trim()) {
                notifyPaymentFinalizeError(@json(__('messages.something_went_wrong')));
                return;
            }

            $modal.empty().html(html);

            if ($.fn.modal) {
                $modal.modal({
                    backdrop: 'static',
                    keyboard: false,
                    show: true
                });
            } else {
                $modal.addClass('in').attr('aria-hidden', 'false').show();
            }

            $modal.trigger('petropd:add-payment-loaded');
        }).fail(function (xhr) {
            notifyPaymentFinalizeError(readPaymentFinalizeError(xhr) || @json(__('messages.something_went_wrong')));
            window.console && console.error('PetroPD Payment to Finalize request failed:', xhr.status, xhr.responseText);
        }).always(function () {
            $button.prop('disabled', false)
                .removeClass('disabled petropd-payment-finalize-loading')
                .removeAttr('aria-busy')
                .html(originalHtml);
            finalizeRequest = null;
        });
    }

    function finalizeClickCapture(event) {
        var target = event.target && event.target.closest ? event.target.closest('#add_payment') : null;
        if (!target) {
            return;
        }

        // Prevent all global .btn-modal / tab handlers from processing this button.
        event.preventDefault();
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
        }

        openPaymentFinalize($(target));
    }

    if (!window.__petroPdPaymentFinalizeBound) {
        window.__petroPdPaymentFinalizeBound = true;
        document.addEventListener('click', finalizeClickCapture, true);
    }

    $(function () {
        window.petroPdSyncPaymentFinalizeTotal();
    });

    $(document).off('change.petroPdFinalize keyup.petroPdFinalize', '#transaction_date, .transaction_date')
        .on('change.petroPdFinalize keyup.petroPdFinalize', '#transaction_date, .transaction_date', function () {
            window.petroPdSyncPaymentFinalizeTotal();
        });

    // Listen for payment updated event and recalculate totals.
    $(document).off('payment:updated.petroPdFinalize').on('payment:updated.petroPdFinalize', function () {
        var settlementNo = $('.settlement_no').first().text().trim();

        if (!settlementNo) {
            return;
        }

        $.ajax({
            url: @json(url('/petropd/settlement-pd/get-payment-tab-totals')),
            method: 'GET',
            data: { settlement_no: settlementNo }
        }).done(function (result) {
            if (!result || !result.success) {
                return;
            }

            var precision = {{ (int) ($currency_precision ?? 2) }};
            $('.payment_meter_sale_total').text(__number_f(result.meter_sale_total, false, false, precision));
            $('.payment_other_sale_total').text(__number_f(result.other_sale_total, false, false, precision));
            $('.payment_other_income_total').text(__number_f(result.other_income_total, false, false, precision));
            $('.payment_customer_payment_total').text(__number_f(result.customer_payment_total, false, false, precision));

            var totalDue = Number(result.meter_sale_total || 0)
                + Number(result.other_sale_total || 0)
                + Number(result.other_income_total || 0)
                + Number(result.customer_payment_total || 0);
            $('#payment_due').text(__number_f(totalDue, false, false, precision));
            window.petroPdSyncPaymentFinalizeTotal();
        }).fail(function (xhr) {
            window.console && console.error('Error updating PetroPD payment totals:', xhr);
        });
    });
})(window, document, window.jQuery);
</script>
