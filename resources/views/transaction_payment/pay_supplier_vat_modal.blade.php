<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('TransactionPaymentController@postPayVatDue'), 'method' => 'post', 'id' =>
    'pay_contact_due_form', 'files' => true ]) !!}

    {!! Form::hidden("due_payment_type", $due_payment_type); !!}
    {!! Form::hidden("statement_id", $statement_id); !!}
    
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
          aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'purchase.add_payment' )</h4>
    </div>

    <div class="modal-body">
      <div class="row payment_row">
        <div class="col-md-4">
          <div class="form-group">
            {!! Form::label("location_id" , __('purchase.business_location') . ':*') !!}
            <div class="input-group">
              <span class="input-group-addon">
                <i class="fa fa-location-arrow"></i>
              </span>
              {!! Form::select("location_id", $business_locations, $business_location_id, ['class' => 'form-control
              select2 location_id', 'required', 'style' => 'width:100%;', 'placeholder' =>
              __('lang_v1.please_select')]); !!}
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            {!! Form::label("payment_ref_no" , __('lang_v1.ref_no') . ':*') !!}
            <div class="input-group">
              <span class="input-group-addon">
                <i class="fa fa-link"></i>
              </span>
              {!! Form::text("payment_ref_no", $payment_ref_no, ['class' => 'form-control
               payment_ref_no', 'readonly', 'style' => 'width:100%;', 'placeholder' =>
              __('lang_v1.ref_no')]); !!}
            </div>
          </div>
        </div>
        <div class="col-md-12">
          <div class="form-group">
            @if($payment_line->amount > 0)
              <div class="alert alert-info">
                <strong>@lang('lang_v1.vat_statement_amount'):</strong> 
                <span class="display_currency" data-currency_symbol="true" data-orig-value="{{ $payment_line->amount }}">
                  {{ $payment_line->amount }}
                </span>
                <br><small>@lang('lang_v1.vat_statement_amount_note')</small>
              </div>
            @else
              <div class="alert alert-warning">
                <strong>@lang('lang_v1.vat_statement_no_amount'):</strong> 
                @lang('lang_v1.vat_statement_no_amount_note')
              </div>
            @endif
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            {!! Form::label("amount" , __('sale.amount') . ':*') !!}
            <div class="input-group">
              <span class="input-group-addon">
                <i class="fa fa-money"></i>
              </span>
              {!! Form::text("amount", $payment_line->amount, ['class' => 'form-control input_number',
              'data-rule-min-value' => 0, 'data-msg-min-value' => __('lang_v1.negative_value_not_allowed'), 'required',
              'placeholder' => 'Amount', 'id' => 'amount']); !!}
            </div>
            <small class="help-block">@lang('lang_v1.amount_can_be_modified')</small>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            {!! Form::label("paid_on" , __('lang_v1.paid_on') . ':*') !!}
            <div class="input-group">
              <span class="input-group-addon">
                <i class="fa fa-calendar"></i>
              </span>
              {!! Form::text('paid_on', @format_datetime($payment_line->paid_on), ['class' => 'form-control',
              'readonly', 'required']); !!}
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            {!! Form::label("method" , __('purchase.payment_method') . ':*') !!}
            <div class="input-group">
              <span class="input-group-addon">
                <i class="fa fa-money"></i>
              </span>
              {!! Form::select("method", $payment_types, $payment_line->method, ['class' => 'form-control select2
              payment_types_dropdown', 'required', 'style' => 'width:100%;']); !!}
            </div>
          </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-md-4">
          <div class="form-group">
            {!! Form::label('document', __('purchase.attach_document') . ':') !!}
            {!! Form::file('document'); !!}
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            {!! Form::label("account_id" , __('lang_v1.payment_account') . ':') !!}
            <div class="input-group">
              <span class="input-group-addon">
                <i class="fa fa-money"></i>
              </span>

              {!! Form::select("account_id", $accounts, !empty($payment_line->account_id) ? $payment_line->account_id : '' , 
                                ['class' => 'form-control select2 account_id', 'id' => "account_id", 'style' => 'width:100%;']); !!}
            </div>
          </div>
        </div>

        <div class="clearfix"></div>

        @include('transaction_payment.payment_type_details')
        <div class="col-md-12">
          <div class="form-group">
            {!! Form::label("note", __('lang_v1.payment_note') . ':') !!}
            {!! Form::textarea("note", $payment_line->note, ['class' => 'form-control', 'rows' => 3]); !!}
          </div>
        </div>
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary submit_btn" id="submit_btn" {{ $payment_line->amount <= 0 ? 'disabled' : '' }}>
        @lang( 'messages.save' )
      </button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
  $('.payment_types_dropdown').trigger('change');
  $('#pay_contact_due_form').validate();
  $(".select2").select2();

  // Format amount field on page load
  $(document).ready(function() {
    if ($('#amount').val()) {
      $('#amount').val(__number_f($('#amount').val()));
    }
    
    // Add input event to format amount as user types
    $('#amount').on('input', function() {
      let value = $(this).val();
      if (value) {
        // Remove any non-numeric characters except decimal point
        value = value.replace(/[^\d.]/g, '');
        $(this).val(value);
      }
      
      // Update submit button state
      updateSubmitButtonState();
    });
    
    // Format amount on blur
    $('#amount').on('blur', function() {
      let value = $(this).val();
      if (value && !isNaN(value)) {
        $(this).val(__number_f(parseFloat(value)));
      }
      
      // Update submit button state
      updateSubmitButtonState();
    });
    
    // Function to update submit button state
    function updateSubmitButtonState() {
      let amount = parseFloat($('#amount').val()) || 0;
      let vatStatementAmount = parseFloat('{{ $payment_line->amount }}') || 0;
      
      if (amount > 0) {
        $('#submit_btn').prop('disabled', false);
        
        // Show warning if amount exceeds VAT statement amount
        if (amount > vatStatementAmount) {
          if (!$('#amount_warning').length) {
            $('#amount').after('<div id="amount_warning" class="help-block text-warning">@lang("lang_v1.amount_exceeds_vat_statement")</div>');
          }
        } else {
          $('#amount_warning').remove();
        }
      } else {
        $('#submit_btn').prop('disabled', true);
        $('#amount_warning').remove();
      }
    }
    
    // Initial state
    updateSubmitButtonState();
  });

  $(document).on('change', '.location_id', function(){
    let location_id = $(this).val();
    $.ajax({
      method: 'get',
      url: "/payments/get-payment-method-by-location-id/"+location_id,
      data: {  },
      contentType: 'html',
      success: function(result) {
        if(result){
          $('#method').empty().append(result);
          $('#method option:eq(0)').prop('selected', 'selected');
          $('.payment_types_dropdown').trigger('change');
        }
      },
    });
  })
  
 
</script>