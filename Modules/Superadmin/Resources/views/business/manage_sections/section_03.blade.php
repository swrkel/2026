{{--
    MA-002: extracted from business/manage.blade.php, which was 14,602 lines.

    The lines below are BYTE-IDENTICAL to the original - nothing was rewritten,
    reindented or reordered. The parent file @includes this at exactly the same
    point, so the rendered page is unchanged.

    Only blocks whose own tags balance were moved. Three blocks in the original
    do not close their divs within their own boundaries, so they stay in the
    parent rather than risk moving markup that depends on what surrounds it.
--}}
                            <div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                                <div class="card-header text-center">
                                    <h4> @lang('superadmin::lang.membership_module')</h4>
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
                                            <label class="search_label">@lang('superadmin::lang.membership_module')</label>
                                        </div>
                                        
                                        <div class="col-md-1">
                                            <label></label>
                                            {!! Form::hidden('membership_module', 0) !!}
                                            {!! Form::checkbox('membership_module', 1, !empty($manage_module_enable['membership_module']) ? true : false,
                                            ['class' => 'input-icheck-red ch_select membership_module', 'id' => 'membership_module_checkbox']) !!}
                                        </div>
                                        
                                        
                                        <div class="col-md-1">
                                            @if(empty($module_activation_data['membership_module_expiry_date']))
                                            <span class="badge badge-danger">not set</span>
                                            @endif
                                            @if(!empty($module_activation_data['membership_module_expiry_date']))
                                                @if(strtotime($module_activation_data['membership_module_expiry_date']) >= time())
                                                    <span class="label label-pill label-primary">active</span>
                                                @endif
                                                
                                                @if(strtotime($module_activation_data['membership_module_expiry_date']) < time())
                                                    <span class="label label-pill label-danger">expired</span>
                                                @endif
                                                
                                            @endif
                                        </div>
                                        
                                        <div class="col-md-1">
                                            {!! Form::select('mf_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['real_time_entries_interval']) ?
                                            $module_activation_data['membership_module_interval'] : 'Years', [
                                                        'id' => 'mf_interval',
                                                        'class' => 'form-control act_interval',
                                                        'style' => 'width:100%'
                                                    ])
                                                !!}   
                                
                                        </div>
                                        
                                        <div class="col-md-1">
                                            {!! Form::number('mf_length', !empty($module_activation_data['membership_module_length']) ?
                                            $module_activation_data['membership_module_length'] : 1, ['class' => 'form-control act_length', 'id' => 'mf_length', 'min' => 1]) !!}
                                        </div>
                                        
                                        <div class="col-md-2">
                                            {!! Form::date('membership_module_activated_on', !empty($module_activation_data['membership_module_activated_on']) ?
                                            $module_activation_data['membership_module_activated_on'] : \Carbon\Carbon::today()->format('Y-m-d'), ['class' => 'form-control act_on', 'id' => 'mf_activated_on']) !!}
                                        </div>
                                        <div class="col-md-2">
                                            {!! Form::date('membership_module_expiry_date', !empty($module_activation_data['membership_module_expiry_date']) ?
                                            $module_activation_data['membership_module_expiry_date'] : null, ['class' => 'form-control act_expiry', 'id' => 'membership_module_expiry_date', 'disabled' => 'disabled']) !!}
                                        </div>
                                        <div class="col-md-2">
                                            {!! Form::text('membership_module_price', !empty($module_activation_data['membership_module_price']) ?
                                            $module_activation_data['membership_module_price'] : null, ['class' => 'form-control', 'id' => 'membership_module_price']) !!}
                                        </div>
                                    </div>
                                    <div class="row membership_module_module_locations check_group">
                                        <div class="col-md-3">
                                            <p style="padding-top: 9px;"><b>@lang('superadmin::lang.select_locations'): </b></p>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" class="check_all input-icheck-red"> {{ __( 'role.select_all' ) }}
                                                </label>
                                            </div>
                                        </div>
                                        <br>
                                        @foreach ($business_locations as $location)
                                            <div class="col-md-3">
                                                <div class="checkbox">
                                                    <label>
                                                        {!! Form::checkbox('module_permission_location[membership_module]['.$location->id.']', 1,
                                                        !empty($module_permission_locations_value['membership_module']->locations) ?
                                                        array_key_exists($location->id,
                                                        $module_permission_locations_value['membership_module']->locations) : false, ['class' =>
                                                        'input-icheck-red ch_select location_checkbox']) !!}
                                                        {{$location->name}}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            
                            <hr>

                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.vat_module')</h4>
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
                                    <label class="search_label">@lang('superadmin::lang.vat_module')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('vat_module', 0) !!}
                                    {!! Form::checkbox('vat_module', 1,
                                            !empty($manage_module_enable['vat_module']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['vat_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['vat_expiry_date']))
                                        @if(strtotime($module_activation_data['vat_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['vat_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                <div class="col-md-1">
                                    {!! Form::select('vat_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['vat_interval']) ?
                                    $module_activation_data['vat_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('vat_length', !empty($module_activation_data['vat_length']) ?
                                    $module_activation_data['vat_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('vat_activated_on', !empty($module_activation_data['vat_activated_on']) ?
                                    $module_activation_data['vat_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('vat_expiry_date', !empty($module_activation_data['vat_expiry_date']) ?
                                    $module_activation_data['vat_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('vat_price', !empty($module_activation_data['vat_price']) ?
                                    $module_activation_data['vat_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                              
                            <div class="check_group">
                                <div class="row">
                                    <div class="check_group">
                                        <div class="col-md-3">
                                            <label>
                                                <input type="checkbox" class="check_all input-icheck-red">{{ __( 'role.select_all' ) }}
                                            </label>
                                        </div>
                                        
                                        <div class="clearfix"></div>
                                        
                                        <div class="col-md-4">
                                        {!! Form::hidden('vat_credit_bill', 0) !!}
                                            {!! Form::checkbox('vat_credit_bill', 1,
                                                !empty($manage_module_enable['vat_credit_bill']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                            <label class="search_label">@lang('superadmin::lang.vat_credit_bill')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                        {!! Form::hidden('vat_sale', 0) !!}
                                            {!! Form::checkbox('vat_sale', 1,
                                            !empty($manage_module_enable['vat_sale']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.vat_sale')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                        {!! Form::hidden('list_vat_sale', 0) !!}
                                            {!! Form::checkbox('list_vat_sale', 1,
                                            !empty($manage_module_enable['list_vat_sale']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.list_vat_sale')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                        {!! Form::hidden('vat_purchase', 0) !!}
                                            {!! Form::checkbox('vat_purchase', 1,
                                            !empty($manage_module_enable['vat_purchase']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.vat_purchase')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                        {!! Form::hidden('list_vat_purchase', 0) !!}
                                            {!! Form::checkbox('list_vat_purchase', 1,
                                            !empty($manage_module_enable['list_vat_purchase']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.list_vat_purchase')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                        {!! Form::hidden('vat_expense', 0) !!}
                                            {!! Form::checkbox('vat_expense', 1,
                                            !empty($manage_module_enable['vat_expense']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.vat_expense')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                        {!! Form::hidden('list_vat_expense', 0) !!}
                                            {!! Form::checkbox('list_vat_expense', 1,
                                            !empty($manage_module_enable['list_vat_expense']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.list_vat_expense')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                        {!! Form::hidden('vat_products', 0) !!}
                                            {!! Form::checkbox('vat_products', 1,
                                            !empty($manage_module_enable['vat_products']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.vat_products')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                        {!! Form::hidden('vat_contacts', 0) !!}
                                            {!! Form::checkbox('vat_contacts', 1,
                                            !empty($manage_module_enable['vat_contacts']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.vat_contacts')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                        {!! Form::hidden('vat_meter_sales', 0) !!}
                                            {!! Form::checkbox('vat_meter_sales', 1,
                                            !empty($manage_module_enable['vat_meter_sales']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.vat_meter_sales')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                        {!! Form::hidden('customized_vat_invoices', 0) !!}
                                            {!! Form::checkbox('customized_vat_invoices', 1,
                                            !empty($manage_module_enable['customized_vat_invoices']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('vat::lang.customized_vat_invoices')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                        {!! Form::hidden('fleet_vat_invoice2', 0) !!}
                                            {!! Form::checkbox('fleet_vat_invoice2', 1,
                                            !empty($manage_module_enable['fleet_vat_invoice2']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('vat::lang.fleet_vat_invoice')</label>
                                        </div>
                                        
                                         <div class="col-md-4">
                                         {!! Form::hidden('vat_linked_accounts', 0) !!}
                                            {!! Form::checkbox('vat_linked_accounts', 1,
                                            !empty($manage_module_enable['vat_linked_accounts']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('vat::lang.vat_payable_to')</label>
                                        </div>
                                        
                                        
                                        <div class="col-md-4">
                                        {!! Form::hidden('vat_delete_customer_statement', 0) !!}
                                            {!! Form::checkbox('vat_delete_customer_statement', 1,
                                            (!empty($manage_module_enable['vat_delete_customer_statement'])  || !array_key_exists('vat_delete_customer_statement',$manage_module_enable)) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('lang_v1.vat.delete_customer_statement')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                        {!! Form::hidden('vat_delete_statement_payment', 0) !!}
                                            {!! Form::checkbox('vat_delete_statement_payment', 1,
                                            (!empty($manage_module_enable['vat_delete_statement_payment'])  || !array_key_exists('vat_delete_statement_payment',$manage_module_enable)) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('lang_v1.vat.delete_statement_payment')</label>
                                        </div>

                                        <div class="col-md-4">
                                            {!! Form::hidden('enable_126_statement', 0) !!}
                                            {!! Form::checkbox('enable_126_statement',
                                                1,
                                                !empty($manage_module_enable['enable_126_statement']) ? true : false,
                                                ['class' => 'input-icheck-red ch_select']
                                            ) !!}
                                            <label class="search_label">@lang('vat::lang.enable_126_statement')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::hidden('vat_print_2026', 0) !!}
                                            {!! Form::checkbox('vat_print_2026', 1,
                                                !empty($manage_module_enable['vat_print_2026']) ? true : false,
                                                ['class' => 'input-icheck-red ch_select']
                                            ) !!}
                                            <label class="search_label">@lang('superadmin::lang.vat_print_2026')</label>
                                        </div>

                                        <div class="col-md-4">
                                            {!! Form::hidden('vat_dis_invoice', 0) !!}
                                            {!! Form::checkbox('vat_dis_invoice', 1,
                                                !empty($manage_module_enable['vat_dis_invoice']) ? true : false,
                                                ['class' => 'input-icheck-red ch_select']
                                            ) !!}
                                            <label class="search_label">VAT – Dis. Invoice</label>
                                        </div>

                                        {{-- MAX QTY VAT REPORT: Moved to VAT Module Main section. Uncomment below to restore here.
                                        <div class="col-md-4">
                                            <label class="search_label">
                                                @lang('superadmin::lang.max_qty_vat_report')<small class="text-muted">(@lang('superadmin::lang.only_fuel_products'))</small>
                                            </label>

                                            {!! Form::select('fuel_products[]',
                                                $fuel_products,
                                                !empty($vat_settings['fuel_products']) ? $vat_settings['fuel_products'] : null,
                                                [
                                                    'class' => 'form-control select2',
                                                    'multiple' => true,
                                                    'id' => 'fuel_products_select',
                                                    'style' => 'width:100%',
                                                    'placeholder' => __('superadmin::lang.select_products')
                                                ]
                                            ) !!}
                                        </div>
                                        --}}
                                        
                                    </div>
                                </div>
                            </div>
                           
                         </div>
                          
                    </div>
                    
                    {{-- VAT Module Main Permission Section --}}
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.vat_module_main')</h4>
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
                                    <label class="search_label">@lang('superadmin::lang.vat_module_main')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('vat_module_main', 0) !!}
                                    {!! Form::checkbox('vat_module_main', 1,
                                            !empty($manage_module_enable['vat_module_main']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select vat_main_module']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['vat_main_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['vat_main_expiry_date']))
                                        @if(strtotime($module_activation_data['vat_main_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['vat_main_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                <div class="col-md-1">
                                    {!! Form::select('vat_main_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['vat_main_interval']) ?
                                    $module_activation_data['vat_main_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('vat_main_length', !empty($module_activation_data['vat_main_length']) ?
                                    $module_activation_data['vat_main_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('vat_main_activated_on', !empty($module_activation_data['vat_main_activated_on']) ?
                                    $module_activation_data['vat_main_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('vat_main_expiry_date', !empty($module_activation_data['vat_main_expiry_date']) ?
                                    $module_activation_data['vat_main_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('vat_main_price', !empty($module_activation_data['vat_main_price']) ?
                                    $module_activation_data['vat_main_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            
                            {{-- Sub-permissions for VAT Module Main --}}
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red"> {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_invoice', 0) !!}
                                    {!! Form::checkbox('vat_main_invoice', 1,
                                    !empty($manage_module_enable['vat_main_invoice']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">VAT Invoice</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_invoice2', 0) !!}
                                    {!! Form::checkbox('vat_main_invoice2', 1,
                                    !empty($manage_module_enable['vat_main_invoice2']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">VAT Invoice 2</label>
                                </div>

                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_report', 0) !!}
                                    {!! Form::checkbox('vat_main_report', 1,
                                    !empty($manage_module_enable['vat_main_report']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">VAT Report</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_report_ledger', 0) !!}
                                    {!! Form::checkbox('vat_main_report_ledger', 1,
                                    !empty($manage_module_enable['vat_main_report_ledger']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">VAT Report Ledger</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_settings', 0) !!}
                                    {!! Form::checkbox('vat_main_settings', 1,
                                    !empty($manage_module_enable['vat_main_settings']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">VAT Settings</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_statement', 0) !!}
                                    {!! Form::checkbox('vat_main_statement', 1,
                                    !empty($manage_module_enable['vat_main_statement']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">VAT Statement</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_schedule', 0) !!}
                                    {!! Form::checkbox('vat_main_schedule', 1,
                                    !empty($manage_module_enable['vat_main_schedule']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">VAT Schedule</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_import_contacts', 0) !!}
                                    {!! Form::checkbox('vat_main_import_contacts', 1,
                                    !empty($manage_module_enable['vat_main_import_contacts']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">Import Contacts</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_print_2026', 0) !!}
                                    {!! Form::checkbox('vat_main_print_2026', 1,
                                    !empty($manage_module_enable['vat_main_print_2026']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">VAT Print 2026</label>
                                </div>

                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_credit_bill', 0) !!}
                                    {!! Form::checkbox('vat_main_credit_bill', 1,
                                    !empty($manage_module_enable['vat_main_credit_bill']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.vat_credit_bill')</label>
                                </div>

                                {{-- Max Qty VAT Report (Only Fuel Products) — Moved from VAT Module section --}}
                                <div class="col-md-12" style="margin-top: 10px;">
                                    <hr style="border-color: #aaa; margin: 8px 0;">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="search_label">
                                                @lang('superadmin::lang.max_qty_vat_report')
                                                <small class="text-muted">(@lang('superadmin::lang.only_fuel_products'))</small>
                                            </label>
                                            {!! Form::select('fuel_products[]',
                                                $fuel_products,
                                                !empty($vat_settings['fuel_products']) ? $vat_settings['fuel_products'] : null,
                                                [
                                                    'class' => 'form-control select2',
                                                    'multiple' => true,
                                                    'id' => 'fuel_products_select_main',
                                                    'style' => 'width:100%',
                                                    'placeholder' => __('superadmin::lang.select_products')
                                                ]
                                            ) !!}
                                        </div>
                                        <div class="col-md-8">
                                            <div class="row mt-2" id="fuel_products_qty_main">
                                                @if(!empty($vat_settings['fuel_products_qty']))
                                                    @foreach($vat_settings['fuel_products_qty'] as $product_id => $qty)
                                                        <div class="col-md-4 mb-2 product-qty-input" data-id="{{ $product_id }}">
                                                            <label>{{ $fuel_products[$product_id] ?? 'Product' }}</label>
                                                            {!! Form::number(
                                                                "fuel_products_qty[$product_id]",
                                                                $qty,
                                                                ['class' => 'form-control', 'min' => 0]
                                                            ) !!}
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_sale', 0) !!}
                                    {!! Form::checkbox('vat_main_sale', 1,
                                    !empty($manage_module_enable['vat_main_sale']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.vat_sale')</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_list_vat_sale', 0) !!}
                                    {!! Form::checkbox('vat_main_list_vat_sale', 1,
                                    !empty($manage_module_enable['vat_main_list_vat_sale']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.list_vat_sale')</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_purchase', 0) !!}
                                    {!! Form::checkbox('vat_main_purchase', 1,
                                    !empty($manage_module_enable['vat_main_purchase']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.vat_purchase')</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_list_vat_purchase', 0) !!}
                                    {!! Form::checkbox('vat_main_list_vat_purchase', 1,
                                    !empty($manage_module_enable['vat_main_list_vat_purchase']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.list_vat_purchase')</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_expense', 0) !!}
                                    {!! Form::checkbox('vat_main_expense', 1,
                                    !empty($manage_module_enable['vat_main_expense']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.vat_expense')</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_list_vat_expense', 0) !!}
                                    {!! Form::checkbox('vat_main_list_vat_expense', 1,
                                    !empty($manage_module_enable['vat_main_list_vat_expense']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.list_vat_expense')</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_products', 0) !!}
                                    {!! Form::checkbox('vat_main_products', 1,
                                    !empty($manage_module_enable['vat_main_products']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.vat_products')</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_contacts', 0) !!}
                                    {!! Form::checkbox('vat_main_contacts', 1,
                                    !empty($manage_module_enable['vat_main_contacts']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.vat_contacts')</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_meter_sales', 0) !!}
                                    {!! Form::checkbox('vat_main_meter_sales', 1,
                                    !empty($manage_module_enable['vat_main_meter_sales']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.vat_meter_sales')</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_customized_invoices', 0) !!}
                                    {!! Form::checkbox('vat_main_customized_invoices', 1,
                                    !empty($manage_module_enable['vat_main_customized_invoices']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.customized_vat_invoices')</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_fleet_invoices', 0) !!}
                                    {!! Form::checkbox('vat_main_fleet_invoices', 1,
                                    !empty($manage_module_enable['vat_main_fleet_invoices']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.fleet_vat_invoices')</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_linked_accounts', 0) !!}
                                    {!! Form::checkbox('vat_main_linked_accounts', 1,
                                    !empty($manage_module_enable['vat_main_linked_accounts']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.vat_linked_accounts')</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_delete_customer_statement', 0) !!}
                                    {!! Form::checkbox('vat_main_delete_customer_statement', 1,
                                    !empty($manage_module_enable['vat_main_delete_customer_statement']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.vat_delete_customer_statement')</label>
                                </div>
                               
                                <div class="col-md-4">
                                    {!! Form::hidden('vat_main_delete_statement_payments', 0) !!}
                                    {!! Form::checkbox('vat_main_delete_statement_payments', 1,
                                    !empty($manage_module_enable['vat_main_delete_statement_payments']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.vat_delete_statement_payments')</label>
                                </div>
                               
                                <div class="col-md-4">
                                    <div class="permission-item">
                                        {!! Form::hidden('vat_main_enabled_126_statement', 0) !!}
                                        {!! Form::checkbox('vat_main_enabled_126_statement', 1,
                                        !empty($manage_module_enable['vat_main_enabled_126_statement']) ? true : false, ['class' =>
                                        'input-icheck-red ch_select']) !!}
                                        <label class="search_label">@lang('superadmin::lang.enabled_126_statement')</label>
                                    </div>
                                </div>
                               
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('vat_main_invoice2_monthly_limit', 'Maximum No of VAT Invoices per month') !!}
                                        {!! Form::number(
                                            'vat_main_invoice2_monthly_limit',
                                            isset($manage_module_enable['vat_main_invoice2_monthly_limit']) ? $manage_module_enable['vat_main_invoice2_monthly_limit'] : 0,
                                            ['class' => 'form-control', 'min' => 0]
                                        ) !!}
                                        
                                    </div>
                                </div>
                                
                            </div>
                          </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.bakery_module')</h4>
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
                                    <label class="search_label">@lang('superadmin::lang.bakery_module')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('bakery_module', 0) !!}
                                    {!! Form::checkbox('bakery_module', 1,
                                            !empty($manage_module_enable['bakery_module']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['bakery_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['bakery_expiry_date']))
                                        @if(strtotime($module_activation_data['bakery_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['bakery_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                <div class="col-md-1">
                                    {!! Form::select('bakery_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['bakery_interval']) ?
                                    $module_activation_data['bakery_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('bakery_length', !empty($module_activation_data['bakery_length']) ?
                                    $module_activation_data['bakery_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('bakery_activated_on', !empty($module_activation_data['bakery_activated_on']) ?
                                    $module_activation_data['bakery_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('bakery_expiry_date', !empty($module_activation_data['bakery_expiry_date']) ?
                                    $module_activation_data['bakery_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('bakery_price', !empty($module_activation_data['bakery_price']) ?
                                    $module_activation_data['bakery_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            
                            <div class="check_group">
                                <div class="row">
                                    <div class="check_group">
                                        <div class="col-md-3">
                                            <label>
                                                <input type="checkbox" class="check_all input-icheck-red">{{ __( 'role.select_all' ) }}
                                            </label>
                                        </div>
                                        
                                        <div class="clearfix"></div>
                                      
                                        <div class="col-md-4">
                                        {!! Form::hidden('bakery_drivers', 0) !!}
                                            {!! Form::checkbox('bakery_drivers', 1,
                                            !empty($manage_module_enable['bakery_drivers']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.bakery_drivers')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                        {!! Form::hidden('bakery_vehicles', 0) !!}
                                            {!! Form::checkbox('bakery_vehicles', 1,
                                            !empty($manage_module_enable['bakery_vehicles']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.bakery_vehicles')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                        {!! Form::hidden('bakery_products', 0) !!}
                                            {!! Form::checkbox('bakery_products', 1,
                                            !empty($manage_module_enable['bakery_products']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.bakery_products')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                        {!! Form::hidden('bakery_starting_no', 0) !!}
                                            {!! Form::checkbox('bakery_starting_no', 1,
                                            !empty($manage_module_enable['bakery_starting_no']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.bakery_starting_no')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                        {!! Form::hidden('bakery_list_products', 0) !!}
                                            {!! Form::checkbox('bakery_list_products', 1,
                                            !empty($manage_module_enable['bakery_list_products']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.bakery_list_products')</label>
                                        </div>

                                        <div class="clearfix"></div>
                                        <div class="col-md-12" style="margin-top: 10px;">
                                            <h5><strong>@lang('superadmin::lang.bakery_settings')</strong></h5>
                                        </div>
                                        <div class="col-md-12">
                                        {!! Form::hidden('bakery_settings_show_vehicle_opening_balance', 0) !!}
                                            {!! Form::checkbox('bakery_settings_show_vehicle_opening_balance', 1,
                                            !empty($manage_module_enable['bakery_settings_show_vehicle_opening_balance']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.bakery_show_vehicle_opening_balance')</label>
                                        </div>
                                        
                                        
                                    </div>
                                </div>
                            </div>
                           
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.um_roles')</h4>
                            <hr>
                          </div>
                          <div class="card-body">
                            <div class="check_group">
                                <div class="row">
                                    <div class="check_group">
                                        <div class="col-md-3">
                                            <label>
                                                <input type="checkbox" class="check_all input-icheck-red">{{ __( 'role.select_all' ) }}
                                            </label>
                                        </div>
                                        
                                        <div class="clearfix"></div>
                                        
                                        <div class="col-md-3">
                                            {!! Form::checkbox('um_add_role', 1,
                                            !empty($manage_module_enable['um_add_role']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.um_add_role')</label>
                                        </div>

                                        {{-- MA-002 (LA-1135): Add User.
                                             Markup copied from um_add_role above so it behaves
                                             identically - same icheck class, same
                                             $manage_module_enable lookup, same label source. --}}
                                        <div class="col-md-3">
                                            {!! Form::checkbox('um_add_user', 1,
                                            !empty($manage_module_enable['um_add_user']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.um_add_user')</label>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            {!! Form::checkbox('um_edit_role', 1,
                                            !empty($manage_module_enable['um_edit_role']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.um_edit_role')</label>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            {!! Form::checkbox('um_delete_role', 1,
                                            !empty($manage_module_enable['um_delete_role']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.um_delete_role')</label>
                                        </div>

                                        <div class="col-md-3">
                                            {!! Form::hidden('um_supervisor_role', 0) !!}
                                            {!! Form::checkbox(
                                                'um_supervisor_role',
                                                1,
                                                true, // default enabled
                                                ['class' => 'input-icheck-red ch_select']
                                            ) !!}
                                            <label class="search_label">Supervisor Role</label>
                                        </div>

                                        
                                    </div>
                                </div>
                            </div>
                           
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('report.aging_report_total')</h4>
                            <hr>
                          </div>
                          <div class="card-body">
                            <div class="check_group">
                                <div class="row">
                                    <div class="check_group">
                                        <div class="col-md-3">
                                            <label>
                                                <input type="checkbox" class="check_all input-icheck-red">{{ __( 'role.select_all' ) }}
                                            </label>
                                        </div>
                                        
                                        <div class="clearfix"></div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::checkbox('1_7_days', 1,
                                            !empty($manage_module_enable['1_7_days']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('report.1_7_days')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::checkbox('8_14_days', 1,
                                            !empty($manage_module_enable['8_14_days']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('report.8_14_days')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::checkbox('15_21_days', 1,
                                            !empty($manage_module_enable['15_21_days']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('report.15_21_days')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::checkbox('22_30_days', 1,
                                            !empty($manage_module_enable['22_30_days']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('report.22_30_days')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::checkbox('over_30_days', 1,
                                            !empty($manage_module_enable['over_30_days']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('report.over_30_days')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::checkbox('1_30_days', 1,
                                            !empty($manage_module_enable['1_30_days']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('report.1_30_days')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::checkbox('31_45_days', 1,
                                            !empty($manage_module_enable['31_45_days']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('report.31_45_days')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::checkbox('46_60_days', 1,
                                            !empty($manage_module_enable['46_60_days']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('report.46_60_days')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::checkbox('61_90_days', 1,
                                            !empty($manage_module_enable['61_90_days']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('report.61_90_days')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::checkbox('over_90_days', 1,
                                            !empty($manage_module_enable['over_90_days']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('report.over_90_days')</label>
                                        </div>
                                        
                                        
                                    </div>
                                </div>
                            </div>
                           
                         </div>
                          
                    </div>
                    
                        
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.hr_module')</h4>
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
                            <div class="check_group">
                                <div class="row">
                                    <div class="col-md-2">
                                        <label class="search_label">@lang('superadmin::lang.hr_module')</label>
                                    </div>
                                    
                                    <div class="col-md-1">
                                    {!! Form::hidden('hr_module', 0) !!}
                                        {!! Form::checkbox('hr_module', 1, !empty($manage_module_enable['hr_module']) ? true :
                                        false,
                                        ['class' => 'input-icheck-red ch_select hr_module']) !!}
                                    </div>
                                    <div class="col-md-1">
                                        @if(empty($module_activation_data['hr_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['hr_expiry_date']))
                                        @if(strtotime($module_activation_data['hr_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['hr_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::select('hr_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['hr_interval']) ?
                                        $module_activation_data['hr_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('hr_length', !empty($module_activation_data['hr_length']) ?
                                        $module_activation_data['hr_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                    
                                    <div class="col-md-2">
                                        {!! Form::date('hr_activated_on', !empty($module_activation_data['hr_activated_on']) ?
                                        $module_activation_data['hr_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                    </div>
                                    <div class="col-md-2">
                                        {!! Form::date('hr_expiry_date', !empty($module_activation_data['hr_expiry_date']) ?
                                        $module_activation_data['hr_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                    </div>
                                    <div class="col-md-2">
                                        {!! Form::text('hr_price', !empty($module_activation_data['hr_price']) ?
                                        $module_activation_data['hr_price'] : null, ['class' => 'form-control']) !!}
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="check_group">
                                        <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" class="check_all input-icheck-red">{{ __( 'role.select_all' ) }}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="clearfix"></div>
                                        <div class="col-md-4">
                                            {!! Form::checkbox('leave_request', 1,
                                            !empty($manage_module_enable['leave_request']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.leave_request')</label>
                                        </div>
                                        <div class="col-md-4">
                                            {!! Form::checkbox('attendance', 1,
                                            !empty($manage_module_enable['attendance']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.attendance')</label>
                                        </div>
                                        <div class="col-md-4">
                                            {!! Form::checkbox('payroll', 1,
                                            !empty($manage_module_enable['payroll']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.payroll')</label>
                                        </div>
                                        <div class="col-md-4">
                                            {!! Form::checkbox('hr_settings', 1,
                                            !empty($manage_module_enable['hr_settings']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.hr_settings')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::checkbox('work_shift', 1,
                                            !empty($manage_module_enable['work_shift']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('essentials::lang.work_shift')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::checkbox('holidays', 1,
                                            !empty($manage_module_enable['holidays']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.holidays')</label>
                                        </div>
                                        <div class="col-md-4">
                                            {!! Form::checkbox('leave_type', 1,
                                            !empty($manage_module_enable['leave_type']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.leave_type')</label>
                                        </div>
                                        <div class="col-md-4">
                                            {!! Form::checkbox('allowance_deduction', 1,
                                            !empty($manage_module_enable['allowance_deduction']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('essentials::lang.allowance_and_deduction')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::checkbox('hrm_ledger', 1,
                                            !empty($manage_module_enable['hrm_ledger']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.hrm_ledger')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::checkbox('hrm_dashboard', 1,
                                            !empty($manage_module_enable['hrm_dashboard']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.hrm_dashboard')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::checkbox('hrm_leave', 1,
                                            !empty($manage_module_enable['hrm_leave']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.hrm_leave')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::checkbox('hrm_sales_target', 1,
                                            !empty($manage_module_enable['hrm_sales_target']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.hrm_sales_target')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::checkbox('hrm_settings', 1,
                                            !empty($manage_module_enable['hrm_settings']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.hrm_settings')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::checkbox('hrm_salary_details', 1,
                                            !empty($manage_module_enable['hrm_salary_details']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.hrm_salary_details')</label>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                           
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('essentials::lang.essentials')</h4>
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
                            <div class="check_group">
                                <div class="row">
                                    <div class="col-md-2">
                                        <label class="search_label">@lang('essentials::lang.essentials')</label>
                                    </div>
                                    
                                    <div class="col-md-1">
                                    {!! Form::hidden('essentials_module', 0) !!}
                                        {!! Form::checkbox('essentials_module', 1, !empty($manage_module_enable['essentials_module']) ? true :
                                        false,
                                        ['class' => 'input-icheck-red ch_select essentials_module']) !!}
                                    </div>
                                    <div class="col-md-1">
                                        @if(empty($module_activation_data['essentials_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['essentials_expiry_date']))
                                        @if(strtotime($module_activation_data['essentials_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['essentials_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::select('essentials_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['essentials_interval']) ?
                                        $module_activation_data['essentials_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('essentials_length', !empty($module_activation_data['essentials_length']) ?
                                        $module_activation_data['essentials_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                    
                                    <div class="col-md-2">
                                        {!! Form::date('essentials_activated_on', !empty($module_activation_data['essentials_activated_on']) ?
                                        $module_activation_data['essentials_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                    </div>
                                    <div class="col-md-2">
                                        {!! Form::date('essentials_expiry_date', !empty($module_activation_data['essentials_expiry_date']) ?
                                        $module_activation_data['essentials_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                    </div>
                                    <div class="col-md-2">
                                        {!! Form::text('essentials_price', !empty($module_activation_data['essentials_price']) ?
                                        $module_activation_data['essentials_price'] : null, ['class' => 'form-control']) !!}
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="check_group">
                                        <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" class="check_all input-icheck-red">{{ __( 'role.select_all' ) }}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="clearfix"></div>
                                        <div class="col-md-4">
                                            {!! Form::checkbox('essentials_todo', 1,
                                            !empty($manage_module_enable['essentials_todo']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('essentials::lang.todo')</label>
                                        </div>
                                        <div class="col-md-4">
                                            {!! Form::checkbox('essentials_document', 1,
                                            !empty($manage_module_enable['essentials_document']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('essentials::lang.document')</label>
                                        </div>
                                        <div class="col-md-4">
                                            {!! Form::checkbox('essentials_memos', 1,
                                            !empty($manage_module_enable['essentials_memos']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('essentials::lang.memos')</label>
                                        </div>
                                        <div class="col-md-4">
                                            {!! Form::checkbox('essentials_reminders', 1,
                                            !empty($manage_module_enable['essentials_reminders']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('essentials::lang.reminders')</label>
                                        </div>
                                        <div class="col-md-4">
                                            {!! Form::checkbox('essentials_messages', 1,
                                            !empty($manage_module_enable['essentials_messages']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('essentials::lang.messages')</label>
                                        </div>
                                        <div class="col-md-4">
                                            {!! Form::checkbox('essentials_settings', 1,
                                            !empty($manage_module_enable['essentials_settings']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('business.settings')</label>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                           
                         </div>
                          
                    </div>
                    
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.visitors_registration_module')</h4>
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
                            <div class="check_group">
                                <div class="row">
                                    <div class="col-md-2">
                                        <label
                                                class="search_label">@lang('superadmin::lang.visitors_registration_module')</label>
                                    </div>
                                    
                                    <div class="col-md-1">
                                    {!! Form::hidden('visitors_registration_module', 0) !!}
                                        {!! Form::checkbox('visitors_registration_module', 1,
                                        !empty($manage_module_enable['visitors_registration_module']) ? true :
                                        false,
                                        ['class' => 'input-icheck-red ch_select visitors_registration_module']) !!}
                                    </div>
                                        <div class="col-md-1">
                                            @if(empty($module_activation_data['vreg_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['vreg_expiry_date']))
                                        @if(strtotime($module_activation_data['vreg_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['vreg_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                        </div>
                                        
                                        <div class="col-md-1">
                                        {!! Form::select('vreg_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['vreg_interval']) ?
                                        $module_activation_data['vreg_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('vreg_length', !empty($module_activation_data['vreg_length']) ?
                                        $module_activation_data['vreg_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                        
                                        <div class="col-md-2">
                                            {!! Form::date('vreg_activated_on', !empty($module_activation_data['vreg_activated_on']) ?
                                            $module_activation_data['vreg_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                        </div>
                                        <div class="col-md-2">
                                            {!! Form::date('vreg_expiry_date', !empty($module_activation_data['vreg_expiry_date']) ?
                                            $module_activation_data['vreg_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                        </div>
                                        <div class="col-md-2">
                                            {!! Form::text('vreg_price', !empty($module_activation_data['vreg_price']) ?
                                            $module_activation_data['vreg_price'] : null, ['class' => 'form-control']) !!}
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
                                    <div class="col-md-4">
                                        {!! Form::checkbox('visitors', 1,
                                        !empty($manage_module_enable['visitors']) ? true : false, ['class' =>
                                        'input-icheck-red
                                        ch_select']) !!}<label class="search_label">@lang('superadmin::lang.visitors')</label>
                                    </div>
                                    <div class="col-md-4">
                                        {!! Form::checkbox('visitors_registration', 1,
                                        !empty($manage_module_enable['visitors_registration']) ? true : false, ['class' =>
                                        'input-icheck-red
                                        ch_select']) !!}<label
                                                class="search_label">@lang('superadmin::lang.visitors_registration')</label>
                                    </div>
                                    <div class="col-md-4">
                                        {!! Form::checkbox('visitors_registration_setting', 1,
                                        !empty($manage_module_enable['visitors_registration_setting']) ? true : false, ['class' =>
                                        'input-icheck-red
                                        ch_select']) !!}<label
                                                class="search_label">@lang('superadmin::lang.visitors_registration_setting')</label>
                                    </div>
                                    <div class="col-md-4">
                                        {!! Form::checkbox('visitors_district', 1,
                                        !empty($manage_module_enable['visitors_district']) ? true : false, ['class' =>
                                        'input-icheck-red
                                        ch_select']) !!}<label
                                                class="search_label">@lang('superadmin::lang.visitors_district')</label>
                                    </div>
                                    <div class="col-md-4">
                                        {!! Form::checkbox('visitors_town', 1,
                                        !empty($manage_module_enable['visitors_town']) ? true : false, ['class' =>
                                        'input-icheck-red
                                        ch_select']) !!}<label
                                                class="search_label">@lang('superadmin::lang.visitors_town')</label>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="col-md-4">
                                        {!! Form::checkbox('disable_all_other_module_vr', 1,
                                        !empty($manage_module_enable['disable_all_other_module_vr']) ? true : false, ['class' =>
                                        'input-icheck-red
                                        ch_select']) !!}<label
                                                class="search_label">@lang('superadmin::lang.disable_all_other_module_vr')</label>
                                    </div>

                                </div>
                            </div>
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.enable_petro_module')</h4>
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
                                    <label class="search_label">@lang('superadmin::lang.enable_petro_module')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('enable_petro_module', 0) !!}
                                    {!! Form::checkbox('enable_petro_module', 1,
                                    !empty($manage_module_enable['enable_petro_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['petro_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['petro_expiry_date']))
                                        @if(strtotime($module_activation_data['petro_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['petro_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('petro_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['petro_interval']) ?
                                        $module_activation_data['petro_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('petro_length', !empty($module_activation_data['petro_length']) ?
                                        $module_activation_data['petro_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                
                                <div class="col-md-2">
                                    {!! Form::date('petro_activated_on', !empty($module_activation_data['petro_activated_on']) ?
                                    $module_activation_data['petro_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('petro_expiry_date', !empty($module_activation_data['petro_expiry_date']) ?
                                    $module_activation_data['petro_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('petro_price', !empty($module_activation_data['petro_price']) ?
                                    $module_activation_data['petro_price'] : null, ['class' => 'form-control']) !!}
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
                                {{-- MA-002 (IS-1917): "petro_settlement" hidden from the Petro General section.
                                 HIDDEN, NOT REMOVED - the value is still posted below so
                                 whatever each business has today is preserved on save. --}}
                            {!! Form::hidden('petro_settlement', !empty($manage_module_enable['petro_settlement']) ? 1 : 0) !!}
                            <div class="col-sm-3" style="display:none;"></div>
                               
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label" class="flex-label">
                                            {!! Form::checkbox('petro_sms_notifications', 1, !empty($manage_module_enable['petro_sms_notifications']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'petro_sms_notifications']) !!}
                                            {{__('petro::lang.petro_sms_notifications')}}
                                        </label>
                                    </div>
                                </div>
                                    
                                    
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label" class="flex-label">
                                            {!! Form::checkbox('edit_settlement_date', 1, !empty($manage_module_enable['edit_settlement_date']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'edit_settlement_date']) !!}
                                            {{__('superadmin::lang.edit_settlement_date')}}
                                        </label>
                                    </div>
                                </div>
                               
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label" class="flex-label">
                                            {!! Form::checkbox('rename_cash_tab', 1, !empty($manage_module_enable['rename_cash_tab']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'rename_cash_tab']) !!}
                                            {{__('superadmin::lang.rename_cash_tab')}}
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::hidden('show_mechanical_meter', 0) !!}
                                        {!! Form::checkbox('show_mechanical_meter', 1, array_key_exists('show_mechanical_meter', $manage_module_enable) ? !empty($manage_module_enable['show_mechanical_meter']) : true, ['class' => 'input-icheck-red ch_select', 'id'=>'show_mechanical_meter']) !!}
                                        Show Mechanical Meter
                                    </label>
                                </div>
                            </div>
                            <div class="clearfix"></div><hr style="border-color: red; margin: 8px 0;">
                                 <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label" class="flex-label">
                                            {!! Form::checkbox('only_walkin', 1, !empty($manage_module_enable['only_walkin']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'only_walkin']) !!}
                                            {{__('superadmin::lang.only_walkin')}}
                                        </label>
                                    </div>
                                </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label" class="flex-label">
                                        {!! Form::checkbox('petro_daily_status', 1, !empty($manage_module_enable['petro_daily_status']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'petro_daily_status']) !!}
                                        {{__('superadmin::lang.petro_daily_status')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label" class="flex-label">
                                        {!! Form::checkbox('tank_transfer', 1, !empty($manage_module_enable['tank_transfer']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'tank_transfer']) !!}
                                        {{__('superadmin::lang.tank_transfer')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label" class="flex-label">
                                        {!! Form::checkbox('petro_dashboard', 1, !empty($manage_module_enable['petro_dashboard']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'petro_dashboard']) !!}
                                        {{__('superadmin::lang.petro_dashboard')}}
                                    </label>
                                </div>
                            </div>
                            <div class="clearfix"></div><hr style="border-color: red; margin: 8px 0;">
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('petro_task_management', 1, !empty($manage_module_enable['petro_task_management']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'petro_task_management']) !!}
                                        {{__('superadmin::lang.petro_task_management')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('pump_management', 1, !empty($manage_module_enable['pump_management']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'pump_management']) !!}
                                        {{__('superadmin::lang.pump_management_tab_page')}}
                                    </label>
                                </div>
                            </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::checkbox('show_pump_operators_when_shifts_pending_in_settlement', 1, !empty($manage_module_enable['show_pump_operators_when_shifts_pending_in_settlement']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'show_pump_operators_when_shifts_pending_in_settlement']) !!}
                                            Show Pump Operators, when the shifts pending in the settlement
                                        </label>
                                    </div>
                                </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::hidden('select_pump_operator_in_settlement', 0) !!}
                                        {!! Form::checkbox('select_pump_operator_in_settlement', 1, array_key_exists('select_pump_operator_in_settlement', $manage_module_enable) ? !empty($manage_module_enable['select_pump_operator_in_settlement']) : true, ['class' => 'input-icheck-red ch_select', 'id'=>'select_pump_operator_in_settlement']) !!}
                                        {{__('superadmin::lang.select_pump_operator_in_settlement')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('pump_management_testing', 1, !empty($manage_module_enable['pump_management_testing']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'pump_management_testing']) !!}
                                        {{__('superadmin::lang.pump_management_testing_page')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="clearfix"></div><hr style="border-color: red; margin: 8px 0;">
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('meter_resetting', 1, !empty($manage_module_enable['meter_resetting']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'meter_resetting']) !!}
                                        {{__('superadmin::lang.meter_resetting_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('meter_reading', 1, !empty($manage_module_enable['meter_reading']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'meter_reading']) !!}
                                        {{__('superadmin::lang.meter_reading_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('pump_dashboard_opening', 1, !empty($manage_module_enable['pump_dashboard_opening']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'pump_dashboard_opening']) !!}
                                        {{__('superadmin::lang.pump_dashboard_opening_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('pumper_dashboard_settings', 1, !empty($manage_module_enable['pumper_dashboard_settings']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'pumper_dashboard_settings']) !!}
                                        {{__('superadmin::lang.pumper_dashboard_settings')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('pumper_management', 1, !empty($manage_module_enable['pumper_management']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'pumper_management']) !!}
                                        {{__('superadmin::lang.pump_management_sub_menu_page')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="clearfix"></div><hr style="border-color: red; margin: 8px 0;">
                            {{-- MA-002 (IS-1917): "settlement" hidden from the Petro General section.
                                 HIDDEN, NOT REMOVED - the value is still posted below so
                                 whatever each business has today is preserved on save. --}}
                            {!! Form::hidden('settlement', !empty($manage_module_enable['settlement']) ? 1 : 0) !!}
                            <div class="col-sm-3" style="display:none;"></div>
                            {{-- MA-002 (IS-1917): "list_settlement" hidden from the Petro General section.
                                 HIDDEN, NOT REMOVED - the value is still posted below so
                                 whatever each business has today is preserved on save. --}}
                            {!! Form::hidden('list_settlement', !empty($manage_module_enable['list_settlement']) ? 1 : 0) !!}
                            <div class="col-sm-3" style="display:none;"></div>
                            {{-- MA-002 (IS-1917): "delete_settlement" hidden from the Petro General section.
                                 HIDDEN, NOT REMOVED - the value is still posted below so
                                 whatever each business has today is preserved on save. --}}
                            {!! Form::hidden('delete_settlement', !empty($manage_module_enable['delete_settlement']) ? 1 : 0) !!}
                            <div class="col-sm-3" style="display:none;"></div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('dip_management', 1, !empty($manage_module_enable['dip_management']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'dip_management']) !!}
                                        {{__('superadmin::lang.dip_management_sub_menu')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('fuel_tanks_edit', 1, !empty($manage_module_enable['fuel_tanks_edit']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'fuel_tanks_edit']) !!}
                                        {{__('superadmin::lang.fuel_tank_edit')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('fuel_tanks_delete', 1, !empty($manage_module_enable['fuel_tanks_delete']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'fuel_tanks_delete']) !!}
                                        {{__('superadmin::lang.fuel_tank_delete')}}
                                    </label>
                                </div>
                            </div>
                            {{-- MA-002 (IS-1917): Pumps Edit and Pumps Delete were on the
                                 requested list but had NO checkbox anywhere on this form.
                                 The permissions are real - used in code, and already saved by
                                 BusinessController - so only the controls were missing. Added
                                 here beside their Fuel Tanks counterparts, following the same
                                 markup. --}}
<div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('pumps_edit', 1, !empty($manage_module_enable['pumps_edit']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'pumps_edit']) !!}
                                        {{ 'Pumps Edit' }}
                                    </label>
                                </div>
                            </div>
<div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('pumps_delete', 1, !empty($manage_module_enable['pumps_delete']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'pumps_delete']) !!}
                                        {{ 'Pumps Delete' }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3" style="display:none;"></div>
                            {{-- MA-002 (IS-1917): "list_tank_transfer" hidden from the Petro General section.
                                 HIDDEN, NOT REMOVED - the value is still posted below so
                                 whatever each business has today is preserved on save. --}}
                            {!! Form::hidden('list_tank_transfer', !empty($manage_module_enable['list_tank_transfer']) ? 1 : 0) !!}
                            <div class="col-sm-3" style="display:none;"></div>
                            
                            <div class="col-md-3">
                                    {!! Form::checkbox('pay_excess_commission', 1,
                                    !empty($manage_module_enable['pay_excess_commission']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.pay_excess_commission')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('recover_shortage', 1,
                                    !empty($manage_module_enable['recover_shortage']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.recover_shortage')</label>
                                </div>
                                
                                <div class="col-md-3">
                                    {!! Form::checkbox('pump_operator_ledger', 1,
                                    !empty($manage_module_enable['pump_operator_ledger']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.pump_operator_ledger')</label>
                                </div>
                                
                                
    

                               
                                
                                <div class="col-md-3">
                                    {!! Form::checkbox('commission_type', 1,
                                    !empty($manage_module_enable['commission_type']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.commission_type')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('edit_settlement', 1,
                                    !empty($manage_module_enable['edit_settlement']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.edit_settlement')</label>
                                </div>
                                
                                <div class="col-md-3">
                                    {!! Form::checkbox('dip_resetting', 1,
                                    !empty($manage_module_enable['dip_resetting']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.dip_resetting')</label>
                                </div>
                                
                                <div class="col-md-3">
                                    {!! Form::checkbox('tank_dip_chart', 1,
                                    !empty($manage_module_enable['tank_dip_chart']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.tank_dip_chart')</label>
                                </div>
                                
                                <div class="col-md-3">
                                    {!! Form::checkbox('edit_settlement_no_change', 1,
                                    !empty($manage_module_enable['edit_settlement_no_change']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                    <label class="search_label">@lang('petro::lang.edit_no_change')</label>
                                </div>

                                {{-- MA-002 (IS-1917): "allow_duplicate_order_numbers" hidden from the Petro General section.
                                 HIDDEN, NOT REMOVED - the value is still posted below so
                                 whatever each business has today is preserved on save. --}}
                            {!! Form::hidden('allow_duplicate_order_numbers', !empty($manage_module_enable['allow_duplicate_order_numbers']) ? 1 : 0) !!}
                            <div class="col-sm-3" style="display:none;"></div>

                                {{-- MA-002 (IS-1917): "settlement_pd" hidden from the Petro General section.
                                 HIDDEN, NOT REMOVED - the value is still posted below so
                                 whatever each business has today is preserved on save. --}}
                            {!! Form::hidden('settlement_pd', !empty($manage_module_enable['settlement_pd']) ? 1 : 0) !!}
                            <div class="col-sm-3" style="display:none;"></div>

                                {{-- MA-002 (IS-1917): "list_settlement_pd" hidden from the Petro General section.
                                 HIDDEN, NOT REMOVED - the value is still posted below so
                                 whatever each business has today is preserved on save. --}}
                            {!! Form::hidden('list_settlement_pd', !empty($manage_module_enable['list_settlement_pd']) ? 1 : 0) !!}
                            <div class="col-sm-3" style="display:none;"></div>
                                <div class="col-md-3" style="display:none;"></div>
                                {{-- MA-002 (IS-1917): "petro_activity_report" hidden from the Petro General section.
                                 HIDDEN, NOT REMOVED - the value is still posted below so
                                 whatever each business has today is preserved on save. --}}
                            {!! Form::hidden('petro_activity_report', !empty($manage_module_enable['petro_activity_report']) ? 1 : 0) !!}
                            <div class="col-sm-3" style="display:none;"></div>
                                
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::checkbox('day_end_settlement', 1, !empty($manage_module_enable['day_end_settlement']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'day_end_settlement']) !!}
                                            @lang('petro::lang.day_end_settlement')
                                        </label>
                                    </div>
                                </div>
                                
                                {{-- IS1961: "petro_whatsapp" removed from the Petro General
                                     section of Superadmin -> Manage.
                                     HIDDEN, NOT DELETED - the value is still posted, so whatever
                                     each business has today survives the next save. Same approach
                                     as the IS-1917 removals above. --}}
                                {!! Form::hidden('petro_whatsapp', !empty($manage_module_enable['petro_whatsapp']) ? 1 : 0) !!}
                                
                                {{-- IS1961: "blocked_pump_operators" removed from the Petro General
                                     section of Superadmin -> Manage.
                                     HIDDEN, NOT DELETED - the value is still posted, so whatever
                                     each business has today survives the next save. Same approach
                                     as the IS-1917 removals above. --}}
                                {!! Form::hidden('blocked_pump_operators', !empty($manage_module_enable['blocked_pump_operators']) ? 1 : 0) !!}
                                
                                {{-- IS1961: "tanks_transaction_details" removed from the Petro General
                                     section of Superadmin -> Manage.
                                     HIDDEN, NOT DELETED - the value is still posted, so whatever
                                     each business has today survives the next save. Same approach
                                     as the IS-1917 removals above. --}}
                                {!! Form::hidden('tanks_transaction_details', !empty($manage_module_enable['tanks_transaction_details']) ? 1 : 0) !!}
                                
                                {{-- IS1961: "tanks_transaction_summary" removed from the Petro General
                                     section of Superadmin -> Manage.
                                     HIDDEN, NOT DELETED - the value is still posted, so whatever
                                     each business has today survives the next save. Same approach
                                     as the IS-1917 removals above. --}}
                                {!! Form::hidden('tanks_transaction_summary', !empty($manage_module_enable['tanks_transaction_summary']) ? 1 : 0) !!}
                                
                                {{-- IS1961: "customer_bill_vat_prefix" removed from the Petro General
                                     section of Superadmin -> Manage.
                                     HIDDEN, NOT DELETED - the value is still posted, so whatever
                                     each business has today survives the next save. Same approach
                                     as the IS-1917 removals above. --}}
                                {!! Form::hidden('customer_bill_vat_prefix', !empty($manage_module_enable['customer_bill_vat_prefix']) ? 1 : 0) !!}
                                
                                {{-- IS1961: "petro_notification_template" removed from the Petro General
                                     section of Superadmin -> Manage.
                                     HIDDEN, NOT DELETED - the value is still posted, so whatever
                                     each business has today survives the next save. Same approach
                                     as the IS-1917 removals above. --}}
                                {!! Form::hidden('petro_notification_template', !empty($manage_module_enable['petro_notification_template']) ? 1 : 0) !!}
                                
                                {{-- IS1961: "disable_shift_no_direct_settlement" removed from the Petro General
                                     section of Superadmin -> Manage.
                                     HIDDEN, NOT DELETED - the value is still posted, so whatever
                                     each business has today survives the next save. Same approach
                                     as the IS-1917 removals above. --}}
                                {!! Form::hidden('disable_shift_no_direct_settlement', !empty($manage_module_enable['disable_shift_no_direct_settlement']) ? 1 : 0) !!}

                                
                        </div>
                       
                        
                        <div class="row">
                            <div class="col-sm-3 product_count">
                                    <div class="form-group">
                                        {!! Form::label('allowed_tanks', __('superadmin::lang.allowed_tanks').':') !!}
                                        {!! Form::number('allowed_tanks', !empty($manage_module_enable['allowed_tanks']) ? $manage_module_enable['allowed_tanks']
                                        : $previous_package_data['allowed_tanks'], ['class' => 'form-control', 'required', 'min' =>
                                        0]) !!}
    
                                        <span class="help-block">
                                        @lang('superadmin::lang.infinite_help')
                                    </span>
                                    </div>
                                </div>
                                
                            <div class="col-md-3 text-danger text-right">
                                <br>{!! Form::label('number_of_pumps', __('superadmin::lang.number_of_pumps'). ':', ['class' =>
                                'search_label']) !!}
                            </div>
                            @foreach ($business_locations as $location)
                                <div class="col-md-3">
                                    {!! Form::label('location_pumps', $location->name) !!}
                                    {!! Form::text('module_permission_location[number_of_pumps]['.$location->id.']',
                                    !empty($module_permission_locations_value['number_of_pumps']->locations[$location->id]) ?
                                    $module_permission_locations_value['number_of_pumps']->locations[$location->id] : null,
                                    ['class' => 'form-control']) !!}
                                </div>
                            @endforeach
                           
                        </div>
                            
                            
                           
                         </div>
                          
                    </div>

                    {{-- Petro PD Module Section --}}