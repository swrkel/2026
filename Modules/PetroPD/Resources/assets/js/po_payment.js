function enterVal(val) {
    console.log(val);
    $("#amount").focus();
    if (val === "precision") {
        str = $("#amount").val();
        str = str + ".";
        $("#amount").val(str);
        return;
    }
    if (val === "backspace") {
        str = $("#amount").val();
        str = str.substring(0, str.length - 1);
        $("#amount").val(str);
        
        return;
    }
    let amount = $("#amount").val() + val;
    amount = amount.replace(",", "");
    $("#amount").val(amount);
    
}

    let currentInput = null;

    $(".other_sale_input").on('focus', function() {
        currentInput = $(this); 
    });
    
    function otherSaleEnterVal(val) {
        if (!currentInput) return;
    
        let str = currentInput.val(); 
    
        if (val === "precision") {
            if (!str.includes(".")) {
                str += ".";
                currentInput.val(str);
            }
            return;
        }
    
        if (val === "backspace") {
            str = str.substring(0, str.length - 1);
            currentInput.val(str);
            return;
        }
    
        str += val; 
        currentInput.val(str);
        currentInput.focus();
        currentInput.trigger('change');
    }
// $(document).on('input','.other_sale_input',function(){
//     var entered_val = __read_number($(this));
//     let row = $(this).closest('tr');
    
//     var unit_price = __read_number(row.find('.other_sale_unit_price'));
//     var starting_meter = __read_number(row.find('.other_sale_starting_meter'));
//     var sold_qty = entered_val - starting_meter;
    
//     var sold_amount = sold_qty * unit_price;
    
//     row.find('.other_sale_span_sold_qty').text(__number_f(sold_qty));
//     row.find('.other_sale_span_amount').text(__number_f(sold_amount));
    
//     row.find('.other_sale_sold_qty').val(__number_uf(__number_f(sold_qty)));
//     row.find('.other_sale_amount').val(__number_uf(__number_f(sold_amount)));
    
//     calculate_other_sales_totals();
    
    
// });

// function calculate_other_sales_totals(){
//     let totalAmount = 0;

//    var there_is_incomplete = 0;
   
//     $('#other_sale_table tbody tr').each(function () {
//         let amount = parseFloat($(this).find('.other_sale_amount').val());
        
//         if (!isNaN(amount)) {
//             totalAmount += amount;
//             if(amount < 0){
//                 there_is_incomplete += 1;
//             }
//         }else{
//             there_is_incomplete += 1;
//         }
//     });
    
    
//     if(there_is_incomplete > 0){
//         $(".other_sale_finalize").prop('disabled',true);
//     }else{
//         $(".other_sale_finalize").prop('disabled',false);
//     }
    
    
//     $('.other_sale_grand_total_amount').text(__number_f(totalAmount));
//     $('.other_sale_grand_total_amount_input').val(__number_uf(__number_f(totalAmount)));

//     let todayDeposited = $(".other_sale_grand_today_deposited_input").val(); 
//     let balanceToDeposit = totalAmount - todayDeposited;
    
//     $('.other_sale_grand_balance_to_deposit').text(__number_f(balanceToDeposit));
//     $('.other_sale_grand_balance_to_deposit_input').val(__number_uf(__number_f(balanceToDeposit)));
// }

//
function other_sale_meter_baseline($row) {
    var $b = $row.find('.other_sale_qty_baseline');
    if ($b.length) {
        var rawBaseline = parseFloat(($b.val() || '').toString().replace(',', '.'));
        if (!isNaN(rawBaseline)) {
            return rawBaseline;
        }
    }
    return __read_number($row.find('.other_sale_starting_meter'));
}

$(document).on('input','.other_sale_input',function(){
    var entered_val = __read_number($(this));
    let row = $(this).closest('tr');
    
    var unit_price = __read_number(row.find('.other_sale_unit_price'));
    var baseline = other_sale_meter_baseline(row);
    
    // Sold qty vs last entered (or assignment starting when no prior sale)
    var sold_qty = Math.max(0, entered_val - baseline);
    
    var sold_amount = sold_qty * unit_price;
    
    row.find('.other_sale_span_sold_qty').text(__number_f(sold_qty));
    row.find('.other_sale_span_amount').text(__number_f(sold_amount));
    
    row.find('.other_sale_sold_qty').val(__number_uf(__number_f(sold_qty)));
    row.find('.other_sale_amount').val(__number_uf(__number_f(sold_amount)));
    
    calculate_other_sales_totals();
});

function calculate_other_sales_totals() {
    let totalAmount = 0;
    let there_is_incomplete = 0;

    const todayDeposited = parseFloat($(".other_sale_grand_today_deposited_input").val()) || 0;

    console.log("Calculating totals. Today Deposited: " + todayDeposited);

    $('#other_sale_table tbody tr').each(function () {
        const $row = $(this);
        const baseline = other_sale_meter_baseline($row);
        const newMeter = __read_number($row.find('.other_sale_new_meter'));
        const unitPrice = __read_number($row.find('.other_sale_unit_price'));

        let amount = 0;

        if (newMeter > baseline) {
            const soldQty = newMeter - baseline;
            amount = soldQty * unitPrice;

            $row.find('.other_sale_span_sold_qty').text(soldQty.toFixed(3));
            $row.find('.other_sale_span_amount').text(__number_f(amount));
            $row.find('.other_sale_sold_qty').val(soldQty.toFixed(3));
            $row.find('.other_sale_amount').val(amount.toFixed(2));
        } else {
            $row.find('.other_sale_span_sold_qty').text(__number_f(0));
            $row.find('.other_sale_span_amount').text(__number_f(0));
            $row.find('.other_sale_sold_qty').val(__number_uf(__number_f(0)));
            $row.find('.other_sale_amount').val(__number_uf(__number_f(0)));
            if (newMeter === 0) {
                there_is_incomplete += 1;
            } else {
                // Entered but not above last reading — block finalize until corrected
                there_is_incomplete += 1;
            }
        }

        if (!isNaN(amount) && amount >= 0) {
            totalAmount += amount;
        } else {
            there_is_incomplete += 1;
        }
    });

    // Enable/disable finalize button
    $(".other_sale_finalize").prop('disabled', there_is_incomplete > 0);

    // Update total amount
    $('.other_sale_grand_total_amount').text(__number_f(totalAmount));
    $('.other_sale_grand_total_amount_input').val(__number_uf(totalAmount));

    console.log("Total Amount: " + totalAmount);

    // Calculate balance = totalAmount - todayDeposited
    const balanceToDeposit = totalAmount - todayDeposited;

    console.log("Balance to Deposit: " + balanceToDeposit);

    $('.other_sale_grand_balance_to_deposit').text(__number_f(balanceToDeposit));
    $('.other_sale_grand_balance_to_deposit_input').val(__number_uf(balanceToDeposit));
}
//




$(document).on("click", ".payment_type_btn", function () {
    // Real Time Entries screens use their own handler (active / locked). Do not return false
    // here — that would block the delegated handler and break payment-type UI.
    if (document.querySelector('.realtime-entries-payment-ui')) {
        return;
    }
    if (window.location.pathname.indexOf('/real-time-entries') !== -1) {
        return;
    }

    clicked_btn = $(this);

    // NOTE: Do not block based on siblings having .active — in this UI, .active means "greyed /
    // not selected", so *every* unselected sibling has .active and the old check always set
    // return_false=true, which prevented choosing Cash and opening the denominations modal.

    siblings = $(clicked_btn).siblings();

    siblings.each(function (i, ele) {
        $(ele).addClass("active");
        $(this).find(".payment_type_checkbox").attr("checked", false);
    });
    $("#payment_type").val($(this).find(".payment_type_checkbox").val());
    $(this).find(".payment_type_checkbox").attr("checked", true);
    $(clicked_btn).removeClass("active");

    // Cash denominations modal (must run after selection succeeds; Blade adds .cash_denoms_enter when enabled).
    var $cashDenomCb = $(clicked_btn).find(
        'input.payment_type_checkbox[value="cash"].cash_denoms_enter'
    );
    if ($cashDenomCb.length && $("#cash_payments").length) {
        setTimeout(function () {
            $("#cash_payments").modal({ backdrop: "static", keyboard: false });
        }, 0);
    }

});

$(document).on("click", "#payment_submit", function () {
    let amount = $("#amount").val();
    var slip_no = $("#sub_slip_no").val() ?? "";
    var card_type = $("#sub_card_type").val() ?? "";
    
    
    let payment_type = $("#payment_type").val();
    if (amount === "" || amount === undefined || amount === null) {
        toastr.error("Please enter amount");
        return false;
    }
    // remove the payment type check 10/18/2024
    // if (payment_type === "" || payment_type === undefined || payment_type === null) {
    //     toastr.error("Please select payment type");
    //     return false;
    // }
    
    
    if(payment_type == 'card'){
        // if(slip_no == ""){
        //     toastr.error("Please enter Slip No");
        //     return false;
        // }
        
        if(card_type == ""){
            toastr.error("Please enter Card Type");
            return false;
        }
    }

    var pump_operator_id = $("#pump_operator").val();
    if (!pump_operator_id) {
        toastr.error("Please select a Pump Operator");
        return false;
    }
    
    var collection_form_no = $("#collection_form_no").val() ?? "";
    var shift_id = $("#active_shift_id").val() ?? "";
    
    $("#payment_submit").attr("disabled", true);
    amount = parseFloat(amount);
    $.ajax({
        method: "POST",
        url: "/petropd/pump-operator-payments",
        data: { amount, payment_type,slip_no,card_type, collection_form_no, pump_operator_id, shift_id },
        success: function (result) {
            if (result.success) {
                toastr.success(result.msg);
                resetPaymentForm();
                refreshPaymentTables();
                if ($(".view_modal").length) {
                    $(".view_modal").modal("hide");
                    
                    $(".collection_form_no").each(function() {
                        $(this).val(result.collection_form_no);
                    });
                    $("#reloadConfirmationModalLabel").html("Confirm Another Payment for Form No. " + result.collection_form_no);
                    $("#reloadConfirmationModal").modal("show");
                    // location.reload();
                }
            } else {
                toastr.error(result.msg);
                $("#payment_submit").attr("disabled", false);
            }
        },
        /*
         * MA-002 (IS-1925 #1): recover the buttons when the request FAILS.
         *
         * There was no error callback here - and none on either ajax call in
         * this file. On success resetPaymentForm() runs and puts everything
         * back; on a failed request nothing ran at all, so:
         *
         *     Save stayed disabled and reading "Saving..."
         *     Amount Correct stayed disabled, because only
         *     resetPaymentForm() re-enables it
         *
         * which is exactly "the previous operation is not completed". The
         * operator then had no way forward except reloading the page.
         */
        error: function (xhr) {
            $("#payment_submit").attr("disabled", false);
            $(".amount-correct").prop("disabled", false).removeClass("btn-disabled");

            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.msg)
                ? xhr.responseJSON.msg
                : 'The payment could not be saved. Please try again.';

            if (typeof toastr !== 'undefined') {
                toastr.error(msg);
            }
        },
    });
});

function refreshPaymentTables() {
    [
        "#pump_operators_payment_summary_table",
        "#pump_operators_meters_with_payments_table",
        "#list_daily_collection_table",
        "#daily_collection_table"
    ].forEach(function (selector) {
        if ($.fn.DataTable && $.fn.DataTable.isDataTable(selector)) {
            $(selector).DataTable().ajax.reload(null, false);
        }
    });
}

function resetPaymentForm() {
    $("#amount").val("");
    $("#payment_type").val("");
    $("#sub_slip_no").val("");
    $("#sub_card_type").val("");
    /*
     * MA-002 (IS-1925 #1): after a reset the amount box is empty, so Amount
     * Correct goes back to LOCKED rather than enabled - see
     * syncAmountCorrectState() below.
     */
    syncAmountCorrectState();
    $("#payment_submit").attr("disabled", true).html($("#payment_submit").data("default-text") || $("#payment_submit").html());
    $(".payment_type_btn").each(function (i, ele) {
        $(ele).removeClass("active");
        $(this).find(".payment_type_checkbox").prop("checked", false).attr("checked", false);
    });
}

function reset() {
    resetPaymentForm();
}
$("#amount").focus();

$(".amount-correct").on('click', function () {
    // Disable the "Amount Correct?" button immediately
    $(this).prop("disabled", true);
    $(this).addClass("btn-disabled"); // optional visual feedback

    // Enable the Save button based on current inputs
    enableSaveButton();
});

function enableSaveButton() {
    console.log('correct button clicked0');
    let amount = $("#amount").val();
    let payment_type = $("#payment_type").val();
    console.log(amount);
    console.log(payment_type.length);

    if (amount == "" || amount == undefined || amount == null) {
        $("#payment_submit").attr("disabled", true);
    } else if ($("#payment_type").prop("checked") == false && payment_type.length == 0) {
        $("#payment_submit").attr("disabled", true);
    } else {
        $("#payment_submit").attr("disabled", false);
    }
}

$("#payment_submit").on('click', function () {
    // MA-002 (IS-1925 #1): follow the amount box rather than enabling
    // unconditionally - the amount may well be empty at this point.
    syncAmountCorrectState();
});


/*
 * MA-002 (IS-1925 #1): Amount Correct is locked until an amount is entered.
 *
 * It used to start ENABLED and only ever disable itself when clicked, so an
 * operator could confirm an amount before typing one - and after a save it
 * could be left disabled with nothing to re-enable it.
 *
 * Now its state follows the amount box: empty or zero means locked, any real
 * amount means active. Bound to input, change and keyup so it responds to
 * typing, to the on-screen keypad, and to a value set by script.
 */
function syncAmountCorrectState() {
    var raw = String($("#amount").val() || '').replace(/,/g, '').trim();
    var amount = parseFloat(raw);
    var ready = raw !== '' && !isNaN(amount) && amount > 0;

    $(".amount-correct")
        .prop("disabled", !ready)
        .toggleClass("btn-disabled", !ready);
}

$(document).on('input change keyup', '#amount', syncAmountCorrectState);

$(function () {
    syncAmountCorrectState();
});
