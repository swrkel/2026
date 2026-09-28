{{-- <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
  
     
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">@lang( 'petrogeneral::lang.credit_sale' )</h4>
      </div>
  
      <div class="modal-body">
          <div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('cr_pump_operator_id', "Pump Operator".':') !!}
                    {!! Form::select('cr_pump_operator_id', $pump_operators, null, ['class' => 'form-control select2',
                    'style' => 'width: 100%;']); !!}
                </div>
            </div>
            <div class="clearfix"></div>
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('credit_sale_customer_id', __('petrogeneral::lang.customer').':') !!}
                    {!! Form::select('credit_sale_customer_id', $customers, null, ['class' => 'form-control select2',
                    'style' => 'width: 100%;']); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('order_number', __( 'petrogeneral::lang.order_number' ) ) !!}
                    {!! Form::text('order_number', null, ['class' => 'form-control credit_sale_fields
                    order_number',
                    'placeholder' => __(
                    'petrogeneral::lang.order_number' ) ]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('order_date', __( 'petrogeneral::lang.order_date' ) ) !!}
                    {!! Form::text('order_date', null, ['class' => 'form-control
                    order_date',
                    'placeholder' => __(
                    'petrogeneral::lang.order_date' ) ]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('customer_reference', __( 'petrogeneral::lang.select_customer_vehicle_no' ) ) !!}
                   {!! Form::select('customer_reference', [], null, ['class' => 'form-control credit_sale_fields select2
                        customer_reference', 'required', 'id' => 'customer_reference', 'style' => 'width: 100%',
                        'placeholder' => __(
                        'petrogeneral::lang.please_select' ) ]); !!}
                </div>
            </div>
            
            <div class="clearfix"></div>
            
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('credit_sale_product_id', __('petrogeneral::lang.credit_sale_product').':') !!}
                    {!! Form::select('credit_sale_product_id', $products, null, ['class' => 'form-control select2',
                    'style' => 'width: 100%;',
                    'placeholder' => __(
                    'petrogeneral::lang.please_select' )]); !!}
                </div>
                <input type="hidden" id="manual_discount" value="{{auth()->user()->can('manual_discount') ? 1 : 0}}">

            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('unit_price', __( 'petrogeneral::lang.unit_price' ) ) !!}
                    {!! Form::text('unit_price', null, ['class' => 'form-control input_number
                    unit_price', 'readonly',
                    'placeholder' => __(
                    'petrogeneral::lang.unit_price' ) ]); !!}
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('unit_discount', __( 'petrogeneral::lang.unit_discount' ) ) !!}
                    {!! Form::text('unit_discount', null, ['class' => 'form-control input_number
                    unit_discount', 'disabled' => true,
                    'placeholder' => __(
                    'petrogeneral::lang.unit_discount' ) ]); !!}
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('credit_sale_qty', __( 'petrogeneral::lang.credit_sale_qty' ) ) !!}
                    {!! Form::text('credit_sale_qty', null, ['class' => 'form-control credit_sale_fields input_number
                    credit_sale_qty',
                    'placeholder' => __(
                    'petrogeneral::lang.credit_sale_qty' ), 'disabled' => true ]); !!}
                    <input type="hidden" name="credit_sale_qty_hidden" value="0" id="credit_sale_qty_hidden" >
                </div>
            </div>
            
            <div class="clearfix"></div>
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('credit_total_amount', __( 'petrogeneral::lang.amount' ).__( 'petrogeneral::lang.before_discount_cr' ) ) !!} 
                    
                    {!! Form::text('credit_total_amount', null, ['id' => 'credit_total_amount', 'class' => 'form-control credit_sale_fields cust_input_number
                    credit_total_amount', 'required', 'disabled' => true,
                    'placeholder' => __(
                    'petrogeneral::lang.credit_total_amount' ) ]); !!}
                </div>
            </div>
            
            
            <div class="col-md-3  hidden">
                <div class="form-group">
                    {!! Form::text('credit_sale_amount', null, ['id' => 'credit_sale_amount','class' => 'form-control credit_sale_fields cust_input_number
                    credit_sale_amount', 'required', 'disabled' => true,
                    'placeholder' => __(
                    'petrogeneral::lang.amount' ) ]); !!}
                    <input type="hidden" name="credit_sale_amount_hidden" value="0" id="credit_sale_amount_hidden" >
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('credit_discount_amount', __( 'petrogeneral::lang.credit_discount_amount' ) ) !!}
                    {!! Form::text('credit_discount_amount', null, ['class' => 'form-control credit_sale_fields cust_input_number
                    credit_discount_amount', 'required', 'disabled' => true,
                    'placeholder' => __(
                    'petrogeneral::lang.credit_discount_amount' ) ]); !!}
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('customer_reference_one_time', __( 'petrogeneral::lang.enter_customer_vehicle_no' ) ) !!}
                    {!! Form::text('customer_reference', null, ['class' => 'form-control
                    customer_reference_one_time', 'id' => 'customer_reference_one_time',
                    'placeholder' => __(
                    'petrogeneral::lang.enter_customer_vehicle_no' ) ]); !!}
                </div>
            </div>
            
            
            <div class="col-md-4">
                <div class="form-group">
                  {!! Form::label("credit_note", __('lang_v1.payment_note') . ':') !!}
                  {!! Form::textarea("credit_note", null, ['class' => 'form-control cash_fields', 'rows' => 3]); !!}
                </div>
            </div>
            <div class="col-md-2 pull-right">
                <button type="button" class="btn btn-primary pull-right credit_sale_add"
                    style="margin-top: 23px;">@lang('messages.add')</button>
            </div>
            <div class="clearfix"></div>

            <div class="col-md-4 text-red" style="font-size: 18px; font-weight: bold; ">
                @lang('petrogeneral::lang.current_outstanding'): <span class="current_outstanding"></span></div>
            <div class="col-md-4 text-red" style="font-size: 18px; font-weight: bold; ">
                @lang('petrogeneral::lang.credit_limit'): <span class="credit_limit"></span></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <table class="table table-bordered table-striped" id="credit_sale_table">
            <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.cusotmer_name' )</th>
                    <th>@lang('petrogeneral::lang.outstanding' )</th>
                    <th>@lang('petrogeneral::lang.limit' )</th>
                    <th>@lang('petrogeneral::lang.order_no' )</th>
                    <th>@lang('petrogeneral::lang.order_date' )</th>
                    <th>@lang('petrogeneral::lang.customer_reference' )</th>
                    <th>@lang('petrogeneral::lang.product' )</th>
                    <th>@lang('petrogeneral::lang.unit_price' )</th>
                    <th>@lang('petrogeneral::lang.qty' )</th>
                    <th>@lang('petrogeneral::lang.sub_total' )</th>
                    <th>@lang('petrogeneral::lang.discount_total' )</th>
                    <th>@lang('petrogeneral::lang.total' )</th>
                    <th>@lang('lang_v1.note') </th>
                    <th>@lang('petrogeneral::lang.action' )</th>
                </tr>
            </thead>
            <tbody>
               
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="9" style="text-align: right; font-weight: bold;">@lang('petrogeneral::lang.total') :</td>
                    <td style="text-align: left; font-weight: bold;" class="credit_sale_total">
                        0.00</td>
                    <td style="text-align: left; font-weight: bold;" class="credit_tb_discount_total">
                        0.00</td>
                    <td style="text-align: left; font-weight: bold;" class="credit_tbl_amount_total">
                        0.00</td>
                </tr>
                <input type="hidden" value="0" name="credit_sale_total" id="credit_sale_total">
            </tfoot>
        </table>
    </div>
    
     <div class="col-md-2 pull-right">
        <button type="button" class="btn btn-danger pull-right credit_sale_finalize"
            style="margin-top: 23px;">@lang('petrogeneral::lang.finalize')</button>
    </div>
            
            
</div>

      </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
  
  <script>
      $("#credit_sale_customer_id").val($("#credit_sale_customer_id option:eq(0)").val()).trigger('change');
      $('#order_date').datepicker("setDate", new Date());
      $('#credit_sale_product_id').select2();
      $('#credit_sale_customer_id').select2();
      $('#customer_reference').select2();
      $(".credit_sale_finalize").hide();
      $(".select2").select2();
  </script> --}}
  <style>
    .modal-body .form-group {
        margin-bottom: 6px;
    }
    .modal-body label {
        margin-bottom: 2px;
        font-size: 12px;
        font-weight: 600;
    }
</style>
<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">

        {{-- HEADER --}}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">@lang('petrogeneral::lang.credit_sale')</h4>
        </div>

        {{-- BODY --}}
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">

                    {{-- ================= ROW 0 ================= --}}
                    {{-- Pump Operator --}}
                    <div class="row">
                        <div class="col-md-3">
                            {!! Form::label('cr_pump_operator_id', __('Pump Operator')) !!}
                            {!! Form::select('cr_pump_operator_id', $pump_operators, null, ['class'=>'form-control select2']) !!}
                        </div>
                    </div>

                    {{-- ================= ROW 1 ================= --}}
                    {{-- Customer | Order No | Order Date | Vehicle No --}}
                    <div class="row">
                        <div class="col-md-2">
                            {!! Form::label('credit_sale_customer_id', __('petrogeneral::lang.customer')) !!}
                            {!! Form::select('credit_sale_customer_id', $customers, null, ['class'=>'form-control select2']) !!}
                        </div>

                        <div class="col-md-2">
                            {!! Form::label('order_number', __('petrogeneral::lang.order_number')) !!}
                            {!! Form::text('order_number', null, ['class'=>'form-control order_number']) !!}
                        </div>

                        <div class="col-md-2">
                            {!! Form::label('order_date', __('petrogeneral::lang.order_date')) !!}
                            {!! Form::text('order_date', null, ['class'=>'form-control order_date']) !!}
                        </div>

                        <div class="col-md-2">
                            {!! Form::label('customer_reference', __('petrogeneral::lang.select_customer_vehicle_no')) !!}
                            {!! Form::select('customer_reference', [], null, ['class'=>'form-control select2','id'=>'customer_reference']) !!}
                        </div>

                         <div class="col-md-2">
                            {!! Form::label('customer_reference_one_time', __('petrogeneral::lang.enter_customer_vehicle_no')) !!}
                            {!! Form::text('customer_reference', null, ['class'=>'form-control','id'=>'customer_reference_one_time']) !!}
                        </div>
                        <div class="col-md-2">
                            {!! Form::label('credit_sale_product_id', __('petrogeneral::lang.credit_sale_product')) !!}
                            {!! Form::select('credit_sale_product_id', $products, null, ['class'=>'form-control select2']) !!}
                            <input type="hidden" id="manual_discount" value="{{ auth()->user()->can('manual_discount') ? 1 : 0 }}">
                        </div>
                    </div>

                    {{-- ================= ROW 2 ================= --}}
                    {{-- Product | Unit Price | Unit Discount | Qty --}}
                    <div class="row">
                        {{-- <div class="col-md-3">
                            {!! Form::label('credit_sale_product_id', __('petrogeneral::lang.credit_sale_product')) !!}
                            {!! Form::select('credit_sale_product_id', $products, null, ['class'=>'form-control select2']) !!}
                            <input type="hidden" id="manual_discount" value="{{ auth()->user()->can('manual_discount') ? 1 : 0 }}">
                        </div> --}}

                        <div class="col-md-2">
                            {!! Form::label('unit_price', __('petrogeneral::lang.unit_price')) !!}
                            {!! Form::text('unit_price', null, ['class'=>'form-control','readonly']) !!}
                        </div>

                        <div class="col-md-2">
                            {!! Form::label('unit_discount', __('petrogeneral::lang.unit_discount')) !!}
                            {!! Form::text('unit_discount', null, ['class'=>'form-control','disabled']) !!}
                        </div>

                        <div class="col-md-2">
                            {!! Form::label('credit_sale_qty', __('petrogeneral::lang.qty')) !!}
                            {!! Form::text('credit_sale_qty', null, ['class'=>'form-control','disabled']) !!}
                            <input type="hidden" id="credit_sale_qty_hidden" value="0">
                        </div>
                    {{-- </div> --}}

                    {{-- ================= ROW 3 ================= --}}
                    {{-- Amount | Discount | Vehicle No --}}
                    {{-- <div class="row"> --}}
                        <div class="col-md-2">
                            {!! Form::label('credit_total_amount', __('petrogeneral::lang.amount_before_discount')) !!}
                            {!! Form::text('credit_total_amount', null, ['class'=>'form-control','disabled']) !!}
                        </div>

                        <div class="col-md-2">
                            {!! Form::label('credit_discount_amount', __('petrogeneral::lang.credit_discount_amount')) !!}
                            {!! Form::text('credit_discount_amount', null, ['class'=>'form-control','disabled']) !!}
                        </div>

                        {{-- <div class="col-md-4">
                            {!! Form::label('customer_reference_one_time', __('petrogeneral::lang.enter_customer_vehicle_no')) !!}
                            {!! Form::text('customer_reference', null, ['class'=>'form-control','id'=>'customer_reference_one_time']) !!}
                        </div> --}}
                    </div>

                    {{-- ================= ROW 4 ================= --}}
                    {{-- Note | Add --}}
                    <div class="row">
                        <div class="col-md-8">
                            {!! Form::label('credit_note', __('lang_v1.payment_note')) !!}
                            {!! Form::textarea('credit_note', null, ['class'=>'form-control','rows'=>2]) !!}
                        </div>

                        <div class="col-md-4 text-right" style="padding-top:22px;">
                            <button type="button" class="btn btn-primary credit_sale_add">
                                @lang('messages.add')
                            </button>
                        </div>
                    </div>

                    {{-- ================= INFO ================= --}}
                    <div class="row" style="margin-top:6px;">
                        <div class="col-md-4 text-red"><b>@lang('petrogeneral::lang.current_outstanding'):</b> <span class="current_outstanding"></span></div>
                        <div class="col-md-4 text-red"><b>@lang('petrogeneral::lang.credit_limit'):</b> <span class="credit_limit"></span></div>
                    </div>

                </div>
            </div>

            {{-- ================= TABLE ================= --}}
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered table-striped" id="credit_sale_table">
                        <thead>
                            <tr>
                                <th>@lang('petrogeneral::lang.customer')</th>
                                <th>@lang('petrogeneral::lang.outstanding')</th>
                                <th>@lang('petrogeneral::lang.limit')</th>
                                <th>@lang('petrogeneral::lang.order_no')</th>
                                <th>@lang('petrogeneral::lang.order_date')</th>
                                <th>@lang('petrogeneral::lang.vehicle')</th>
                                <th>@lang('petrogeneral::lang.product')</th>
                                <th>@lang('petrogeneral::lang.unit_price')</th>
                                <th>@lang('petrogeneral::lang.qty')</th>
                                <th>@lang('petrogeneral::lang.sub_total')</th>
                                <th>@lang('petrogeneral::lang.discount')</th>
                                <th>@lang('petrogeneral::lang.total')</th>
                                <th>@lang('lang_v1.note')</th>
                                <th>@lang('petrogeneral::lang.action')</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="9" class="text-right"><b>@lang('petrogeneral::lang.total') :</b></td>
                                <td class="credit_sale_total">0.00</td>
                                <td class="credit_tb_discount_total">0.00</td>
                                <td class="credit_tbl_amount_total">0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="col-md-2 pull-right">
                    <button type="button" class="btn btn-danger credit_sale_finalize" style="margin-top:10px;">
                        @lang('petrogeneral::lang.finalize')
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
<script>
      $("#credit_sale_customer_id").val($("#credit_sale_customer_id option:eq(0)").val()).trigger('change');
      $('#order_date').datepicker("setDate", new Date());
      $('#credit_sale_product_id').select2();
      $('#credit_sale_customer_id').select2();
      $('#customer_reference').select2();
      $(".credit_sale_finalize").hide();
      $(".select2").select2();
  </script>