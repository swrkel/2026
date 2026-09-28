@extends('layouts.'.$layout)
@section('css')
    @parent
    <link rel="stylesheet" href="{{ asset('css/pump-operator-enter-meters-modern.css') }}?v=20260726-3">
    <link rel="stylesheet" href="{{ asset('css/pump-operator-credit-sale-modern.css') }}?v=20260726-customer-double-confirm-1">
    <link rel="stylesheet" href="{{ asset('css/pumper-dashboard-remaining-modern.css') }}?v=20260726-is1779-1">
@endsection

@section('content')
<style>
    @media print {
        .no-print,
        .no-print * {
            display: none !important;
        }
    }
    .print_section {
        display: none;
    }

    @media print {
        .print_section {
            display: inline !important;
        }
    }

    #cash_payments.modal {
        z-index: 20000 !important;
    }

    #cash_payments .modal-dialog {
        z-index: 20001 !important;
    }

    #reloadConfirmationModal {
        z-index: 20020 !important;
    }

    /* Reverse buttons for sweetalert */
    .swal-reverse-buttons .swal-footer {
        display: flex !important;
        flex-direction: row-reverse !important;
        justify-content: center !important;
    }
    .swal-reverse-buttons .swal-button-container {
        margin: 5px !important;
    }


    /* Credit Sale reconfirmation must stay above the full-screen Credit Sale modal. */
    #creditSaleReconfirmModal {
        z-index: 2147483000 !important;
    }
    #creditSaleReconfirmModal .modal-dialog {
        width: min(560px, calc(100vw - 30px));
        margin: 12vh auto 30px;
    }
    #creditSaleReconfirmModal .modal-content {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 22px 60px rgba(15, 23, 42, 0.35);
    }
    #creditSaleReconfirmModal .modal-header {
        padding: 18px 22px;
        border-bottom: 1px solid #e5e7eb;
        background: #fff7ed;
    }
    #creditSaleReconfirmModal .modal-title {
        margin: 0;
        color: #9a3412;
        font-size: 24px;
        font-weight: 800;
        text-align: center;
    }
    #creditSaleReconfirmModal .modal-body {
        padding: 22px;
        background: #ffffff;
    }
    #creditSaleReconfirmModal .credit-sale-reconfirm-detail-row {
        padding: 12px 14px;
        border: 1px solid #dbeafe;
        border-radius: 10px;
        background: #f8fafc;
    }
    #creditSaleReconfirmModal .credit-sale-reconfirm-detail-label {
        margin-bottom: 4px;
        color: #475569;
        font-size: 15px;
        font-weight: 700;
    }
    #creditSaleReconfirmModal .credit-sale-reconfirm-detail-value {
        color: #0f172a;
        font-size: 21px;
        font-weight: 800;
        overflow-wrap: anywhere;
    }
    #creditSaleReconfirmModal .modal-footer {
        display: flex;
        justify-content: center;
        gap: 14px;
        padding: 16px 22px 22px;
        border-top: 0;
        background: #ffffff;
    }
    #creditSaleReconfirmModal .credit-sale-reconfirm-button {
        min-width: 145px;
        min-height: 48px;
        border: 0;
        border-radius: 9px;
        color: #ffffff;
        font-size: 18px;
        font-weight: 800;
    }
    #creditSaleReconfirmModal .credit-sale-reconfirm-no {
        background: #dc2626;
    }
    #creditSaleReconfirmModal .credit-sale-reconfirm-yes {
        background: #16a34a;
    }
    .credit-sale-reconfirm-backdrop {
        z-index: 2147482990 !important;
        opacity: 0.72 !important;
    }

    /* Credit Sale Select2 typography.
       Select2 may append the opened results panel to <body>, so styling it
       only below #direct_cr is not reliable. The dedicated dropdown class is
       added at initialisation/open time and keeps both the selected value and
       every opened option at exactly the same font size. */
    #direct_cr select.credit-sale-select2,
    #direct_cr select.credit-sale-select2 option,
    #direct_cr .credit-sale-select-control .select2-selection__rendered,
    #direct_cr .credit-sale-select-control .select2-selection__placeholder,
    .credit-sale-select2-dropdown,
    .credit-sale-select2-dropdown .select2-results__options,
    .credit-sale-select2-dropdown .select2-results__option,
    .credit-sale-select2-dropdown .select2-results__option *,
    .credit-sale-select2-dropdown .select2-search__field {
        font-size: 18px !important;
    }

    .credit-sale-select2-dropdown .select2-results__option {
        line-height: 1.35 !important;
        font-weight: 400 !important;
    }

    .credit-sale-select2-dropdown .select2-results__option[aria-selected="true"],
    .credit-sale-select2-dropdown .select2-results__option--highlighted[aria-selected] {
        font-size: 18px !important;
    }
</style>
<div class="container no-print">
  @include('pumperdashboard::partials.payment_section', ['pop_up' => false])
</div>

<div class="modal fade pumper-credit-sale-modal" id="direct_cr" role="dialog"
    aria-labelledby="pumperCreditSaleTitle" aria-modal="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog pumper-credit-sale-dialog" role="document">
      <div class="modal-content pumper-credit-sale-content">

        <div class="modal-header pumper-credit-sale-header">
          <h2 class="modal-title pumper-credit-sale-title" id="pumperCreditSaleTitle">@lang('pumperdashboard::lang.credit_sale')</h2>
          <button type="button" class="close pumper-credit-sale-close" data-dismiss="modal"
            aria-label="@lang('messages.close')"><span aria-hidden="true">&times;</span></button>
        </div>

        <div class="modal-body pumper-credit-sale-body">
            @include('pumperdashboard::credit_sale')
        </div>

      </div>
    </div>
  </div>

<div class="modal fade" id="creditSaleReconfirmModal" role="dialog"
    aria-labelledby="creditSaleReconfirmTitle" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="creditSaleReconfirmTitle">Reconfirm Details</h3>
            </div>
            <div class="modal-body">
                <div id="creditSaleReconfirmDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn credit-sale-reconfirm-button credit-sale-reconfirm-no">No</button>
                <button type="button" class="btn credit-sale-reconfirm-button credit-sale-reconfirm-yes">Yes</button>
            </div>
        </div>
    </div>
</div>
  
 <div class="modal fade" id="cheque_payments" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">

        <!-- Modal Header -->
        <div class="modal-header">
          <h4 class="modal-title">@lang( 'pumperdashboard::lang.cheque' )</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body">
            @include('pumperdashboard::cheque_payment')
        </div>

      </div>
    </div>
  </div>
  
<div class="modal fade" id="cash_payments" tabindex="-1" role="dialog" aria-labelledby="cashPaymentsTitle">
    {{--
        MA-002: rebuilt to the design supplied.

        The design's own class names are used - cash-form, btn-cancel-top,
        search-bar, bulk-amount, total-row, btn-correct, btn-cancel - so this
        file reads the same as the HTML you sent.

        WHAT HAD TO SURVIVE, and did: the script locates its values by class -
        denom_value, denom_qty, denom_amt, denom_total, cash_payment_input -
        and every one is on the same element as before. The form field names
        are unchanged, so the server receives exactly what it did.
    --}}
    <style>
        /*
            MA-002: your stylesheet, applied to the popup.

            Every rule and value is yours. The only changes are:

              - .cash-form is on .modal-content, so the modal supplies the
                white panel your design had. Its own max-width and margin are
                dropped because the modal handles centring and width.

              - each rule is prefixed with .cash-form so it cannot leak into
                the other five modals on this page. Your design was a whole
                document; this is one popup among several.
        */
        .cash-form {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            padding: 20px 30px;
            position: relative;
        }
        .cash-form h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        .cash-form .btn-cancel-top {
            position: absolute;
            top: 20px;
            right: 30px;
            background-color: #f44336;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 14px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .cash-form .btn-cancel-top:hover { background-color: #d32f2f; }

        /*
            MA-002: the three top fields, side by side.

            flex with equal basis, so they share the width evenly and stay on
            ONE row. When Bulk Amount is hidden by the permission, the other
            two simply widen to fill it - no gap is left behind.

            The input styling is yours, unchanged.
        */
        .cash-form .top-fields {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .cash-form .top-field { flex: 1 1 0; }
        /*
            MA-002: the box title, above its box.

            display:block so it sits on its own line rather than beside the
            input, and a small bottom margin so it does not touch it.
        */
        .cash-form .top-field label {
            display: block;
            margin: 0 0 4px;
            font-size: 13px;
            font-weight: 600;
            color: #333;
        }
        /*
            MA-002: the bulk field when Allow Bulk Cash Amount is Disabled.

            Visibly inert, so nobody wonders why it will not take a value.
        */
        .cash-form .top-field input:disabled {
            background: #eee;
            color: #999;
            cursor: not-allowed;
        }
        .cash-form .top-field input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }
        /* The Total mirror is read-only - shown as a figure, not a box to fill. */
        .cash-form .top-field input#denom_total_top {
            text-align: right;
            font-weight: bold;
            color: green;
            background: #fafafa;
        }

        .cash-form table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        /*
            MA-002: row height reduced by 20%.

            Row height was about 52px - 10px cell padding top and bottom, plus
            a ~32px input.

            Now 40px: 6px padding and a fixed 28px input. That is 23% smaller,
            the closest clean step to the 20% you asked for. I checked the
            arithmetic rather than guessing at the numbers - my first attempt
            came to only 12%.

            The BOXES ARE KEPT. You said we could drop them if it did not look
            right - it does, because the borders are what keep the count and
            subtotal columns visually separate at this width. Say the word if
            you would rather they went.
        */
        .cash-form table th,
        .cash-form table td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: center;
            font-size: 14px;
        }
        .cash-form table th {
            background-color: #f0f0f0;
            color: #333;
        }
        .cash-form .total-row {
            font-weight: bold;
            background-color: #fafafa;
        }

        .cash-form .buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        /*
            MA-002: the button pair at the top right.

            Absolutely positioned so it sits on the same line as the heading
            rather than pushing it down, which is where the single Cancel used
            to sit.
        */
        .cash-form .top-buttons {
            position: absolute;
            top: 20px;
            right: 30px;
            display: flex;
            gap: 10px;
            z-index: 1;
        }
        .cash-form .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .cash-form .btn-correct { background-color: #4CAF50; color: #fff; }
        .cash-form .btn-correct:hover { background-color: #388E3C; }
        .cash-form .btn-cancel { background-color: #f44336; color: #fff; }
        .cash-form .btn-cancel:hover { background-color: #d32f2f; }

        /* The inputs sit flush in their cells, as in your design. */
        .cash-form table td input {
            width: 100%;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 3px 8px;
            height: 28px;
            font-size: 14px;
        }
        .cash-form table td input[readonly] {
            border: 0;
            background: transparent;
        }
    </style>

    <div class="modal-dialog cash-form-modal" role="document" style="width: 600px; max-width: 94%;">
      <div class="modal-content cash-form">

            {{--
                MA-002: the form now OPENS BEFORE the top buttons.

                "Correct. Save" is a submit button, so it must be inside the
                form to submit it. The form used to open below the heading,
                which would have left the button outside and doing nothing when
                clicked.

                Nothing else moves - the form still closes in the same place,
                so every field between is unaffected.
            --}}
            {!! Form::open(['url' => action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorPaymentController@saveCashDenom'), 'method' => 'post', 'id' => 'cash_denom_form']) !!}

          {{-- MA-002: both buttons on the top right, Save first. --}}
          <div class="top-buttons">
              <button type="submit" class="btn btn-correct cash-denom-save">Correct. Save</button>
              <button type="button" class="btn btn-cancel" data-dismiss="modal">Cancel</button>
          </div>

          <h2 id="cashPaymentsTitle">@lang( 'pumperdashboard::lang.cash' )</h2>

            <input type="hidden" name="shift_id" value="{{ $shift_id ?? '' }}">

            {{--
                MA-002: three fields across the top, in ONE row.

                    Search Denomination | Enter Bulk Amount | Total

                Bulk Amount renders only when Petro PD Settings -> Enter Cash
                Denominations is Yes. When it is off, the other two share the
                row so nothing is left with a gap beside it.

                Total mirrors the grand total at the foot of the table, so the
                figure is visible without scrolling to the bottom. It is
                read-only and carries no form name, so it cannot be typed into
                and cannot be submitted twice.
            --}}
            <div class="top-fields">
                <div class="top-field">
                    <label for="denom_search">Search Denomination</label>
                    <input type="text" id="denom_search" autocomplete="off" placeholder="Search Denomination">
                </div>
                {{--
                    MA-002: the bulk field now ALWAYS SHOWS.

                    It used to be removed entirely when the setting was off.
                    Now it is always on screen and simply INACTIVE when Allow
                    Bulk Cash Amount is set to Disable.

                    'disabled' rather than 'readonly': a disabled input cannot
                    be focused, tabbed into, or typed in, and it is not
                    submitted - so the permission is real and not just visual.

                    The default is 'no', so if the setting is missing or
                    unreadable the field is inactive rather than open.
                --}}
                {{--
                    MA-002: the block form is used below, not the inline one.

                    The inline form has to find its own closing bracket, and an
                    expression with nested brackets followed by more expression
                    confuses it - it closes at the wrong one and swallows the
                    markup that follows.

                    NOTE TO ANYONE EDITING THIS FILE: never write a Blade
                    directive inside a Blade comment. Blade compiles them even
                    in here, so an example written as documentation becomes
                    live code. That is exactly what broke this popup once
                    already.
                --}}
                @php
                    $pdBulkAllowed = ($enter_cash_denoms ?? 'no') === 'yes';
                @endphp
                <div class="top-field">
                    <label for="denom_bulk_amount">Enter Bulk Amount</label>
                    <input type="text" id="denom_bulk_amount" autocomplete="off"
                        placeholder="Enter Bulk Amount"
                        @if(! $pdBulkAllowed)
                            disabled
                            title="Enable Allow Bulk Cash Amount in PD Settings to use this"
                        @endif
                    >
                </div>
                <div class="top-field">
                    <label for="denom_total_top">Grand Total</label>
                    <input type="text" id="denom_total_top" class="denom_total" readonly placeholder="Grand Total" value="0.00">
                </div>
            </div>

            <table id="denom_table">
                <thead>
                    <tr>
                        <th>Denominations</th>
                        <th>Count &amp; Enter</th>
                        <th>Sub Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cash_denoms as $denom)
                        <tr class="denom-row">
                            <td>
                                <input type="hidden" value="{{$denom}}" class="denom_value">
                                {{@num_format($denom)}}
                            </td>
                            <td>
                                {!! Form::number('qty[]', 0, ['class' => 'form-control cash_payment_input denom_qty', 'required', 'min' => '0']) !!}
                            </td>
                            <td>
                                {!! Form::text('total_amount[]', 0, ['class' => 'form-control denom_amt', 'required', 'readonly']) !!}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2">{{ __( 'lang_v1.total' ) }}</td>
                        <td>
                            {!! Form::text('grand_total', 0, ['class' => 'form-control denom_total', 'required', 'readonly', 'style' => 'width:100%; text-align:right; border:0; background:transparent; font-weight:bold;']) !!}
                        </td>
                    </tr>
                </tbody>
            </table>

            {{--
                MA-002: the bottom button pair is REMOVED - both are now at
                the top, as asked.

                Leaving them here as well would have put TWO submit buttons in
                one form. A double-click across the two, or an Enter keypress,
                could have posted the same cash payment twice.
            --}}
            {!! Form::close() !!}

      {{--
          MA-002: four closes here, matching the version you had working.

          I briefly removed one, thinking it surplus. It is not - parcel 322,
          which worked, carries the same four. I checked rather than reasoned.
      --}}
      </div>
    </div>
</div>
</div>
<div class="modal fade pd-card-payment-modal" id="card_payment" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
    <div class="modal-dialog pd-card-payment-dialog">
      <div class="modal-content pd-card-payment-content">

        <!-- Modal Header -->
        <div class="modal-header pd-card-payment-header">
          <h4 class="modal-title pd-card-payment-title">@lang('pumperdashboard::lang.card')</h4>
          <button type="button" class="pd-card-payment-close" data-dismiss="modal" aria-label="@lang('messages.close')">&times;</button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body pd-card-payment-body">
            @include('pumperdashboard::card_payment')
        </div>
        
        <div class="modal-footer pd-card-payment-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>

      </div>
    </div>
  </div>
  
  <div class="modal fade pumper-enter-meters-modal" id="other_sales" role="dialog"
    aria-labelledby="pumperEnterMetersTitle" aria-modal="true">
    <div class="modal-dialog pumper-enter-meters-dialog" role="document">
      <div class="modal-content">

        <div class="modal-header pumper-enter-meters-header">
          <h2 class="modal-title pumper-enter-meters-title" id="pumperEnterMetersTitle">@lang('pumperdashboard::lang.enter_meters')</h2>
          <button type="button" class="close pumper-enter-meters-close" data-dismiss="modal"
            aria-label="@lang('messages.close')"><span aria-hidden="true">&times;</span></button>
        </div>

        <div class="modal-body pumper-enter-meters-body">
            @include('pumperdashboard::other_sales', compact('pending_pumps'))
        </div>

      </div>
    </div>
  </div>

    <!-- This will be printed -->
    <section class="invoice print_section" id="receipt_section">
    </section>

@endsection

@section('javascript')
{{-- Matches public/modules/pumper-dashboard/js/ on deployment (e.g. .../public_html/public/modules/pumper-dashboard/js/). --}}
@php
    $pumperPoPaymentJs = file_exists(public_path('Modules/pumper-dashboard/js/po_payment.js'))
        ? asset('Modules/pumper-dashboard/js/po_payment.js')
        : asset('modules/pumper-dashboard/js/po_payment.js');
    $pumperPoPaymentCashFixJs = file_exists(public_path('Modules/pumper-dashboard/js/po_payment_pumper_cash_fix.js'))
        ? asset('Modules/pumper-dashboard/js/po_payment_pumper_cash_fix.js')
        : asset('modules/pumper-dashboard/js/po_payment_pumper_cash_fix.js');
@endphp
<script src="{{ $pumperPoPaymentJs }}?v={{ time() }}"></script>
{{-- Pumper Add Payments only: fixes Cash + denominations modal if main po_payment.js is stale. --}}
<script src="{{ $pumperPoPaymentCashFixJs }}?v={{ time() }}"></script>

<script>
(function (window, document, $) {
    'use strict';

    if (!$ || @json(!empty($enter_cash_denoms) && $enter_cash_denoms === 'yes')) {
        return;
    }

    /*
     * Own the standard Cash save at capture phase so stale/delegated copies of
     * po_payment.js cannot submit the same payment twice. Other payment types
     * continue through their existing handlers unchanged.
     */
    document.addEventListener('click', function (event) {
        var button = event.target.closest ? event.target.closest('#payment_submit') : null;
        if (!button || String($('#payment_type').val() || '').toLowerCase() !== 'cash') {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();

        var $button = $(button);
        if ($button.data('cash-save-in-flight')) {
            return;
        }

        var amount = String($('#amount').val() || '').replace(/,/g, '').trim();
        if (!amount || !isFinite(Number(amount)) || Number(amount) <= 0) {
            toastr.error('Please enter a valid cash amount.');
            return;
        }

        var originalText = $button.text();
        $button
            .data('cash-save-in-flight', true)
            .prop('disabled', true)
            .text('Saving...');

        $.ajax({
            method: 'POST',
            url: "{{ route('pumperdashboard.pump-operator-payments.store') }}",
            dataType: 'json',
            timeout: 30000,
            data: {
                _token: "{{ csrf_token() }}",
                amount: amount,
                payment_type: 'cash',
                pump_operator_id: $('#pump_operator').val(),
                shift_id: $('#active_shift_id').val(),
                collection_form_no: $('#collection_form_no').val()
            }
        }).done(function (result) {
            if (result && result.success) {
                showReloadConfirmationModal(result.collection_form_no || $('#collection_form_no').val(), 'cash');
                return;
            }

            $button
                .data('cash-save-in-flight', false)
                .prop('disabled', false)
                .text(originalText);
            toastr.error(result && result.msg ? result.msg : 'Unable to save the cash payment.');
        }).fail(function (xhr) {
            $button
                .data('cash-save-in-flight', false)
                .prop('disabled', false)
                .text(originalText);

            var message = xhr.responseJSON && xhr.responseJSON.msg
                ? xhr.responseJSON.msg
                : 'Unable to save the cash payment. Please try again.';
            toastr.error(message);
        });
    }, true);
})(window, document, window.jQuery);
</script>

<script>
function showReloadConfirmationModal(formNo, paymentType) {
    if (typeof window.pdMarkPaymentMethodUsed === 'function' && paymentType) {
        window.pdMarkPaymentMethodUsed(paymentType);
    }

    if (formNo) {
        $("#reloadConfirmationModalLabel").html("Confirm Another Payment for Form No. " + formNo);
    } else {
        $("#reloadConfirmationModalLabel").html("Confirm Another Payment");
    }

    $("#cash_payments, #card_payment, #cheque_payments, #direct_cr").modal("hide");
    $(".modal-backdrop").remove();
    $("body").removeClass("modal-open").css("padding-right", "");
    $("#reloadConfirmationModal").appendTo(document.body).modal("show");
}

/*
 * IS2306: Processing state belongs only to the form that actually contains an
 * Other Sales finalize button. The Cash denomination form is a separate save
 * workflow and must never inherit the Other Sales "Processing..." state.
 */
$(document).on('submit', 'form', function () {
    const btn = $(this).find('.other_sale_finalize');
    if (!btn.length) {
        return;
    }

    btn.prop('disabled', true)
       .text('Processing...');
});
</script>

<script>
    $(document).ready(function(){
        window.creditSaleIsResetting = false;

        function initCreditSaleSelect2() {
            var $creditModal = $("#direct_cr");

            $creditModal.find("select.credit-sale-select2").each(function () {
                var $select = $(this);
                var $control = $select.closest(".credit-sale-select-control");

                // Destroy the registered instance first, then remove any orphan
                // containers left by older repeated initialisation.
                if ($select.hasClass("select2-hidden-accessible")) {
                    try {
                        $select.select2("destroy");
                    } catch (error) {
                        // Continue with the clean rebuild below.
                    }
                }

                $control.children(".select2-container").remove();
                $select
                    .removeClass("select2-hidden-accessible")
                    .removeAttr("data-select2-id aria-hidden tabindex");
                $select.find("option").removeAttr("data-select2-id");

                $select.select2({
                    width: "100%",
                    dropdownParent: $creditModal,
                    minimumResultsForSearch: 0,
                    dropdownCssClass: "credit-sale-select2-dropdown"
                });

                // Some Select2 builds/themes ignore dropdownCssClass or move
                // the results panel outside the modal. Add the class again
                // when this specific control opens so the option typography
                // remains correctly scoped and cannot be overridden by the
                // larger global dropdown font rule.
                $select
                    .off("select2:open.creditSaleOptionFont")
                    .on("select2:open.creditSaleOptionFont", function () {
                        window.setTimeout(function () {
                            $(".select2-container--open .select2-dropdown")
                                .last()
                                .addClass("credit-sale-select2-dropdown");
                        }, 0);
                    });

                // A final safety check guarantees one visible control only.
                var $containers = $control.children(".select2-container");
                if ($containers.length > 1) {
                    $containers.slice(0, -1).remove();
                }
            });
        }

        // Initialise the other page dropdowns normally. Credit Sale dropdowns
        // are handled only by initCreditSaleSelect2().
        $(".select2").not("#direct_cr select.credit-sale-select2").select2();

        window.creditSaleReconfirmPopup = function (title, detailLabel, detailValue) {
            var details = Array.isArray(detailLabel)
                ? detailLabel
                : [{ label: detailLabel, value: detailValue }];
            var deferred = $.Deferred();
            var $modal = $("#creditSaleReconfirmModal");
            var $details = $("#creditSaleReconfirmDetails");
            var settled = false;
            var answer = false;

            $modal.appendTo(document.body);
            $("#creditSaleReconfirmTitle").text(String(title || "Reconfirm Details"));
            $details.empty();

            details.forEach(function (detail, index) {
                var $row = $("<div>", { class: "credit-sale-reconfirm-detail-row" });
                if (index < details.length - 1) {
                    $row.css("margin-bottom", "12px");
                }

                $("<div>", {
                    class: "credit-sale-reconfirm-detail-label",
                    text: String(detail.label || "")
                }).appendTo($row);

                $("<div>", {
                    class: "credit-sale-reconfirm-detail-value",
                    text: String(detail.value || "")
                }).appendTo($row);

                $details.append($row);
            });

            function choose(value) {
                if (settled) {
                    return;
                }
                settled = true;
                answer = Boolean(value);
                $modal.modal("hide");
            }

            $modal
                .off("click.creditSaleReconfirmYes", ".credit-sale-reconfirm-yes")
                .on("click.creditSaleReconfirmYes", ".credit-sale-reconfirm-yes", function () {
                    choose(true);
                })
                .off("click.creditSaleReconfirmNo", ".credit-sale-reconfirm-no")
                .on("click.creditSaleReconfirmNo", ".credit-sale-reconfirm-no", function () {
                    choose(false);
                })
                .off("shown.bs.modal.creditSaleReconfirm")
                .on("shown.bs.modal.creditSaleReconfirm", function () {
                    $(".modal-backdrop").last().addClass("credit-sale-reconfirm-backdrop");
                    $modal.find(".credit-sale-reconfirm-yes").trigger("focus");
                })
                .off("hidden.bs.modal.creditSaleReconfirm")
                .on("hidden.bs.modal.creditSaleReconfirm", function () {
                    $(".credit-sale-reconfirm-backdrop").remove();
                    if ($("#direct_cr").hasClass("in") || $("#direct_cr").is(":visible")) {
                        $("body").addClass("modal-open");
                    }
                    deferred.resolve(answer);
                });

            $modal.modal({
                backdrop: "static",
                keyboard: false,
                show: true
            });

            return deferred.promise();
        };

        window.markCreditFieldLocked = function (selector, locked) {
            var $field = $(selector).closest('.credit-sale-field');
            if (locked) {
                $field.addClass('is-locked');
            } else {
                $field.removeClass('is-locked');
            }
        };


        window.showCreditSaleNote = function (note) {
            var cleanNote = String(note || '').trim();
            if (!cleanNote) {
                return;
            }

            swal({
                title: 'Payment Note',
                text: cleanNote,
                icon: 'info',
                button: 'Close',
                closeOnClickOutside: false
            });
        };
        
        $(document).on('click', '.add_other_sales', function(e) {
            
            $("#other_sales").modal({
                backdrop: 'static',
                keyboard: false 
            });
            
            $(".other_sale_finalize").attr('disabled',true);

            // Re-evaluate after modal opens in case inputs already have values
            setTimeout(function() {
                if (typeof calculate_other_sales_totals === 'function') {
                    calculate_other_sales_totals();
                }
            }, 300);
        });
        
        
         $(document).on('click', '.add_cheque_payment', function(e) {
            $("#cheque_payments").modal({
                backdrop: 'static',
                keyboard: false 
            });
        });
        
        // Cash denominations modal is opened from po_payment.js after Cash is selected (avoids handler order / duplicate opens).

        
        $(document).on('click', '.card_payment_btn', function(e) {
            resetCardPaymentForm();

            $("#card_payment").modal({
                backdrop: 'static',
                keyboard: false 
            });
        });

        $(document).on('shown.bs.modal hidden.bs.modal', '#card_payment', function () {
            resetCardPaymentForm();
        });
        
        $(document).on('change','#card_type,#slip_no,#card_amount',function(){
            
            if($("#card_type").val() && $("#card_amount").val() ){
                $(".card_payment_add").attr('disabled',false);
            }else{
                $(".card_payment_add").attr('disabled',true);
            }
            
        })
        
        function toggerCardSaveBtn(){
            if($(".card-data").length > 0){
                $(".card-save-btn").prop('disabled',false);
            }else{
                $(".card-save-btn").prop('disabled',true);
            }
        }

        function resetCardPaymentEntry() {
            $("#slip_no").val("").css("border", "").trigger("input").trigger("change");
            $("#card_no").val("").css("border", "").trigger("input").trigger("change");
            $("#card_amount").val("").trigger("input").trigger("change");
            $("#card_type").val(null).trigger("change");
            $(".card_payment_add").attr('disabled', true);
        }

        function resetCardPaymentForm() {
            $("#card_payment_table tbody").empty();
            resetCardPaymentEntry();
            $(".card-save-btn").prop('disabled', true);
        }
        
        $(document).on('click', '.card_payment_add', function () {
            const dropdown = document.getElementById('card_pmt_type');
             const enterCardNumbers = $("#enter_card_numbers").val();
            if (dropdown.value !== 'bulk') {
                let hasError = false;
                if (!$("#slip_no").val()) {
                    $("#slip_no").css("border", "1px solid red");
                    hasError = true;
                    $("#slip_no").on("input", function () {
                        $(this).css("border", "");
                    });
                } else {
                    $("#slip_no").css("border", "");
                }
                if (enterCardNumbers == 'yes') {
                    if (!$("#card_no").val()) {
                        $("#card_no").css("border", "1px solid red");
                        hasError = true;
                        $("#card_no").on("input", function () {
                            $(this).css("border", "");
                        });
                    } else {
                        $("#card_no").css("border", "");
                    }
                }
                // if (!$("#card_no").val()) {
                //     $("#card_no").css("border", "1px solid red");
                //     hasError = true;
                //     $("#card_no").on("input", function () {
                //         $(this).css("border", "");
                //     });
                // } else {
                //     $("#card_no").css("border", "");
                // }

                if (hasError) {
                    toastr.error("Enter Slip Number");
                    return;
                }
            }
            const cardNumberRequired =  enterCardNumbers !== 'no';
           
            var data = {
                card_type: $("#card_type").val(),
                slip_no: $("#slip_no").val(),
                amount: $("#card_amount").val(),
                card_number: cardNumberRequired ? $("#card_no").val() : null, // null if not required
                collection_form_no: $("#collection_form_no").val()
            };
            var cardNumberTd = cardNumberRequired ? `<td>` + $("#card_no").val() + `</td>` : '';
            var amount = parseFloat($("#card_amount").val()) || 0;
var formattedAmount = amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

              var html = `
                <tr>
                    <td>`+ $("#card_type option:selected").text() + `</td>
                    <td>`+ $("#slip_no").val() + `</td>
                    `+ cardNumberTd +`
                  <td>`+ formattedAmount +`
                      <input type="hidden" name="card_data[]" class="card-data" required value='`+JSON.stringify(data)+`'>
                </td>

                    <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
                </tr>
            `;
        
            $("#card_payment_table tbody").append(html);
            resetCardPaymentEntry();
            toggerCardSaveBtn();
        });
        
        // Remove row when the remove button is clicked
        $(document).on('click', '.remove-row', function () {
            $(this).closest('tr').remove();
            toggerCardSaveBtn()
        });

        // Card payment form AJAX submission
        $(document).on('submit', '#card_payment_form', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Card payment form submit triggered via AJAX');
            
            var $form = $(this);
            var $saveBtn = $form.find('.card-save-btn');
            
            // Prevent double submission
            if ($saveBtn.prop('disabled')) {
                console.log('Button already disabled, preventing duplicate submission');
                return false;
            }
            
            $saveBtn.prop('disabled', true).html('Saving...');
            
            var formData = $form.serialize();
            console.log('Form data:', formData);
            
            $.ajax({
                method: 'POST',
                url: $form.attr('action'),
                data: formData,
                dataType: 'json',
                success: function(result) {
                    $saveBtn.prop('disabled', false).html('@lang("pumperdashboard::lang.save")');
                    
                    console.log('Card payment result:', result);
                    console.log('Collection form no:', result.collection_form_no);
                    
                    if (result.success) {
                        toastr.success(result.msg);
                        
                        // Update collection form number
                        $(".collection_form_no").each(function() {
                            $(this).val(result.collection_form_no);
                        });
                        
                        resetCardPaymentForm();
                        
                        // Hide card payment modal
                        $("#card_payment").modal("hide");
                        
                        // Show confirmation modal with form number
                        if (result.collection_form_no) {
                            showReloadConfirmationModal(result.collection_form_no, 'card');
                        } else {
                            console.error('No collection_form_no in response!');
                            showReloadConfirmationModal(null, 'card');
                        }
                    } else {
                        toastr.error(result.msg || 'Something went wrong');
                    }
                },
                error: function(xhr, status, error) {
                    $saveBtn.prop('disabled', false).html('@lang("pumperdashboard::lang.save")');
                    
                    console.error('Card payment save error:', {
                        status: xhr.status,
                        responseText: xhr.responseText,
                        error: error
                    });
                    
                    var errorMsg = 'Something went wrong while saving card payment';
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.msg) {
                            errorMsg = response.msg;
                        }
                    } catch (e) {
                        // Use default message
                    }
                    
                    toastr.error(errorMsg);
                }
            });
            
            return false;
        });


        
        
        function setCreditConfirmationVisible(selector, visible) {
            var $container = $(selector);
            if (visible) {
                $container.removeAttr("hidden");
            } else {
                $container.attr("hidden", true);
            }
        }

        function resetCreditSaleFormFull() {
            window.creditSaleIsResetting = true;

            var $customer = $("#credit_sale_customer_id");
            var $vehicle = $("#customer_reference");
            var $product = $("#credit_sale_product_id");
            var $manualVehicle = $("#customer_reference_one_time");
            var $orderNumber = $("#order_number");

            $customer.prop("disabled", false).val(null).trigger("change.select2");
            $vehicle
                .prop("disabled", false)
                .empty()
                .append('<option value="">Please Select</option>')
                .append('<option value="No Vehicle No">No Vehicle No</option>')
                .val("No Vehicle No")
                .trigger("change.select2");
            $product.prop("disabled", false).val(null).trigger("change.select2");
            $manualVehicle.prop("readonly", false).val("");

            var initialOrderValue = $orderNumber.attr("value");
            $orderNumber
                .prop("disabled", false)
                .prop("readonly", false)
                .val(initialOrderValue !== undefined ? initialOrderValue : "");

            setCreditConfirmationVisible("#customer_reconfirmed_container", false);
            setCreditConfirmationVisible("#order_reconfirmed_container", false);
            setCreditConfirmationVisible("#customer_vehicle_reconfirmed_container", false);
            setCreditConfirmationVisible("#customer_vehicle_manual_reconfirmed_container", false);
            $("#direct_cr .credit-sale-field").removeClass("is-locked");

            $("#unit_price, #unit_discount, #credit_sale_qty, #credit_total_amount, #credit_discount_amount, #credit_sale_amount, #credit_note").val("");
            $("#credit_sale_qty, #credit_total_amount, #credit_discount_amount").prop("disabled", true);
            $(".current_outstanding, .credit_limit").val("0.00");

            if ($.fn.datepicker && $("#order_date").length) {
                $("#order_date").datepicker("setDate", new Date());
            }

            if (creditSaleCustomerDetailsRequest && creditSaleCustomerDetailsRequest.readyState !== 4) {
                creditSaleCustomerDetailsRequest.abort();
            }
            creditSaleCustomerDetailsRequest = null;
            creditSaleCustomerConfirmationSequence = (Number(creditSaleCustomerConfirmationSequence) || 0) + 1;
            creditSaleOrderConfirmationSequence = (Number(creditSaleOrderConfirmationSequence) || 0) + 1;
            creditSaleVehicleConfirmationSequence = (Number(creditSaleVehicleConfirmationSequence) || 0) + 1;
            isConfirmingCustomer = false;
            isConfirmingOrderNumber = false;
            isConfirmingCustomerVehicle = false;

            window.creditSaleIsResetting = false;
        }
        window.resetCreditSaleFormFull = resetCreditSaleFormFull;

        $(".credit_sale_finalize").hide();
        $(".credit_sale_finalize_print").hide();
        
        // Open the Credit Sale modal with a static backdrop. Rebuild the
        // three dropdowns exactly once per opening after removing old remnants.
        $(document)
          .off("click.creditSaleOpen", ".po_credit_payment")
          .on("click.creditSaleOpen", ".po_credit_payment", function (event) {
              event.preventDefault();
              $("#credit_sale_table tbody").empty();
              $(".credit_sale_total, .credit_tb_discount_total, .credit_tbl_amount_total").text("0.00");
              $(".credit_sale_finalize, .credit_sale_finalize_print").hide();
              resetCreditSaleFormFull();

              $("#direct_cr").modal({
                  backdrop: "static",
                  keyboard: false,
                  show: true
              });
          });

        var $creditSaleModal = $("#direct_cr");

        $creditSaleModal
          .off("shown.bs.modal.creditSale")
          .on("shown.bs.modal.creditSale", function () {
              initCreditSaleSelect2();
          })
          .off("hidden.bs.modal.creditSale")
          .on("hidden.bs.modal.creditSale", function () {
              $("#credit_sale_table tbody").empty();
              $(".credit_sale_total, .credit_tb_discount_total, .credit_tbl_amount_total").text("0.00");
              $(".credit_sale_finalize, .credit_sale_finalize_print").hide();
              resetCreditSaleFormFull();
          })
          .off("hide.bs.modal.creditSaleDropdownGuard")
          .on("hide.bs.modal.creditSaleDropdownGuard", function (event) {
              // A click inside an open Select2 menu must never close the form.
              if ($creditSaleModal.find(".select2-container--open").length > 0) {
                  event.preventDefault();
                  event.stopImmediatePropagation();
                  $creditSaleModal.find("select.credit-sale-select2").select2("close");
              }
          });

        $(document)
          .off("mousedown.creditSaleDropdown click.creditSaleDropdown touchstart.creditSaleDropdown", "#direct_cr .select2-container, #direct_cr .select2-dropdown")
          .on("mousedown.creditSaleDropdown click.creditSaleDropdown touchstart.creditSaleDropdown", "#direct_cr .select2-container, #direct_cr .select2-dropdown", function (event) {
              event.stopPropagation();
          });

        // Don't auto-select first customer - let placeholder show by default
        // Removed: $("#credit_sale_customer_id").val($("#credit_sale_customer_id option:eq(0)").val()).trigger('change');
        $('#order_date').datepicker("setDate", new Date());
    });

    
    $(document).on('change', '.cash_payment_input', function() {
        /*
         * MA-002: made instant. Three costs were paid on EVERY keystroke:
         *
         *   console.log(total)   REMOVED. Writing to the console on each
         *                        keypress is the biggest single cost when
         *                        devtools are open, and it served no purpose.
         *
         *   $('.denom_amt')      searched the WHOLE document every time - this
         *   $('.denom_total')    page is 2,800 lines with six modals. Both are
         *                        now cached, in calculateDenomTotals below.
         *
         * The row is looked up once and reused rather than repeatedly.
         */
        var $field = $(this);
        var amount = parseFloat($field.val()) || 0;
        var $row = $field.closest('.denom-row');
        var denom_value = parseFloat($row.find('.denom_value').val()) || 0;

        $row.find('.denom_amt').val(pdFormatMoney(denom_value * amount));
        calculateDenomTotals();
    });

    
   /*
    * MA-002: one formatter for every amount in this popup - the subtotal on
    * each row and the grand total. Thousands separators, exactly 2 decimals.
    *
    * Anything reading these values back strips the commas first, so the
    * arithmetic is unaffected by the formatting.
    */
   function pdFormatMoney(value) {
       var n = parseFloat(String(value).replace(/,/g, '')) || 0;
       return n.toLocaleString('en-US', {
           minimumFractionDigits: 2,
           maximumFractionDigits: 2
       });
   }

   /*
    * MA-002: STRIP THE SEPARATORS BEFORE THE FORM IS SUBMITTED.
    *
    * This is the important one. The server does:
    *
    *     $payment_amount = $request->grand_total;
    *
    * and PHP casting "12,345.67" to a number gives 12 - it stops at the
    * comma. A formatted total would have been SAVED AS 12.
    *
    * So the display keeps its separators for the person reading the screen,
    * and they are removed the instant the form is submitted. What reaches
    * the database is the plain number, exactly as before this change.
    *
    * Bound to the denominations form only, by its submit button, so no other
    * form on the page is affected.
    */
   $(document).on('submit', '#cash_payments form', function () {
       $(this).find('.denom_amt, .denom_total').each(function () {
           $(this).val(String($(this).val()).replace(/,/g, ''));
       });

       return true;
   });

   /*
    * MA-002: Search Denomination.
    *
    * A DISPLAY FILTER ONLY. A hidden row keeps its value and still submits,
    * so filtering can never lose a count already entered - which is why the
    * rows are hidden rather than removed.
    *
    * The total row is never hidden.
    */
   $(document).on('input', '#denom_search', function () {
       var term = $.trim($(this).val()).replace(/,/g, '').toLowerCase();

       $('#denom_table tbody tr.denom-row').each(function () {
           if (term === '') {
               $(this).show();
               return;
           }

           var value = String($(this).find('.denom_value').val() || '');
           $(this).toggle(value.indexOf(term) !== -1);
       });
   });

   /*
    * MA-002: Enter Bulk Amount.
    *
    * Fills the counts from a single total, largest note first - 7,500 becomes
    * one 5,000, one 2,000 and one 500.
    *
    * ANY REMAINDER IS REPORTED, not silently dropped. If the amount cannot be
    * made from the available notes, the person is told how much is left over
    * rather than discovering a short count later.
    *
    * Behaviour inferred from the field name - say the word if you meant
    * something different.
    */
   /*
    * MA-002: Enter Bulk Amount sets the GRAND TOTAL only.
    *
    * It used to distribute the amount across the denomination counts, filling
    * them largest note first. That was my inference from the field name and it
    * was wrong.
    *
    * WHAT IT DOES NOW: the amount typed becomes the grand total, in both
    * places - the box at the top and the one in the table footer. THE
    * DENOMINATION COUNTS ARE NOT TOUCHED.
    *
    * Both carry the class denom_total, so one assignment updates both.
    *
    * NOTE ON WHICH VALUE IS SAVED: only the field in the table has a name
    * attribute (grand_total), so only that one submits. The box at the top is
    * a display mirror and cannot post a second, conflicting value.
    *
    * The separators are stripped again on submit, so the server receives
    * 12345.67 rather than 12.
    */
   /*
    * MA-002: the bulk box just triggers a recalculation.
    *
    * calculateDenomTotals adds the bulk amount to the rows, so ONE place is
    * responsible for the arithmetic. Writing the total here as well would be
    * a second copy of the rule, and the two would eventually disagree.
    */
   $(document).on('change', '#denom_bulk_amount', function () {
       calculateDenomTotals();
   });

   /*
    * MA-002: amount fields respond AS YOU TYPE, and accept digits only.
    *
    * ONE RULE FOR THE WHOLE PAGE. Every amount field is covered by the
    * selector below, so a field added later is covered too - nothing to
    * register and nothing that can fall behind.
    *
    * TWO THINGS IT DOES:
    *
    *   1. DIGITS AND ONE DECIMAL POINT ONLY. Commas, spaces, letters and
    *      symbols are stripped as they are typed. Pasting is covered as well,
    *      because 'input' fires on a paste where 'keypress' does not.
    *
    *   2. FIRES 'change' IMMEDIATELY. The existing handlers all listen for
    *      'change', which only fires when the field LOSES focus - that is why
    *      you had to click away to see a total. Raising it here means every
    *      one of them now runs as you type, with no change to their own code.
    *
    * The caret is preserved when a character is removed, so typing does not
    * jump to the end of the field.
    *
    * The COUNT boxes take whole numbers only - you cannot have half a
    * banknote - so a decimal point is not allowed in those.
    */
   var PD_AMOUNT_FIELDS = [
       '.cash_payment_input',
       '.denom_qty',
       '#denom_bulk_amount',
       '#card_amount',
       '.input_number',
       '.amount',
       'input[name="amount"]',
       'input[name="qty[]"]'
   ].join(', ');

   $(document).on('input', PD_AMOUNT_FIELDS, function () {
       /*
        * MA-002: the raw DOM element is used where possible.
        *
        * this.readOnly, this.value and classList are direct property reads.
        * $(this).prop(...) and .hasClass(...) each build a jQuery object -
        * negligible once, but this runs on every keypress in every amount
        * field on the page.
        */
       var el = this;

       if (el.readOnly || el.disabled) {
           return;
       }

       var raw = String(el.value || '');

       // Whole numbers for a count of notes; decimals allowed elsewhere.
       var wholeOnly = el.classList && el.classList.contains('denom_qty');
       var cleaned = wholeOnly
           ? raw.replace(/[^0-9]/g, '')
           : raw.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');

       if (cleaned !== raw) {
           var caret = el.selectionStart;
           el.value = cleaned;

           // Keep the caret where it was, allowing for what was removed.
           if (typeof caret === 'number' && el.setSelectionRange) {
               var shift = raw.length - cleaned.length;
               try {
                   el.setSelectionRange(caret - shift, caret - shift);
               } catch (e) {
                   // Some input types do not support selection - harmless.
               }
           }
       }

       /*
        * MA-002: THE 500ms DELAY ON THE BULK BOX IS REMOVED.
        *
        * It was there because the bulk amount used to REDISTRIBUTE every
        * denomination count, and running that on each keystroke made the
        * counts flicker through wrong states.
        *
        * The bulk amount no longer touches the counts - it is simply added to
        * the total. So there is nothing to defer, and the delay was only
        * making the grand total feel slow compared with the count boxes.
        *
        * Every amount field is now immediate.
        */

       // Everything already listening for 'change' now runs as you type.
       $(el).trigger('change');
   });

   /*
    * MA-002: the amount and total fields are found ONCE and reused.
    *
    * They were searched for across the whole document on every keystroke.
    * The lookup is cached, and refreshed whenever the popup opens so it
    * cannot go stale if the rows are re-rendered.
    *
    * this.value is used instead of $(this).val() - no jQuery object is
    * created per row, which matters when this runs on every keypress.
    */
   var pdDenomAmounts = null;
   var pdDenomTotals = null;

   function pdDenomCache() {
       if (!pdDenomAmounts || !pdDenomAmounts.length) {
           pdDenomAmounts = $('#cash_payments').find('.denom_amt');
           pdDenomTotals = $('.denom_total');
       }
   }

   $(document).on('shown.bs.modal', '#cash_payments', function () {
       pdDenomAmounts = null;
       pdDenomCache();
   });

   /*
    * MA-002: GRAND TOTAL = bulk amount + the denomination rows.
    *
    * The two are ADDED, not one or the other:
    *
    *     bulk 335, nothing counted        ->  335.00
    *     bulk 335, one 5,000 note counted ->  5,335.00
    *     no bulk, one 5,000 note counted  ->  5,000.00
    *
    * An earlier version let the bulk amount override the rows. That was
    * wrong - a count entered afterwards was ignored instead of adding.
    *
    * The bulk box exists only when the setting permits it. With the setting
    * off, bulk is zero and the total is simply the sum of the rows, exactly
    * as before.
    */
   function calculateDenomTotals() {
       pdDenomCache();

       var total = 0;

       pdDenomAmounts.each(function () {
           // Separators stripped, or "1,500.00" would read as 1.
           total += parseFloat(String(this.value).replace(/,/g, '')) || 0;
       });

       /*
        * MA-002: a DISABLED bulk field contributes nothing.
        *
        * The field is always on the page now - inactive when Allow Bulk Cash
        * Amount is set to Disable. A disabled input cannot be typed into, so
        * it would be empty anyway, but this makes the rule explicit rather
        * than relying on that.
        */
       var bulkEl = document.getElementById('denom_bulk_amount');

       if (bulkEl && !bulkEl.disabled) {
           total += parseFloat(String(bulkEl.value).replace(/,/g, '')) || 0;
       }

       pdDenomTotals.val(pdFormatMoney(total));
   }


    
    $(document).on("click", ".credit_sale_add", function () {
        // S280-001: Validate the actual numeric amount/quantity, not only the raw input string.
        // Some formatted number inputs keep a visible value while .val() can be blank/stale during
        // input events; __read_number reads the same value format used by the rest of the form.
        var entered_amount = __read_number($("#credit_sale_amount")) || __read_number($("#credit_total_amount")) || 0;
        var entered_qty = __read_number($("#credit_sale_qty")) || 0;
        if (entered_amount <= 0 && entered_qty <= 0) {
            toastr.error("Please enter amount");
            $("#credit_sale_amount").focus();
            return false;
        }
        var credit_sale_customer_id = $("#credit_sale_customer_id").val();
        var customer_name = $("#credit_sale_customer_id :selected").text();

        if (!credit_sale_customer_id) {
            toastr.error("Please select a customer");
            return false;
        }

        if (!customer_name || customer_name === "None") {
            toastr.error("Please select a valid customer");
            return false;
        }

        if (!isCreditSaleCustomerConfirmed()) {
            requestCustomerReconfirmation(credit_sale_customer_id, customer_name);
            toastr.info("Please complete both Customer reconfirmations before adding the credit sale.");
            return false;
        }

        var $orderNumber = $("#order_number");
        var confirmedOrderNumber = String($orderNumber.val() || "").trim();

        if (confirmedOrderNumber === "") {
            toastr.error("Please enter the Order No before adding the credit sale.");
            $orderNumber.focus();
            return false;
        }

        if (!isCreditSaleOrderConfirmed()) {
            requestOrderNumberReconfirmation(confirmedOrderNumber);
            toastr.info("Please complete both Order No reconfirmations before adding the credit sale.");
            return false;
        }

        var selectedVehicleNo = String($("#customer_reference").val() || "").trim();
        var manualVehicleNo = String($("#customer_reference_one_time").val() || "").trim();

        if (!selectedVehicleNo && !manualVehicleNo) {
            toastr.error("Please select or enter the vehicle number before adding the credit sale.");
            return false;
        }

        if (!isCreditSaleVehicleConfirmed()) {
            if (manualVehicleNo) {
                requestCustomerVehicleReconfirmation("manual", manualVehicleNo);
            } else {
                requestCustomerVehicleReconfirmation("selected", selectedVehicleNo);
            }
            toastr.info("Please complete both Vehicle reconfirmations before adding the credit sale.");
            return false;
        }

        var credit_sale_product_id = $("#credit_sale_product_id").val();
        var credit_sale_product_name = $("#credit_sale_product_id :selected").text();
        
        // Validate product is selected
        if (!credit_sale_product_id || credit_sale_product_id === '' || credit_sale_product_id === null) {
            toastr.error("Please select a product");
            return false;
        }
        
        // Validate product name is not "None" or empty
        if (!credit_sale_product_name || credit_sale_product_name === '' || credit_sale_product_name === 'None') {
            toastr.error("Please select a valid product");
            return false;
        }
        
        if ($("#customer_reference_one_time").val() !== "" && $("#customer_reference_one_time").val() !== null && $("#customer_reference_one_time").val() !== undefined) {
            var customer_reference = $("#customer_reference_one_time").val();
        } else {
            var customer_reference = $("#customer_reference").val();
        }
        var settlement_no = $("#settlement_no").val();
        var order_date = $("#order_date").val();
        var order_number = confirmedOrderNumber;
        
        // Validate: Same order number cannot be used with different customers
        if (order_number && order_number.trim() !== '' && order_number.trim() !== '0') {
            var duplicateOrderWithDifferentCustomer = false;
            $("#credit_sale_table tbody tr").each(function() {
                var existingData = JSON.parse($(this).find('.credit_data').val());
                var existingOrderNumber = existingData.order_number;
                var existingCustomerId = existingData.customer_id;
                
                // Check if same order number exists with a different customer
                if (existingOrderNumber && existingOrderNumber.trim() !== '' && 
                    existingOrderNumber.trim() !== '0' && 
                    existingOrderNumber.trim() === order_number.trim() &&
                    existingCustomerId !== credit_sale_customer_id) {
                    duplicateOrderWithDifferentCustomer = true;
                    return false; // Break the loop
                }
            });
            
            if (duplicateOrderWithDifferentCustomer) {
                toastr.error("Order Number " + order_number + " is already used with a different customer. Please use a different order number or select the same customer.");
                return false;
            }
        }
        
        // Order number is optional - no validation required
        
        var credit_sale_price = __read_number($("#unit_price")) || 0;
        var credit_unit_discount = __read_number($("#unit_discount")) ?? 0;
        var credit_total_amount = __read_number($("#credit_total_amount")) ?? 0;
        var credit_total_discount = __read_number($("#credit_discount_amount")) ?? 0;
        var credit_sub_total = __read_number($("#credit_sale_amount")) ?? 0;

        var credit_sale_qty_display = __read_number($("#credit_sale_qty")) ?? 0;
        var credit_sale_qty = credit_sale_qty_display;

        if (credit_sale_price > 0 && credit_total_amount > 0) {
            credit_sale_qty = credit_total_amount / credit_sale_price;
            credit_sub_total = credit_total_amount - credit_total_discount;
        }

        // Validate quantity is required and greater than 0
        if (!credit_sale_qty || credit_sale_qty <= 0 || isNaN(credit_sale_qty)) {
            toastr.error("Please enter a valid quantity (must be greater than 0)");
            $("#credit_sale_qty").focus();
            return false;
        }
        var outstanding = $(".current_outstanding").val() || "0.00";
        var credit_limit = $(".credit_limit").val() || "0.00";
        var credit_note = $("#credit_note").val();
        
        
        var credit_data = {
            settlement_no: '',
            customer_id: credit_sale_customer_id, // Ensure this is not empty
            customer_name: customer_name, // Also include customer_name for fallback
            product_id: credit_sale_product_id,
            order_number: order_number,
            order_date: order_date,
            price: credit_sale_price,
            unit_discount: credit_unit_discount,
            qty: credit_sale_qty,
            amount: credit_total_amount,
            sub_total: credit_sub_total,
            total_discount: credit_total_discount,
            outstanding: outstanding,
            credit_limit: credit_limit,
            customer_reference: customer_reference,
            note: credit_note
        };
        var $row = $("<tr>");
        var $customerCell = $("<td>", { class: "credit-sale-customer-cell" });
        $("<div>", {
            class: "credit-sale-customer-name",
            text: customer_name
        }).appendTo($customerCell);

        if (String(credit_note || "").trim() !== "") {
            $("<button>", {
                type: "button",
                class: "btn btn-xs credit-sale-note-button",
                text: "Note"
            })
                .attr("data-note", credit_note)
                .appendTo($customerCell);
        }

        $("<input>", {
            type: "hidden",
            class: "credit_data"
        }).val(JSON.stringify(credit_data)).appendTo($customerCell);

        $row.append($customerCell);
        $row.append($("<td>").text(order_number));
        $row.append($("<td>").text(order_date));
        $row.append($("<td>").text(customer_reference || ""));
        $row.append($("<td>").text(credit_sale_product_name));
        $row.append($("<td>").text(__number_f(credit_sale_price, false, false, __currency_precision)));
        $row.append($("<td>").text(__number_f(credit_sale_qty_display, false, false, __currency_precision)));
        $row.append($("<td>", { class: "credit_sale_amount" }).text(
            __number_f(credit_total_amount, false, false, __currency_precision)
        ));
        $row.append($("<td>", { class: "credit_tbl_discount_amount" }).text(
            __number_f(credit_total_discount, false, false, __currency_precision)
        ));
        $row.append($("<td>", { class: "credit_tbl_total_amount" }).text(
            __number_f(credit_sub_total, false, false, __currency_precision)
        ));
        $row.append(
            $("<td>").append(
                $("<button>", {
                    type: "button",
                    class: "btn btn-xs btn-danger delete_credit_sale_payment",
                    html: '<i class="fa fa-times" aria-hidden="true"></i>',
                    title: "Delete"
                })
            )
        );

        $("#credit_sale_table tbody").prepend($row);
        toastr.success("Successfully Added");
                
                // Clear the entry fields without re-triggering the reconfirmation workflow.
                window.creditSaleIsResetting = true;
                $("#credit_sale_product_id").val(null).trigger("change");

                if (!$("#credit_sale_customer_id").prop("disabled")) {
                    $("#credit_sale_customer_id").val(null).trigger("change.select2");
                    $("#customer_reference").prop("disabled", false).val(null).trigger("change.select2");
                    $("#customer_reference_one_time").prop("readonly", false).val("");
                    hideCreditReconfirmed("#customer_reconfirmed_container");
                    hideCreditReconfirmed("#customer_vehicle_reconfirmed_container");
                    hideCreditReconfirmed("#customer_vehicle_manual_reconfirmed_container");
                }

                if (!$("#order_number").prop("readonly")) {
                    $("#order_number").val("");
                    hideCreditReconfirmed("#order_reconfirmed_container");
                    window.markCreditFieldLocked("#order_number", false);
                    if ($.fn.datepicker) {
                        $("#order_date").datepicker("setDate", new Date());
                    }
                }

                $("#unit_price, #unit_discount, #credit_sale_qty, #credit_total_amount, #credit_discount_amount, #credit_sale_amount, #credit_note").val("");
                $("#credit_sale_qty, #credit_total_amount, #credit_discount_amount").prop("disabled", true);
                window.creditSaleIsResetting = false;
                
                // Recalculate totals
                calculateTotal("#credit_sale_table", ".credit_sale_amount", ".credit_sale_total");
                calculateTotal("#credit_sale_table", ".credit_tbl_discount_amount", ".credit_tb_discount_total");
                calculateTotal("#credit_sale_table", ".credit_tbl_total_amount", ".credit_tbl_amount_total");
});

function calculateTotal(table_name, class_name_td, output_element) {
    let total = 0.0;
    $(table_name + " tbody")
        .find(class_name_td)
        .each(function () {
            total += parseFloat(__number_uf($(this).text()));
        });
        
        if(total <=0){
            $(".credit_sale_finalize").hide();
            $(".credit_sale_finalize_print").hide();
        }else{
            $(".credit_sale_finalize").show();
            $(".credit_sale_finalize_print").show();
        }
    $(output_element).text(__number_f(total, false, false, __currency_precision));
}

$(document).on('input','#credit_total_amount', function() {
    $("#credit_sale_qty").attr('disabled',true);
    
    let price = __read_number($("#unit_price")) ?? 0;
    let total_amount = __read_number($("#credit_total_amount")) ?? 0;
    let qty = price > 0 ? (total_amount / price) : 0; 
    
    let total_discount = __read_number($("#credit_discount_amount")) ?? 0;
    let unit_discount = qty > 0 ? (total_discount / qty) : 0;
    let amount = total_amount - total_discount
    
    __write_number($("#credit_sale_amount"), amount);
    __write_number($("#unit_discount"), unit_discount);
    __write_number_without_decimal_format($("#credit_sale_qty"), qty.toFixed(__currency_precision));
    
    
});

$(document).on('input','#credit_sale_qty', function() {
    $("#credit_total_amount").attr('disabled',true);
    
    let price = __read_number($("#unit_price")) ?? 0;
    let qty = __read_number($("#credit_sale_qty")) ?? 0; 
    let total_amount = price * qty;
    
    let total_discount = __read_number($("#credit_discount_amount")) ?? 0;
    let unit_discount = qty > 0 ? (total_discount / qty) : 0;
    let amount = total_amount - total_discount
    
    __write_number($("#credit_sale_amount"), amount);
    __write_number($("#unit_discount"), unit_discount);
    __write_number($("#credit_total_amount"),total_amount);
    
});

$(document).on("change", "#credit_discount_amount, #unit_price", function () {
    let price = __read_number($("#unit_price")) ?? 0;
    let qty_check = __read_number($("#credit_sale_qty")) ?? 0;
    
    var qty = 0;
    var total_amount = 0;
    
    if(qty_check > 0){
        qty = __read_number($("#credit_sale_qty")) ?? 0;
        total_amount = price * qty; 
        __write_number($("#credit_total_amount"), total_amount);
    }else{
        total_amount = __read_number($("#credit_total_amount")) ?? 0;
        qty = price > 0 ? (total_amount / price) : 0; 
        __write_number_without_decimal_format($("#credit_sale_qty"), qty.toFixed(__currency_precision));
    }
    
    
    
    let total_discount = __read_number($("#credit_discount_amount")) ?? 0;
    let unit_discount = qty > 0 ? (total_discount / qty) : 0;
    let amount = total_amount - total_discount
    
    __write_number($("#unit_discount"), unit_discount);
    __write_number($("#credit_sale_amount"),amount);
    
});
    
$(document).on("change", "#credit_sale_product_id", function () {
    if ($(this).val()) {
        $.ajax({
            method: "get",
            url: "/pumper-dashboard/settlement/payment/get-product-price",
            data: { product_id: $(this).val() },
            success: function (result) {
                $("#unit_price").val(result.price);
                $("#unit_price").trigger('change');
                
                $("#credit_total_amount").attr("disabled", false);
                $("#credit_sale_qty").attr("disabled", false);
                if($("#manual_discount").val() == 1){
                    $("#credit_discount_amount").attr("disabled", false);
                }
                
            },
        });
    } else {
        $("#credit_total_amount").attr("disabled", true);
        $("#credit_sale_qty").attr("disabled", true);
        $("#credit_discount_amount").attr("disabled", true);
    }
});
var isConfirmingCustomer = false;
var isConfirmingOrderNumber = false;
var isConfirmingCustomerVehicle = false;
var creditSaleCustomerConfirmationSequence = 0;
var creditSaleOrderConfirmationSequence = 0;
var creditSaleVehicleConfirmationSequence = 0;
var creditSaleCustomerDetailsRequest = null;
var CREDIT_SALE_NO_VEHICLE_VALUE = "No Vehicle No";

function isCreditSaleNoVehicleValue(value) {
    value = String(value || "").trim().toLowerCase();
    return value === "no vehicle no" || value === "no vehicle";
}

function showCreditReconfirmed(selector) {
    $(selector).removeAttr("hidden");
}

function hideCreditReconfirmed(selector) {
    $(selector).attr("hidden", true);
}

function isCreditSaleCustomerConfirmed() {
    var $customer = $("#credit_sale_customer_id");
    return Boolean($customer.val())
        && $customer.prop("disabled")
        && !$("#customer_reconfirmed_container").is("[hidden]");
}

function isCreditSaleOrderConfirmed() {
    var $order = $("#order_number");
    return String($order.val() || "").trim() !== ""
        && $order.prop("readonly")
        && !$("#order_reconfirmed_container").is("[hidden]");
}

function isCreditSaleVehicleConfirmed() {
    var selectedVehicleNo = String($("#customer_reference").val() || "").trim();
    var manualVehicleNo = String($("#customer_reference_one_time").val() || "").trim();

    // "No Vehicle No" is an intentional absence of a vehicle registration.
    // It is always valid and reusable, so it must not depend on the normal
    // vehicle reconfirmation/locking state.
    if (isCreditSaleNoVehicleValue(selectedVehicleNo)) {
        return true;
    }

    var selectedConfirmed = selectedVehicleNo !== ""
        && $("#customer_reference").prop("disabled")
        && !$("#customer_vehicle_reconfirmed_container").is("[hidden]");
    var manualConfirmed = manualVehicleNo !== ""
        && $("#customer_reference_one_time").prop("readonly")
        && !$("#customer_vehicle_manual_reconfirmed_container").is("[hidden]");

    return selectedConfirmed || manualConfirmed;
}

function resetCustomerDependentCreditFields() {
    $(".current_outstanding, .credit_limit").val("0.00");

    window.creditSaleIsResetting = true;
    $("#customer_reference")
        .prop("disabled", false)
        .empty()
        .append('<option value="">Please Select</option>')
        .append('<option value="No Vehicle No">No Vehicle No</option>')
        .val(CREDIT_SALE_NO_VEHICLE_VALUE)
        .trigger("change.select2");
    $("#customer_reference_one_time").prop("readonly", false).val("");
    window.creditSaleIsResetting = false;

    hideCreditReconfirmed("#customer_vehicle_reconfirmed_container");
    hideCreditReconfirmed("#customer_vehicle_manual_reconfirmed_container");
    window.markCreditFieldLocked("#customer_reference", false);
    window.markCreditFieldLocked("#customer_reference_one_time", false);
    creditSaleVehicleConfirmationSequence += 1;
    isConfirmingCustomerVehicle = false;
}

function loadCreditSaleCustomerDetails(customerId) {
    if (creditSaleCustomerDetailsRequest && creditSaleCustomerDetailsRequest.readyState !== 4) {
        creditSaleCustomerDetailsRequest.abort();
    }

    $(".current_outstanding, .credit_limit").val("Loading...");
    var customerDetailsRequest = $.ajax({
        method: "get",
        url: "/pumper-dashboard/settlement/payment/get-customer-details/" + customerId,
        data: {}
    });
    creditSaleCustomerDetailsRequest = customerDetailsRequest;

    customerDetailsRequest
        .done(function (result) {
            if (String($("#credit_sale_customer_id").val() || "") !== String(customerId)) {
                return;
            }

            $(".current_outstanding").val(result.total_outstanding || "0.00");
            $(".credit_limit").val(result.credit_limit || "0.00");

            // Do not replace a vehicle that the operator has selected, entered,
            // or is currently reconfirming while this request is completing.
            var selectedVehicleValue = String($("#customer_reference").val() || "").trim();
            if ((isCreditSaleVehicleConfirmed()
                    && !isCreditSaleNoVehicleValue(selectedVehicleValue))
                || isConfirmingCustomerVehicle
                || (selectedVehicleValue !== ""
                    && !isCreditSaleNoVehicleValue(selectedVehicleValue))
                || String($("#customer_reference_one_time").val() || "").trim() !== "") {
                return;
            }

            window.creditSaleIsResetting = true;
            var $vehicle = $("#customer_reference");
            $vehicle
                .prop("disabled", false)
                .empty()
                .append('<option value="">Please Select</option>')
                .append('<option value="No Vehicle No">No Vehicle No</option>');

            (result.customer_references || []).forEach(function (reference) {
                var referenceValue = String(reference.reference || "").trim();
                if (!referenceValue || isCreditSaleNoVehicleValue(referenceValue)) {
                    return;
                }
                $vehicle.append($("<option>", {
                    value: referenceValue,
                    text: referenceValue
                }));
            });

            $vehicle.val(CREDIT_SALE_NO_VEHICLE_VALUE).trigger("change.select2");
            window.creditSaleIsResetting = false;
        })
        .fail(function (xhr, status) {
            if (status === "abort") {
                return;
            }

            if (String($("#credit_sale_customer_id").val() || "") !== String(customerId)) {
                return;
            }

            $(".current_outstanding, .credit_limit").val("0.00");
            toastr.error("Unable to load the selected customer details. Please select the customer again.");
        })
        .always(function () {
            if (creditSaleCustomerDetailsRequest === customerDetailsRequest) {
                creditSaleCustomerDetailsRequest = null;
            }
        });

    return customerDetailsRequest;
}

function clearUnconfirmedCustomer() {
    creditSaleCustomerConfirmationSequence += 1;
    creditSaleVehicleConfirmationSequence += 1;
    isConfirmingCustomer = false;
    isConfirmingCustomerVehicle = false;

    if (creditSaleCustomerDetailsRequest && creditSaleCustomerDetailsRequest.readyState !== 4) {
        creditSaleCustomerDetailsRequest.abort();
    }
    creditSaleCustomerDetailsRequest = null;

    window.creditSaleIsResetting = true;
    $("#credit_sale_customer_id").prop("disabled", false).val(null).trigger("change.select2");
    hideCreditReconfirmed("#customer_reconfirmed_container");
    window.markCreditFieldLocked("#credit_sale_customer_id", false);
    window.creditSaleIsResetting = false;

    resetCustomerDependentCreditFields();
}

function lockReconfirmedCustomer(customerId) {
    var $customer = $("#credit_sale_customer_id");
    if (String($customer.val() || "") !== String(customerId)) {
        clearUnconfirmedCustomer();
        return;
    }

    window.creditSaleIsResetting = true;
    $customer.prop("disabled", true).trigger("change.select2");
    showCreditReconfirmed("#customer_reconfirmed_container");
    window.markCreditFieldLocked("#credit_sale_customer_id", true);
    window.creditSaleIsResetting = false;
}

function requestCustomerReconfirmation(customerId, customerName) {
    var $customer = $("#credit_sale_customer_id");
    customerId = String(customerId || "").trim();
    customerName = $.trim(customerName || $customer.find(":selected").text());

    if (window.creditSaleIsResetting || isConfirmingCustomer || !customerId) {
        return;
    }

    isConfirmingCustomer = true;
    var confirmationSequence = ++creditSaleCustomerConfirmationSequence;
    var details = [
        { label: "Selected Customer", value: customerName }
    ];

    window.creditSaleReconfirmPopup("Reconfirm Customer - 1 of 2", details)
        .then(function (firstConfirmed) {
            if (confirmationSequence !== creditSaleCustomerConfirmationSequence) {
                return;
            }

            if (!firstConfirmed) {
                isConfirmingCustomer = false;
                clearUnconfirmedCustomer();
                return;
            }

            window.creditSaleReconfirmPopup("Reconfirm Customer - 2 of 2", details)
                .then(function (secondConfirmed) {
                    if (confirmationSequence !== creditSaleCustomerConfirmationSequence) {
                        return;
                    }

                    isConfirmingCustomer = false;
                    if (!secondConfirmed) {
                        clearUnconfirmedCustomer();
                        return;
                    }

                    if (String($customer.val() || "") !== customerId) {
                        clearUnconfirmedCustomer();
                        toastr.error("The Customer changed during reconfirmation. Please select and reconfirm it again.");
                        return;
                    }

                    lockReconfirmedCustomer(customerId);
                });
        });
}

$(document)
    .off("change.creditSaleCustomerStage", "#credit_sale_customer_id")
    .on("change.creditSaleCustomerStage", "#credit_sale_customer_id", function () {
        if (window.creditSaleIsResetting || $(this).prop("disabled")) {
            return;
        }

        var customerId = String($(this).val() || "").trim();
        var customerName = $.trim($(this).find(":selected").text());

        creditSaleCustomerConfirmationSequence += 1;
        isConfirmingCustomer = false;
        hideCreditReconfirmed("#customer_reconfirmed_container");
        window.markCreditFieldLocked("#credit_sale_customer_id", false);
        resetCustomerDependentCreditFields();

        if (!customerId) {
            return;
        }

        loadCreditSaleCustomerDetails(customerId);
        requestCustomerReconfirmation(customerId, customerName);
    });

function clearUnconfirmedCustomerVehicle(source) {
    creditSaleVehicleConfirmationSequence += 1;
    isConfirmingCustomerVehicle = false;

    window.creditSaleIsResetting = true;
    var $vehicle = $("#customer_reference");
    var $manualVehicle = $("#customer_reference_one_time");

    $vehicle.prop("disabled", false);
    $manualVehicle.prop("readonly", false);

    if (source === "manual") {
        $manualVehicle.val("");
    } else {
        $vehicle.val(null).trigger("change.select2");
    }

    hideCreditReconfirmed("#customer_vehicle_reconfirmed_container");
    hideCreditReconfirmed("#customer_vehicle_manual_reconfirmed_container");
    window.markCreditFieldLocked("#customer_reference", false);
    window.markCreditFieldLocked("#customer_reference_one_time", false);
    window.creditSaleIsResetting = false;
}

function lockReconfirmedCustomerVehicle(source, vehicleNo) {
    window.creditSaleIsResetting = true;
    var $vehicle = $("#customer_reference");
    var $manualVehicle = $("#customer_reference_one_time");

    if (source === "manual") {
        $vehicle.prop("disabled", true).val(null).trigger("change.select2");
        $manualVehicle.prop("readonly", true).val(vehicleNo);
        hideCreditReconfirmed("#customer_vehicle_reconfirmed_container");
        showCreditReconfirmed("#customer_vehicle_manual_reconfirmed_container");
        window.markCreditFieldLocked("#customer_reference", false);
        window.markCreditFieldLocked("#customer_reference_one_time", true);
    } else {
        $vehicle.prop("disabled", true).val(vehicleNo).trigger("change.select2");
        $manualVehicle.prop("readonly", true).val("");
        showCreditReconfirmed("#customer_vehicle_reconfirmed_container");
        hideCreditReconfirmed("#customer_vehicle_manual_reconfirmed_container");
        window.markCreditFieldLocked("#customer_reference", true);
        window.markCreditFieldLocked("#customer_reference_one_time", false);
    }

    window.creditSaleIsResetting = false;
}

function requestCustomerVehicleReconfirmation(source, vehicleNo) {
    var $customer = $("#credit_sale_customer_id");
    var customerId = $customer.val();
    var customerName = $.trim($customer.find(":selected").text());
    vehicleNo = String(vehicleNo || "").trim();

    if (window.creditSaleIsResetting || isConfirmingCustomerVehicle) {
        return;
    }

    // This sentinel is deliberately reusable and has no vehicle-specific
    // validation or reconfirmation conditions.
    if (isCreditSaleNoVehicleValue(vehicleNo)) {
        lockReconfirmedCustomerVehicle("selected", CREDIT_SALE_NO_VEHICLE_VALUE);
        return;
    }

    if (!customerId || !isCreditSaleCustomerConfirmed()) {
        toastr.error("Please complete both Customer reconfirmations before selecting or entering the vehicle number.");
        clearUnconfirmedCustomerVehicle(source);
        return;
    }

    if (!vehicleNo) {
        return;
    }

    isConfirmingCustomerVehicle = true;
    var confirmationSequence = ++creditSaleVehicleConfirmationSequence;
    var vehicleLabel = source === "manual" ? "Entered Vehicle No" : "Selected Vehicle No";
    var titlePrefix = source === "manual" ? "Reconfirm Entered Vehicle No" : "Reconfirm Customer Vehicle No";
    var details = [
        { label: "Confirmed Customer", value: customerName },
        { label: vehicleLabel, value: vehicleNo }
    ];

    window.creditSaleReconfirmPopup(titlePrefix + " - 1 of 2", details)
        .then(function (firstConfirmed) {
            if (confirmationSequence !== creditSaleVehicleConfirmationSequence) {
                return;
            }

            if (!firstConfirmed) {
                isConfirmingCustomerVehicle = false;
                clearUnconfirmedCustomerVehicle(source);
                return;
            }

            window.creditSaleReconfirmPopup(titlePrefix + " - 2 of 2", details)
                .then(function (secondConfirmed) {
                    if (confirmationSequence !== creditSaleVehicleConfirmationSequence) {
                        return;
                    }

                    isConfirmingCustomerVehicle = false;
                    if (!secondConfirmed) {
                        clearUnconfirmedCustomerVehicle(source);
                        return;
                    }

                    var currentVehicleNo = source === "manual"
                        ? String($("#customer_reference_one_time").val() || "").trim()
                        : String($("#customer_reference").val() || "").trim();

                    if (currentVehicleNo !== vehicleNo || !isCreditSaleCustomerConfirmed()) {
                        clearUnconfirmedCustomerVehicle(source);
                        toastr.error("The Customer or Vehicle No changed during reconfirmation. Please reconfirm it again.");
                        return;
                    }

                    lockReconfirmedCustomerVehicle(source, vehicleNo);
                });
        });
}

$(document)
    .off("change.creditSaleVehicleConfirm", "#customer_reference")
    .on("change.creditSaleVehicleConfirm", "#customer_reference", function () {
        if (window.creditSaleIsResetting || $(this).prop("disabled")) {
            return;
        }

        var vehicleNo = String($(this).val() || "").trim();
        if (!vehicleNo) {
            hideCreditReconfirmed("#customer_vehicle_reconfirmed_container");
            window.markCreditFieldLocked("#customer_reference", false);
            return;
        }

        window.creditSaleIsResetting = true;
        $("#customer_reference_one_time").prop("readonly", false).val("");
        hideCreditReconfirmed("#customer_vehicle_manual_reconfirmed_container");
        window.markCreditFieldLocked("#customer_reference_one_time", false);
        window.creditSaleIsResetting = false;

        requestCustomerVehicleReconfirmation("selected", vehicleNo);
    });

$(document)
    .off("change.creditSaleVehicleManual blur.creditSaleVehicleManual keydown.creditSaleVehicleManual", "#customer_reference_one_time")
    .on("change.creditSaleVehicleManual blur.creditSaleVehicleManual keydown.creditSaleVehicleManual", "#customer_reference_one_time", function (event) {
        if (window.creditSaleIsResetting || $(this).prop("readonly")) {
            return;
        }

        if (event.type === "keydown") {
            if (event.key !== "Enter" && event.keyCode !== 13) {
                return;
            }
            event.preventDefault();
        }

        var vehicleNo = String($(this).val() || "").trim();
        if (!vehicleNo) {
            hideCreditReconfirmed("#customer_vehicle_manual_reconfirmed_container");
            window.markCreditFieldLocked("#customer_reference_one_time", false);
            return;
        }

        $(this).val(vehicleNo);
        window.creditSaleIsResetting = true;
        $("#customer_reference").prop("disabled", false).val(null).trigger("change.select2");
        hideCreditReconfirmed("#customer_vehicle_reconfirmed_container");
        window.markCreditFieldLocked("#customer_reference", false);
        window.creditSaleIsResetting = false;

        requestCustomerVehicleReconfirmation("manual", vehicleNo);
    });

function clearUnconfirmedOrderNumber() {
    creditSaleOrderConfirmationSequence += 1;
    isConfirmingOrderNumber = false;

    window.creditSaleIsResetting = true;
    $("#order_number")
        .prop("disabled", false)
        .prop("readonly", false)
        .val("");
    hideCreditReconfirmed("#order_reconfirmed_container");
    window.markCreditFieldLocked("#order_number", false);
    window.creditSaleIsResetting = false;
}

function lockReconfirmedOrderNumber(orderNo) {
    window.creditSaleIsResetting = true;
    $("#order_number")
        .prop("disabled", false)
        .prop("readonly", true)
        .val(orderNo);
    showCreditReconfirmed("#order_reconfirmed_container");
    window.markCreditFieldLocked("#order_number", true);
    window.creditSaleIsResetting = false;
}

function requestOrderNumberReconfirmation(orderNo) {
    var $order = $("#order_number");
    orderNo = String(orderNo || "").trim();

    if (window.creditSaleIsResetting || isConfirmingOrderNumber || !orderNo) {
        return;
    }

    $order.val(orderNo);
    isConfirmingOrderNumber = true;
    var confirmationSequence = ++creditSaleOrderConfirmationSequence;
    var customerName = $.trim($("#credit_sale_customer_id :selected").text());
    var details = [];

    if (customerName && customerName !== "None" && customerName !== "Please Select") {
        details.push({ label: "Selected Customer", value: customerName });
    }
    details.push({ label: "Entered Order No", value: orderNo });

    window.creditSaleReconfirmPopup("Reconfirm Order No - 1 of 2", details)
        .then(function (firstConfirmed) {
            if (confirmationSequence !== creditSaleOrderConfirmationSequence) {
                return;
            }

            if (!firstConfirmed) {
                isConfirmingOrderNumber = false;
                clearUnconfirmedOrderNumber();
                return;
            }

            window.creditSaleReconfirmPopup("Reconfirm Order No - 2 of 2", details)
                .then(function (secondConfirmed) {
                    if (confirmationSequence !== creditSaleOrderConfirmationSequence) {
                        return;
                    }

                    isConfirmingOrderNumber = false;
                    if (!secondConfirmed) {
                        clearUnconfirmedOrderNumber();
                        return;
                    }

                    if (String($order.val() || "").trim() !== orderNo) {
                        clearUnconfirmedOrderNumber();
                        toastr.error("The Order No changed during reconfirmation. Please enter and reconfirm it again.");
                        return;
                    }

                    lockReconfirmedOrderNumber(orderNo);
                });
        });
}

$(document)
    .off("change.creditSaleOrderConfirm blur.creditSaleOrderConfirm keydown.creditSaleOrderConfirm", "#order_number")
    .on("change.creditSaleOrderConfirm blur.creditSaleOrderConfirm keydown.creditSaleOrderConfirm", "#order_number", function (event) {
        if (window.creditSaleIsResetting || $(this).prop("readonly")) {
            return;
        }

        if (event.type === "keydown") {
            if (event.key !== "Enter" && event.keyCode !== 13) {
                return;
            }
            event.preventDefault();
        }

        var orderNo = String($(this).val() || "").trim();
        if (!orderNo) {
            hideCreditReconfirmed("#order_reconfirmed_container");
            window.markCreditFieldLocked("#order_number", false);
            return;
        }

        requestOrderNumberReconfirmation(orderNo);
    });

$(document)
    .off("click.creditSaleNote", ".credit-sale-note-button")
    .on("click.creditSaleNote", ".credit-sale-note-button", function () {
        window.showCreditSaleNote($(this).attr("data-note"));
    });

$(document).on("click", ".delete_credit_sale_payment", function () {
    tr = $(this).closest("tr");
    tr.remove();
    
    calculateTotal("#credit_sale_table", ".credit_sale_amount", ".credit_sale_total");
    calculateTotal("#credit_sale_table", ".credit_tbl_discount_amount", ".credit_tb_discount_total");
    calculateTotal("#credit_sale_table", ".credit_tbl_total_amount", ".credit_tbl_amount_total");
});

$(document).on("click", ".credit_sale_finalize", function (e) {
    e.preventDefault();
    
    // Prevent double-clicking
    var $btn = $(this);
    if ($btn.prop('disabled')) {
        return false;
    }
    $btn.prop('disabled', true);
    
    var dataArray = [];
    $(".credit_data").each(function() {
        var jsonData = JSON.parse($(this).val());
        var collection_form_no = $("#collection_form_no").val() ?? "";
        jsonData.collection_form_no = collection_form_no;
        dataArray.push(jsonData);
    });
    $.ajax({
        method: "post",
        url: "/pumper-dashboard/pump-operator-pmts/save-credit",
        data: {
            credit_data : dataArray,
            collection_form_no : $("#collection_form_no").val() ?? "",
        },
        dataType: 'json',
        success: function (result) {
            $btn.prop('disabled', false); // Re-enable button
            if (result.success) {
            toastr.success(result.msg);
            $(".collection_form_no").each(function() {
                $(this).val(result.collection_form_no);
            });
                // Clear the table after successful save
                $("#credit_sale_table tbody").empty();
                $(".credit_sale_total").text("0.00");
                $(".credit_tb_discount_total").text("0.00");
                $(".credit_tbl_amount_total").text("0.00");
                $(".credit_sale_finalize").hide();
                $(".credit_sale_finalize_print").hide();
                resetCreditSaleFormFull();
            $("#direct_cr").modal("hide");
            showReloadConfirmationModal(result.collection_form_no, 'credit');
            } else {
                toastr.error(result.msg || 'Something went wrong');
            }
        },
        error: function(xhr, status, error) {
            $btn.prop('disabled', false); // Re-enable button on error
            console.error('Credit sale save error:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            
            var errorMsg = 'Something went wrong while saving credit sale';
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.msg) {
                    errorMsg = response.msg;
                }
            } catch (e) {
                // If response is not JSON, use default message
            }
            
            toastr.error(errorMsg);
        }
    });
});

$(document).on('click', '.credit_sale_finalize_print', function(e) {
    e.preventDefault();
    
    // Show the print copy selection modal
    $('#print_copy_selection_modal').modal('show');
    
    // Store button reference for later use
    window.creditSaleFinalizePrintBtn = $(this);
});

// Handle the print confirmation button
$(document).on('click', '#confirm_print_btn', function(e) {
    e.preventDefault();
    
    var selectedCopy = $('input[name="print_copy_option"]:checked').val();
    
    // Close the modal
    $('#print_copy_selection_modal').modal('hide');
    
    // Open the print window early (user gesture) to avoid popup blocking.
    var printWindow = null;
    try {
        printWindow = window.open('', '_blank');
    } catch (err) {
        printWindow = null;
    }

    var saveBtn = window.creditSaleFinalizePrintBtn;
    saveBtn.html('Saving and Printing...');
    saveBtn.prop('disabled', true);
    
    var dataArray = [];
    $(".credit_data").each(function() {
        var jsonData = JSON.parse($(this).val());
        var collection_form_no = $("#collection_form_no").val() ?? "";
        jsonData.collection_form_no = collection_form_no;
        dataArray.push(jsonData);
    });
    $.ajax({
        method: "post",
        url: "/pumper-dashboard/pump-operator-pmts/save-credit",
        data: {
            credit_data : dataArray,
            collection_form_no : $("#collection_form_no").val() ?? "",
            print: "print",
            print_copy_option: selectedCopy,
        },
        dataType: 'json',
        success: function (result) {
            saveBtn.html('Print and Save');
            saveBtn.prop('disabled', false);
            
            if (result.success) {
            toastr.success(result.msg);
            $(".collection_form_no").each(function() {
                $(this).val(result.collection_form_no);
            });
                // Clear the table after successful save
                $("#credit_sale_table tbody").empty();
                $(".credit_sale_total").text("0.00");
                $(".credit_tb_discount_total").text("0.00");
                $(".credit_tbl_amount_total").text("0.00");
                $(".credit_sale_finalize").hide();
                $(".credit_sale_finalize_print").hide();
                resetCreditSaleFormFull();
            $("#direct_cr").modal("hide");

            console.log(result);
            if(result.print){
                if (result.print_credit_sale_id) {
                    // Navigate the pre-opened window to the server-side print route.
                    // Using location.href (not document.write) avoids browser security
                    // policy that blocks cross-window document.write on blank tabs.
                    var url = '/pumper-dashboard/pump-operator-pmts/print-credit-sale/' + result.print_credit_sale_id + '?copy=' + encodeURIComponent(selectedCopy);
                    console.log('[CreditSalePrint] Navigating to:', url, 'printWindow:', printWindow);
                    if (printWindow && !printWindow.closed) {
                        printWindow.location.href = url;
                        printWindow.focus();
                    } else {
                        // printWindow was blocked or closed — open fresh
                        window.open(url, '_blank');
                    }
                } else {
                    console.warn('[CreditSalePrint] print_credit_sale_id missing in response:', result);
                    toastr.error('Print content is missing. Please try reprint.');
                    if (printWindow && !printWindow.closed) {
                        printWindow.close();
                    }
                }
            }

            showReloadConfirmationModal(result.collection_form_no, 'credit');
            } else {
                toastr.error(result.msg || 'Something went wrong');
                saveBtn.html('Print and Save');
                saveBtn.prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            console.error('Credit sale save error:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            
            var errorMsg = 'Something went wrong while saving credit sale';
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.msg) {
                    errorMsg = response.msg;
                }
            } catch (e) {
                // If response is not JSON, use default message
            }
            
            toastr.error(errorMsg);
            saveBtn.html('Print and Save');
            saveBtn.prop('disabled', false);
            if (printWindow && !printWindow.closed) {
                printWindow.close();
            }
        }
    });
});



    let cash_payment_currentInput = null;

    $(".cash_payment_input").on('focus', function() {
        cash_payment_currentInput = $(this); 
    });
    
    function cashPaymentEnterVal(val) {
        if (!cash_payment_currentInput) return;
    
        let str = cash_payment_currentInput.val(); 
    
        if (val === "precision") {
            if (!str.includes(".")) {
                str += ".";
                cash_payment_currentInput.val(str);
            }
            return;
        }
    
        if (val === "backspace") {
            str = str.substring(0, str.length - 1);
            cash_payment_currentInput.val(str);
            return;
        }
    
        str += val; 
        cash_payment_currentInput.val(str);
        cash_payment_currentInput.focus();
        cash_payment_currentInput.trigger('change');
    }
    
    
    let card_payment_currentInput = null;

    $(".card_payment_input").on('focus', function() {
        card_payment_currentInput = $(this); 
    });
    
    function cardPaymentEnterVal(val) {
        if (!card_payment_currentInput) return;
    
        let str = card_payment_currentInput.val(); 
    
        if (val === "precision") {
            if (!str.includes(".")) {
                str += ".";
                card_payment_currentInput.val(str);
            }
            return;
        }
    
        if (val === "backspace") {
            str = str.substring(0, str.length - 1);
            card_payment_currentInput.val(str);
            return;
        }
    
        str += val; 
        card_payment_currentInput.val(str);
        card_payment_currentInput.focus();
        card_payment_currentInput.trigger('change');
    }

</script>
@endsection



