@extends('layouts.app')

@section('title', __('realtimeentries::lang.real_time_payments'))

@section('content')
    <section class="content">
        <div class="row no-print">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('realtimeentries::lang.real_time_payments')])
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('realtime_entries_pump_operator', __('realtimeentries::lang.pump_operator') . ':') !!}
                            {!! Form::select('pump_operator', $pump_operators, null, [
                                'class' => 'form-control select2 realtime-entries-pump-operator-select',
                                'placeholder' => __('realtimeentries::lang.select_pump_operator'),
                                'id' => 'realtime_entries_pump_operator',
                                'style' => 'width:100%',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('shift_number', __('realtimeentries::lang.shift_number') . ':') !!}
                            <select class="form-control select2" style="width:100%" id="shift_number">
                                <option value="">@lang('realtimeentries::lang.select_shift_number')</option>
                            </select>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>

        <div class="container no-print realtime-entries-payment-ui">
            @include('realtimeentries::partials.payment_section', ['pop_up' => false])
            <input type="hidden" id="selected_shift_id" name="selected_shift_id" value="">
        </div>

        <div class="modal fade" id="direct_cr" role="dialog" aria-labelledby="gridSystemModalLabel">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">

                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">@lang('petro::lang.credit_sale')</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>

                    <!-- Modal Body -->
                    <div class="modal-body">
                        @include('petro::pump_operators.credit_sale')
                    </div>

                </div>
            </div>
        </div>

        <div class="modal fade" id="cheque_payments" role="dialog" aria-labelledby="gridSystemModalLabel">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">

                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">@lang('petro::lang.cheque') .</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>

                    <!-- Modal Body -->
                    <div class="modal-body">
                        @include('petro::pump_operators.cheque_payment')
                    </div>

                </div>
            </div>
        </div>

        {{-- Override cheque form submission for real-time-payments context --}}
        <script>
        $(document).off('submit', '#cheque_payment_form').on('submit', '#cheque_payment_form', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $btn  = $('#cheque_finalize_btn');
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

            var payload = {
                _token:           $('input[name="_token"]').first().val(),
                submit_cheques:   1,
                'customer[]':     $('#cheque_customer_id').val(),
                'amount[]':       $form.find('.amount').val(),
                'cheque_no[]':    $form.find('.cheque_number').val(),
                'cheque_date[]':  $form.find('.cheque_date').val(),
                'bank[]':         '',
                shift_number:     $('#shift_number').val() || '',
                pump_operator_id: $('#realtime_entries_pump_operator').val() || '',
                transaction_date: $('#transaction_date').val() || '',
            };

            $.ajax({
                method:   'POST',
                url:      '{{ route("realtime.real-time-payments") }}',
                data:     payload,
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        toastr.success(result.msg || '{{ __("lang_v1.success") }}');
                        $('#cheque_payments').modal('hide');
                        $form[0].reset();

                        // 🔹 collection form no update
                        var formNo = result.collection_form_no || '';
                        $(".collection_form_no").val(formNo);

                        // Clear amount only and refresh tables
                        $("#amount").val('');
                        refreshRealTimePaymentTables();

                        // Show confirmation to enter another payment
                        $("#reloadConfirmationModalLabel").html("Confirm Another Payment for Form No. " + formNo);
                        $("#reloadConfirmationModal").removeData('pending-action').data('payment-type', 'cheque').modal('show');
                    } else {
                        toastr.error(result.msg || '{{ __("messages.something_went_wrong") }}');
                    }
                },
                error: function() {
                    toastr.error('{{ __("messages.something_went_wrong") }}');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('{{ __("petro::lang.finalize") }}');
                }
            });
        });
        </script>

        <div class="modal fade" id="cash_payments" role="dialog" aria-labelledby="gridSystemModalLabel">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">

                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">@lang('petro::lang.cash')</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>

                    <!-- Modal Body -->
                    <div class="modal-body">
                        {!! Form::open([
                            'url' => '#',
                            'method' => 'post',
                            'id' => 'cash_denom_form',
                        ]) !!}
                        <div class="row">
                            <div class="col-md-6">
                                @foreach ($cash_denoms as $denom)
                                    <div class="row">
                                        <input type="hidden" value="{{ $denom }}" class="denom_value">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>{{ @num_format($denom) }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {!! Form::number('qty[]', 0, [
                                                    'class' => 'form-control cash_payment_input denom_qty',
                                                    'required',
                                                    'placeholder' => __('petro::lang.qty'),
                                                ]) !!}
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {!! Form::text('total_amount[]', 0, [
                                                    'class' => 'form-control denom_amt',
                                                    'required',
                                                    'readonly',
                                                    'placeholder' => __('petro::lang.total_amount'),
                                                ]) !!}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                <div class="row denoms_totals">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{ __('lang_v1.total') }}</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::text('grand_total', 0, [
                                                'class' => 'form-control denom_total',
                                                'required',
                                                'readonly',
                                                'placeholder' => __('petro::lang.total'),
                                                'style' => 'color:green;font-weight:bold;',
                                            ]) !!}
                                        </div>
                                    </div>

                                    <div class="col-md-2 pull-right">
                                        <button type="button" class="btn btn-success pull-right cash_denom_save"
                                            style="margin-top: 23px;">@lang('petro::lang.correct')</button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    {!! Form::close() !!}
                </div>

            </div>
        </div>

        <div class="modal fade" id="card_payment" role="dialog" aria-labelledby="gridSystemModalLabel">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">

                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">@lang('petro::lang.card')</h4>
                        <button type="button" class="btn btn-primary pull-right" data-dismiss="modal">&times;</button>
                    </div>

                    <!-- Modal Body -->
                    <div class="modal-body">
                        @include('petro::pump_operators.card_payment')
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    </div>

                </div>
            </div>
        </div>

        <div class="modal fade" id="other_sales" role="dialog" aria-labelledby="gridSystemModalLabel">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">

                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">@lang('petro::lang.enter_meters')</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>

                    <!-- Modal Body -->
                    <div class="modal-body">
                        @include('realtimeentries::enter_meters_modal', compact('pending_pumps'))
                    </div>

                </div>
            </div>
        </div>

        <!-- This will be printed -->
        <section class="invoice print_section" id="receipt_section">
        </section>
    </section>

@endsection

@section('javascript')
    <script src="{{ asset('modules/petro/js/po_payment.js') }}?v={{ time() }}"></script>


    <script>
        function realTimeCardPaymentForm($source) {
            var $form = $source && $source.length ? $source.closest('form') : $();
            if (!$form.length) {
                $form = $('#card_payment_form');
            }
            if (!$form.length) {
                $form = $('#card_payment form').first();
            }
            return $form;
        }

        $(document).ready(function() {
            $(".select2").select2();

            $('#shift_number').on('change', function() {
                var sid = $(this).val() ?? '';
                $('#selected_shift_id').val(sid);
                setShiftInModals(sid);
            });
            // ensure any already-selected value (page reload) is propagated
            setTimeout(function() {
                var initial = $('#shift_number').val() ?? '';
                $('#selected_shift_id').val(initial);
                setShiftInModals(initial);
            }, 100);


            function setShiftInModals(shiftId) {
                var modalSelectors = ['#card_payment', '#cheque_payments', '#other_sales', '#direct_cr',
                    '#cash_payments'
                ];
                modalSelectors.forEach(function(sel) {
                    var $modal = $(sel);
                    if ($modal.length === 0) return;
                    // prefer placing inside any form found in modal, otherwise modal-body
                    var $container = $modal.find('form').first();
                    if ($container.length === 0) $container = $modal.find('.modal-body').first();
                    if ($container.length === 0) return;
                    var $existing = $container.find('input[name="shift_id"]');
                    if ($existing.length > 0) {
                        $existing.val(shiftId);
                    } else {
                        // append hidden input so server-side gets it when the form submits
                        $container.append('<input type="hidden" name="shift_id" value="' + (shiftId || '') +
                            '">');
                    }
                });
            }

            $(document).on('click', '.add_other_sales', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var shiftId = $('#shift_number').val();
                var operatorId = $('#realtime_entries_pump_operator').val();

                if (!shiftId || !operatorId) {
                    alert('Please select both pump operator and shift');
                    return;
                }

                // Show modal first
                $("#other_sales").modal({
                    backdrop: 'static',
                    keyboard: false
                });

                // Show loading state in modal
                $('#other_sales .modal-body').html(`
                        <div class="text-center" style="padding: 50px;">
                            <i class="fa fa-spinner fa-spin fa-3x"></i>
                            <p>Loading pending pumps...</p>
                        </div>
                    `);

                // Fetch pending pumps for this shift
                fetchPendingPumps(operatorId, shiftId);
            });

            function fetchPendingPumps(operatorId, shiftId) {
                $.ajax({
                    url: '{{ route('realtime.index') }}',
                    method: 'GET',
                    data: {
                        get_pending_pumps: 1,
                        pump_operator_id: operatorId,
                        shift_id: shiftId
                    },
                    success: function(response) {
                        console.log('Pending pumps response:', response);

                        if (response.pending_pumps && response.pending_pumps.length > 0) {
                            // Update the modal with the fetched pumps
                            updateOtherSalesModal(response.pending_pumps, response.balance_to_deposit ||
                                0);
                        } else {
                            $('#other_sales .modal-body').html(`
                                    <div class="alert alert-warning text-center">
                                        <h4>No Pending Pumps Found</h4>
                                        <p>There are no pending pump readings for the selected shift.</p>
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                    </div>
                                `);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching pending pumps:', error);
                        console.error('XHR response:', xhr.responseText);

                        $('#other_sales .modal-body').html(`
                                <div class="alert alert-danger text-center">
                                    <h4>Error Loading Pumps</h4>
                                    <p>Unable to load pending pumps. Please try again.</p>
                                    <p><small>Error: ${error}</small></p>
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                </div>
                            `);
                    }
                });
            }

            function updateOtherSalesModal(pendingPumps, balanceToDeposit) {
                var formHtml = `
                        {!! Form::open([
                            'url' => action('\Modules\Petro\Http\Controllers\PumpOperatorPaymentController@saveMeterSale'),
                            'method' => 'post',
                            'id' => 'enter_meters_form',
                        ]) !!}
                        <input type="hidden" name="shift_id" value="${$('#shift_number').val()}">
                        <input type="hidden" name="pump_operator_id" value="${$('#realtime_entries_pump_operator').val()}">

                        <div class="row">
                            <div class="col-md-12">
                                <table class="table table-bordered table-striped" id="other_sale_table">
                                    <thead>
                                        <tr>
                                            <th>@lang('petro::lang.pump_no')</th>
                                            <th>@lang('petro::lang.received_meter')</th>
                                            <th>@lang('petro::lang.new_meter')</th>
                                            <th>@lang('petro::lang.sold_qty')</th>
                                            <th>@lang('petro::lang.unit_price')</th>
                                            <th>@lang('petro::lang.amount')</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;

                // Build table rows with the fetched pumps
                pendingPumps.forEach(function(pump) {
                    var startingMeter = parseFloat(pump.starting_meter || 0).toFixed(3);
                    var unitPrice = parseFloat(pump.sell_price_inc_tax || 0).toFixed(2);

                    formHtml += `
                                <tr style="border: 2px solid #b0ade6ff;">
                                    <td>${pump.pump_no}
                                        <input type="hidden" name="pump_no[]" class="form-control other_sale_pump_no" value="${pump.pump_no}">
                                        <input type="hidden" name="assignment_id[]" class="form-control other_sale_assigment_id" value="${pump.id}">
                                    </td>
                                    <td>${startingMeter}
                                        <input type="hidden" name="starting_meter[]" class="form-control other_sale_starting_meter" required value="${startingMeter}">
                                    </td>
                                    <td>
                                        <input type="number" step="0.001" name="new_meter[]" class="form-control other_sale_input other_sale_new_meter" 
                                            oninput="validateMeterInput(this, ${startingMeter})" 
                                            onchange="validateMeterInputOnChange(this, ${startingMeter})">
                                    </td>
                                    <td><span class="other_sale_span_sold_qty">0.00</span></td>
                                    <td><span class="other_sale_span_unit_price">${unitPrice}</span></td>
                                    <td>
                                        <span class="other_sale_span_amount">0.00</span>
                                        <input type="hidden" name="sold_qty[]" class="form-control other_sale_sold_qty">
                                        <input type="hidden" name="unit_price[]" class="form-control other_sale_unit_price" value="${unitPrice}">
                                        <input type="hidden" name="sale_amount[]" class="form-control other_sale_amount">
                                    </td>
                                </tr>`;
                });

                formHtml += `
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>@lang('petro::lang.total_amount')</td>
                                    <td>
                                        <span class="other_sale_grand_total_amount">0.00</span>
                                        <input type="hidden" name="grand_total" class="other_sale_grand_total_amount_input">
                                    </td>
                                    <td colspan="4"></td>
                                </tr>
                                
                                <tr>
                                    <td>@lang('petro::lang.today_deposited')</td>
                                    <td>
                                        <span class="other_sale_grand_today_deposited">${__number_f(balanceToDeposit || 0)}</span>
                                        <input type="hidden" name="today_deposited" class="other_sale_grand_today_deposited_input" value="${balanceToDeposit || 0}">
                                    </td>
                                    <td colspan="4"></td>
                                </tr>
                                
                                <tr>
                                    <td>@lang('petro::lang.balance_to_deposit')</td>
                                    <td>
                                        <span class="other_sale_grand_balance_to_deposit">${__number_f(balanceToDeposit || 0)}</span>
                                        <input type="hidden" name="balance_to_deposit" class="other_sale_grand_balance_to_deposit_input" value="${balanceToDeposit || 0}">
                                    </td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-2 pull-right">
                        <button type="submit" class="btn btn-danger pull-right other_sale_finalize"
                            style="margin-top: 23px;">@lang('petro::lang.finalize')</button>
                    </div>         
                </div>
                {!! Form::close() !!}`;

                // Update modal content
                $('#other_sales .modal-body').html(formHtml);

                // Re-enable inputs and focus on first input
                $('#other_sales').find('input, select, textarea').prop('disabled', false);
                $('#other_sales').find('input[type="number"]:first').focus();
                $(".other_sale_finalize").prop('disabled', false);

                // Re-initialize any necessary event handlers
                initializeOtherSalesEvents();
            }

            function initializeOtherSalesEvents() {
                // This will re-attach any necessary event handlers after modal content is updated
                if (typeof calculate_other_sales_totals === 'function') {
                    // Re-attach the input event for calculations
                    $(document).off('input', '.other_sale_new_meter').on('input', '.other_sale_new_meter',
                        function() {
                            calculate_other_sales_totals();
                        });
                }
            }


            $(document).on('change input', '#card_payment [name="card_type"], #card_payment [name="slip_no"], #card_payment [name="card_no"], #card_payment [name="card_amount"]', function() {
                var $form = realTimeCardPaymentForm($(this));

                if ($form.find('[name="card_type"]').val() && $form.find('[name="card_amount"]').val()) {
                    $form.find(".card_payment_add").attr('disabled', false);
                } else {
                    $form.find(".card_payment_add").attr('disabled', true);
                }
            })

            function toggerCardSaveBtn($form) {
                $form = $form && $form.length ? $form : $('#card_payment form').first();

                if ($form.find(".card-data").length > 0) {
                    $form.find(".card-save-btn").prop('disabled', false);
                } else {
                    $form.find(".card-save-btn").prop('disabled', true);
                }
            }

            // $(document).on('click', '.card_payment_add', function() {
            //     const dropdown = document.getElementById('card_pmt_type');
            //     const enterCardNumbers = $("#enter_card_numbers").val();
            //     if (dropdown.value !== 'bulk') {
            //         let hasError = false;
            //         if (!$("#slip_no").val()) {
            //             $("#slip_no").css("border", "1px solid red");
            //             hasError = true;
            //             $("#slip_no").on("input", function() {
            //                 $(this).css("border", "");
            //             });
            //         } else {
            //             $("#slip_no").css("border", "");
            //         }
            //         if (enterCardNumbers == 'yes') {
            //             if (!$("#card_no").val()) {
            //                 $("#card_no").css("border", "1px solid red");
            //                 hasError = true;
            //                 $("#card_no").on("input", function() {
            //                     $(this).css("border", "");
            //                 });
            //             } else {
            //                 $("#card_no").css("border", "");
            //             }
            //         }

            //         if (hasError) {
            //             toastr.error("Enter Slip Number");
            //             return;
            //         }
            //     }
            //     const cardNumberRequired = enterCardNumbers !== 'no';

            //     var data = {
            //         card_type: $("#card_type").val(),
            //         slip_no: $("#slip_no").val(),
            //         amount: $("#card_amount").val(),
            //         card_number: cardNumberRequired ? $("#card_no").val() :
            //         null, // null if not required
            //         collection_form_no: $("#collection_form_no").val()
            //     };
            //     data.shift_id = $('#shift_number').val() ?? null;
            //     var cardNumberTd = cardNumberRequired ? `<td>` + $("#card_no").val() + `</td>` : '';
            //     var amount = parseFloat($("#card_amount").val()) || 0;
            //     var formattedAmount = amount.toLocaleString(undefined, {
            //         minimumFractionDigits: 2,
            //         maximumFractionDigits: 2
            //     });

            //     var html = `
        //     <tr>
        //         <td>` + $("#card_type option:selected").text() + `</td>
        //         <td>` + $("#slip_no").val() + `</td>
        //         ` + cardNumberTd + `
        //       <td>` + formattedAmount + `
        //           <input type="hidden" name="card_data[]" class="card-data" required value='` + JSON.stringify(
            //         data) + `'>
        //     </td>

        //         <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
        //     </tr>
        // `;

            //     $("#card_payment_table tbody").append(html);
            //     $(".card_payment_input").val(""); // Clear inputs
            //     toggerCardSaveBtn();
            // });

            // Remove row when the remove button is clicked
            $(document).on('click', '.remove-row', function() {
                $(this).closest('tr').remove();
                toggerCardSaveBtn(realTimeCardPaymentForm($(this)))
            });

            $(".credit_sale_finalize").hide();
            $(".credit_sale_finalize_print").hide();

            // $(document).on('click', '.cash_payment_btn', function() {
            //     console.log('cash button clicked');
            //     $("#cash_payments").modal({
            //         backdrop: 'static',
            //         keyboard: false
            //     });
            // });

            $("#credit_sale_customer_id").val($("#credit_sale_customer_id option:eq(0)").val()).trigger('change');
            $('#order_date').datepicker("setDate", new Date());
            $('#credit_sale_product_id').select2();
            $('#credit_sale_customer_id').select2();
            $('#customer_reference').select2();
        });

        $(document).on('change', '#customer_reference_one_time', function() {
            if ($(this).val() !== '' && $(this).val() !== null && $(this).val() !== undefined) {
                $('#customer_reference').attr('disabled', 'disabled');
                $('.quick_add_customer_reference').attr('disabled', 'disabled');
            } else {
                $('#customer_reference').removeAttr('disabled');
                $('.quick_add_customer_reference').removeAttr('disabled');
            }
        })

        $(document).on('change', '.cash_payment_input', function() {
            var amount = $(this).val() ?? 0; // Default to 0 if no value
            var row = $(this).closest('.row'); // Find the closest row element
            var denom_value = row.find(".denom_value").val(); // Get the denomination value
            var total = denom_value * amount; // Calculate the total

            console.log(total); // Log the calculated total

            row.find('.denom_amt').val(total); // Set the calculated total in the denom_amt field

            calculateDenomTotals(); // Call the function to update the totals
        });


        function calculateDenomTotals() {
            var total = 0;

            $('.denom_amt').each(function() {
                var denom_total = parseFloat($(this).val()) || 0;
                total += denom_total;
            });

            $('.denom_total').val(total.toFixed(2));
        }

        // Cash denominations "Correct": copy total to main amount, close modal, then user can click Save
        // so the usual cash save runs and the "Confirm Another Payment?" modal is shown.
        $(document).on("click", ".cash_denom_save", function(e) {
            e.preventDefault();
            var totalVal = $("#cash_payments .denom_total").val() || $(".denom_total").val() || "0";
            totalVal = String(totalVal).replace(/,/g, "");
            var totalAmount = parseFloat(totalVal) || 0;
            if (totalAmount <= 0) {
                toastr.error("Please enter cash denominations");
                return;
            }
            $("#amount").val(totalAmount.toFixed(2)).trigger("input");
            $("#cash_payments").modal("hide");
            if ($("#realtime_entries_pump_operator").val() && $("#shift_number").val()) {
                $("#real_time_payment_submit").prop("disabled", false);
            }
        });

        $(document).on("click", "#real_time_payment_submit", function(e) {
            e.preventDefault();

            var $btn = $(this); // button reference

            var pump_operator = $("#realtime_entries_pump_operator").val();
            var shift_number = $("#shift_number").val();
            var totalAmount = parseFloat($("#amount").val()) || 0;

            if (!pump_operator) {
                toastr.error("Please select pump operator");
                return false;
            }

            if (!shift_number) {
                toastr.error("Please select shift number");
                return false;
            }

            if (totalAmount <= 0) {
                toastr.error("Please enter cash denominations");
                return false;
            }

            var collectionFormNo = $('#collection_form_no').val() || '';

            // Prepare a function to perform the actual AJAX save
            function doSaveCash() {
                $btn.prop('disabled', true).html('Saving...');

                $.ajax({
                    method: "POST",
                    url: "{{ route('realtime.save-cash') }}",
                    data: {
                        amount: totalAmount,
                        shift_number: shift_number,
                        collection_form_no: collectionFormNo,
                        pump_operator_id: pump_operator,
                    },
                    dataType: 'json',
                    success: function(result) {
                        // restore button so user can enter another payment if desired
                        $btn.prop('disabled', false).html("@lang('lang_v1.save')");

                        if (result.success) {
                            toastr.success(result.msg || 'Cash payment saved successfully');

                            // 🔹 collection form no update
                            var formNo = result.collection_form_no || '';
                            $(".collection_form_no").val(formNo);

                            // ✅ RESET FIELDS AFTER SAVE — keep pump operator and shift selected
                            $("#amount").val('');
                            refreshRealTimePaymentTables();
                            // track recent payments to detect duplicates in this session
                            window.recentPayments = window.recentPayments || [];
                            window.recentPayments.push({
                                type: 'cash',
                                amount: totalAmount,
                                shift: shift_number,
                                operator: pump_operator
                            });

                            // Show confirmation to enter another payment
                            $("#reloadConfirmationModalLabel").html("Confirm Another Payment for Form No. " + formNo);
                            $("#reloadConfirmationModal").removeData('pending-action').data('payment-type', 'cash').modal('show');

                        } else {
                            toastr.error(result.msg || 'Unable to save cash payment');
                        }
                    },
                    error: function(xhr) {
                        // on error also re-enable so user can retry
                        $btn.prop('disabled', false).html("@lang('lang_v1.save')");

                        var errorMsg = 'Something went wrong while saving cash payment';
                        try {
                            var response = JSON.parse(xhr.responseText);
                            errorMsg = response.msg || response.message || errorMsg;
                        } catch (e) {}

                        toastr.error(errorMsg);
                    }
                });
            }

            // Duplicate detection: if same payment already saved in this session, confirm
            window.recentPayments = window.recentPayments || [];
            var isDuplicate = window.recentPayments.some(function(p) {
                return p.type === 'cash' && p.amount === totalAmount && p.shift === shift_number && p.operator === pump_operator;
            });

            if (isDuplicate) {
                // Show confirmation modal before saving duplicate
                $("#reloadConfirmationModalLabel").html("This looks like a duplicate cash payment. Save again?");
                $("#reloadConfirmationModal").data('pending-action', 'save-cash').modal('show');

                // Wire confirm button to proceed
                $("#confirmReload").off('click.duplicate').on('click.duplicate', function() {
                    $("#confirmReload").off('click.duplicate');
                    $("#reloadConfirmationModal").modal('hide');
                    doSaveCash();
                });

            } else {
                doSaveCash();
            }
        });


        $(document).on("click", ".credit_sale_add", function() {

            if ($("#credit_sale_amount").val() == "") {
                toastr.error("Please enter amount");
                return false;
            }

            var $customerSelect = $("#credit_sale_customer_id");
            var credit_sale_customer_id = $customerSelect.val();

            if (!credit_sale_customer_id) {
                toastr.error("Please select a customer");
                $customerSelect.select2('open');
                return false;
            }

            var customer_name = $customerSelect.find("option:selected").text().trim();

            if (!customer_name || customer_name.toLowerCase().includes("please")) {
                toastr.error("Invalid customer selected");
                return false;
            }

            var $productSelect = $("#credit_sale_product_id");
            var credit_sale_product_id = $productSelect.val();

            if (!credit_sale_product_id) {
                toastr.error("Please select a product");
                $productSelect.select2('open');
                return false;
            }

            var credit_sale_product_name = $productSelect.find("option:selected").text().trim();

            var customer_reference = $("#customer_reference_one_time").val() ?
                $("#customer_reference_one_time").val() :
                $("#customer_reference").val();

            var order_date = $("#order_date").val();
            var order_number = $("#order_number").val();

            var credit_sale_price = __read_number($("#unit_price"));
            var credit_unit_discount = __read_number($("#unit_discount")) ?? 0;
            var credit_sale_qty = __read_number($("#credit_sale_qty")) ?? 0;
            var credit_total_amount = __read_number($("#credit_total_amount")) ?? 0;
            var credit_total_discount = __read_number($("#credit_discount_amount")) ?? 0;
            var credit_sub_total = __read_number($("#credit_sale_amount")) ?? 0;

            var outstanding = $(".current_outstanding").text();
            var credit_limit = $(".credit_limit").text();
            var credit_note = $("#credit_note").val();

            var credit_data = {
                settlement_no: '',
                customer_id: credit_sale_customer_id,
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
                note: credit_note,
                shift_id: $('#shift_number').val() ?? null
            };

            $("#credit_sale_table tbody").prepend(`
                <tr>
                    <td data-customer-id="${credit_sale_customer_id}">
                        ${customer_name}
                        <input type="hidden" class="credit_data" value='${JSON.stringify(credit_data)}'>
                    </td>
                    <td>${outstanding}</td>
                    <td>${credit_limit}</td>
                    <td>${order_number}</td>
                    <td>${order_date}</td>
                    <td>${customer_reference ?? ''}</td>
                    <td>${credit_sale_product_name}</td>
                    <td>${__number_f(credit_sale_price, false, false, __currency_precision)}</td>
                    <td>${__number_f(credit_sale_qty, false, false, __currency_precision)}</td>
                    <td class="credit_sale_amount">
                        ${__number_f(credit_total_amount, false, false, __currency_precision)}
                    </td>
                    <td class="credit_tbl_discount_amount">
                        ${__number_f(credit_total_discount, false, false, __currency_precision)}
                    </td>
                    <td class="credit_tbl_total_amount">
                        ${__number_f(credit_sub_total, false, false, __currency_precision)}
                    </td>
                    <td>${credit_note ?? ''}</td>
                    <td>
                        <button type="button" class="btn btn-xs btn-danger delete_credit_sale_payment">
                            <i class="fa fa-times"></i>
                        </button>
                    </td>
                </tr>
            `);

            toastr.success("Successfully Added");

            // Reset product select properly (Select2 safe)
            $productSelect.val(null).trigger("change");

            // Reset only non-customer fields
            $("#unit_price").val('');
            $("#unit_discount").val('');
            $("#credit_sale_qty").val('');
            $("#credit_total_amount").val('');
            $("#credit_discount_amount").val('');
            $("#credit_sale_amount").val('');
            $("#customer_reference_one_time").val('');
            $("#credit_note").val('');

            // DO NOT reset customer select here
            // Leave it selected intentionally (better POS UX)

            calculateTotal("#credit_sale_table", ".credit_sale_amount", ".credit_sale_total");
            calculateTotal("#credit_sale_table", ".credit_tbl_discount_amount", ".credit_tb_discount_total");
            calculateTotal("#credit_sale_table", ".credit_tbl_total_amount", ".credit_tbl_amount_total");
        });

        function calculateTotal(table_name, class_name_td, output_element) {
            let total = 0.0;
            $(table_name + " tbody")
                .find(class_name_td)
                .each(function() {
                    total += parseFloat(__number_uf($(this).text()));
                });

            if (total <= 0) {
                $(".credit_sale_finalize").hide();
                $(".credit_sale_finalize_print").hide();
            } else {
                $(".credit_sale_finalize").show();
                $(".credit_sale_finalize_print").show();
            }
            $(output_element).text(__number_f(total, false, false, __currency_precision));
        }

        $(document).on('input', '#credit_total_amount', function() {
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

        $(document).on('input', '#credit_sale_qty', function() {
            $("#credit_total_amount").attr('disabled', true);

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

        $(document).on("change", "#credit_discount_amount, #unit_price", function() {
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

        $(document).on("change", "#credit_sale_product_id", function() {
            if ($(this).val()) {
                $.ajax({
                    method: "get",
                    url: "/petro/settlement/payment/get-product-price",
                    data: {
                        product_id: $(this).val()
                    },
                    success: function(result) {
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
        $(document).on("change", "#credit_sale_customer_id", function() {
            $.ajax({
                method: "get",
                url: "/petro/settlement/payment/get-customer-details/" + $(this).val(),
                data: {},
                success: function(result) {
                    $(".current_outstanding").text(result.total_outstanding);
                    $(".credit_limit").text(result.credit_limit);
                    $("#customer_reference").empty();
                    $("#customer_reference").append(
                        `<option selected="selected" value="">Please Select</option>`);
                    if (Array.isArray(result.customer_references)) {
                        result.customer_references.forEach(function(ref) {
                            $("#customer_reference").append(
                                `<option value="${ref.reference}">${ref.reference}</option>`
                            );
                        });
                    }
                },
            });
        });
        $(document).on("click", ".delete_credit_sale_payment", function() {
            tr = $(this).closest("tr");
            tr.remove();

            calculateTotal("#credit_sale_table", ".credit_sale_amount", ".credit_sale_total");
            calculateTotal("#credit_sale_table", ".credit_tbl_discount_amount", ".credit_tb_discount_total");
            calculateTotal("#credit_sale_table", ".credit_tbl_total_amount", ".credit_tbl_amount_total");
        });

        $(document).on("click", ".credit_sale_finalize", function(e) {
            e.preventDefault();
            var dataArray = [];
            console.log("Processing credit sale finalize...");
            console.log( $("#realtime_entries_pump_operator").val());
            $(".credit_data").each(function() {
                var jsonData = JSON.parse($(this).val());
                var collection_form_no = $("#collection_form_no").val() ?? "";
                jsonData.collection_form_no = collection_form_no;
                jsonData.shift_id = $('#shift_number').val() ?? null;
                dataArray.push(jsonData);
            });
            $.ajax({
                method: "post",
                url: "/petro/pump-operator-pmts/save-credit",
                data: {
                    credit_data: dataArray,
                    collection_form_no: $("#collection_form_no").val() ?? "",
                    pump_operator_id: $("#realtime_entries_pump_operator").val(),
                },
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        var formNo = result.collection_form_no || '';
                        $(".collection_form_no").each(function() {
                            $(this).val(formNo);
                        });
                        reset();
                    } else {
                        toastr.error(result.msg || 'Something went wrong');
                    }
                },
                error: function(xhr, status, error) {
                    var errorMsg = 'Something went wrong while saving credit sale';
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.msg) {
                            errorMsg = response.msg;
                        }
                    } catch (e) {}
                    toastr.error(errorMsg);
                },
            });
        });

        $(document).on('click', '.credit_sale_finalize_print', function(e) {
            e.preventDefault();

            // Show the print copy selection modal
            $('#print_copy_selection_modal').modal('show');

            // Store button reference for later use
            window.creditSaleFinalizePrintBtn = $(this);
        });


        $(document).on('click', '#confirm_print_btn', function(e) {
            e.preventDefault();

            var selectedCopy = $('input[name="print_copy_option"]:checked').val();

            // Hide the modal and make sure backdrop is removed quickly to avoid blurring the page
            $('#print_copy_selection_modal').modal('hide').on('hidden.bs.modal', function() {
                $('.modal-backdrop').remove();
                $('#print_copy_selection_modal').off('hidden.bs.modal');
            });


            var saveBtn = window.creditSaleFinalizePrintBtn;
            // no pre-opened window required for printing in current page
            saveBtn.html('Saving & Print...');
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
                url: "/petro/pump-operator-pmts/save-credit",
                data: {
                    credit_data: dataArray,
                    collection_form_no: $("#collection_form_no").val() ?? "",
                    pump_operator_id: $("#realtime_entries_pump_operator").val(),
                    print: "print",
                    print_copy_option: selectedCopy,
                },
                dataType: 'json',
                success: function(result) {
                    saveBtn.html('Print & Save');
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
                        $(".credit_sale_fields").val("");
                        $("#customer_reference_one_time").val("").trigger("change");
                        $("#direct_cr").modal("hide");

                        if (result.print) {
                            if (result.html_content) {
                                // Open print content in a new popup window to avoid printing the main page
                                var printWin = window.open('', '_blank', 'width=800,height=600');
                                printWin.document.open();
                                printWin.document.write(result.html_content);
                                printWin.document.close();
                            } else if (result.print_credit_sale_id) {
                                // fallback to server route - open in new window
                                window.open('/petro/pump-operator-pmts/print-credit-sale/' +
                                    result.print_credit_sale_id, '_blank');
                            } else {
                                toastr.error('Print content is missing. Please try reprint.');
                            }
                        }

                        reset();
                    } else {
                        toastr.error(result.msg || 'Something went wrong');
                        saveBtn.html('Print & Save');
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
                    saveBtn.html('Print & Save');
                    saveBtn.prop('disabled', false);
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
    </script>

    <script>
        $(document).ready(function() {
            // Ensure CSRF token is sent with AJAX
            if (typeof $.ajaxSetup === 'function') {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
            }

            // Do not call $('.select2').select2() here — first script block already inits Select2.
            // Re-applying Select2 corrupts widgets and breaks #shift_number when options are replaced.

            function destroyShiftNumberSelect2() {
                var $el = $('#shift_number');
                try {
                    if ($el.data('select2')) {
                        $el.select2('destroy');
                    }
                } catch (err) {
                    // ignore
                }
            }

            function initShiftNumberSelect2() {
                $('#shift_number').select2({ width: '100%' });
            }

            function lockPayments() {
                // Disable amount input
                $("#amount").prop("disabled", true);

                // Disable keypad buttons
                $("#key_pad button").prop("disabled", true);

                // Disable payment type buttons
                $(".rtp_payment_type_btn").addClass("disabled").css("pointer-events", "none");
                $(".payment_type_checkbox").prop("disabled", true);

                // Disable all action buttons (scoped — avoid matching unrelated .btn-danger on page)
                $(".add_other_sales, .real-time-amount-correct, #real_time_payment_submit, .rtp-payment-cancel-btn").prop(
                    "disabled", true);
            }

            function unlockPayments() {
                $("#amount").prop("disabled", false);
                $("#key_pad button").prop("disabled", false);
                $(".rtp_payment_type_btn").removeClass("disabled").css("pointer-events", "auto");
                $(".payment_type_checkbox").prop("disabled", false);

                // Enable action buttons
                $(".add_other_sales, .real-time-amount-correct, .rtp-payment-cancel-btn").prop("disabled", false);

                // Save button enabled only if amount > 0
                const amount = parseFloat($("#amount").val() || 0);
                $("#real_time_payment_submit").prop("disabled", isNaN(amount) || amount <= 0);
            }

            function checkUnlockCondition() {
                const operatorId = $("#realtime_entries_pump_operator").val();
                const shiftNumber = $("#shift_number").val();

                if (operatorId && shiftNumber) {
                    unlockPayments();
                } else {
                    lockPayments();
                }
            }

            window.checkRealTimePaymentPrerequisites = checkUnlockCondition;

            // Initial state: locked until both dropdowns have values
            lockPayments();

            // Flag to prevent clearing saved shift during restore
            window._restoringOperator = false;
            window._shiftToRestore = null;

            // Pump operator change
            $('#realtime_entries_pump_operator').on('change', function() {
                var operator_id = $(this).val();

                // Use window._shiftToRestore (set during page restore) as primary source,
                // fall back to sessionStorage for manual operator changes during restore
                var savedShiftBeforeChange = window._shiftToRestore || sessionStorage.getItem('rtp_shift_number') || '';

                destroyShiftNumberSelect2();
                $('#shift_number').html('<option value="">@lang('realtimeentries::lang.select_shift_number')</option>');
                initShiftNumberSelect2();
                checkUnlockCondition(); // lock/unlock immediately

                // Persist selection so it survives page refresh
                sessionStorage.setItem('rtp_pump_operator', operator_id || '');
                // Keep payment partial hidden in sync (avoid wrong id being read elsewhere on the page)
                $('#pump_operator_id').val(operator_id || '');
                // Only clear saved shift on manual operator change, not during restore
                if (!window._restoringOperator) {
                    sessionStorage.setItem('rtp_shift_number', '');
                    savedShiftBeforeChange = '';
                }

                if (operator_id) {
                    destroyShiftNumberSelect2();
                    $('#shift_number').html('<option value="">Loading shift numbers...</option>');
                    $('#shift_number').prop('disabled', true);
                    initShiftNumberSelect2();

                    $.ajax({
                        url: '{{ route('realtime.index') }}',
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        data: {
                            get_shifts: 1,
                            pump_operator_id: operator_id
                        },
                        dataType: 'json',
                        success: function(data) {
                            // If we have a shift to restore, protect storage from being cleared during re-population
                            var isRestoringShift = (savedShiftBeforeChange !== '');
                            if (isRestoringShift) window._restoringOperator = true;

                            destroyShiftNumberSelect2();
                            $('#shift_number').empty();
                            $('#shift_number').append(
                                '<option value="">@lang('realtimeentries::lang.select_shift_number')</option>');

                            if ($.isEmptyObject(data)) {
                                $('#shift_number').append(
                                    '<option value="">No shifts found</option>');
                            } else {
                                $.each(data, function(key, value) {
                                    $('#shift_number').append('<option value="' + key +
                                        '">' + value + '</option>');
                                });
                            }

                            $('#shift_number').prop('disabled', false);
                            initShiftNumberSelect2();

                            // Restore previously selected shift after options are loaded
                            if (isRestoringShift) {
                                setTimeout(function() {
                                    if ($('#shift_number option[value="' + savedShiftBeforeChange + '"]').length) {
                                        $('#shift_number').val(savedShiftBeforeChange).trigger('change');
                                    }
                                    window._restoringOperator = false;
                                    window._shiftToRestore = null;
                                    // Re-save shift to sessionStorage in case it was cleared by async events
                                    if (savedShiftBeforeChange) {
                                        sessionStorage.setItem('rtp_shift_number', savedShiftBeforeChange);
                                    }
                                }, 100);
                            }
                        },
                        error: function(xhr, status, error) {
                            destroyShiftNumberSelect2();
                            $('#shift_number').html(
                                '<option value="">Error loading shifts</option>');
                            $('#shift_number').prop('disabled', false);
                            initShiftNumberSelect2();
                            window._shiftToRestore = null;
                        }
                    });
                }
            });

            // Shift number change
            $('#shift_number').on('change', function() {
                var shift_val = $(this).val();
                if (window._restoringOperator || window._shiftToRestore) {
                    // If restoring, only update if we actually got a value
                    // This prevents wiping storage when dropdown is temporarily emptied
                    if (shift_val && shift_val !== '') {
                        sessionStorage.setItem('rtp_shift_number', shift_val);
                    }
                } else {
                    // Manual change: always update
                    sessionStorage.setItem('rtp_shift_number', shift_val || '');
                }
                checkUnlockCondition();
            });

            // Restore pump operator and shift from sessionStorage (survives page refresh)
            // IMPORTANT: Must be AFTER change handlers are registered above
            (function restoreOperatorShift() {
                var savedOperator = sessionStorage.getItem('rtp_pump_operator');
                var savedShift = sessionStorage.getItem('rtp_shift_number');

                if (savedOperator) {
                    // Store shift to restore in a window variable so it survives async AJAX
                    window._shiftToRestore = savedShift || '';
                    if ($('#realtime_entries_pump_operator option[value="' + savedOperator + '"]').length) {
                        window._restoringOperator = true;
                        $('#realtime_entries_pump_operator').val(savedOperator).trigger('change');
                        window._restoringOperator = false;
                    } else {
                        // Dropdown might not be ready, retry once
                        setTimeout(function() {
                            if ($('#realtime_entries_pump_operator option[value="' + savedOperator + '"]').length) {
                                window._restoringOperator = true;
                                $('#realtime_entries_pump_operator').val(savedOperator).trigger('change');
                                window._restoringOperator = false;
                            }
                        }, 500);
                    }
                }
            })();

            // Amount typing: update save button
            $('#amount').on('input', function() {
                if ($("#realtime_entries_pump_operator").val() && $("#shift_number").val()) {
                    const amount = parseFloat($(this).val() || 0);
                    $("#real_time_payment_submit").prop('disabled', isNaN(amount) || amount <= 0);
                }
            });

            // Initialize: all payment buttons unlocked
            // Consolidated function to unlock all payment buttons and reset UI
            // Exposed globally so payment_section partial can access it
            window.unlockRealTimePayments = unlockRealTimePayments;
            function unlockRealTimePayments() {
                $(".rtp_payment_type_btn")
                    .removeClass("locked active disabled")
                    .css({
                        "pointer-events": "auto",
                        "background-color": "",
                        "border-color": "",
                        "color": ""
                    });

                $(".payment_type_checkbox").prop("checked", false);

                // Re-apply operator + shift gating (keypad, amount, actions, payment types)
                checkUnlockCondition();
            }

            // Keep for backward compatibility or direct calls
            function unlockAllPayments() {
                unlockRealTimePayments();
            }

            // Reset UI and unlock everything (used by Cancel button)
            // Exposed globally so payment_section partial can access it
            window.reset = reset;
            function reset() {
                // Store current values
                var currentOperator = $("#realtime_entries_pump_operator").val();
                var currentShift = $("#shift_number").val();

                // Clear amount only
                $("#amount").val('');

                // Close specific modals
                $('#reloadConfirmationModal, #cash_payments, #card_payment, #cheque_payments, #direct_cr, #other_sales').modal('hide');
                $('.modal-backdrop').remove();

                // Re-init select2 but restore values if they exist
                if (currentOperator) {
                    window._restoringOperator = true;
                    $('#realtime_entries_pump_operator').val(currentOperator).trigger('change');
                    window._restoringOperator = false;
                }
                
                // If operator change triggered (reloading shifts), we wait for it to finish and restore shift
                // or if it was already there, we manually trigger to ensure listeners check conditions.
                if (currentShift) {
                    setTimeout(function() {
                        if ($('#shift_number option[value="' + currentShift + '"]').length) {
                            $('#shift_number').val(currentShift).trigger('change');
                        }
                    }, 500);
                } else {
                    checkUnlockCondition();
                }

                // Unlock payment-type UI and re-apply operator/shift gating
                unlockRealTimePayments();

                // Save stays off until amount is confirmed (workflow + checkUnlockCondition may enable via amount rules)
                $("#real_time_payment_submit").prop('disabled', true);
                
                // Reset card/cheque temporary inputs
                $("#card_payment_table tbody, #cheques-table tbody").empty();
                resetRealTimeCardPaymentForm($('#card_payment form'));
                $(".denom_qty").val(0);
                $(".denom_amt").val('0');
                $(".denom_total").val('0.00');
                $("#cheques_customer").val('').trigger('change');
                $("#cheques_amount, #cheques_cheque_no, #cheques_cheque_date").val('');

                // Reset credit sale temporary inputs
                $("#credit_sale_table tbody").empty();
                $(".credit_sale_total").text("0.00");
                $(".credit_tb_discount_total").text("0.00");
                $(".credit_tbl_amount_total").text("0.00");
                $(".credit_sale_finalize").hide();
                $(".credit_sale_finalize_print").hide();
                $(".credit_sale_fields").val("");
                $("#customer_reference_one_time").val("").trigger("change");
                $("#credit_note").val("");
            }

            window.refreshRealTimePaymentTables = refreshRealTimePaymentTables;
            function refreshRealTimePaymentTables() {
                [
                    '#pump_operators_payment_summary_table',
                    '#pump_operators_meters_with_payments_table',
                    '#list_daily_collection_table',
                    '#daily_collection_table'
                ].forEach(function(selector) {
                    if ($.fn.DataTable && $.fn.DataTable.isDataTable(selector)) {
                        $(selector).DataTable().ajax.reload(null, false);
                    }
                });
            }

            function realTimeCardPaymentForm($source) {
                var $form = $source.closest('form');
                if (!$form.length) {
                    $form = $('#card_payment_form');
                }
                if (!$form.length) {
                    $form = $('#card_payment form').first();
                }
                return $form;
            }

            function resetRealTimeCardPaymentEntry($form) {
                var $cardType = $form.find('[name="card_type"]');

                $form.find('[name="slip_no"]').val('').css('border', '').trigger('input').trigger('change');
                $form.find('[name="card_no"]').val('').css('border', '').trigger('input').trigger('change');
                $form.find('[name="card_amount"]').val('').trigger('input').trigger('change');
                $cardType.val(null).trigger('change');
                $form.find('.card_payment_add').attr('disabled', true);
            }

            function resetRealTimeCardPaymentAmountEntry($form) {
                $form.find('[name="card_amount"]').val('').trigger('input').trigger('change');
                $form.find('.card_payment_add').attr('disabled', true);
            }

            function resetRealTimeCardPaymentForm($form) {
                $form.find('#card_payment_table tbody').empty();
                resetRealTimeCardPaymentEntry($form);
                $form.find('.card-save-btn').prop('disabled', true);
            }

            function lockOtherPayments(selectedBtn) {
                $(".rtp_payment_type_btn").not(selectedBtn)
                    .addClass("locked")
                    .css("pointer-events", "none");
            }

            function activateRealTimePaymentType($btn) {
                $(".rtp_payment_type_btn").removeClass("active");
                $btn.addClass("active");
                $btn.find(".payment_type_checkbox").prop("checked", true);
                lockOtherPayments($btn);

                if ($btn.hasClass("card_payment_btn")) {
                    resetRealTimeCardPaymentForm($('#card_payment form'));
                    $("#card_payment").modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                    $(".card-save-btn").prop('disabled', true);
                    $(".card_payment_add").attr('disabled', true);
                } else if ($btn.hasClass("add_cheque_payment")) {
                    $("#cheque_payments").modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                } else if ($btn.hasClass("po_credit_payment")) {
                    $("#direct_cr").modal('show');
                }
            }

            // Single handler: active/locked state + open card/cheque/credit modals (avoids duplicate
            // delegated handlers on .card_payment_btn / .add_cheque_payment etc.)
            $(document).on("click", ".realtime-entries-payment-ui .rtp_payment_type_btn", function(e) {
                const $this = $(this);

                if (!$("#realtime_entries_pump_operator").val() || !$("#shift_number").val()) {
                    e.preventDefault();
                    return false;
                }

                const isActive = $this.hasClass("active");

                if (isActive) {
                    e.preventDefault();
                    unlockAllPayments();
                    $this.find(".payment_type_checkbox").prop("checked", false);
                    return false;
                }

                e.preventDefault();
                activateRealTimePaymentType($this);
                return false;
            });

            // Payment UI starts locked (lockPayments above); unlock only when operator + shift are set.

            // Show customer balance for cheques
            $('#customer').on('change', function() {
                var customer_id = $(this).val();
                if (customer_id) {
                    $.ajax({
                        url: '{{ route('realtime.index') }}',
                        data: {
                            get_balance: 1,
                            customer_id: customer_id
                        },
                        success: function(balance) {
                            $('#customer-balance').text('Current Balance: ' + balance);
                        }
                    });
                } else {
                    $('#customer-balance').text('');
                }
            });

            /**
             * ===============================
             * CHEQUE PAYMENTS SECTION
             * ===============================
             */

            // Check if all required cheque fields are filled
            function toggleAddChequeBtn() {
                let customerId = $('#cheques_customer').val();
                let amount = $('#cheques_amount').val();
                let chequeDate = $('#cheques_cheque_date').val();

                if (customerId && amount && chequeNo && chequeDate) {
                    $("#add-cheque").prop("disabled", false);
                } else {
                    $("#add-cheque").prop("disabled", true);
                }
            }

            // Enable/disable Submit button
            function toggleSubmitBtn() {
                if ($("#cheques-table tbody tr").length > 0) {
                    $("#submit-cheques").prop("disabled", false);
                } else {
                    $("#submit-cheques").prop("disabled", true);
                }
            }

            // Watch input changes to enable/disable Add button
            $(document).on('input change',
                '#cheques_customer, #cheques_amount, #cheques_cheque_no, #cheques_cheque_date',
                function() {
                    toggleAddChequeBtn();
                });

            // Add cheque to table
            $('#add-cheque').on('click', function(e) {
                e.preventDefault();

                var customerId = $('#cheques_customer').val();
                var customerText = $('#cheques_customer option:selected').text();
                var amount = $('#cheques_amount').val();
                var chequeNo = $('#cheques_cheque_no').val();
                var chequeDate = $('#cheques_cheque_date').val();

                if (!customerId || !amount || !chequeNo || !chequeDate) {
                    toastr.error('All fields are required.');
                    return;
                }

                var shiftId = $('#shift_number').val();
                var row = `
                    <tr>
                        <td>${customerText}<input type="hidden" name="customer_id[]" value="${customerId}"></td>
                        <td>${amount}<input type="hidden" name="amount[]" value="${amount}"></td>
                        <td>${chequeNo}<input type="hidden" name="cheque_number[]" value="${chequeNo}"></td>
                        <td>${chequeDate}<input type="hidden" name="cheque_date[]" value="${chequeDate}"></td>
                        <td><input type="hidden" name="shift_id[]" value="${shiftId}">${shiftId}</td>
                        <td><button type="button" class="btn btn-sm btn-danger remove-cheque"><i class="fa fa-trash"></i></button></td>
                    </tr>
                `;

                $('#cheques-table tbody').append(row);

                // Do NOT reset fields – keep last values in place for easier next entry
                // Just keep Add button disabled until user edits again
                $("#add-cheque").prop("disabled", true);

                // Enable submit
                toggleSubmitBtn();
            });

            // Remove cheque row
            $(document).on('click', '.remove-cheque', function() {
                $(this).closest('tr').remove();
                toggleSubmitBtn();
            });


            /**
             * ===============================
             * CARD PAYMENTS SECTION
             * ===============================
             */

            $(document).on('change input', '#card_payment [name="card_type"], #card_payment [name="slip_no"], #card_payment [name="card_no"], #card_payment [name="card_amount"]', function() {
                var $form = realTimeCardPaymentForm($(this));

                if ($form.find('[name="card_type"]').val() && $form.find('[name="card_amount"]').val()) {
                    $form.find(".card_payment_add").attr('disabled', false);
                } else {
                    $form.find(".card_payment_add").attr('disabled', true);
                }
            });

            function toggerCardSaveBtn($form) {
                $form = $form && $form.length ? $form : $('#card_payment form').first();

                if ($form.find(".card-data").length > 0) {
                    $form.find(".card-save-btn").prop('disabled', false);
                } else {
                    $form.find(".card-save-btn").prop('disabled', true);
                }
            }

            $(document).on('click', '.card_payment_add', function() {
                const $btn = $(this);
                const $container = $btn.closest('form'); // ✅ CORRECT SCOPE

                const $dropdown = $container.find('#card_pmt_type').get(0);
                const enterCardNumbers = $container.find('#enter_card_numbers').val();

                const $slipNo = $container.find('input[name="slip_no"]');
                const $cardNo = $container.find('input[name="card_no"]');

                console.log('Slip:', $slipNo.val(), 'Card:', $cardNo.val());

                if ($dropdown && $dropdown.value !== 'bulk') {
                    let hasError = false;

                    if (!($slipNo.val() && String($slipNo.val()).trim())) {
                        $slipNo.css('border', '1px solid red');
                        hasError = true;
                    } else {
                        $slipNo.css('border', '');
                    }

                    if (!($cardNo.val() && String($cardNo.val()).trim())) {
                        $cardNo.css('border', '1px solid red');
                        hasError = true;
                    } else {
                        $cardNo.css('border', '');
                    }

                    if (hasError) {
                        toastr.error("Please enter required fields");
                        return;
                    }
                }

                const data = {
                    card_type: $container.find('[name="card_type"]').val(),
                    slip_no: $slipNo.val(),
                    amount: $container.find('[name="card_amount"]').val(),
                    card_number: $cardNo.val(),
                    collection_form_no: $container.find('[name="collection_form_no"]').val(),
                    shift_id: $('#shift_number').val() ?? null
                };

                const amount = parseFloat(data.amount) || 0;
                const formattedAmount = amount.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                const cardNumberTd = data.card_number ? `<td>${data.card_number}</td>` : '';

                const html = `
                    <tr>
                        <td>${$container.find('[name="card_type"] option:selected').text()}</td>
                        <td>${data.slip_no}</td>
                        ${cardNumberTd}
                        <td>${formattedAmount}
                            <input type="hidden" name="card_data[]" class="card-data" value='${JSON.stringify(data)}'>
                        </td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
                    </tr>
                `;

                $container.find("#card_payment_table tbody").append(html);
                resetRealTimeCardPaymentAmountEntry(realTimeCardPaymentForm($btn));
                toggerCardSaveBtn($container);
            });


            // Remove row when the remove button is clicked
            $(document).on('click', '.remove-row', function() {
                $(this).closest('tr').remove();
                toggerCardSaveBtn(realTimeCardPaymentForm($(this)));
            });

            // Handle card payment form submission for real-time entries
            $(document).on('submit', '#card_payment form', function(e) {
                // Check if we're on the real-time entries page
                var currentUrl = window.location.pathname;
                if (currentUrl.indexOf('/real-time-entries') !== -1 || currentUrl.indexOf(
                        '/real-time-payments') !== -1) {
                    e.preventDefault();

                    var cardData = [];
                    $(".card-data").each(function() {
                        var jsonData = JSON.parse($(this).val());
                        cardData.push(jsonData);
                    });

                    if (cardData.length === 0) {
                        toastr.error("Please add at least one card payment");
                        return false;
                    }

                    var shift_number = $('#shift_number').val() ?? $("#selected_shift_id").val() ?? "";
                    var transaction_date = $("#transaction_date").val() ?? "";

                    $(".card-save-btn").prop('disabled', true);
                    resetRealTimeCardPaymentForm($('#card_payment form'));
                    $("#card_payment").modal("hide");

                    // Save each card payment (wrapped in function to allow confirmation)
                    function doSaveCards() {
                        var savePromises = [];
                        cardData.forEach(function(data) {
                            var promise = $.ajax({
                                method: "POST",
                                url: "/real-time-entries/save-card",
                                data: {
                                    amount: data.amount,
                                    card_type: data.card_type,
                                    slip_no: data.slip_no,
                                    card_number: data.card_number,
                                    shift_number: shift_number,
                                    transaction_date: transaction_date
                                },
                                dataType: 'json'
                            });
                            savePromises.push(promise);
                        });

                        console.log('Starting Promise.all with', savePromises.length, 'promises');
                        Promise.all(savePromises).then(function(results) {
                            var allSuccess = results.every(function(result) {
                                return result.success;
                            });

                            if (allSuccess) {
                                toastr.success('Card payments saved successfully');

                                var lastFormNo = results.length > 0 ? results[results.length - 1].collection_form_no : null;

                                toggerCardSaveBtn();
                                $(".card-save-btn").prop('disabled', true);
                                refreshRealTimePaymentTables();

                                // track saved card payments in session list
                                window.recentPayments = window.recentPayments || [];
                                cardData.forEach(function(d) {
                                    window.recentPayments.push({
                                        type: 'card',
                                        amount: d.amount,
                                        slip_no: d.slip_no,
                                        shift: shift_number,
                                        operator: $('#realtime_entries_pump_operator').val()
                                    });
                                });

                                reset();

                            } else {
                                toastr.error('Some card payments failed to save');
                                $(".card-save-btn").prop('disabled', false);
                            }
                        }).catch(function(error) {
                            toastr.error('Error saving card payments: ' + ((error.responseJSON && error.responseJSON.msg) || error));
                            $(".card-save-btn").prop('disabled', false);
                        });
                    }

                    // Check duplicates in this session
                    window.recentPayments = window.recentPayments || [];
                    // consider a card entry a duplicate if the amount, shift and operator
                    // match.  slip numbers can vary or be empty so we don't require them to
                    // match (previous logic included slip_no which prevented the check
                    // from ever firing in some cases).
                    var hasDuplicate = cardData.some(function(d) {
                        return window.recentPayments.some(function(p) {
                            return p.type === 'card' && p.amount == d.amount && p.shift == shift_number && p.operator == $('#realtime_entries_pump_operator').val();
                        });
                    });

                    if (hasDuplicate) {

                        $("#reloadConfirmationModalLabel").html("This looks like duplicate card payment(s). Save again?");
                        $("#reloadConfirmationModal").data('pending-action', 'save-cards').modal('show');
                        $("#confirmReload").off('click.duplicateCard').on('click.duplicateCard', function() {
                            $("#confirmReload").off('click.duplicateCard');
                            $("#reloadConfirmationModal").modal('hide');
                            doSaveCards();
                        });
                    } else {
                        doSaveCards();
                    }
                }
            });


            /**
             * ===============================
             * CARD NUMERIC KEYPAD HANDLING
             * ===============================
             */

            let card_payment_currentInput = null;

            $(".card_payment_input").on('focus', function() {
                card_payment_currentInput = $(this);
            });

            window.cardPaymentEnterVal = function(val) {
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
                // card_payment_currentInput.trigger('change');
                card_payment_currentInput.trigger('input');
            }

        });
    </script>
@endsection
