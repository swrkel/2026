      <div class="card-custom">

        <div class="card-header">Meter Sales</div>

        <div class="card-body">

          <form id="meter-sales-form" class="form-row-custom align-items-end">



            <div class="form-group-custom " style="flex: 0 0 30%;">

              {!! Form::label('pump_no', __('settlementsw::lang.pump_no').':') !!}

              {!! Form::select('pump_no', $pump_nos, !empty($temp_data->pump_no) ? $temp_data->pump_no : $pump_no, ['class' => 'form-control meter_sale_fields check_pumper select2', 'placeholder' => __('settlementsw::lang.please_select')]) !!}

            </div>



            <div class="form-group-custom " style="flex: 0 0 20%;">

              {!! Form::label('pump_starting_meter', __('settlementsw::lang.pump_starting_meter')) !!}

              {!! Form::text('pump_starting_meter', !empty($temp_data->pump_starting_meter) ? $temp_data->pump_starting_meter : $pump_starting_meter, ['class' => 'form-control meter_sale_fields check_pumper input_number pump_starting_meter', 'required', 'readonly', 'placeholder' => __('settlementsw::lang.pump_starting_meter')]) !!}

            </div>



            <div class="form-group-custom " style="flex: 0 0 20%;">

              {!! Form::label('pump_closing_meter', __('settlementsw::lang.pump_closing_meter')) !!}

              {!! Form::text('pump_closing_meter', !empty($temp_data->pump_closing_meter) ? $temp_data->pump_closing_meter : $pump_closing_meter, ['class' => 'form-control meter_sale_fields check_pumper input_number pump_closing_meter', 'required', 'step' => '0.001', 'min' => '0', 'placeholder' => __('settlementsw::lang.pump_closing_meter')]) !!}

            </div>



            <div class="form-group-custom " style="flex: 0 0 25%;">

              {!! Form::label('sold_qty', __('settlementsw::lang.sold_qty')) !!}

              {!! Form::text('sold_qty', !empty($temp_data->sold_qty) ? number_format($temp_data->sold_qty, 3) : number_format($sold_qty, 3), ['class' => 'form-control meter_sale_fields check_pumper sold_qty input_number', 'required', 'disabled', 'placeholder' => __('settlementsw::lang.sold_qty'), 'step' => '0.001']) !!}

              <input type="hidden" class="meter_sale_fields is_from_pumper" id="is_from_pumper" value="{{ !empty($temp_data->is_from_pumper) ? $temp_data->is_from_pumper : 0 }}">

              <input type="hidden" class="meter_sale_fields assignment_id" id="assignment_id" value="{{ !empty($temp_data->assignment_id) ? $temp_data->assignment_id : 0 }}">

              <input type="hidden" class="meter_sale_fields pumper_entry_id" id="pumper_entry_id" value="{{ !empty($temp_data->pumper_entry_id) ? $temp_data->pumper_entry_id : 0 }}">

            </div>



            <div class="form-group-custom " style="flex: 0 0 30%;">

              {!! Form::label('unit_price', __('settlementsw::lang.unit_price')) !!}

              {!! Form::text('meter_sale_unit_price', !empty($temp_data->unit_price) ? $temp_data->unit_price : $meter_sale_unit_price, ['id' => 'meter_sale_unit_price', 'class' => 'form-control meter_sale_fields check_pumper unit_price input_number', 'readonly', 'placeholder' => __('settlementsw::lang.unit_price')]) !!}

            </div>



            <div class="form-group-custom " style="flex: 0 0 30%;">

              {!! Form::label('testing_qty', __('settlementsw::lang.testing_qty')) !!}

              {!! Form::text('testing_qty', !empty($temp_data->testing_qty) ? $temp_data->testing_qty : $testing_qty, ['class' => 'form-control check_pumper input_number testing_qty', 'required', 'placeholder' => __('settlementsw::lang.testing_qty')]) !!}

            </div>



            <div class="form-group-custom " style="flex: 0 0 20%;">

              {!! Form::label('meter_sale_discount_type', __('settlementsw::lang.discount_type')) !!}

              {!! Form::select('meter_sale_discount_type', $discount_types, !empty($temp_data->discount_type) ? $temp_data->discount_type : $meter_sale_discount_type, ['class' => 'form-control meter_sale_fields check_pumper meter_sale_discount_type', 'required', 'placeholder' => __('settlementsw::lang.please_select')]) !!}

            </div>



            <div class="form-group-custom " style="flex: 0 0 30%;">

              {!! Form::label('meter_sale_discount', __('settlementsw::lang.discount')) !!}

              {!! Form::text('meter_sale_discount', !empty($temp_data->discount) ? $temp_data->discount : $meter_sale_discount, ['class' => 'form-control meter_sale_fields check_pumper meter_sale_discount input_number', 'required', 'placeholder' => __('settlementsw::lang.discount')]) !!}

            </div>



            <div class="form-group-custom " style="display:flex;align-items:flex-end;justify-content:center;">

              <button type="button" class="btn-add" id="add-meter-sale">

                <i class="fa fa-plus"></i>

              </button>

            </div>

          </form>



          <input type="hidden" value="{{ $final_total ?? 0 }}" name="meter_sale_total" id="meter_sale_total">

          <div class="table-responsive meter-sale-scroll">

            <table id="meter_sale_table" class="table-custom">

              <thead>

                <tr>

                  <th>@lang('settlementsw::lang.code' )</th>

                  <th class="product-column">@lang('settlementsw::lang.products' )</th>

                  <th>@lang('settlementsw::lang.pump' )</th>

                  <th class="starting-meter">@lang('settlementsw::lang.starting_meter')</th>

                  <th class="closing-meter">@lang('settlementsw::lang.closing_meter')</th>

                  <th class="price-meter">@lang('settlementsw::lang.price')</th>

                  <th>@lang('settlementsw::lang.sold_qty' )</th> {{-- Qty = Closing Meter- Starting Meter - Testing Qty --}}

                  <th style="width: 10px;">@lang('settlementsw::lang.discount_type' )</th>

                  <th style="width: 6px;">@lang('settlementsw::lang.discount_value' )</th>

                  <th style="width: 10px;">@lang('settlementsw::lang.testing_qty' )</th>

                  <th class="total-qty-meter">@lang('settlementsw::lang.total_qty' )</th>

                  <th class="before-discount-meter">@lang('settlementsw::lang.before_discount' )</th>

                  <th class="after-discount-meter">@lang('settlementsw::lang.after_discount' )</th>

                  <th class="action-meter">@lang('settlementsw::lang.action' )</th>

                </tr>

              </thead>

              <tbody id="meter-sales-tbody">

                {{-- rows --}}

              </tbody>

              <tfoot>

                <tr>

                  <td colspan="6"></td>

                  <td><span class="product_summary"></span></td>

                  <td colspan="6" class="text-right">Meter Sales Total:</td>

                  <td colspan="2" id="meter-sales-total">0.00</td>

                </tr>

              </tfoot>

            </table>

          </div>

        </div>

      </div>