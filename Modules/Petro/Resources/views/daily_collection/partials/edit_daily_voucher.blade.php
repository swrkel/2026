<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
     
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">@lang( 'petro::lang.edit_credit_sale' )</h4>
      </div>
  
      <div class="modal-body">
          <form id="edit_daily_voucher_form" action="{{ action('\Modules\Petro\Http\Controllers\DailyVoucherController@update', $daily_voucher->id) }}" method="POST">
              @csrf
              <input type="hidden" name="_method" value="PUT">
              <input type="hidden" name="daily_voucher_id" value="{{ $daily_voucher->id }}">
              <input type="hidden" name="payment_id" value="{{ $payment_id ?? '' }}">
              <input type="hidden" name="credit_sale_id" value="{{ $credit_sale_id ?? ($credit_sale_payment->id ?? '') }}">
              
          <div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('cr_pump_operator_id', "Pump Operator".':') !!}
                    {!! Form::select('cr_pump_operator_id', $pump_operators, $daily_voucher->operator_id, ['class' => 'form-control select2',
                    'style' => 'width: 100%;', 'disabled' => true]); !!}
                </div>
            </div>
            <div class="clearfix"></div>
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('credit_sale_customer_id', __('petro::lang.customer').':') !!}
                    {!! Form::select('credit_sale_customer_id', $customers, $daily_voucher->customer_id, ['class' => 'form-control select2 credit_sale_fields',
                    'style' => 'width: 100%;', 'id' => 'edit_credit_sale_customer_id']); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('order_number', __( 'petro::lang.order_number' ) ) !!}
                    {!! Form::text('order_number', $daily_voucher->voucher_order_number, ['class' => 'form-control credit_sale_fields
                    order_number',
                    'placeholder' => __(
                    'petro::lang.order_number' ) ]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('order_date', __( 'petro::lang.order_date' ) ) !!}
                    {!! Form::text('order_date', @format_date($daily_voucher->voucher_order_date), ['class' => 'form-control
                    order_date',
                    'placeholder' => __(
                    'petro::lang.order_date' ) ]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('customer_reference', __( 'petro::lang.select_customer_vehicle_no' ) ) !!}
                   {!! Form::select('customer_reference', [], $daily_voucher->vehicle_no, ['class' => 'form-control credit_sale_fields select2
                        customer_reference', 'id' => 'edit_customer_reference', 'style' => 'width: 100%',
                        'placeholder' => __(
                        'petro::lang.please_select' ) ]); !!}
                </div>
            </div>
            
            <div class="clearfix"></div>
            
            @php
                $item = $daily_voucher_items->first();
                $credit_payment = $credit_sale_payment;
            @endphp
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('credit_sale_product_id', __('petro::lang.credit_sale_product').':') !!}
                    {!! Form::select('credit_sale_product_id', $products, $item->product_id ?? null, ['class' => 'form-control select2 credit_sale_fields',
                    'style' => 'width: 100%;',
                    'placeholder' => __(
                    'petro::lang.please_select' ), 'id' => 'edit_credit_sale_product_id']); !!}
                </div>
                <input type="hidden" id="manual_discount" value="{{auth()->user()->can('manual_discount') ? 1 : 0}}">

            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('unit_price', __( 'petro::lang.unit_price' ) ) !!}
                    {!! Form::text('unit_price', $item ? @num_format($item->unit_price) : null, ['class' => 'form-control input_number
                    unit_price', 'readonly',
                    'placeholder' => __(
                    'petro::lang.unit_price' ) ]); !!}
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('unit_discount', __( 'petro::lang.unit_discount' ) ) !!}
                    {!! Form::text('unit_discount', $credit_payment ? @num_format($credit_payment->discount) : null, ['class' => 'form-control input_number
                    unit_discount',
                    'placeholder' => __(
                    'petro::lang.unit_discount' ) ]); !!}
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('credit_sale_qty', __( 'petro::lang.credit_sale_qty' ) ) !!}
                    {!! Form::text('credit_sale_qty', $item ? @num_format($item->qty) : null, ['class' => 'form-control credit_sale_fields input_number
                    credit_sale_qty',
                    'placeholder' => __(
                    'petro::lang.credit_sale_qty' ) ]); !!}
                </div>
            </div>
            
            <div class="clearfix"></div>
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('credit_total_amount', __( 'petro::lang.amount' ).__( 'petro::lang.before_discount_cr' ) ) !!} 
                    
                    {!! Form::text('credit_total_amount', $credit_payment ? @num_format($credit_payment->amount) : null, ['id' => 'credit_total_amount', 'class' => 'form-control credit_sale_fields cust_input_number
                    credit_total_amount', 'required', 'disabled' => true,
                    'placeholder' => __(
                    'petro::lang.credit_total_amount' ) ]); !!}
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('credit_discount_amount', __( 'petro::lang.credit_discount_amount' ) ) !!}
                    {!! Form::text('credit_discount_amount', $credit_payment ? @num_format($credit_payment->total_discount) : null, ['class' => 'form-control credit_sale_fields cust_input_number
                    credit_discount_amount', 'required', 'disabled' => true,
                    'placeholder' => __(
                    'petro::lang.credit_discount_amount' ) ]); !!}
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('customer_reference_one_time', __( 'petro::lang.enter_customer_vehicle_no' ) ) !!}
                    {!! Form::text('customer_reference_one_time', $daily_voucher->vehicle_reference ?? null, ['class' => 'form-control
                    customer_reference_one_time', 'id' => 'customer_reference_one_time',
                    'placeholder' => __(
                    'petro::lang.enter_customer_vehicle_no' ) ]); !!}
                </div>
            </div>
            
            
            <div class="col-md-4">
                <div class="form-group">
                  {!! Form::label("credit_note", __('lang_v1.payment_note') . ':') !!}
                  {!! Form::textarea("credit_note", $credit_payment->note ?? null, ['class' => 'form-control cash_fields', 'rows' => 3, 'name' => 'note']); !!}
                </div>
            </div>
            <div class="col-md-2 pull-right">
                <button type="submit" class="btn btn-primary pull-right edit_credit_sale_submit"
                    style="margin-top: 23px;">@lang('messages.update')</button>
            </div>
            <div class="clearfix"></div>

            <div class="col-md-4 text-red" style="font-size: 18px; font-weight: bold; ">
                @lang('petro::lang.current_outstanding'): <span class="current_outstanding">{{ @num_format($daily_voucher->current_outstanding) }}</span></div>
            <div class="col-md-4 text-red" style="font-size: 18px; font-weight: bold; ">
                @lang('petro::lang.credit_limit'): <span class="credit_limit">{{ @num_format($daily_voucher->credit_limit ?? 0) }}</span></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <table class="table table-bordered table-striped" id="credit_sale_table">
            <thead>
                <tr>
                    <th>@lang('petro::lang.cusotmer_name' )</th>
                    <th>@lang('petro::lang.outstanding' )</th>
                    <th>@lang('petro::lang.limit' )</th>
                    <th>@lang('petro::lang.order_no' )</th>
                    <th>@lang('petro::lang.order_date' )</th>
                    <th>@lang('petro::lang.customer_reference' )</th>
                    <th>@lang('petro::lang.product' )</th>
                    <th>@lang('petro::lang.unit_price' )</th>
                    <th>@lang('petro::lang.qty' )</th>
                    <th>@lang('petro::lang.sub_total' )</th>
                    <th>@lang('petro::lang.discount_total' )</th>
                    <th>@lang('petro::lang.total' )</th>
                    <th>@lang('lang_v1.note') </th>
                    <th>@lang('petro::lang.action' )</th>
                </tr>
            </thead>
            <tbody>
                @if($item && $credit_payment)
                <tr>
                    <td>{{ $daily_voucher->customer_name }}</td>
                    <td class="text-right">{{ @num_format($daily_voucher->current_outstanding) }}</td>
                    <td class="text-right">{{ @num_format($daily_voucher->credit_limit ?? 0) }}</td>
                    <td>{{ $daily_voucher->voucher_order_number }}</td>
                    <td>{{ @format_date($daily_voucher->voucher_order_date) }}</td>
                    <td>{{ $daily_voucher->vehicle_reference ?? '' }}</td>
                    <td>{{ $item->product_name ?? '' }}</td>
                    <td class="text-right">{{ @num_format($item->unit_price) }}</td>
                    <td class="text-right">{{ @num_format($item->qty) }}</td>
                    <td class="text-right">{{ @num_format($credit_payment->amount) }}</td>
                    <td class="text-right">{{ @num_format($credit_payment->total_discount) }}</td>
                    <td class="text-right">{{ @num_format($credit_payment->sub_total) }}</td>
                    <td>{{ $credit_payment->note ?? '' }}</td>
                    <td></td>
                </tr>
                @endif
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="9" style="text-align: right; font-weight: bold;">@lang('petro::lang.total') :</td>
                    <td class="text-right credit_sale_total" style="font-weight: bold;">
                        {{ $credit_payment ? @num_format($credit_payment->amount) : '0.00' }}</td>
                    <td class="text-right credit_tb_discount_total" style="font-weight: bold;">
                        {{ $credit_payment ? @num_format($credit_payment->total_discount) : '0.00' }}</td>
                    <td class="text-right credit_tbl_amount_total" style="font-weight: bold;">
                        {{ $credit_payment ? @num_format($credit_payment->sub_total) : '0.00' }}</td>
                </tr>
                <input type="hidden" value="{{ $credit_payment ? $credit_payment->sub_total : 0 }}" name="credit_sale_total" id="credit_sale_total">
            </tfoot>
        </table>
    </div>
    
     <div class="col-md-2 pull-right">
        <button type="submit" class="btn btn-danger pull-right"
            style="margin-top: 23px;">@lang('messages.update')</button>
    </div>
            
            
</div>
          </form>

      </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
  
  <script>
      $(document).ready(function() {
          var dailyVoucherId = {{ $daily_voucher->id }};
          var customerId = {{ $daily_voucher->customer_id }};
          
          // Load customer references
          if (customerId) {
              $.ajax({
                  url: "{{ url('petro/daily-voucher/get-customer-reference') }}/" + customerId,
                  success: function(data) {
                      $('#edit_customer_reference').html(data);
                      $('#edit_customer_reference').val('{{ $daily_voucher->vehicle_no }}').trigger('change');
                  }
              });
          }
          
          $('#order_date').datepicker({
              autoclose: true,
              format: datepicker_date_format
          });
          
          $('#edit_credit_sale_product_id').select2();
          $('#edit_credit_sale_customer_id').select2();
          $('#edit_customer_reference').select2();
          $(".select2").select2();

          function readEditNumber(selector) {
              var $field = $('#edit_daily_voucher_form').find(selector);
              if (typeof __read_number === 'function') {
                  return __read_number($field) || 0;
              }

              return parseFloat(($field.val() || '0').toString().replace(/,/g, '')) || 0;
          }

          function writeEditNumber(selector, value) {
              var $field = $('#edit_daily_voucher_form').find(selector);
              if (typeof __write_number === 'function') {
                  __write_number($field, value);
              } else {
                  $field.val((parseFloat(value) || 0).toFixed(2));
              }
          }

          function formatEditNumber(value) {
              value = parseFloat(value) || 0;
              if (typeof __number_f === 'function') {
                  return __number_f(value, false, false, typeof __currency_precision !== 'undefined' ? __currency_precision : 2);
              }

              return value.toFixed(2);
          }

          function recalculateEditCreditSaleTotals() {
              var price = readEditNumber('input[name="unit_price"]');
              var qty = readEditNumber('input[name="credit_sale_qty"]');
              var unitDiscount = readEditNumber('input[name="unit_discount"]');
              var amount = price * qty;
              var totalDiscount = unitDiscount * qty;
              var subTotal = amount - totalDiscount;

              if (subTotal < 0) {
                  subTotal = 0;
              }

              writeEditNumber('#credit_total_amount', amount);
              writeEditNumber('input[name="credit_discount_amount"]', totalDiscount);
              $('#credit_sale_total').val(subTotal);

              var $row = $('#credit_sale_table tbody tr').first();
              if ($row.length) {
                  $row.find('td:eq(8)').text(formatEditNumber(qty));
                  $row.find('td:eq(9)').text(formatEditNumber(amount));
                  $row.find('td:eq(10)').text(formatEditNumber(totalDiscount));
                  $row.find('td:eq(11)').text(formatEditNumber(subTotal));
              }

              $('.credit_sale_total').text(formatEditNumber(amount));
              $('.credit_tb_discount_total').text(formatEditNumber(totalDiscount));
              $('.credit_tbl_amount_total').text(formatEditNumber(subTotal));
          }

          $(document).on('input change', '#edit_daily_voucher_form input[name="credit_sale_qty"], #edit_daily_voucher_form input[name="unit_discount"]', function() {
              recalculateEditCreditSaleTotals();
          });
          
          // Handle form submission
          $('#edit_daily_voucher_form').on('submit', function(e) {
              e.preventDefault();
              recalculateEditCreditSaleTotals();
              
              var formData = {
                  _token: $('input[name="_token"]').val(),
                  _method: 'PUT',
                  payment_id: $('#edit_daily_voucher_form').find('input[name="payment_id"]').val(),
                  credit_sale_id: $('#edit_daily_voucher_form').find('input[name="credit_sale_id"]').val(),
                  credit_data: [{
                      customer_id: $('#edit_credit_sale_customer_id').val(),
                      product_id: $('#edit_credit_sale_product_id').val(),
                      order_number: $('#edit_daily_voucher_form').find('input[name="order_number"]').val(),
                      order_date: $('#edit_daily_voucher_form').find('input[name="order_date"]').val(),
                      customer_reference: $('#edit_customer_reference').val() || $('#customer_reference_one_time').val(),
                      price: $('#edit_daily_voucher_form').find('input[name="unit_price"]').val(),
                      unit_discount: $('#edit_daily_voucher_form').find('input[name="unit_discount"]').val() || 0,
                      qty: $('#edit_daily_voucher_form').find('input[name="credit_sale_qty"]').val(),
                      amount: $('#credit_total_amount').val(),
                      sub_total: $('#credit_sale_total').val(),
                      total_discount: $('input[name="credit_discount_amount"]').val() || 0,
                      outstanding: $('.current_outstanding').text().replace(/,/g, ''),
                      credit_limit: $('.credit_limit').text().replace(/,/g, ''),
                  }],
                  note: $('textarea[name="note"]').val()
              };
              
              $.ajax({
                  url: $(this).attr('action'),
                  method: 'POST',
                  data: formData,
                  success: function(response) {
                      if (response.success) {
                          toastr.success(response.msg);
                          if ($.fn.DataTable.isDataTable('#daily_voucher_table')) {
                              $('#daily_voucher_table').DataTable().ajax.reload(null, false);
                          }
                          if ($.fn.DataTable.isDataTable('#daily_credit_sale_table')) {
                              $('#daily_credit_sale_table').DataTable().ajax.reload(null, false);
                          }
                          if ($.fn.DataTable.isDataTable('#pump_operators_payment_summary_table')) {
                              $('#pump_operators_payment_summary_table').DataTable().ajax.reload(null, false);
                          }
                          $('.pump_modal, .view_modal').modal('hide');
                      } else {
                          toastr.error(response.msg);
                      }
                  },
                  error: function(xhr) {
                      toastr.error('{{ __("messages.something_went_wrong") }}');
                  }
              });
          });
      });
  </script>

