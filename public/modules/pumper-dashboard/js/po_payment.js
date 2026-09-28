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
        currentInput.trigger('input').trigger('change');
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
    let validEntryCount = 0;
    let invalidEntryCount = 0;

    const todayDeposited = parseFloat($(".other_sale_grand_today_deposited_input").val()) || 0;

    console.log("Calculating totals. Today Deposited: " + todayDeposited);

    $('#other_sale_table tbody tr').each(function () {
        const $row = $(this);
        const baseline = other_sale_meter_baseline($row);
        const newMeterRaw = ($row.find('.other_sale_new_meter').val() || '').toString().trim();
        const newMeter = __read_number($row.find('.other_sale_new_meter'));
        const unitPrice = __read_number($row.find('.other_sale_unit_price'));

        let amount = 0;

        if (newMeterRaw !== '' && newMeter >= baseline) {
            const soldQty = newMeter - baseline;
            amount = soldQty * unitPrice;
            validEntryCount += 1;

            $row.find('.other_sale_span_sold_qty').text(soldQty.toFixed(3));
            $row.find('.other_sale_span_amount').text(__number_f(amount));
            $row.find('.other_sale_sold_qty').val(soldQty.toFixed(3));
            $row.find('.other_sale_amount').val(amount.toFixed(2));
        } else {
            $row.find('.other_sale_span_sold_qty').text(__number_f(0));
            $row.find('.other_sale_span_amount').text(__number_f(0));
            $row.find('.other_sale_sold_qty').val(__number_uf(__number_f(0)));
            $row.find('.other_sale_amount').val(__number_uf(__number_f(0)));

            // A blank row is optional: the operator can enter and finalize one
            // or more pumps at a time. Only a nonblank invalid reading blocks
            // Finalize.
            if (newMeterRaw !== '') {
                invalidEntryCount += 1;
            }
        }

        if (!isNaN(amount) && amount >= 0) {
            totalAmount += amount;
        } else {
            invalidEntryCount += 1;
        }
    });

    // Finalize when at least one valid new meter is present. Other blank pump
    // rows are left for the next entry during this same shift.
    $(".other_sale_finalize").prop(
        'disabled',
        validEntryCount === 0 || invalidEntryCount > 0
    );

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
    
    $("#payment_submit").attr("disabled", true);
    amount = parseFloat(amount);
    $.ajax({
        method: "POST",
        url: "/pumper-dashboard/pump-operator-payments",
        data: { amount, payment_type,slip_no,card_type, collection_form_no, pump_operator_id },
        success: function (result) {
            if (result.success) {
                toastr.success(result.msg);
                if ($(".view_modal").length) {
                    $(".view_modal").modal("hide");
                    
                    $(".collection_form_no").each(function() {
                        $(this).val(result.collection_form_no);
                    });
                    if (typeof showReloadConfirmationModal === "function") {
                        showReloadConfirmationModal(result.collection_form_no);
                    } else {
                        $("#cash_payments").modal("hide");
                        $(".modal-backdrop").remove();
                        $("body").removeClass("modal-open").css("padding-right", "");
                        $("#reloadConfirmationModalLabel").html("Confirm Another Payment for Form No. " + result.collection_form_no);
                        $("#reloadConfirmationModal").appendTo(document.body).modal("show");
                    }
                    // location.reload();
                }
            } else {
                toastr.error(result.msg);
                $("#payment_submit").attr("disabled", false);
            }
        },
    });
});

function reset() {
    document.getElementById("amount").value = "";
    document.getElementById("payment_type").value = "";
    $(".payment_type_btn").each(function (i, ele) {
        console.log("asdf");
        $(ele).removeClass("active");
        $(this).find(".payment_type_checkbox").attr("checked", false);
    });
}
$("#amount").focus();

/*
 * MA-002 (IS1954): the Amount Correct button no longer disables itself.
 *
 * It used to disable on click, and was re-enabled only when Save was clicked.
 * That left a dead end on the second payment:
 *
 *   click Amount Correct  ->  it disables itself
 *                         ->  enableSaveButton() runs, but that only enables
 *                             Save when #amount and #payment_type still hold
 *                             values - and the form has been cleared by the
 *                             first save
 *
 *   result: Amount Correct disabled, Save disabled, and the only way out is
 *           to reload the page.
 *
 * Nothing reads this button's disabled state - I checked. It was visual
 * feedback only, so removing the self-disable costs nothing and the
 * confirmation can be repeated for every payment, which is what was asked
 * for.
 *
 * THE DOUBLE-CLICK CONCERN THIS WAS PROBABLY GUARDING AGAINST is handled
 * better below: clicking it simply re-runs enableSaveButton(), which is
 * idempotent - running it twice has the same effect as running it once.
 */
$(".amount-correct").on('click', function () {
    // Kept usable. See the note above.
    $(this).prop("disabled", false).removeClass("btn-disabled");

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
    $(".amount-correct").prop("disabled", false).removeClass("btn-disabled");
});
