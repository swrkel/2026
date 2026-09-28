<div class="modal-dialog" role="document" style="width: 80%;">
    <div class="modal-content">

        
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'pumperdashboard::lang.other_sales' )</h4>
        </div>

        <div class="modal-body">
            
            @php
                $default_store = request()->session()->get('business.default_store');
            @endphp
            <div class="col-md-12">
                <input type="hidden" value="{{$pump_operator->location_id}}" id="location_id">
                
                <input type="hidden" value="{{$shift_id}}" id="shift_id">
                
                <input type="hidden" value="{{$pump_operator->id}}" id="pump_operator_id">
                
                
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="col-md-2 store_field">
                        <div class="form-group">
                            {!! Form::label('store', __('pumperdashboard::lang.store').':') !!}
                            {!! Form::select('store_id', $stores, $default_store, ['class' => 'form-control check_pumper
                            select2', 'style' => 'width: 100%;', 'id' => 'store_id',
                            'placeholder' => __('pumperdashboard::lang.please_select')]); !!}
                        </div>
                    </div>
                    <div class="col-md-2 bulk_tank_field hide">
                        <div class="form-group">
                            {!! Form::label('bulk_tank', __('pumperdashboard::lang.bulk_tank').':') !!}
                            {!! Form::select('bulk_tank', $bulk_tanks, null, ['class' => 'form-control check_pumper
                            select2', 'style' => 'width: 100%;', 'id' => 'bulk_tank',
                            'placeholder' => __('pumperdashboard::lang.please_select')]); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('item', __('pumperdashboard::lang.select_item').':') !!}
                            {!! Form::select('item', $items, null, ['class' => 'form-control other_sale_fields check_pumper
                            select2', 'style' => 'width: 100%;','placeholder' => __('pumperdashboard::lang.please_select')]); !!}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            {!! Form::label('balance_stock', __( 'pumperdashboard::lang.balance_stock' ) ) !!}
                            {!! Form::text('balance_stock', null, ['class' => 'form-control other_sale_fields check_pumper input_number
                            balance_stock', 'required', 'readonly',
                            'placeholder' => __(
                            'pumperdashboard::lang.balance_stock' ) ]); !!}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            {!! Form::label('other_sale_price', __( 'pumperdashboard::lang.price' ) ) !!}
                            {!! Form::text('other_sale_price', null, ['class' => 'form-control other_sale_fields check_pumper input_number
                            other_sale_price', 'required', 'readonly',
                            'placeholder' => __(
                            'pumperdashboard::lang.price' ) ]); !!}
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-2">
                        <div class="form-group">
                            {!! Form::label('other_sale_qty', __( 'pumperdashboard::lang.qty' ) ) !!}
                            {!! Form::text('other_sale_qty', null, ['class' => 'form-control other_sale_fields check_pumper qty input_number',
                            'required',
                            'placeholder' => __(
                            'pumperdashboard::lang.qty' ) ]); !!}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            {!! Form::label('other_sale_discount_type', __( 'pumperdashboard::lang.discount_type' ) ) !!}
                            {!! Form::select('other_sale_discount_type', ['fixed' => 'Fixed', 'percentage' => 'Percentage'], null, ['class' => 'form-control other_sale_fields check_pumper
                            input_number
                            other_sale_discount_type', 'required',
                            'placeholder' => __(
                            'pumperdashboard::lang.please_select' ) ]); !!}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            {!! Form::label('other_sale_discount', __( 'pumperdashboard::lang.discount' ) ) !!}
                            {!! Form::text('other_sale_discount', null, ['class' => 'form-control other_sale_fields check_pumper input_number
                            other_sale_discount', 'required',
                            'placeholder' => __(
                            'pumperdashboard::lang.discount' ) ]); !!}
                        </div>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary btn_other_sale"
                            style="margin-top: 23px;">@lang('messages.add')</button>
                    </div>
                </div>
            </div>
            <br>
            <br>
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered table-striped" id="other_sale_table">
                        <thead>
                            <tr>
                                <th>@lang('pumperdashboard::lang.code' )</th>
                                <th>@lang('pumperdashboard::lang.products' )</th>
                                <th>@lang('pumperdashboard::lang.balance_stock' )</th>
                                <th>@lang('pumperdashboard::lang.price')</th>
                                <th>@lang('pumperdashboard::lang.qty' )</th>
                                <th>@lang('pumperdashboard::lang.discount_type' )</th>
                                <th>@lang('pumperdashboard::lang.discount_value' )</th>
                                <th>@lang('pumperdashboard::lang.before_discount' )</th>
                                <th>@lang('pumperdashboard::lang.after_discount' )</th>
                                <th>@lang('pumperdashboard::lang.action' )</th>
                            </tr>
                        </thead>
            
                        <tbody>
                            @php
                                $other_sale_final_total = 0.00;
                            @endphp
                            @if (!empty($other_sales))
                                @foreach ($other_sales as $ot_item)
                                    @php
                                        $product = $other_sale_products->get($ot_item->product_id);
                                        $discount_amount = $ot_item->discount_amount;
                                        $withDiscount = $ot_item->sub_total - $discount_amount;
                                        $other_sale_final_total += $withDiscount;
                                    @endphp
                                  
                                    <tr>
                                        <td>{{!empty($product) ? $product->sku : ''}}</td>
                                        <td>{{!empty($product) ? $product->name : ''}}</td>
                                        <td>{{number_format($ot_item->balance_stock,4,'.',',')}}</td>
                                        <td>{{@num_format($ot_item->price)}}</td>
                                        <td>{{number_format($ot_item->qty,4,'.',',')}}</td>
                                        <td>{{$ot_item->discount_type}}</td>
                                        <td>{{@num_format($ot_item->discount)}}</td>
                                        <td>{{@num_format($ot_item->sub_total)}}</td>
                                        <td>{{@num_format($withDiscount)}}</td>
                                        <td><button class="btn btn-xs btn-danger delete_other_sale"
                                                data-href="/pumper-dashboard/pump-operator-payments/delete-other-sale/{{$ot_item->id}}"><i class="fa fa-times"></i>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
            
                        </tbody>
            
                        <tfoot>
                            <tr>
                                <td colspan="7" style="text-align: right; font-weight: bold;">@lang('pumperdashboard::lang.other_sale_total')
                                    :</td>
                                <td style="text-align: left; font-weight: bold;" class="other_sale_total">
                                    {{@num_format($other_sale_final_total)}}</td>
            
                            </tr>
                            <input type="hidden" value="{{$other_sale_final_total}}" name="other_sale_total" id="other_sale_total">
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <div class="clearfix"></div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary add_fuel_tank_btn">@lang( 'messages.save' )</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
            </div>

           
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->

    <script>
        $(".select2").select2();
    </script>




