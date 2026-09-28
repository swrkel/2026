
@php
                    
    $business_id = request()
        ->session()
        ->get('user.business_id');
    
    $pacakge_details = [];
        
    $subscription = Modules\Superadmin\Entities\Subscription::active_subscription($business_id);
    if (!empty($subscription)) {
        $pacakge_details = $subscription->package_details;
    }

    // Check VAT Module Main permissions using ModuleUtil
    $moduleUtil = new \App\Utils\ModuleUtil();
    
    // Check if VAT Module Main is enabled
    $vat_module_main_enabled = !empty($pacakge_details['vat_module_main']) && $pacakge_details['vat_module_main'] == 1;
    
    // VAT Module Main sub-permissions
    /*
        MA-002 (S-608) - MENU VISIBILITY NOW HONOURS THE MANAGE PAGE.

        These used hasThePermissionInSubscription(), which returns TRUE for
        anyone holding the superadmin permission - before it reads the setting
        at all:

            if (SidebarPermissionUtil::hasSuperAdminBypass()) { return true; }

        and hasSuperAdminBypass() is satisfied by can('superadmin') alone. So
        every VAT sub-module appeared for such a user however the Manage page
        was configured. That is exactly what S-608 reports.

        ModuleUtil's own comment already says this is wrong for menus:
            "Login As Business deliberately retains the Super Admin permission
             for functional support access, so permission alone is not a
             sufficient menu visibility check."

        hasConfiguredPermissionInSubscription() reads the SAME saved setting
        WITHOUT the bypass, and is already used this way in PetroDirect. It is
        the right call for deciding what a menu shows.

        WORTH KNOWING ABOUT THE STORED VALUE
        An unchecked box is not saved as 0 - it is absent from package_details
        entirely, because an unchecked HTML checkbox posts nothing. Both
        methods treat absent as "not granted", so nothing else needed changing.

        FUNCTIONAL ACCESS IS UNTOUCHED. Only menu rendering is decided here. A
        superadmin who needs to reach a screen for support can still do so by
        URL - which is the second half of S-608 and is NOT fixed by this file.
        See the README.
    */
    $vat_main_credit_bill = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_credit_bill');
    $vat_main_sale = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_sale');
    $vat_main_list_vat_sale = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_list_vat_sale');
    $vat_main_purchase = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_purchase');
    $vat_main_list_vat_purchase = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_list_vat_purchase');
    $vat_main_expense = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_expense');
    $vat_main_list_vat_expense = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_list_vat_expense');
    $vat_main_products = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_products');
    $vat_main_contacts = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_contacts');
    $vat_main_meter_sales = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_meter_sales');
    $vat_main_customized_invoices = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_customized_invoices');
    $vat_main_fleet_invoices = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_fleet_invoices');
    $vat_main_linked_accounts = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_linked_accounts');
    $vat_main_delete_customer_statement = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_delete_customer_statement');
    $vat_main_delete_statement_payments = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_delete_statement_payments');
    $vat_main_enabled_126_statement = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_enabled_126_statement');
    
    // New VAT module permissions
    $vat_main_invoice = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_invoice');
    $vat_main_invoice2 = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_invoice2');
    $vat_main_report = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_report');
    $vat_main_report_ledger = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_report_ledger');
    $vat_main_settings = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_settings');
    $vat_main_statement = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_statement');
    $vat_main_schedule = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_schedule');
    $vat_main_import_contacts = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_import_contacts');
    $vat_main_print_2026 = \App\Utils\ModuleUtil::hasConfiguredPermissionInSubscription($business_id, 'vat_main_print_2026');

@endphp

@if($vat_module_main_enabled)
<!-- Start VAT Module -->
<li class="nav-item {{ in_array($request->segment(1), ['vat-module']) ? 'active active-sub' : '' }}">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#vat-menu" aria-expanded="true" aria-controls="vat-menu">
        <i class="ti-id-badge"></i>
        <span>@lang('vat::lang.vat_module')</span>
    </a>
    <div id="vat-menu" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">@lang('vat::lang.vat_module'):</h6>

            <!-- VAT -->
            @if($vat_main_invoice)
            <a class="collapse-item {{ $request->segment(3) == 'vat-invoice' ? 'active' : '' }}" href="{{action('\Modules\Vat\Http\Controllers\VatInvoiceController@index')}}">@lang('vat::lang.vat_invoice')</a>
            @endif
           
            <!-- VAT-2 -->
            @if($vat_main_invoice2)
            <a class="collapse-item {{ $request->segment(3) == 'vat-invoice2' ? 'active' : '' }}" href="{{action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@index')}}">@lang('vat::lang.vat_invoice')-2</a>
            @endif
            
            <!-- Statement 126 -->
            @if($vat_main_enabled_126_statement)
            <a class="collapse-item {{ $request->segment(3) == 'statement-126' ? 'active' : '' }}" href="{{action('\Modules\Vat\Http\Controllers\VatStatement126Controller@index')}}">@lang('vat::lang.list_126_statement')</a>
            @endif
            
            @if($vat_main_fleet_invoices)
                <!-- Fleet VAT Invoices -->
                <a class="collapse-item {{ $request->segment(3) == 'fleet-vat-invoice2' ? 'active' : '' }}" href="{{action('\Modules\Vat\Http\Controllers\FleetVatInvoice2Controller@index')}}">@lang('vat::lang.fleet_vat_invoice')</a>
            @endif

             <!-- VAT-SALE -->
            @if($vat_main_sale)  
                <a class="collapse-item" href="{{action('\Modules\Vat\Http\Controllers\VatSettlementController@index')}}">@lang('vat::lang.vat_sale')</a>
            @endif
            
            <!-- List VAT-SALE -->
            @if($vat_main_list_vat_sale)  
                <a class="collapse-item" href="{{action('\Modules\Vat\Http\Controllers\VatSettlementController@index')}}">@lang('vat::lang.list_vat_sale')</a>
            @endif
          
            <!-- VAT-PURCHASE -->
            @if($vat_main_purchase)  
                <a class="collapse-item" href="{{action('\Modules\Vat\Http\Controllers\VatPurchaseController@index')}}">@lang('vat::lang.vat_purchase')</a>
            @endif
            
            <!-- List VAT-PURCHASE -->
            @if($vat_main_list_vat_purchase)  
                <a class="collapse-item" href="{{action('\Modules\Vat\Http\Controllers\VatPurchaseController@index')}}">@lang('vat::lang.list_vat_purchase')</a>
            @endif
          
            <!-- VAT-EXPENSES -->
            @if($vat_main_expense)  
                <a class="collapse-item" href="{{action('\Modules\Vat\Http\Controllers\VatExpenseController@index')}}">@lang('vat::lang.vat_expenses')</a>
            @endif
            
            <!-- List VAT-EXPENSES -->
            @if($vat_main_list_vat_expense)  
                <a class="collapse-item" href="{{action('\Modules\Vat\Http\Controllers\VatExpenseController@index')}}">@lang('vat::lang.list_vat_expense')</a>
            @endif

            <!-- VAT-CONTACTS -->
            @if($vat_main_contacts)  
                <a class="collapse-item " href="{{action('\Modules\Vat\Http\Controllers\VatContactController@index',['type' => 'customer'])}}">@lang('vat::lang.vat_contacts')</a>
            @endif
            
            <!-- VAT-PRODUCTS -->
            @if($vat_main_products)  
                <a class="collapse-item " href="{{action('\Modules\Vat\Http\Controllers\VatProductController@index')}}">@lang('vat::lang.vat_products')</a>
            @endif
            
            {{-- TODO: VatMeterSalesController needs to be created before enabling this menu item --}}
            {{-- @if($vat_main_meter_sales)  
                <a class="collapse-item " href="{{action('\Modules\Vat\Http\Controllers\VatMeterSalesController@index')}}">@lang('vat::lang.vat_meter_sales')</a>
            @endif --}}
            
            <!-- VAT-CREDIT-BILL -->
            @if($vat_main_credit_bill)  
                <a class="collapse-item " href="{{action('\Modules\Vat\Http\Controllers\VatCreditBillController@index')}}">@lang('vat::lang.vat_credit_bill')</a>
            @endif

            @if($vat_main_report)
            <a class="collapse-item {{ $request->segment(2) == 'reports' ? 'active' : '' }}" href="{{ action('\Modules\Vat\Http\Controllers\VatController@getVatReport') }}">@lang('report.tax_report')</a>
            @endif
    
            @if($vat_main_report_ledger)
            <a class="collapse-item {{ $request->segment(2) == 'reports-ledger' ? 'active' : '' }}" href="{{ action('\Modules\Vat\Http\Controllers\VatReportController@index') }}">@lang('vat::lang.vat_report_ledger')</a>
            @endif
            
            @if($vat_main_statement)
            <a class="collapse-item {{ $request->segment(2) == 'customer-statement' ? 'active' : '' }}" href="{{action('\Modules\Vat\Http\Controllers\CustomerStatementController@index')}}">@lang('vat::lang.vat_statement')</a>
            @endif
            
            @if($vat_main_import_contacts)
            <a class="collapse-item " href="{{action('\Modules\Vat\Http\Controllers\ImportContactsController@index')}}">@lang('vat::lang.import_contacts')</a>
            @endif
            
            @if($vat_main_settings)
            <a class="collapse-item {{ $request->segment(2) == 'vat-settings' ? 'active' : '' }}" href="{{action('\Modules\Vat\Http\Controllers\SettingsController@index')}}">@lang('vat::lang.vat_settings')</a>
            @endif
            
            @if($vat_main_schedule)
            <a class="collapse-item {{ $request->segment(2) == 'customer-vat-schedule' ? 'active' : '' }}" href="{{action('\Modules\Vat\Http\Controllers\VatController@getCustomerVatSchedule')}}">@lang('vat::lang.vat_schedule')</a>
            @endif
        </div>
    </div>
</li>
<!-- End VAT Module -->
@endif
