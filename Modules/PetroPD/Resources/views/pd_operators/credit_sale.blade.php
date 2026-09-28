<style>
    /* IS1759-03: increase the complete Credit Sale Add form and its bill grid
       by 50% without changing other payment tabs. */
    #petropd_credit_sale_entry label,
    #petropd_credit_sale_entry .form-control,
    #petropd_credit_sale_entry .select2-selection__rendered,
    #petropd_credit_sale_entry #credit_sale_table th,
    #petropd_credit_sale_entry #credit_sale_table td,
    #petropd_credit_sale_entry .text-red,
    #petropd_credit_sale_entry .modal-body,
    #petropd_credit_sale_entry .modal-body label {
        font-size: 21px !important;
    }

    #petropd_credit_sale_entry .form-control,
    #petropd_credit_sale_entry .select2-selection,
    #petropd_credit_sale_entry .select2-selection__rendered {
        min-height: 46px !important;
        line-height: 44px !important;
    }

    #petropd_credit_sale_entry #credit_sale_table th,
    #petropd_credit_sale_entry #credit_sale_table td {
        padding: 10px 8px !important;
        vertical-align: middle;
    }
</style>

<div id="petropd_credit_sale_entry">
<div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('credit_sale_customer_id', __('petropd::lang.customer') . ':') !!}
                    {!! Form::select('credit_sale_customer_id', $customers, null, [
                        'class' => 'form-control select2 credit_sale_fields',
                        'style' => 'width: 100%;',
                        'required' => true,
                        'placeholder' => __('petropd::lang.please_select'),
                    ]) !!}
                </div>
            </div>

            <!-- <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('order_number', __('petropd::lang.order_number')) !!}
                    {!! Form::text('order_number', null, [
                        'class' => 'form-control credit_sale_fields
                                                                                order_number',
                        'placeholder' => __('petropd::lang.order_number'),
                    ]) !!}
                </div>
            </div> -->

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('order_number', __('petropd::lang.order_number')) !!}

                    @if ($business->duplicate_orders_allowed === 1)
                        {{-- Duplicate orders allowed → default value 0 --}}
                        {!! Form::text('order_number', 0, [
                            'class' => 'form-control credit_sale_fields order_number',
                            'placeholder' => __('petropd::lang.order_number'),
                        ]) !!}
                    @else
                        {{-- Duplicate orders not allowed → default empty --}}
                        {!! Form::text('order_number', null, [
                            'class' => 'form-control credit_sale_fields order_number',
                            'placeholder' => __('petropd::lang.order_number'),
                        ]) !!}
                    @endif
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('order_date', __('petropd::lang.order_date')) !!}
                    {!! Form::text('order_date', null, [
                        'class' => 'form-control
                                                                                order_date',
                        'placeholder' => __('petropd::lang.order_date'),
                    ]) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('customer_reference', __('petropd::lang.select_customer_vehicle_no') . ':*') !!}
                    {!! Form::select('customer_reference', [], null, [
                        'class' => 'form-control credit_sale_fields select2
                                                                                    customer_reference',
                        'required' => true,
                        'data-msg-required' => 'Please select Customer Vehicle No before continuing.',
                        'id' => 'customer_reference',
                        'style' => 'width: 100%',
                        'placeholder' => __('petropd::lang.please_select'),
                    ]) !!}
                </div>
            </div>

            {{-- <div class="clearfix"></div> --}}


            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('credit_sale_product_id', __('petropd::lang.credit_sale_product') . ':') !!}
                    {!! Form::select('credit_sale_product_id', $products, null, [
                        'class' => 'form-control select2 credit_sale_fields',
                        'style' => 'width: 100%;',
                        'placeholder' => __('petropd::lang.please_select'),
                        'required' => true,
                    ]) !!}
                </div>
                <input type="hidden" id="manual_discount" value="{{ auth()->user()->can('manual_discount') ? 1 : 0 }}">

            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('unit_price', __('petropd::lang.unit_price')) !!}
                    {!! Form::text('unit_price', null, [
                        'class' => 'form-control input_number
                                                                            unit_price',
                        'readonly',
                        'placeholder' => __('petropd::lang.unit_price'),
                    ]) !!}
                </div>
            </div>
        </div>
        <div class="row">
            {{-- <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('unit_price', __('petropd::lang.unit_price')) !!}
                    {!! Form::text('unit_price', null, [
                        'class' => 'form-control input_number
                                                                            unit_price',
                        'readonly',
                        'placeholder' => __('petropd::lang.unit_price'),
                    ]) !!}
                </div>
            </div> --}}

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('unit_discount', __('petropd::lang.unit_discount')) !!}
                    {!! Form::text('unit_discount', null, [
                        'class' => 'form-control input_number
                                                                            unit_discount',
                        'disabled' => true,
                        'placeholder' => __('petropd::lang.unit_discount'),
                    ]) !!}
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('credit_sale_qty', __('petropd::lang.credit_sale_qty')) !!}
                    {!! Form::text('credit_sale_qty', null, [
                        'class' => 'form-control credit_sale_fields input_number
                                                                            credit_sale_qty',
                        'required' => true,
                        'placeholder' => __('petropd::lang.credit_sale_qty'),
                        'disabled' => true,
                    ]) !!}
                    <input type="hidden" name="credit_sale_qty_hidden" value="0" id="credit_sale_qty_hidden">
                </div>
            </div>

            {{-- <div class="clearfix"></div> --}}

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('credit_total_amount', __('petropd::lang.amount') . __('petropd::lang.before_discount_cr')) !!}

                    {!! Form::text('credit_total_amount', null, [
                        'id' => 'credit_total_amount',
                        'class' => 'form-control credit_sale_fields cust_input_number
                                                                            credit_total_amount',
                        'required',
                        'disabled' => true,
                        'placeholder' => __('petropd::lang.credit_total_amount'),
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
                        'placeholder' => __('petropd::lang.amount'),
                    ]) !!}
                    <input type="hidden" name="credit_sale_amount_hidden" value="0"
                        id="credit_sale_amount_hidden">
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('credit_discount_amount', __('petropd::lang.credit_discount_amount')) !!}
                    {!! Form::text('credit_discount_amount', null, [
                        'class' => 'form-control credit_sale_fields cust_input_number
                                                                            credit_discount_amount',
                        'required',
                        'disabled' => true,
                        'placeholder' => __('petropd::lang.credit_discount_amount'),
                    ]) !!}
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('customer_reference_one_time', __('petropd::lang.enter_customer_vehicle_no')) !!}
                    {!! Form::text('customer_reference', null, [
                        'class' => 'form-control
                                                                            customer_reference_one_time',
                        'id' => 'customer_reference_one_time',
                        'placeholder' => __('petropd::lang.enter_customer_vehicle_no'),
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
                @lang('petropd::lang.current_outstanding'): <span class="current_outstanding"></span></div>
            <div class="col-md-4 text-red" style="font-size: 18px; font-weight: bold; ">
                @lang('petropd::lang.credit_limit'): <span class="credit_limit"></span></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <table class="table table-bordered table-striped" id="credit_sale_table">
            <thead>
                <tr>
                    <th>@lang('petropd::lang.cusotmer_name')</th>
                    <th>@lang('petropd::lang.outstanding')</th>
                    <th>@lang('petropd::lang.limit')</th>
                    <th>@lang('petropd::lang.order_no')</th>
                    <th>@lang('petropd::lang.order_date')</th>
                    <th>Vehicle No</th>
                    <th>@lang('petropd::lang.product')</th>
                    <th>@lang('petropd::lang.unit_price')</th>
                    <th>@lang('petropd::lang.qty')</th>
                    <th>@lang('petropd::lang.sub_total')</th>
                    <th>@lang('petropd::lang.discount_total')</th>
                    <th>@lang('petropd::lang.total')</th>
                    <th>@lang('lang_v1.note') </th>
                    <th>@lang('petropd::lang.action')</th>
                </tr>
            </thead>
            <tbody>

            </tbody>

            <tfoot>
                <tr>
                    <td colspan="9" style="text-align: right; font-weight: bold;">@lang('petropd::lang.total') :</td>
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
            style="margin-left: 10px; margin-top: 23px;">@lang('petropd::lang.finalize')</button>
    </div>


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
