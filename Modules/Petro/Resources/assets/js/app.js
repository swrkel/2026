//meter sale tab
/*
$('#pump_operator_id').change(function () {

    let store_id = $("select#store_id option").filter(":selected").val();

    var op_id = $(this).val();

    if ($(this).val() === '' || $(this).val() === undefined) {
        toastr.error('Please Select the Pump operator and continue');
    } else {

        $.ajax({
            method: 'get',
            url: "/petro/settlement/get_pumps/" + op_id,
            data: {
                settlement_no: $('#settlement_no').val(),
                location_id: $('#location_id').val(),
                pump_operator_id: $('#pump_operator_id').val(),
                transaction_date: $('#transaction_date').val(),
                work_shift: $('#work_shift').val(),
                note: $('#note').val(),
            },
            success: function (result) {
                if (result.success == false) {
                    toastr.error(result.msg);
                    return false;
                }

                if (result.should_reload > 0) {
                    window.location.reload();
                }

                $('#below_box *').attr('disabled', false);
                if (store_id == null || store_id == "") {
                    $('.other_sale_fields#item').attr('disabled', true);
                }

                // Select the dropdown menu
                var dropdown = $('#pump_no');

                // Clear any existing options
                dropdown.empty();

                // Add the "Please select" option as the first option
                dropdown.append($('<option>').text('Please select').val(''));

                // Iterate through the object and add options to the dropdown
                $.each(result.pumps, function (key, value) {
                    dropdown.append($('<option>').text(value).val(key));
                });
            },
        });


    }
});
*/
$(document).ready(function () {
    updateTotalSoldQty();
    var settlement_id = 0;
    let store_id = $("select#store_id option").filter(":selected").val();
    if ($('#pump_operator_id').val() === '' || $('#pump_operator_id').val() === undefined) {
        $('#below_box *').attr('disabled', true);
    } else {
        $('#below_box *').attr('disabled', false);
        if (store_id == null || store_id == "") {
            $('.other_sale_fields#item').attr('disabled', true);
        }
    }
});
var tank_qty = 0;
var code = '';
var price = 0.0;
var product_name = '';
var pump_name = '';
var pump_closing_meter = 0.0;
var pump_starting_meter = 0.0;
var meter_sale_total = parseFloat($('#meter_sale_total').val());
var product_id = null;
var pump_id = null;

function getCurrentSettlementShiftId() {
    var workShiftVal = $('#work_shift').val();
    if (Array.isArray(workShiftVal) && workShiftVal.length > 0) {
        return workShiftVal[0];
    }
    if (workShiftVal !== undefined && workShiftVal !== null && workShiftVal !== '') {
        return workShiftVal;
    }

    var shiftVal = $('#shift_number').val();
    if (Array.isArray(shiftVal) && shiftVal.length > 0) {
        return shiftVal[0];
    }
    if (shiftVal !== undefined && shiftVal !== null && shiftVal !== '') {
        return shiftVal;
    }

    return null;
}

$(document).on('change', '#pump_no', function () {
    if (window.__directSettlementPumpSyncing) return;
    pump_closing_meter = 0.0;
    pump_starting_meter = 0.0;
    var shift_id = getCurrentSettlementShiftId();
    var pump_no = $(this).val();

    if (pump_no) {
        var selectedPumpText = $.trim($(this).find('option:selected').text() || '');
        $(this).attr('data-selected-pump-id', String(pump_no))
            .attr('data-selected-pump-text', selectedPumpText);
        $('#meter_sale_selected_pump_id').val(String(pump_no));
        $('#meter_sale_selected_pump_text').val(selectedPumpText);
        if (typeof window.__rememberDirectSettlementPumpSelection === 'function') {
            window.__rememberDirectSettlementPumpSelection();
        }
    }

    if (!pump_no) {
        $('#pump_starting_meter').val('');
        $('#pump_closing_meter').val('');
        $('#meter_sale_unit_price').val('');
        $('#meter_sale_product_id').val('');
        $('#sold_qty').val('').prop('disabled', true);
        $('#bulk_sale_meter').val(0);
        $(document).trigger('petro:meter-sale-pump-loaded');
        return;
    }


    $.ajax({
        method: 'get',
        // url: '/petro/settlement/get-pump-details/' + $(this).val(),
        url: shift_id
            ? '/petro/settlement/get-pump-details/' + pump_no + '/' + shift_id
            : '/petro/settlement/get-pump-details/' + pump_no,
        data: {},
        success: function (result) {
            if (String($('#pump_no').val() || '') !== String(pump_no || '')) return;

            $('#pump_starting_meter').val(result.colsing_value);

            if (result.po_closing > 0) {
                $('#pump_closing_meter').val(result.po_closing);
                // $("#pump_closing_meter").prop('readonly',true);
                $('#pump_closing_meter').trigger('change');
                $("#is_from_pumper").val(1);

                $('#assignment_id').val(result.assignment_id);
                $('#pumper_entry_id').val(result.pumper_entry_id);

            } else {
                $('#pump_closing_meter').val("");
                // $("#pump_closing_meter").prop('readonly',false);
                $("#is_from_pumper").val(0);
            }

            if (result.po_testing > 0) {
                $('#testing_qty').val(result.po_testing);
                $("#testing_qty").prop('readonly', true);
                $('#testing_qty').trigger('change');
            } else {
                $('#testing_qty').val(0);
                $("#testing_qty").prop('readonly', false);
            }


            pump_starting_meter = parseFloat(result.colsing_value);
            tank_qty = result.tank_remaing_qty;
            code = result.product.sku;
            price = result.product.default_sell_price;
            product_name = result.product.name;
            pump_name = result.pump_name;
            pump_id = result.pump_id;
            product_id = result.product_id;
            $('#meter_sale_product_id').val(result.product_id || '');
            if (result.bulk_sale_meter == '1') {
                $('#bulk_sale_meter').val(1);
                $('.pump_starting_meter_div').addClass('hide');
                $('.pump_closing_meter_div').addClass('hide');
                // Bulk quantity is entered manually. Clear any quantity left by a
                // previously selected normal pump so Add cannot use stale data.
                $('#sold_qty').val('').prop('disabled', false);
            } else {
                $('#bulk_sale_meter').val(0);
                $('.pump_starting_meter_div').removeClass('hide');
                $('.pump_closing_meter_div').removeClass('hide');
                $('#sold_qty').prop('disabled', true);
            }
            $('#meter_sale_unit_price').val(price);
            $(document).trigger('petro:meter-sale-pump-loaded', [result]);
        },
    });
});

$(document).on('change', '#pump_no_pd', function () {
    pump_closing_meter = 0.0;
    pump_starting_meter = 0.0;
    var shift_id = getCurrentSettlementShiftId();
    var pump_no = $(this).val();

    if (!pump_no) {
        $('#pump_starting_meter').val('');
        $('#pump_closing_meter').val('');
        $('#meter_sale_unit_price').val('');
        return;
    }


    $.ajax({
        method: 'get',
        // url: '/petro/settlement/get-pump-details/' + $(this).val(),
        url: shift_id
            ? '/petro/settlement-pd/get-pump-details/' + pump_no + '/' + shift_id
            : '/petro/settlement-pd/get-pump-details/' + pump_no,
        data: {},
        success: function (result) {
            console.log('123');
            $('#pump_starting_meter').val(result.colsing_value);

            if (result.po_closing > 0) {
                $('#pump_closing_meter').val(result.po_closing);
                // $("#pump_closing_meter").prop('readonly',true);
                $('#pump_closing_meter').trigger('change');
                $("#is_from_pumper").val(1);

                $('#assignment_id').val(result.assignment_id);
                $('#pumper_entry_id').val(result.pumper_entry_id);

            } else {
                $('#pump_closing_meter').val("");
                // $("#pump_closing_meter").prop('readonly',false);
                $("#is_from_pumper").val(0);
            }

            if (result.po_testing > 0) {
                $('#testing_qty').val(result.po_testing);
                $("#testing_qty").prop('readonly', true);
                $('#testing_qty').trigger('change');
            } else {
                $('#testing_qty').val(0);
                $("#testing_qty").prop('readonly', false);
            }


            pump_starting_meter = parseFloat(result.colsing_value);
            tank_qty = result.tank_remaing_qty;
            code = result.product.sku;
            price = result.product.default_sell_price;
            product_name = result.product.name;
            pump_name = result.pump_name;
            pump_id = result.pump_id;
            product_id = result.product_id;
            if (result.bulk_sale_meter == '1') {
                $('#bulk_sale_meter').val(1);
                $('.pump_starting_meter_div').addClass('hide');
                $('.pump_closing_meter_div').addClass('hide');
                $('#sold_qty').prop('disabled', false);
            } else {
                $('#bulk_sale_meter').val(0);
                $('.pump_starting_meter_div').removeClass('hide');
                $('.pump_closing_meter_div').removeClass('hide');
                $('#sold_qty').prop('disabled', true);
            }
            $('#meter_sale_unit_price').val(price);
        },
    });
});


$(document).on('change', '#pump_closing_meter', function () {
    pump_closing_meter = parseFloat($(this).val());
    pump_starting_meter = parseFloat($('#pump_starting_meter').val());
    // Sold Qty = gross (closing - starting); testing is applied only when Add is clicked
    sold_qty = (pump_closing_meter - pump_starting_meter).toFixed(3);

    if (pump_closing_meter < pump_starting_meter) {
        toastr.error('Enter closing meter greater than the starting meter');
        $(this).val('');
    }
    // I commented this line -- Bekzod Erkinov
    // else if (tank_qty >= sold_qty) {
    //     toastr.error('Out of Stock');
    //     $(this).val('');
    // }
    else {
        $('#sold_qty').val(sold_qty);
    }
});

// Do not update Sold Qty when Testing Qty changes; reduction is applied only on Add
$(document).on('change', '#testing_qty', function () {
    // No-op: Sold Qty stays as (closing - starting) until user clicks Add
});



// Flag to prevent duplicate meter sale submissions on double-click
let isMeterSaleSubmitting = false;

/*
$(document).off('click', '.btn_meter_sale').off('click.petro_meter_sale').on('click.petro_meter_sale', '.btn_meter_sale', function () {
    if (window.isMeterSaleSubmitting) {
        return false;
    }
    window.isMeterSaleSubmitting = true;
    $(this).prop('disabled', true).addClass('disabled');
    var $button = $(this);

    var testing_qty = $('#testing_qty').val();
    var is_from_pumper = $("#is_from_pumper").val() || 0;

    var assignment_id = $("#assignment_id").val() || 0;
    var pumper_entry_id = $("#pumper_entry_id").val() || 0;

    var meter_sale_discount = $('#meter_sale_discount').val();
    var meter_sale_discount_type = $('#meter_sale_discount_type').val();
    var meter_sale_discount_type_text = '';
    if ($('#meter_sale_discount_type').val() !== '') {
        meter_sale_discount_type_text = $('#meter_sale_discount_type option[value="' + $('#meter_sale_discount_type').val() + '"]').text();
    }
    // Sold Qty field is already chargeable (Closing - Starting - Testing); do not subtract testing again
    var sold_qty = parseFloat($('#sold_qty').val()) || 0;
    var total_qty = sold_qty + (parseFloat(testing_qty) || 0);
    sub_total = parseFloat(sold_qty) * parseFloat(price);

    if (!meter_sale_discount) {
        meter_sale_discount = 0;
    }
    var meter_sale_discount_amount = sub_total - calculate_discount(meter_sale_discount_type, meter_sale_discount, sub_total);
    var meter_sale_id = null;

    $.ajax({
        method: 'post',
        url: '/petro/settlement/save-meter-sale',
        data: {
            settlement_no: $('#settlement_no').val(),
            location_id: $('#location_id').val(),
            pump_operator_id: $('#pump_operator_id').val(),
            transaction_date: $('#transaction_date').val(),
            work_shift: $('#work_shift').val(),
            note: $('#note').val(),
            pump_id: pump_id,
            starting_meter: pump_starting_meter,
            closing_meter: $('#pump_closing_meter').val(),
            product_id: product_id,
            price: price,
            qty: sold_qty,
            discount: meter_sale_discount,
            discount_type: meter_sale_discount_type,
            discount_amount: meter_sale_discount_amount,
            testing_qty: testing_qty,
            sub_total: sub_total,
            is_edit: is_edit,
            is_from_pumper: is_from_pumper,
            assignment_id: assignment_id,
            pumper_entry_id: pumper_entry_id,
            shift_id: getCurrentSettlementShiftId()
        },
        success: function (result) {
            if (!result.success) {
                toastr.error(result.msg);
                return false;
            }

            if (result.settlement_no) {
                $('#settlement_no').val(result.settlement_no);
            }

            refresh_settlement_totals();
            $('#pump_no')
                .find('option[value=' + pump_id + ']')
                .remove();

            meter_sale_id = result.meter_sale_id;
            settlement_id = result.settlement_id;
            $('#active_settlement_id').val(settlement_id);

            $('#note, #work_shift, #transaction_date, #pump_operator_id, #location_id').change(function () {



                $.ajax({
                    method: 'put',
                    url: "/petro/settlement/" + settlement_id,
                    data: {
                        note: $('#note').val(),
                        work_shift: $('#work_shift').val(),
                        transaction_date: $('#transaction_date').val(),
                        pump_operator_id: $('#pump_operator_id').val(),
                        location_id: $('#location_id').val()
                    },
                    success: function (result) {
                        if (result.success == 1) {
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            })


            meter_sale_totals = __number_f(sub_total);
            var with_disc = __number_f(sub_total + meter_sale_discount);

            sold_qty = (sold_qty);
            pump_starting_meter = parseFloat(pump_starting_meter) || 0.0;
            pump_closing_meter = parseFloat(pump_closing_meter) || 0.0;
            var rowHTML = `
                <tr> 
                    <td>` +
                code +
                `</td>
                    <td><span class="product_name">` +
                product_name +
                `</span></td>
                    <td>` +
                pump_name +
                `</td>
                    <td>` +
                pump_starting_meter.toFixed(3) +
                `</td>
                    <td>` +
                pump_closing_meter.toFixed(3) +
                `</td>
                    <td>` +
                __number_f(price) +
                `</td>
                    
                    <td>
                     <span class="sold_qty">` +
                formatNumber(sold_qty) +
                `</span>
                    </td>
                    <td>` +
                meter_sale_discount_type_text +
                `</td>
                    <td>` +
                __number_f(meter_sale_discount) +
                `</td>
                    <td>` +
                formatNumber(testing_qty)
                +
                `</td>
                    <td>` +
                formatNumber(total_qty)
                +
                `</td>
                    <td>` +
                __number_f(sub_total) +
                `</td>
                    <td class="text-right">` +
                __number_f(meter_sale_discount_amount) +
                `</td>` +
                `<td>` +
                `<button class="btn btn-xs btn-primary get_meter_sale_from" data-type="edit" data-href="/petro/settlement/get-meter-sale-form/` +
                meter_sale_id +
                `"><i class="fa fa-edit"></i></button>` +
                `<button class="btn btn-xs btn-danger delete_meter_sale" data-href="/petro/settlement/delete-meter-sale/` +
                meter_sale_id +
                `"><i class="fa fa-times"></i></button>
                    </td>
                </tr>
            `;
            $('#meter_sale_table tbody').prepend(rowHTML);
            $('#pump_operator_meter_sale_table tbody').prepend(rowHTML);
            if ($.fn.DataTable.isDataTable('#pump_operator_meter_sale_table')) {
                // DataTable is already initialized
                console.log("pump_operator_meter_sale_table initialized");
                $('#pump_operator_meter_sale_table').DataTable().ajax.reload(null, false);  // `false` prevents page reset
            } else {
                console.log("pump_operator_meter_sale_table not initialized");
            }

            // Clear form fields after Add
            $('#pump_no').val('').trigger('change');
            $('#pump_starting_meter').val('');
            $('#pump_closing_meter').val('');
            $('#sold_qty').val('');
            $('#meter_sale_unit_price').val('');
            $('#testing_qty').val('');
            $('.meter_sale_fields').not('select').val('');

            refresh_settlement_totals();
            updateTotalSoldQty();
        },
        error: function () {
            // Re-enable button on error (with delay to prevent double-click)
            setTimeout(function () {
                window.isMeterSaleSubmitting = false;
                $button.prop('disabled', false).removeClass('disabled');
            }, 500);
        },
        complete: function () {
            // Re-enable after a short delay so double-click cannot submit twice
            setTimeout(function () {
                window.isMeterSaleSubmitting = false;
                $button.prop('disabled', false).removeClass('disabled');
            }, 500);
        }
    });
});

$(document).off('click.petro_meter_sale_pd').on('click.petro_meter_sale_pd', '.btn_meter_sale_pd', function () {
    if (window.isMeterSaleSubmitting) {
        return false;
    }
    window.isMeterSaleSubmitting = true;
    var $btn = $(this);
    $btn.prop('disabled', true).addClass('disabled');

    var testing_qty = $('#testing_qty').val();
    var is_from_pumper = $("#is_from_pumper").val() || 0;

    var assignment_id = $("#assignment_id").val() || 0;
    var pumper_entry_id = $("#pumper_entry_id").val() || 0;

    var meter_sale_discount = $('#meter_sale_discount').val();
    var meter_sale_discount_type = $('#meter_sale_discount_type').val();
    var meter_sale_discount_type_text = '';
    if ($('#meter_sale_discount_type').val() !== '') {
        meter_sale_discount_type_text = $('#meter_sale_discount_type option[value="' + $('#meter_sale_discount_type').val() + '"]').text();
    }
    // Sold Qty field is already chargeable (Closing - Starting - Testing); do not subtract testing again
    var sold_qty = parseFloat($('#sold_qty').val()) || 0;
    var total_qty = sold_qty + (parseFloat(testing_qty) || 0);
    sub_total = parseFloat(sold_qty) * parseFloat(price);

    if (!meter_sale_discount) {
        meter_sale_discount = 0;
    }
    var meter_sale_discount_amount = sub_total - calculate_discount(meter_sale_discount_type, meter_sale_discount, sub_total);
    var meter_sale_id = null;

    $.ajax({
        method: 'post',
        url: '/petro/settlement-pd/save-meter-sale',
        data: {
            settlement_no: $('#settlement_no').val(),
            location_id: $('#location_id').val(),
            pump_operator_id: $('#pump_operator_id').val(),
            transaction_date: $('#transaction_date').val(),
            work_shift: $('#work_shift').val(),
            note: $('#note').val(),
            pump_id: pump_id,
            starting_meter: pump_starting_meter,
            closing_meter: $('#pump_closing_meter').val(),
            product_id: product_id,
            price: price,
            qty: sold_qty,
            discount: meter_sale_discount,
            discount_type: meter_sale_discount_type,
            discount_amount: meter_sale_discount_amount,
            testing_qty: testing_qty,
            sub_total: sub_total,
            is_edit: is_edit,
            is_from_pumper: is_from_pumper,
            assignment_id: assignment_id,
            pumper_entry_id: pumper_entry_id,
            shift_id: getCurrentSettlementShiftId()
        },
        success: function (result) {
            if (!result.success) {
                toastr.error(result.msg);
                return false;
            }

            $('#pump_no')
                .find('option[value=' + pump_id + ']')
                .remove();

            meter_sale_id = result.meter_sale_id;
            settlement_id = result.settlement_id;
            $('#active_settlement_id').val(settlement_id);

            $('#note, #work_shift, #transaction_date, #pump_operator_id, #location_id').change(function () {



                $.ajax({
                    method: 'put',
                    url: "/petro/settlement/" + settlement_id,
                    data: {
                        note: $('#note').val(),
                        work_shift: $('#work_shift').val(),
                        transaction_date: $('#transaction_date').val(),
                        pump_operator_id: $('#pump_operator_id').val(),
                        location_id: $('#location_id').val()
                    },
                    success: function (result) {
                        if (result.success == 1) {
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });

            $('#pump_no').val('').trigger('change');
            $('#pump_starting_meter').val('');
            $('#pump_closing_meter').val('');
            $('#sold_qty').val('');
            $('#meter_sale_unit_price').val('');
            $('#testing_qty').val('');
            $('.meter_sale_fields').not('select').val('');

            if (typeof loadMeterSalesData === 'function') {
                loadMeterSalesData();
            }
            if (typeof refresh_settlement_totals === 'function') {
                refresh_settlement_totals();
            }
            updateTotalSoldQty();
        },
        error: function () {
            setTimeout(function () {
                window.isMeterSaleSubmitting = false;
                $btn.prop('disabled', false).removeClass('disabled');
            }, 500);
        },
        complete: function () {
            setTimeout(function () {
                window.isMeterSaleSubmitting = false;
                $btn.prop('disabled', false).removeClass('disabled');
            }, 500);
        }
    });
});
*/

function updateTotalSoldQty() {
    var productSoldQty = {};

    $('#meter_sale_table tbody tr').each(function () {
        var productName = $(this).find('.product_name').text();

        var soldQty = parseFloat($(this).find('span.sold_qty').text().replace(',', ''));

        if (!isNaN(soldQty)) {
            if (productSoldQty[productName] === undefined) {
                productSoldQty[productName] = soldQty;
            } else {
                productSoldQty[productName] += soldQty;
            }
        }
    });

    var productSummaryHtml = '';
    for (var productName in productSoldQty) {
        productSummaryHtml += productName + ' = ' + __number_f(productSoldQty[productName]) + '<br>';
    }

    // Set the HTML content in the product_summary element
    $('.product_summary').html(productSummaryHtml);
}

// product_summary


function formatNumber(number) {
    if (typeof number !== 'number') {
        number = parseFloat(number); // Or use Number(number)
    }

    if (!isNaN(number)) {
        return number.toFixed(3); // Or however many decimals you need
    } else {
        return 'Invalid number';
    }
}



function calculate_discount(discount_type, discount_value, amount) {
    if (discount_type == 'fixed') {
        return parseFloat(discount_value) || 0;
    }
    if (discount_type == 'percentage') {
        return ((amount * parseFloat(discount_value)) / 100) || 0;
    }
    return 0;
}

$(document).on('click', '.delete_meter_sale', function () {
    url = $(this).data('href');
    var meterSaleId = (url && typeof url === 'string') ? url.split('/').pop() : null;
    tr = $(this).closest('tr');
    var is_edit = $("#is_edit").val() || 0;
    $.ajax({
        method: 'delete',
        url: url,
        data: { is_edit },
        success: function (result) {
            if (result.success) {
                toastr.success(result.msg);
                // Remove row from BOTH tables (same sale is prepended to meter_sale_table and pump_operator_meter_sale_table)
                if (meterSaleId) {
                    $('.delete_meter_sale[data-href*="delete-meter-sale/' + meterSaleId + '"]').closest('tr').remove();
                } else {
                    tr.remove();
                }
                // Recalc meter sales total from remaining rows (sum After Discount; no accumulation)
                var $dt = $('#pump_operator_meter_sale_table');
                var $static = $('#meter_sale_table');
                if (typeof sum_table_col === 'function') {
                    var newTotal = 0;
                    if ($dt.length && $dt.find('tbody tr').length) {
                        newTotal = sum_table_col($dt, 'discount_amount');
                        $('#footer_list_meter_sales_amount').val(newTotal).text(newTotal);
                    } else if ($static.length && $static.find('tbody tr').length) {
                        newTotal = sum_table_col($static, 'discount_amount');
                    }
                    if (!isNaN(newTotal)) {
                        $('#meter_sale_total').val(parseFloat(String(newTotal).replace(/,/g, '')) || 0);
                        calculate_payment_tab_total();
                    }
                }

                refresh_settlement_totals();
                // If DataTable is in use, reload so internal state matches DOM
                if ($.fn.DataTable && $.fn.DataTable.isDataTable('#pump_operator_meter_sale_table')) {
                    $('#pump_operator_meter_sale_table').DataTable().ajax.reload(null, false);
                }
            } else {
                toastr.error(result.msg);
            }
        },
    });
});

//other sale tab
other_sale_code = null;
other_sale_product_name = null;
other_sale_price = 0.0;
other_sale_qty = 0.0;
other_sale_discount = 0.0;
other_sale_total = parseFloat($('#other_sale_total').val());
$('#item').change(function () {
    let item_id = $(this).val();
    if (item_id) {
        $.ajax({
            method: 'get',
            url: '/petro/settlement/get_balance_stock_by_id/' + item_id,
            data: {
                store_id: $("select#store_id option").filter(":selected").val(),
                location_id: $("select#location_id option").filter(":selected").val()
            },
            success: function (result) {
                $('#balance_stock').val(result.balance_stock);
                $('#other_sale_price').val(result.price);
                other_sale_code = result.code;
                other_sale_product_name = result.product_name;
                other_sale_price = result.price;
            },
        });
    }

});
function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}
// Flag to prevent duplicate other sale submissions on double-click
let isOtherSaleSubmitting = false;

$(document).off('click', '.btn_other_sale').off('click.petro_other_sale').on('click.petro_other_sale', '.btn_other_sale', function (e) {
    if (window.isOtherSaleSubmitting) {
        return false;
    }
    window.isOtherSaleSubmitting = true;
    $(this).prop('disabled', true).addClass('disabled');
    var $button = $(this);

    e.preventDefault();

    var allowoverselling = $("#allowoverselling").val();
    var other_sale_qty = $('#other_sale_qty').val();
    var balance_stock = $('#balance_stock').val();

    if (parseFloat(other_sale_qty) > parseFloat(balance_stock) && allowoverselling == 'true') {
        toastr.error('Out of Stock');
        $('#other_sale_qty').focus();
        isOtherSaleSubmitting = false;
        $button.prop('disabled', false).removeClass('disabled');
        return false;
    }

    var other_sale_discount = $('#other_sale_discount').val();
    var other_sale_discount_type = $('#other_sale_discount_type').val();
    var sub_total = parseFloat(other_sale_qty) * parseFloat(other_sale_price);
    if (!other_sale_discount_type) {
        other_sale_discount_type = 'fixed';
    }
    var other_sale_discount_amount = calculate_discount(other_sale_discount_type, other_sale_discount, sub_total);

    var other_sale_id = null;
    let sub = parseFloat(sub_total);
    let other_sale_total = parseFloat($('#other_sale_total').val().replace(',', ''));

    let with_discount = sub_total - other_sale_discount_amount;
    other_sale_total = other_sale_total + with_discount;
    var is_edit = $("#is_edit").val() || 0;

    $.ajax({
        method: 'post',
        url: '/petro/settlement/save-other-sale',
        data: {
            active_settlement_id: $('#active_settlement_id').val(),
            settlement_no: $('#settlement_no').val(),
            location_id: $('#location_id').val(),
            pump_operator_id: $('#pump_operator_id').val(),
            transaction_date: $('#transaction_date').val(),
            work_shift: $('#work_shift').val(),
            note: $('#note').val(),
            product_id: $('#item').val(), //item is product in whole page
            store_id: $('#store_id').val(),
            price: other_sale_price,
            qty: other_sale_qty,
            balance_stock: balance_stock,
            discount: other_sale_discount,
            discount_type: other_sale_discount_type,
            discount_amount: other_sale_discount_amount,
            sub_total: sub,
            is_edit: is_edit
        },
        success: function (result) {
            if (!result.success) {
                toastr.error(result.msg);
                return false;
            }
            refresh_settlement_totals();

            $('#active_settlement_id').val(result.settlement_id);
            if (result.settlement_no) {
                $('#settlement_no').val(result.settlement_no);
            }
            if ($('#pump_operator_id').val() && result.settlement_id) {
                window.__directSettlementByOperator = window.__directSettlementByOperator || {};
                window.__directSettlementByOperator[$('#pump_operator_id').val()] = result.settlement_id;
            }
            if (result.settlement_id && window.history && window.history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.set('view_settlement_id', result.settlement_id);
                window.history.replaceState({}, '', url.toString());
            }

            other_sale_id = result.other_sale_id;
            sub_total = __number_f(sub_total);
            // Prefer the server-rendered row so the Edit / Edit-no-change form
            // always shows the row it just added, even when the JS globals
            // (other_sale_code, other_sale_product_name, ...) are stale.
            if (result.row_html) {
                $('#other_sale_table tbody').prepend(result.row_html);
            } else {
                $('#other_sale_table tbody').prepend(
                    `
                    <tr>
                        <td>`+ other_sale_code + `</td>
                        <td>`+ other_sale_product_name + `</td>
                        <td>`+ balance_stock + `</td>
                        <td>`+ __number_f(other_sale_price) + `</td>
                        <td>`+ other_sale_qty + `</td>
                        <td>`+ capitalizeFirstLetter(other_sale_discount_type) + `</td>
                        <td>`+ __number_f(other_sale_discount) + `</td>
                        <td>`+ sub_total + `</td>
                        <td>`+ __number_f(with_discount) + `</td>
                        <td><button class="btn btn-xs btn-danger delete_other_sale" data-href="/petro/settlement/delete-other-sale/` +
                    other_sale_id +
                    `"><i class="fa fa-times"></i></button>
                        </td>
                    </tr>
                `
                );
            }
            $('.other_sale_fields').val('').trigger('change');

            refresh_settlement_totals();
        },
        error: function () {
            // Re-enable button on error (with delay to prevent double-click)
            setTimeout(function () {
                window.isOtherSaleSubmitting = false;
                $button.prop('disabled', false).removeClass('disabled');
            }, 500);
        },
        complete: function () {
            // Re-enable after a short delay so double-click cannot submit twice
            setTimeout(function () {
                window.isOtherSaleSubmitting = false;
                $button.prop('disabled', false).removeClass('disabled');
            }, 500);
        }
    });
});

$(document).on('click', '.delete_other_sale', function () {
    url = $(this).data('href');
    tr = $(this).closest('tr');
    var is_edit = $("#is_edit").val() || 0;

    $.ajax({
        method: 'delete',
        url: url,
        data: { is_edit },
        success: function (result) {
            if (result.success) {
                toastr.success(result.msg);
                tr.remove();
                // recalc footer value if table exists
                if ($('#other_sale_table').length) {
                    const newTotal = sum_table_col($('#other_sale_table'), 'sub_total');
                    $('#footer_list_other_sales_amount').val(newTotal).text(newTotal);
                }
                // Recalc other sale total from remaining table rows so total stays in sync
                refresh_settlement_totals();
            } else {
                toastr.error(result.msg);
            }
        },
    });
});

//other income tab
var other_income_total = parseFloat($('#other_income_total').val().replace(',', ''));
var sub_total = 0.0;
var other_income_code = null;
var other_income_product_name = null;
var other_income_price = 0.0;

$(document).off('click', '.btn_other_income').on('click', '.btn_other_income', function () {
    if (window.__petro_settlement_create_local) return;
    var other_income_product_id = $('#other_income_product_id').val();
    var other_income_qty = $('#other_income_qty').val();
    var other_income_reason = $('#other_income_reason').val();
    var other_income_id = null;

    var otherIncomePrice = $('#other_income_price').val();
    var priceWithoutCommas = otherIncomePrice.replace(/,/g, '');

    other_income_price = parseFloat(priceWithoutCommas);

    var other_income_amount = parseFloat(other_income_qty) * other_income_price;
    var is_edit = $("#is_edit").val() || 0;

    let other_income_total = parseFloat($('#other_income_total').val().replace(',', ''));
    other_income_total = other_income_total + other_income_amount;
    $('#other_income_total').val(other_income_total);
    $.ajax({
        method: 'post',
        url: '/petro/settlement/save-other-income',
        data: {
            settlement_no: $('#settlement_no').val(),
            location_id: $('#location_id').val(),
            pump_operator_id: $('#pump_operator_id').val(),
            transaction_date: $('#transaction_date').val(),
            work_shift: $('#work_shift').val(),
            note: $('#note').val(),
            product_id: other_income_product_id,
            qty: other_income_qty,
            price: other_income_price,
            other_income_reason: other_income_reason,
            sub_total: other_income_amount,
            is_edit: is_edit
        },
        success: function (result) {
            if (!result.success) {
                toastr.error(result.msg);
                return false;
            }

            other_income_id = result.other_income_id;
            other_income_sub_total = __number_f(other_income_amount);
            $('#other_income_table tbody').prepend(
                `
                <tr> 
                    <td>` +
                other_income_product_name +
                `</td>
                    <td>` +
                __number_f(other_income_qty) +
                `</td>
                    <td>` +
                other_income_reason +
                `</td>
                    <td>` +
                other_income_sub_total +
                `</td>
                    <td><button class="btn btn-xs btn-danger delete_other_income" data-href="/petro/settlement/delete-other-income/` +
                other_income_id +
                `"><i class="fa fa-times"></i></button>
                    </td>
                </tr>
            `
            );
            $('.other_income_fields').val('').trigger('change');
            refresh_settlement_totals();
        },
    });
});
$('#other_income_product_id').change(function () {
    let item_id = $(this).val();
    $.ajax({
        method: 'get',
        url: '/petro/settlement/get_balance_stock/' + item_id,
        data: {},
        success: function (result) {
            other_income_code = result.code;
            other_income_product_name = result.product_name;
            other_income_price = result.price;
            $('#other_income_price').val(__number_f(other_income_price));
        },
    });
});

$(document).on('click', '.delete_other_income', function () {
    if (window.__petro_settlement_create_local) return;
    url = $(this).data('href');
    tr = $(this).closest('tr');
    var is_edit = $("#is_edit").val() || 0;

    $.ajax({
        method: 'delete',
        url: url,
        data: { is_edit },
        success: function (result) {
            if (result.success) {
                toastr.success(result.msg);
                tr.remove();
                refresh_settlement_totals();
            } else {
                toastr.error(result.msg);
            }
        },
    });
});
//customer_payment tab
var customer_payment_total = parseFloat($('#customer_payment_total').val().replace(',', ''));
var sub_total = 0.0;

$(document).off('click', '.btn_customer_payment').on('click', '.btn_customer_payment', function () {
    var customer_payment_amount = parseFloat($('#customer_payment_amount').val());
    var customer_name = $('#customer_payment_customer_id :selected').text();
    var payment_method = $('#customer_payment_payment_method').val();
    var bank_name = $('#customer_payment_bank_name').val();
    var cheque_date = $('#customer_payment_cheque_date').val();
    var cheque_number = $('#customer_payment_cheque_number').val();
    var post_dated_cheque = $('#customer_payment_post_dated_cheque').val();
    var customer_payment_id = null;

    let customer_payment_total = parseFloat($('#customer_payment_total').val().replace(',', ''));
    customer_payment_total = customer_payment_total + customer_payment_amount;
    $('#customer_payment_total').val(customer_payment_total);
    var is_edit = $("#is_edit").val() || 0;

    $.ajax({
        method: 'post',
        url: '/petro/settlement/save-customer-payment',
        data: {
            settlement_no: $('#settlement_no').val(),
            location_id: $('#location_id').val(),
            pump_operator_id: $('#pump_operator_id').val(),
            transaction_date: $('#transaction_date').val(),
            work_shift: $('#work_shift').val(),
            note: $('#note').val(),

            customer_id: $('#customer_payment_customer_id').val(),
            payment_method: $('#customer_payment_payment_method').val(),
            bank_name: bank_name,
            cheque_date: cheque_date,
            cheque_number: cheque_number,
            amount: customer_payment_amount,
            sub_total: customer_payment_amount,
            is_edit: is_edit,
            post_dated_cheque: post_dated_cheque
        },
        success: function (result) {
            if (!result.success) {
                toastr.error(result.msg);
                return false;
            }

            customer_payment_id = result.customer_payment_id;
            customer_payment_amount = __number_f(customer_payment_amount);
            $('#customer_payment_table tbody').prepend(
                `
                <tr> 
                    <td>` +
                customer_name +
                `</td>
                    <td>` +
                payment_method +
                `</td>
                    <td>` +
                bank_name +
                `</td>
                    <td>` +
                cheque_date +
                `</td>
                    <td>` +
                cheque_number +
                `</td>
                    <td>` +
                customer_payment_amount +
                `</td>
                    <td><button class="btn btn-xs btn-danger delete_customer_payment" data-href="/petro/settlement/delete-customer-payment/` +
                customer_payment_id +
                `"><i class="fa fa-times"></i></button>
                    </td>
                </tr>
            `
            );
            $('.customer_payment_fields').val('').trigger('change');
            refresh_settlement_totals();
        },
    });
});

$('#customer_payment_payment_method').change(function () {
    if ($(this).val() == 'cheque') {
        $('.cheque_divs').removeClass('hide');
    } else {
        $('.cheque_divs').addClass('hide');
    }
});

$(document).on('click', '.delete_customer_payment', function () {
    url = $(this).data('href');
    tr = $(this).closest('tr');
    var is_edit = $("#is_edit").val() || 0;

    $.ajax({
        method: 'delete',
        url: url,
        data: { is_edit },
        success: function (result) {
            if (result.success) {
                toastr.success(result.msg);
                tr.remove();
                refresh_settlement_totals();
            } else {
                toastr.error(result.msg);
            }
        },
    });
});

function isPetroPdSettlementPage() {
    return window.location.pathname.indexOf('/settlement-pd') !== -1 || window.location.pathname.indexOf('/petropd') !== -1;
}

function readSettlementTotal(selector) {
    let value = $(selector).val();
    if (value === undefined || value === null || value === '') {
        return 0;
    }

    value = parseFloat(String(value).replace(/,/g, ''));
    return Number.isFinite(value) ? value : 0;
}

function calculate_payment_tab_total() {
    let meter_sale_totals = readSettlementTotal('#meter_sale_total');
    let shift_operator_other_sale_total = readSettlementTotal('#shift_operator_other_sale_total');
    let other_sale_totals = readSettlementTotal('#other_sale_total') + shift_operator_other_sale_total;
    let other_income_totals = readSettlementTotal('#other_income_total');
    let customer_payment_totals = readSettlementTotal('#customer_payment_total');

    let all_totals =
        meter_sale_totals + other_sale_totals + other_income_totals + customer_payment_totals;

    if (!Number.isFinite(all_totals)) {
        return;
    }

    $('.payment_meter_sale_total').text(
        __number_f(meter_sale_totals, false, false, __currency_precision)
    );
    $('.payment_other_sale_total').text(
        __number_f(other_sale_totals, false, false, __currency_precision)
    );
    $('.payment_other_income_total').text(
        __number_f(other_income_totals, false, false, __currency_precision)
    );
    $('.payment_customer_payment_total').text(
        __number_f(customer_payment_totals, false, false, __currency_precision)
    );
    $('.meter_sale_total').text(__number_f(meter_sale_totals, false, false, __currency_precision));
    $('.other_sale_total').text(__number_f(other_sale_totals, false, false, __currency_precision));
    $('.other_income_total').text(
        __number_f(other_income_totals, false, false, __currency_precision)
    );
    $('.customer_payment_total').text(
        __number_f(customer_payment_totals, false, false, __currency_precision)
    );

    $('#payment_due').text(__number_f(all_totals, false, false, __currency_precision));
}

function refresh_settlement_totals() {
    let settlement_no = $('#settlement_no').val();
    if (!settlement_no) return;

    let totalsUrl = '/petro/settlement/get-payment-tab-totals';
    if (isPetroPdSettlementPage()) {
        totalsUrl = '/petro/settlement-pd/get-payment-tab-totals';
    }

    $.ajax({
        method: 'get',
        url: totalsUrl,
        data: { settlement_no: settlement_no },
        success: function (result) {
            if (result.success) {
                $('#meter_sale_total').val(result.meter_sale_total);
                $('#other_sale_total').val(result.other_sale_total);
                $('#other_income_total').val(result.other_income_total);
                $('#customer_payment_total').val(result.customer_payment_total);

                calculate_payment_tab_total();
            }
        },
    });
}

$(document).on('shown.bs.tab', 'a[href="#payment_tab"]', function (e) {
    refresh_settlement_totals();
    // PetroPD edit pages already render canonical totals server-side; wait for the PetroPD AJAX refresh.
    if (!isPetroPdSettlementPage()) {
        calculate_payment_tab_total();
    }
});

/*
 * MA-002: resolve the petro module prefix from the page you are on.
 *
 * The handlers below are GLOBAL - they fire on any page containing a
 * #location_id or #store_id field, which includes Petro General's Add Fuel
 * Tank form. They called "/petro/get-stores-by-id" regardless of which module
 * the page belonged to.
 *
 * On a business where the old Petro module is switched off in Manage Side Bar,
 * that request is refused by EnforceBusinessSidebarModuleAccess and the user
 * sees "This module has been disabled from Manage Side Bar for this business"
 * while doing something in Petro General that has nothing to do with Petro.
 *
 * That is the Add Fuel Tank failure: the SAVE was never blocked - this lookup
 * was, when the Business Location dropdown changed.
 *
 * Petro General, Petro PD and Petro Direct each own the same endpoint under
 * their own prefix, so the fix is to ask the current url which module we are
 * in rather than assume Petro.
 */
function __petro_module_prefix() {
    try {
        var first = (window.location.pathname || '').split('/').filter(Boolean)[0] || '';
        switch (first) {
            case 'petro-general':
            case 'petropd':
            case 'petro':
                // These modules each own /<prefix>/get-stores-by-id.
                return '/' + first;

            /*
             * S678-RETIRE: everything else now uses the CORE route.
             *
             * The default used to be '/petro'. That made every non-fuel page
             * carrying a #location_id field - Suppliers -> Advance Payment
             * among them - depend on the Petro module being switched on, and
             * is why petro_module = 1 has been left enabled at ep127 against
             * the customer's wishes (MA 007 section 5).
             *
             * '' resolves to /get-stores-by-id, registered in routes/web.php
             * and served by App\Http\Controllers\StoreLookupController. It
             * is business-scoped and gated by no module.
             *
             * 'petrodirect' is still not listed: PetroDirect serves this
             * endpoint at /settlement/get-stores-by-id rather than under its
             * own prefix, so it falls through to core like everything else.
             */
            default:
                return '';
        }
    } catch (e) {
        return '';
    }
}

// Added by Muneeb Ahmad for Store Dropdown - Task#3454
$(document).on('change', '#location_id', function () {
    let location_id = $(this).val();
    console.log('change#location_id');
    $.ajax({
        method: 'get',
        url: __petro_module_prefix() + "/get-stores-by-id",
        data: { location_id },
        contentType: 'html',
        success: function (result) {
            $('#store_id').empty().append(result);
        },
    });
});
$(document).on('change', '#store_id', function () {
    let location_id = $('#location_id').val();
    let store_id = $(this).val();
    let tab = 'any';
    if ($('#other_sale_tab').length && $('#other_sale_tab').hasClass('active')) {
        tab = 'other_sale';
    }
    $.ajax({
        method: 'get',
        url: "/petro/get-products-by-store-id",
        data: { 'location_id': location_id, 'store_id': store_id, 'tab': tab },
        contentType: 'html',
        success: function (result) {
            $('#item').empty().append(result);
            if (store_id !== null && store_id !== "") {
                document.getElementById("item").disabled = false;
            }
        },
    });
});

function updateCancelMeterForm(url, data) {
    $.ajax({
        method: 'get',
        url: url,
        data: data,
        success: function (result) {
            if (result.success) {
                $('#meter-sale-form-block').html(result.html);
                $('#pump_no').select2();
                $('#meter_sale_discount_type').select2();
            } else {
                toastr.error(result.msg);
            }
        },
    });
}
var __meter_sale_edit_tr = null;
$(document).on('click', '.get_meter_sale_from', function () {
    url = $(this).data('href');
    data = { action_type: 'edit' };
    __meter_sale_edit_tr = $(this).closest('tr');
    updateCancelMeterForm(url, data);
});

$(document).on('click', '.btn_meter_sale_cancel', function () {
    url = $(this).data('href');
    data = { action_type: 'cancel' };
    __meter_sale_edit_tr = null;
    updateCancelMeterForm(url, data);
});

function applyMeterSaleTableHtml(tableHtml, fallbackTotal) {
    if (typeof tableHtml === 'string' && tableHtml.length > 0) {
        var $wrap = $('<div>').append($.parseHTML(tableHtml));
        var $newTable = $wrap.find('table#meter_sale_table');

        if ($newTable.length) {
            var newTbody = $newTable.children('tbody').html();
            var newTfoot = $newTable.children('tfoot').html();
            if (newTbody != null) $('#meter_sale_table').children('tbody').html(newTbody);
            if (newTfoot != null) $('#meter_sale_table').children('tfoot').html(newTfoot);

            var $totalInput = $newTable.find('input#meter_sale_total');
            if ($totalInput.length && $totalInput.val() !== '') {
                $('#meter_sale_total').val($totalInput.val());
            }
        }
    }

    if (fallbackTotal !== undefined && fallbackTotal !== null && fallbackTotal !== '') {
        $('#meter_sale_total').val(fallbackTotal);
    }

    if ($('#meter_sale_table').length && typeof sum_table_col === 'function') {
        var total = sum_table_col($('#meter_sale_table'), 'discount_amount');
        if (!isNaN(total)) {
            $('#meter_sale_total').val(total);
        }
    }

    calculate_payment_tab_total();
}

$(document).on('click', '.btn_update_meter_sale', function () {
    url = $(this).data('href');
    var is_edit = $("#is_edit").val() || 0;
    pump_id = $('#pump_no').val();

    var testing_qty = $('#testing_qty').val();
    var meter_sale_discount = $('#meter_sale_discount').val() || 0;
    var meter_sale_discount_type = $('#meter_sale_discount_type').val();
    var sold_qty = parseFloat($('#sold_qty').val()) || 0;
    var form_price = parseFloat($('#meter_sale_unit_price').val()) || 0;
    var sub_total = sold_qty * form_price;
    var meter_sale_discount_amount = sub_total - calculate_discount(meter_sale_discount_type, meter_sale_discount, sub_total);

    $.ajax({
        method: 'post',
        url: url,
        dataType: 'json',
        data: {
            pump_id: pump_id,
            starting_meter: $('#pump_starting_meter').val(),
            closing_meter: $('#pump_closing_meter').val(),
            price: $('#meter_sale_unit_price').val(),
            qty: sold_qty,
            discount: meter_sale_discount,
            discount_type: meter_sale_discount_type,
            discount_amount: meter_sale_discount_amount,
            testing_qty: testing_qty,
            sub_total: sub_total,
            is_edit: is_edit,
            is_from_pumper: $("#is_from_pumper").val() || 0,
            assignment_id: $("#assignment_id").val() || 0,
            pumper_entry_id: $("#pumper_entry_id").val() || 0,
            shift_id: getCurrentSettlementShiftId()
        },
        success: function (result) {
            if (!result || !result.success) {
                toastr.error((result && result.msg) ? result.msg : 'Update failed');
                return false;
            }

            toastr.success(result.msg);
            applyMeterSaleTableHtml(result.table_html, result.meter_sale_total);
            $('.meter_sale_fields').val('');
            $('.testing_qty').val(0);
            __meter_sale_edit_tr = null;
            $('.btn_meter_sale_cancel').trigger('click');

            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#pump_operator_meter_sale_table')) {
                $('#pump_operator_meter_sale_table').DataTable().ajax.reload(null, false);
            }

            refresh_settlement_totals();
            updateTotalSoldQty();
        },
    });
});

/*
$(document).on('click', '.btn_update_meter_sale', function () {
    url = $(this).data('href');
    tr = __meter_sale_edit_tr || $(this).closest('tr');
    var is_edit = $("#is_edit").val() || 0;
    pump_id = $('#pump_no').val();
    var form_start = $('#pump_starting_meter').val();
    var form_close = $('#pump_closing_meter').val();
    var form_price = $('#meter_sale_unit_price').val();
    var form_pump_name = ($('#pump_no option:selected').text() || '').trim() || pump_name;

    var testing_qty = $('#testing_qty').val();
    var is_from_pumper = $("#is_from_pumper").val() || 0;

    var assignment_id = $("#assignment_id").val() || 0;
    var pumper_entry_id = $("#pumper_entry_id").val() || 0;

    var meter_sale_discount = $('#meter_sale_discount').val();
    var meter_sale_discount_type = $('#meter_sale_discount_type').val();
    var meter_sale_discount_type_text = '';
    if ($('#meter_sale_discount_type').val() !== '') {
        meter_sale_discount_type_text = $('#meter_sale_discount_type option[value="' + $('#meter_sale_discount_type').val() + '"]').text();
    }
    // Sold Qty field is already chargeable (Closing - Starting - Testing); do not subtract testing again
    var sold_qty = parseFloat($('#sold_qty').val()) || 0;
    var total_qty = sold_qty + (parseFloat(testing_qty) || 0);
    sub_total = parseFloat(sold_qty) * parseFloat(price);

    if (!meter_sale_discount) {
        meter_sale_discount = 0;
    }
    var meter_sale_discount_amount = sub_total - calculate_discount(meter_sale_discount_type, meter_sale_discount, sub_total);
    var meter_sale_id = null;

    $.ajax({
        method: 'post',
        url: url,
        dataType: 'json',
        data: {
            pump_id: pump_id,
            starting_meter: $('#pump_starting_meter').val(),
            closing_meter: $('#pump_closing_meter').val(),
            product_id: product_id,
            price: $('#meter_sale_unit_price').val(),
            qty: sold_qty,
            discount: meter_sale_discount,
            discount_type: meter_sale_discount_type,
            discount_amount: meter_sale_discount_amount,
            testing_qty: testing_qty,
            sub_total: sub_total,
            is_edit: is_edit,
            is_from_pumper: is_from_pumper,
            assignment_id: assignment_id,
            pumper_entry_id: pumper_entry_id,
        },
        success: function (result) {
            if (!result || !result.success) {
                toastr.error((result && result.msg) ? result.msg : 'Update failed');
                return false;
            }
            toastr.success(result.msg);

            refresh_settlement_totals();
            $('#pump_no')
                .find('option[value=' + pump_id + ']')
                .remove();

            meter_sale_id = result.meter_sale_id;
            settlement_id = result.settlement_id;

            $('.meter_sale_fields').val('');
            $('.testing_qty').val(0);
            __meter_sale_edit_tr = null;

            var tableHtml = (result && result.table_html) ? result.table_html : '';
            if (typeof tableHtml === 'string' && tableHtml.length > 0) {
                var $wrap = $('<div>').append($.parseHTML(tableHtml));
                var $newTable = $wrap.find('table#meter_sale_table');
                if ($newTable.length) {
                    var newTbody = $newTable.children('tbody').html();
                    var newTfoot = $newTable.children('tfoot').html();
                    if (newTbody != null) $('#meter_sale_table').children('tbody').html(newTbody);
                    if (newTfoot != null) $('#meter_sale_table').children('tfoot').html(newTfoot);
                    var $totalInput = $newTable.find('input#meter_sale_total');
                    if ($totalInput.length && $totalInput.val()) {
                        $('#meter_sale_total').val($totalInput.val());
                        if (typeof calculate_payment_tab_total === 'function') calculate_payment_tab_total();
                    }
                }
            } else {
                meter_sale_totals = __number_f(sub_total);
                sold_qty = (sold_qty);
                tr.replaceWith(
                    '<tr>' +
                    '<td>' + (code || '') + '</td>' +
                    '<td><span class="product_name">' + (product_name || '') + '</span></td>' +
                    '<td>' + (form_pump_name || pump_name || '') + '</td>' +
                    '<td>' + (form_start !== undefined ? form_start : pump_starting_meter) + '</td>' +
                    '<td>' + (form_close !== undefined ? form_close : pump_closing_meter) + '</td>' +
                    '<td>' + __number_f(form_price || price) + '</td>' +
                    '<td><span class="sold_qty">' + sold_qty + '</span></td>' +
                    '<td>' + (meter_sale_discount_type_text || '') + '</td>' +
                    '<td>' + __number_f(meter_sale_discount) + '</td>' +
                    '<td>' + (testing_qty || '') + '</td>' +
                    '<td>' + (total_qty || '') + '</td>' +
                    '<td>' + __number_f(sub_total) + '</td>' +
                    '<td><span class="display_currency discount_amount" data-orig-value="' + meter_sale_discount_amount + '">' + __number_f(meter_sale_discount_amount) + '</span></td>' +
                    '<td><button class="btn btn-xs btn-primary get_meter_sale_from" data-type="edit" data-href="/petro/settlement/get-meter-sale-form/' + meter_sale_id + '"><i class="fa fa-edit"></i></button>' +
                    '<button class="btn btn-xs btn-danger delete_meter_sale" data-href="/petro/settlement/delete-meter-sale/' + meter_sale_id + '"><i class="fa fa-times"></i></button></td>' +
                    '</tr>'
                );
            }

            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#pump_operator_meter_sale_table')) {
                $('#pump_operator_meter_sale_table').DataTable().ajax.reload(null, false);
            }
            if ($('#meter_sale_table').length && typeof sum_table_col === 'function') {
                var total = sum_table_col($('#meter_sale_table'), 'discount_amount');
                if (!isNaN(total)) {
                    $('#meter_sale_total').val(total);
                    if (typeof calculate_payment_tab_total === 'function') calculate_payment_tab_total();
                }
            }
            refresh_settlement_totals();
            updateTotalSoldQty();
        },
    });
});
*/
/*
$(document).on('click', '.btn_update_meter_sale_pd', function () {
    url = $(this).data('href');
    tr = __meter_sale_edit_tr || $(this).closest('tr');
    var is_edit = $("#is_edit").val() || 0;
    pump_id = $('#pump_no').val();

    var testing_qty = $('#testing_qty').val();
    var is_from_pumper = $("#is_from_pumper").val() || 0;

    var assignment_id = $("#assignment_id").val() || 0;
    var pumper_entry_id = $("#pumper_entry_id").val() || 0;

    var meter_sale_discount = $('#meter_sale_discount').val();
    var meter_sale_discount_type = $('#meter_sale_discount_type').val();
    var meter_sale_discount_type_text = '';
    if ($('#meter_sale_discount_type').val() !== '') {
        meter_sale_discount_type_text = $('#meter_sale_discount_type option[value="' + $('#meter_sale_discount_type').val() + '"]').text();
    }
    // Sold Qty field is already chargeable (Closing - Starting - Testing); do not subtract testing again
    var sold_qty = parseFloat($('#sold_qty').val()) || 0;
    var total_qty = sold_qty + (parseFloat(testing_qty) || 0);
    sub_total = parseFloat(sold_qty) * parseFloat(price);

    if (!meter_sale_discount) {
        meter_sale_discount = 0;
    }
    var meter_sale_discount_amount = sub_total - calculate_discount(meter_sale_discount_type, meter_sale_discount, sub_total);
    var meter_sale_id = null;

    $.ajax({
        method: 'post',
        url: url,
        data: {
            pump_id: pump_id,
            starting_meter: $('#pump_starting_meter').val(),
            closing_meter: $('#pump_closing_meter').val(),
            product_id: product_id,
            price: $('#meter_sale_unit_price').val(),
            qty: sold_qty,
            discount: meter_sale_discount,
            discount_type: meter_sale_discount_type,
            discount_amount: meter_sale_discount_amount,
            testing_qty: testing_qty,
            sub_total: sub_total,
            is_edit: is_edit,
            is_from_pumper: is_from_pumper,
            assignment_id: assignment_id,
            pumper_entry_id: pumper_entry_id,
        },
        success: function (result) {
            if (!result.success) {
                toastr.error(result.msg);
                return false;
            }
            toastr.success(result.msg);

            $('#pump_no')
                .find('option[value=' + pump_id + ']')
                .remove();

            meter_sale_id = result.meter_sale_id;
            settlement_id = result.settlement_id;

            $('.meter_sale_fields').val('');
            $('.testing_qty').val(0);
            __meter_sale_edit_tr = null;
            $('.btn_meter_sale_cancel').trigger('click');
            if (typeof loadMeterSalesData === 'function') {
                loadMeterSalesData();
            }
            if (typeof refresh_settlement_totals === 'function') {
                refresh_settlement_totals();
            }
            updateTotalSoldQty();
        },
    });
});
*/
