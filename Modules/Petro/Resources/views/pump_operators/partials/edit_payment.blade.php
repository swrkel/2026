<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => action('\Modules\Petro\Http\Controllers\PumpOperatorPaymentController@update', $payment->id), 'method' => 'put', 'id' =>
        'customer_reference_add_form' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'petro::lang.edit_payment' )</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-12">
                <div class="form-group">
                    {!! Form::label('payment_amount', __( 'petro::lang.amount' ) . ':*') !!}
                    @php
                        // Format amount: show 2 decimal places, remove trailing zeros for whole numbers
                        // e.g., 23010.000000 -> 23010, 23010.50 -> 23010.50
                        $formatted_amount = is_numeric($payment->payment_amount) 
                            ? number_format((float)$payment->payment_amount, 2, '.', '')
                            : $payment->payment_amount;
                        // Remove trailing zeros and decimal point if not needed
                        $formatted_amount = rtrim(rtrim($formatted_amount, '0'), '.');
                    @endphp
                    {!! Form::text('payment_amount', $formatted_amount, ['class' => 'form-control amount', 'required',
                    'placeholder' => __(
                    'petro::lang.amount' ) ]) !!}
                </div>
            </div>
            {{-- <div class="col-md-12">
                <div class="form-group">
                    {!! Form::label('payment_type', __( 'petro::lang.branch' ) . ':*') !!}
                    {!! Form::select('payment_type', $payment_types, $payment->payment_type , ['class' => 'form-control select2
                    payment_type', 'disabled',
                    'placeholder' => __(
                    'petro::lang.please_select' ), 'style' => 'width: 100%;']) !!}
                </div>
            </div> --}}
            <div class="col-md-12">
                <div class="form-group">
                    {!! Form::label('location_name', __('petro::lang.location') . ':') !!}
                    {!! Form::text('location_name', $payment->location_name ?? '', ['class' => 'form-control', 'readonly']) !!}
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    {!! Form::label('note', __( 'petro::lang.note' ) . ':* '. '(Compulsory to Enter)') !!}
                    {!! Form::textarea('note', $payment->note, ['class' => 'form-control note', 'required', 'rows' => 3,
                    'placeholder' => __(
                    'petro::lang.note' ) ]) !!}
                </div>
            </div>

            {{-- Hidden field for credit_sale_id when editing credit payments --}}
            @if(isset($payment->credit_sale_id))
                {!! Form::hidden('credit_sale_id', $payment->credit_sale_id) !!}
            @endif
            {{-- Hidden field for payment_type --}}
            {!! Form::hidden('payment_type', $payment->payment_type) !!}

            <div class="clearfix"></div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
            </div>

            {!! Form::close() !!}

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->

    <script>
       $('.select2').select2();
       
       // Handle AJAX form submission
       $('#customer_reference_add_form').on('submit', function(e) {
           e.preventDefault();
           
           var form = $(this);
           var formData = form.serialize();
           var url = form.attr('action');
           var paymentId = '{{ $payment->id }}';
           var paymentType = '{{ $payment->payment_type }}';
           
           $.ajax({
               url: url,
               method: 'POST',
               data: formData + '&_method=PUT',
               headers: {
                   'X-Requested-With': 'XMLHttpRequest'
               },
               success: function(response) {
                   if (response.success) {
                       // Close the modal
                       $('.view_modal').modal('hide');
                       
                       // If it's a cash payment, update the cash table row
                       if (paymentType === 'cash' && typeof updateCashPaymentRow === 'function') {
                           updateCashPaymentRow(paymentId, response.payment_amount, response.old_amount);
                       }
                       
                       // Trigger a custom event for other listeners
                       $(document).trigger('payment:updated', [response]);
                       
                       toastr.success(response.msg);
                       
                       // Reload the payment table if it exists
                       if ($.fn.DataTable.isDataTable('#pump_operator_payment_table')) {
                           $('#pump_operator_payment_table').DataTable().ajax.reload(null, false);
                       }
                       
                       // Reload the Payment Summary table if it exists (for Pumper Management/Payment Summary)
                       if ($.fn.DataTable.isDataTable('#pump_operators_payment_summary_table')) {
                           $('#pump_operators_payment_summary_table').DataTable().ajax.reload(null, false);
                       }
                       
                       // Reload the Daily Collection table if it exists (for Daily collection/Daily credit sale)
                       if ($.fn.DataTable.isDataTable('#daily_collection_table')) {
                           $('#daily_collection_table').DataTable().ajax.reload(null, false);
                       }
                   } else {
                       toastr.error(response.msg);
                   }
               },
               error: function(xhr) {
                   var response = xhr.responseJSON;
                   var errorMsg = response && response.msg ? response.msg : 'An error occurred while updating the payment.';
                   toastr.error(errorMsg);
               }
           });
       });
    </script>