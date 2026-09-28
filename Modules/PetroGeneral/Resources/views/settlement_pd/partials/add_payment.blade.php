@php
$business_id = session()->get('user.business_id');
$business_details = App\Business::find($business_id);
$currency_precision = !empty($business_details->currency_precision) ? $business_details->currency_precision : 2;
$quantity_precision = !empty($business_details->quantity_precision) ? $business_details->quantity_precision : 2;
// S366 urgent fix: pre-compute finalize button visibility before the modal header renders.
$total_paid = !empty($total_paid) ? (float) $total_paid : 0;
$total_amount = !empty($total_amount) ? (float) $total_amount : 0;
$total_balance = round(($total_amount - $total_paid), $currency_precision);
$finalize_zero_tolerance = 1 / pow(10, max((int) $currency_precision, 0));
$can_finalize_settlement = abs((float) $total_balance) < $finalize_zero_tolerance;
@endphp

{{-- Fix swal z-index so it appears above Bootstrap modals --}}
<style>
    .swal-overlay {
        z-index: 99999 !important;
    }

    .swal-modal {
        z-index: 100000 !important;
    }
</style>

<div class="modal-dialog" role="document" style="width: 85%;">

    <div class="modal-content">

        {!! Form::open(['url' => action('\Modules\PetroGeneral\Http\Controllers\SettlementPDController@store'), 'method' =>
        'post', 'id' =>'settlement_form' ]) !!}



        <div class="modal-header">

            {{--

            @ModifiedBy Afes oktavianus

            @DateBy 31-05-2021

            @Task 3350

            --}}



            <h4 class="modal-title pull-left" style="padding-right: 25px">@lang( 'petrogeneral::lang.add_payment' )</h4>

            @if (!isset($provider) && $provider != 'SET_SW')

            <h4 class="modal-title pull-left" style="padding-right: 25px">@lang( 'petrogeneral::lang.settlement_no' ):
                {{$settlement->settlement_no}}</h4>

            <h4 class="modal-title pull-left" style="padding-right: 25px">Shift No : {{$show_shift_no}}</h4>



            <h4 class="modal-title">@lang( 'petrogeneral::lang.date' ): {{$settlement->transaction_date}}</h4>

            @endif

            <div class="pull-right">
                <button type="button" class="btn btn-danger" data-dismiss="modal">@lang('petrogeneral::lang.back')</button>
                <button
                    data-href="{{ action('\\Modules\\PetroGeneral\\Http\\Controllers\\AddPaymentController@preview', [$settlement->id]) }}"
                    class="btn-modal btn btn-success" id="payment_review_btn" data-container=".preview_settlement"
                    style="margin-left: 5px;">
                    @lang("petrogeneral::lang.preview")
                </button>
                <button type="button" id="settlement_save_btn"
                    class="btn btn-primary @if(empty($can_finalize_settlement)) hide @endif" style="margin-left: 5px;">
                    Finalize Settlement
                </button>
            </div>

        </div>



        <div class="modal-body">

            <div class="col-md-12">

                <div class="row">

                    @if (!isset($provider) && $provider != 'SET_SW')

                    <div class="col-md-2 text-center">

                        <b>@lang('petrogeneral::lang.pump_operator')</b> <br>

                        {{isset($pump_operator->name) ? $pump_operator->name : ''}}

                    </div>

                    @endif

                    <div class="col-md-2 text-center">

                        <b>@lang('petrogeneral::lang.current_short')</b> <br>



                        {{@num_format($operator_bal > 0 ? abs($operator_bal) : 0) }}

                    </div>

                    <div class="col-md-2 text-center">

                        <b>@lang('petrogeneral::lang.current_excess')</b> <br>



                        {{@num_format($operator_bal < 0 ? abs($operator_bal) : 0) }} </div>

                            <div class="col-md-2 text-center">

                                <b>@lang('petrogeneral::lang.daily_collections')</b> <br>

                                {{@num_format($total_daily_collection)}}

                            </div>

                            <div class="col-md-2 text-center">

                                <b>@lang('petrogeneral::lang.daily_vouchers')</b> <br>

                                {{@num_format(0)}}

                            </div>

                            <div class="col-md-2 text-center">

                                <b>@lang('petrogeneral::lang.commision_ammount')</b> <br>

                                {{isset($pump_operator->total_commision) ? @num_format($pump_operator->total_commision)
                                : 0 }}

                            </div>

                    </div>

                    @php


                    $total_paid = !empty($total_paid) ? $total_paid : 0;
                    // S366 urgent fix: the UI displays balance using currency precision.
                    // Therefore the Finalize Settlement button must also use the rounded
                    // displayed balance, not a hidden raw float such as 0.004999.
                    $total_balance = round(((float) $total_amount - (float) $total_paid), $currency_precision);
                    $finalize_zero_tolerance = 1 / pow(10, max((int) $currency_precision, 0));
                    $can_finalize_settlement = abs((float) $total_balance) < $finalize_zero_tolerance;

                    @endphp

                    <br><br>
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <!-- Show Balance to Operator button only if balance is > 0 -->
                            <button type="button" id="balance_to_operator_btn"
                                class="btn @if(!empty($can_finalize_settlement)) hide @endif"
                                style="background-color: purple; color: white;">
                                Balance to Operator
                            </button>
                        </div>
                    </div>
                    <br><br>

                    <div class="row">

                        <div class="col-md-3 text-center text-red">

                            <b>@lang('petrogeneral::lang.total_amount'): </b>

                            <span class="total_amount">{{@num_format($total_amount)}}</span>

                        </div>

                        <div class="col-md-3 text-center text-red">

                            <b>@lang('petrogeneral::lang.total_paid'): </b>

                            <span class="total_paid">{{@num_format($total_paid)}}</span>

                        </div>



                        <div class="col-md-3 text-center text-red">

                            <b>@lang('petrogeneral::lang.balance'): </b>



                            <span class="total_balance">{{@num_format($total_balance)}}</span>

                        </div>

                        <div class="col-md-3 text-center text-red">

                        </div>




                        <!-- <div class="col-md-3 text-center text-red"> -->

                        <!--<button type="button" id="settlement_save_btn" style="margin-left: 45px;"-->

                        <!--   class="btn btn-primary pull-left @if(!empty($total_balance) && $total_balance == 0) hide @endif">@lang('messages.save')</button>-->

                        <!-- <button type="button" id="settlement_save_btn" style="margin-left: 45px;"

                     class="btn btn-primary @if( $total_balance != 0 ) {{ 'hide' }} @endif pull-left">@lang('messages.save')</button>

                     <button data-href="{{action('\Modules\PetroGeneral\Http\Controllers\AddPaymentController@preview', [$settlement->id])}}" class="btn-modal btn btn-success pull-right" id="payment_review_btn" data-container=".preview_settlement"> @lang("petrogeneral::lang.preview")</button>

               </div> -->

                        <div class="col-md-3 text-center text-red">
                        </div>


                    </div>

                </div>

                <input type="hidden" name="settlement_id" value="{{$settlement->settlement_no}}">
                <input type="hidden" name="payment_settlement_id" id="payment_settlement_id" value="{{$settlement->id}}">
                <!-- FIX: Added id='settlement_no' for JavaScript save handler - was missing, causing save to fail -->
                <input type="hidden" name="settlement_no" id="settlement_no" value="{{$settlement->settlement_no}}">

                <input type="hidden" name="total_balance" id="total_balance"
                    value="{{ number_format((float) $total_balance, $currency_precision, '.', '') }}">

                <input type="hidden" name="total_amount" id="total_amount"
                    value="{{!empty($total_amount)? $total_amount : 0 }}">

                <input type="hidden" name="total_paid" id="total_paid"
                    value="{{!empty($total_paid)? $total_paid : 0 }}">

                <br><br>

                <div class="clearfix"></div>

                <div style="margin-top: 20px;">

                    @include('petrogeneral::settlement.partials.payment_tabs')

                </div>



                <div class="clearfix"></div>

                {!! Form::close() !!}

                <div class="modal fade contact_modal" tabindex="-1" role="dialog"
                    aria-labelledby="gridSystemModalLabel">

                </div>





            </div><!-- /.modal-content -->

        </div><!-- /.modal-dialog -->

        <script>
            // Define precision variables globally for this script block to avoid hoisting/initialization issues
            var __currency_precision = {{ $currency_precision ?? 2 }};
            var __quantity_precision = {{ $quantity_precision ?? 2 }};

            $(document).ready(function () {
                // Call show_hide_excess_shortage_tab on modal open
                $(document).off('shown.bs.modal', '.add_payment').on('shown.bs.modal', '.add_payment', function () {
                    // Immediately call to set correct tab visibility
                    show_hide_excess_shortage_tab();
                    // Also call after a small delay to ensure DOM is fully ready
                    setTimeout(function () {
                        show_hide_excess_shortage_tab();
                    }, 100);
                });

                // Also call on modal shown to handle page refresh scenarios
                $('.add_payment').on('shown.bs.modal', function () {
                    show_hide_excess_shortage_tab();
                });

                function openSettlementFinalizePrintWindow() {
                    var printWindow = null;

                    try {
                        printWindow = window.open('', '_blank');
                    } catch (e) {
                        printWindow = null;
                    }

                    if (printWindow) {
                        printWindow.document.open();
                        printWindow.document.write(
                            '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Settlement Print</title></head>' +
                            '<body style="font-family: Arial, sans-serif; padding: 24px;">Preparing print preview...</body></html>'
                        );
                        printWindow.document.close();
                        printWindow.focus();
                    }

                    return printWindow;
                }

                function writeSettlementFinalizePrint(printWindow, printContent, fallbackUrl) {
                    var hasContent = printContent && typeof printContent === 'string' && printContent.trim().length > 0;

                    if (!printWindow || printWindow.closed) {
                        if (fallbackUrl) {
                            window.open(fallbackUrl, '_blank');
                        } else {
                            toastr.warning('Please allow popups to print the settlement');
                        }
                        return;
                    }

                    if (!hasContent) {
                        if (fallbackUrl) {
                            printWindow.onload = function () {
                                setTimeout(function () {
                                    printWindow.focus();
                                    printWindow.print();
                                }, 700);
                            };
                            printWindow.location.href = fallbackUrl;
                        } else {
                            printWindow.close();
                            toastr.warning('Print content is missing. Please try reprint.');
                        }
                        return;
                    }

                    var printableHtml = /<html[\s>]/i.test(printContent)
                        ? printContent
                        : '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Settlement Print</title>' +
                            '<style>@media print { body { margin: 0; } }</style></head><body>' +
                            printContent +
                            '</body></html>';

                    printWindow.document.open();
                    printWindow.document.write(printableHtml);
                    printWindow.document.close();
                    printWindow.focus();

                    var runPrint = function () {
                        if (printWindow && !printWindow.closed) {
                            printWindow.focus();
                            printWindow.print();
                        }
                    };

                    if (printWindow.document.readyState === 'complete') {
                        setTimeout(runPrint, 700);
                    } else {
                        printWindow.onload = function () {
                            setTimeout(runPrint, 700);
                        };
                    }
                }

                //save settlement add payment js

                $(document).off("click", "#settlement_save_btn").on("click", "#settlement_save_btn", function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    console.log("save settlement clicked");

                    var $btn = $(this);

                    // Double-submission prevention
                    if ($btn.data('submitting')) {
                        console.log("Settlement save already in progress, ignoring click");
                        return false;
                    }

                    // Clear the balance lock interval when finalizing
                    if (window.__petro_balance_lock_interval) {
                        clearInterval(window.__petro_balance_lock_interval);
                        window.__petro_balance_lock_interval = null;
                    }
                    window.__petro_balance_locked = false;

                    // Mark as submitting
                    $btn.data('submitting', true);
                    $btn.attr("disabled", "disabled");

                    var url = $("#settlement_form").attr("action");
                    console.log("Form action URL:", url);

                    if (!url) {
                        toastr.error("Form action URL not found!");
                        $btn.removeAttr("disabled");
                        $btn.data('submitting', false);
                        return false;
                    }

                    var settlement_no = $("#settlement_no").val();
                    console.log("Settlement no:", settlement_no);
                    var printWindow = openSettlementFinalizePrintWindow();

                    var no_change = $("#no_change").val();



                    var qtyArray = [];

                    $('.denom_qty').each(function () {

                        var qtyValue = $(this).val();

                        qtyArray.push(qtyValue); // Add the value to the array

                    });



                    var denomArray = [];

                    $('.denom_value').each(function () {

                        var denomValue = $(this).val();

                        denomArray.push(denomValue); // Add the value to the array

                    });



                    var denoEnabled = 0;



                    if ($('#enable_cash_denoms').is(':checked')) {

                        denomEnabled = 1;

                    } else {

                        denomEnabled = 0;

                    }

                    var shift_ids = $('#shift_number').val();

                    $.ajax({

                        method: "post",

                        url: url,

                        dataType: "json",

                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },

                        data: { settlement_no: settlement_no, denom_qty: qtyArray, denom_value: denomArray, denom_enabled: denomEnabled, no_change: no_change, shift_ids: shift_ids },

                        success: function (result) {
                            console.log("Save response type:", typeof result);
                            console.log("Save response:", result);

                            // Check if result is JSON (object with success property)
                            if (result && typeof result === 'object' && result.hasOwnProperty('success')) {
                                // Result is JSON response
                                if (result.success === 0) {
                                    // Error response
                                    toastr.error(result.msg || "Something went wrong while saving.");
                                    if (printWindow && !printWindow.closed) {
                                        printWindow.close();
                                    }
                                    $btn.removeAttr("disabled"); // Re-enable button on error
                                    $btn.data('submitting', false); // Reset submitting flag
                                    return;
                                } else {
                                    // Success response (JSON)
                                    console.log("Save successful (JSON response)");
                                    toastr.success(result.msg || "Saved Successfully");
                                    localStorage.removeItem('lastUpdateData');

                                    // Only open print when HTML is present and non-empty (fix blank print)
                                    var printContent = result.html;
                                    if (printContent && typeof printContent === 'string' && printContent.trim().length > 0) {
                                        $("#settlement_print").html(printContent);
                                        console.log("Triggering instant print for settlement PD");
                                        writeSettlementFinalizePrint(
                                            printWindow,
                                            printContent,
                                            result.settlement_id ? "{{ url('/petro-general/settlement-pd/print') }}/" + result.settlement_id : null
                                        );
                                    } else {
                                        toastr.warning("Print content not available. You can print from the settlement list.");
                                        writeSettlementFinalizePrint(
                                            printWindow,
                                            null,
                                            result.settlement_id ? "{{ url('/petro-general/settlement-pd/print') }}/" + result.settlement_id : null
                                        );
                                    }

                                    // Close modal and reload page after a slightly longer delay
                                    // to ensure print window preparation isn't interrupted
                                    setTimeout(function () {
                                        $('.add_payment').modal('hide');
                                        location.reload();
                                    }, 3000);
                                    // Don't reset submitting flag here - page will reload
                                    return;
                                }
                            }

                            // Result is HTML view (string) - success case (fallback for non-AJAX requests)
                            console.log("Save successful, showing print view (HTML response)");
                            toastr.success("Saved Successfully");
                            localStorage.removeItem('lastUpdateData');
                            var htmlContent = typeof result === 'string' ? result : '';
                            if (htmlContent.trim().length > 0) {
                                $("#settlement_print").html(htmlContent);
                                console.log("Triggering instant print for settlement PD");
                                writeSettlementFinalizePrint(printWindow, htmlContent, null);
                            } else {
                                toastr.warning("Print content not available. You can print from the settlement list.");
                                if (printWindow && !printWindow.closed) {
                                    printWindow.close();
                                }
                            }

                            // Close modal and reload page after a slightly longer delay
                            // to ensure print window preparation isn't interrupted
                            setTimeout(function () {
                                $('.add_payment').modal('hide');
                                location.reload();
                            }, 3000);
                            // Don't reset submitting flag here - page will reload

                        },

                        error: function (xhr, status, error) {
                            console.error("Save error:", error, xhr.responseText);
                            var errorMessage = "Something went wrong while saving.";
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.msg) {
                                    errorMessage = response.msg;
                                }
                            } catch (e) {
                                // Use default error message
                            }
                            toastr.error(errorMessage);
                            if (printWindow && !printWindow.closed) {
                                printWindow.close();
                            }
                            $btn.removeAttr("disabled"); // Re-enable button on error
                            $btn.data('submitting', false); // Reset submitting flag on error
                        }

                    });

                });

                // Prevent form from submitting normally (only allow AJAX submission via button)
                $(document).off("submit", "#settlement_form").on("submit", "#settlement_form", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log("Form submit prevented - use Finalize Settlement button instead");
                    return false;
                });

                $(document).on("click", ".cash_add", function () {

                    if ($("#cash_amount").val() == "") {

                        toastr.error("Please enter amount");

                        return false;

                    }

                    var cash_customer_id = $("#cash_customer_id").val();

                    var cash_amount = $("#cash_amount").val();

                    var settlement_no = $("#settlement_no").val();

                    var customer_name = $("#cash_customer_id :selected").text();

                    var cash_note = $("#cash_note").val();

                    var is_edit = $("#is_edit").val() ?? 0;





                    $.ajax({

                        method: "post",

                        url: "/petro-general/settlement/payment/save-cash-payment",

                        data: {

                            customer_id: cash_customer_id,

                            amount: cash_amount,

                            settlement_no: settlement_no,

                            note: cash_note,

                            is_edit: is_edit

                        },

                        success: function (result) {

                            if (!result.success) {

                                toastr.error(result.msg);

                            } else {

                                if ($('#calculate_cash').is(':checked')) {

                                    $(".denoms_totals").hide();

                                    $(".cash_to_disable").hide();

                                    $("#cash_amount").prop('readonly', true);

                                } else {

                                    $(".denoms_totals").show();

                                    $(".cash_to_disable").show();

                                    $("#cash_amount").prop('readonly', false);

                                }



                                console.log('here is cash add data ==>', result);

                                settlement_cash_payment_id = result.settlement_cash_payment_id;

                                add_payment(cash_amount);

                                $("#cash_table tbody").prepend(

                                    `

                    <tr>

                        <td>` +

                                    customer_name +

                                    `</td>

                        <td class="cash_amount">` +

                                    __number_f(cash_amount, false, false, __currency_precision) +

                                    `</td>

                        <td>` +

                                    cash_note +

                                    `</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_cash_payment" data-href="/petro-general/settlement/payment/delete-cash-payment/` +

                                    settlement_cash_payment_id +

                                    `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                                );

                                $(".cash_fields").val("");

                                calculateTotal("#cash_table", ".cash_amount", ".cash_total");

                            }

                        },

                    });

                });

                $(document).on("click", ".delete_cash_payment", function () {

                    url = $(this).data("href");

                    tr = $(this).closest("tr");

                    var is_edit = $("#is_edit").val() ?? 0;



                    $.ajax({

                        method: "delete",

                        url: url,

                        data: { is_edit },

                        success: function (result) {

                            if (result.success) {

                                toastr.success(result.msg);

                                tr.remove();

                                let this_amount = result.amount;

                                // Cash payments should NOT affect balance - they are separate from outstanding balance
                                // Removed: delete_payment(this_amount);

                                calculateTotal("#cash_table", ".cash_amount", ".cash_total");

                            } else {

                                toastr.error(result.msg);

                            }

                        },

                    });

                });





                // $(document).on("click", ".customer_loans_add", function () {
                $(document).off("click", ".customer_loans_add").on("click", ".customer_loans_add", function () {

                    if ($("#customer_loans_amount").val() == "") {

                        toastr.error("Please enter amount");

                        return false;

                    }

                    var cash_customer_id = $("#customer_loans_customer_id").val();

                    var cash_amount = $("#customer_loans_amount").val();

                    var settlement_no = $("#settlement_no").val();

                    var customer_name = $("#customer_loans_customer_id :selected").text();

                    var is_edit = $("#is_edit").val() ?? 0;



                    var note = $("#customer_loans_note").val();





                    $.ajax({

                        method: "post",

                        url: "/petro-general/settlement/payment/save-customer-loans",

                        data: {

                            customer_id: cash_customer_id,

                            amount: cash_amount,

                            settlement_no: settlement_no,

                            is_edit: is_edit,

                            note: note

                        },

                        success: function (result) {

                            if (!result.success) {

                                toastr.error(result.msg);

                            } else {



                                settlement_customer_loan_id = result.settlement_customer_loan_id;

                                add_payment(cash_amount);

                                $("#customer_loans_table tbody").prepend(

                                    `

                    <tr>

                        <td>` +

                                    customer_name +

                                    `</td>

                        <td class="customer_loan_amount">` +

                                    __number_f(cash_amount, false, false, __currency_precision) +

                                    `</td>



                        <td>` +

                                    note +

                                    `</td>



                        <td><button type="button" class="btn btn-xs btn-danger delete_customer_loans_payment" data-href="/petro-general/settlement/payment/delete-customer-loans/` +

                                    settlement_customer_loan_id +

                                    `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                                );

                                $(".customer_loans_field").val("");

                                calculateTotal("#customer_loans_table", ".customer_loan_amount", ".customer_loans_total");

                            }

                        },

                    });

                });

                $(document).on("click", ".delete_customer_loans_payment", function () {

                    url = $(this).data("href");

                    tr = $(this).closest("tr");



                    var is_edit = $("#is_edit").val() ?? 0;



                    $.ajax({

                        method: "delete",

                        url: url,

                        data: { is_edit },

                        success: function (result) {

                            if (result.success) {

                                toastr.success(result.msg);

                                tr.remove();

                                let this_amount = result.amount;

                                delete_payment(this_amount);

                                calculateTotal("#customer_loans_table", ".customer_loan_amount", ".customer_loans_total");

                            } else {

                                toastr.error(result.msg);

                            }

                        },

                    });

                });





                // $(document).on("click", ".loan_payments_add", function () {
                $(document).off("click", ".loan_payments_add").on("click", ".loan_payments_add", function () {

                    if ($("#loan_payments_amount").val() === "") {

                        toastr.error("Please enter amount");

                        return false;

                    }



                    if ($("#loan_payments_bank").val() === "") {

                        toastr.error("Please choose loan account");

                        return false;

                    }





                    var loan_payments_amount = $("#loan_payments_amount").val();

                    var settlement_no = $("#settlement_no").val();

                    var loan_payments_bank = $("#loan_payments_bank").val();



                    var bank_name = $("#loan_payments_bank :selected").text();

                    var loan_payments_note = $("#loan_payments_note").val();

                    var is_edit = $("#is_edit").val() ?? 0;





                    $.ajax({

                        method: "post",

                        url: "/petro-general/settlement/payment/save-loan-payment",

                        data: {

                            loan_account: loan_payments_bank,

                            amount: loan_payments_amount,

                            settlement_no: settlement_no,

                            note: loan_payments_note,

                            is_edit: is_edit

                        },

                        success: function (result) {

                            if (!result.success) {

                                toastr.error(result.msg);

                            } else {



                                settlement_loan_payment_id = result.settlement_loan_payment_id;

                                add_payment(loan_payments_amount);

                                $("#loan_payments_table tbody").prepend(

                                    `

                    <tr>

                        <td>` +

                                    bank_name +

                                    `</td>

                        <td class="loan_payments_amount">` +

                                    __number_f(loan_payments_amount, false, false, __currency_precision) +

                                    `</td>

                        <td>` +

                                    loan_payments_note +

                                    `</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_loan_payment" data-href="/petro-general/settlement/payment/delete-loan-payment/` +

                                    settlement_loan_payment_id +

                                    `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                                );

                                $(".loan_payments_fields").val("");

                                calculateTotal("#loan_payments_table", ".loan_payments_amount", ".loan_payments_total");

                            }

                        },

                    });

                });

                $(document).on("click", ".delete_loan_payment", function () {

                    url = $(this).data("href");

                    tr = $(this).closest("tr");

                    var is_edit = $("#is_edit").val() ?? 0;



                    $.ajax({

                        method: "delete",

                        url: url,

                        data: { is_edit },

                        success: function (result) {

                            if (result.success) {

                                toastr.success(result.msg);

                                tr.remove();

                                let this_amount = result.amount;

                                delete_payment(this_amount);

                                calculateTotal("#loan_payments_table", ".loan_payments_amount", ".loan_payments_total");

                            } else {

                                toastr.error(result.msg);

                            }

                        },

                    });

                });



                // $(document).on("click", ".drawing_payments_add", function () {
                $(document).off("click", ".drawing_payments_add").on("click", ".drawing_payments_add", function () {

                    if ($("#drawing_payments_amount").val() === "") {

                        toastr.error("Please enter amount");

                        return false;

                    }



                    if ($("#drawing_payments_bank").val() === "") {

                        toastr.error("Please choose account");

                        return false;

                    }





                    var loan_payments_amount = $("#drawing_payments_amount").val();

                    var settlement_no = $("#settlement_no").val();

                    var loan_payments_bank = $("#drawing_payments_bank").val();



                    var bank_name = $("#drawing_payments_bank :selected").text();

                    var loan_payments_note = $("#drawing_payments_note").val();

                    var is_edit = $("#is_edit").val() ?? 0;





                    $.ajax({

                        method: "post",

                        url: "/petro-general/settlement/payment/save-drawing-payment",

                        data: {

                            loan_account: loan_payments_bank,

                            amount: loan_payments_amount,

                            settlement_no: settlement_no,

                            note: loan_payments_note,

                            is_edit: is_edit

                        },

                        success: function (result) {

                            if (!result.success) {

                                toastr.error(result.msg);

                            } else {



                                settlement_loan_payment_id = result.settlement_loan_payment_id;

                                add_payment(loan_payments_amount);

                                $("#drawing_payments_table tbody").prepend(

                                    `

                    <tr>

                        <td>` +

                                    bank_name +

                                    `</td>

                        <td class="loan_payments_amount">` +

                                    __number_f(loan_payments_amount, false, false, __currency_precision) +

                                    `</td>

                        <td>` +

                                    loan_payments_note +

                                    `</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_drawing_payment" data-href="/petro-general/settlement/payment/delete-drawing-payment/` +

                                    settlement_loan_payment_id +

                                    `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                                );

                                $(".loan_payments_fields").val("");

                                calculateTotal("#drawing_payments_table", ".drawing_payments_amount", ".drawing_payments_total");

                            }

                        },

                    });

                });

                $(document).on("click", ".delete_drawing_payment", function () {

                    url = $(this).data("href");

                    tr = $(this).closest("tr");

                    var is_edit = $("#is_edit").val() ?? 0;



                    $.ajax({

                        method: "delete",

                        url: url,

                        data: { is_edit },

                        success: function (result) {

                            if (result.success) {

                                toastr.success(result.msg);

                                tr.remove();

                                let this_amount = result.amount;

                                delete_payment(this_amount);

                                calculateTotal("#drawing_payments_table", ".drawing_payments_amount", ".drawing_payments_total");

                            } else {

                                toastr.error(result.msg);

                            }

                        },

                    });

                });





                // $(document).on("click", ".cash_deposit_add", function () {
                $(document).off("click", ".cash_deposit_add").on("click", ".cash_deposit_add", function () {

                    if ($("#cash_deposit_amount").val() == "") {

                        toastr.error("Please enter amount");

                        return false;

                    }

                    var bank_id = $("#cash_deposit_bank").val();

                    var bank_name = $("#cash_deposit_bank :selected").text();

                    var cash_deposit_amount = $("#cash_deposit_amount").val();

                    var settlement_no = $("#settlement_no").val();

                    var account = $("#cash_deposit_account").val();

                    var time = $("#cash_deposit_time").val();

                    // var image = $("#image").val();

                    var is_edit = $("#is_edit").val() ?? 0;

                    $.ajax({
                        method: "post",
                        url: "/petro-general/settlement/payment/save-cash-deposit",
                        data: {
                            bank_id,
                            cash_deposit_amount: cash_deposit_amount.toString().replace(/,/g, ""),
                            settlement_no,
                            account,
                            time,
                            is_edit
                        },
                        success: function (result) {

                            if (!result.success) {

                                toastr.error(result.msg);

                            }

                            else {

                                settlement_cash_payment_id = result.settlement_cash_payment_id;

                                add_payment(cash_deposit_amount);

                                $("#cash_deposit_table tbody").prepend(

                                    `

                    <tr>

                        <td>` +

                                    bank_name +

                                    `</td>

                        <td>` +

                                    account +

                                    `</td>

                        <td class="cash_deposit_amount">` +

                                    __number_f(cash_deposit_amount, false, false, __currency_precision) +

                                    `</td>

                        <td>` +

                                    time +

                                    `</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_cash_deposit" data-href="/petro-general/settlement/payment/delete-cash-deposit/` +

                                    settlement_cash_payment_id +

                                    `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                                );



                                calculateTotal("#cash_deposit_table", ".cash_deposit_amount", ".cash_deposit_total");

                            }

                        },

                    });

                });

            });

            $(document).on("click", ".delete_cash_deposit", function () {

                url = $(this).data("href");

                tr = $(this).closest("tr");

                var is_edit = $("#is_edit").val() ?? 0;



                $.ajax({

                    method: "delete",

                    url: url,

                    data: { is_edit },

                    success: function (result) {

                        if (result.success) {

                            toastr.success(result.msg);

                            tr.remove();

                            let this_amount = result.amount;

                            delete_payment(this_amount);

                            calculateTotal("#cash_deposit_table", ".cash_deposit_amount", ".cash_deposit_total");

                        } else {

                            toastr.error(result.msg);

                        }

                    },

                });

            });





            //card payments

            // $(document).on("click", ".card_add", function () {
            $(document).off("click", ".card_add").on("click", ".card_add", function () {
                if ($("#card_amount").val() == "") {

                    toastr.error("Please enter amount");

                    return false;

                }





                var card_customer_id = $("#card_customer_id").val();

                var customer_name = $("#card_customer_id :selected").text();

                var card_amount = $("#card_amount").val();

                var $form = $(this).closest('form');
                if (!$form.length) {
                    $form = $("#settlement_form");
                }
                var settlement_no = $form.find('input[name="settlement_no"]').val();
                var settlement_id = $form.find('input[name="payment_settlement_id"]').val();

                var card_type = $("#card_type :selected").text();

                var card_type_id = $("#card_type").val();

                var card_number = $("#card_number").val();

                var card_note = $("#card_note").val();

                var slip_no = $("#slip_no").val();

                var is_edit = $form.find("#is_edit").val() ?? $("#is_edit").val() ?? 0;



                if (card_type_id == null || card_type_id == "" || card_type_id == "undefined") {

                    toastr.error("Please select card type");

                    return false;

                }

                swal({
                    title: "Add Card Payment?",
                    text: "Are you sure you want to add this card payment?",
                    icon: "warning",
                    buttons: true,
                    dangerMode: false,
                }).then(function (confirmed) {
                    if (!confirmed) return;

                    $.ajax({

                        method: "post",

                        url: "/petro-general/settlement/payment/save-card-payment",

                        data: {

                            customer_id: card_customer_id,

                            amount: card_amount,

                            card_type: card_type_id,

                            card_number: card_number,

                            settlement_no: settlement_no,

                            note: card_note,

                            slip_no: slip_no,

                            is_edit: is_edit

                        },

                        success: function (result) {

                            if (!result.success) {

                                toastr.error(result.msg);

                            } else {

                                settlement_card_payment_id = result.settlement_card_payment_id;

                                add_payment(card_amount);

                                $("#card_table tbody").prepend(

                                    `

                    <tr>

                        <td>` +

                                    customer_name +

                                    `</td>

                        <td>` +

                                    card_type +

                                    `</td>

                        <td>` +

                                    card_number +

                                    `</td>

                        <td class="card_amount">` +

                                    __number_f(card_amount, false, false, __currency_precision) +

                                    `</td>

                        <td>` +

                                    slip_no +

                                    `</td>

                        <td>` +

                                    card_note +

                                    `</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_card_payment" data-href="/petro-general/settlement/payment/delete-card-payment/` +

                                    settlement_card_payment_id +

                                    `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                                );

                                $(".card_fields").val("").trigger('change');

                                $(".cash_fields").val("").trigger('change');

                                calculateTotal("#card_table", ".card_amount", ".card_total");

                            }

                        },

                    });

                });

            });

            $(document).on("click", ".delete_card_payment", function () {

                url = $(this).data("href");

                tr = $(this).closest("tr");

                var is_edit = $("#is_edit").val() ?? 0;



                $.ajax({

                    method: "delete",

                    url: url,

                    data: { is_edit },

                    success: function (result) {

                        if (result.success) {

                            toastr.success(result.msg);

                            tr.remove();

                            let this_amount = result.amount;

                            delete_payment(this_amount);

                            calculateTotal("#card_table", ".card_amount", ".card_total");

                        } else {

                            toastr.error(result.msg);

                        }

                    },

                });

            });

            //cheque payments

            // $(document).on("click", ".cheque_add", function () {
            $(document).off("click", ".cheque_add").on("click", ".cheque_add", function () {
                if ($("#cheque_amount").val() == "") {

                    toastr.error("Please enter amount");

                    return false;

                }

                var cheque_customer_id = $("#cheque_customer_id").val();

                var customer_name = $("#cheque_customer_id :selected").text();

                var cheque_amount = $("#cheque_amount").val();

                var $form = $(this).closest('form');
                if (!$form.length) {
                    $form = $("#settlement_form");
                }
                var settlement_no = $form.find('input[name="settlement_no"]').val();
                var settlement_id = $form.find('input[name="payment_settlement_id"]').val();

                var cheque_date = $("#cheque_date").val();

                var bank_name = $("#bank_name").val();

                var cheque_number = $("#cheque_number").val();

                var cheque_post_dated_cheque = $("#cheque_post_dated_cheque").val();

                var cheque_note = $("#cheque_note").val();

                var is_edit = $form.find("#is_edit").val() ?? $("#is_edit").val() ?? 0;

                swal({
                    title: "Add Cheque Payment?",
                    text: "Are you sure you want to add this cheque payment?",
                    icon: "warning",
                    buttons: true,
                    dangerMode: false,
                }).then(function (confirmed) {
                    if (!confirmed) return;

                    $.ajax({

                        method: "post",

                        url: "/petro-general/settlement/payment/save-cheque-payment",

                        data: {

                            customer_id: cheque_customer_id,

                            amount: cheque_amount,

                            bank_name: bank_name,

                            cheque_date: cheque_date,

                            cheque_number: cheque_number,

                            settlement_no: settlement_no,

                            note: cheque_note,

                            is_edit: is_edit

                        },

                        success: function (result) {

                            if (!result.success) {

                                toastr.error(result.msg);

                            } else {

                                settlement_cheque_payment_id = result.settlement_cheque_payment_id;

                                add_payment(cheque_amount);

                                $("#cheque_table tbody").prepend(

                                    `

                    <tr>

                        <td>` +

                                    customer_name +

                                    `</td>

                        <td>` +

                                    bank_name +

                                    `</td>

                        <td>` +

                                    cheque_number +

                                    `</td>

                        <td>` +

                                    cheque_date +

                                    `</td>

                        <td class="cheque_amount">` +

                                    __number_f(cheque_amount, false, false, __currency_precision) +

                                    `</td>

                         <td>` +

                                    cheque_note +

                                    `</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_cheque_payment" data-href="/petro-general/settlement/payment/delete-cheque-payment/` +

                                    settlement_cheque_payment_id +

                                    `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                                );

                                $(".cheque_fields").val("");

                                $(".cash_fields").val("");

                                calculateTotal("#cheque_table", ".cheque_amount", ".cheque_total");

                            }

                        },

                    });

                });

            });

            $(document).on("click", ".delete_cheque_payment", function () {

                url = $(this).data("href");

                tr = $(this).closest("tr");

                var is_edit = $("#is_edit").val() ?? 0;



                $.ajax({

                    method: "delete",

                    url: url,

                    data: { is_edit },

                    success: function (result) {

                        if (result.success) {

                            toastr.success(result.msg);

                            tr.remove();

                            let this_amount = result.amount;

                            delete_payment(this_amount);

                            calculateTotal("#cheque_table", ".cheque_amount", ".cheque_total");

                        } else {

                            toastr.error(result.msg);

                        }

                    },

                });

            });

            //credit_sale payments

            // $(document).on("click", ".credit_sale_add", function () {

            //      console.log('789');

            //     if ($("#credit_sale_amount").val() == "") {

            //         toastr.error("Please enter amount");

            //         return false;

            //     }

            //     var credit_sale_customer_id = $("#credit_sale_customer_id").val();

            //     var customer_name = $("#credit_sale_customer_id :selected").text();

            //     var credit_sale_product_id = $("#credit_sale_product_id").val();

            //     var credit_sale_product_name = $("#credit_sale_product_id :selected").text();

            //     if ($("#customer_reference_one_time").val() !== "" && $("#customer_reference_one_time").val() !== null && $("#customer_reference_one_time").val() !== undefined) {

            //         var customer_reference = $("#customer_reference_one_time").val();

            //     } else {

            //         var customer_reference = $("#customer_reference").val();

            //     }

            //     var settlement_no = $("#settlement_no").val();

            //     var order_date = $("#order_date").val();

            //     var order_number = $("#order_number").val();



            //     var credit_sale_price = __read_number($("#unit_price"));

            //     var credit_unit_discount = __read_number($("#unit_discount")) ?? 0;

            //     var credit_sale_qty = __read_number($("#credit_sale_qty")) ?? 0;

            //     var credit_total_amount = __read_number($("#credit_total_amount")) ?? 0;

            //     var credit_total_discount = __read_number($("#credit_discount_amount")) ?? 0;

            //     var credit_sub_total = __read_number($("#credit_sale_amount")) ?? 0;



            //     var outstanding = $(".current_outstanding").text();

            //     var credit_limit = $(".credit_limit").text();

            //     var credit_note = $("#credit_note").val();

            //     var is_edit = $("#is_edit").val() ?? 0;



            //     $.ajax({

            //         method: "post",

            //         url: "/petro-general/settlement/payment/save-credit-sale-payment",

            //         data: {

            //             settlement_no: settlement_no,

            //             customer_id: credit_sale_customer_id,

            //             product_id: credit_sale_product_id,

            //             order_number: order_number,

            //             order_date: order_date,



            //             price: credit_sale_price,

            //             unit_discount: credit_unit_discount,

            //             qty: credit_sale_qty,

            //             amount: credit_total_amount,

            //             sub_total: credit_sub_total,

            //             total_discount: credit_total_discount,

            //             outstanding: outstanding,

            //             credit_limit: credit_limit,

            //             customer_reference: customer_reference,

            //             note: credit_note,

            //             is_edit: is_edit

            //         },

            //         success: function (result) {

            //             if (!result.success) {

            //                 toastr.error(result.msg);

            //             } else {

            //                 settlement_credit_sale_payment_id = result.settlement_credit_sale_payment_id;

            //                 add_payment(credit_total_amount-credit_total_discount);

            //                 $("#credit_sale_table tbody").prepend(

            //                     `

            //                     <tr>

            //                         <td>` +

            //                         customer_name +

            //                         `</td>

            //                         <td>` +

            //                         outstanding +

            //                         `</td>

            //                         <td>` +

            //                         credit_limit +

            //                         `</td>

            //                         <td>` +

            //                         order_number +

            //                         `</td>

            //                         <td>` +

            //                         order_date +

            //                         `</td>

            //                         <td>` +

            //                         customer_reference +

            //                         `</td>

            //                         <td>` +

            //                         credit_sale_product_name +

            //                         `</td>

            //                         <td>` +

            //                         __number_f(credit_sale_price, false, false, __currency_precision) +

            //                         `</td>

            //                         <td>` +

            //                         __number_f(credit_sale_qty, false, false, __currency_precision) +

            //                         `</td>

            //                         <td class="credit_sale_amount">` +

            //                         __number_f(credit_total_amount, false, false, __currency_precision) +

            //                         `</td>



            //                         <td class="credit_tbl_discount_amount">` +

            //                         __number_f(credit_total_discount, false, false, __currency_precision) +

            //                         `</td>

            //                         <td class="credit_tbl_total_amount">` +

            //                         __number_f(credit_sub_total, false, false, __currency_precision) +

            //                         `</td>





            //                         <td>` +

            //                       credit_note +

            //                         `</td>

            //                         <td><button type="button" class="btn btn-xs btn-danger delete_credit_sale_payment" data-href="/petro-general/settlement/payment/delete-credit-sale-payment/` +

            //                         settlement_credit_sale_payment_id +

            //                         `"><i class="fa fa-times"></i></button>

            //                         </td>

            //                     </tr>

            //                 `

            //                 );

            //                 $("#customer_reference_one_time").val("").trigger("change");

            //                 $(".credit_sale_fields").val("");

            //                 $(".cash_fields").val("");

            //                 $("#credit_sale_product_id").trigger('change');

            //                 $("#order_number").val(order_number);

            //                 calculateTotal("#credit_sale_table", ".credit_sale_amount", ".credit_sale_total");

            //                 calculateTotal("#credit_sale_table", ".credit_tbl_discount_amount", ".credit_tb_discount_total");

            //                 calculateTotal("#credit_sale_table", ".credit_tbl_total_amount", ".credit_tbl_amount_total");



            //             }

            //         },

            //     });

            // });



            $(document).on('input', '#credit_total_amount', function () {

                $("#credit_sale_qty").attr('disabled', true);



                let price = __read_number($("#unit_price")) ?? 0;

                let total_amount = __read_number($("#credit_total_amount")) ?? 0;

                let qty = total_amount / price;



                let total_discount = __read_number($("#credit_discount_amount")) ?? 0;

                let unit_discount = total_discount / qty;

                let amount = total_amount - total_discount



                __write_number($("#credit_sale_amount"), amount);

                __write_number($("#unit_discount"), unit_discount);

                __write_number_without_decimal_format($("#credit_sale_qty"), qty);





            });



            $(document).on('input', '#credit_sale_qty', function () {

                if ($('#total_amount_enable').val() != '1') {

                    $("#credit_total_amount").attr('disabled', true);

                }



                let price = __read_number($("#unit_price")) ?? 0;

                let qty = __read_number($("#credit_sale_qty")) ?? 0;

                let total_amount = price * qty;



                let total_discount = __read_number($("#credit_discount_amount")) ?? 0;

                let unit_discount = total_discount / qty;

                let amount = total_amount - total_discount



                __write_number($("#credit_sale_amount"), amount);

                __write_number($("#unit_discount"), unit_discount);

                __write_number($("#credit_total_amount"), total_amount);



            });



            $(document).on("change", "#credit_discount_amount, #unit_price", function () {

                let price = __read_number($("#unit_price")) ?? 0;

                let qty_check = __read_number($("#credit_sale_qty")) ?? 0;



                var qty = 0;

                var total_amount = 0;



                if (qty_check > 0) {

                    qty = __read_number($("#credit_sale_qty")) ?? 0;

                    total_amount = price * qty;

                    __write_number($("#credit_total_amount"), total_amount);

                } else {

                    total_amount = __read_number($("#credit_total_amount")) ?? 0;

                    qty = total_amount / price;

                    __write_number_without_decimal_format($("#credit_sale_qty"), qty);

                }







                let total_discount = __read_number($("#credit_discount_amount")) ?? 0;

                let unit_discount = total_discount / qty;

                let amount = total_amount - total_discount



                __write_number($("#unit_discount"), unit_discount);

                __write_number($("#credit_sale_amount"), amount);



            });



            $(document).on("change", "#credit_sale_product_id", function () {

                if ($(this).val()) {

                    $.ajax({

                        method: "get",

                        url: "/petro-general/settlement/payment/get-product-price",

                        data: { product_id: $(this).val() },

                        success: function (result) {

                            $("#unit_price").val(result.price);

                            $("#unit_price").trigger('change');



                            $("#credit_total_amount").attr("disabled", false);

                            $("#credit_sale_qty").attr("disabled", false);

                            if ($("#manual_discount").val() == 1) {

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

            $(document).on("change", "#credit_sale_customer_id", function () {

                $.ajax({

                    method: "get",

                    url: "/petro-general/settlement/payment/get-customer-details/" + $(this).val(),

                    data: {},

                    success: function (result) {

                        $(".current_outstanding").text(result.total_outstanding);

                        $(".credit_limit").text(result.credit_limit);

                        if (result.manual_bill_settlement == 1) {

                            $('.unit_discount').prop('disabled', false);

                            $('.credit_total_amount').prop('disabled', false);

                            $('.credit_discount_amount').prop('disabled', false);

                            // Set hidden input to 1

                            $('#total_amount_enable').val(1);

                        }


                        // Clear existing options
                        $("#customer_reference").empty();

                        if (result.customer_references && result.customer_references.length > 0) {
                            // Add "Please Select"
                            $("#customer_reference").append(`<option value="">Please Select</option>`);


                            // Append other vehicle options
                            result.customer_references.forEach(function (ref) {
                                $("#customer_reference").append(`<option value="` + ref.reference + `">` + ref.reference + `</option>`);
                            });
                        } else {
                            // If no vehicles, just show "No Vehicle No" selected
                            $("#customer_reference").append(`<option selected="selected" value="no_vehicle">No Vehicle No</option>`);
                        }

                        // Optionally, trigger change if needed
                        // $("#customer_reference").val("no_vehicle").trigger("change");



                    },

                });

            });

            $(document).on("click", ".delete_credit_sale_payment", function () {

                url = $(this).data("href");

                tr = $(this).closest("tr");

                var is_edit = $("#is_edit").val() ?? 0;



                $.ajax({

                    method: "delete",

                    url: url,

                    data: { is_edit },

                    success: function (result) {

                        if (result.success) {

                            toastr.success(result.msg);

                            tr.remove();

                            // Use net_amount (after discount) for balance calculation
                            let this_amount = result.net_amount || result.amount;

                            delete_payment(this_amount);

                            calculateTotal("#credit_sale_table", ".credit_sale_amount", ".credit_sale_total");

                            calculateTotal("#credit_sale_table", ".credit_tbl_discount_amount", ".credit_tb_discount_total");

                            calculateTotal("#credit_sale_table", ".credit_tbl_total_amount", ".credit_tbl_amount_total");

                        } else {

                            toastr.error(result.msg);

                        }

                    },

                });

            });

            //expense payments

            // $(document).on("click", ".expense_add", function () {
            $(document).off("click", ".expense_add").on("click", ".expense_add", function () {

                if ($("#expense_amount").val() == "") {

                    toastr.error("Please enter amount");

                    return false;

                }

                var $form = $(this).closest('form');
                if (!$form.length) {
                    $form = $("#settlement_form");
                }
                var settlement_no = $form.find('input[name="settlement_no"]').val();
                var settlement_id = $form.find('input[name="payment_settlement_id"]').val();

                var expense_number = $("#expense_number").val();

                var reference_no = $("#reference_no").val();

                var expense_account = $("#expense_account").val();

                var expense_account_name = $("#expense_account :selected").text();

                var expense_category = $("#expense_category").val();

                var expense_category_name = $("#expense_category :selected").text();

                var expense_reason = $("#expense_reason").val();

                var expense_amount = $("#expense_amount").val();

                var is_edit = $form.find("#is_edit").val() ?? $("#is_edit").val() ?? 0;



                $.ajax({

                    method: "post",

                    url: "/petro-general/settlement/payment/save-expense-payment",

                    data: {

                        settlement_no: settlement_no,
                        payment_settlement_id: settlement_id,

                        expense_number: expense_number,

                        category_id: expense_category,

                        reference_no: reference_no,

                        account_id: expense_account,

                        reason: expense_reason,

                        amount: expense_amount,

                        is_edit: is_edit

                    },

                    success: function (result) {

                        if (!result.success) {

                            toastr.error(result.msg);

                        } else {

                            settlement_expense_payment_id = result.settlement_expense_payment_id;

                            add_payment(expense_amount);

                            $("#expense_table tbody").prepend(

                                `

                    <tr>

                        <td>` +

                                expense_number +

                                `</td>

                        <td>` +

                                expense_category_name +

                                `</td>

                        <td>` +

                                reference_no +

                                `</td>

                        <td>` +

                                expense_account_name +

                                `</td>

                        <td>` +

                                expense_reason +

                                `</td>

                        <td class="expense_amount">` +

                                __number_f(expense_amount, false, false, __currency_precision) +

                                `</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_expense_payment" data-href="/petro-general/settlement/payment/delete-expense-payment/` +

                                settlement_expense_payment_id +

                                `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                            );

                            $(".expense_fields").val("").trigger('change');

                            $("#expense_number").val(result.expense_number);

                            calculateTotal("#expense_table", ".expense_amount", ".expense_total");

                        }

                    },

                });

            });

            $(document).on("click", ".delete_expense_payment", function () {

                url = $(this).data("href");

                tr = $(this).closest("tr");



                var is_edit = $("#is_edit").val() ?? 0;



                $.ajax({

                    method: "delete",

                    url: url,

                    data: { is_edit },

                    success: function (result) {

                        if (result.success) {

                            toastr.success(result.msg);

                            tr.remove();

                            let this_amount = result.amount;

                            delete_payment(this_amount);

                            calculateTotal("#expense_table", ".expense_amount", ".expense_total");

                        } else {

                            toastr.error(result.msg);

                        }

                    },

                });

            });

            //shortage payments

            // $(document).on("click", ".shortage_add", function () {
            $(document).off("click", ".shortage_add").on("click", ".shortage_add", function () {
                if ($("#shortage_amount").val() == "") {

                    toastr.error("Please enter amount");

                    return false;

                }

                var current_balance = parseFloat(($("#total_balance").val() || "0").replace(/,/g, ""));
                if (!window.__petro_balance_to_operator_active && !isNaN(current_balance) && current_balance < 0) {
                    toastr.error("Balance is negative. Please use Excess");
                    return false;
                }

                var $form = $(this).closest('form');
                if (!$form.length) {
                    $form = $("#settlement_form");
                }
                var settlement_no = $form.find('input[name="settlement_no"]').val();
                var settlement_id = $form.find('input[name="payment_settlement_id"]').val();

                var shortage_amount = __read_number($("#shortage_amount")) ?? 0;
                if (isNaN(shortage_amount) || shortage_amount <= 0) {
                    toastr.error("Please enter a positive amount");
                    return false;
                }

                var shortage_note = $("#shortage_note").val();



                var is_edit = $form.find("#is_edit").val() ?? $("#is_edit").val() ?? 0;



                $.ajax({

                    method: "post",

                    url: "/petro-general/settlement/payment/save-shortage-payment",

                    data: {

                        settlement_no: settlement_no,
                        payment_settlement_id: settlement_id,

                        amount: shortage_amount,

                        note: shortage_note,

                        is_edit: is_edit

                    },

                    success: function (result) {

                        if (!result.success) {

                            toastr.error(result.msg);

                        } else {

                            settlement_shortage_payment_id = result.settlement_shortage_payment_id;

                            if (!window.__petro_balance_to_operator_active) {
                                add_payment(shortage_amount);
                            }

                            var auto_balance_attr = (window.__petro_auto_balance_pending && window.__petro_auto_balance_pending.type === 'shortage')
                                ? ' data-auto-balance="1"' : '';
                            $("#shortage_table tbody").prepend(

                                `

                    <tr` + auto_balance_attr + `>

                        <td></td>

                        <td class="shortage_amount">` +

                                __number_f(shortage_amount, false, false, __currency_precision) +

                                `</td>

                        <td>` +

                                shortage_note +

                                `</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_shortage_payment" data-href="/petro-general/settlement/payment/delete-shortage-payment/` +

                                settlement_shortage_payment_id +

                                `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                            );
                            if (auto_balance_attr) {
                                window.__petro_auto_balance_state = window.__petro_auto_balance_pending;
                                window.__petro_auto_balance_pending = null;
                            }

                            $(".shortage_fields").val("");

                            $(".cash_fields").val("");

                            $("#shortage_number").val(result.shortage_number);

                            calculateTotal("#shortage_table", ".shortage_amount", ".shortage_total");

                        }

                    },

                });

            });

            $(document).on("click", ".delete_shortage_payment", function () {

                url = $(this).data("href");

                tr = $(this).closest("tr");



                var is_edit = $("#is_edit").val() ?? 0;



                $.ajax({

                    method: "delete",

                    url: url,

                    data: { is_edit },

                    success: function (result) {

                        if (result.success) {

                            toastr.success(result.msg);

                            tr.remove();

                            let this_amount = result.amount;

                            delete_payment(this_amount);

                            calculateTotal("#shortage_table", ".shortage_amount", ".shortage_total");

                        } else {

                            toastr.error(result.msg);

                        }

                    },

                });

            });

            //excess payments

            $(document).on("click", ".excess_add", function () {

                console.log('function called')

                var excess_amount_input = $("#excess_amount").val();

                var excess_note = $("#excess_note").val();

                if (excess_amount_input == "") {

                    toastr.error("Please enter amount");

                    return false;

                }

                if (!window.__petro_balance_to_operator_active && !isNaN(current_balance) && current_balance > 0) {
                    toastr.error("Balance is positive. Please use Shortage");
                    return false;
                }

                var excess_amount = __read_number($("#excess_amount")) ?? 0;

                // FIX Issue B: Normalize input with abs() so user can enter positive or negative
                // System stores excess as negative, but user can input any sign
                excess_amount = Math.abs(excess_amount);

                if (isNaN(excess_amount) || excess_amount === 0) {

                    toastr.error("Please enter a positive amount");

                    return false;

                }

                var excess_amount_signed = 0 - Math.abs(excess_amount);

                var $form = $(this).closest('form');
                if (!$form.length) {
                    $form = $("#settlement_form");
                }
                var settlement_no = $form.find('input[name="settlement_no"]').val();
                var settlement_id = $form.find('input[name="payment_settlement_id"]').val();

                var is_edit = $form.find("#is_edit").val() ?? $("#is_edit").val() ?? 0;



                $.ajax({

                    method: "post",

                    url: "/petro-general/settlement/payment/save-excess-payment",

                    data: {

                        settlement_no: settlement_no,
                        payment_settlement_id: settlement_id,

                        amount: excess_amount_signed,

                        note: excess_note,

                        is_edit: is_edit

                    },

                    success: function (result) {

                        console.log(result)

                        if (!result.success) {

                            toastr.error(result.msg);

                        } else {

                            settlement_excess_payment_id = result.settlement_excess_payment_id;

                            var auto_balance_attr = (window.__petro_auto_balance_pending && window.__petro_auto_balance_pending.type === 'excess')
                                ? ' data-auto-balance="1"' : '';

                            $("#excess_table tbody").prepend(

                                `

                    <tr` + auto_balance_attr + `>

                        <td></td>

                        <td class="excess_amount">` +

                                __number_f(Math.abs(excess_amount), false, false, __currency_precision) +

                                `</td>

                        <td>` +

                                excess_note +

                                `</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_excess_payment" data-href="/petro-general/settlement/payment/delete-excess-payment/` +

                                settlement_excess_payment_id +

                                `"><i class="fa fa-times"></i></button>

                        </td>

                    </tr>

                `

                            );
                            if (auto_balance_attr) {
                                window.__petro_auto_balance_state = window.__petro_auto_balance_pending;
                                window.__petro_auto_balance_pending = null;
                            }

                            console.log('working');

                            $(".excess_fields").val("");

                            $(".cash_fields").val("");

                            $("#excess_number").val(result.excess_number);

                            if (!window.__petro_skip_add_payment) {
                                if (typeof rebuildExcessTable === 'function') {
                                    rebuildExcessTable();
                                } else {
                                    calculateTotal("#excess_table", ".excess_amount", ".excess_total");
                                }
                            } else {
                                // Just update the total text without calling add_payment again via rebuild/calculate
                                calculateTotal("#excess_table", ".excess_amount", ".excess_total");
                            }

                        }

                        console.log('result', result)

                    },

                });

            });

            $(document).on("click", ".delete_excess_payment", function () {

                url = $(this).data("href");

                tr = $(this).closest("tr");

                var is_edit = $("#is_edit").val() ?? 0;



                $.ajax({

                    method: "delete",

                    url: url,

                    data: { is_edit },

                    success: function (result) {

                        if (result.success) {

                            toastr.success(result.msg);

                            tr.remove();

                            let this_amount = result.amount;



                            if (typeof rebuildExcessTable === 'function') {
                                rebuildExcessTable();
                            } else {
                                calculateTotal("#excess_table", ".excess_amount", ".excess_total");
                            }

                        } else {

                            toastr.error(result.msg);

                        }

                    },

                });

            });



            function calculateDenoms(amount = 0) {

                var grand_total = 0;

                $('.denom_amt').each(function () {

                    var amt = $(this).val();

                    if (amt && typeof amt === 'string') {
                        amt = parseFloat(amt.replace(/,/g, ""));
                    } else if (amt) {
                        amt = parseFloat(amt);
                    } else {
                        amt = 0;
                    }





                    if (!isNaN(amt)) {

                        grand_total += amt;

                    }

                });





                if ($('#calculate_cash').is(':checked')) {

                    $("#cash_amount").val(grand_total);

                    $("#cash_amount").prop('readonly', true);

                } else {

                    $("#cash_amount").prop('readonly', false);

                }



                $('.denom_total').val(grand_total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));



                var cashtotal = $(".cash_total").text() || "0";

                var bal = parseFloat(cashtotal.replace(/,/g, "")) - grand_total + amount;

                var total_balance_val = $("#total_balance").val() || "0";
                total_balance = parseFloat(total_balance_val.replace(/,/g, ""));





                if ($('#enable_cash_denoms').is(':checked')) {

                    if (bal == 0 && total_balance == 0) {

                        $("#settlement_save_btn").removeClass("hide");

                    } else {

                        $("#settlement_save_btn").addClass("hide");

                    }

                } else {

                    if (total_balance == 0) {

                        $("#settlement_save_btn").removeClass("hide");

                    } else {

                        $("#settlement_save_btn").addClass("hide");

                    }

                }





                $(".denom_bal").val(bal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

            }



            function add_payment(add_amount) {
                // When Balance to Operator pre-updated the UI, skip the duplicate update
                if (window.__petro_skip_add_payment) {
                    return;
                }

                add_amount = parseFloat(add_amount.toString().replace(/,/g, ""));

                let total_balance = parseFloat($("#total_balance").val().toString().replace(/,/g, ""));

                total_paid = parseFloat($("#total_paid").val().toString().replace(/,/g, ""));

                total_balance = total_balance - add_amount;

                total_paid = total_paid + add_amount;

                $("#total_balance").val(__number_f(total_balance, false, false, __currency_precision));

                $("#total_paid").val(total_paid);

                $(".total_balance").text(__number_f(total_balance, false, false, __currency_precision));

                $(".total_paid").text(__number_f(total_paid, false, false, __currency_precision));

                //    if (total_balance === 0) {

                //        $("#settlement_save_btn").removeClass("hide");

                //    }

                show_hide_excess_shortage_tab();

                updateBalanceButtonVisibility();

                calculateDenoms(add_amount);

                // Trigger payment updated event to update totals in payment tab
                $(document).trigger('payment:updated', { amount: add_amount });

            }

            window.add_payment = add_payment;

            function isPetroPdZeroBalance(value) {
                var n = parseFloat((value || '0').toString().replace(/,/g, '').replace(/\(/g, '-').replace(/\)/g, '').trim());
                if (isNaN(n)) { n = 0; }
                var precision = parseInt(window.__currency_precision || {{ (int) $currency_precision }}, 10);
                if (isNaN(precision) || precision < 0) { precision = 2; }
                var tolerance = 1 / Math.pow(10, precision);
                return Math.abs(n) < tolerance;
            }

            function forcePetroPdFinalizeButtonState() {
                var balanceText = $("#total_balance").val() || $(".total_balance").text() || "0";
                if (isPetroPdZeroBalance(balanceText)) {
                    $('#balance_to_operator_btn').addClass('hide').hide();
                    $('#settlement_save_btn').removeClass('hide').show().css('display', 'inline-block');
                } else {
                    $('#balance_to_operator_btn').removeClass('hide').show();
                    $('#settlement_save_btn').addClass('hide').hide();
                }
            }

            function updateBalanceButtonVisibility() {
                forcePetroPdFinalizeButtonState();
            }

            function show_hide_excess_shortage_tab() {
                // Get balance value - try multiple sources (input first, then display text)
                var balanceText = $("#total_balance").val() || $(".total_balance").text() || "0";

                // Normalize formatting (commas, parentheses for negatives)
                var clean = balanceText.toString()
                    .replace(/,/g, "")
                    .replace(/\(/g, "-")
                    .replace(/\)/g, "")
                    .trim();
                let total_balance = parseFloat(clean);

                // Handle NaN
                if (isNaN(total_balance)) {
                    total_balance = 0;
                }

                // unbind disabled links so they don't navigate
                $(document).find('li.disabled a').off('click');
                $('#excess_amount').prop('disabled', false);

                // When balance to operator or locked state is active we may want to
                // avoid changing the tab visibility/disabled state in some cases.
                var isBalanceToOperatorActive = window.__petro_balance_to_operator_active || window.__petro_balance_locked;

                // Show/hide tabs based on balance state (hide irrelevant ones when not needed)
                if (total_balance > 0) {
                    // positive -> shortage scenario
                    if (isBalanceToOperatorActive) {
                        // keep both visible but keep excess disabled so shortage stays clickable
                        $('.excess_tab').parents('li:first').show().removeClass('disabled');
                        $('.shortage_tab').parents('li:first').show().addClass('disabled');
                    } else {
                        $('.excess_tab').parents('li:first').hide().removeClass('disabled');
                        $('.shortage_tab').parents('li:first').show().removeClass('disabled');
                    }

                    $('.excess_add_btn').prop('disabled', true);
                    $('.shortage_add').prop('disabled', false);
                    $('#shortage_amount').prop('disabled', false);

                    // if the user was on excess, switch them back to shortage
                    var active_tab = $('#settlement_form .settlement_tabs li.active a').attr('href');
                    if (active_tab === '#excess_tab') {
                        $('.shortage_tab').tab('show');
                    }
                } else if (total_balance < 0) {
                    // negative -> excess scenario
                    if (isBalanceToOperatorActive) {
                        // during a balance-to-operator operation we don't want to
                        // inadvertently lock the shortage tab if the computed
                        // balance briefly flips sign due to floating point
                        // rounding.  Keep both tabs enabled so the user can see
                        // what just happened.
                        $('.shortage_tab').parents('li:first').show().removeClass('disabled');
                        $('.excess_tab').parents('li:first').show().addClass('disabled');
                    } else {
                        $('.shortage_tab').parents('li:first').hide().removeClass('disabled');
                        $('.excess_tab').parents('li:first').show().removeClass('disabled');
                    }

                    $('.shortage_add').prop('disabled', true);
                    $('#shortage_amount').prop('disabled', true);
                    $('.excess_add_btn').prop('disabled', false);

                    var active_tab = $('#settlement_form .settlement_tabs li.active a').attr('href');
                    if (active_tab === '#shortage_tab') {
                        $('.excess_tab').tab('show');
                    }
                } else {
                    // zero balance: check if Balance to Operator was used (keep the relevant tab active, lock the other)
                    var lastBalanceType = window.__petro_balance_to_operator_type;
                    var hasShortageAuto = $('#shortage_table tbody tr[data-auto-balance="1"]').length > 0;
                    var hasExcessAuto = $('#excess_table tbody tr[data-auto-balance="1"]').length > 0;
                    if (!lastBalanceType && (hasShortageAuto || hasExcessAuto)) {
                        lastBalanceType = hasShortageAuto ? 'shortage' : 'excess';
                    }
                    var useLockedState = (isBalanceToOperatorActive || window.__petro_balance_locked || hasShortageAuto || hasExcessAuto) && lastBalanceType;

                    if (useLockedState && lastBalanceType === 'shortage') {
                        $('#settlement_form .shortage_tab').parents('li:first').show().addClass('disabled');
                        $('#settlement_form .excess_tab').parents('li:first').show().removeClass('disabled');
                        $('.shortage_add').prop('disabled', true);
                        $('.excess_add_btn').prop('disabled', false);
                        $('#shortage_amount').prop('disabled', true);
                        $('#excess_amount').prop('disabled', false);
                    } else if (useLockedState && lastBalanceType === 'excess') {
                        $('#settlement_form .excess_tab').parents('li:first').show().addClass('disabled');
                        $('#settlement_form .shortage_tab').parents('li:first').show().removeClass('disabled');
                        $('.excess_add_btn').prop('disabled', true);
                        $('.shortage_add').prop('disabled', false);
                        $('#excess_amount').prop('disabled', true);
                        $('#shortage_amount').prop('disabled', false);
                    } else {
                        $('.excess_tab').parents('li:first').show().removeClass('disabled');
                        $('.shortage_tab').parents('li:first').show().removeClass('disabled');
                        $('.excess_add_btn').prop('disabled', false);
                        $('.shortage_add').prop('disabled', false);
                        $('#shortage_amount').prop('disabled', false);
                        $('#excess_amount').prop('disabled', false);
                    }
                }

                // show/hide finalize and balance to operator buttons using displayed/rounded zero balance
                forcePetroPdFinalizeButtonState();

                $(document).find('li.disabled a').on('click', function (e) {
                    e.preventDefault();
                    return false;
                });
            }

            var active_tab = $('#settlement_form .settlement_tabs li.active a').attr('href');
            if (active_tab === '#shortage_tab' || active_tab === '#excess_tab') {
                if (total_balance > 0) {
                    $('.shortage_tab').tab('show');
                } else if (total_balance < 0) {
                    $('.excess_tab').tab('show');
                }
            }

            $(document).find('li.disabled a').on('click', function (e) { e.preventDefault(); return false; });
            $(".excess_tab, .shortage_tab").off('click');

            $(document).find('#settlement_form .settlement_tabs li a').on('click', function (e) {

                setTimeout(() => {

                    $('#excess_amount').prop('disabled', false);

                }, 500);

                localStorage.setItem("settlement_tabs", $(this).attr('href'));

            });



            function delete_payment(delete_amount) {
                delete_amount = parseFloat(delete_amount.toString().replace(/,/g, ""));
                total_balance = parseFloat($("#total_balance").val().toString().replace(/,/g, ""));
                total_paid = parseFloat($("#total_paid").val().toString().replace(/,/g, ""));
                total_balance = total_balance + delete_amount;
                total_paid = total_paid - delete_amount;
                $("#total_balance").val(__number_f(total_balance, false, false, __currency_precision));
                $("#total_paid").val(total_paid);
                $(".total_balance").text(__number_f(total_balance, false, false, __currency_precision));
                $(".total_paid").text(__number_f(total_paid, false, false, __currency_precision));

                if (total_balance == 0) {
                    $("#settlement_save_btn").removeClass("hide");
                } else {
                    $("#settlement_save_btn").addClass("hide");
                }

                show_hide_excess_shortage_tab();
                updateBalanceButtonVisibility();
                calculateDenoms((0 - delete_amount));
                $(document).trigger('payment:updated', { amount: delete_amount });
            }

            function calculateTotal(table_name, class_name_td, output_element) {

                let total = 0.0;

                $(table_name + " tbody")

                    .find(class_name_td)

                    .each(function () {

                        total += parseFloat(__number_uf($(this).text()));

                    });

                $(output_element).text(__number_f(total, false, false, __currency_precision));

            }



            function myFloatNumber(i) {

                var value = Math.floor(i * 100) / 100;

                return value;

            }

            $(document).on("change", "#expense_category", function () {

                /*
                 * IS1982 #1: the route is registered as
                 *     get-expense-account-category-id/{category_id}
                 * with a REQUIRED parameter. Resetting the form after an expense is added
                 * fires this change event while the dropdown still reads "Please Select",
                 * so the URL ended in a bare slash, matched no route, and Laravel returned
                 * "The route get-expense-account-category-id could not be found".
                 */
                var categoryId = $(this).val();

                if (categoryId === null || categoryId === undefined || categoryId === "") {
                    $("#expense_account").empty();
                    return;
                }

                $.ajax({

                    method: "get",

                    url: "/get-expense-account-category-id/" + encodeURIComponent(categoryId),

                    data: {},

                    success: function (result) {

                        $("#expense_account").empty().append(`<option value="${result.expense_account_id}" selected>${result.name}</option>`);

                    },

                });

            });



            //Check Active Tab

            $(document).on("click", "#add_payment", function () {

                // Call immediately when modal opens
                show_hide_excess_shortage_tab();

                var myVar = setInterval(() => {

                    if ($('.add_payment').hasClass('in')) {

                        if ($("#cash_tab").hasClass("active")) {

                            $("#cash_amount").focus();

                        }

                    }

                    if ($("#cash_amount").is(":focus")) {

                        clearInterval(myVar);

                    }

                    show_hide_excess_shortage_tab();

                    calculateDenoms();

                }, 1000);

            });

            $(document).on("click", ".tabs", function () {

                var tab_id = $(this).attr("href");



                if (tab_id == "#expense_tab" && ($("#expense_tab").hasClass("active")) && ($(".total_balance").val()) <= 0) {

                    $('#expense_category').focus();

                    $('.excess_amount').prop('disabled', false);

                } else if (tab_id == "#credit_sales_tab" && ($("#credit_sales_tab").hasClass("active"))) {

                    $('#order_number').focus();

                } else {

                    $(tab_id + ' :input:enabled:visible:first').focus();

                    $('.excess_amount').prop('disabled', true);

                }

            });







            $('#show_bulk_tank').on('ifChecked', function (event) {

                $('.store_field').addClass('hide');

                $('.bulk_tank_field').removeClass('hide');

            });



            $('#show_bulk_tank').on('ifUnchecked', function (event) {

                $('.store_field').removeClass('hide');

                $('.bulk_tank_field').addClass('hide');

            });

            var total_balance = "{{$total_balance}}";

            var total_excess = "{{$total_excess}}";

            $('#excess_amount').prop('disabled', false);

            $(document).find('li.disabled a').off('click');
            $(".shortage_tab").parents("li:first").removeClass("disabled");
            $(".excess_tab").parents("li:first").removeClass("disabled");

            if (total_balance < 0) {

                // When balance is negative:



            } else {

                $(".shortage_tab").click(); // Automatically open the shortage tab

            }





            // if (total_balance === 0) {

            //     $("#settlement_save_btn").removeClass("hide");

            // } else {

            //     $("#settlement_save_btn").addClass("hide");

            // }

            // if(total_excess >0){

            //   $('#shortage_add').prop('disabled', true);

            //   $('#shortage_amount').prop('disabled', true);

            // }

            $('#shortage_amount').on('input', function () {

                if ($(this).val().length) {

                    //$('#excess_amount').prop('disabled', true);

                    $('.excess_amount_err').removeClass('hidden');

                } else {

                    //$('#excess_amount').prop('disabled', false);

                    $('.excess_amount_err').addClass('hidden');



                }

            });



            $('#excess_amount').on('input', function () {

                if ($(this).val().length) {

                    $('#shortage_amount').prop('disabled', true);

                    $('.shortage_amount_err').removeClass('hidden');



                } else {

                    $('#shortage_amount').prop('disabled', false);

                    $('.shortage_amount_err').addClass('hidden');

                }



            });

            $(document).find('li.disabled a').on('click', function (e) { e.preventDefault(); return false; });

            $(document).find('#settlement_form .settlement_tabs li a').on('click', function (e) {

                setTimeout(() => {

                    $('#excess_amount').prop('disabled', false);

                }, 500);

                localStorage.setItem("settlement_tabs", $(this).attr('href'));

            });

            if (localStorage.getItem("settlement_tabs")) {

                $("#settlement_form .settlement_tabs li a[href='" + localStorage.getItem("settlement_tabs") + "'").click();

            }

            // Ensure only one tab pane is visible (fixes Excess tab showing Credit Sales content)
            function ensureSettlementTabExclusive(tabId) {
                var $form = $('#settlement_form');
                if (!$form.length) return;
                var id = (tabId.indexOf('#') === 0) ? tabId : '#' + tabId;
                $form.find('.tab-content .tab-pane').removeClass('active');
                $form.find('.nav-tabs li').removeClass('active');
                $form.find(id).addClass('active');
                $form.find('a[href="' + id + '"]').parent('li').addClass('active');
            }

            $(document).off('click', '#balance_to_operator_btn').on('click', '#balance_to_operator_btn', function () {
                console.log("Balance to Operator button clicked");
                // Get the raw balance value from the hidden input
                var raw_balance_val = $('#total_balance').val() || '0';
                console.log("Raw balance value from input:", raw_balance_val);
                // Clean up: remove commas and handle parentheses as negative
                var raw_balance = raw_balance_val.toString().replace(/,/g, '').replace(/\(/g, '-').replace(/\)/g, '').trim();
                var balance = parseFloat(raw_balance);
                console.log("Parsed balance:", balance);

                // Fallback: if still invalid, try reading from displayed text
                if (isNaN(balance)) {
                    var display_text = $('.total_balance').text() || '0';
                    raw_balance = display_text.toString().replace(/,/g, '').replace(/\(/g, '-').replace(/\)/g, '').trim();
                    balance = parseFloat(raw_balance);
                }

                if (isNaN(balance)) {
                    toastr.error('Balance value is not valid.');
                    return;
                }

                if (balance === 0) {
                    toastr.info('No balance to add');
                    return;
                }

                // Existing settlement rule: balance > 0 => Shortage, balance < 0 => Excess
                var type = balance > 0 ? 'shortage' : 'excess';

                // Guard: if the auto-balance row already exists for this balance, don't add again.
                var has_auto_row = $('#shortage_table tbody tr[data-auto-balance=\"1\"]').length > 0
                    || $('#excess_table tbody tr[data-auto-balance=\"1\"]').length > 0;
                if (has_auto_row && window.__petro_auto_balance_state
                    && window.__petro_auto_balance_state.type === type
                    && window.__petro_auto_balance_state.balance === balance) {
                    toastr.info('Balance already added');
                    return;
                }

                window.__petro_auto_balance_pending = { type: type, balance: balance };

                var is_edit = $("#is_edit").val() ?? 0;

                var remove_existing_auto = function () {
                    var $row = $('#shortage_table tbody tr[data-auto-balance=\"1\"], #excess_table tbody tr[data-auto-balance=\"1\"]').first();
                    if ($row.length === 0) {
                        return $.Deferred().resolve(true).promise();
                    }

                    var $btn = $row.find('button.delete_shortage_payment, button.delete_excess_payment').first();
                    var url = $btn.data('href');
                    if (!url) {
                        $row.remove();
                        return $.Deferred().resolve(true).promise();
                    }

                    return $.ajax({
                        method: "delete",
                        url: url,
                        data: { is_edit: is_edit },
                    }).then(function (result) {
                        if (!(result && result.success)) {
                            toastr.error((result && result.msg) ? result.msg : 'Unable to remove existing balance row');
                            return $.Deferred().reject().promise();
                        }

                        $row.remove();
                        if (typeof delete_payment === 'function' && result.amount !== undefined) {
                            delete_payment(result.amount);
                        }
                        if ($btn.hasClass('delete_shortage_payment')) {
                            calculateTotal("#shortage_table", ".shortage_amount", ".shortage_total");
                        } else {
                            if (typeof rebuildExcessTable === 'function') {
                                rebuildExcessTable();
                            } else {
                                calculateTotal("#excess_table", ".excess_amount", ".excess_total");
                            }
                        }
                        return true;
                    }, function () {
                        toastr.error('Unable to remove existing balance row');
                        return $.Deferred().reject().promise();
                    });
                };

                remove_existing_auto().done(function () {
                    $('#shortage_amount').val('');
                    $('#excess_amount').val('');

                    if (type === 'shortage') {
                        var shortage_amount = Math.abs(balance);
                        $('#shortage_amount').val(shortage_amount.toFixed(2));

                        // Skip the normal add_payment() call inside the AJAX success handler
                        window.__petro_skip_add_payment = true;

                        // Pre-update UI BEFORE triggering the AJAX call
                        var current_total_paid = parseFloat($('#total_paid').val() || 0);
                        var new_total_paid = current_total_paid + shortage_amount;
                        var current_total_balance = parseFloat(($('#total_balance').val() || '0').replace(/,/g, ''));
                        var new_balance = current_total_balance - shortage_amount;

                        // Lock values so other handlers cannot overwrite them
                        window.__petro_locked_total_paid = new_total_paid;
                        window.__petro_locked_total_balance = new_balance;
                        window.__petro_balance_locked = true;

                        // Continuously enforce locked values until finalisation
                        if (window.__petro_balance_lock_interval) {
                            clearInterval(window.__petro_balance_lock_interval);
                        }
                        window.__petro_balance_lock_interval = setInterval(function () {
                            if (window.__petro_balance_locked) {
                                $('#total_paid').val(window.__petro_locked_total_paid);
                                $('#total_balance').val(window.__petro_locked_total_balance);
                                $('.total_paid').text(__number_f(window.__petro_locked_total_paid, false, false, __currency_precision));
                                $('.total_balance').text(__number_f(window.__petro_locked_total_balance, false, false, __currency_precision));
                                if (window.__petro_locked_total_balance === 0) {
                                    $('#balance_to_operator_btn').addClass('hide').hide();
                                    $('#settlement_save_btn').removeClass('hide').show().css('display', 'inline-block');
                                }
                            }
                        }, 100);

                        // Update hidden fields and display immediately
                        $('#total_paid').val(new_total_paid);
                        $('#total_balance').val(new_balance);
                        $('.total_paid').text(__number_f(new_total_paid, false, false, __currency_precision));
                        $('.total_balance').text(__number_f(new_balance, false, false, __currency_precision));

                        // Update buttons immediately
                        $('#balance_to_operator_btn').addClass('hide').hide();
                        $('#settlement_save_btn').removeClass('hide').show().css('display', 'inline-block');

                        // Set flags BEFORE any tab operations
                        window.__petro_balance_to_operator_active = true;
                        window.__petro_balance_to_operator_type = type;

                        // Disable Shortage tab and enable Excess tab
                        $('#settlement_form .shortage_tab').parents('li:first').addClass('disabled');
                        $('#settlement_form .excess_tab').parents('li:first').removeClass('disabled');

                        $('#shortage_note').val('Balance to Operator');

                        // Switch to Shortage tab
                        $('#settlement_form .shortage_tab').tab('show');

                        setTimeout(function () {
                            ensureSettlementTabExclusive('#shortage_tab');

                            if ($('#shortage_amount').val() != shortage_amount.toFixed(2)) {
                                $('#shortage_amount').val(shortage_amount.toFixed(2));
                            }

                            window.__petro_balance_to_operator_active = true;
                            $('#settlement_form .shortage_add').first().trigger('click');

                            setTimeout(function () {
                                window.__petro_balance_to_operator_active = false;
                            }, 500);

                            setTimeout(function () {
                                if (window.__petro_balance_locked) {
                                    $('#total_paid').val(window.__petro_locked_total_paid);
                                    $('#total_balance').val(window.__petro_locked_total_balance);
                                    $('.total_paid').text(__number_f(window.__petro_locked_total_paid, false, false, __currency_precision));
                                    $('.total_balance').text(__number_f(window.__petro_locked_total_balance, false, false, __currency_precision));
                                }
                                window.__petro_skip_add_payment = false;

                                var final_balance = parseFloat($('#total_balance').val() || 0);
                                if (final_balance === 0) {
                                    $('#balance_to_operator_btn').addClass('hide').hide();
                                    $('#settlement_save_btn').removeClass('hide').show().css('display', 'inline-block');
                                }

                                if (typeof show_hide_excess_shortage_tab === 'function') {
                                    show_hide_excess_shortage_tab();
                                }
                            }, 300);
                        }, 200);
                    } else {
                        // Excess (negative balance)
                        var excess_amount = -1 * Math.abs(balance);
                        $('#excess_amount').val(excess_amount.toFixed(2));

                        // Skip the normal add_payment() call inside the AJAX success handler
                        window.__petro_skip_add_payment = true;

                        // Pre-update UI BEFORE triggering the AJAX call
                        var current_total_paid = parseFloat($('#total_paid').val() || 0);
                        var new_total_paid = current_total_paid + excess_amount;
                        var current_total_balance = parseFloat(($('#total_balance').val() || '0').replace(/,/g, ''));
                        var new_balance = current_total_balance - excess_amount;

                        // Lock values so other handlers cannot overwrite them
                        window.__petro_locked_total_paid = new_total_paid;
                        window.__petro_locked_total_balance = new_balance;
                        window.__petro_balance_locked = true;

                        // Continuously enforce locked values
                        if (window.__petro_balance_lock_interval) {
                            clearInterval(window.__petro_balance_lock_interval);
                        }
                        window.__petro_balance_lock_interval = setInterval(function () {
                            if (window.__petro_balance_locked) {
                                $('#total_paid').val(window.__petro_locked_total_paid);
                                $('#total_balance').val(window.__petro_locked_total_balance);
                                $('.total_paid').text(__number_f(window.__petro_locked_total_paid, false, false, __currency_precision));
                                $('.total_balance').text(__number_f(window.__petro_locked_total_balance, false, false, __currency_precision));
                                if (window.__petro_locked_total_balance === 0) {
                                    $('#balance_to_operator_btn').addClass('hide').hide();
                                    $('#settlement_save_btn').removeClass('hide').show().css('display', 'inline-block');
                                }
                            }
                        }, 100);

                        // Update hidden fields and display immediately
                        $('#total_paid').val(new_total_paid);
                        $('#total_balance').val(new_balance);
                        $('.total_paid').text(__number_f(new_total_paid, false, false, __currency_precision));
                        $('.total_balance').text(__number_f(new_balance, false, false, __currency_precision));

                        // Update buttons immediately
                        $('#balance_to_operator_btn').addClass('hide').hide();
                        $('#settlement_save_btn').removeClass('hide').show().css('display', 'inline-block');

                        // Set flags BEFORE any tab operations
                        window.__petro_balance_to_operator_active = true;
                        window.__petro_balance_to_operator_type = type;

                        // Disable Excess tab and enable Shortage tab
                        $('#settlement_form .excess_tab').parents('li:first').addClass('disabled');
                        $('#settlement_form .shortage_tab').parents('li:first').removeClass('disabled');

                        $('#excess_note').val('Balance to Operator');

                        // Switch to Excess tab
                        $('#settlement_form .excess_tab').tab('show');

                        setTimeout(function () {
                            ensureSettlementTabExclusive('#excess_tab');

                            if ($('#excess_amount').val() != excess_amount.toFixed(2)) {
                                $('#excess_amount').val(excess_amount.toFixed(2));
                            }

                            window.__petro_balance_to_operator_active = true;
                            $('#settlement_form .excess_add_btn').first().trigger('click');

                            setTimeout(function () {
                                window.__petro_balance_to_operator_active = false;
                            }, 500);

                            setTimeout(function () {
                                if (window.__petro_balance_locked) {
                                    $('#total_paid').val(window.__petro_locked_total_paid);
                                    $('#total_balance').val(window.__petro_locked_total_balance);
                                    $('.total_paid').text(__number_f(window.__petro_locked_total_paid, false, false, __currency_precision));
                                    $('.total_balance').text(__number_f(window.__petro_locked_total_balance, false, false, __currency_precision));
                                }
                                window.__petro_skip_add_payment = false;

                                var final_balance = parseFloat($('#total_balance').val() || 0);
                                if (final_balance === 0) {
                                    $('#balance_to_operator_btn').addClass('hide').hide();
                                    $('#settlement_save_btn').removeClass('hide').show().css('display', 'inline-block');
                                }

                                if (typeof show_hide_excess_shortage_tab === 'function') {
                                    show_hide_excess_shortage_tab();
                                }
                            }, 300);
                        }, 200);
                    }
                });
            function loadCustomerDetails(customerId) {
                if (!customerId) return;

                $.ajax({
                    method: "GET",
                    url: "/petro-general/settlement/payment/get-customer-details/" + customerId,
                    data: {},
                    success: function (result) {
                        // Update outstanding and credit limit
                        $(".current_outstanding").text(result.total_outstanding);
                        $(".credit_limit").text(result.credit_limit);

                        // Enable fields if manual bill settlement
                        if (result.manual_bill_settlement == 1) {
                            $('.unit_discount').prop('disabled', false);
                            $('.credit_total_amount').prop('disabled', false);
                            $('.credit_discount_amount').prop('disabled', false);

                            // Set hidden input to 1
                            $('#total_amount_enable').val(1);
                        }

                        // Clear existing options
                        $("#customer_reference").empty();

                        if (result.customer_references && result.customer_references.length > 0) {
                            // Add "Please Select"
                            $("#customer_reference").append(`<option value="">Please Select</option>`);

                            // Append other vehicle options
                            result.customer_references.forEach(function (ref) {
                                $("#customer_reference").append(`<option value="` + ref.reference + `">` + ref.reference + `</option>`);
                            });
                        } else {
                            // If no vehicles, just show "No Vehicle No" selected
                            $("#customer_reference").append(`<option selected="selected" value="no_vehicle">No Vehicle No</option>`);
                        }

                        // Trigger change for Select2 if needed
                        $("#customer_reference").trigger('change.select2');
                    }
                });
            }

            // TASK 2: Dedicated print function for guaranteed instant printing (Settlement PD)
            window.triggerSettlementPrint = function (printContent) {
                console.log("triggerSettlementPrint called for Settlement PD");
                writeSettlementFinalizePrint(openSettlementFinalizePrintWindow(), printContent, null);
                return true;
            };

                // Precision variables already defined at the top of the script

            // S366 urgent fix: run once after the modal content is loaded so a displayed
            // Balance 0.00 immediately shows Finalize Settlement without requiring any tab/action.
            setTimeout(function () {
                if (typeof forcePetroPdFinalizeButtonState === 'function') {
                    forcePetroPdFinalizeButtonState();
                }
            }, 100);

            // Disallow comma in amount fields and warn user
            var amount_selectors = '.cust_input_number, .input_number, #cash_amount, #card_amount, #cheque_amount, #shortage_amount, #excess_amount, #credit_total_amount, #credit_discount_amount, #loan_payments_amount, #drawing_payments_amount, #customer_loans_amount, #expense_amount, #cash_deposit_amount, #unit_price, #unit_discount';
            var quantity_selectors = '#credit_sale_qty';

            $(document).on('input', amount_selectors + ', ' + quantity_selectors, function () {
                var val = $(this).val();
                if (val.includes(',')) {
                    toastr.error('Please enter the value without commas.');
                    $(this).val(val.replace(/,/g, ''));
                }
            });

            $(document).on('change', amount_selectors, function () {
                var val = $(this).val();
                if (val !== '') {
                    var parts = val.split('.');
                    if (parts[1] && parts[1].length > __currency_precision) {
                        parts[1] = parts[1].substring(0, __currency_precision);
                        $(this).val(parts.join('.'));
                    }
                }
            });

            $(document).on('change', quantity_selectors, function () {
                var val = $(this).val();
                if (val !== '') {
                    var parts = val.split('.');
                    if (parts[1] && parts[1].length > __quantity_precision) {
                        parts[1] = parts[1].substring(0, __quantity_precision);
                        $(this).val(parts.join('.'));
                    }
                }
            });
        });
        </script>
