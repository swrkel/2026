<div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('credit_sale_customer_id', __('petrogeneral::lang.customer') . ':') !!}
                    {!! Form::select('credit_sale_customer_id', $customers, null, [
                        'class' => 'form-control select2 credit_sale_fields',
                        'style' => 'width: 100%;',
                        'required' => true,
                        'placeholder' => __('petrogeneral::lang.please_select'),
                    ]) !!}
                </div>
            </div>

            <!-- <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('order_number', __('petrogeneral::lang.order_number')) !!}
                    {!! Form::text('order_number', null, [
                        'class' => 'form-control credit_sale_fields
                                                                                order_number',
                        'placeholder' => __('petrogeneral::lang.order_number'),
                    ]) !!}
                </div>
            </div> -->

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('order_number', __('petrogeneral::lang.order_number')) !!}

                    @if ($business->duplicate_orders_allowed === 1)
                        {{-- Duplicate orders allowed → default value 0 --}}
                        {!! Form::text('order_number', 0, [
                            'class' => 'form-control credit_sale_fields order_number',
                            'placeholder' => __('petrogeneral::lang.order_number'),
                        ]) !!}
                    @else
                        {{-- Duplicate orders not allowed → default empty --}}
                        {!! Form::text('order_number', null, [
                            'class' => 'form-control credit_sale_fields order_number',
                            'placeholder' => __('petrogeneral::lang.order_number'),
                        ]) !!}
                    @endif
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('order_date', __('petrogeneral::lang.order_date')) !!}
                    {!! Form::text('order_date', null, [
                        'class' => 'form-control
                                                                                order_date',
                        'placeholder' => __('petrogeneral::lang.order_date'),
                    ]) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('customer_reference', __('petrogeneral::lang.select_customer_vehicle_no')) !!}
                    {!! Form::select('customer_reference', [], null, [
                        'class' => 'form-control credit_sale_fields select2
                                                                                    customer_reference',
                        'required',
                        'id' => 'customer_reference',
                        'style' => 'width: 100%',
                        'placeholder' => __('petrogeneral::lang.please_select'),
                    ]) !!}
                </div>
            </div>

            {{-- <div class="clearfix"></div> --}}


            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('credit_sale_product_id', __('petrogeneral::lang.credit_sale_product') . ':') !!}
                    {!! Form::select('credit_sale_product_id', $products, null, [
                        'class' => 'form-control select2 credit_sale_fields',
                        'style' => 'width: 100%;',
                        'placeholder' => __('petrogeneral::lang.please_select'),
                        'required' => true,
                    ]) !!}
                </div>
                <input type="hidden" id="manual_discount" value="{{ auth()->user()->can('manual_discount') ? 1 : 0 }}">

            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('unit_price', __('petrogeneral::lang.unit_price')) !!}
                    {!! Form::text('unit_price', null, [
                        'class' => 'form-control input_number
                                                                            unit_price',
                        'readonly',
                        'placeholder' => __('petrogeneral::lang.unit_price'),
                    ]) !!}
                </div>
            </div>
        </div>
        <div class="row">
            {{-- <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('unit_price', __('petrogeneral::lang.unit_price')) !!}
                    {!! Form::text('unit_price', null, [
                        'class' => 'form-control input_number
                                                                            unit_price',
                        'readonly',
                        'placeholder' => __('petrogeneral::lang.unit_price'),
                    ]) !!}
                </div>
            </div> --}}

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('unit_discount', __('petrogeneral::lang.unit_discount')) !!}
                    {!! Form::text('unit_discount', null, [
                        'class' => 'form-control input_number
                                                                            unit_discount',
                        'disabled' => true,
                        'placeholder' => __('petrogeneral::lang.unit_discount'),
                    ]) !!}
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('credit_sale_qty', __('petrogeneral::lang.credit_sale_qty')) !!}
                    {!! Form::text('credit_sale_qty', null, [
'class' => 'form-control credit_sale_fields credit_sale_qty',

                        'required' => true,
                        'placeholder' => __('petrogeneral::lang.credit_sale_qty'),
                        'disabled' => true,
                    ]) !!}
                    <input type="hidden" name="credit_sale_qty_hidden" value="0" id="credit_sale_qty_hidden">
                </div>
            </div>

            {{-- <div class="clearfix"></div> --}}

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('credit_total_amount', __('petrogeneral::lang.amount') . __('petrogeneral::lang.before_discount_cr')) !!}

                    {!! Form::text('credit_total_amount', null, [
                        'id' => 'credit_total_amount',
                        'class' => 'form-control credit_sale_fields cust_input_number
                                                                            credit_total_amount',
                        'required',
                        'disabled' => true,
                        'placeholder' => __('petrogeneral::lang.credit_total_amount'),
                    ]) !!}
                </div>
            </div>


            <div class="col-md-2  hidden">
                <div class="form-group">
                    {!! Form::text('credit_sale_amount', null, [
                        'id' => 'credit_sale_amount',
                        'class' => 'form-control credit_sale_fields cust_input_number
                                                                            credit_sale_amount',
                        'required',
                        'disabled' => true,
                        'placeholder' => __('petrogeneral::lang.amount'),
                    ]) !!}
                    <input type="hidden" name="credit_sale_amount_hidden" value="0"
                        id="credit_sale_amount_hidden">
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('credit_discount_amount', __('petrogeneral::lang.credit_discount_amount')) !!}
                    {!! Form::text('credit_discount_amount', null, [
                        'class' => 'form-control credit_sale_fields cust_input_number
                                                                            credit_discount_amount',
                        'required',
                        'disabled' => true,
                        'placeholder' => __('petrogeneral::lang.credit_discount_amount'),
                    ]) !!}
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('customer_reference_one_time', __('petrogeneral::lang.enter_customer_vehicle_no')) !!}
                    {!! Form::text('customer_reference', null, [
                        'class' => 'form-control
                                                                            customer_reference_one_time',
                        'id' => 'customer_reference_one_time',
                        'placeholder' => __('petrogeneral::lang.enter_customer_vehicle_no'),
                    ]) !!}
                </div>
            </div>
        </div>
        <div class="row">

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('credit_note', __('lang_v1.payment_note') . ':') !!}
                    {!! Form::textarea('credit_note', null, ['class' => 'form-control cash_fields', 'rows' => 3]) !!}
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
                    <th>@lang('petrogeneral::lang.cusotmer_name')</th>
                    <th>@lang('petrogeneral::lang.outstanding')</th>
                    <th>@lang('petrogeneral::lang.limit')</th>
                    <th>@lang('petrogeneral::lang.order_no')</th>
                    <th>@lang('petrogeneral::lang.order_date')</th>
                    <th>Vehicle No</th>
                    <th>@lang('petrogeneral::lang.product')</th>
                    <th>@lang('petrogeneral::lang.unit_price')</th>
                    <th>@lang('petrogeneral::lang.qty')</th>
                    <th>@lang('petrogeneral::lang.sub_total')</th>
                    <th>@lang('petrogeneral::lang.discount_total')</th>
                    <th>@lang('petrogeneral::lang.total')</th>
                    <th>@lang('lang_v1.note') </th>
                    <th>@lang('petrogeneral::lang.action')</th>
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

    <div class="col-md-3 pull-right">
        <button type="button" class="btn btn-danger pull-right credit_sale_finalize_print"
            style="margin-left: 10px; margin-top: 23px;">Print & Save</button>
        <button type="button" class="btn btn-danger pull-right credit_sale_finalize"
            style="margin-left: 10px; margin-top: 23px;">@lang('petrogeneral::lang.finalize')</button>
    </div>


</div>

<!-- Print Copy Selection Modal -->
<div class="modal fade" id="print_copy_selection_modal" role="dialog" 
    aria-labelledby="printCopySelectionLabel" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header">
          <h4 class="modal-title" id="printCopySelectionLabel">Select Print Options</h4>
        </div>
        <!-- Modal Body -->
        <div class="modal-body">
          <div class="form-group">
            <label>Choose which copy to print:</label>
            <div class="radio">
              <label>
                <input type="radio" name="print_copy_option" value="customer" checked>
                Customer Copy (Default)
              </label>
            </div>
            <div class="radio">
              <label>
                <input type="radio" name="print_copy_option" value="both">
                Two Copies (Customer & Merchant)
              </label>
            </div>
          </div>
        </div>
        <!-- Modal Footer -->
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" id="confirm_print_btn">Print</button>
        </div>
      </div>
    </div>
</div>
