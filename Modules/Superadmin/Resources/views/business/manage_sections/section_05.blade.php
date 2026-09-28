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
                            <h4>Customers - Bank</h4>
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
                                     <label class="search_label">Customers - Bank</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('loan_module', 0) !!}
                                    {!! Form::checkbox('loan_module', 1,
                                    !empty($manage_module_enable['loan_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['contact_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['contact_expiry_date']))
                                        @if(strtotime($module_activation_data['contact_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['contact_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('contact_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['contact_interval']) ?
                                        $module_activation_data['contact_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('contact_length', !empty($module_activation_data['contact_length']) ?
                                        $module_activation_data['contact_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('contact_activated_on', !empty($module_activation_data['contact_activated_on']) ?
                                    $module_activation_data['contact_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('contact_expiry_date', !empty($module_activation_data['contact_expiry_date']) ?
                                    $module_activation_data['contact_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('contact_price', !empty($module_activation_data['contact_price']) ?
                                    $module_activation_data['contact_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            {{-- Loan Module: Enable for Locations --}}
                            <div class="row loan_module_locations check_group">
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
                                                {!!
                                                Form::checkbox('module_permission_location[loan_module]['.$location->id.']',
                                                1,
                                                !empty($module_permission_locations_value['loan_module']->locations) ?
                                                array_key_exists($location->id,
                                                $module_permission_locations_value['loan_module']->locations) : false,
                                                ['class' => 'input-icheck-red ch_select']) !!}
                                                {{$location->name}}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <hr>
                            {{-- Loan Module: Sub-permissions --}}
                            <div class="row">
                                <div class="col-md-4">
                                    {!! Form::hidden('loan_show_contact_type', 0) !!}
                                    {!! Form::checkbox('loan_show_contact_type', 1,
                                    !empty($manage_module_enable['loan_show_contact_type']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select']) !!}
                                    <label class="search_label">Show "Contact Type" dropdown in Add/Edit Loan</label>
                                </div>
                            </div>
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="search_label">@lang('superadmin::lang.customer_credit_notification_type')</label><br>
                                    
                                    {!! Form::select('customer_credit_notification_type[]', ['settlement' => __('contact.settlement'), 'customer_bill' => __('contact.bill_to_customer'),'pumper_dashboard' => __('contact.pumper_dashboard')],
                                    $customer_credit_notification_type, ['class' => 'form-control
                                    select2', 'multiple', 'id' => 'customer_credit_notification_type']) !!}
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
                                
                               
                                <div class="col-md-3">
                                    {!! Form::checkbox('contact_supplier', 1,
                                    !empty($manage_module_enable['contact_supplier']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.contact_supplier')</label>
                                </div>
                          
                                <div class="col-md-3">
                                    {!! Form::checkbox('contact_customer', 1,
                                    !empty($manage_module_enable['contact_customer']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.contact_customer')</label>
                                </div>
                                <div class="clearfix"></div>
                                
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_supplier', 1, !empty($manage_module_enable['contact_supplier']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_supplier']) !!}
                                        {{__('superadmin::lang.supplier_tab_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_customer', 1, !empty($manage_module_enable['contact_customer']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_customer']) !!}
                                        {{__('superadmin::lang.customer_tab_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_group_customer', 1, !empty($manage_module_enable['contact_group_customer']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_group_customer']) !!}
                                        {{__('superadmin::lang.contact_group_customer_tab_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_group_supplier', 1, !empty($manage_module_enable['contact_group_supplier']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_group_supplier']) !!}
                                        {{__('superadmin::lang.contact_group_supplier_tab_page')}}
                                    </label>
                                </div>
                            </div>
                        
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('import_contact', 1, !empty($manage_module_enable['import_contact']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'import_contact']) !!}
                                        {{__('superadmin::lang.import_contact_tab_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('customer_reference', 1, !empty($manage_module_enable['customer_reference']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'customer_reference']) !!}
                                        {{__('superadmin::lang.customer_reference_tab_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('customer_statement', 1, !empty($manage_module_enable['customer_statement']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'customer_statement']) !!}
                                        {{__('superadmin::lang.customer_statement_tab_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('customer_payment', 1, !empty($manage_module_enable['customer_payment']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=> 'customer_payment']) !!}
                                        {{__('superadmin::lang.customer_payment_tab_page')}}
                                    </label>
                                </div>
                            </div>
                        
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('outstanding_received', 1, !empty($manage_module_enable['outstanding_received']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'outstanding_received']) !!}
                                        {{__('superadmin::lang.outstanding_received_tab_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('stock_taking_page', 1, !empty($manage_module_enable['stock_taking_page']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'stock_taking_page']) !!}
                                        {{__('superadmin::lang.stock_taking_page')}}
                                    </label>

                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('issue_payment_detail', 1, !empty($manage_module_enable['issue_payment_detail']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'issue_payment_detail']) !!}
                                        {{__('superadmin::lang.issue_payment_detail_tab_page')}}
                                    </label>

                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('edit_received_outstanding', 1, !empty($manage_module_enable['edit_received_outstanding']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'edit_received_outstanding']) !!}
                                        {{__('superadmin::lang.edit_received_outstanding')}}
                                    </label>
                                </div>
                            </div>
                            
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('customer_payment_simple', 1, !empty($manage_module_enable['customer_payment_simple']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'customer_payment_simple']) !!}
                                        {{__('superadmin::lang.customer_payment_simple')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('customer_payment_bulk', 1, !empty($manage_module_enable['customer_payment_bulk']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'customer_payment_bulk']) !!}
                                        {{__('superadmin::lang.customer_payment_bulk')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('list_customer_payments', 1, !empty($manage_module_enable['list_customer_payments']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'list_customer_payments']) !!}
                                        {{__('superadmin::lang.list_customer_payments')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('customer_interest', 1, !empty($manage_module_enable['customer_interest']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'customer_interest']) !!}
                                        {{__('superadmin::lang.customer_interest')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('interest_settings', 1, !empty($manage_module_enable['interest_settings']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'interest_settings']) !!}
                                        {{__('superadmin::lang.interest_settings')}}
                                    </label>
                                </div>
                            </div>
                            
                             <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('ledger_discount', 1, !empty($manage_module_enable['ledger_discount']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'ledger_discount']) !!}
                                        {{__('superadmin::lang.ledger_discount')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('customer_statement_pmt', 1, !empty($manage_module_enable['customer_statement_pmt']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'customer_statement_pmt']) !!}
                                        {{__('contact.customer_statements_with_payment')}}
                                    </label>
                                </div>
                            </div>
                            
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_list_customer_loans', 1, !empty($manage_module_enable['contact_list_customer_loans']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_list_customer_loans']) !!}
                                        {{__('superadmin::lang.contact_list_customer_loans')}}
                                    </label>
                                </div>
                            </div>

                            <!-- <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('loan_module', 1, !empty($manage_module_enable['loan_module']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'loan_module']) !!}
                                        {{__('loan::lang.loan')}}
                                    </label>
                                </div>
                            </div> -->
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_settings', 1, !empty($manage_module_enable['contact_settings']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_settings']) !!}
                                        {{__('superadmin::lang.contact_settings')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_list_supplier_map_products', 1, !empty($manage_module_enable['contact_list_supplier_map_products']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_list_supplier_map_products']) !!}
                                        {{__('superadmin::lang.contact_list_supplier_map_products')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_add_supplier_products', 1, !empty($manage_module_enable['contact_add_supplier_products']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_add_supplier_products']) !!}
                                        {{__('superadmin::lang.contact_add_supplier_products')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_import_opening_balalnces', 1, !empty($manage_module_enable['contact_import_opening_balalnces']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_import_opening_balalnces']) !!}
                                        {{__('superadmin::lang.contact_import_opening_balalnces')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_returned_cheque_details', 1, !empty($manage_module_enable['contact_returned_cheque_details']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_returned_cheque_details']) !!}
                                        {{__('superadmin::lang.contact_returned_cheque_details')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                {!! Form::checkbox('delete_customer_statement', 1,
                                (!empty($manage_module_enable['delete_customer_statement'])  || !array_key_exists('delete_customer_statement',$manage_module_enable)) ? true : false, ['class' =>
                                'input-icheck-red
                                ch_select']) !!}<label
                                        class="search_label">@lang('lang_v1.vat.delete_customer_statement')</label>
                            </div>
                            
                            <div class="col-md-3">
                                {!! Form::checkbox('delete_statement_payment', 1,
                                (!empty($manage_module_enable['delete_statement_payment'])  || !array_key_exists('delete_statement_payment',$manage_module_enable)) ? true : false, ['class' =>
                                'input-icheck-red
                                ch_select']) !!}<label
                                        class="search_label">@lang('lang_v1.vat.delete_statement_payment')</label>
                            </div>
                              <div class="col-md-3">
                                {!! Form::checkbox('credit_customer_manual_bills', 1,
                                (!empty($manage_module_enable['credit_customer_manual_bills'])  || !array_key_exists('credit_customer_manual_bills',$manage_module_enable)) ? true : false, ['class' =>
                                'input-icheck-red
                                ch_select']) !!}<label
                                        class="search_label">Credit Customer Manual Bills</label>
                            </div>
                            <div class="col-md-3">
                                {!! Form::checkbox('manual_bill', 1,
                                !empty($manage_module_enable['manual_bill']) ? true : false,
                                ['class' => 'input-icheck-red ch_select', 'id'=>'manual_bill']) !!}
                                <label class="search_label">Manual Bill</label>
                            </div>

                        </div>
                            
                         </div>
                          
                    </div>
                    
                    {{-- My Health Module --}}
                    <div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                        <div class="card-header text-center">
                            <h4>My Health</h4>
                            <hr>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::hidden('my_health_module', 0) !!}
                                            {!! Form::checkbox('my_health_module', 1, !empty($manage_module_enable['my_health_module']) || !empty($manage_module_enable['myhealth_module']) || !empty($manage_module_enable['myhealthmembers_module']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'my_health_module']) !!}
                                            My Health
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- End My Health Module --}}

                    {{-- Customers Module is already rendered above through
                         customers::superadmin.manage_customers_module.
                         Do not add a second Customers section here: duplicate
                         hidden checkbox values can overwrite Bulk Payment as disabled. --}}

                    {{-- Leads-New Module --}}
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4>Leads-New</h4>
                            <hr>
                          </div>
                          <div class="card-body">
                            <div class="row">
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::hidden('leads_new_module', 0) !!}
                                            {!! Form::checkbox('leads_new_module', 1, !empty($manage_module_enable['leads_new_module']) || !empty($manage_module_enable['enable_leads_new']) || !empty($manage_module_enable['leadsnew_module']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'leads_new_module']) !!}
                                            Enable Leads-New Module
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::hidden('leads_new_dashboard', 0) !!}
                                            {!! Form::checkbox('leads_new_dashboard', 1, !empty($manage_module_enable['leads_new_dashboard']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'leads_new_dashboard']) !!}
                                            Leads-New Dashboard
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::hidden('leads_new_leads', 0) !!}
                                            {!! Form::checkbox('leads_new_leads', 1, !empty($manage_module_enable['leads_new_leads']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'leads_new_leads']) !!}
                                            Leads-New Leads
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::hidden('leads_new_reports', 0) !!}
                                            {!! Form::checkbox('leads_new_reports', 1, !empty($manage_module_enable['leads_new_reports']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'leads_new_reports']) !!}
                                            Leads-New Reports
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::hidden('leads_new_settings', 0) !!}
                                            {!! Form::checkbox('leads_new_settings', 1, !empty($manage_module_enable['leads_new_settings']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'leads_new_settings']) !!}
                                            Leads-New Settings
                                        </label>
                                    </div>
                                </div>
                            </div>
                         </div>
                    </div>
                    {{-- End Leads-New Module --}}

                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.report_module')</h4>
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
                                    <label class="search_label">@lang('superadmin::lang.report_module')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('report_module', 0) !!}
                                    {!! Form::checkbox('report_module', 1,
                                    !empty($manage_module_enable['report_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['report_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['report_expiry_date']))
                                        @if(strtotime($module_activation_data['report_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['report_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('report_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['report_interval']) ?
                                        $module_activation_data['report_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('report_length', !empty($module_activation_data['report_length']) ?
                                        $module_activation_data['report_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('report_activated_on', !empty($module_activation_data['report_activated_on']) ?
                                    $module_activation_data['report_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('report_expiry_date', !empty($module_activation_data['report_expiry_date']) ?
                                    $module_activation_data['report_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('report_price', !empty($module_activation_data['report_price']) ?
                                    $module_activation_data['report_price'] : null, ['class' => 'form-control']) !!}
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
                                
                                <div class="col-md-3">
                                    {!! Form::checkbox('verification_report', 1,
                                    !empty($manage_module_enable['verification_report']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.verification_report')</label>
                                </div>
                                
                                <div class="col-md-3">
                                    {!! Form::checkbox('monthly_report', 1,
                                    !empty($manage_module_enable['monthly_report']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.monthly_report')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('comparison_report', 1,
                                    !empty($manage_module_enable['comparison_report']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.comparison_report')</label>
                                </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label" class="flex-label">
                                        {!! Form::checkbox('product_report', 1, !empty($manage_module_enable['product_report']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'product_report']) !!}
                                        {{__('superadmin::lang.product_report_sub_menu')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('payment_status_report', 1, !empty($manage_module_enable['payment_status_report']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'payment_status_report']) !!}
                                        {{__('superadmin::lang.payment_status_report_sub_menu')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('report_daily', 1, !empty($manage_module_enable['report_daily']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'report_daily']) !!}
                                        {{__('superadmin::lang.report_daily_tab_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('report_daily_summary', 1, !empty($manage_module_enable['report_daily_summary']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'report_daily_summary']) !!}
                                        {{__('superadmin::lang.report_daily_summary_tab_page')}}
                                    </label>
                                </div>
                            </div>
                        
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('report_register', 1, !empty($manage_module_enable['report_register']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'report_register']) !!}
                                        {{__('superadmin::lang.report_register_tab_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('report_profit_loss', 1, !empty($manage_module_enable['report_profit_loss']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'report_profit_loss']) !!}
                                        {{__('superadmin::lang.report_profit_loss_tab_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('report_credit_status', 1, !empty($manage_module_enable['report_credit_status']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'report_credit_status']) !!}
                                        {{__('superadmin::lang.report_credit_status_tab_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('activity_report', 1, !empty($manage_module_enable['activity_report']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'activity_report']) !!}
                                        {{__('superadmin::lang.activity_report_sub_menu')}}
                                    </label>
                                </div>
                            </div>
                        
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_report', 1, !empty($manage_module_enable['contact_report']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'contact_report']) !!}
                                        {{__('superadmin::lang.contact_report_sub_menu')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('trending_product', 1, !empty($manage_module_enable['trending_product']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'trending_product']) !!}
                                        {{__('superadmin::lang.trending_product_sub_menu')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('user_activity', 1, !empty($manage_module_enable['user_activity']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'user_activity']) !!}
                                        {{__('superadmin::lang.user_activity_sub_menu')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('report_verification', 1, !empty($manage_module_enable['report_verification']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'report_verification']) !!}
                                        @lang('lang_v1.verification_reports')
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('helpguide', 1, !empty($manage_module_enable['helpguide']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'helpguide']) !!}
                                        Help Guide
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('report_table', 1, !empty($manage_module_enable['report_table']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'report_table']) !!}
                                        @lang('lang_v1.table_report')
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('report_staff_service', 1, !empty($manage_module_enable['report_staff_service']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'report_staff_service']) !!}
                                        @lang('lang_v1.service_staff_reports')
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::hidden('customized_report', 0) !!}
                                        {!! Form::checkbox('customized_report', 1, !empty($manage_module_enable['customized_report']) || !empty($manage_module_enable['customized_reports_module']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'customized_report']) !!}
                                        @lang('lang_v1.customized_report')
                                    </label>
                                </div>
                            </div>
                            
                        </div>
                         </div>
                          
                    </div>

                    {{-- Modified by Engr. Alex -- task 7882: Issue 7 - Customized Reports section --}}
                    <div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                        <div class="card-header text-center">
                            <h4>Customized Reports</h4>
                            <hr>
                        </div>
                        <div class="card-body">
                            <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2"><b>Module Name</b></div>
                                <div class="col-md-1"><b>Enable</b></div>
                                <div class="col-md-1"><b>Status</b></div>
                                <div class="col-md-1"><b>Interval</b></div>
                                <div class="col-md-1"><b>Interval Length</b></div>
                                <div class="col-md-2"><b>Activated on</b></div>
                                <div class="col-md-2"><b>Expiry</b></div>
                                <div class="col-md-2 text-center"><h5><b>Module Price</b></h5></div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">Customized Reports</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::hidden('customized_reports_module', 0) !!}
                                    {!! Form::checkbox('customized_reports_module', 1, !empty($manage_module_enable['customized_reports_module']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id' => 'customized_reports_module']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['customized_reports_expiry_date']))
                                        <span class="badge badge-danger">not set</span>
                                    @elseif(strtotime($module_activation_data['customized_reports_expiry_date']) >= time())
                                        <span class="label label-pill label-primary">active</span>
                                    @else
                                        <span class="label label-pill label-danger">expired</span>
                                    @endif
                                </div>
                                <div class="col-md-1">
                                    {!! Form::select('customized_reports_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'],
                                        !empty($module_activation_data['customized_reports_interval']) ? $module_activation_data['customized_reports_interval'] : 'Years',
                                        ['class' => 'form-control act_interval', 'style' => 'width:100%']) !!}
                                </div>
                                <div class="col-md-1">
                                    {!! Form::number('customized_reports_length', !empty($module_activation_data['customized_reports_length']) ? $module_activation_data['customized_reports_length'] : 1,
                                        ['class' => 'form-control act_length', 'min' => 1]) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('customized_reports_activated_on', !empty($module_activation_data['customized_reports_activated_on']) ? $module_activation_data['customized_reports_activated_on'] : \Carbon\Carbon::today()->format('Y-m-d'),
                                        ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('customized_reports_expiry_date', !empty($module_activation_data['customized_reports_expiry_date']) ? $module_activation_data['customized_reports_expiry_date'] : null,
                                        ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('customized_reports_price', !empty($module_activation_data['customized_reports_price']) ? $module_activation_data['customized_reports_price'] : null,
                                        ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label><input type="checkbox" class="check_all input-icheck-red"> {{ __('role.select_all') }}</label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::checkbox('cr_add_lioc_statement', 1, !empty($manage_module_enable['cr_add_lioc_statement']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id' => 'cr_add_lioc_statement']) !!}
                                            Add LIOC Statement
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::checkbox('cr_list_lioc_statements', 1, !empty($manage_module_enable['cr_list_lioc_statements']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id' => 'cr_list_lioc_statements']) !!}
                                            List LIOC Statements
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::checkbox('cr_edit_lioc_statement', 1, !empty($manage_module_enable['cr_edit_lioc_statement']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id' => 'cr_edit_lioc_statement']) !!}
                                            Edit List LIOC Statements
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::checkbox('cr_prefix_numbers', 1, !empty($manage_module_enable['cr_prefix_numbers']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id' => 'cr_prefix_numbers']) !!}
                                            Prefix &amp; Numbers
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.settings_module')</h4>
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
                                    <label class="search_label">@lang('superadmin::lang.settings_module')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('settings_module', 0) !!}
                                    {!! Form::checkbox('settings_module', 1,
                                    !empty($manage_module_enable['settings_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['settings_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['settings_expiry_date']))
                                        @if(strtotime($module_activation_data['settings_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['settings_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('settings_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['settings_interval']) ?
                                        $module_activation_data['settings_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('settings_length', !empty($module_activation_data['settings_length']) ?
                                        $module_activation_data['settings_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('settings_activated_on', !empty($module_activation_data['settings_activated_on']) ?
                                    $module_activation_data['settings_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('settings_expiry_date', !empty($module_activation_data['settings_expiry_date']) ?
                                    $module_activation_data['settings_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('settings_price', !empty($module_activation_data['settings_price']) ?
                                    $module_activation_data['settings_price'] : null, ['class' => 'form-control']) !!}
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
                                <div class="col-md-3">
                                    {!! Form::checkbox('business_settings', 1,
                                    !empty($manage_module_enable['business_settings']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.business_settings')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('business_location', 1,
                                    !empty($manage_module_enable['business_location']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.business_location')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('invoice_settings', 1,
                                    !empty($manage_module_enable['invoice_settings']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.invoice_settings')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('tax_rates', 1,
                                    !empty($manage_module_enable['tax_rates']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.tax_rates')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('settings_otp_verification', 1,
                                    !empty($manage_module_enable['settings_otp_verification']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.OTP_verification_enabled')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('settings_pay_online', 1,
                                    !empty($manage_module_enable['settings_pay_online']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">Pay Online</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('settings_reports_configurations', 1,
                                    !empty($manage_module_enable['settings_reports_configurations']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">Reports Configurations</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('settings_user_locations', 1,
                                    !empty($manage_module_enable['settings_user_locations']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">User Locations</label>
                                </div>
                            </div> 
                            <hr>
                            <h4 class="text-center">Store Settings</h4>
                            <div class="row" style="margin-top: 15px;">

                                <div class="col-md-12">
                                    <label><b>Store Label Option (Only one can be selected)</b></label>
                                </div>

                                <div class="col-md-4">
                                    <label>
                                        {!! Form::radio('store_label_type', 'stores',
                                            isset($manage_module_enable['store_label_type']) 
                                            && $manage_module_enable['store_label_type'] == 'stores' ? true : false,
                                            ['class' => 'input-icheck-red']
                                        ) !!}
                                        Stores
                                    </label>
                                </div>

                                <div class="col-md-4">
                                    <label>
                                        {!! Form::radio('store_label_type', 'stores_vehicle',
                                            isset($manage_module_enable['store_label_type']) 
                                            && $manage_module_enable['store_label_type'] == 'stores_vehicle' ? true : false,
                                            ['class' => 'input-icheck-red']
                                        ) !!}
                                        Stores / Vehicle
                                    </label>
                                </div>

                                <div class="col-md-4">
                                    <label>
                                        {!! Form::radio('store_label_type', 'vehicles',
                                            isset($manage_module_enable['store_label_type']) 
                                            && $manage_module_enable['store_label_type'] == 'vehicles' ? true : false,
                                            ['class' => 'input-icheck-red']
                                        ) !!}
                                        Vehicles
                                    </label>
                                </div>
                            </div>

                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.sale_module')</h4>
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
                                    <label class="search_label">@lang('superadmin::lang.sale_module')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('sale_module', 0) !!}
                                    {!! Form::checkbox('sale_module', 1,
                                    !empty($manage_module_enable['sale_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['sale_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['sale_expiry_date']))
                                        @if(strtotime($module_activation_data['sale_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['sale_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('sale_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['sale_interval']) ?
                                        $module_activation_data['sale_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('sale_length', !empty($module_activation_data['sale_length']) ?
                                        $module_activation_data['sale_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('sale_activated_on', !empty($module_activation_data['sale_activated_on']) ?
                                    $module_activation_data['sale_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('sale_expiry_date', !empty($module_activation_data['sale_expiry_date']) ?
                                    $module_activation_data['sale_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('sale_price', !empty($module_activation_data['sale_price']) ?
                                    $module_activation_data['sale_price'] : null, ['class' => 'form-control']) !!}
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
                                
                                <div class="col-md-3">
                                    {!! Form::checkbox('all_sales', 1,
                                    !empty($manage_module_enable['all_sales']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.all_sales')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('add_sale', 1,
                                    !empty($manage_module_enable['add_sale']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.add_sale')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('pos_sale', 1,
                                    !empty($manage_module_enable['pos_sale']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.pos')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('list_pos', 1,
                                    !empty($manage_module_enable['list_pos']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.list_pos')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('list_draft', 1,
                                    !empty($manage_module_enable['list_draft']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.list_draft')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('list_quotation', 1,
                                    !empty($manage_module_enable['list_quotation']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.list_quotation')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('list_sell_return', 1,
                                    !empty($manage_module_enable['list_sell_return']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.list_sell_return')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('shipment', 1,
                                    !empty($manage_module_enable['shipment']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.shipment')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('discount', 1,
                                    !empty($manage_module_enable['discount']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.discount')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('import_sale', 1,
                                    !empty($manage_module_enable['import_sale']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.import_sale')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('reserved_stock', 1,
                                    !empty($manage_module_enable['reserved_stock']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.reserved_stock')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('list_orders', 1,
                                    !empty($manage_module_enable['list_orders']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.list_orders')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('upload_orders', 1,
                                    !empty($manage_module_enable['upload_orders']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.upload_orders')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('subcriptions', 1,
                                    !empty($manage_module_enable['subcriptions']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.subcriptions')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('over_limit_sales', 1,  
                                    !empty($manage_module_enable['over_limit_sales']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.over_limit_sales')</label> 
                                </div>
                            
                                <div class="col-md-3">
                                    {!! Form::checkbox('status_order', 1,
                                    !empty($manage_module_enable['status_order']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.status_order')</label>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::checkbox('pos_button_on_top_belt', 1, !empty($manage_module_enable['pos_button_on_top_belt']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'pos_button_on_top_belt']) !!}
                                            {{__('superadmin::lang.pos_button_on_top_belt')}}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.leads_module')</h4>
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
                                    <label class="search_label">@lang('superadmin::lang.leads_module')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('leads_module', 0) !!}
                                    {!! Form::checkbox('leads_module', 1,
                                    !empty($manage_module_enable['leads_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['leads_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['leads_expiry_date']))
                                        @if(strtotime($module_activation_data['leads_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['leads_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('leads_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['leads_interval']) ?
                                        $module_activation_data['leads_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('leads_length', !empty($module_activation_data['leads_length']) ?
                                        $module_activation_data['leads_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('leads_activated_on', !empty($module_activation_data['leads_activated_on']) ?
                                    $module_activation_data['leads_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('leads_expiry_date', !empty($module_activation_data['leads_expiry_date']) ?
                                    $module_activation_data['leads_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('leads_price', !empty($module_activation_data['leads_price']) ?
                                    $module_activation_data['leads_price'] : null, ['class' => 'form-control']) !!}
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
                                
                                <div class="col-md-3">
                                    {!! Form::checkbox('leads', 1,
                                    !empty($manage_module_enable['leads']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.leads')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('day_count', 1,
                                    !empty($manage_module_enable['day_count']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.day_count')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('leads_import', 1,
                                    !empty($manage_module_enable['leads_import']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.leads_import')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('leads_settings', 1,
                                    !empty($manage_module_enable['leads_settings']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.settings')</label>
                                </div>
                            </div>
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.enable_sms')</h4>
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
                                
                                <div class="row">
                                    <div class="col-md-2">
                                        <label class="search_label">@lang('superadmin::lang.enable_sms')</label>
                                    </div>
                                    
                                    <div class="col-md-1">
                                    {!! Form::hidden('enable_sms', 0) !!}
                                        {!! Form::checkbox('enable_sms', 1, !empty($manage_module_enable['enable_sms']) ? true :
                                        false,
                                        ['class' => 'input-icheck-red ch_select']) !!}
                                    </div>
                                        <div class="col-md-1">
                                            @if(empty($module_activation_data['enable_sms_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['enable_sms_expiry_date']))
                                        @if(strtotime($module_activation_data['enable_sms_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['enable_sms_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                        </div>
                                        
                                        <div class="col-md-1">
                                        {!! Form::select('enable_sms_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['enable_sms_interval']) ?
                                        $module_activation_data['enable_sms_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('enable_sms_length', !empty($module_activation_data['enable_sms_length']) ?
                                        $module_activation_data['enable_sms_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                        
                                        <div class="col-md-2">
                                            {!! Form::date('enable_sms_activated_on', !empty($module_activation_data['enable_sms_activated_on']) ?
                                            $module_activation_data['enable_sms_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                        </div>
                                        <div class="col-md-2">
                                            {!! Form::date('enable_sms_expiry_date', !empty($module_activation_data['enable_sms_expiry_date']) ?
                                            $module_activation_data['enable_sms_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                        </div>
                                        <div class="col-md-2">
                                            {!! Form::text('enable_sms_price', !empty($module_activation_data['enable_sms_price']) ?
                                            $module_activation_data['enable_sms_price'] : null, ['class' => 'form-control']) !!}
                                        </div>
                                </div>

                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.list_sms')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::checkbox('list_sms', 1,
                                    !empty($manage_module_enable['list_sms']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['list_sms_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['list_sms_expiry_date']))
                                        @if(strtotime($module_activation_data['list_sms_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['list_sms_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('list_sms_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['list_sms_interval']) ?
                                    $module_activation_data['list_sms_interval'] : null, [
                                                'id' => 'status',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%',
                                                'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('list_sms_length', !empty($module_activation_data['list_sms_length']) ?
                                    $module_activation_data['list_sms_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('list_sms_activated_on', !empty($module_activation_data['list_sms_activated_on']) ?
                                    $module_activation_data['list_sms_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('list_sms_expiry_date', !empty($module_activation_data['list_sms_expiry_date']) ?
                                    $module_activation_data['list_sms_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('list_sms_price', !empty($module_activation_data['list_sms_price']) ?
                                    $module_activation_data['list_sms_price'] : null, ['class' => 'form-control']) !!}
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
                                            {!! Form::checkbox('access_sms_settings', 1,!empty($manage_module_enable['access_sms_settings']) ? true : false, ['class' => 'input-icheck-red
                                                ch_select', 'id' => 'sms_settings_checkbox']) !!}
                                            @lang('superadmin::lang.access_sms_settings')
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::checkbox('sms_ledger', 1, !empty($manage_module_enable['sms_ledger']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                            {{__('lang_v1.sms_ledger')}}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::checkbox('sms_delivery_report', 1, !empty($manage_module_enable['sms_delivery_report']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                            {{__('lang_v1.sms_delivery_report')}}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::checkbox('sms_history', 1, !empty($manage_module_enable['sms_history']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                            {{__('lang_v1.sms_history')}}
                                        </label>
                                    </div>
                                </div>
                            </div>
                                
                            <div class="row sms_setting_div">
                                 @include('business.partials.settings_sms')
                            </div>
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.enable_sms')</h4>
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
                                    <label class="search_label">@lang('lang_v1.smsmodule')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('smsmodule_module', 0) !!}
                                    {!! Form::checkbox('smsmodule_module', 1,
                                    !empty($manage_module_enable['smsmodule_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['smsmodule_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['smsmodule_expiry_date']))
                                        @if(strtotime($module_activation_data['smsmodule_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['smsmodule_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('smsmodule_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['smsmodule_interval']) ?
                                    $module_activation_data['smsmodule_interval'] : null, [
                                                'id' => 'status',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%',
                                                'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('smsmodule_length', !empty($module_activation_data['smsmodule_length']) ?
                                    $module_activation_data['smsmodule_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('smsmodule_activated_on', !empty($module_activation_data['smsmodule_activated_on']) ?
                                    $module_activation_data['smsmodule_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('smsmodule_expiry_date', !empty($module_activation_data['smsmodule_expiry_date']) ?
                                    $module_activation_data['smsmodule_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('smsmodule_price', !empty($module_activation_data['smsmodule_price']) ?
                                    $module_activation_data['smsmodule_price'] : null, ['class' => 'form-control']) !!}
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
                                            {!! Form::checkbox('sms_quick_send', 1,!empty($manage_module_enable['sms_quick_send']) ? true : false, ['class' => 'input-icheck-red
                                                ch_select', 'id' => 'sms_settings_checkbox']) !!}
                                            @lang('lang_v1.sms_quick_send')
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::checkbox('sms_from_file', 1, !empty($manage_module_enable['sms_from_file']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                            {{__('lang_v1.sms_from_file')}}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::checkbox('sms_campaign', 1, !empty($manage_module_enable['sms_campaign']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                            {{__('lang_v1.sms_campaign')}}
                                        </label>
                                    </div>
                                </div>
                               
                            </div>
                         </div>
                          
                    </div>
                    