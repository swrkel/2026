<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => url('petropd/pump-operators/payment/' . $payment->id), 'method' => 'put', 'id' =>
        'customer_reference_add_form' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'petropd::lang.edit_payment' )</h4>
        </div>

        <div class="modal-body">
            {{-- IS1460 credit sale edit professional restore --}}
            @php
                $is_credit_payment = strtolower((string)($payment->payment_type ?? '')) === 'credit';
                $credit_sale_obj = $credit_sale ?? null;
                $display_amount = $credit_sale_obj->amount ?? $payment->payment_amount ?? 0;
                $formatted_amount = is_numeric($display_amount)
                    ? rtrim(rtrim(number_format((float)$display_amount, 2, '.', ''), '0'), '.')
                    : $display_amount;
            @endphp

            @if($is_credit_payment)
                <div class="row" style="margin-bottom:12px;">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('display_payment_type', __('petropd::lang.payment_method') . ':') !!}
                            {!! Form::text('display_payment_type', 'Credit Sales', ['class' => 'form-control', 'readonly']) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('display_collection_form_no', __('petropd::lang.collection_form_no') . ':') !!}
                            {!! Form::text('display_collection_form_no', $payment->collection_form_no ?? '', ['class' => 'form-control', 'readonly']) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('display_order_no', __('petropd::lang.order_no') . ':') !!}
                            {!! Form::text('order_number', $credit_sale_obj->order_number ?? '', ['class' => 'form-control', 'placeholder' => __('petropd::lang.order_no')]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('location_name', __('petropd::lang.location') . ':') !!}
                            {!! Form::text('location_name', $payment->location_name ?? '', ['class' => 'form-control', 'readonly']) !!}
                        </div>
                    </div>
                </div>

                <div class="row" style="margin-bottom:12px;">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('payment_amount', __( 'petropd::lang.amount' ) . ':*') !!}
                            {{--
                                Amount is editable and linked both ways with Qty.

                                Previously it was readonly and derived from Qty only.
                                Now either field can be typed and the other follows,
                                using the line's unit price:

                                    amount = (qty x price) - discount
                                    qty    = (amount + discount) / price

                                The two handlers guard against each other so editing
                                one cannot bounce back and overwrite what is being
                                typed.
                            --}}
                            {!! Form::text('payment_amount', $formatted_amount, ['class' => 'form-control amount pd-credit-edit-amount', 'required', 'inputmode' => 'decimal', 'placeholder' => __('petropd::lang.amount')]) !!}
                            <small class="text-muted">Enter the Amount or the Qty - the other updates automatically.</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('display_customer', __('contact.customer') . ':') !!}
                            {!! Form::select('customer_id', $customers ?? [], $credit_sale_obj->customer_id ?? null, ['class' => 'form-control select2', 'placeholder' => __('lang_v1.please_select')]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('display_outstanding', (__('lang_v1.outstanding') == 'lang_v1.outstanding' ? 'Outstanding' : __('lang_v1.outstanding')) . ':') !!}
                            {!! Form::text('display_outstanding', isset($credit_sale_obj->customer_outstanding) ? @num_format($credit_sale_obj->customer_outstanding) : '', ['class' => 'form-control', 'readonly']) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('display_limit', __('lang_v1.credit_limit') . ':') !!}
                            {!! Form::text('display_limit', isset($credit_sale_obj->customer_limit) ? @num_format($credit_sale_obj->customer_limit) : '', ['class' => 'form-control', 'readonly']) !!}
                        </div>
                    </div>
                </div>

                @php
                    /*
                     * LA-1162 #1: quantities are handled to 2 decimal places.
                     *
                     * The current value used to be keyed with number_format(..., 3),
                     * so a stored qty of 47.005 became the option "47.005" while the
                     * amount beside it was rounded to 2 decimals - the two no longer
                     * agreed. Everything here is now normalised to 2 decimals, which
                     * matches how the amount is calculated and displayed.
                     */
                    $current_qty = round((float) ($credit_sale_obj->qty ?? 0), 2);

                    // Shown without trailing zeros: 47.00 -> 47, 47.50 -> 47.5
                    $current_qty_value = $current_qty > 0
                        ? rtrim(rtrim(number_format($current_qty, 2, '.', ''), '0'), '.')
                        : '';
                @endphp
                <div class="row" style="margin-bottom:12px;">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('qty', 'Qty:*') !!}
                            {{--
                                LA-1162 #1: a plain number box, not a dropdown.

                                This was a searchable select2 listing 1-100, where a
                                decimal had to be TYPED INTO THE SEARCH BOX to create
                                it as a new option. That was not discoverable - it had
                                to be explained before it could be used - and it only
                                worked at all if select2's tag support initialised
                                correctly, which it did not.

                                A quantity can be almost any value, so a number box is
                                the right control: type 47.35 directly.

                                step="any", NOT "0.01".

                                With step="0.01" the browser refused anything with
                                more than 2 decimals - "Please enter a valid value.
                                The two nearest valid values are 401.49 and 401.5."
                                That blocked the Amount -> Qty direction, which
                                derives a 4 decimal quantity on purpose so the
                                entered Amount survives the save.

                                step="any" removes the browser's own restriction and
                                leaves the field free. min is dropped for the same
                                reason - it is another browser-side rule that can
                                block submission with a message the user cannot act
                                on. A negative quantity is still rejected by the
                                handlers below and by the server's numeric|min:0.
                            --}}
                            {!! Form::input('number', 'qty', $current_qty_value, [
                                'class' => 'form-control pd-credit-edit-qty',
                                'required',
                                'step' => 'any',
                                'inputmode' => 'decimal',
                                'placeholder' => '0.00',
                                'data-price' => (float) ($credit_sale_obj->price ?? 0),
                                'data-discount' => (float) ($credit_sale_obj->total_discount ?? 0),
                                'style' => 'width:100%;',
                            ]) !!}
                            <small class="text-muted">Enter the Qty or the Amount - the other updates automatically.</small>
                        </div>
                    </div>
                </div>

                <div class="row" style="margin-bottom:12px;">
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! Form::label('note', __( 'petropd::lang.note' ) . ':* '. '(Compulsory to Enter)') !!}
                            {!! Form::textarea('note', $payment->note ?? '', ['class' => 'form-control note', 'required', 'rows' => 3, 'placeholder' => __('petropd::lang.note')]) !!}
                        </div>
                    </div>
                </div>
            @else
            <div class="col-md-12">
                <div class="form-group">
                    {!! Form::label('payment_amount', __( 'petropd::lang.amount' ) . ':*') !!}
                    @php
                        $formatted_amount = is_numeric($payment->payment_amount) 
                            ? number_format((float)$payment->payment_amount, 2, '.', '')
                            : $payment->payment_amount;
                        $formatted_amount = rtrim(rtrim($formatted_amount, '0'), '.');
                    @endphp
                    {!! Form::text('payment_amount', $formatted_amount, ['class' => 'form-control amount', 'required',
                    'placeholder' => __( 'petropd::lang.amount' ) ]) !!}
                </div>
            </div>
            @if(strtolower((string)($payment->payment_type ?? '')) === 'card')
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('customer_id', __('contact.customer') . ':*') !!}
                            {!! Form::select('customer_id', $customers ?? [], $payment->customer_id ?? null, [
                                'class' => 'form-control select2',
                                'required',
                                'placeholder' => __('lang_v1.please_select'),
                                'style' => 'width:100%;',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('card_type', 'Card Type:*') !!}
                            {!! Form::select('card_type', $card_types ?? [], $payment->card_type ?? null, [
                                'class' => 'form-control select2',
                                'required',
                                'placeholder' => __('lang_v1.please_select'),
                                'style' => 'width:100%;',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('card_number', 'Card Number:') !!}
                            {!! Form::text('card_number', $payment->card_number ?? '', ['class' => 'form-control']) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('slip_no', 'Slip No:') !!}
                            {!! Form::text('slip_no', $payment->slip_no ?? '', ['class' => 'form-control']) !!}
                        </div>
                    </div>
                </div>
            @endif
            <div class="col-md-12">
                <div class="form-group">
                    {!! Form::label('location_name', __('petropd::lang.location') . ':') !!}
                    {!! Form::text('location_name', $payment->location_name ?? '', ['class' => 'form-control', 'readonly']) !!}
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    {!! Form::label('note', __( 'petropd::lang.note' ) . ':* '. '(Compulsory to Enter)') !!}
                    {!! Form::textarea('note', $payment->note ?? '', ['class' => 'form-control note', 'required', 'rows' => 3,
                    'placeholder' => __( 'petropd::lang.note' ) ]) !!}
                </div>
            </div>
            @endif

            {{-- Hidden field for credit_sale_id when editing credit payments --}}
            @if(isset($payment->credit_sale_id))
                {!! Form::hidden('credit_sale_id', $payment->credit_sale_id) !!}
            @endif
            {{-- Hidden field for payment_type --}}
            {!! Form::hidden('payment_type', $payment->payment_type ?? '') !!}

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

       /*
        | LA-1162: Qty is a plain number input, so there is no select2 to set up.
        |
        | It deliberately does NOT carry the 'select2' class, so the
        | $('.select2').select2() call above leaves it alone.
        |
        | The amount is recalculated as the quantity changes. 'input' fires while
        | typing and 'change' fires on blur and on the spinner arrows, so the
        | Amount box - which is readonly and cannot be corrected by hand - always
        | matches the quantity actually entered.
        */
       /*
        |----------------------------------------------------------------------
        | Amount <-> Qty, linked both ways.
        |----------------------------------------------------------------------
        |
        | Either field can be typed and the other follows, from the line's unit
        | price held in data-price, with data-discount applied:
        |
        |     amount = (qty x price) - discount
        |     qty    = (amount + discount) / price
        |
        | The isSyncing guard is essential. Writing to one field fires its own
        | 'input'/'change' handler, which would immediately recompute the field
        | being typed in and fight the user - a number would appear to correct
        | itself mid-keystroke. The flag makes the write one-way for that moment.
        |
        | Rounding differs by direction, on purpose:
        |
        |   typing QTY    - qty is rounded to 2 decimals on blur, as before, and
        |                   the amount follows exactly.
        |
        |   typing AMOUNT - qty is kept to 4 decimals, NOT 2. The server
        |                   recalculates amount as round(qty x price, 4) and that
        |                   value wins, so a qty rounded to 2 would change the
        |                   amount the user just typed. 4 decimals keeps the
        |                   entered amount intact when it is saved.
        */
       (function () {
           var $qty = $('.pd-credit-edit-qty');
           var $amount = $('#customer_reference_add_form [name="payment_amount"]');
           var isSyncing = false;

           function priceOf() {
               return parseFloat($qty.data('price')) || 0;
           }

           function discountOf() {
               return parseFloat($qty.data('discount')) || 0;
           }

           // Qty -> Amount
           function amountFromQty() {
               if (isSyncing) {
                   return;
               }

               var qty = parseFloat($qty.val());
               var price = priceOf();

               if (!isFinite(qty) || qty < 0 || price <= 0) {
                   return;
               }

               isSyncing = true;
               $amount.val(Math.max(0, (qty * price) - discountOf()).toFixed(2));
               isSyncing = false;
           }

           // Amount -> Qty
           function qtyFromAmount() {
               if (isSyncing) {
                   return;
               }

               var amount = parseFloat($amount.val());
               var price = priceOf();

               // A zero unit price would divide by zero - leave Qty alone.
               if (!isFinite(amount) || amount < 0 || price <= 0) {
                   return;
               }

               var qty = (amount + discountOf()) / price;

               isSyncing = true;
               // 4 decimals - see the note above on why not 2.
               $qty.val(parseFloat(qty.toFixed(4)));
               isSyncing = false;
           }

           $qty.on('input change', amountFromQty);
           $amount.on('input change', qtyFromAmount);

           /*
            | Tidy up on blur, so a half-typed value is not reformatted mid-entry.
            */
           /*
            | Qty is NOT rounded on blur any more.
            |
            | It used to snap to 2 decimals, which silently undid the Amount ->
            | Qty direction: a derived 401.4997 became 401.5, and the Amount
            | recalculated from it no longer matched what the user had typed.
            |
            | Trailing zeros are tidied (47.5000 -> 47.5) without changing the
            | value, and the Amount is refreshed so the two always agree.
            */
           $qty.on('blur', function () {
               var qty = parseFloat($(this).val());

               if (!isFinite(qty) || qty < 0) {
                   return;
               }

               isSyncing = true;
               $(this).val(String(qty));
               isSyncing = false;

               amountFromQty();
           });

           $amount.on('blur', function () {
               var amount = parseFloat($(this).val());

               if (!isFinite(amount) || amount < 0) {
                   return;
               }

               isSyncing = true;
               $(this).val(amount.toFixed(2));
               isSyncing = false;
           });
       })();

       // Handle AJAX form submission
       $('#customer_reference_add_form').on('submit', function(e) {
           e.preventDefault();
           
           var form = $(this);
           var formData = form.serialize();
           var url = form.attr('action');
           var paymentId = '{{ $payment->id }}';
           var paymentType = '{{ $payment->payment_type ?? '' }}';
           
           $.ajax({
               url: url,
               method: 'POST',
               data: formData + '&_method=PUT',
               headers: {
                   'X-Requested-With': 'XMLHttpRequest'
               },
               success: function(response) {
                   if (response.success) {
                       // Close the edit popup after successful save.
                       // The Payment Summary edit form is loaded into .pd_payment_edit_modal,
                       // not .view_modal, so hiding only .view_modal leaves this popup open.
                       var $editModal = form.closest('.modal');
                       if ($editModal.length) {
                           $editModal.modal('hide');
                       } else {
                           $('.pd_payment_edit_modal, .view_modal').modal('hide');
                       }
                       
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
