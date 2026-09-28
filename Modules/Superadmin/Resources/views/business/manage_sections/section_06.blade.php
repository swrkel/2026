{{--
    MA-002: extracted from business/manage.blade.php, which was 14,602 lines.

    The lines below are BYTE-IDENTICAL to the original - nothing was rewritten,
    reindented or reordered. The parent file @includes this at exactly the same
    point, so the rendered page is unchanged.

    Only blocks whose own tags balance were moved. Three blocks in the original
    do not close their divs within their own boundaries, so they stay in the
    parent rather than risk moving markup that depends on what surrounds it.
--}}
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> Purchase Module</h4>
                            <hr>
                          </div>
                          <div class="card-body">
                              <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>
                                
                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2  text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>
                          
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.purchase')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('purchase', 0) !!}
                                    {!! Form::checkbox('purchase', 1,
                                    !empty($manage_module_enable['purchase']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['purchase_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['purchase_expiry_date']))
                                        @if(strtotime($module_activation_data['purchase_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['purchase_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('purchase_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['purchase_interval']) ?
                                        $module_activation_data['purchase_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('purchase_length', !empty($module_activation_data['purchase_length']) ?
                                        $module_activation_data['purchase_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('purchase_activated_on', !empty($module_activation_data['purchase_activated_on']) ?
                                    $module_activation_data['purchase_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('purchase_expiry_date', !empty($module_activation_data['purchase_expiry_date']) ?
                                    $module_activation_data['purchase_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('purchase_price', !empty($module_activation_data['purchase_price']) ?
                                    $module_activation_data['purchase_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                              
                            </div>
                            <hr>
                            
                            <div class="row">
                            <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red">
                                            {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('all_purchase', 1, !empty($manage_module_enable['all_purchase']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'all_purchase']) !!}
                                        {{__('All Purchase')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('add_purchase', 1, !empty($manage_module_enable['add_purchase']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'add_purchase']) !!}
                                        {{__('Add Purchase')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('add_bulk_purchase', 1, !empty($manage_module_enable['add_bulk_purchase']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'add_bulk_purchase']) !!}
                                        {{__('Add Bulk Purchase')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('import_purchase', 1, !empty($manage_module_enable['import_purchase']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'import_purchase']) !!}
                                        {{__('Import Purchase')}}
                                    </label>
                                </div>
                            </div>
                        
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('pop_button_on_top_belt', 1, !empty($manage_module_enable['pop_button_on_top_belt']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'pop_button_on_top_belt']) !!}
                                        {{__('Pop Button On The Belt')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('purchase_return', 1, !empty($manage_module_enable['purchase_return']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'purchase_return']) !!}
                                        {{__('Purchase Return')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('purchase_discounts', 1, !empty($manage_module_enable['purchase_discounts']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'purchase_discounts']) !!}
                                        {{__('Purchase Discounts')}}
                                    </label>
                                </div>
                            </div>
                        </div>
                            
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> Products Module</h4>
                            <hr>
                          </div>
                          <div class="card-body">
                              <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>
                                
                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2  text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>
                          
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.products')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('products', 0) !!}
                                    {!! Form::checkbox('products', 1,
                                    !empty($manage_module_enable['products']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['products_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['products_expiry_date']))
                                        @if(strtotime($module_activation_data['products_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['products_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('products_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['products_interval']) ?
                                        $module_activation_data['products_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('products_length', !empty($module_activation_data['products_length']) ?
                                        $module_activation_data['products_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('products_activated_on', !empty($module_activation_data['products_activated_on']) ?
                                    $module_activation_data['products_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('products_expiry_date', !empty($module_activation_data['products_expiry_date']) ?
                                    $module_activation_data['products_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('products_price', !empty($module_activation_data['products_price']) ?
                                    $module_activation_data['products_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                              
                            </div>
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red">
                                            {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('stock_taking', 1, !empty($manage_module_enable['stock_taking']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'stock_taking']) !!}
                                        {{__('superadmin::lang.stock_taking')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('fixed_conversion', 1, !empty($manage_module_enable['fixed_conversion']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'fixed_conversion']) !!}
                                        {{__('superadmin::lang.fixed_conversion')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('products_list_product', 1, (!empty($manage_module_enable['products_list_product']) || !array_key_exists('products_list_product',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'products_list_product']) !!}
                                        {{__('superadmin::lang.products_list_product')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('products_all_products', 1, (!empty($manage_module_enable['products_all_products']) || !array_key_exists('products_all_products',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'products_all_products']) !!}
                                        {{__('superadmin::lang.products_all_products')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('products_current_stock', 1, (!empty($manage_module_enable['products_current_stock']) || !array_key_exists('products_current_stock',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'products_current_stock']) !!}
                                        {{__('superadmin::lang.products_current_stock')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('products_add_edit', 1, (!empty($manage_module_enable['products_add_edit']) || !array_key_exists('products_add_edit',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'products_add_edit']) !!}
                                        {{__('superadmin::lang.products_add_edit')}}
                                    </label>
                                </div>
                            </div>
                        
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('products_stock_history', 1, (!empty($manage_module_enable['products_stock_history']) || !array_key_exists('products_stock_history',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'products_stock_history']) !!}
                                        {{__('superadmin::lang.products_stock_history')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('products_stock_report', 1, (!empty($manage_module_enable['products_stock_report']) || !array_key_exists('products_stock_report',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'products_stock_report']) !!}
                                        {{__('superadmin::lang.products_stock_report')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('products_opening_stock', 1, (!empty($manage_module_enable['products_opening_stock']) || !array_key_exists('products_opening_stock',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'products_opening_stock']) !!}
                                        {{__('superadmin::lang.products_opening_stock')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('products_variations', 1, (!empty($manage_module_enable['products_variations']) || !array_key_exists('products_variations',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'products_variations']) !!}
                                        {{__('superadmin::lang.products_variations')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('products_import', 1, (!empty($manage_module_enable['products_import']) || !array_key_exists('products_import',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'products_import']) !!}
                                        {{__('superadmin::lang.products_import')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('products_import_opening_stock', 1, (!empty($manage_module_enable['products_import_opening_stock']) || !array_key_exists('products_import_opening_stock',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'products_import_opening_stock']) !!}
                                        {{__('superadmin::lang.products_import_opening_stock')}}
                                    </label>
                                </div>
                            </div>


                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('products_selling_price_group', 1, (!empty($manage_module_enable['products_selling_price_group']) || !array_key_exists('products_selling_price_group',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'products_selling_price_group']) !!}
                                        {{__('superadmin::lang.products_selling_price_group')}}
                                    </label>
                                </div>
                            </div>





                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('products_units', 1, (!empty($manage_module_enable['products_units']) || !array_key_exists('products_units',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'products_units']) !!}
                                        {{__('superadmin::lang.products_units')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('products_stock_conversion', 1, (!empty($manage_module_enable['products_stock_conversion']) || !array_key_exists('products_stock_conversion',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'products_stock_conversion']) !!}
                                        {{__('superadmin::lang.products_stock_conversion')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('products_categories', 1, (!empty($manage_module_enable['products_categories']) || !array_key_exists('products_categories',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'products_categories']) !!}
                                        {{__('superadmin::lang.products_categories')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('products_brand_warranties', 1, (!empty($manage_module_enable['products_brand_warranties']) || !array_key_exists('products_brand_warranties',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'products_brand_warranties']) !!}
                                        {{__('superadmin::lang.products_brand_warranties')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('product_print_labels', 1, (!empty($manage_module_enable['product_print_labels']) || !array_key_exists('product_print_labels',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'product_print_labels']) !!}
                                        {{__('superadmin::lang.product_print_labels')}}
                                    </label>
                                </div>
                            </div>



                            
                            
                            {{-- Show in Pumper Dashboard (Business-level default) --}}
<div class="col-sm-3">
  <div class="checkbox">
    <label class="flex-label search_label">
    {{-- اجعل 0 يُرسل دائمًا عندما يكون الـ checkbox غير محدد --}}
{!! Form::hidden('products_pumper_dashboard_default', 0) !!}

{!! Form::checkbox(
    'products_pumper_dashboard_default',
    1,
    // اقرأ القيمة المحفوظة (1 أو 0)؛ الافتراضي 0 إذا غير موجودة
    (int)($manage_module_enable['products_pumper_dashboard_default'] ?? 0) === 1,
    ['class' => 'input-icheck-red ch_select','id' => 'products_pumper_dashboard_default']
) !!}
      {{ __('superadmin::lang.products_pumper_dashboard_default') }}
    </label>
  </div>
</div>
                            
                            
                        </div>
                            
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.show_in_role_page')</h4>
                            <hr>
                          </div>
                          <div class="card-body">
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red">
                                            {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('management_reports', 1, (!empty($manage_module_enable['management_reports']) || !array_key_exists('management_reports',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'management_reports']) !!}
                                        {{__('superadmin::lang.management_reports')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('unfinished_form', 1, (!empty($manage_module_enable['unfinished_form']) || !array_key_exists('unfinished_form',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'unfinished_form']) !!}
                                        {{__('superadmin::lang.unfinished_form')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('routes', 1, (!empty($manage_module_enable['routes']) || !array_key_exists('routes',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'routes']) !!}
                                        {{__('superadmin::lang.routes')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('drivers', 1, (!empty($manage_module_enable['drivers']) || !array_key_exists('drivers',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'drivers']) !!}
                                        {{__('superadmin::lang.drivers')}}
                                    </label>
                                </div>
                            </div>
                        
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('helpers', 1, (!empty($manage_module_enable['helpers']) || !array_key_exists('helpers',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'helpers']) !!}
                                        {{__('superadmin::lang.helpers')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('pump_operator', 1, (!empty($manage_module_enable['pump_operator']) || !array_key_exists('pump_operator',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'pump_operator']) !!}
                                        {{__('superadmin::lang.pump_operator')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('daily_pump_status', 1, (!empty($manage_module_enable['daily_pump_status']) || !array_key_exists('daily_pump_status',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'daily_pump_status']) !!}
                                        {{__('superadmin::lang.daily_pump_status')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('day_count', 1, (!empty($manage_module_enable['day_count']) || !array_key_exists('day_count',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'day_count']) !!}
                                        {{__('superadmin::lang.day_count')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('access_selling_price', 1, (!empty($manage_module_enable['access_selling_price']) || !array_key_exists('access_selling_price',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'access_selling_price']) !!}
                                        {{__('superadmin::lang.access_selling_price')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('set_minimum_price', 1, (!empty($manage_module_enable['set_minimum_price']) || !array_key_exists('set_minimum_price',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'set_minimum_price']) !!}
                                        {{__('superadmin::lang.set_minimum_price')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('view_sales_commission', 1, (!empty($manage_module_enable['view_sales_commission']) || !array_key_exists('view_sales_commission',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'view_sales_commission']) !!}
                                        {{__('superadmin::lang.view_sales_commission')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('current_sale', 1, (!empty($manage_module_enable['current_sale']) || !array_key_exists('current_sale',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'current_sale']) !!}
                                        {{__('superadmin::lang.current_sale')}}
                                    </label>
                                </div>
                            </div>
                            
                        </div>
                            
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4>{{__('superadmin::lang.subscriptions_module')}}</h4>
                            <hr>
                          </div>
                          <div class="card-body">
                              
                            <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>
                                
                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2  text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">{{__('superadmin::lang.subscriptions_module')}}</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('subscriptions_module', 0) !!}
                                    {!! Form::checkbox('subscriptions_module', 1, (!empty($manage_module_enable['subscriptions_module']) || !array_key_exists('subscriptions_module',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'subscriptions_module']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['subscriptions_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['subscriptions_expiry_date']))
                                        @if(strtotime($module_activation_data['subscriptions_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['subscriptions_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                <div class="col-md-1">
                                    {!! Form::select('subscriptions_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['subscriptions_interval']) ?
                                    $module_activation_data['subscriptions_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('subscriptions_length', !empty($module_activation_data['subscriptions_length']) ?
                                    $module_activation_data['subscriptions_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('subscriptions_activated_on', !empty($module_activation_data['subscriptions_activated_on']) ?
                                    $module_activation_data['subscriptions_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('subscriptions_expiry_date', !empty($module_activation_data['subscriptions_expiry_date']) ?
                                    $module_activation_data['subscriptions_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('subscriptions_price', !empty($module_activation_data['subscriptions_price']) ?
                                    $module_activation_data['subscriptions_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red">
                                            {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                          
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('list_subscriptions', 1, (!empty($manage_module_enable['list_subscriptions']) || !array_key_exists('list_subscriptions',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'list_subscriptions']) !!}
                                        {{__('superadmin::lang.list_subscriptions')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('subscriptions_settings', 1, (!empty($manage_module_enable['subscriptions_settings']) || !array_key_exists('subscriptions_settings',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'subscriptions_settings']) !!}
                                        {{__('superadmin::lang.subscriptions_settings')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('subscriptions_sms_template', 1, (!empty($manage_module_enable['subscriptions_sms_template']) || !array_key_exists('subscriptions_sms_template',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'subscriptions_sms_template']) !!}
                                        {{__('superadmin::lang.subscriptions_sms_template')}}
                                    </label>
                                </div>
                            </div>
                        
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('subscriptions_user_activity', 1, (!empty($manage_module_enable['subscriptions_user_activity']) || !array_key_exists('subscriptions_user_activity',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'subscriptions_user_activity']) !!}
                                        {{__('superadmin::lang.subscriptions_user_activity')}}
                                    </label>
                                </div>
                            </div>
                            
                        </div>
                            
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> Price Changes Module</h4>
                            <hr>
                          </div>
                          <div class="card-body">
                              <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>
                                
                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2  text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>
                          
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('pricechanges::lang.mpcs')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('price_changes_module', 0) !!}
                                    {!! Form::checkbox('price_changes_module', 1,
                                    !empty($manage_module_enable['price_changes_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['price_changes_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['price_changes_expiry_date']))
                                        @if(strtotime($module_activation_data['price_changes_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['price_changes_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('price_changes_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['price_changes_interval']) ?
                                    $module_activation_data['price_changes_interval'] : null, [
                                                'id' => 'status',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%',
                                                'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('price_changes_length', !empty($module_activation_data['price_changes_length']) ?
                                    $module_activation_data['price_changes_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('price_changes_activated_on', !empty($module_activation_data['price_changes_activated_on']) ?
                                    $module_activation_data['price_changes_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('price_changes_expiry_date', !empty($module_activation_data['price_changes_expiry_date']) ?
                                    $module_activation_data['price_changes_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('price_changes_price', !empty($module_activation_data['price_changes_price']) ?
                                    $module_activation_data['price_changes_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                               
                                </div>
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red">
                                            {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::checkbox('price_change_edit_qty', 1, !empty($manage_module_enable['price_change_edit_qty']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'price_change_edit_qty']) !!}
                                            {{__('superadmin::lang.price_change_edit_qty')}}
                                        </label>
                                    </div>
                                </div>
                                
                            </div>
                            
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('petro::lang.ezy_products')</h4>
                            <hr>
                          </div>
                          <div class="card-body">
                              <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>
                                
                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2  text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>
                          
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('petro::lang.ezy_products')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('ezy_products', 0) !!}
                                    {!! Form::checkbox('ezy_products', 1,
                                    !empty($manage_module_enable['ezy_products']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['ezy_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['ezy_expiry_date']))
                                        @if(strtotime($module_activation_data['ezy_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['ezy_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('ezy_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['ezy_interval']) ?
                                    $module_activation_data['ezy_interval'] : null, [
                                                'id' => 'status',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%',
                                                'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('ezy_length', !empty($module_activation_data['ezy_length']) ?
                                    $module_activation_data['ezy_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('ezy_activated_on', !empty($module_activation_data['ezy_activated_on']) ?
                                    $module_activation_data['ezy_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('ezy_expiry_date', !empty($module_activation_data['ezy_expiry_date']) ?
                                    $module_activation_data['ezy_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('ezy_price', !empty($module_activation_data['ezy_price']) ?
                                    $module_activation_data['ezy_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                                
                            </div>
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red">
                                            {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('ezy_list_products', 1, !empty($manage_module_enable['ezy_list_products']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'ezy_list_products']) !!}
                                        {{__('superadmin::lang.ezy_list_products')}}
                                    </label>
                                </div>
                            </div>
                         
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('ezy_units', 1, !empty($manage_module_enable['ezy_units']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'ezy_units']) !!}
                                        {{__('superadmin::lang.ezy_units')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('ezy_categories', 1, !empty($manage_module_enable['ezy_categories']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'ezy_categories']) !!}
                                        {{__('superadmin::lang.ezy_categories')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('ezy_show_current_stock', 1, !empty($manage_module_enable['ezy_show_current_stock']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'ezy_show_current_stock']) !!}
                                        {{__('superadmin::lang.ezy_show_current_stock')}}
                                    </label>
                                </div>
                            </div>
                        
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('ezy_show_stock_report', 1, !empty($manage_module_enable['ezy_show_stock_report']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'ezy_show_stock_report']) !!}
                                        {{__('superadmin::lang.ezy_show_stock_report')}}
                                    </label>
                                </div>
                            </div>
                        </div>
                            
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> Cheque Writing Module</h4>
                            <hr>
                          </div>
                          <div class="card-body">
                              <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>
                                
                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2  text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>
                          
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('lang_v1.enable_cheque_writing')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('enable_cheque_writing', 0) !!}
                                    {!! Form::checkbox('enable_cheque_writing', 1,
                                    !empty($manage_module_enable['enable_cheque_writing']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['cheque_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['cheque_expiry_date']))
                                        @if(strtotime($module_activation_data['cheque_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['cheque_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('cheque_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['cheque_interval']) ?
                                    $module_activation_data['cheque_interval'] : null, [
                                                'id' => 'status',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%',
                                                'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('cheque_length', !empty($module_activation_data['cheque_length']) ?
                                    $module_activation_data['cheque_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('cheque_activated_on', !empty($module_activation_data['cheque_activated_on']) ?
                                    $module_activation_data['cheque_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('cheque_expiry_date', !empty($module_activation_data['cheque_expiry_date']) ?
                                    $module_activation_data['cheque_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('cheque_price', !empty($module_activation_data['cheque_price']) ?
                                    $module_activation_data['cheque_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                                
                            </div>
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red">
                                            {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('cheque_templates', 1, !empty($manage_module_enable['cheque_templates']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'cheque_templates']) !!}
                                        {{__('Templates')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('write_cheque', 1, !empty($manage_module_enable['write_cheque']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'write_cheque']) !!}
                                        {{__('Write Cheque')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('manage_stamps', 1, !empty($manage_module_enable['manage_stamps']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'manage_stamps']) !!}
                                        {{__('Manage Stamps')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('manage_payee', 1, !empty($manage_module_enable['manage_payee']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'manage_payee']) !!}
                                        {{__('Manage Payee')}}
                                    </label>
                                </div>
                            </div>
                        
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('cheque_number_list', 1, !empty($manage_module_enable['cheque_number_list']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'cheque_number_list']) !!}
                                        {{__('Cheque Number List')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('deleted_cheque_details', 1, !empty($manage_module_enable['deleted_cheque_details']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'deleted_cheque_details']) !!}
                                        {{__('Delete Cheque Numbers')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('printed_cheque_details', 1, !empty($manage_module_enable['printed_cheque_details']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'printed_cheque_details']) !!}
                                        {{__('Printed Cheque Details')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('default_setting', 1, !empty($manage_module_enable['default_setting']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'default_setting']) !!}
                                        {{__('Default Settings')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('default_setting', 1, !empty($manage_module_enable['default_setting']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'default_setting']) !!}
                                        {{__('superadmin::lang.cheque_dashboard')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('cheque_add_template', 1, !empty($manage_module_enable['cheque_add_template']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'cheque_add_template']) !!}
                                        {{__('superadmin::lang.cheque_add_template')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('cheque_cancelled_cheques', 1, !empty($manage_module_enable['cheque_cancelled_cheques']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'cheque_cancelled_cheques']) !!}
                                        {{__('superadmin::lang.cheque_cancelled_cheques')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('cheque_printed_cheques', 1, !empty($manage_module_enable['cheque_printed_cheques']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'cheque_printed_cheques']) !!}
                                        {{__('superadmin::lang.cheque_printed_cheques')}}
                                    </label>
                                </div>
                            </div>
                        </div>
                            
                         </div>
                          
                    </div>
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> Settlement SW Module</h4>
                            <hr>
                          </div>
                          <div class="card-body">
                              <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>
                                
                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2  text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>

                            
                          
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label"> @lang('superadmin::lang.settlement_sw')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox(
                                        'settlement_sw_module',
                                        1,
                                        isset($manage_module_enable['settlement_sw_module']) && $manage_module_enable['settlement_sw_module'] == 1,
                                        ['class' => 'input-icheck-red ch_select']
                                    ) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['settlement_sw_module_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['settlement_sw_module_expiry_date']))
                                        @if(strtotime($module_activation_data['settlement_sw_module_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['settlement_sw_module_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('settlement_sw_module_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['settlement_sw_module_interval']) ?
                                    $module_activation_data['settlement_sw_module_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('settlement_sw_module_length', !empty($module_activation_data['settlement_sw_module_length']) ?
                                    $module_activation_data['settlement_sw_module_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('settlement_sw_module_activated_on', !empty($module_activation_data['settlement_sw_module_activated_on']) ?
                                    $module_activation_data['settlement_sw_module_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('settlement_sw_module_expiry_date', !empty($module_activation_data['settlement_sw_module_expiry_date']) ?
                                    $module_activation_data['settlement_sw_module_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('settlement_sw_module_price', !empty($module_activation_data['settlement_sw_module_price']) ?
                                    $module_activation_data['settlement_sw_module_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red"> {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                    <div class="col-md-2" style="background: #d6e8c8;">
                                        {!! Form::hidden('settlement_sw_other_income', 0) !!}
                                        {!! Form::checkbox('settlement_sw_other_income', 1,
                                            !empty($manage_module_enable['settlement_sw_other_income']) ? true : false,
                                            ['class' => 'input-icheck-red ch_select']
                                        ) !!}
                                        <label for="settlement_sw_other_income">Settlement SW Other Income</label>
                                    </div>
                                
                                    <div class="col-md-2" style="background: #d6e8c8; width: 276px;">
                                        {!! Form::hidden('settlement_sw_customer_payments', 0) !!}
                                        {!! Form::checkbox('settlement_sw_customer_payments', 1,
                                            !empty($manage_module_enable['settlement_sw_customer_payments']) ? true : false,
                                            ['class' => 'input-icheck-red ch_select']
                                        ) !!}
                                        <label for="settlement_sw_customer_payments" >Settlement SW Customer Payments</label>
                                    </div>
                                
                                    <div class="col-md-2" style="background: #d6e8c8;">
                                        {!! Form::hidden('settlement_sw_expenses', 0) !!}
                                        {!! Form::checkbox('settlement_sw_expenses', 1,
                                            !empty($manage_module_enable['settlement_sw_expenses']) ? true : false,
                                            ['class' => 'input-icheck-red ch_select']
                                        ) !!}
                                        <label for="settlement_sw_expenses">Settlement SW Expenses</label>
                                    </div>
                                    <div class="col-md-2" style="background: #d6e8c8;">
                                        {!! Form::hidden('settlement_sw_payment', 0) !!}
                                        {!! Form::checkbox('settlement_sw_payment', 1,
                                            !empty($manage_module_enable['settlement_sw_payment']) ? true : false,
                                            ['class' => 'input-icheck-red ch_select']
                                        ) !!}
                                        <label for="settlement_sw_payment">Settlement SW Payment</label>
                                    </div>

                                    <div class="col-md-2" style="background: #d6e8c8;">
                                        {!! Form::hidden('sw_credit_sales', 0) !!}
                                        {!! Form::checkbox('sw_credit_sales', 1,
                                            !empty($manage_module_enable['sw_credit_sales']) ? true : false,
                                            ['class' => 'input-icheck-red ch_select']
                                        ) !!}
                                        <label for="sw_credit_sales">Settlement SW Credit Sales</label>
                                    </div>
                                
                            </div>
                            <hr style="border: 2px solid #2196F3; margin: 20px 0;">
                            <div class="row">
                                <h5 style="margin-left: 15px;">Settlement SW Payment Section with Multi Tabs</h5>
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red"> {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                    <div class="col-md-2" style="background: #d6e8c8;">
                                        {!! Form::hidden('settlement_sw_cash', 0) !!}
                                        {!! Form::checkbox('settlement_sw_cash', 1,
                                            !empty($manage_module_enable['settlement_sw_cash']) ? true : false,
                                            ['class' => 'input-icheck-red ch_select']
                                        ) !!}
                                        <label for="settlement_sw_cash">Cash</label>
                                    </div>
                                    <div class="col-md-2" style="background: #d6e8c8;">
                                        {!! Form::hidden('settlement_sw_credit', 0) !!}
                                        {!! Form::checkbox('settlement_sw_credit', 1,
                                            !empty($manage_module_enable['settlement_sw_credit']) ? true : false,
                                            ['class' => 'input-icheck-red ch_select']
                                        ) !!}
                                        <label for="settlement_sw_credit">Credit Sales</label>
                                    </div>
                                    <div class="col-md-2" style="background: #d6e8c8; width: 276px;">
                                        {!! Form::hidden('settlement_sw_cash_deposit', 0) !!}
                                        {!! Form::checkbox('settlement_sw_cash_deposit', 1,
                                            !empty($manage_module_enable['settlement_sw_cash_deposit']) ? true : false,
                                            ['class' => 'input-icheck-red ch_select']
                                        ) !!}
                                        <label for="settlement_sw_cash_deposit" >Cash Deposit</label>
                                    </div>
                                
                                    <div class="col-md-2" style="background: #d6e8c8;">
                                        {!! Form::hidden('settlement_sw_cards', 0) !!}
                                        {!! Form::checkbox('settlement_sw_cards', 1,
                                            !empty($manage_module_enable['settlement_sw_cards']) ? true : false,
                                            ['class' => 'input-icheck-red ch_select']
                                        ) !!}
                                        <label for="settlement_sw_cards">Cards</label>
                                    </div>
                                    <div class="col-md-2" style="background: #d6e8c8;">
                                        {!! Form::hidden('settlement_sw_cheque', 0) !!}
                                        {!! Form::checkbox('settlement_sw_cheque', 1,
                                            !empty($manage_module_enable['settlement_sw_cheque']) ? true : false,
                                            ['class' => 'input-icheck-red ch_select']
                                        ) !!}
                                        <label for="settlement_sw_cheque">Cheques</label>
                                    </div>
                                    <div class="col-md-2" style="background: #d6e8c8;">
                                        {!! Form::hidden('settlement_sw_payment_expenses', 0) !!}
                                        {!! Form::checkbox('settlement_sw_payment_expenses', 1,
                                            !empty($manage_module_enable['settlement_sw_payment_expenses']) ? true : false,
                                            ['class' => 'input-icheck-red ch_select']
                                        ) !!}
                                        <label for="settlement_sw_payment_expenses">Expenses</label>
                                    </div>
                                     <div class="col-md-2" style="background: #d6e8c8;">
                                        {!! Form::hidden('settlement_sw_shortage', 0) !!}
                                        {!! Form::checkbox('settlement_sw_shortage', 1,
                                            !empty($manage_module_enable['settlement_sw_shortage']) ? true : false,
                                            ['class' => 'input-icheck-red ch_select']
                                        ) !!}
                                        <label for="settlement_sw_shortage">Shortage</label>
                                    </div>
                                    <div class="col-md-2" style="background: #d6e8c8;">
                                        {!! Form::hidden('settlement_sw_excess', 0) !!}
                                        {!! Form::checkbox('settlement_sw_excess', 1,
                                            !empty($manage_module_enable['settlement_sw_excess']) ? true : false,
                                            ['class' => 'input-icheck-red ch_select']
                                        ) !!}
                                        <label for="settlement_sw_excess">Excess</label>
                                    </div>
                                    <div class="col-md-2" style="background: #d6e8c8;">
                                        {!! Form::hidden('settlement_sw_credit_sales', 0) !!}
                                        {!! Form::checkbox('settlement_sw_credit_sales', 1,
                                            !empty($manage_module_enable['settlement_sw_credit_sales']) ? true : false,
                                            ['class' => 'input-icheck-red ch_select']
                                        ) !!}
                                        <label for="settlement_sw_credit_sales">Credit Sales</label>
                                    </div>
                                    <div class="col-md-2" style="background: #d6e8c8;">
                                        {!! Form::hidden('settlement_sw_loan_payments', 0) !!}
                                        {!! Form::checkbox('settlement_sw_loan_payments', 1,
                                            !empty($manage_module_enable['settlement_sw_loan_payments']) ? true : false,
                                            ['class' => 'input-icheck-red ch_select']
                                        ) !!}
                                        <label for="settlement_sw_loan_payments">Loan Payments</label>
                                    </div>
                                    <div class="col-md-2" style="background: #d6e8c8;">
                                        {!! Form::hidden('settlement_sw_owners_drawings', 0) !!}
                                        {!! Form::checkbox('settlement_sw_owners_drawings', 1,
                                            !empty($manage_module_enable['settlement_sw_owners_drawings']) ? true : false,
                                            ['class' => 'input-icheck-red ch_select']
                                        ) !!}
                                        <label for="settlement_sw_owners_drawings">Owners Drawings</label>
                                    </div>
                                    <div class="col-md-2" style="background: #d6e8c8;">
                                        {!! Form::hidden('settlement_sw_loan_to_customer', 0) !!}
                                        {!! Form::checkbox('settlement_sw_loan_to_customer', 1,
                                            !empty($manage_module_enable['settlement_sw_loan_to_customer']) ? true : false,
                                            ['class' => 'input-icheck-red ch_select']
                                        ) !!}
                                        <label for="settlement_sw_loan_to_customer">Loan to Customer</label>
                                    </div>
                                
                            </div>

                            
                            <hr>
                            
                             
                        
                            
                         </div>

                         

                        

                          
                    </div>
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> HMS  Module</h4>
                            <hr>
                          </div>
                          <div class="card-body">
                              <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>
                                
                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2  text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>
                          
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label"> @lang('superadmin::lang.hms_module')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('hms_module', 1, !empty($manage_module_enable['hms_module']) ? true :
                                    false, ['class' => 'input-icheck-red ch_select'])
                                    !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['hms_module_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['hms_module_expiry_date']))
                                        @if(strtotime($module_activation_data['hms_module_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['hms_module_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('hms_module_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['hms_module_interval']) ?
                                    $module_activation_data['hms_module_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('hms_module_length', !empty($module_activation_data['hms_module_length']) ?
                                    $module_activation_data['hms_module_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('hms_module_activated_on', !empty($module_activation_data['hms_module_activated_on']) ?
                                    $module_activation_data['hms_module_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('hms_module_expiry_date', !empty($module_activation_data['hms_module_expiry_date']) ?
                                    $module_activation_data['hms_module_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('hms_module_price', !empty($module_activation_data['hms_module_price']) ?
                                    $module_activation_data['hms_module_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="room_subscribe">{{ __('No of Room Subscribe') }}</label>
                                        {!! Form::text('room_subscribe', !empty($module_activation_data['room_subscribe']) ?
                                            $module_activation_data['room_subscribe'] : $previous_package_data['room_subscribe'], ['class' => 'form-control']) !!}
                                       
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                    <label for="room_added">{{ __('No of Room Added') }}</label>
                                    {!! Form::text('room_added', !empty($module_activation_data['room_added']) ?
                                        $module_activation_data['room_added'] : $previous_package_data['room_added'], ['class' => 'form-control', 'readonly' => 'readonly']) !!}

                                     
                                       
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="room_could_be_added">{{ __('No of Room Could be added') }}</label>
                                        {!! Form::text('room_could_be_added', !empty($module_activation_data['room_could_be_added']) ?
                                            $module_activation_data['room_could_be_added'] : $previous_package_data['room_could_be_added'], ['class' => 'form-control']) !!}

                                      
                                    </div>
                                </div>
                            </div>

                            
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> Other Modules Activation</h4>
                            <hr>
                          </div>
                          <div class="card-body">
                              <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>
                                
                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2  text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>
                            
                             {{-- <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.issue_customer_bill')</label>
                                </div>
                                <div class="col-md-2">
                                     {!! Form::hidden('issue_customer_bill', 0) !!}
                                    {!! Form::checkbox('issue_customer_bill', 1,
                                    !empty($manage_module_enable['issue_customer_bill']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                --}}
                                
                            <!--Additional modules-->
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label"> @lang('superadmin::lang.issue_customer_bill')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('issue_customer_bill', 1, !empty($manage_module_enable['issue_customer_bill']) ? true :
                                    false, ['class' => 'input-icheck-red ch_select'])
                                    !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['issue_customer_bill_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['issue_customer_bill_expiry_date']))
                                        @if(strtotime($module_activation_data['issue_customer_bill_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['issue_customer_bill_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('issue_customer_bill_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['issue_customer_bill_interval']) ?
                                    $module_activation_data['issue_customer_bill_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('issue_customer_bill_length', !empty($module_activation_data['issue_customer_bill_length']) ?
                                    $module_activation_data['issue_customer_bill_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('issue_customer_bill_activated_on', !empty($module_activation_data['issue_customer_bill_activated_on']) ?
                                    $module_activation_data['issue_customer_bill_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('issue_customer_bill_expiry_date', !empty($module_activation_data['issue_customer_bill_expiry_date']) ?
                                    $module_activation_data['issue_customer_bill_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('issue_customer_bill_price', !empty($module_activation_data['issue_customer_bill_price']) ?
                                    $module_activation_data['issue_customer_bill_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            
                            
                            <!--Additional modules-->
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label"> @lang('superadmin::lang.issue_customer_bill_vat')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('issue_customer_bill_vat', 1, !empty($manage_module_enable['issue_customer_bill_vat']) ? true :
                                    false, ['class' => 'input-icheck-red ch_select'])
                                    !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['issue_customer_bill_vat_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['issue_customer_bill_vat_expiry_date']))
                                        @if(strtotime($module_activation_data['issue_customer_bill_vat_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['issue_customer_bill_vat_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('issue_customer_bill_vat_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['issue_customer_bill_vat_interval']) ?
                                    $module_activation_data['issue_customer_bill_vat_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('issue_customer_bill_vat_length', !empty($module_activation_data['issue_customer_bill_vat_length']) ?
                                    $module_activation_data['issue_customer_bill_vat_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('issue_customer_bill_vat_activated_on', !empty($module_activation_data['issue_customer_bill_vat_activated_on']) ?
                                    $module_activation_data['issue_customer_bill_vat_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('issue_customer_bill_vat_expiry_date', !empty($module_activation_data['issue_customer_bill_vat_expiry_date']) ?
                                    $module_activation_data['issue_customer_bill_vat_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('issue_customer_bill_vat_price', !empty($module_activation_data['issue_customer_bill_vat_price']) ?
                                    $module_activation_data['issue_customer_bill_vat_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            
                            
                            <!--Additional modules-->
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label"> @lang('superadmin::lang.crm_module')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('crm_module', 1, !empty($manage_module_enable['crm_module']) ? true :
                                    false, ['class' => 'input-icheck-red ch_select'])
                                    !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['crm_module_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['crm_module_expiry_date']))
                                        @if(strtotime($module_activation_data['crm_module_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['crm_module_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('crm_module_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['crm_module_interval']) ?
                                    $module_activation_data['crm_module_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('crm_module_length', !empty($module_activation_data['crm_module_length']) ?
                                    $module_activation_data['crm_module_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('crm_module_activated_on', !empty($module_activation_data['crm_module_activated_on']) ?
                                    $module_activation_data['crm_module_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('crm_module_expiry_date', !empty($module_activation_data['crm_module_expiry_date']) ?
                                    $module_activation_data['crm_module_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('crm_module_price', !empty($module_activation_data['crm_module_price']) ?
                                    $module_activation_data['crm_module_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            
                            <!--Additional modules-->
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label"> @lang('superadmin::lang.ezyinvoice_module')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('ezyinvoice_module', 1, !empty($manage_module_enable['ezyinvoice_module']) ? true :
                                    false, ['class' => 'input-icheck-red ch_select'])
                                    !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['ezyinvoice_module_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['ezyinvoice_module_expiry_date']))
                                        @if(strtotime($module_activation_data['ezyinvoice_module_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['ezyinvoice_module_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('ezyinvoice_module_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['ezyinvoice_module_interval']) ?
                                    $module_activation_data['ezyinvoice_module_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('ezyinvoice_module_length', !empty($module_activation_data['ezyinvoice_module_length']) ?
                                    $module_activation_data['ezyinvoice_module_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('ezyinvoice_module_activated_on', !empty($module_activation_data['ezyinvoice_module_activated_on']) ?
                                    $module_activation_data['ezyinvoice_module_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('ezyinvoice_module_expiry_date', !empty($module_activation_data['ezyinvoice_module_expiry_date']) ?
                                    $module_activation_data['ezyinvoice_module_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('ezyinvoice_module_price', !empty($module_activation_data['ezyinvoice_module_price']) ?
                                    $module_activation_data['ezyinvoice_module_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label"> @lang('superadmin::lang.airline_module')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('airline_module', 1, !empty($manage_module_enable['airline_module']) ? true :
                                    false, ['class' => 'input-icheck-red ch_select'])
                                    !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['airline_module_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['airline_module_expiry_date']))
                                        @if(strtotime($module_activation_data['airline_module_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['airline_module_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('airline_module_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['airline_module_interval']) ?
                                    $module_activation_data['airline_module_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('airline_module_length', !empty($module_activation_data['airline_module_length']) ?
                                    $module_activation_data['airline_module_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('airline_module_activated_on', !empty($module_activation_data['airline_module_activated_on']) ?
                                    $module_activation_data['airline_module_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('airline_module_expiry_date', !empty($module_activation_data['airline_module_expiry_date']) ?
                                    $module_activation_data['airline_module_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('airline_module_price', !empty($module_activation_data['airline_module_price']) ?
                                    $module_activation_data['airline_module_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label"> @lang('superadmin::lang.shipping_module')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('shipping_module', 1, !empty($manage_module_enable['shipping_module']) ? true :
                                    false, ['class' => 'input-icheck-red ch_select'])
                                    !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['shipping_module_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['shipping_module_expiry_date']))
                                        @if(strtotime($module_activation_data['shipping_module_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['shipping_module_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('shipping_module_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['shipping_module_interval']) ?
                                    $module_activation_data['shipping_module_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('shipping_module_length', !empty($module_activation_data['shipping_module_length']) ?
                                    $module_activation_data['shipping_module_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('shipping_module_activated_on', !empty($module_activation_data['shipping_module_activated_on']) ?
                                    $module_activation_data['shipping_module_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('shipping_module_expiry_date', !empty($module_activation_data['shipping_module_expiry_date']) ?
                                    $module_activation_data['shipping_module_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('shipping_module_price', !empty($module_activation_data['shipping_module_price']) ?
                                    $module_activation_data['shipping_module_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label"> @lang('superadmin::lang.asset_module')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('asset_module', 1, !empty($manage_module_enable['asset_module']) ? true :
                                    false, ['class' => 'input-icheck-red ch_select'])
                                    !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['asset_module_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['asset_module_expiry_date']))
                                        @if(strtotime($module_activation_data['asset_module_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['asset_module_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('asset_module_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['asset_module_interval']) ?
                                    $module_activation_data['asset_module_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('asset_module_length', !empty($module_activation_data['asset_module_length']) ?
                                    $module_activation_data['asset_module_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('asset_module_activated_on', !empty($module_activation_data['asset_module_activated_on']) ?
                                    $module_activation_data['asset_module_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('asset_module_expiry_date', !empty($module_activation_data['asset_module_expiry_date']) ?
                                    $module_activation_data['asset_module_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('asset_module_price', !empty($module_activation_data['asset_module_price']) ?
                                    $module_activation_data['asset_module_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            
                            
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label"> @lang('superadmin::lang.access_module')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('access_module', 1, !empty($manage_module_enable['access_module']) ? true :
                                    false, ['class' => 'input-icheck-red ch_select'])
                                    !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['access_module_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['access_module_expiry_date']))
                                        @if(strtotime($module_activation_data['access_module_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['access_module_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('access_module_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['access_module_interval']) ?
                                    $module_activation_data['access_module_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('access_module_length', !empty($module_activation_data['access_module_length']) ?
                                    $module_activation_data['access_module_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('access_module_activated_on', !empty($module_activation_data['access_module_activated_on']) ?
                                    $module_activation_data['access_module_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('access_module_expiry_date', !empty($module_activation_data['access_module_expiry_date']) ?
                                    $module_activation_data['access_module_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('access_module_price', !empty($module_activation_data['access_module_price']) ?
                                    $module_activation_data['access_module_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label"> Installment Module</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('installment_module', 1, !empty($manage_module_enable['installment_module']) ? true :
                                    false, ['class' => 'input-icheck-red ch_select'])
                                    !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['installment_module_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['installment_module_expiry_date']))
                                        @if(strtotime($module_activation_data['installment_module_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['installment_module_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('installment_module_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['installment_module_interval']) ?
                                    $module_activation_data['installment_module_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('installment_module_length', !empty($module_activation_data['installment_module_length']) ?
                                    $module_activation_data['installment_module_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('installment_module_activated_on', !empty($module_activation_data['installment_module_activated_on']) ?
                                    $module_activation_data['installment_module_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('installment_module_expiry_date', !empty($module_activation_data['installment_module_expiry_date']) ?
                                    $module_activation_data['installment_module_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('installment_module_price', !empty($module_activation_data['installment_module_price']) ?
                                    $module_activation_data['installment_module_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                             <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label"> @lang('superadmin::lang.hospital_system')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('hospital_system', 1, !empty($manage_module_enable['hospital_system']) ?
                                    true :
                                    false, ['class' => 'input-icheck-red ch_select'])
                                    !!}
                                </div>
                                
                                <div class="col-md-1">
                                        @if(empty($module_activation_data['hospital_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['hospital_expiry_date']))
                                        @if(strtotime($module_activation_data['hospital_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['hospital_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::select('hospital_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['hospital_interval']) ?
                                        $module_activation_data['hospital_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('hospital_length', !empty($module_activation_data['hospital_length']) ?
                                        $module_activation_data['hospital_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                    
                                    <div class="col-md-2">
                                        {!! Form::date('hospital_activated_on', !empty($module_activation_data['hospital_activated_on']) ?
                                        $module_activation_data['hospital_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                    </div>
                                    <div class="col-md-2">
                                        {!! Form::date('hospital_expiry_date', !empty($module_activation_data['hospital_expiry_date']) ?
                                        $module_activation_data['hospital_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                    </div>
                                    <div class="col-md-2">
                                        {!! Form::text('hospital_price', !empty($module_activation_data['hospital_price']) ?
                                        $module_activation_data['hospital_price'] : null, ['class' => 'form-control']) !!}
                                    </div>
                                
                            </div>
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.enable_duplicate_invoice')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('enable_duplicate_invoice', 1,
                                    !empty($manage_module_enable['enable_duplicate_invoice']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['duplicate_invoice_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['duplicate_invoice_expiry_date']))
                                        @if(strtotime($module_activation_data['duplicate_invoice_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['duplicate_invoice_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('duplicate_invoice_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['duplicate_invoice_interval']) ?
                                    $module_activation_data['duplicate_invoice_interval'] : null, [
                                                'id' => 'status',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%',
                                                'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('duplicate_invoice_length', !empty($module_activation_data['duplicate_invoice_length']) ?
                                    $module_activation_data['duplicate_invoice_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('duplicate_invoice_activated_on', !empty($module_activation_data['duplicate_invoice_activated_on']) ?
                                    $module_activation_data['duplicate_invoice_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('duplicate_invoice_expiry_date', !empty($module_activation_data['duplicate_invoice_expiry_date']) ?
                                    $module_activation_data['duplicate_invoice_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('duplicate_invoice_price', !empty($module_activation_data['duplicate_invoice_price']) ?
                                    $module_activation_data['duplicate_invoice_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                                
                                
                            </div>
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('lang_v1.auto_services_and_repair_module')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('auto_services_and_repair_module', 1,
                                    !empty($manage_module_enable['auto_services_and_repair_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['auto_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['auto_expiry_date']))
                                        @if(strtotime($module_activation_data['auto_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['auto_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('auto_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['auto_interval']) ?
                                        $module_activation_data['auto_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('auto_length', !empty($module_activation_data['auto_length']) ?
                                        $module_activation_data['auto_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('auto_activated_on', !empty($module_activation_data['auto_activated_on']) ?
                                    $module_activation_data['auto_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('auto_expiry_date', !empty($module_activation_data['auto_expiry_date']) ?
                                    $module_activation_data['auto_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('auto_price', !empty($module_activation_data['auto_price']) ?
                                    $module_activation_data['auto_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                               
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('lang_v1.stock_taking_module')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('stock_taking_module', 1,
                                    !empty($manage_module_enable['stock_taking_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['stock_taking_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['stock_taking_expiry_date']))
                                        @if(strtotime($module_activation_data['stock_taking_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['stock_taking_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('stock_taking_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['stock_taking_interval']) ?
                                        $module_activation_data['stock_taking_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('stock_taking_length', !empty($module_activation_data['stock_taking_length']) ?
                                        $module_activation_data['stock_taking_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('stock_taking_activated_on', !empty($module_activation_data['stock_taking_activated_on']) ?
                                    $module_activation_data['stock_taking_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('stock_taking_expiry_date', !empty($module_activation_data['stock_taking_expiry_date']) ?
                                    $module_activation_data['stock_taking_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('stock_taking_price', !empty($module_activation_data['stock_taking_price']) ?
                                    $module_activation_data['stock_taking_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                               
                            </div>
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.property_module')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('property_module', 1,
                                    !empty($manage_module_enable['property_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['property_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['property_expiry_date']))
                                        @if(strtotime($module_activation_data['property_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['property_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('property_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['property_interval']) ?
                                        $module_activation_data['property_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('property_length', !empty($module_activation_data['property_length']) ?
                                        $module_activation_data['property_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('property_activated_on', !empty($module_activation_data['property_activated_on']) ?
                                    $module_activation_data['property_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('property_expiry_date', !empty($module_activation_data['property_expiry_date']) ?
                                    $module_activation_data['property_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('property_price', !empty($module_activation_data['property_price']) ?
                                    $module_activation_data['property_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.ran_module')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('ran_module', 1,
                                    !empty($manage_module_enable['ran_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['ran_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['ran_expiry_date']))
                                        @if(strtotime($module_activation_data['ran_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['ran_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('ran_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['ran_interval']) ?
                                        $module_activation_data['ran_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('ran_length', !empty($module_activation_data['ran_length']) ?
                                        $module_activation_data['ran_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('ran_activated_on', !empty($module_activation_data['ran_activated_on']) ?
                                    $module_activation_data['ran_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('ran_expiry_date', !empty($module_activation_data['ran_expiry_date']) ?
                                    $module_activation_data['ran_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('ran_price', !empty($module_activation_data['ran_price']) ?
                                    $module_activation_data['ran_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.notification_template_module')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('notification_template_module', 1,
                                    !empty($manage_module_enable['notification_template_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['notification_template_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['notification_template_expiry_date']))
                                        @if(strtotime($module_activation_data['notification_template_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['notification_template_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('notification_template_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['notification_template_interval']) ?
                                        $module_activation_data['notification_template_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('notification_template_length', !empty($module_activation_data['notification_template_length']) ?
                                        $module_activation_data['notification_template_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('notification_template_activated_on', !empty($module_activation_data['notification_template_activated_on']) ?
                                    $module_activation_data['notification_template_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('notification_template_expiry_date', !empty($module_activation_data['notification_template_expiry_date']) ?
                                    $module_activation_data['notification_template_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('notification_template_price', !empty($module_activation_data['notification_template_price']) ?
                                    $module_activation_data['notification_template_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.list_easy_payment')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('list_easy_payment', 1,
                                    !empty($manage_module_enable['list_easy_payment']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['list_easy_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['list_easy_expiry_date']))
                                        @if(strtotime($module_activation_data['list_easy_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['list_easy_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('list_easy_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['list_easy_interval']) ?
                                    $module_activation_data['list_easy_interval'] : null, [
                                                'id' => 'status',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%',
                                                'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('list_easy_length', !empty($module_activation_data['list_easy_length']) ?
                                    $module_activation_data['list_easy_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('list_easy_activated_on', !empty($module_activation_data['list_easy_activated_on']) ?
                                    $module_activation_data['list_easy_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('list_easy_expiry_date', !empty($module_activation_data['list_easy_expiry_date']) ?
                                    $module_activation_data['list_easy_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('list_easy_price', !empty($module_activation_data['list_easy_price']) ?
                                    $module_activation_data['list_easy_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                                
                                
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.user_management_module')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::hidden('user_management_module', 0) !!}
                                    {!! Form::checkbox('user_management_module', 1,
                                    !empty($manage_module_enable['user_management_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['um_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['um_expiry_date']))
                                        @if(strtotime($module_activation_data['um_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['um_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                
                                <div class="col-md-1">
                                        {!! Form::select('um_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['um_interval']) ?
                                        $module_activation_data['um_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('um_length', !empty($module_activation_data['um_length']) ?
                                        $module_activation_data['um_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                
                                <div class="col-md-2">
                                    {!! Form::date('um_activated_on', !empty($module_activation_data['um_activated_on']) ?
                                    $module_activation_data['um_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('um_expiry_date', !empty($module_activation_data['um_expiry_date']) ?
                                    $module_activation_data['um_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('um_price', !empty($module_activation_data['um_price']) ?
                                    $module_activation_data['um_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.banking_module')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('banking_module', 1,
                                    !empty($manage_module_enable['banking_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['banking_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['banking_expiry_date']))
                                        @if(strtotime($module_activation_data['banking_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['banking_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('banking_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['banking_interval']) ?
                                        $module_activation_data['banking_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('banking_length', !empty($module_activation_data['banking_length']) ?
                                        $module_activation_data['banking_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('banking_activated_on', !empty($module_activation_data['banking_activated_on']) ?
                                    $module_activation_data['banking_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('banking_expiry_date', !empty($module_activation_data['banking_expiry_date']) ?
                                    $module_activation_data['banking_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('banking_price', !empty($module_activation_data['banking_price']) ?
                                    $module_activation_data['banking_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                          
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.stock_transfer')</label>
                                </div>
                                <div class="col-md-1">
                                     {!! Form::checkbox('stock_transfer', 1,
                                    !empty($manage_module_enable['stock_transfer']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['stock_transfer_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['stock_transfer_expiry_date']))
                                        @if(strtotime($module_activation_data['stock_transfer_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['stock_transfer_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('stock_transfer_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['stock_transfer_interval']) ?
                                        $module_activation_data['stock_transfer_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('stock_transfer_length', !empty($module_activation_data['stock_transfer_length']) ?
                                        $module_activation_data['stock_transfer_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('stock_transfer_activated_on', !empty($module_activation_data['stock_transfer_activated_on']) ?
                                    $module_activation_data['stock_transfer_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('stock_transfer_expiry_date', !empty($module_activation_data['stock_transfer_expiry_date']) ?
                                    $module_activation_data['stock_transfer_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('stock_transfer_price', !empty($module_activation_data['stock_transfer_price']) ?
                                    $module_activation_data['stock_transfer_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                              
                            </div>
                           <hr>
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">Daily Report Review</label>
                                </div>
                                <div class="col-md-1">
                                     {!! Form::checkbox('daily_review', 1,
                                    !empty($manage_module_enable['daily_review']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['daily_review_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['daily_review_expiry_date']))
                                        @if(strtotime($module_activation_data['daily_review_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['daily_review_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('daily_review_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['daily_review_interval']) ?
                                        $module_activation_data['daily_review_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('daily_review_length', !empty($module_activation_data['daily_review_length']) ?
                                        $module_activation_data['daily_review_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('daily_review_activated_on', !empty($module_activation_data['daily_review_activated_on']) ?
                                    $module_activation_data['daily_review_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('daily_review_expiry_date', !empty($module_activation_data['daily_review_expiry_date']) ?
                                    $module_activation_data['daily_review_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('daily_review_price', !empty($module_activation_data['daily_review_price']) ?
                                    $module_activation_data['daily_review_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                              
                            </div>
                         </div>
                          
                    </div>
                    
                    {{-- Distribution Module Section --}}