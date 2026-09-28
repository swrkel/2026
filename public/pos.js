$(document).ready(function() {

    customer_set = false;
    //Prevent enter key function except texarea
    $('form').on('keyup keypress', function(e) {
        var keyCode = e.keyCode || e.which;
        if (keyCode === 13 && e.target.tagName != 'TEXTAREA') {
            e.preventDefault();
            return false;
        }
    });

    //For edit pos form
    if ($('form#edit_pos_sell_form').length > 0) {
        pos_total_row();
        pos_form_obj = $('form#edit_pos_sell_form');
        // Store initial total for edit forms to compare later
        var initial_total = __read_number($('input#final_total_input'));
        $('input#final_total_input').data('old-total', initial_total);
    } else {
        pos_form_obj = $('form#add_pos_sell_form');
    }
    if ($('form#edit_pos_sell_form').length > 0 || $('form#add_pos_sell_form').length > 0) {
        initialize_printer();
    }

    $('select#select_location_id').change(function() {
        reset_pos_form();

        var default_price_group = $(this).find(':selected').data('default_price_group')
        if (default_price_group) {
            if($("#price_group option[value='" + default_price_group + "']").length > 0) {
                $("#price_group").val(default_price_group);
                $("#price_group").change();
            }
        }

        //Set default price group
        if ($('#default_price_group').length) {
            var dpg = default_price_group ?
            default_price_group : 0;
            $('#default_price_group').val(dpg);
        }

        var payment_settings = $('select#select_location_id')
                .find(':selected')
                .data('default_payment_accounts');
        payment_settings = payment_settings ? payment_settings : [];
        enabled_payment_types = [];
        for (var key in payment_settings) {
            if (payment_settings[key] && payment_settings[key]['is_enabled']) {
                enabled_payment_types.push(key);
            }
        }
        $(".payment_types_dropdown > option").each(function() {
            if ($(this).val()) {
                if (enabled_payment_types.indexOf($(this).val()) != -1) {
                    $(this).removeClass('hide');
                } else {
                    $(this).addClass('hide');
                }
            }
        });

        if ($('#types_of_service_id').length) {
            $('#types_of_service_id').change();
        }
    });

    //get customer
    $('#customer_id').select2({
        ajax: {
            url: '/contacts/customers',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term, // search term
                    page: params.page,
                };
            },
            processResults: function(data) {
                return {
                    results: data,
                };
            },
        },
        templateResult: function (data) { 
            var template = data.text + "<br>" + LANG.mobile + ": " + data.mobile;
            if (typeof(data.total_rp) != "undefined") {
                var rp = data.total_rp ? data.total_rp : 0;
                template += "<br><i class='fa fa-gift text-success'></i> " + rp;
            }

            return  template;
        },
        minimumInputLength: 1,
        language: {
            noResults: function() {
                var name = $('#customer_id')
                    .data('select2')
                    .dropdown.$search.val();
                return (
                    '<button type="button" data-name="' +
                    name +
                    '" class="btn btn-link add_new_customer"><i class="fa fa-plus-circle fa-lg" aria-hidden="true"></i>&nbsp; ' +
                    __translate('add_name_as_new_customer', { name: name }) +
                    '</button>'
                );
            },
        },
        escapeMarkup: function(markup) {
            return markup;
        },
    });

    //get customer
    $('#pos_patients').select2({
        ajax: {
            url: '/patient/getPatient',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term, // search term
                    page: params.page,
                };
            },
            processResults: function(data) {
                console.log(data);
                
                return {
                    results: data,
                };
            },
        },
        templateResult: function (data) { 
            var template = 
            '<li><a style="text-decoration: none; color: black;" href="#" data-href="" class="btn-modal" data-container=".patient_prescriptions_modal">'+
                data.username + '<br>' + 'Name: ' + data.name + ' '+ LANG.mobile + ': ' + data.mobile +'</a></li>';
            return  template;
        },
        minimumInputLength: 1,
        language: {
            noResults: function() {
                var name = $('#pos_patients')
                    .data('select2')
                    .dropdown.$search.val();
                return (
                    '<p>No patient found</p>'
                );
            },
        },
        escapeMarkup: function(markup) {
            return markup;
        },
    });
    

    $('#pos_patients').on('select2:select', function(e) {
        
        var data = e.params.data;
        $('.patient_prescriptions_modal').data('bs.modal', null);
        $.ajax({
            method: '',
            url: '/prescription/getPrescriptions/'+data.id,
            data: {  },
            success: function(result) {
                $('.patient_prescriptions_modal').find('.modal-dialog').remove();
                $('.patient_prescriptions_modal').append(result).modal('show');
                
            },
        });
        console.log(data.id);
        

       
    });


    $('#customer_id').on('select2:select', function(e) {
        var data = e.params.data;
        if (data.pay_term_number) {
            $('input#pay_term_number').val(data.pay_term_number);
        } else {
            $('input#pay_term_number').val('');
        }

        if (data.pay_term_type) {
            $('#pay_term_type').val(data.pay_term_type);
        } else {
            $('#pay_term_type').val('');
        }
    });

    set_default_customer();

    //Add Product
    $('#search_product')
        .autocomplete({
            source: function(request, response) {
                var price_group = '';
                var search_fields = [];
                $('.search_fields:checked').each(function(i){
                  search_fields[i] = $(this).val();
                });
                if($('#product_category').val() != 'all'){
                    var product_category = $('#product_category').val();
                }else{
                    product_category = '';
                }
                
                if ($('#price_group').length > 0) {
                    price_group = $('#price_group').val();
                }
                
                $.getJSON(
                    '/products/list',
                    {
                        price_group: price_group,
                        location_id: $('input#location_id').val(),
                        term: request.term,
                        not_for_selling: 0,
                        search_fields: search_fields,
                        product_category: product_category
                    },
                    response
                );
            },
            minLength: 2,
            response: function(event, ui) {
                if (ui.content.length == 1) {
                    ui.item = ui.content[0];
                    if (ui.item.qty_available > 0) {
                        $(this)
                            .data('ui-autocomplete')
                            ._trigger('select', 'autocompleteselect', ui);
                        $(this).autocomplete('close');
                    }
                } else if (ui.content.length == 0) {
                    toastr.error(LANG.no_products_found);
                    $('input#search_product').select();
                }
            },
            focus: function(event, ui) {
                if (ui.item.qty_available <= 0) {
                    return false;
                }
            },
            select: function(event, ui) {
                var searched_term = $(this).val();
                var is_overselling_allowed = false;
                if($('input#is_overselling_allowed').length) {
                    is_overselling_allowed = true;
                }

                if (ui.item.enable_stock != 1 || ui.item.qty_available > 0 || is_overselling_allowed) {
                    $(this).val(null);

                    //Pre select lot number only if the searched term is same as the lot number
                    var purchase_line_id = ui.item.purchase_line_id && searched_term == ui.item.lot_number ? ui.item.purchase_line_id : null;
                    pos_product_row(ui.item.variation_id, purchase_line_id);
                } else {
                    alert(LANG.out_of_stock);
                }
            },
        })
        .autocomplete('instance')._renderItem = function(ul, item) {
            var is_overselling_allowed = false;
            if($('input#is_overselling_allowed').length) {
                is_overselling_allowed = true;
            }
            var enable_code = false;
            if($('input#enable_code').val() == 1) {
                enable_code = true;
            }
            var enable_rack_number = false;
            if($('input#enable_rack_number').val() == 1) {
                enable_rack_number = true;
            }
            var enable_qty = false;
            if($('input#enable_qty').val() == 1) {
                enable_qty = true;
            }
            var enable_product_cost = false;
            if($('input#enable_product_cost').val() == 1) {
                enable_product_cost = true;
            }
            var enable_product_supplier = false;
            if($('input#enable_product_supplier').val() == 1) {
                enable_product_supplier = true;
            }
            
        if (item.enable_stock == 1 && item.qty_available <= 0 && !is_overselling_allowed) {
            var string = '<li class="ui-state-disabled">' + item.name;
            if (item.type == 'variable') {
                string += '-' + item.variation;
            }
            var selling_price = item.selling_price;
            if (item.variation_group_price) {
                selling_price = item.variation_group_price;
            }
            string +=
                ' (' +
                item.sub_sku +
                ')' +
                '<br> Price: ' +
                selling_price +
                ' (Out of stock) </li>';
            return $(string).appendTo(ul);
        } else {
            var string = '<div>' + item.name;
            string += '-' + item.variation;
            if (item.type == 'variable') {
                string += '-' + item.variation;
            }

            var selling_price = item.selling_price;
            if (item.variation_group_price) {
                selling_price = item.variation_group_price;
            }

            if(enable_code){
                string += ' (' + item.sub_sku + ')' ;
            }
            string += '<br> Price: ' + selling_price;
            if(enable_product_cost){
                string += ' - P.Price: ' + item.purchase_price;
            }
            if (item.enable_stock == 1 && enable_qty == true) {
                var qty_available = __currency_trans_from_en(item.qty_available, false, false, __currency_precision, true);
                string += ' - ' + qty_available + item.unit;
            }
            if (item.rack_number != null && enable_rack_number == true) {
                string += ' - Rack: ' + item.rack_number;
            }
            if (item.supplier_name != null && enable_product_supplier == true) {
                string += ' - Supplier: ' + item.supplier_name;
            }
            string += '</div>';

            return $('<li>')
                .append(string)
                .appendTo(ul);
        }
    };

    //Update line total and check for quantity not greater than max quantity
    $('table#pos_table tbody').on('change', 'input.pos_quantity', function() {
        if (sell_form_validator) {
            sell_form_validator.element($(this));
        }
        if (pos_form_validator) {
            pos_form_validator.element($(this));
        }
        
        var tr = $(this).parents('tr');

        // Recalculate everything using pos_each_row for consistency
        pos_each_row(tr);
        pos_total_row();

        adjustComboQty(tr);
    });

    //If change in unit price update price including tax and line total
    $('table#pos_table tbody').on('change', 'input.pos_unit_price', function() {
        var unit_price = __read_number($(this));
        var tr = $(this).parents('tr');

        //calculate discounted unit price
        var discounted_unit_price = calculate_discounted_unit_price(tr);

        var tax_rate = tr
            .find('select.tax_id')
            .find(':selected')
            .data('rate');
        var quantity = __read_number(tr.find('input.pos_quantity'));

        var unit_price_inc_tax = __add_percent(discounted_unit_price, tax_rate);
        var line_total = quantity * unit_price_inc_tax;
        
        // Calculate line discount
        var original_line_total = quantity * (unit_price + __calculate_amount('percentage', tax_rate, unit_price));
        var line_discount = original_line_total - line_total;

        __write_number(tr.find('input.pos_unit_price_inc_tax'), unit_price_inc_tax);
        tr.find('span.price_inc_tax_display').text(__currency_trans_from_en(unit_price_inc_tax, true));
        __write_number(tr.find('input.pos_line_total'), line_total, false, 2);
        __write_number(tr.find('input.pos_line_discount'), line_discount, false, 2);
        tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));
        pos_each_row(tr);
        pos_total_row();
        round_row_to_iraqi_dinnar(tr);
    });

    //If change in tax rate then update unit price according to it.
    $('table#pos_table tbody').on('change', 'select.tax_id', function() {
        var tr = $(this).parents('tr');

        var tax_rate = tr
            .find('select.tax_id')
            .find(':selected')
            .data('rate');
        var unit_price_inc_tax = __read_number(tr.find('input.pos_unit_price_inc_tax'));

        var discounted_unit_price = __get_principle(unit_price_inc_tax, tax_rate);
        var unit_price = get_unit_price_from_discounted_unit_price(tr, discounted_unit_price);
        __write_number(tr.find('input.pos_unit_price'), unit_price);
        pos_each_row(tr);
    });

    //If change in unit price including tax, update unit price
    $('table#pos_table tbody').on('change', 'input.pos_unit_price_inc_tax', function() {
        var unit_price_inc_tax = __read_number($(this));

        if (iraqi_selling_price_adjustment) {
            unit_price_inc_tax = round_to_iraqi_dinnar(unit_price_inc_tax);
            __write_number($(this), unit_price_inc_tax);
        }

        var tr = $(this).parents('tr');

        var tax_rate = tr
            .find('select.tax_id')
            .find(':selected')
            .data('rate');
        var quantity = __read_number(tr.find('input.pos_quantity'));

        var line_total = quantity * unit_price_inc_tax;
        var discounted_unit_price = __get_principle(unit_price_inc_tax, tax_rate);
        var unit_price = get_unit_price_from_discounted_unit_price(tr, discounted_unit_price);
        
        // Calculate line discount
        var original_line_total = quantity * (unit_price + __calculate_amount('percentage', tax_rate, unit_price));
        var line_discount = original_line_total - line_total;

        __write_number(tr.find('input.pos_unit_price'), unit_price);
        __write_number(tr.find('input.pos_line_total'), line_total, false, 2);
        __write_number(tr.find('input.pos_line_discount'), line_discount, false, 2);
        tr.find('span.price_inc_tax_display').text(__currency_trans_from_en(unit_price_inc_tax, true));
        tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));

        pos_each_row(tr);
        pos_total_row();
    });

    //Change max quantity rule if lot number changes
    $('table#pos_table tbody').on('change', 'select.lot_number', function() {
        var qty_element = $(this)
            .closest('tr')
            .find('input.pos_quantity');

        var tr = $(this).closest('tr');
        var multiplier = 1;
        var unit_name = '';
        var sub_unit_length = tr.find('select.sub_unit').length;
        if (sub_unit_length > 0) {
            var select = tr.find('select.sub_unit');
            multiplier = parseFloat(select.find(':selected').data('multiplier'));
            unit_name = select.find(':selected').data('unit_name');
        }
        var allow_overselling = qty_element.data('allow-overselling');
        if ($(this).val() && !allow_overselling) {
            var lot_qty = $('option:selected', $(this)).data('qty_available');
            var max_err_msg = $('option:selected', $(this)).data('msg-max');

            if (sub_unit_length > 0) {
                lot_qty = lot_qty / multiplier;
                var lot_qty_formated = __number_f(lot_qty, false);
                max_err_msg = __translate('lot_max_qty_error', {
                    max_val: lot_qty_formated,
                    unit_name: unit_name,
                });
            }

            qty_element.attr('data-rule-max-value', lot_qty);
            qty_element.attr('data-msg-max-value', max_err_msg);

            qty_element.rules('add', {
                'max-value': lot_qty,
                messages: {
                    'max-value': max_err_msg,
                },
            });
        } else {
            var default_qty = qty_element.data('qty_available');
            var default_err_msg = qty_element.data('msg_max_default');
            if (sub_unit_length > 0) {
                default_qty = default_qty / multiplier;
                var lot_qty_formated = __number_f(default_qty, false);
                default_err_msg = __translate('pos_max_qty_error', {
                    max_val: lot_qty_formated,
                    unit_name: unit_name,
                });
            }

            qty_element.attr('data-rule-max-value', default_qty);
            qty_element.attr('data-msg-max-value', default_err_msg);

            qty_element.rules('add', {
                'max-value': default_qty,
                messages: {
                    'max-value': default_err_msg,
                },
            });
        }
        qty_element.trigger('change');
    });

    //Change in row discount type or discount amount
    $('table#pos_table tbody').on(
        'change',
        'select.row_discount_type, input.row_discount_amount',
        function() {
            var tr = $(this).parents('tr');
            
            // Use pos_each_row to recalculate everything correctly (including order tax and discount)
            // This ensures discount is calculated correctly with all taxes included
            pos_each_row(tr);
            
            // Recalculate totals (this will sum all row discounts correctly - not recalculated as percentage)
            pos_total_row();
            round_row_to_iraqi_dinnar(tr);
        }
    );

    //Remove row on click on remove row
    $('table#pos_table tbody').on('click', 'i.pos_remove_row', function() {
        $(this)
            .parents('tr')
            .remove();
        pos_total_row();
    });

    //Cancel the invoice
    $('button#pos-cancel').click(function() {
        reset_pos_form();
    });

    //Save invoice as draft
    $('button#pos-draft').click(function() {
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        var is_valid = isValidPosForm();
        if (is_valid != true) {
            return;
        }

        var data = pos_form_obj.serialize();
        data = data + '&status=draft';
        var url = pos_form_obj.attr('action');

        $.ajax({
            method: 'POST',
            url: url,
            data: data,
            dataType: 'json',
            success: function(result) {
                if (result.success == 1) {
                    reset_pos_form();
                    toastr.success(result.msg);
                    get_recent_transactions('draft', $('div#tab_draft'));
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });

    //Save invoice as Quotation
    $('button#pos-quotation').click(function() {
        console.log("here")
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        var is_valid = isValidPosForm();
        if (is_valid != true) {
            return;
        }

        var data = pos_form_obj.serialize();
        data = data + '&status=quotation';
        var url = pos_form_obj.attr('action');

        // $.ajax({
        //     method: 'POST',
        //     url: url,
        //     data: data,
        //     dataType: 'json',
        //     success: function(result) {
        //         console.log(result)
        //         if (result.success == 1) {
        //             reset_pos_form();
        //             toastr.success(result.msg);

        //             //Check if enabled or not
        //             if (result.receipt.is_enabled) {
        //                 pos_print(result.receipt);
        //             }

        //             get_recent_transactions('quotation', $('div#tab_quotation'));
        //         } else {
        //             toastr.error(result.msg);
        //         }
        //     },
        // });
    });

   
    $('button#pos-quotation-notify').click(function() {
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        var is_valid = isValidPosForm();
        if (is_valid != true) {
            return;
        }

        var data = pos_form_obj.serialize();
        data = data + '&status=quotation&notify=1';
        var url = pos_form_obj.attr('action');

        $.ajax({
            method: 'POST',
            url: url,
            data: data,
            dataType: 'json',
            success: function(result) {
                if (result.success == 1) {
                    reset_pos_form();
                    toastr.success(result.msg);

                    //Check if enabled or not
                    if (result.receipt.is_enabled) {
                        pos_print(result.receipt);
                    }

                    get_recent_transactions('quotation', $('div#tab_quotation'));
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });

    //Finalize invoice, open payment modal
    $('button#pos-finalize').click(function() {
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        if ($('#reward_point_enabled').length) {
            var validate_rp = isValidatRewardPoint();
            if (!validate_rp['is_valid']) {
                toastr.error(validate_rp['msg']);
                return false;
            }
        }

        $('#modal_payment').modal('show');
    });

    $('#modal_payment').one('shown.bs.modal', function() {
        $('#modal_payment')
            .find('input')
            .filter(':visible:first')
            .focus()
            .select();
        if ($('form#edit_pos_sell_form').length == 0) {
            $(this).find('#method_0').change();
        }
    });

    //Finalize without showing payment options
    $('button.pos-express-finalize').click(function() {
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        if ($('#reward_point_enabled').length) {
            var validate_rp = isValidatRewardPoint();
            if (!validate_rp['is_valid']) {
                toastr.error(validate_rp['msg']);
                return false;
            }
        }

        var pay_method = $(this).data('pay_method');

        //If pay method is credit sale submit form
        if (pay_method == 'credit_sale') {
            $('#is_credit_sale').val(1);
            pos_form_obj.submit();
            return true;
        } else {
            if ($('#is_credit_sale').length) {
                $('#is_credit_sale').val(0);
            }
        }

        //Check for remaining balance & add it in 1st payment row
        var total_payable = __read_number($('input#final_total_input'));
        var total_paying = __read_number($('input#total_paying_input'));
        if (total_payable > total_paying) {
            var bal_due = total_payable - total_paying;

            var first_row = $('#payment_rows_div')
                .find('.payment-amount')
                .first();
            var first_row_val = __read_number(first_row);
            first_row_val = first_row_val + bal_due;
            __write_number(first_row, first_row_val);
            first_row.trigger('change');
        }

        //Change payment method.
        var payment_method_dropdown = $('#payment_rows_div')
            .find('.payment_types_dropdown')
            .first();
        
            payment_method_dropdown.val(pay_method);
            payment_method_dropdown.change();
        if (pay_method == 'card') {
            $('div#card_details_modal').modal('show');
        } else if (pay_method == 'suspend') {
            $('div#confirmSuspendModal').modal('show');
        } else {
            pos_form_obj.submit();
        }
    });

    $('div#card_details_modal').on('shown.bs.modal', function(e) {
        $('input#card_number').focus();
    });

    $('div#confirmSuspendModal').on('shown.bs.modal', function(e) {
        $(this)
            .find('textarea')
            .focus();
    });

    //on save card details
    $('button#pos-save-card').click(function() {
        $('input#card_number_0').val($('#card_number').val());
        $('input#card_holder_name_0').val($('#card_holder_name').val());
        $('input#card_transaction_number_0').val($('#card_transaction_number').val());
        $('select#card_type_0').val($('#card_type').val());
        $('input#card_month_0').val($('#card_month').val());
        $('input#card_year_0').val($('#card_year').val());
        $('input#card_security_0').val($('#card_security').val());

        $('div#card_details_modal').modal('hide');
        pos_form_obj.submit();
    });

    $('button#pos-suspend').click(function() {
        $('input#is_suspend').val(1);
        $('div#confirmSuspendModal').modal('hide');
        pos_form_obj.submit();
        $('input#is_suspend').val(0);
    });

    //fix select2 input issue on modal
    $('#modal_payment')
        .find('.select2')
        .each(function() {
            $(this).select2({
                dropdownParent: $('#modal_payment'),
            });
        });

    $('button#add-payment-row').click(function() {
        var row_index = $('#payment_row_index').val();
        
        // Validate row index
        if (!row_index || isNaN(parseInt(row_index))) {
            toastr.error('Invalid payment row index. Please refresh the page.');
            return;
        }
        
        // Disable button to prevent multiple rapid clicks
        $(this).prop('disabled', true);
        
        $.ajax({
            method: 'POST',
            url: '/sells/pos/get_payment_row',
            data: { row_index: row_index },
            dataType: 'html',
            timeout: 10000, // 10 second timeout
            success: function(result) {
                try {
                    if (result && result.trim() !== '') {
                        var appended = $('#payment_rows_div').append(result);
                        
                        // Increment row index immediately after successful append
                        $('#payment_row_index').val(parseInt(row_index) + 1);

                        var total_payable = __read_number($('input#final_total_input'));
                        var total_paying = __read_number($('input#total_paying_input'));
                        var b_due = total_payable - total_paying;
                        
                        // Wait for DOM to be ready before manipulating elements
                        setTimeout(function() {
                            try {
                                var payment_amount_input = $(appended).find('input.payment-amount').last();
                                if (payment_amount_input.length) {
                                    payment_amount_input
                                        .val(__currency_trans_from_en(b_due, false))
                                        .change()
                                        .select()
                                        .focus();
                                }
                                
                                // Initialize select2 dropdowns
                                var select2_elements = $(appended).find('.select2');
                                if (select2_elements.length) {
                                    __select2(select2_elements);
                                }
                                
                                // Trigger payment method change
                                var method_dropdown = $(appended).find('#method_' + row_index);
                                if (method_dropdown.length) {
                                    method_dropdown.change();
                                }
                            } catch (dom_error) {
                                console.error('Error initializing payment row elements:', dom_error);
                            }
                        }, 100);
                    } else {
                        toastr.error('Empty response received. Please try again.');
                    }
                } catch (error) {
                    console.error('Error processing payment row response:', error);
                    toastr.error('Failed to process payment row. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Payment row AJAX error:', {xhr: xhr, status: status, error: error});
                
                var error_message = 'Failed to add payment row. ';
                if (status === 'timeout') {
                    error_message += 'Request timed out.';
                } else if (xhr.status === 500) {
                    error_message += 'Server error occurred.';
                } else if (xhr.status === 404) {
                    error_message += 'Payment row endpoint not found.';
                } else {
                    error_message += 'Please try again.';
                }
                
                toastr.error(error_message);
            },
            complete: function() {
                // Re-enable button after request completes
                $('button#add-payment-row').prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.remove_payment_row', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $(this)
                    .closest('.payment_row')
                    .remove();
                calculate_balance_due();
            }
        });
    });

    pos_form_validator = pos_form_obj.validate({
        submitHandler: function(form) {
            var cnf = true;

            // Calculate balance due properly - ensure all payment rows are included
            var total_payable = __read_number($('input#final_total_input'));
            var total_paying = parseFloat($('input#was_customer_wallet').val()) || 0;
            
            // Use more specific selector to ensure all payment amounts are captured
            $('#payment_rows_div .payment-amount').each(function() {
                var payment_val = __read_number($(this));
                if (!isNaN(payment_val) && payment_val > 0) {
                    total_paying += payment_val;
                }
            });
            
            var balance_due = total_payable - total_paying;
            
            //Ignore if the difference is less than 0.5 (allow small rounding differences)
            if (balance_due >= 0.5) {
                cnf = confirm(LANG.paid_amount_is_less_than_payable);
            } else if (balance_due < -0.5) {
                // If paying more than total, show warning but allow if user confirms
                cnf = confirm(LANG.paid_amount_is_more_than_payable || 'You are paying more than the total amount. Continue?');
            }

            if (cnf) {
                $('div.pos-processing').show();
                $('#pos-save').attr('disabled', 'true');
                
                // Ensure form data includes all payment rows
                var data = $(form).serialize();
                data = data + '&status=final';
                var url = $(form).attr('action');
                
                $.ajax({
                    method: 'POST',
                    url: url,
                    data: data,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == 1) {
                            $('#modal_payment').modal('hide');
                            toastr.success(result.msg);
                            reset_pos_form();

                            //Check if enabled or not
                            if (result.receipt.is_enabled) {
                                pos_print(result.receipt);
                            }

                            get_recent_transactions('final', $('div#tab_final'));
                        } else {
                            toastr.error(result.msg);
                        }

                        $('div.pos-processing').hide();
                        $('#pos-save').removeAttr('disabled');
                    },
                    error: function() {
                        toastr.error('Payment processing failed. Please try again.');
                        $('div.pos-processing').hide();
                        $('#pos-save').removeAttr('disabled');
                    }
                });
            }
            return false;
        },
    });

    $(document).on('change', '.payment-amount', function() {
        // Clear any previous validation errors for this payment row
        $(this).siblings('.error').remove();
        
        // Validate the payment amount
        var amount = __read_number($(this));
        if ($(this).val() && (isNaN(amount) || amount <= 0)) {
            var error = '<span class="error">Invalid payment amount</span>';
            $(this).after(error);
        }
        
        // Ensure balance is recalculated when any payment amount changes
        calculate_balance_due();
    });

    //Sync discount values from hidden fields to modal when modal is shown
    $('#posEditDiscountModal').on('shown.bs.modal', function() {
        // Sync discount type from hidden field to modal select
        var discount_type = $('input#discount_type').val();
        if (discount_type) {
            $('select#discount_type_modal').val(discount_type).trigger('change');
        }
        // Sync discount amount from hidden field to modal input
        var discount_amount = __read_number($('input#discount_amount'));
        if (discount_amount !== null && !isNaN(discount_amount)) {
            __write_number($('input#discount_amount_modal'), discount_amount);
        }
    });

    // Update hidden field immediately when discount type changes in modal to prevent reset
    $(document).on('change', 'select#discount_type_modal', function() {
        var selected_value = $(this).val();
        if (selected_value) {
            // Update the hidden field immediately so it stays in sync
            $('input#discount_type').val(selected_value);
        }
    });

    //Update discount
    $('button#posEditDiscountModalUpdate').click(function() {
        //Close modal
        $('div#posEditDiscountModal').modal('hide');

        //Update values
        $('input#discount_type').val($('select#discount_type_modal').val());
        __write_number($('input#discount_amount'), __read_number($('input#discount_amount_modal')));

        if ($('#reward_point_enabled').length) {
            var reward_validation = isValidatRewardPoint();
            if (!reward_validation['is_valid']) {
                toastr.error(reward_validation['msg']);
                $('#rp_redeemed_modal').val(0);
                $('#rp_redeemed_modal').change();
            }
            updateRedeemedAmount();
        }

        // Recalculate each row to update price inc tax display
        $('table#pos_table tbody tr').each(function() {
            pos_each_row($(this));
        });
        pos_total_row();
    });

    //Shipping
    $('button#posShippingModalUpdate').click(function() {
        //Close modal
        $('div#posShippingModal').modal('hide');

        //update shipping details
        $('input#shipping_details').val($('#shipping_details_modal').val());

        $('input#shipping_address').val($('#shipping_address_modal').val());
        $('input#shipping_status').val($('#shipping_status_modal').val());
        $('input#delivered_to').val($('#delivered_to_modal').val());

        //Update shipping charges
        __write_number(
            $('input#shipping_charges'),
            __read_number($('input#shipping_charges_modal'))
        );

        //$('input#shipping_charges').val(__read_number($('input#shipping_charges_modal')));

        pos_total_row();
    });

    $('#posShippingModal').on('shown.bs.modal', function() {
        $('#posShippingModal')
            .find('#shipping_details_modal')
            .filter(':visible:first')
            .focus()
            .select();
    });

    $(document).on('shown.bs.modal', '.row_edit_product_price_model', function() {
        $('.row_edit_product_price_model')
            .find('input')
            .filter(':visible:first')
            .focus()
            .select();
    });

    //Update Order tax
    $('button#posEditOrderTaxModalUpdate').click(function() {
        //Close modal
        $('div#posEditOrderTaxModal').modal('hide');

        var tax_obj = $('select#order_tax_modal');
        var tax_id = tax_obj.val();
        var tax_rate = tax_obj.find(':selected').data('rate');

        if (!tax_id || !tax_rate) {
            // Clear order tax if no selection
            $('input#tax_rate_id').val('');
            __write_number($('input#tax_calculation_amount'), 0);
        } else {
            $('input#tax_rate_id').val(tax_id);
            __write_number($('input#tax_calculation_amount'), tax_rate);
        }
        
        // Force immediate recalculation of all rows to include order tax in Price inc. Tax column
        $('table#pos_table tbody tr').each(function() {
            var tr = $(this);
            // Recalculate the row which will update Price inc. Tax with order tax
            pos_each_row(tr);
        });
        
        // Then recalculate totals
        pos_total_row();
    });

    //Displays list of recent transactions
    get_recent_transactions('final', $('div#tab_final'));
    get_recent_transactions('quotation', $('div#tab_quotation'));
    get_recent_transactions('draft', $('div#tab_draft'));

    $(document).on('click', '.add_new_customer', function() {
        $('#customer_id').select2('close');
        var name = $(this).data('name');
        $('.contact_modal')
            .find('input#name')
            .val(name);
        $('.contact_modal')
            .find('select#contact_type')
            .val('customer')
            .closest('div.contact_type_div')
            .addClass('hide');
        $('.contact_modal').modal('show');
    });
    $('form#quick_add_contact')
        .submit(function(e) {
            e.preventDefault();
        })
        .validate({
            rules: {
                contact_id: {
                    remote: {
                        url: '/contacts/check-contact-id',
                        type: 'post',
                        data: {
                            contact_id: function() {
                                return $('#contact_id').val();
                            },
                            hidden_id: function() {
                                if ($('#hidden_id').length) {
                                    return $('#hidden_id').val();
                                } else {
                                    return '';
                                }
                            },
                        },
                    },
                },
            },
            messages: {
                contact_id: {
                    remote: LANG.contact_id_already_exists,
                },
            },
            submitHandler: function(form) {
                $(form)
                    .find('button[type="submit"]')
                    .attr('disabled', true);
                var data = $(form).serialize();
                $.ajax({
                    method: 'POST',
                    url: $(form).attr('action'),
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            $('select#customer_id').append(
                                $('<option>', { value: result.data.id, text: result.data.name })
                            );
                            $('select#customer_id')
                                .val(result.data.id)
                                .trigger('change');
                            $('div.contact_modal').modal('hide');
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            },
        });
    $('.contact_modal').on('hidden.bs.modal', function() {
        $('form#quick_add_contact')
            .find('button[type="submit"]')
            .removeAttr('disabled');
        $('form#quick_add_contact')[0].reset();
    });

    //Updates for add sell
    $('select#discount_type, input#discount_amount, input#shipping_charges, \
        input#rp_redeemed_amount').change(function() {
        pos_total_row();
    });
    $('select#tax_rate_id').change(function() {
        var tax_rate = $(this)
            .find(':selected')
            .data('rate');
        __write_number($('input#tax_calculation_amount'), tax_rate);
        pos_total_row();
    });
    //Datetime picker
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });

    //Direct sell submit
    sell_form = $('form#add_sell_form');
    if ($('form#edit_sell_form').length) {
        sell_form = $('form#edit_sell_form');
        pos_total_row();
    }
    sell_form_validator = sell_form.validate();

    $('button#submit-sell').click(function(e) {
        e.preventDefault();
        //check if internet available or not
        var offline = Offline.state;

        if(offline == 'down'){
            toastr.error('No internet connection available');
            return offline;
        }
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        if ($('#reward_point_enabled').length) {
            var validate_rp = isValidatRewardPoint();
            if (!validate_rp['is_valid']) {
                toastr.error(validate_rp['msg']);
                return false;
            }
        }

        if (sell_form.valid()) {
            window.onbeforeunload = null;
            sell_form.submit();
        }
    });

    //Show product list.
    get_product_suggestion_list(
        $('select#product_category').val(),
        $('select#product_brand').val(),
        $('input#location_id').val(),
        null
    );
    $('select#product_category, select#product_brand').on('change', function(e) {
        $('input#suggestion_page').val(1);
        $('input#cat_id_suggestion').val($('select#product_category').val()); //  temp cat id and brand id if there is any temp data
        $('input#brand_id_suggestion').val($('select#product_brand').val()); //  temp cat id and brand id if there is any temp data
        var location_id = $('input#location_id').val();
        if (location_id != '' || location_id != undefined) {
            get_product_suggestion_list(
                $('select#product_category').val(),
                $('select#product_brand').val(),
                $('input#location_id').val(),
                null
            );
        }
    });

    $(document).on('click', 'div.product_box', function() {
        //Check if location is not set then show error message.
        if ($('input#location_id').val() == '') {
            toastr.warning(LANG.select_location);
        } else {
            pos_product_row($(this).data('variation_id'));
        }
    });

    $(document).on('shown.bs.modal', '.row_description_modal', function() {
        $(this)
            .find('textarea')
            .first()
            .focus();
    });

    //Press enter on search product to jump into last quantty and vice-versa
    $('#search_product').keydown(function(e) {
        var key = e.which;
        if (key == 9) {
            // the tab key code
            e.preventDefault();
            if ($('#pos_table tbody tr').length > 0) {
                $('#pos_table tbody tr:last')
                    .find('input.pos_quantity')
                    .focus()
                    .select();
            }
        }
    });
    $('#pos_table').on('keypress', 'input.pos_quantity', function(e) {
        var key = e.which;
        if (key == 13) {
            // the enter key code
            $('#search_product').focus();
        }
    });

    $('#exchange_rate').change(function() {
        var curr_exchange_rate = 1;
        if ($(this).val()) {
            curr_exchange_rate = __read_number($(this));
        }
        var total_payable = __read_number($('input#final_total_input'));
        var shown_total = total_payable * curr_exchange_rate;
        $('span#total_payable').text(__currency_trans_from_en(shown_total, false));
    });

    $('select#price_group').change(function() {
        //If types of service selected then price group dropdown has no effect
        if ($('#types_of_service_price_group').length > 0 && 
            $('#types_of_service_price_group').val()) {
            return false;
        }
        var curr_val = $(this).val();
        var prev_value = $('input#hidden_price_group').val();
        $('input#hidden_price_group').val(curr_val);
        if (curr_val != prev_value && $('table#pos_table tbody tr').length > 0) {
            swal({
                title: LANG.sure,
                text: LANG.form_will_get_reset,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(willDelete => {
                if (willDelete) {
                    if ($('form#edit_pos_sell_form').length > 0) {
                        $('table#pos_table tbody').html('');
                        pos_total_row();
                    } else {
                        reset_pos_form();
                    }

                    $('input#hidden_price_group').val(curr_val);
                    $('select#price_group')
                        .val(curr_val)
                        .change();
                } else {
                    $('input#hidden_price_group').val(prev_value);
                    $('select#price_group')
                        .val(prev_value)
                        .change();
                }
            });
        }
    });

    //Quick add product
    $(document).on('click', 'button.pos_add_quick_product', function() {
        var url = $(this).data('href');
        var container = $(this).data('container');
        $.ajax({
            url: url + '?product_for=pos',
            dataType: 'html',
            success: function(result) {
                $(container)
                    .html(result)
                    .modal('show');
                $('.os_exp_date').datepicker({
                    autoclose: true,
                    format: 'dd-mm-yyyy',
                    clearBtn: true,
                });
            },
        });
    });

    $(document).on('change', 'form#quick_add_product_form input#single_dpp', function() {
        var unit_price = __read_number($(this));
        $('table#quick_product_opening_stock_table tbody tr').each(function() {
            var input = $(this).find('input.unit_price');
            __write_number(input, unit_price);
            input.change();
        });
    });

    $(document).on('quickProductAdded', function(e) {
        //Check if location is not set then show error message.
        if ($('input#location_id').val() == '') {
            toastr.warning(LANG.select_location);
        } else {
            pos_product_row(e.variation.id);
        }
    });

    $('div.view_modal').on('show.bs.modal', function() {
        __currency_convert_recursively($(this));
    });

    $('table#pos_table').on('change', 'select.sub_unit', function() {
        var tr = $(this).closest('tr');
        var base_unit_selling_price = tr.find('input.hidden_base_unit_sell_price').val();

        var selected_option = $(this).find(':selected');

        var multiplier = parseFloat(selected_option.data('multiplier'));

        var allow_decimal = parseInt(selected_option.data('allow_decimal'));

        tr.find('input.base_unit_multiplier').val(multiplier);

        // var unit_sp = base_unit_selling_price * multiplier;
        var unit_sp = parseFloat(selected_option.data('unit_price'));

        var sp_element = tr.find('input.pos_unit_price');
        __write_number(sp_element, unit_sp);

        sp_element.change();

        var qty_element = tr.find('input.pos_quantity');
        var base_max_avlbl = qty_element.data('qty_available');
        var error_msg_line = 'pos_max_qty_error';

        if (tr.find('select.lot_number').length > 0) {
            var lot_select = tr.find('select.lot_number');
            if (lot_select.val()) {
                base_max_avlbl = lot_select.find(':selected').data('qty_available');
                error_msg_line = 'lot_max_qty_error';
            }
        }

        qty_element.attr('data-decimal', allow_decimal);
        var abs_digit = true;
        if (allow_decimal) {
            abs_digit = false;
        }
        qty_element.rules('add', {
            abs_digit: abs_digit,
        });

        if (base_max_avlbl) {
            var max_avlbl = parseFloat(base_max_avlbl) / multiplier;
            var formated_max_avlbl = __number_f(max_avlbl);
            var unit_name = selected_option.data('unit_name');
            var max_err_msg = __translate(error_msg_line, {
                max_val: formated_max_avlbl,
                unit_name: unit_name,
            });
            qty_element.attr('data-rule-max-value', max_avlbl);
            qty_element.attr('data-msg-max-value', max_err_msg);
            qty_element.rules('add', {
                'max-value': max_avlbl,
                messages: {
                    'max-value': max_err_msg,
                },
            });
            qty_element.trigger('change');
        }
    });

    //Confirmation before page load.
    window.onbeforeunload = function() {
        if($('form#edit_pos_sell_form').length == 0){
            if($('table#pos_table tbody tr').length > 0) {
                return LANG.sure;
            } else {
                return null;
            }
        }
    }
});

function get_product_suggestion_list(category_id, brand_id, location_id, url = null) {

    if($('div#product_list_body').length == 0) {
        return false;
    }

    if (url == null) {
        url = '/sells/pos/get-product-suggestion';
    }
    $('#suggestion_page_loader').fadeIn(700);
    var page = $('input#suggestion_page').val();
    if (page == 1) {
        $('div#product_list_body').html('');
    }
    if ($('div#product_list_body').find('input#no_products_found').length > 0) {
        $('#suggestion_page_loader').fadeOut(700);
        return false;
    }
    $.ajax({
        method: 'GET',
        url: url,
        data: {
            category_id: category_id,
            brand_id: brand_id,
            location_id: location_id,
            page: page,
        },
        dataType: 'html',
        success: function(result) {
            $('div#product_list_body').append(result);
            $('#suggestion_page_loader').fadeOut(700);
        },
    });
}

//Get recent transactions
function get_recent_transactions(status, element_obj) {
    if (element_obj.length == 0) {
        return false;
    }

    $.ajax({
        method: 'GET',
        url: '/sells/pos/get-recent-transactions',
        data: { status: status },
        dataType: 'html',
        success: function(result) {
            element_obj.html(result);
            __currency_convert_recursively(element_obj);
        },
    });
}

function pos_product_row(variation_id, purchase_line_id = null) {
    //Get item addition method
    var item_addtn_method = 0;
    var add_via_ajax = true;

    if ($('#item_addition_method').length) {
        item_addtn_method = $('#item_addition_method').val();
    }

    if (item_addtn_method == 0) {
        add_via_ajax = true;
    } else {
        var is_added = false;

        //Search for variation id in each row of pos table
        $('#pos_table tbody')
            .find('tr')
            .each(function() {
                var row_v_id = $(this)
                    .find('.row_variation_id')
                    .val();
                var enable_sr_no = $(this)
                    .find('.enable_sr_no')
                    .val();
                var modifiers_exist = false;
                if ($(this).find('input.modifiers_exist').length > 0) {
                    modifiers_exist = true;
                }

                if (
                    row_v_id == variation_id &&
                    enable_sr_no !== '1' &&
                    !modifiers_exist &&
                    !is_added
                ) {
                    add_via_ajax = false;
                    is_added = true;

                    //Increment product quantity
                    qty_element = $(this).find('.pos_quantity');
                    var qty = __read_number(qty_element);
                    __write_number(qty_element, qty + 1);
                    qty_element.change();

                    round_row_to_iraqi_dinnar($(this));

                    $('input#search_product')
                        .focus()
                        .select();
                }
            });
    }

    if (add_via_ajax) {
        var product_row = $('input#product_row_count').val();
        var location_id = $('input#location_id').val();
        var customer_id = $('select#customer_id').val();
        var is_direct_sell = false;
        if (
            $('input[name="is_direct_sale"]').length > 0 &&
            $('input[name="is_direct_sale"]').val() == 1
        ) {
            is_direct_sell = true;
        }

        var price_group = '';
        if ($('#price_group').length > 0) {
            price_group = parseInt($('#price_group').val());
        }

        //If default price group present
        if ($('#default_price_group').length > 0 && 
            !price_group) {
            price_group = $('#default_price_group').val();
        }

        //If types of service selected give more priority
        if ($('#types_of_service_price_group').length > 0 && 
            $('#types_of_service_price_group').val()) {
            price_group = $('#types_of_service_price_group').val();
        }

        $.ajax({
            method: 'GET',
            url: '/sells/pos/get_product_row/' + variation_id + '/' + location_id,
            async: false,
            data: {
                product_row: product_row-1, // minus 1 added here to start from 0
                customer_id: customer_id,
                is_direct_sell: is_direct_sell,
                price_group: price_group,
                purchase_line_id: purchase_line_id
            },
            dataType: 'json',
            success: function(result) {
                if (result.success) {
                    $('table#pos_table tbody')
                        .append(result.html_content)
                        .find('input.pos_quantity');
                    //increment row count
                    $('input#product_row_count').val(parseInt(product_row) + 1);
                    var this_row = $('table#pos_table tbody')
                        .find('tr')
                        .last();
                    pos_each_row(this_row);

                    //For initial discount if present
                    var line_total = __read_number(this_row.find('input.pos_line_total'));
                    this_row.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));
                    
                    // Initialize line discount
                    var unit_price = __read_number(this_row.find('input.pos_unit_price'));
                    var tax_rate = this_row.find('select.tax_id').find(':selected').data('rate');
                    var qty = __read_number(this_row.find('input.pos_quantity'));
                    var unit_price_inc_tax = __read_number(this_row.find('input.pos_unit_price_inc_tax'));
                    var original_line_total = qty * (unit_price + __calculate_amount('percentage', tax_rate, unit_price));
                    var line_discount = original_line_total - line_total;
                    __write_number(this_row.find('input.pos_line_discount'), line_discount, false, 2);

                    pos_total_row();

                    //Check if multipler is present then multiply it when a new row is added.
                    if(__getUnitMultiplier(this_row) > 1){
                        this_row.find('select.sub_unit').trigger('change');
                    }

                    if (result.enable_sr_no == '1') {
                        var new_row = $('table#pos_table tbody')
                            .find('tr')
                            .last();
                        new_row.find('.add-pos-row-description').trigger('click');
                    }

                    round_row_to_iraqi_dinnar(this_row);
                    __currency_convert_recursively(this_row);

                    $('input#search_product')
                        .focus()
                        .select();

                    //Used in restaurant module
                    if (result.html_modifier) {
                        $('table#pos_table tbody')
                            .find('tr')
                            .last()
                            .find('td:first')
                            .append(result.html_modifier);
                    }

                    //scroll bottom of items list
                    $(".pos_product_div").animate({ scrollTop: $('.pos_product_div').prop("scrollHeight")}, 1000);
                } else {
                    toastr.error(result.msg);
                    $('input#search_product')
                        .focus()
                        .select();
                }
            },
        });
    }
}

//Update values for each row
function pos_each_row(row_obj) {
    var unit_price = __read_number(row_obj.find('input.pos_unit_price'));

    var discounted_unit_price = calculate_discounted_unit_price(row_obj);
    
    // Get tax rate - check if it's a select or hidden input
    var tax_rate = 0;
    var tax_element = row_obj.find('.tax_id');
    if (tax_element.is('select')) {
        tax_rate = tax_element.find(':selected').data('rate') || 0;
    } else {
        tax_rate = tax_element.data('rate') || 0;
    }

    // Calculate unit price with item tax (before order tax)
    var unit_price_inc_item_tax =
        discounted_unit_price + __calculate_amount('percentage', tax_rate, discounted_unit_price);
    
    // Get order tax rate if selected - ONLY include if user has explicitly selected an order tax
    var order_tax_rate = __read_number($('#tax_calculation_amount')) || 0;
    var tax_rate_id = $('#tax_rate_id').val();
    
    // IMPORTANT: Only include order tax in Price inc. Tax display if user has explicitly selected it
    // Check if tax_rate_id exists and has a value (not empty string)
    var has_order_tax = tax_rate_id && tax_rate_id.toString().trim() !== '' && order_tax_rate > 0;
    
    // Calculate unit price including order tax ONLY if order tax is explicitly selected
    var unit_price_inc_tax = unit_price_inc_item_tax;
    if (has_order_tax) {
        // Apply order tax on top of item tax
        unit_price_inc_tax = unit_price_inc_item_tax * (1 + order_tax_rate / 100);
    }
    
    __write_number(row_obj.find('input.pos_unit_price_inc_tax'), unit_price_inc_item_tax);
    
    // Update the display of price inc tax (including order tax ONLY if explicitly selected)
    row_obj.find('span.price_inc_tax_display').text(__currency_trans_from_en(unit_price_inc_tax, true));

    var qty = __read_number(row_obj.find('input.pos_quantity'));
    
    // Calculate line total: For VAT-inclusive products, use consistent Unit_Price_Inc_Tax × Quantity formula
    // This matches the initial template calculation: $__initial_subtotal = $__initial_qty * $__initial_unit_inc
    // Use the stored unit_price_inc_tax value consistently for both calculation and display
    var stored_unit_price_inc_tax = __read_number(row_obj.find('input.pos_unit_price_inc_tax'));
    var line_total = qty * stored_unit_price_inc_tax;
    __write_number(row_obj.find('input.pos_line_total'), line_total, false, 2);
    
    // Update subtotal display - use the same consistent calculation
    row_obj.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));
    
    // Calculate line discount (difference between original price and discounted price including all taxes)
    // Original price with all taxes (item tax + order tax if selected)
    var original_unit_price_inc_all_tax = (unit_price + __calculate_amount('percentage', tax_rate, unit_price));
    if (has_order_tax) {
        original_unit_price_inc_all_tax = original_unit_price_inc_all_tax * (1 + order_tax_rate / 100);
    }
    var original_line_total = qty * original_unit_price_inc_all_tax;
    // Discount is the difference between original and discounted (both including all taxes)
    // This preserves the actual discount amount (fixed or percentage) from the product row
    var line_discount = original_line_total - line_total;
    if (line_discount < 0) line_discount = 0; // Ensure non-negative
    __write_number(row_obj.find('input.pos_line_discount'), line_discount, false, 2);

    __write_number(row_obj.find('input.item_tax'), unit_price_inc_item_tax - discounted_unit_price);
}

// Update Price inc. Tax column to include order tax per unit
function updatePriceIncTaxWithOrderTax(price_total, order_level_tax) {
    // Only update if order tax exists and price_total > 0
    if (order_level_tax > 0 && price_total > 0) {
        $('table#pos_table tbody tr').each(function() {
            var tr = $(this);
            var line_total = __read_number(tr.find('input.pos_line_total'));
            
            // Calculate order tax per unit for this row (proportional to line total)
            var order_tax_per_unit = 0;
            if (price_total > 0) {
                var order_tax_for_line = (line_total / price_total) * order_level_tax;
                var qty = __read_number(tr.find('input.pos_quantity'));
                if (qty > 0) {
                    order_tax_per_unit = order_tax_for_line / qty;
                }
            }
            
            // Get current unit price inc tax (with item tax)
            var current_unit_price_inc_tax = __read_number(tr.find('input.pos_unit_price_inc_tax'));
            
            // Add order tax per unit
            var unit_price_inc_tax_with_order_tax = current_unit_price_inc_tax + order_tax_per_unit;
            
            // Update the display (but keep the hidden input as is for calculations)
            tr.find('span.price_inc_tax_display').text(__currency_trans_from_en(unit_price_inc_tax_with_order_tax, true));
        });
    } else {
        // If no order tax, just show the regular price inc tax
        $('table#pos_table tbody tr').each(function() {
            var tr = $(this);
            var unit_price_inc_tax = __read_number(tr.find('input.pos_unit_price_inc_tax'));
            tr.find('span.price_inc_tax_display').text(__currency_trans_from_en(unit_price_inc_tax, true));
        });
    }
}

function pos_total_row() {
    var total_quantity = 0;
    var price_total = 0;
    var total_row_discounts = 0;
    var total_row_taxes = 0;

    $('table#pos_table tbody tr').each(function() {
        total_quantity = total_quantity + __read_number($(this).find('input.pos_quantity'));
        price_total = price_total + __read_number($(this).find('input.pos_line_total'));
        
        // Sum all row discounts
        var row_discount = __read_number($(this).find('input.pos_line_discount'));
        if (isNaN(row_discount)) {
            row_discount = 0;
        }
        total_row_discounts = total_row_discounts + row_discount;
        
        // Sum all row taxes (item_tax * quantity)
        var item_tax = __read_number($(this).find('input.item_tax'));
        var qty = __read_number($(this).find('input.pos_quantity'));
        var row_tax = item_tax * qty;
        total_row_taxes = total_row_taxes + row_tax;
    });

    //Go through the modifier prices.
    $('input.modifiers_price').each(function() {
        price_total = price_total + __read_number($(this));
    });

    //updating shipping charges
    $('span#shipping_charges_amount').text(
        __currency_trans_from_en(__read_number($('input#shipping_charges_modal')), false)
    );

    $('span.total_quantity').each(function() {
        $(this).html(__number_f(total_quantity));
    });

    //$('span.unit_price_total').html(unit_price_total);
    $('span.price_total').html(__currency_trans_from_en(price_total, false));
    calculate_billing_details(price_total, total_row_discounts, total_row_taxes);
}

function calculate_billing_details(price_total, total_row_discounts, total_row_taxes) {
    // IMPORTANT: Use ONLY total_row_discounts (sum of all item discounts)
    // Do NOT use order-level discount - the bottom discount should be the sum of all product discounts
    // This preserves the actual discount amounts (fixed or percentage) from each product, not recalculated
    var discount = 0;
    
    if (typeof total_row_discounts !== 'undefined' && total_row_discounts > 0) {
        discount = total_row_discounts;
    } else {
        var discount_type = $('#discount_type').val();
        var discount_amount = __read_number($('#discount_amount'));
        if (discount_type && discount_amount > 0) {
            var order_level_discount = pos_discount(price_total);
            discount = order_level_discount || 0;
        } else {
            discount = 0;
        }
    }
    
    if ($('#reward_point_enabled').length) {
        total_customer_reward = $('#rp_redeemed_amount').val();
        discount = parseFloat(discount) + parseFloat(total_customer_reward);

        if ($('input[name="is_direct_sale"]').length <= 0) {
            $('span#total_discount').text(__currency_trans_from_en(discount, false));
        }
    } else {
        // Update discount display with sum of all row discounts (not recalculated as percentage)
        $('span#total_discount').text(__currency_trans_from_en(discount, false));
    }

    // Calculate order-level tax (tax on the total after discounts)
    var order_level_tax = pos_order_tax(price_total, discount);
    
    // Use total_row_taxes if provided, otherwise calculate from rows
    var item_taxes_total = 0;
    if (typeof total_row_taxes !== 'undefined' && total_row_taxes > 0) {
        item_taxes_total = total_row_taxes;
    } else {
        // Calculate item taxes from rows (tax on each product)
        $('table#pos_table tbody tr').each(function() {
            var item_tax = __read_number($(this).find('input.item_tax'));
            var qty = __read_number($(this).find('input.pos_quantity'));
            item_taxes_total += item_tax * qty;
        });
    }
    
    // Total order tax displayed = item taxes (already in price_total) + order-level tax
    // Note: price_total already includes item taxes, so order_tax at bottom shows order-level tax + item taxes for clarity
    var order_tax = item_taxes_total + order_level_tax;
    $('span#order_tax').text(__currency_trans_from_en(order_tax, false));
    
    // Update Price inc. Tax column - order tax is now included in pos_each_row
    // Recalculate all rows to ensure order tax is included ONLY if explicitly selected
    var tax_rate_id_check = $('#tax_rate_id').val();
    var order_tax_rate_check = __read_number($('#tax_calculation_amount')) || 0;
    var has_order_tax_check = tax_rate_id_check && tax_rate_id_check.toString().trim() !== '' && order_tax_rate_check > 0;
    
    // Only update Price inc. Tax display if order tax is explicitly selected
    if (has_order_tax_check) {
        $('table#pos_table tbody tr').each(function() {
            pos_each_row($(this));
        });
    } else {
        // If no order tax, just show price with item tax only
        $('table#pos_table tbody tr').each(function() {
            var tr = $(this);
            var unit_price_inc_item_tax = __read_number(tr.find('input.pos_unit_price_inc_tax'));
            tr.find('span.price_inc_tax_display').text(__currency_trans_from_en(unit_price_inc_item_tax, true));
        });
    }

    //Add shipping charges.
    var shipping_charges = __read_number($('input#shipping_charges'));

    //Add packaging charge
    var packing_charge = 0;
    if ($('#types_of_service_id').length > 0 && 
            $('#types_of_service_id').val()) {
        packing_charge = __calculate_amount($('#packing_charge_type').val(), 
            __read_number($('input#packing_charge')), price_total);

        $('#packing_charge_text').text(__currency_trans_from_en(packing_charge, false));
    }

    // Total payable = price_total (already includes item taxes) + order_level_tax - discount + shipping + packing
    var total_payable = price_total + order_level_tax - discount + shipping_charges + packing_charge;

    __write_number($('input#final_total_input'), total_payable);
    var curr_exchange_rate = 1;
    if ($('#exchange_rate').length > 0 && $('#exchange_rate').val()) {
        curr_exchange_rate = __read_number($('#exchange_rate'));
    }
    var shown_total = total_payable * curr_exchange_rate;
    $('span#total_payable').text(__currency_trans_from_en(shown_total, false));

    $('span.total_payable_span').text(__currency_trans_from_en(total_payable, true));

    //Update payment amount for both new and edit forms when total changes
    // For edit forms, update the first payment amount if it exists
    if ($('.payment-amount').length > 0) {
        var first_payment = $('.payment-amount').first();
        // Only update if it's a new form, or if it's an edit form and we want to update payment
        if ($('form#edit_pos_sell_form').length == 0) {
            __write_number(first_payment, total_payable);
        } else {
            // For edit forms, update payment amount to match new total (user can adjust if needed)
            var current_payment_total = 0;
            $('.payment-amount').each(function() {
                current_payment_total += __read_number($(this));
            });
            // If current payment total matches old total, update first payment to new total
            // Otherwise, let user manually adjust
            var old_total = __read_number($('input#final_total_input').data('old-total')) || __read_number($('input#final_total_input'));
            if (Math.abs(current_payment_total - old_total) < 0.01) {
                __write_number(first_payment, total_payable);
            }
        }
    }

    $(document).trigger('invoice_total_calculated');

    calculate_balance_due();
}

function pos_discount(total_amount) {
    var calculation_type = $('#discount_type').val();
    var calculation_amount = __read_number($('#discount_amount'));

    var discount = __calculate_amount(calculation_type, calculation_amount, total_amount);

    // Don't update display here - it will be updated in calculate_billing_details
    // $('span#total_discount').text(__currency_trans_from_en(discount, false));

    return discount;
}

function pos_order_tax(price_total, discount) {
    var tax_rate_id = $('#tax_rate_id').val();
    var calculation_type = 'percentage';
    var calculation_amount = __read_number($('#tax_calculation_amount'));
    var total_amount = price_total - discount;

    if (tax_rate_id) {
        var order_tax = __calculate_amount(calculation_type, calculation_amount, total_amount);
    } else {
        var order_tax = 0;
    }

    // Don't update display here - it will be updated in calculate_billing_details
    // $('span#order_tax').text(__currency_trans_from_en(order_tax, false));

    return order_tax;
}

function calculate_balance_due() {
    var total_payable = __read_number($('#final_total_input'));
    var total_paying = parseFloat($('input#was_customer_wallet').val()) || 0;
    
    if(parseFloat($('input#was_customer_wallet').val()) > 0){
        $('span.total_paying').css('color', 'brown');
    }else{
        $('span.total_paying').css('color', 'white');
    }
    
    // Ensure we get all payment amounts from all payment rows
    $('#payment_rows_div .payment-amount').each(function() {
        var payment_val = __read_number($(this));
        if (!isNaN(payment_val) && payment_val > 0) {
            total_paying += payment_val;
        }
    });
    
    var bal_due = total_payable - total_paying;
    var change_return = 0;

    //change_return
    if (bal_due < 0 || Math.abs(bal_due) < 0.05) {
        __write_number($('input#change_return'), bal_due * -1);
        $('span.change_return_span').text(__currency_trans_from_en(bal_due * -1, true));
        change_return = bal_due * -1;
        bal_due = 0;
    } else {
        __write_number($('input#change_return'), 0);
        $('span.change_return_span').text(__currency_trans_from_en(0, true));
        change_return = 0;
    }

    __write_number($('input#total_paying_input'), total_paying);
    $('span.total_paying').text(__currency_trans_from_en(total_paying, true));

    __write_number($('input#in_balance_due'), bal_due);
    $('span.balance_due').text(__currency_trans_from_en(bal_due, true));

    __highlight(bal_due * -1, $('span.balance_due'));
    __highlight(change_return * -1, $('span.change_return_span'));
}

function isValidPosForm() {
    flag = true;
    $('span.error').remove();

    if ($('select#customer_id').val() == null) {
        flag = false;
        error = '<span class="error">' + LANG.required + '</span>';
        $(error).insertAfter($('select#customer_id').parent('div'));
    }

    if ($('tr.product_row').length == 0) {
        flag = false;
        error = '<span class="error">' + LANG.no_products + '</span>';
        $(error).insertAfter($('input#search_product').parent('div'));
    }

    // Validate payment entries
    var payment_validation_failed = false;
    $('#payment_rows_div .payment_row').each(function() {
        var payment_row = $(this);
        var amount_input = payment_row.find('.payment-amount');
        var method_select = payment_row.find('.payment_types_dropdown');
        
        // Check if payment amount is valid
        var amount = __read_number(amount_input);
        if (amount_input.val() && (isNaN(amount) || amount <= 0)) {
            payment_validation_failed = true;
            var error = '<span class="error">Invalid payment amount</span>';
            payment_row.find('.payment-amount').after(error);
        }
        
        // Check if payment method is selected when amount is provided
        if (amount_input.val() && !method_select.val()) {
            payment_validation_failed = true;
            var error = '<span class="error">Payment method required</span>';
            payment_row.find('.payment_types_dropdown').after(error);
        }
    });
    
    if (payment_validation_failed) {
        flag = false;
    }

    return flag;
}

function reset_pos_form(){

	//If on edit page then redirect to Add POS page
	if($('form#edit_pos_sell_form').length > 0){
		setTimeout(function() {
			window.location = $("input#pos_redirect_url").val();
		}, 4000);
		return true;
	}
	if(pos_form_obj[0]){
        pos_form_obj[0].reset();
	}
	if(sell_form[0]){
        sell_form[0].reset();
	}
	set_default_customer();
	set_location();
    
    $('#in_customer_wallet').val('0');
	$('tr.product_row').remove();
	$('span.total_quantity, span.price_total, span#total_discount, span#order_tax, span#total_payable, span#shipping_charges_amount').text(0);
	$('span.total_payable_span', 'span.total_paying', 'span.balance_due').text(0);

	// Clear all payment rows except the first one, and reset the first one
	$('#modal_payment').find('.remove_payment_row').each( function(){
		$(this).closest('.payment_row').remove();
	});
	
	// Reset payment row index to 1 for next addition
	$('#payment_row_index').val(1);

	//Reset discount
	__write_number($('input#discount_amount'), $('input#discount_amount').data('default'));
	$('input#discount_type').val($('input#discount_type').data('default'));

	//Reset tax rate
	$('input#tax_rate_id').val($('input#tax_rate_id').data('default'));
	__write_number($('input#tax_calculation_amount'), $('input#tax_calculation_amount').data('default'));

	$('select.payment_types_dropdown').val('').trigger('change');
	$('#price_group').trigger('change');

	//Reset shipping
	__write_number($('input#shipping_charges'), $('input#shipping_charges').data('default'));
	$('input#shipping_details').val($('input#shipping_details').data('default'));

	if($('input#is_recurring').length > 0){
		$('input#is_recurring').iCheck('update');
	};
    getInvoice();
    $(document).trigger('sell_form_reset');
}

function set_default_customer() {
    var default_customer_id = $('#default_customer_id').val();
    var default_customer_name = $('#default_customer_name').val();
    var exists = $("select#customer_id option[value='" + default_customer_id + "']").length;
    if (exists == 0) {
        $('select#customer_id').append(
            $('<option>', { value: default_customer_id, text: default_customer_name })
        );
    }

    $('select#customer_id')
        .val(default_customer_id)
        .trigger('change');

    customer_set = true;
}

//Set the location and initialize printer
function set_location() {
    if ($('select#select_location_id').length == 1) {
        $('input#location_id').val($('select#select_location_id').val());
        $('input#location_id').data(
            'receipt_printer_type',
            $('select#select_location_id')
                .find(':selected')
                .data('receipt_printer_type')
        );
    }

    if ($('input#location_id').val()) {
        $('input#search_product')
            .prop('disabled', false)
            .focus();
    } else {
        $('input#search_product').prop('disabled', true);
    }

    initialize_printer();
}

function initialize_printer() {
    if ($('input#location_id').data('receipt_printer_type') == 'printer') {
        initializeSocket();
    }
}

$('body').on('click', 'label', function(e) {
    var field_id = $(this).attr('for');
    if (field_id) {
        if ($('#' + field_id).hasClass('select2')) {
            $('#' + field_id).select2('open');
            return false;
        }
    }
});

$('body').on('focus', 'select', function(e) {
    var field_id = $(this).attr('id');
    if (field_id) {
        if ($('#' + field_id).hasClass('select2')) {
            $('#' + field_id).select2('open');
            return false;
        }
    }
});

function round_row_to_iraqi_dinnar(row) {
    if (iraqi_selling_price_adjustment) {
        var element = row.find('input.pos_unit_price_inc_tax');
        var unit_price = round_to_iraqi_dinnar(__read_number(element));
        __write_number(element, unit_price);
        element.change();
    }
}

function pos_print(receipt) {
    console.log("dd");
    console.log(receipt);
    //If printer type then connect with websocket
    if (receipt.print_type == 'printer') {
        var content = receipt;
        content.type = 'print-receipt';

        //Check if ready or not, then print.
        if (socket != null && socket.readyState == 1) {
            socket.send(JSON.stringify(content));
        } else {
            initializeSocket();
            setTimeout(function() {
                socket.send(JSON.stringify(content));
            }, 700);
        }

    } else if (receipt.html_content != '') {
        //If printer type browser then print content
        $('#receipt_section').html(receipt.html_content);
        __currency_convert_recursively($('#receipt_section'));
        __print_receipt('receipt_section');
    }
}

function calculate_discounted_unit_price(row) {
    var this_unit_price = __read_number(row.find('input.pos_unit_price'));
    var row_discounted_unit_price = this_unit_price;
    
    // Check both possible class names for discount type and amount
    var row_discount_type = row.find('select.row_discount_type_table').val() || row.find('select.row_discount_type').val();
    var row_discount_amount = __read_number(row.find('input.row_discount_amount_table')) || __read_number(row.find('input.row_discount_amount'));
    
    if (row_discount_amount && row_discount_amount > 0) {
        var qty = __read_number(row.find('input.pos_quantity')) || 1;
        if (row_discount_type == 'fixed') {
            // For fixed discount, subtract the amount per unit
            row_discounted_unit_price = this_unit_price - (row_discount_amount / qty);
        } else {
            // For percentage discount, subtract percentage
            row_discounted_unit_price = __substract_percent(this_unit_price, row_discount_amount);
        }
        // Ensure price doesn't go negative
        if (row_discounted_unit_price < 0) row_discounted_unit_price = 0;
    }

    return row_discounted_unit_price;
}

function get_unit_price_from_discounted_unit_price(row, discounted_unit_price) {
    var this_unit_price = discounted_unit_price;
    var row_discount_type = row.find('select.row_discount_type').val();
    var row_discount_amount = __read_number(row.find('input.row_discount_amount'));
    if (row_discount_amount) {
        if (row_discount_type == 'fixed') {
            this_unit_price = discounted_unit_price + row_discount_amount;
        } else {
            this_unit_price = __get_principle(discounted_unit_price, row_discount_amount, true);
        }
    }

    return this_unit_price;
}

//Update quantity if line subtotal changes
$('table#pos_table tbody').on('change', 'input.pos_line_total', function() {
    var subtotal = __read_number($(this));
    var tr = $(this).parents('tr');
    var quantity_element = tr.find('input.pos_quantity');
    var unit_price_inc_tax = __read_number(tr.find('input.pos_unit_price_inc_tax'));
    var quantity = subtotal / unit_price_inc_tax;
    __write_number(quantity_element, quantity);
    
    // Recalculate line discount with new subtotal
    var unit_price = __read_number(tr.find('input.pos_unit_price'));
    var tax_rate = tr.find('select.tax_id').find(':selected').data('rate');
    var original_line_total = quantity * (unit_price + __calculate_amount('percentage', tax_rate, unit_price));
    var line_discount = original_line_total - subtotal;
    __write_number(tr.find('input.pos_line_discount'), line_discount, false, 2);

    if (sell_form_validator) {
        sell_form_validator.element(quantity_element);
    }
    if (pos_form_validator) {
        pos_form_validator.element(quantity_element);
    }
    tr.find('span.pos_line_total_text').text(__currency_trans_from_en(subtotal, true));

    pos_total_row();
});

$('div#product_list_body').on('scroll', function() {
    if ($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight) {
        var page = parseInt($('#suggestion_page').val());
        page += 1;
        $('#suggestion_page').val(page);
        var location_id = $('input#location_id').val();
        var category_id = $('select#product_category').val();
        var brand_id = $('select#product_brand').val();

        get_product_suggestion_list(category_id, brand_id, location_id);
    }
});

$(document).on('ifChecked', '#is_recurring', function() {
    $('#recurringInvoiceModal').modal('show');
});

$(document).on('shown.bs.modal', '#recurringInvoiceModal', function() {
    $('input#recur_interval').focus();
});

$(document).on('click', '#select_all_service_staff', function() {
    var val = $('#res_waiter_id').val();
    $('#pos_table tbody')
        .find('select.order_line_service_staff')
        .each(function() {
            $(this)
                .val(val)
                .change();
        });
});

$(document).on('click', '.print-invoice-link', function(e) {
    e.preventDefault();
    $.ajax({
        url: $(this).attr('href') + "?check_location=true",
        dataType: 'json',
        success: function(result) {
            if (result.success == 1) {
                //Check if enabled or not
                if (result.receipt.is_enabled) {
                    pos_print(result.receipt);
                }
            } else {
                toastr.error(result.msg);
            }

        },
    });
});

function getCustomerRewardPoints() {
    if ($('#reward_point_enabled').length <= 0) {
        return false;
    }
    var is_edit = $('form#edit_sell_form').length || 
    $('form#edit_pos_sell_form').length ? true : false;
    if (is_edit && !customer_set) {
        return false;
    }

    var customer_id = $('#customer_id').val();

    $.ajax({
        method: 'POST',
        url: '/sells/pos/get-reward-details',
        data: { 
            customer_id: customer_id
        },
        dataType: 'json',
        success: function(result) {
            $('#available_rp').text(result.points);
            $('#rp_redeemed_modal').data('max_points', result.points);
            updateRedeemedAmount();
            $('#rp_redeemed_amount').change()
        },
    });
}

function updateRedeemedAmount(argument) {
    var points = $('#rp_redeemed_modal').val().trim();
    points = points == '' ? 0 : parseInt(points);
    var amount_per_unit_point = parseFloat($('#rp_redeemed_modal').data('amount_per_unit_point'));
    var redeemed_amount = points * amount_per_unit_point;
    $('#rp_redeemed_amount_text').text(__currency_trans_from_en(redeemed_amount, true));
    $('#rp_redeemed').val(points);
    $('#rp_redeemed_amount').val(redeemed_amount);
}

$(document).on('change', 'select#customer_id', function(){
    var default_customer_id = $('#default_customer_id').val();
    if ($(this).val() == default_customer_id) {
        //Disable reward points for walkin customers
        if ($('#rp_redeemed_modal').length) {
            $('#rp_redeemed_modal').val('');
            $('#rp_redeemed_modal').change();
            $('#rp_redeemed_modal').attr('disabled', true);
            $('#available_rp').text('');
            updateRedeemedAmount();
            pos_total_row();
        }
    } else {
        if ($('#rp_redeemed_modal').length) {
            $('#rp_redeemed_modal').removeAttr('disabled');
        }
        getCustomerRewardPoints();
    }
});

$(document).on('change', '#rp_redeemed_modal', function(){
    var points = $(this).val().trim();
    points = points == '' ? 0 : parseInt(points);
    var amount_per_unit_point = parseFloat($(this).data('amount_per_unit_point'));
    var redeemed_amount = points * amount_per_unit_point;
    $('#rp_redeemed_amount_text').text(__currency_trans_from_en(redeemed_amount, true));
    var reward_validation = isValidatRewardPoint();
    if (!reward_validation['is_valid']) {
        toastr.error(reward_validation['msg']);
        $('#rp_redeemed_modal').select();
    }
});

$(document).on('change', '.direct_sell_rp_input', function(){
    updateRedeemedAmount();
    pos_total_row();
});

function isValidatRewardPoint() {
    var element = $('#rp_redeemed_modal');
    var points = element.val().trim();
    points = points == '' ? 0 : parseInt(points);

    var max_points = parseInt(element.data('max_points'));
    var is_valid = true;
    var msg = '';

    if (points == 0) {
        return {
            is_valid: is_valid,
            msg: msg
        }
    }

    var rp_name = $('input#rp_name').val();
    if (points > max_points) {
        is_valid = false;
        msg = __translate('max_rp_reached_error', {max_points: max_points, rp_name: rp_name});
    }

    var min_order_total_required = parseFloat(element.data('min_order_total'));

    var order_total = __read_number($('#final_total_input'));

    if (order_total < min_order_total_required) {
        is_valid = false;
        msg = __translate('min_order_total_error', {min_order: __currency_trans_from_en(min_order_total_required, true), rp_name: rp_name});
    }

    var output = {
        is_valid: is_valid,
        msg: msg,
    }

    return output;
}

function adjustComboQty(tr){
    if(tr.find('input.product_type').val() == 'combo'){
        var qty = __read_number(tr.find('input.pos_quantity'));
        var multiplier = __getUnitMultiplier(tr);

        tr.find('input.combo_product_qty').each(function(){
            $(this).val($(this).data('unit_quantity') * qty * multiplier);
        });
    }
}

$(document).on('change', '#types_of_service_id', function(){
    var types_of_service_id = $(this).val();
    var location_id = $('#location_id').val();

    if(types_of_service_id) {
        $.ajax({
            method: 'POST',
            url: '/sells/pos/get-types-of-service-details',
            data: { 
                types_of_service_id: types_of_service_id,
                location_id: location_id
            },
            dataType: 'json',
            success: function(result) {
                //reset form if price group is changed
                var prev_price_group = $('#types_of_service_price_group').val();
                console.log(prev_price_group);
                console.log(result.price_group_id);
                console.log(prev_price_group != result.price_group_id);
                if (prev_price_group != result.price_group_id) {
                    if ($('form#edit_pos_sell_form').length > 0) {
                        $('table#pos_table tbody').html('');
                        pos_total_row();
                    } else {
                        reset_pos_form();
                    }
                }

                if(result.price_group_id) {
                    $('#types_of_service_price_group').val(result.price_group_id);
                    $('#price_group_text').removeClass('hide');
                    $('#price_group_text span').text(result.price_group_name);
                } else {
                    $('#types_of_service_price_group').val('');
                    $('#price_group_text').addClass('hide');
                    $('#price_group_text span').text('');
                }
                $('#types_of_service_id').val(types_of_service_id);
                $('.types_of_service_modal').html(result.modal_html);
                $('.types_of_service_modal').modal('show');
            },
        });
    } else {
        $('.types_of_service_modal').html('');
        $('#types_of_service_price_group').val('');
        $('#price_group_text').addClass('hide');
        $('#price_group_text span').text('');

        if ($('form#edit_pos_sell_form').length > 0) {
            $('table#pos_table tbody').html('');
            pos_total_row();
        } else {
            reset_pos_form();
        }
    }
});

$(document).on('change', 'input#packing_charge', function() {
    pos_total_row();
});

$(document).on('click', '.service_modal_btn', function(e) {
    if ($('#types_of_service_id').val()) {
        $('.types_of_service_modal').modal('show');
    }
});

$(document).on('change', '.payment_types_dropdown', function(e) {
    try {
        var default_accounts = $('select#select_location_id').length ? 
                    $('select#select_location_id')
                    .find(':selected')
                    .data('default_payment_accounts') : $('#location_id').data('default_accounts');
        var payment_type = $(this).val();
        var payment_row = $(this).closest('.payment_row');
        
        // Validate inputs
        if (!payment_type || !payment_row.length) {
            console.warn('Invalid payment type or payment row not found');
            return;
        }
        
        // Clear any previous validation errors for this payment row
        $(this).siblings('.error').remove();
        
        // Get default account with proper null checks
        var default_account = '';
        if (default_accounts && default_accounts[payment_type] && default_accounts[payment_type]['account']) {
            default_account = default_accounts[payment_type]['account'];
        } else if (payment_type === 'cash') {
            // For cash payments, try to get the cash account ID from a hidden field or data attribute
            var cash_account_id = $('#cash_account_id').val() || $('body').data('cash-account-id');
            if (cash_account_id) {
                default_account = cash_account_id;
            }
        }
        
        var row_index = payment_row.find('.payment_row_index').val();
        if (!row_index) {
            console.warn('Payment row index not found');
            return;
        }

        // Only update the account dropdown for this specific payment row
        var account_dropdown = payment_row.find('select#account_' + row_index);
        if (account_dropdown.length) {
            if (default_account) {
                account_dropdown.val(default_account);
                account_dropdown.change();
            } else if (payment_type === 'cash') {
                // For cash payments without default account, show warning
                console.warn('Cash payment selected but no cash account configured');
                toastr.warning('Cash account not configured. Please contact administrator.');
            }
        }

        // Only update required attribute for this specific payment row
        if (payment_type == 'bank_transfer' || payment_type == 'direct_bank_deposit') {
            payment_row.find('.account_id').attr("required", true);
        } else {
            payment_row.find('.account_id').attr("required", false);
        }
        
        // Show/hide payment type specific fields for this row only
        payment_row.find('.bank_transfer_fields').addClass('hide');
        payment_row.find('.post_dated_cheque').addClass('hide');
        
        if (payment_type == 'bank_transfer' || payment_type == 'cheque') {
            payment_row.find('.bank_transfer_fields').removeClass('hide');
        }
        
        if (payment_type == 'cheque') {
            payment_row.find('.post_dated_cheque').removeClass('hide');
        }
        
    } catch (error) {
        console.error('Error in payment method selection:', error);
        toastr.error('Failed to update payment method. Please try again.');
    }
});