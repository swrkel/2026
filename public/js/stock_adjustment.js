$(document).ready(function() {
    var $stockAdjustmentSearch = $('#search_product_for_srock_adjustment');
    var lastNoResultTerm = '';

    if ($stockAdjustmentSearch.length > 0) {
        $stockAdjustmentSearch.autocomplete({
            delay: 200,
            minLength: 1,
            source: function(request, response) {
                var term = $.trim(request.term || '');
                var locationId = $('#location_id').val();

                if (!locationId || term.length < 1) {
                    response([]);
                    return;
                }

                $.ajax({
                    url: '/products/list-sa',
                    method: 'GET',
                    dataType: 'json',
                    data: {
                        location_id: locationId,
                        term: term,
                        search_fields: ['name', 'sku', 'sub_sku'],
                        module: $('#module').val()
                    },
                    success: function(data) {
                        response($.isArray(data) ? data : []);
                    },
                    error: function() {
                        response([]);
                    }
                });
            },
            response: function(event, ui) {
                var term = $.trim($(this).val() || '');
                if (ui.content.length === 0 && term.length >= 2 && lastNoResultTerm !== term) {
                    lastNoResultTerm = term;
                    swal(LANG.no_products_found);
                } else if (ui.content.length > 0) {
                    lastNoResultTerm = '';
                }
            },
            focus: function(event, ui) {
                event.preventDefault();
                $(this).val(ui.item.label || ui.item.value || ui.item.name || '');
                return false;
            },
            select: function(event, ui) {
                event.preventDefault();

                var variationId = parseInt(ui.item.variation_id, 10);
                if (!variationId) {
                    toastr.error('The selected product is invalid. Please search again.');
                    return false;
                }

                $(this).val('');
                stock_adjustment_product_row(variationId);
                return false;
            }
        });

        var autocompleteInstance = $stockAdjustmentSearch.autocomplete('instance');
        if (autocompleteInstance) {
            autocompleteInstance._renderItem = function(ul, item) {
                var productName = $.trim(item.product_name || item.name || '');
                var variationName = $.trim(item.variation || '');
                var sku = $.trim(item.sub_sku || item.sku || '');
                var displayText = $.trim(item.label || item.value || productName);

                if (!displayText) {
                    displayText = productName;
                    if (item.type === 'variable' && variationName && variationName.toLowerCase() !== 'dummy') {
                        displayText += ' - ' + variationName;
                    }
                    if (sku) {
                        displayText += ' (' + sku + ')';
                    }
                }

                if (parseFloat(item.qty_available || 0) <= 0) {
                    displayText += ' (Out of stock)';
                }

                return $('<li>')
                    .append($('<div>').text(displayText))
                    .appendTo(ul);
            };
        }
    }

    $('select#location_id').change(function() {
        if ($(this).val()) {
            $('#search_product_for_srock_adjustment').removeAttr('disabled');
        } else {
            $('#search_product_for_srock_adjustment').attr('disabled', 'disabled');
        }
        $('table#stock_adjustment_product_table tbody').html('');
        $('#product_row_index').val(0);
        update_table_total();
    });

    $(document).on('change', 'input.product_quantity', function() {
        update_table_row($(this).closest('tr'));
    });
    $(document).on('change', 'input.product_unit_price', function() {
        update_table_row($(this).closest('tr'));
    });
    $(document).on('change', 'select.row_stock_adjustment_type', function() {
        update_table_row($(this).closest('tr'));
    });

    $(document).on('click', '.remove_product_row', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $(this)
                    .closest('tr')
                    .remove();
                update_table_total();
            }
        });
    });

    // Date picker
    // S277: initialise from the actual ISO value saved in the input. This prevents the UI from
    // interpreting DD/MM as MM/DD and showing/saving the next month's date.
    if ($('#transaction_date').length) {
        var existingTransactionDate = ($('#transaction_date').val() || '').trim();
        $('#transaction_date').datetimepicker({
            format: moment_date_format + ' ' + moment_time_format,
            ignoreReadonly: true,
        });
        if (existingTransactionDate) {
            var parsedTransactionDate = moment(existingTransactionDate, ['YYYY-MM-DD HH:mm:ss', 'YYYY-MM-DD HH:mm'], true);
            if (parsedTransactionDate.isValid()) {
                $('#transaction_date').data('DateTimePicker').date(parsedTransactionDate);
            }
        }
    }

    $('form#stock_adjustment_form').validate();

    stock_adjustment_table = $('#stock_adjustment_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/stock-adjustments',
        columnDefs: [
            {
                targets: 0,
                orderable: false,
                searchable: false,
            },
        ],
        aaSorting: [[1, 'desc']],
        columns: [
            { data: 'action', name: 'action' },
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'ref_no', name: 'ref_no' },
            { data: 'location_name', name: 'BL.name' },
            { data: 'adjustment_type', name: 'adjustment_type' },
            { data: 'stock_adjustment_type', name: 'stock_adjustment_type' },
            { data: 'final_total', name: 'final_total' },
            { data: 'total_amount_recovered', name: 'total_amount_recovered' },
            { data: 'additional_notes', name: 'additional_notes' },
            { data: 'added_by', name: 'u.first_name' },
        ],
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#stock_adjustment_table'));
        },
    });
    var detailRows = [];

    $(document).on('click', 'button.delete_stock_adjustment', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).data('href');
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            stock_adjustment_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
});

function stock_adjustment_product_row(variation_id) {
    var variationId = parseInt(variation_id, 10);
    var rowIndex = parseInt($('#product_row_index').val(), 10) || 0;
    var locationId = parseInt($('select#location_id').val(), 10);
    var $search = $('#search_product_for_srock_adjustment');

    if (!variationId || !locationId) {
        toastr.error('Please select a valid location and product.');
        return;
    }

    var duplicate = $('table#stock_adjustment_product_table tbody')
        .find('input[name$="[variation_id]"][value="' + variationId + '"]')
        .length > 0;

    if (duplicate) {
        toastr.warning('This product is already added.');
        $search.val('').focus();
        return;
    }

    if ($search.data('loading-product-row')) {
        return;
    }

    $search.data('loading-product-row', true).prop('disabled', true);

    $.ajax({
        method: 'POST',
        url: '/stock-adjustments/get_product_row',
        data: {
            row_index: rowIndex,
            variation_id: variationId,
            location_id: locationId
        },
        dataType: 'html',
        success: function(result) {
            var html = $.trim(result || '');
            if (!html || html.indexOf('product_row') === -1) {
                toastr.error('The selected product could not be loaded.');
                return;
            }

            $('table#stock_adjustment_product_table tbody').append(html);
            $('#product_row_index').val(rowIndex + 1);
            update_table_total();
        },
        error: function(xhr) {
            var message = 'The selected product could not be loaded.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            toastr.error(message);
        },
        complete: function() {
            $search
                .data('loading-product-row', false)
                .prop('disabled', false)
                .val('')
                .focus();
        }
    });
}

function update_table_total() {
    var increase_total = 0;
    var decrease_total = 0;
    $('table#stock_adjustment_product_table tbody tr').each(function() {
        var this_total = parseFloat(__read_number($(this).find('input.product_line_total')));
        var row_type = $(this).find('select.row_stock_adjustment_type').val();
        if (this_total) {
            if (row_type == 'decrease') {
                decrease_total += this_total;
            } else {
                increase_total += this_total;
            }
        }
    });

    // final_total = absolute sum for display; per-product account entries handle the split
    var abs_total = increase_total + decrease_total;
    $('input#total_amount').val(abs_total);

    // Show increase and decrease totals separately in the summary
    if (increase_total > 0 && decrease_total > 0) {
        $('span#total_adjustment').html(
            '<span class="text-success">Increase: ' + __number_f(increase_total) + '</span>' +
            ' | <span class="text-danger">Decrease: ' + __number_f(decrease_total) + '</span>' +
            ' | Total: ' + __number_f(abs_total)
        );
    } else {
        $('span#total_adjustment').text(__number_f(abs_total));
    }
}

function update_table_row(tr) {
    var quantity = parseFloat(__read_number(tr.find('input.product_quantity')));
    var unit_price = parseFloat(__read_number(tr.find('input.product_unit_price')));
    var row_total = 0;
    if (quantity && unit_price) {
        row_total = quantity * unit_price;
    }
    
    var row_adjustment_type = tr.find('select.row_stock_adjustment_type').val();
    
    // For decrease, make the value negative for internal calculation
    if(row_adjustment_type == 'decrease') {
        row_total = row_total * -1;
    }
    
    tr.find('input.product_line_total').val(__number_f(Math.abs(row_total)));

    if(row_total != 0){
        var $input = tr.find('input.product_line_total');
        var $container = $input.closest('.negative_display');
        // Reset the container class
        $container.removeClass('negative-display');
        if(row_adjustment_type == 'decrease') {
            // show negative sign
            $container.addClass('negative-display');
        }
    }
    update_table_total();
}

$(document).on('shown.bs.modal', '.view_modal', function() {
    __currency_convert_recursively($('.view_modal'));
});
