@extends('layouts.app')
@section('title', __('repair::lang.add_jobsheet_parts'))

@section('content')
    @include('autorepairservices::layouts.nav')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('repair::lang.add_jobsheet_parts')</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        @component('components.widget', ['class' => 'box-solid'])
            <table class="table">
                <tr>
                    <th>@lang('repair::lang.job_sheet_no'):</th>
                    <td>{{ $job_sheet->job_sheet_no }}</td>
                    <th>@lang('receipt.date'):</th>
                    <td>{{ @format_datetime($job_sheet->created_at) }}</td>
                </tr>
                <tr>
                    <th>
                        @lang('role.customer'):
                    </th>
                    <td>{{ $job_sheet->customer->name }}</td>
                    <th>@lang('business.location'):</th>
                    <td>
                        {{ optional($job_sheet->businessLocation)->name }}
                    </td>
                </tr>
            </table>
        @endcomponent
        {!! Form::open([
            'url' => action('\Modules\AutoRepairServices\Http\Controllers\JobSheetController@saveParts', $job_sheet->id),
            'method' => 'post',
            'id' => 'add_part_form',
        ]) !!}
        @component('components.widget', ['class' => 'box-solid', 'title' => __('repair::lang.add_parts')])
            <div class="row">
                <div class="col-sm-8 col-sm-offset-2">
                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-search"></i>
                            </span>
                            {!! Form::text('search_product', null, [
                                'class' => 'form-control',
                                'id' => 'search_job_sheet_parts',
                                'placeholder' => __('repair::lang.search_parts'),
                            ]) !!}
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-10 col-sm-offset-1">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-condensed" id="job_sheet_parts_table">
                            <thead>
                                <tr>
                                    <th class="col-sm-3 text-center">
                                        @lang('repair::lang.part')
                                    </th>
                                    <th class="col-sm-1 text-center">
                                        @lang('sale.qty')
                                    </th>
                                    <th class="col-sm-2 text-center">
                                        Unit Price
                                    </th>
                                    <th class="col-sm-2 text-center">
                                        Purchase Price
                                    </th>
                                    <th class="col-sm-2 text-center">
                                        @lang('sale.subtotal')
                                    </th>
                                    <th class="col-sm-1 text-center">
                                        Tax
                                    </th>
                                    <th class="col-sm-1 text-center">
                                        Total
                                    </th>
                                    <th class="col-sm-1 text-center"><i class="fa fa-trash" aria-hidden="true"></i></th>
                                </tr>
                            </thead>
                            <tbody>

                                @if (!empty($parts))
                                    @foreach ($parts as $part)
                                        @include(
                                            'autorepairservices::job_sheet.partials.job_sheet_part_row',
                                            [
                                                'variation_name' => $part['variation_name'],
                                                'unit' => $part['unit'],
                                                'quantity' => $part['quantity'],
                                                'variation_id' => $part['variation_id'],
                                                'unit_price' => $part['unit_price'],
                                                'purchase_price' => $part['purchase_price'],
                                                'tax_rate' => $part['tax_rate'] ?? 0,
                                            ]
                                        )
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Summary Section -->
            <div class="row">
                <div class="col-sm-8 col-sm-offset-3">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <td><strong>Subtotal:</strong></td>
                                    <td class="text-right">
                                        <span id="total_subtotal">0.00</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Total Tax:</strong></td>
                                    <td class="text-right">
                                        <span id="total_tax">0.00</span>
                                    </td>
                                </tr>
                                <tr class="info">
                                    <td><strong>Grand Total:</strong></td>
                                    <td class="text-right">
                                        <span id="grand_total">0.00</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endcomponent
        @if (!empty($status_update_data) && $status_update_data['job_sheet_id'] == $job_sheet->id)
            @component('components.widget', ['class' => 'box-solid'])
                @include('autorepairservices::job_sheet.partials.edit_status_form', [
                    'status_update_data' => $status_update_data,
                ])
            @endcomponent
        @endif
        <div class="row">
            <div class="col-sm-12">
                <button type="button" id="submit_add_part_form"
                    class="btn btn-primary pull-right">@lang('messages.save')</button>
            </div>
        </div>
        {!! Form::close() !!}
    </section>
@stop
@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            // Initialize summary totals
            updateSummaryTotals();

            // Calculate initial totals for existing parts
            $('.product_row').each(function() {
                calculateSubtotal($(this));
            });

            $('#search_job_sheet_parts')
                .autocomplete({
                    source: function(request, response) {
                        $.getJSON(
                            '/products/list', {
                                term: request.term
                            },
                            response
                        );
                    },
                    minLength: 2,
                    response: function(event, ui) {
                        if (ui.content.length == 1) {
                            ui.item = ui.content[0];
                            $(this)
                                .data('ui-autocomplete')
                                ._trigger('select', 'autocompleteselect', ui);
                            $(this).autocomplete('close');
                        } else if (ui.content.length == 0) {
                            swal(LANG.no_products_found);
                        }
                    },
                    select: function(event, ui) {
                        job_sheet_parts_row(ui.item.variation_id);
                    },
                })
                .autocomplete('instance')._renderItem = function(ul, item) {
                    var string = '<div>' + item.name;
                    if (item.type == 'variable') {
                        string += '-' + item.variation;
                    }
                    string += ' (' + item.sub_sku + ') </div>';
                    return $('<li>')
                        .append(string)
                        .appendTo(ul);
                };

            //initialize editor
            tinymce.init({
                selector: 'textarea#email_body',
            });

            $('#send_sms').change(function() {
                if ($(this).is(":checked")) {
                    $('div.sms_body').fadeIn();
                } else {
                    $('div.sms_body').fadeOut();
                }
            });

            $('#send_email').change(function() {
                if ($(this).is(":checked")) {
                    $('div.email_template').fadeIn();
                } else {
                    $('div.email_template').fadeOut();
                }
            });

            if ($('#status_id_modal').length) {
                ;
                $("#sms_body").val($("#status_id_modal :selected").data('sms_template'));
                $("#email_subject").val($("#status_id_modal :selected").data('email_subject'));
                tinymce.activeEditor.setContent($("#status_id_modal :selected").data('email_body'));
            }

            $('#status_id_modal').on('change', function() {
                var sms_template = $(this).find(':selected').data('sms_template');
                var email_subject = $(this).find(':selected').data('email_subject');
                var email_body = $(this).find(':selected').data('email_body');

                $("#sms_body").val(sms_template);
                $("#email_subject").val(email_subject);
                tinymce.activeEditor.setContent(email_body);

                if ($('#status_modal .mark-as-complete-btn').length) {
                    if ($(this).find(':selected').data('is_completed_status') == 1) {
                        $('#status_modal').find('.mark-as-complete-btn').removeClass('hide');
                        $('#status_modal').find('.mark-as-incomplete-btn').addClass('hide');
                    } else {
                        $('#status_modal').find('.mark-as-complete-btn').addClass('hide');
                        $('#status_modal').find('.mark-as-incomplete-btn').removeClass('hide');
                    }
                }
            });
        });

        function job_sheet_parts_row(variation_id) {
            var row_index = parseInt($('#product_row_index').val());
            var location_id = $('select#location_id').val();
            $.ajax({
                method: 'POST',
                url: "{{ action('\Modules\AutoRepairServices\Http\Controllers\JobSheetController@jobsheetPartRow') }}",
                data: {
                    variation_id: variation_id
                },
                dataType: 'html',
                success: function(result) {
                    $('table#job_sheet_parts_table tbody').append(result);

                    $('input#search_job_sheet_parts').val('')
                    $('input#search_job_sheet_parts')
                        .focus()
                        .select();

                    // Calculate totals for the new row
                    let newRow = $('table#job_sheet_parts_table tbody tr:last');
                    calculateSubtotal(newRow);
                },
            });
        }

        $(document).on('click', '.remove_product_row', function() {
            $(this).closest('tr').remove();
            // Recalculate totals after removing a row
            updateSummaryTotals();
        })

        $(document).on('click', '#submit_add_part_form', function(e) {
            $('form#add_part_form').submit();
        })

        // Handle plus button
        $(document).on('click', '.qty-increase', function() {
            let row = $(this).closest('.product_row');
            let input = row.find('.quantity_input');
            let currentVal = parseFloat(input.val()) || 0;
            input.val((currentVal + 1).toFixed(2));
            calculateSubtotal(row);
        });

        // Handle minus button (min quantity is 1)
        $(document).on('click', '.qty-decrease', function() {
            let row = $(this).closest('.product_row');
            let input = row.find('.quantity_input');
            let currentVal = parseFloat(input.val()) || 0;
            if (currentVal > 1) {
                input.val((currentVal - 1).toFixed(2));
                calculateSubtotal(row);
            }
        });

        // Handle manual input change (prevent values below 1)
        $(document).on('input', '.quantity_input', function() {
            let row = $(this).closest('.product_row');
            let input = $(this);
            let value = parseFloat(input.val()) || 1;
            if (value < 1) {
                input.val('1.00');
            }
            calculateSubtotal(row);
        });

        // Function to calculate subtotal for a row
        function calculateSubtotal(row) {
            let quantity = parseFloat(row.find('.quantity_input').val()) || 0;
            let price = parseFloat(row.find('.price_input').val()) || 0;

            // Ensure values are numeric
            if (isNaN(quantity) || isNaN(price)) {
                console.error('Invalid numeric values:', {
                    quantity,
                    price
                });
                quantity = 0;
                price = 0;
            }

            let subtotal = quantity * price;
            let taxRate = parseFloat(row.find('.tax_rate_input').val()) || 0;

            if (isNaN(taxRate)) {
                taxRate = 0;
            }

            let tax = subtotal * (taxRate / 100); // Convert percentage to decimal
            let total = subtotal + tax;

            row.find('.subtotal_input').val(subtotal.toFixed(2));
            row.find('.tax_input').val(tax.toFixed(2));
            row.find('.total_input').val(total.toFixed(2));

            // Update summary totals
            updateSummaryTotals();
        }

        // Function to update summary totals
        function updateSummaryTotals() {
            let totalSubtotal = 0;
            let totalTax = 0;
            let grandTotal = 0;

            $('.product_row').each(function() {
                let subtotal = parseFloat($(this).find('.subtotal_input').val()) || 0;
                let tax = parseFloat($(this).find('.tax_input').val()) || 0;
                let total = parseFloat($(this).find('.total_input').val()) || 0;

                // Ensure values are numeric
                if (!isNaN(subtotal)) totalSubtotal += subtotal;
                if (!isNaN(tax)) totalTax += tax;
                if (!isNaN(total)) grandTotal += total;
            });

            $('#total_subtotal').text(totalSubtotal.toFixed(2));
            $('#total_tax').text(totalTax.toFixed(2));
            $('#grand_total').text(grandTotal.toFixed(2));
        }
    </script>
@endsection
