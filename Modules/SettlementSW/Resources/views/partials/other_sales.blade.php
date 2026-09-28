        <div class="card-custom">

          <div class="card-header">Other Sales</div>

          <div class="card-body">

            <form id="other-sales-form" class="form-row-custom align-items-end">

              <div class="d-flex" style="width: 100%;">

                <div class="" style="flex: 0 0 16%; margin: auto;">

                  {!! Form::label('store', __('settlementsw::lang.store').':') !!}

                  {!! Form::select('store_id', $stores, !empty($temp_data->store_id) ? $temp_data->store_id : $default_store, [

                      'class' => 'form-control check_pumper select2',

                      'id' => 'store_id',

                      'placeholder' => __('settlementsw::lang.please_select')

                  ]) !!}

                </div>

  

                <div class=" " style="flex: 0 0 30%; margin: auto;">

                  {!! Form::label('item', __('SettlementSW::lang.Select_Product').':') !!}

                  {!! Form::select('item', $items, !empty($temp_data->item) ? $temp_data->item : null, [

                      'class' => 'form-control other_sale_fields check_pumper select2',

                      'placeholder' => __('settlementsw::lang.please_select')

                  ]) !!}

                </div>

  

                <div class=" " style="flex: 0 0 13%; margin: auto;">

                  {!! Form::label('balance_stock', __('settlementsw::lang.balance_stock')) !!}

                  {!! Form::text('balance_stock', null, [

                      'class' => 'form-control other_sale_fields check_pumper input_number balance_stock',

                      'required',

                      'id' => 'balance_stock',

                      'readonly',

                      'placeholder' => __('settlementsw::lang.balance_stock')

                  ]) !!}

                </div>

  

                <div class=" " style="flex: 0 0 13%; margin: auto;">

                  {!! Form::label('other_sale_price', __('settlementsw::lang.price')) !!}

                  {!! Form::text('other_sale_price', !empty($temp_data->other_sale_price) ? $temp_data->other_sale_price : null, [

                      'class' => 'form-control other_sale_fields check_pumper input_number other_sale_price',

                      'required',

                      'readonly',

                      'placeholder' => __('settlementsw::lang.price')

                  ]) !!}

                </div>

  

                <div class=" " style="flex: 0 0 13%; margin: auto;">

                  {!! Form::label('other_sale_qty', __('settlementsw::lang.qty')) !!}

                  {!! Form::text('other_sale_qty', !empty($temp_data->other_sale_qty) ? $temp_data->other_sale_qty : null, [

                      'class' => 'form-control other_sale_fields check_pumper qty input_number',

                      'required',

                      'id' => 'other_sale_qty',

                      'placeholder' => __('settlementsw::lang.qty')

                  ]) !!}

                </div>

  

                <div class=" " style="flex: 0 0 11%; margin: auto;">

                  {!! Form::label('other_sale_discount_type', __('settlementsw::lang.discount_type')) !!}

                  {!! Form::select('other_sale_discount_type', ['fixed' => 'Fixed', 'percentage' => 'Percentage'],

                      !empty($temp_data->other_sale_discount_type) ? $temp_data->other_sale_discount_type : null, [

                      'class' => 'form-control other_sale_fields check_pumper other_sale_discount_type',

                      'required',

                      'placeholder' => __('settlementsw::lang.please_select')

                  ]) !!}

                </div>

              </div>



              <div class="form-group-custom " style="flex: 0 0 20%;">

                {!! Form::label('other_sale_discount', __('settlementsw::lang.discount')) !!}

                {!! Form::text('other_sale_discount', !empty($temp_data->other_sale_discount) ? $temp_data->other_sale_discount : null, [

                    'class' => 'form-control other_sale_fields check_pumper input_number other_sale_discount',

                    'required',

                    'placeholder' => __('settlementsw::lang.discount')

                ]) !!}

              </div>



              <input type="hidden" value="{{ $pump_other_sale_final_total ?? 0 }}" name="other_sale_total" id="other_sale_total">

              <input type="hidden" value="{{ !empty($temp_data->shift_operator_other_sale_total) ? $temp_data->shift_operator_other_sale_total : 0 }}" id="shift_operator_other_sale_total">

              <input type="hidden" value="{{ $check_qty }}" id="allowoverselling">



              <div class="form-group-custom " style="display:flex;align-items:flex-end;justify-content:center;">

                <button type="button" class="btn-add" id="add-other-sale"><i class="fa fa-plus"></i></button>

              </div>

            </form>



            <table class="table-custom">

              <thead>

                <tr>

                  <th style="width: 15%;">Code</th>

                  <th style="width: 20%;">Products</th>

                  <th style="width: 10%;">Balance Stock</th>

                  <th style="width: 10%;">Price</th>

                  <th style="width: 10%;">Qty</th>

                  <th style="width: 8%;">Discount Type</th>

                  <th style="width: 8%;">Discount Value</th>

                  <th style="width: 10%;">Before Discount</th>

                  <th style="width: 10%;">After Discount</th>

                  <th style="width: 9%;">Action</th>

                </tr>

              </thead>

              <tbody id="other-sales-tbody">

                {{-- Dynamic rows via JS --}}

              </tbody>

              <tfoot>

                <tr>

                  <td colspan="8" class="text-right">Other Sales Total:</td>

                  <td colspan="2" id="other-sale-after-discount-total">0.00</td>

                </tr>

              </tfoot>

            </table>

          </div>

        </div>