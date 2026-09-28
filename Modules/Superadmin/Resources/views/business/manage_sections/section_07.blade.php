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
                            <h4> @lang('superadmin::lang.distribution_module')</h4>
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
                                    <label class="search_label">@lang('superadmin::lang.distribution_module')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('distribution_module', 0) !!}
                                    {!! Form::checkbox('distribution_module', 1,
                                            !empty($manage_module_enable['distribution_module']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select distribution_main_module']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['distribution_module_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['distribution_module_expiry_date']))
                                        @if(strtotime($module_activation_data['distribution_module_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['distribution_module_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                <div class="col-md-1">
                                    {!! Form::select('distribution_module_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['distribution_module_interval']) ?
                                    $module_activation_data['distribution_module_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('distribution_module_length', !empty($module_activation_data['distribution_module_length']) ?
                                    $module_activation_data['distribution_module_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('distribution_module_activated_on', !empty($module_activation_data['distribution_module_activated_on']) ?
                                    $module_activation_data['distribution_module_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('distribution_module_expiry_date', !empty($module_activation_data['distribution_module_expiry_date']) ?
                                    $module_activation_data['distribution_module_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('distribution_module_price', !empty($module_activation_data['distribution_module_price']) ?
                                    $module_activation_data['distribution_module_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            
                            {{-- Sub-permissions for Distribution Module --}}
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red"> {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    {!! Form::hidden('distribution_invoices', 0) !!}
                                    {!! Form::checkbox('distribution_invoices', 1,
                                    !empty($manage_module_enable['distribution_invoices']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.distribution_invoices')</label>
                                </div>
                                
                                <div class="col-md-4">
                                    {!! Form::hidden('distribution_loadings', 0) !!}
                                    {!! Form::checkbox('distribution_loadings', 1,
                                    !empty($manage_module_enable['distribution_loadings']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.distribution_loadings')</label>
                                </div>
                                
                                <div class="col-md-4">
                                    {!! Form::hidden('distribution_daily_summary', 0) !!}
                                    {!! Form::checkbox('distribution_daily_summary', 1,
                                    !empty($manage_module_enable['distribution_daily_summary']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.distribution_daily_summary')</label>
                                </div>
                                
                                <div class="col-md-4">
                                    {!! Form::hidden('distribution_vehicles', 0) !!}
                                    {!! Form::checkbox('distribution_vehicles', 1,
                                    !empty($manage_module_enable['distribution_vehicles']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.distribution_vehicles')</label>
                                </div>
                                
                                <div class="col-md-4">
                                    {!! Form::hidden('distribution_routes', 0) !!}
                                    {!! Form::checkbox('distribution_routes', 1,
                                    !empty($manage_module_enable['distribution_routes']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.distribution_routes')</label>
                                </div>
                                
                                <div class="col-md-4">
                                    {!! Form::hidden('distribution_agents', 0) !!}
                                    {!! Form::checkbox('distribution_agents', 1,
                                    !empty($manage_module_enable['distribution_agents']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.distribution_agents')</label>
                                </div>
                                
                                <div class="col-md-4">
                                    {!! Form::hidden('distribution_settings', 0) !!}
                                    {!! Form::checkbox('distribution_settings', 1,
                                    !empty($manage_module_enable['distribution_settings']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.distribution_settings')</label>
                                </div>
                                
                                <div class="col-md-4">
                                    {!! Form::hidden('distribution_reports', 0) !!}
                                    {!! Form::checkbox('distribution_reports', 1,
                                    !empty($manage_module_enable['distribution_reports']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.distribution_reports')</label>
                                </div>
                                
                                <div class="col-md-4">
                                    {!! Form::hidden('distribution_dashboard', 0) !!}
                                    {!! Form::checkbox('distribution_dashboard', 1,
                                    !empty($manage_module_enable['distribution_dashboard']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.distribution_dashboard')</label>
                                </div>
                                
                                <div class="col-md-4">
                                    {!! Form::hidden('distribution_vehicle_meters', 0) !!}
                                    {!! Form::checkbox('distribution_vehicle_meters', 1,
                                    !empty($manage_module_enable['distribution_vehicle_meters']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.distribution_vehicle_meters')</label>
                                </div>
                                <div class="col-md-4">
                                    {!! Form::hidden('distribution_free_issues', 0) !!}
                                    {!! Form::checkbox('distribution_free_issues', 1,
                                    !empty($manage_module_enable['distribution_free_issues']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.distribution_free_issues')</label>
                                </div>
                                
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_dis_invoice', 0) !!}
                                    {!! Form::checkbox('vat_dis_invoice', 1,
                                    !empty($manage_module_enable['vat_dis_invoice']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">VAT – Dis. Invoice</label>
                                </div>
                            </div>
                            
                            {{-- Distribution Configuration Options --}}
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <h5><b>@lang('superadmin::lang.distribution_configuration')</b></h5>
                                </div>
                                
                                <div class="col-md-4">
                                    {!! Form::hidden('distribution_show_date_picker', 0) !!}
                                    {!! Form::checkbox('distribution_show_date_picker', 1,
                                    !empty($manage_module_enable['distribution_show_date_picker']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.distribution_show_date_picker')</label>
                                </div>
                                
                                <div class="col-md-4">
                                    {!! Form::hidden('distribution_auto_date_time', 0) !!}
                                    {!! Form::checkbox('distribution_auto_date_time', 1,
                                    !empty($manage_module_enable['distribution_auto_date_time']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.distribution_auto_date_time')</label>
                                </div>

<div class="col-md-4">
    <div class="form-group">
        {!! Form::label('distribution_free', 'Free') !!}
        {!! Form::select('distribution_free', ['No' => 'No', 'Yes' => 'Yes'],
            $manage_module_enable['distribution_free'] ?? 'No',
            ['class' => 'form-control']) !!}
    </div>
</div>

<div class="col-md-4">
    <div class="form-group">
        {!! Form::label('distribution_free_bottles', 'Free Bottles') !!}
        {!! Form::select('distribution_free_bottles', ['No' => 'No', 'Yes' => 'Yes'],
            $manage_module_enable['distribution_free_bottles'] ?? 'No',
            ['class' => 'form-control']) !!}
    </div>
</div>

                            </div>
                            
                            {{-- Distribution Limits --}}
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <h5><b>@lang('superadmin::lang.distribution_limits')</b></h5>
                                </div>
                                
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        {!! Form::label('vehicle_count', __('superadmin::lang.no_of_vehicles').':') !!}
                                        {!! Form::number('vehicle_count', !empty($manage_module_enable['vehicle_count']) ? $manage_module_enable['vehicle_count']
                                        : $previous_package_data['vehicle_count'], ['class' => 'form-control', 'required', 'min' =>
                                        0]) !!}
    
                                        <span class="help-block">
                                        @lang('superadmin::lang.infinite_help')
                                    </span>
                                    </div>
                                </div>
                                
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        {!! Form::label('category_count', __('superadmin::lang.no_of_product_categories').':') !!}
                                        {!! Form::number('category_count', !empty($manage_module_enable['category_count']) ? $manage_module_enable['category_count']
                                        : $previous_package_data['category_count'], ['class' => 'form-control', 'required', 'min' =>
                                        0]) !!}
    
                                        <span class="help-block">
                                        @lang('superadmin::lang.infinite_help')
                                    </span>
                                    </div>
                                </div>
                            </div>
                          </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                    
                    <div class="row">
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">Spreadsheet Module</label>
                                </div>
                                <div class="col-md-1">
                                     {!! Form::checkbox('spreadsheet', 1,
                                    !empty($manage_module_enable['spreadsheet']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['spreadsheet_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['spreadsheet_expiry_date']))
                                        @if(strtotime($module_activation_data['spreadsheet_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['spreadsheet_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('spreadsheet_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['spreadsheet_interval']) ?
                                        $module_activation_data['spreadsheet_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('spreadsheet_length', !empty($module_activation_data['spreadsheet_length']) ?
                                        $module_activation_data['spreadsheet_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('spreadsheet_activated_on', !empty($module_activation_data['spreadsheet_activated_on']) ?
                                    $module_activation_data['spreadsheet_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('spreadsheet_expiry_date', !empty($module_activation_data['spreadsheet_expiry_date']) ?
                                    $module_activation_data['spreadsheet_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('spreadsheet_price', !empty($module_activation_data['spreadsheet_price']) ?
                                    $module_activation_data['spreadsheet_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                              
                            </div>
                            
                            <hr>
                           
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.service_staff')</label>
                                </div>
                                <div class="col-md-1">
                                     {!! Form::checkbox('service_staff', 1,
                                    !empty($manage_module_enable['service_staff']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['service_staff_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['service_staff_expiry_date']))
                                        @if(strtotime($module_activation_data['service_staff_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['service_staff_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('service_staff_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['service_staff_interval']) ?
                                        $module_activation_data['service_staff_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('service_staff_length', !empty($module_activation_data['service_staff_length']) ?
                                        $module_activation_data['service_staff_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('service_staff_activated_on', !empty($module_activation_data['service_staff_activated_on']) ?
                                    $module_activation_data['service_staff_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('service_staff_expiry_date', !empty($module_activation_data['service_staff_expiry_date']) ?
                                    $module_activation_data['service_staff_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('service_staff_price', !empty($module_activation_data['service_staff_price']) ?
                                    $module_activation_data['service_staff_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                              
                            </div>
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.enable_subscription')</label>
                                </div>
                                <div class="col-md-1">
                                     {!! Form::checkbox('enable_subscription', 1,
                                    !empty($manage_module_enable['enable_subscription']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['enable_subscription_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['enable_subscription_expiry_date']))
                                        @if(strtotime($module_activation_data['enable_subscription_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['enable_subscription_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('enable_subscription_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['enable_subscription_interval']) ?
                                        $module_activation_data['enable_subscription_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('enable_subscription_length', !empty($module_activation_data['enable_subscription_length']) ?
                                        $module_activation_data['enable_subscription_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('enable_subscription_activated_on', !empty($module_activation_data['enable_subscription_activated_on']) ?
                                    $module_activation_data['enable_subscription_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('enable_subscription_expiry_date', !empty($module_activation_data['enable_subscription_expiry_date']) ?
                                    $module_activation_data['enable_subscription_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('enable_subscription_price', !empty($module_activation_data['enable_subscription_price']) ?
                                    $module_activation_data['enable_subscription_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                              
                            </div>
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.pump_operator_dashboard')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('pump_operator_dashboard', 1,
                                    !empty($manage_module_enable['pump_operator_dashboard']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['pump_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['pump_expiry_date']))
                                        @if(strtotime($module_activation_data['pump_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['pump_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('pump_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['pump_interval']) ?
                                    $module_activation_data['pump_interval'] : null, [
                                                'id' => 'status',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%',
                                                'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('pump_length', !empty($module_activation_data['pump_length']) ?
                                    $module_activation_data['pump_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('pump_activated_on', !empty($module_activation_data['pump_activated_on']) ?
                                    $module_activation_data['pump_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('pump_expiry_date', !empty($module_activation_data['pump_expiry_date']) ?
                                    $module_activation_data['pump_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('pump_price', !empty($module_activation_data['pump_price']) ?
                                    $module_activation_data['pump_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                               
                                </div>
                                
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('lang_v1.customer_interest_deduction')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('customer_interest_module', 1,
                                    !empty($manage_module_enable['customer_interest_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['customer_interest_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['customer_interest_expiry_date']))
                                        @if(strtotime($module_activation_data['customer_interest_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['customer_interest_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('customer_interest_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['customer_interest_interval']) ?
                                    $module_activation_data['customer_interest_interval'] : null, [
                                                'id' => 'status',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%',
                                                'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('customer_interest_length', !empty($module_activation_data['customer_interest_length']) ?
                                    $module_activation_data['customer_interest_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('customer_interest_activated_on', !empty($module_activation_data['customer_interest_activated_on']) ?
                                    $module_activation_data['customer_interest_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('customer_interest_expiry_date', !empty($module_activation_data['customer_interest_expiry_date']) ?
                                    $module_activation_data['customer_interest_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('customer_interest_price', !empty($module_activation_data['customer_interest_price']) ?
                                    $module_activation_data['customer_interest_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                               
                                </div>
                                
                            {{-- IS1962: Pumper Dashboard / Payments / Card.
                                 On  - a slip number already used for a card payment in this
                                       business is refused, with "Duplicate Slip Number, Please Check".
                                 Off - duplicates are allowed, which is the existing behaviour. --}}
                            <hr>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="search_label">Do not Allow Duplicate Slip Numbers</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('do_not_allow_duplicate_slip_no', 1,
                                    !empty($manage_module_enable['do_not_allow_duplicate_slip_no']) ? true : false,
                                    ['class' => 'input-icheck-red ch_select']) !!}
                                </div>
                            </div>

                            <hr>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('lang_v1.day_end_module')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('day_end_module', 1,
                                    !empty($manage_module_enable['day_end_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['day_end_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['day_end_expiry_date']))
                                        @if(strtotime($module_activation_data['day_end_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['day_end_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('day_end_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['day_end_interval']) ?
                                    $module_activation_data['day_end_interval'] : null, [
                                                'id' => 'status',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%',
                                                'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('day_end_length', !empty($module_activation_data['day_end_length']) ?
                                    $module_activation_data['day_end_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('day_end_activated_on', !empty($module_activation_data['day_end_activated_on']) ?
                                    $module_activation_data['day_end_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('day_end_expiry_date', !empty($module_activation_data['day_end_expiry_date']) ?
                                    $module_activation_data['day_end_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('day_end_price', !empty($module_activation_data['day_end_price']) ?
                                    $module_activation_data['day_end_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                               
                                </div>
                            
                            
                            
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> Other Permissions</h4>
                            <hr>
                          </div>
                          <div class="card-body">
                              <div class="row">
                                    <div class="col-md-2">
                                        <label class="search_label">@lang('superadmin::lang.enable_crm')</label>
                                    </div>
                                    <div class="col-md-2">
                                    {!! Form::hidden('enable_crm', 0) !!}
                                        {!! Form::checkbox('enable_crm', 1, !empty($manage_module_enable['enable_crm']) ? true :
                                        false,
                                        ['class' => 'input-icheck-red ch_select']) !!}
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label class="search_label">@lang('superadmin::lang.catalogue_qr')</label>
                                    </div>
                                    <div class="col-md-2">
                                    {!! Form::hidden('catalogue_qr', 0) !!}
                                        {!! Form::checkbox('catalogue_qr', 1,
                                        !empty($manage_module_enable['catalogue_qr']) ? true : false, ['class' =>
                                        'input-icheck-red
                                        ch_select']) !!}
                                    </div>
          
                                    <div class="col-md-2">
                                        <label class="search_label">@lang('superadmin::lang.enable_sale_cmsn_agent')</label>
                                    </div>
                                    <div class="col-md-2">
                                    {!! Form::hidden('enable_sale_cmsn_agent', 0) !!}
                                        {!! Form::checkbox('enable_sale_cmsn_agent', 1,
                                        !empty($manage_module_enable['enable_sale_cmsn_agent']) ? true : false, ['class' =>
                                        'input-icheck-red
                                        ch_select']) !!}
                                    </div>
                                    
                                </div><hr>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.monthly_total_sales_volumn')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('monthly_total_sales_volumn', 0) !!}
                                    {!! Form::checkbox('monthly_total_sales_volumn', 1,
                                    !empty($manage_module_enable['monthly_total_sales_volumn']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.customer_order_own_customer')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('customer_order_own_customer', 0) !!}
                                    {!! Form::checkbox('customer_order_own_customer', 1,
                                    !empty($manage_module_enable['customer_order_own_customer']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.customer_settings')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('customer_settings', 0) !!}
                                    {!! Form::checkbox('customer_settings', 1,
                                    !empty($manage_module_enable['customer_settings']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                            </div> <hr>   
                            <div class="row">    
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.customer_order_general_customer')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('customer_order_general_customer', 0) !!}
                                    {!! Form::checkbox('customer_order_general_customer', 1,
                                    !empty($manage_module_enable['customer_order_general_customer']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                 <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.customer_to_directly_in_panel')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('customer_to_directly_in_panel', 0) !!}
                                    {!! Form::checkbox('customer_to_directly_in_panel', 1,
                                    !empty($manage_module_enable['customer_to_directly_in_panel']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.member_registration')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('member_registration', 0) !!}
                                    {!! Form::checkbox('member_registration', 1,
                                    !empty($manage_module_enable['member_registration']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                            </div><hr>
                             <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('contact.enable_separate_customer_statement_no')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('enable_separate_customer_statement_no', 0) !!}
                                    {!! Form::checkbox('enable_separate_customer_statement_no', 1,
                                    !empty($manage_module_enable['enable_separate_customer_statement_no']) ? true : false,
                                    ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="search_label">@lang('contact.edit_customer_statement')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('edit_customer_statement', 0) !!}
                                    {!! Form::checkbox('edit_customer_statement', 1,
                                    !empty($manage_module_enable['edit_customer_statement']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>

                                <div class="col-md-2">
                                    <label class="search_label">Dashboard Logistics</label>
                                </div>
                                <div class="col-md-2">
                                    {!! Form::hidden('dashboard_logistics', 0) !!}
                                    {!! Form::checkbox(
                                        'dashboard_logistics',
                                        1,
                                        !empty($manage_module_enable['dashboard_logistics']) ? true : false,
                                        ['class' => 'input-icheck-red ch_select']
                                    ) !!}
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
                                
                                
                                
                            </div><hr>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.home_dashboard')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('home_dashboard', 0) !!}
                                    {!! Form::checkbox('home_dashboard', 1,
                                    !empty($manage_module_enable['home_dashboard']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                {{-- <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.stock_adjustment')</label>
                                </div> --}}
                                {{-- <div class="col-md-2">
                                {!! Form::hidden('stock_adjustment', 0) !!}
                                    {!! Form::checkbox('stock_adjustment', 1,
                                    !empty($manage_module_enable['stock_adjustment']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div> --}}
                                
                                 <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.tables')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('tables', 0) !!}
                                    {!! Form::checkbox('tables', 1,
                                    !empty($manage_module_enable['tables']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                            </div><hr>
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.type_of_service')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('type_of_service', 0) !!}
                                    {!! Form::checkbox('type_of_service', 1,
                                    !empty($manage_module_enable['type_of_service']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.expenses')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('expenses', 0) !!}
                                    {!! Form::checkbox('expenses', 1,
                                    !empty($manage_module_enable['expenses']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.modifiers')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('modifiers', 0) !!}
                                    {!! Form::checkbox('modifiers', 1,
                                    !empty($manage_module_enable['modifiers']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                
                                
                            </div><hr>
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.kitchen')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('kitchen', 0) !!}
                                    {!! Form::checkbox('kitchen', 1,
                                    !empty($manage_module_enable['kitchen']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.cache_clear')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('cache_clear', 0) !!}
                                    {!! Form::checkbox('cache_clear', 1,
                                    !empty($manage_module_enable['cache_clear']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.customer_interest_deduct_option')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('customer_interest_deduct_option', 0) !!}
                                    {!! Form::checkbox('customer_interest_deduct_option', 1,
                                    !empty($manage_module_enable['customer_interest_deduct_option']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                               
                            </div><hr>
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.upload_images')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('upload_images', 0) !!}
                                    {!! Form::checkbox('upload_images', 1,
                                    !empty($manage_module_enable['upload_images']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.dsr_module')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('dsr_module', 0) !!}
                                    {!! Form::checkbox('dsr_module', 1,
                                    !empty($manage_module_enable['dsr_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.discount_module')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('discount_module', 0) !!}
                                    {!! Form::checkbox('discount_module', 1,
                                    !empty($manage_module_enable['discount_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.tpos_module')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('tpos_module', 0) !!}
                                    {!! Form::checkbox('tpos_module', 1,
                                    !empty($manage_module_enable['tpos_module']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                    
                                </div> 
                                
                                
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.stock_conversion_module')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('stock_conversion_module', 0) !!}
                                    {!! Form::checkbox('stock_conversion_module', 1,
                                    !empty($manage_module_enable['stock_conversion_module']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                    
                                </div> 
                                
                                
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.list_credit_sales_page')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('list_credit_sales_page', 0) !!}
                                    {!! Form::checkbox('list_credit_sales_page', 1,
                                    !empty($manage_module_enable['list_credit_sales_page']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                    
                                </div> 
                                
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.docmanagement_module')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('docmanagement_module', 0) !!}
                                    {!! Form::checkbox('docmanagement_module', 1,
                                    !empty($manage_module_enable['docmanagement_module']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                    
                                </div> 
                                 
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.duplicate_slip_numbers')</label>
                                </div>
                                <div class="col-md-2">
                                {!! Form::hidden('duplicate_slip_numbers', 0) !!}
                                    {!! Form::checkbox('duplicate_slip_numbers', 1,
                                    !empty($manage_module_enable['duplicate_slip_numbers']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                    
                                </div>  
                                
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.development')</label>
                                </div>
                                <div class="col-md-2">
                                    {!! Form::hidden('development', 0) !!}
                                    {!! Form::checkbox('development', 1,
                                    !empty($manage_module_enable['development']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}

                                </div>
                            </div>

                            <div class="row mt-2 doc_management_settings">
                                <div class="col-md-3">
                                    <label>@lang('superadmin::lang.documents_per_month')</label>
                                    {!! Form::number(
                                        'doc_monthly_limit',
                                        $business->doc_monthly_limit ?? null,
                                        ['class' => 'form-control', 'min' => 1]
                                    ) !!}
                                </div>

                                <div class="col-md-3">
                                    <label>@lang('superadmin::lang.valid_from')</label>
                                    {!! Form::date(
                                        'doc_valid_from',
                                        $business->doc_valid_from ?? null,
                                        ['class' => 'form-control']
                                    ) !!}
                                </div>

                                <div class="col-md-3">
                                    <label>@lang('superadmin::lang.valid_till')</label>
                                    {!! Form::date(
                                        'doc_valid_to',
                                        $business->doc_valid_to ?? null,
                                        ['class' => 'form-control']
                                    ) !!}
                                </div>
                            </div>

                            
                            
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                      <div class="card-header text-center">
                        <h4> Payment Options</h4>
                        <hr>
                      </div>
                      <div class="card-body">
                          <div class="row">
                        <div class="col-sm-12">
                        <label class="search_label">@lang('lang_v1.payment_methods'):</label>
                 
                    @foreach (($payment_business_locations ?? $business_locations) as $business_location)
                        <!--Accordion wrapper-->
                            <div class="accordion md-accordion" id="accordionEx{{$business_location->id}}" role="tablist"
                                 aria-multiselectable="true">

                                <div class="card">
                                    <div class="card-header" role="tab" id="headingTwo{{$business_location->id}}">
                                        <a class="collapsed" data-toggle="collapse"
                                           data-parent="#accordionEx{{$business_location->id}}"
                                           href="#collapseTwo{{$business_location->id}}" aria-expanded="false"
                                           aria-controls="collapseTwo{{$business_location->id}}">
                                            <h5 class="mb-0 text-black">
                                                <label class="search_label">{{$business_location->name}}</label>
                                                <i class="fa fa-angle-down rotate-icon pull-right"></i>
                                            </h5>
                                        </a>
                                    </div>
                                    <div id="collapseTwo{{$business_location->id}}" class="collapse" role="tabpanel"
                                         aria-labelledby="headingTwo{{$business_location->id}}"
                                         data-parent="#accordionEx{{$business_location->id}}">
                                        <div class="card-body" style="margin-bottom: 10px;">
                                            <hr>
                                            @php
                                                $default_payment_accounts = json_decode($business_location->default_payment_accounts);
                                                $default_payment_accounts = is_object($default_payment_accounts) ? $default_payment_accounts : (object) [];
                                                $__paymentNonMethodKeys = ['location_id', 'business_id', 'id', '_token', '_method'];
                                            @endphp
                                            <table class="table table-condensed table-striped">
                                                <thead>
                                                <tr>
                                                    <th class="text-center">@lang('lang_v1.payment_method')</th>
                                                    <th class="text-center">@lang('lang_v1.enable')</th>
                                                    
                                                    <th class="text-center">@lang('superadmin::lang.purchases')</th>
                                                    <th class="text-center">@lang('superadmin::lang.sales')</th>
                                                    <th class="text-center">@lang('superadmin::lang.expenses')</th>
                                                    <th class="text-center">@lang('superadmin::lang.purchase_return')</th>
                                                    <th class="text-center">@lang('superadmin::lang.sales_return')</th>
                                                    
                                                    <th class="text-center @if(empty($accounts)) hide @endif">
                                                        @lang('lang_v1.default_account_groups')
                                                        @show_tooltip(__('lang_v1.default_account_help'))</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                
                                                @php $i = 0; @endphp   
                                                @foreach($default_payment_accounts as $key => $value)
                                                    @php
                                                        $__paymentTechnicalKey = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', (string) $key), '_'));
                                                    @endphp
                                                    @continue(in_array($__paymentTechnicalKey, $__paymentNonMethodKeys, true))
                                                    <tr>
                                                        <td class="text-center">{{ucfirst($key)}}</td>
                                                        <td class="text-center">
                                                            <input type="hidden" name="default_payment_accounts[{{$business_location->id}}][name][{{$i}}]" value="{{$key}}">
                                                            {!! Form::select('default_payment_accounts['.$business_location->id.'][is_enabled]['.$i.']', ['0' => 'Not Active','1' => 'Active'],!empty($default_payment_accounts->$key->is_enabled) ? $default_payment_accounts->$key->is_enabled : 0,['class' => 'form-control input-sm','placeholder' => __('superadmin::lang.please_select'),'required']) !!}</td>
                                                        
                                                        <td>
                                                            {!! Form::hidden('default_payment_accounts['.$business_location->id.'][is_purchase_enabled]['.$i.']', 0) !!}
                                                            {!! Form::checkbox('default_payment_accounts['.$business_location->id.'][is_purchase_enabled]['.$i.']', 1, 
                                                                !empty($default_payment_accounts->$key->is_purchase_enabled) ?
                                                                $default_payment_accounts->$key->is_purchase_enabled : 0, ['class' => 'form-control input-sm']) !!}
                                                        </td>
                                                        
                                                        <td>
                                                            {!! Form::hidden('default_payment_accounts['.$business_location->id.'][is_sale_enabled]['.$i.']', 0) !!}
                                                            {!! Form::checkbox('default_payment_accounts['.$business_location->id.'][is_sale_enabled]['.$i.']', 1, 
                                                                !empty($default_payment_accounts->$key->is_sale_enabled) ?
                                                                $default_payment_accounts->$key->is_sale_enabled : 0, ['class' => 'form-control input-sm']) !!}
                                                        </td>
                                                        
                                                        <td>
                                                            {!! Form::hidden('default_payment_accounts['.$business_location->id.'][is_expense_enabled]['.$i.']', 0) !!}
                                                            {!! Form::checkbox('default_payment_accounts['.$business_location->id.'][is_expense_enabled]['.$i.']', 1, 
                                                                !empty($default_payment_accounts->$key->is_expense_enabled) ?
                                                                $default_payment_accounts->$key->is_expense_enabled : 0, ['class' => 'form-control input-sm']) !!}
                                                        </td>
                                                        
                                                        <td>
                                                            {!! Form::hidden('default_payment_accounts['.$business_location->id.'][is_purchase_return_enabled]['.$i.']', 0) !!}
                                                            {!! Form::checkbox('default_payment_accounts['.$business_location->id.'][is_purchase_return_enabled]['.$i.']', 1, 
                                                                !empty($default_payment_accounts->$key->is_purchase_return_enabled) ?
                                                                $default_payment_accounts->$key->is_purchase_return_enabled : 0, ['class' => 'form-control input-sm']) !!}
                                                        </td>
                                                        
                                                        <td>
                                                            {!! Form::hidden('default_payment_accounts['.$business_location->id.'][is_sale_return_enabled]['.$i.']', 0) !!}
                                                            {!! Form::checkbox('default_payment_accounts['.$business_location->id.'][is_sale_return_enabled]['.$i.']', 1, 
                                                                !empty($default_payment_accounts->$key->is_sale_return_enabled) ?
                                                                $default_payment_accounts->$key->is_sale_return_enabled : 0, ['class' => 'form-control input-sm']) !!}
                                                        </td>

                                                        
                                                        <td class="text-center @if(empty($accounts)) hide @endif">
                                                            {!! Form::select('default_payment_accounts['.$business_location->id.'][account]['.$i.']', ($payment_account_groups ?? $account_groups),
                                                            !empty($default_payment_accounts->$key->account) ?
                                                            $default_payment_accounts->$key->account :
                                                            null, ['class' => 'form-control input-sm select2', 'id' => 'account_'.$key,'placeholder' => __('superadmin::lang.please_select'),'required'])
                                                            !!}
                                                        </td>
                                                        <td>
                                                            @if($i == 0 || sizeof(json_decode($business_location->default_payment_accounts,true)) == 1)
                                                                    <button type="button" class="btn btn-success add-row" data-id="{{$business_location->id}}"> + </button>
                                                            @endif
                                                            @if(!empty($default_payment_accounts->$key->is_custom) && $default_payment_accounts->$key->is_custom == 1)
                                                                <button type="button" class="btn btn-danger remove-row"> - </button>
                                                                <input type="hidden" name="default_payment_accounts[{{$business_location->id}}][is_custom][{{$i}}]" value="1">
                                                            @else
                                                                <input type="hidden" name="default_payment_accounts[{{$business_location->id}}][is_custom][{{$i}}]" value="0">
                                                                
                                                            @endif 
                                                        </td>
                                                        
                                                    </tr>
                                                    @php $i++; @endphp
                                                @endforeach
                                                </tbody>
                                            </table>
                                            <hr>
                                        </div>
                                    </div>

                                </div>

                            </div>
                        @endforeach
                    </div>
                    </div>
                     </div>
                      
                </div>
                
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> General</h4>
                            <hr>
                          </div>
                          <div class="card-body">
                              <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>@lang('superadmin::lang.login_page_showing_type')</label>
                                        {!! Form::select('background_showing_type', ['only_background_image'
                                        =>__('superadmin::lang.only_background_image'), 'background_image_and_logo' =>
                                        __('superadmin::lang.background_image_and_logo')],
                                        $business_details->background_showing_type , ['class' => 'form-control',
                                        'placeholder' => __('superadmin::lang.please_select')]) !!}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('background_image', __( 'superadmin::lang.background_image' ) . ':') !!}
                                        {!! Form::file('background_image', ['accept' => 'image/*']) !!}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('logo', __( 'superadmin::lang.logo' ) . ':') !!}
                                        {!! Form::file('logo', ['accept' => 'image/*']) !!}
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <h3> @lang('superadmin::lang.with_variables')</h3>
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-md-5">

                            </div>
                            <div class="col-md-3">
                                {!! Form::label('current_value', __('superadmin::lang.current_values'), ['class' =>
                                'search_label']) !!}
                            </div>
                            <div class="col-md-4">

                            </div>
                        </div>
                        </br>
                        <div class="row">
                            <div class="col-md-5">
                                <label class="search_label">@lang('superadmin::lang.number_of_branches')</label>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::number('current_values[number_of_branches]',
                                    !empty($current_values['number_of_branches']) ? $current_values['number_of_branches'] :
                                    null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary btn-xs btn-modal" data-container=".option_modal"
                                        data-href="{{action('\Modules\Superadmin\Http\Controllers\CompanyPackageVariableController@getOptionVariables', [ 'id' => '0', 'business_id' => $business->id])}}">@lang('superadmin::lang.enter_variables')</button>
                            </div>
                        </div>
                        </br>
                        <div class="row">
                            <div class="col-md-5">
                                <label class="search_label">@lang('superadmin::lang.number_of_users')</label>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::number('current_values[number_of_users]',
                                    !empty($current_values['number_of_users']) ? $current_values['number_of_users'] : null,
                                    ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary btn-xs btn-modal" data-container=".option_modal"
                                        data-href="{{action('\Modules\Superadmin\Http\Controllers\CompanyPackageVariableController@getOptionVariables', [ 'id' => '1', 'business_id' => $business->id])}}">@lang('superadmin::lang.enter_variables')</button>
                            </div>
                        </div>
                        </br>
                        <div class="row">
                            <div class="col-md-5">
                                <label class="search_label">@lang('superadmin::lang.number_of_customers')</label>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::number('current_values[number_of_customers]',
                                    !empty($current_values['number_of_customers']) ? $current_values['number_of_customers'] :
                                    null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary btn-xs btn-modal" data-container=".option_modal"
                                        data-href="{{action('\Modules\Superadmin\Http\Controllers\CompanyPackageVariableController@getOptionVariables', [ 'id' => '4', 'business_id' => $business->id])}}">@lang('superadmin::lang.enter_variables')</button>
                            </div>
                        </div>
                        </br>
                        <div class="row">
                            <div class="col-md-5">
                                <label class="search_label">@lang('superadmin::lang.number_of_products')</label>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::number('current_values[number_of_products]',
                                    !empty($current_values['number_of_products']) ? $current_values['number_of_products'] :
                                    null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary btn-xs btn-modal" data-container=".option_modal"
                                        data-href="{{action('\Modules\Superadmin\Http\Controllers\CompanyPackageVariableController@getOptionVariables', [ 'id' => '2', 'business_id' => $business->id])}}">@lang('superadmin::lang.enter_variables')</button>
                            </div>
                        </div>
                        </br>
                        <div class="row">
                            <div class="col-md-5">
                                <label class="search_label">@lang('superadmin::lang.number_of_periods')</label>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::number('current_values[number_of_periods]',
                                    !empty($current_values['number_of_periods']) ? $current_values['number_of_periods'] : null,
                                    ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary btn-xs btn-modal" data-container=".option_modal"
                                        data-href="{{action('\Modules\Superadmin\Http\Controllers\CompanyPackageVariableController@getOptionVariables', [ 'id' => '3', 'business_id' => $business->id])}}">@lang('superadmin::lang.enter_variables')</button>
                            </div>
                        </div>
                        </br>
                        <div class="row">
                            <div class="col-md-5">
                                <label class="search_label">@lang('superadmin::lang.number_of_stores')</label>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::number('current_values[number_of_stores]',
                                    !empty($current_values['number_of_stores']) ? $current_values['number_of_stores'] : null,
                                    ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary btn-xs btn-modal" data-container=".option_modal"
                                        data-href="{{action('\Modules\Superadmin\Http\Controllers\CompanyPackageVariableController@getOptionVariables', [ 'id' => '5', 'business_id' => $business->id])}}">@lang('superadmin::lang.enter_variables')</button>
                            </div>
                        </div>
                    </div>
                            </div>
                         </div>
                          
                    </div>