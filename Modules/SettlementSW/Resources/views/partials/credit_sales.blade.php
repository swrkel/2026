        <div class="card-custom">

          <div class="card-header">Credit Sales</div>

          <div class="card-body">

            <div style="margin-bottom:1rem;">

              @if (auth()->user()->can('customer.create'))

              <button type="button" data-href="{{action('ContactController@create',['type' => 'customer','is_credit' => true])}}"

              data-container=".contact_modal" class="btn-primary-custom btn-modal">{{__('SettlementSW::lang.add_new_customer')}}</button>



              @endif

            </div>

            <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">

            </div>

              <form id="credit-sales-form" class="form-row-custom align-items-end">

              <div class="form-group-custom ">

                {!! Form::label('sw_credit_sale_customer_iddelete_expense_payment', __('SettlementSW::lang.customer').':') !!}

                {!! Form::select('sw_credit_sale_customer_iddelete_expense_payment', !empty($only_walkin) ? [] : $credit_customers,

                  !empty($temp_data->credit_sale_customer_id) ? $temp_data->credit_sale_customer_id : null,

                  ['class' => 'form-control select2', 'style' => 'width: 100%;']) !!}

              </div>



              <div class="form-group-custom ">

                {!! Form::label('sw_order_number', __('SettlementSW::lang.order_number')) !!}

                {!! Form::text('sw_order_number', !empty($temp_data->order_number) ? $temp_data->order_number : null,

                  ['class' => 'form-control credit_sale_fields sw_order_number',

                  'placeholder' => __('SettlementSW::lang.order_number')]) !!}

              </div>



              <div class="form-group-custom ">

                {!! Form::label('sw_order_date', __('SettlementSW::lang.order_date')) !!}

                {!! Form::text('sw_order_date', !empty($temp_data->order_date) ? $temp_data->order_date : null,

                  ['class' => 'form-control sw_order_date',

                  'placeholder' => __('SettlementSW::lang.order_date')]) !!}

              </div>



              <div class="form-group-custom ">

                {!! Form::label('sw_customer_reference', __('SettlementSW::lang.select_customer_vehicle_no')) !!}

                {!! Form::select('sw_customer_reference', [], !empty($temp_data->customer_reference) ? $temp_data->customer_reference : null,

                  ['class' => 'form-control credit_sale_fields select2 sw_customer_reference',

                  'required', 'id' => 'sw_customer_reference', 'style' => 'width: 100%',

                  'placeholder' => __('SettlementSW::lang.please_select')]) !!}

              </div>



              <div class="form-group-custom ">

                {!! Form::label('sw_credit_sale_product_id', __('SettlementSW::lang.credit_sale_product').':') !!}

                {!! Form::select('sw_credit_sale_product_id', $products,

                  !empty($temp_data->credit_sale_product_id) ? $temp_data->credit_sale_product_id : null,

                  ['class' => 'form-control select2', 'style' => 'width: 100%',

                  'placeholder' => __('SettlementSW::lang.please_select')]) !!}

              </div>



              <input type="hidden" id="manual_discount" value="{{ auth()->user()->can('manual_discount') ? 1 : 0 }}">

              <input type="hidden" id="is_modal" value="1">



              <div class="form-group-custom ">

                {!! Form::label('sw_credit_sale_qty', __('SettlementSW::lang.credit_sale_qty')) !!}

                {!! Form::text('sw_credit_sale_qty', !empty($temp_data->credit_sale_qty) ? $temp_data->credit_sale_qty : null,

                  ['class' => 'form-control credit_sale_fields input_number sw_credit_sale_qty',

                  'placeholder' => __('SettlementSW::lang.credit_sale_qty')]) !!}

                <input type="hidden" name="credit_sale_qty_hidden" value="0" id="credit_sale_qty_hidden">

              </div>



              <div class="form-group-custom ">

                {!! Form::label('sw_unit_price', __('SettlementSW::lang.unit_price')) !!}

                {!! Form::text('sw_unit_price', !empty($temp_data->unit_price) ? $temp_data->unit_price : null,

                  ['class' => 'form-control input_number sw_unit_price',

                  'readonly', 'placeholder' => __('SettlementSW::lang.unit_price')]) !!}

              </div>



              <div class="form-group-custom ">

                {!! Form::label('sw_unit_discount', __('SettlementSW::lang.unit_discount')) !!}

                {!! Form::text('sw_unit_discount', !empty($temp_data->unit_discount) ? $temp_data->unit_discount : null,

                  ['class' => 'form-control input_number sw_unit_discount',

                  'disabled' => true, 'placeholder' => __('SettlementSW::lang.unit_discount')]) !!}

              </div>



              <div class="form-group-custom ">

                {!! Form::label('sw_credit_total_amount', __('SettlementSW::lang.amount') . __('SettlementSW::lang.before_discount_cr')) !!}

                {!! Form::text('sw_credit_total_amount', !empty($temp_data->credit_total_amount) ? $temp_data->credit_total_amount : null,

                  ['id' => 'sw_credit_total_amount', 'class' => 'form-control credit_sale_fields cust_input_number sw_credit_total_amount',

                  'required', 'disabled' => true, 'placeholder' => __('SettlementSW::lang.credit_total_amount')]) !!}

              </div>



              <div class="form-group-custom ">

                {!! Form::label('sw_credit_sale_amount', __('SettlementSW::lang.amount') . __('SettlementSW::lang.after_discount_cr')) !!}

                {!! Form::text('sw_credit_sale_amount', !empty($temp_data->credit_sale_amount) ? $temp_data->credit_sale_amount : null,

                  ['id' => 'sw_credit_sale_amount', 'class' => 'form-control credit_sale_fields cust_input_number sw_credit_sale_amount',

                  'required', 'disabled' => true, 'placeholder' => __('SettlementSW::lang.amount')]) !!}

                <input type="hidden" name="credit_sale_amount_hidden" value="0" id="credit_sale_amount_hidden">

              </div>

              

              <div class="form-group-custom " id="customer-reference-one-time-wrapper">

                <label for="customer_reference_one_time">

                  {{__('SettlementSW::lang.enter_customer_vehicle_no')}}

                </label>

                <input type="text" class="form-control customer_reference_one_time"

                      id="customer_reference_one_time"

                      name="sw_customer_reference"

                      placeholder="{{__('SettlementSW::lang.enter_customer_vehicle_no')}}">

              </div>

                  

              <div class="form-group-custom" style="display:flex;align-items:flex-end;justify-content:bottom;">

                  <button type="button" class="btn-primary-custom" id="noteButton">+ Note</button><br>

                  <div id="noteDisplay" style="margin-left: 5px; color: #333;"></div>

              </div>



              <div id="noteModal" style="display: none; position: fixed; top: 20%; left: 50%; transform: translateX(-50%); background: white; padding: 20px; border: 1px solid #aaa; border-radius: 8px; z-index: 1000; width: 300px;">

                  <h4>{{ __('SettlementSW::lang.Add_note') }} </h4>

                  <div class="form-group">

                      {!! Form::textarea('note_temp', null, [

                          'class' => 'form-control',

                          'id' => 'noteInput',

                          'rows' => 4

                      ]) !!}

                  </div>

                  <button type="button" class="btn btn-primary" style="padding: 5px; margin-right: 10px" id="saveNote">{{ __('SettlementSW::lang.Save') }}</button>

                  <button type="button" class="btn btn-light" style="padding: 5px; margin-right: 10px" id="closeNote">{{ __('SettlementSW::lang.Close') }}</button>

              </div>



              {{-- Overlay  modal --}}

              <div id="noteOverlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.3); z-index: 999;"></div>

              {!! Form::hidden('note', null, ['id' => 'noteHidden']) !!}



              <div class="form-group " style="display:flex;align-items:flex-end;justify-content:center;">

                <button type="button" class="btn-add" id="sw_add-credit-sale">

                  <i class="fa fa-plus"></i>

                </button>

              </div>

            </form>

            {{-- Credit Sales Data Table --}}

              <table class="table table-bordered mt-4" id="credit-sales-table">

                <thead>

                  <tr>

                    <th>Customer Name</th>

                    <th>Outstanding</th>

                    <th>Limit</th>

                    <th>Order No</th>

                    <th>Order Date</th>

                    <th>Customer Reference</th>

                    <th>Product</th>

                    <th>Unit Price</th>

                    <th>Qty</th>

                    <th>Sub Total</th>

                    <th>Discount Total</th>

                    <th>Total</th>

                    <th>Note</th>

                    <th>Action</th>

                  </tr>

                </thead>

                <tbody id="credit-sales-tbody">

                  {{-- Dynamic rows will be inserted here via JavaScript --}}

                </tbody>

                <tfoot>

                  <tr>

                    <td colspan="9" class="text-right"><strong>Total:</strong></td>

                    <td id="credit-sales-subtotal">0.00</td>

                    <td id="credit-sales-discount-total">0.00</td>

                    <td id="credit-sales-total">0.00</td>

                    <td colspan="2"></td>

                  </tr>

                </tfoot>

              </table>

            <div class="summary-text total-credit-sales-value hidden">Total Credit Sales: <span id="credit-sales-total">0.00</span></div>

          </div>

        </div>