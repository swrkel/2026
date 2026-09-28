@inject('request', 'Illuminate\Http\Request')
@php
    $sidebar_setting = App\SiteSettings::where('id', 1)
        ->select('ls_side_menu_bg_color', 'ls_side_menu_font_color', 'sub_module_color', 'sub_module_bg_color')
        ->first();

    $module_array['disable_all_other_module_vr'] = 0;
    $module_array['enable_petro_module'] = 0;
    $module_array['enable_petro_dashboard'] = 0;
    $module_array['petro_daily_status'] = 0;
    $module_array['tank_transfer'] = 0;
    $module_array['enable_petro_task_management'] = 0;
    $module_array['enable_petro_pump_dashboard'] = 0;
    $module_array['enable_petro_pumper_management'] = 0;
    $module_array['enable_petro_daily_collection'] = 0;
    $module_array['enable_petro_settlement'] = 0;
    $module_array['enable_petro_list_settlement'] = 0;
    $module_array['enable_petro_dip_management'] = 0;
    $module_array['enable_sale_cmsn_agent'] = 0;
    $module_array['pump_operator_dashboard'] = 0;
    $module_array['enable_crm'] = 0;
    $module_array['mf_module'] = 0;
    $module_array['helpguide'] = 0;
    $module_array['customized_report'] = 0;
    $module_array['petro_sms_notifications'] = 0;

    $module_array['contact_list_customer_loans'] = 0;
    $module_array['contact_settings'] = 0;
    $module_array['contact_list_supplier_map_products'] = 0;
    $module_array['contact_add_supplier_products'] = 0;
    $module_array['contact_import_opening_balalnces'] = 0;
    $module_array['contact_returned_cheque_details'] = 0;
    $module_array['product_print_labels'] = 0;

    $module_array['cheque_dashboard'] = 0;
    $module_array['cheque_add_template'] = 0;
    $module_array['cheque_cancelled_cheques'] = 0;
    $module_array['cheque_printed_cheques'] = 0;
    $module_array['ezy_list_products'] = 0;
    $module_array['ezy_units'] = 0;
    $module_array['ezy_categories'] = 0;
    $module_array['ezy_show_current_stock'] = 0;
    $module_array['ezy_show_stock_report'] = 0;

    $module_array['cheque_dashboard'] = 0;
    $module_array['cheque_add_template'] = 0;
    $module_array['cheque_cancelled_cheques'] = 0;
    $module_array['cheque_printed_cheques'] = 0;

    $module_array['ezy_products'] = 0;

    $module_array['bakery_module'] = 0;

    $module_array['post_dated_cheque'] = 0;

    $module_array['crm_module'] = 0;
    $module_array['list_credit_sales_page'] = 0;
    $module_array['stock_conversion_module'] = 0;
    $module_array['docmanagement_module'] = 0;

    $module_array['ezyinvoice_module'] = 0;
    $module_array['shipping_module'] = 0;
    $module_array['airline_module'] = 0;

    $module_array['vat_module'] = 0;
    $module_array['vat_module_main'] = 0;
    $module_array['asset_module'] = 0;
    $module_array['deposits_module'] = 0;
    $module_array['dsr_module'] = 0;

    $module_array['ns_asset_management'] = 0;
    $module_array['ns_deposits_module'] = 0;
    $module_array['ns_discount_module'] = 0;
    $module_array['ns_dsr_module'] = 0;

    $module_array['ns_vat_module'] = 0;

    $module_array['realize_cheque'] = 0;
    $module_array['discount_module'] = 0;
    $module_array['tpos_module'] = 0;

    $module_array['hms_module'] = 0;

    $module_array['hr_module'] = 0;
    $module_array['loan_module'] = 0;
    $module_array['employee'] = 0;
    $module_array['teminated'] = 0;
    $module_array['award'] = 0;
    $module_array['leave_request'] = 0;
    $module_array['attendance'] = 0;
    $module_array['import_attendance'] = 0;
    $module_array['late_and_over_time'] = 0;
    $module_array['payroll'] = 0;
    $module_array['salary_details'] = 0;
    $module_array['basic_salary'] = 0;
    $module_array['payroll_payments'] = 0;
    $module_array['hr_reports'] = 0;
    $module_array['notice_board'] = 0;
    $module_array['hr_settings'] = 0;
    $module_array['department'] = 0;
    $module_array['jobtitle'] = 0;
    $module_array['jobcategory'] = 0;
    $module_array['workingdays'] = 0;
    $module_array['workshift'] = 0;
    $module_array['holidays'] = 0;
    $module_array['leave_type'] = 0;
    $module_array['salary_grade'] = 0;
    $module_array['employment_status'] = 0;
    $module_array['salary_component'] = 0;
    $module_array['hr_prefix'] = 0;
    $module_array['hr_tax'] = 0;
    $module_array['religion'] = 0;
    $module_array['hr_setting_page'] = 0;
    $module_array['enable_sms'] = 0;
    $module_array['access_account'] = 0;
    $module_array['enable_booking'] = 0;
    $module_array['customer_order_own_customer'] = 0;
    $module_array['customer_settings'] = 0;
    $module_array['customer_order_general_customer'] = 0;
    $module_array['mpcs_module'] = 0;

    $module_array['price_changes_module'] = 0;

    $module_array['shipping_module'] = 0;

    $module_array['fleet_module'] = 0;
    $module_array['ezyboat_module'] = 0;
    $module_array['merge_sub_category'] = 0;
    $module_array['backup_module'] = 0;
    $module_array['banking_module'] = 0;
    $module_array['products'] = 0;
    $module_array['purchase'] = 0;
    $module_array['stock_transfer'] = 0;
    $module_array['service_staff'] = 0;
    $module_array['enable_subscription'] = 0;
    $module_array['add_sale'] = 0;
    $module_array['stock_adjustment'] = 0;
    $module_array['tables'] = 0;
    $module_array['type_of_service'] = 0;
    $module_array['pos_sale'] = 0;
    $module_array['expenses'] = 0;
    $module_array['modifiers'] = 0;
    $module_array['kitchen'] = 0;
    $module_array['orders'] = 0;
    $module_array['enable_cheque_writing'] = 0;
    $module_array['issue_customer_bill'] = 0;
    $module_array['issue_customer_bill_vat'] = 0;
    $module_array['tasks_management'] = 0;
    $module_array['notes_page'] = 0;
    $module_array['tasks_page'] = 0;
    $module_array['reminder_page'] = 0;
    $module_array['member_registration'] = 0;
    $module_array['visitors_registration_module'] = 0;
    $module_array['visitors'] = 0;
    $module_array['visitors_registration'] = 0;
    $module_array['visitors_registration_setting'] = 0;
    $module_array['visitors_district'] = 0;
    $module_array['visitors_town'] = 0;
    $module_array['home_dashboard'] = 0;
    $module_array['contact_module'] = 0;
    $module_array['stock_taking_page'] = 0;
    $module_array['contact_supplier'] = 0;
    $module_array['contact_customer'] = 0;
    $module_array['contact_group_customer'] = 0;
    $module_array['import_contact'] = 0;
    $module_array['customer_reference'] = 0;
    $module_array['customer_statement'] = 0;
    $module_array['customer_statement_pmt'] = 0;
    $module_array['customer_payment'] = 0;
    $module_array['outstanding_received'] = 0;
    $module_array['issue_payment_detail'] = 0;
    $module_array['property_module'] = 0;
    $module_array['ran_module'] = 0;
    $module_array['report_module'] = 0;
    $module_array['product_report'] = 0;
    $module_array['payment_status_report'] = 0;
    $module_array['verification_report'] = 0;
    $module_array['activity_report'] = 0;
    $module_array['contact_report'] = 0;
    $module_array['trending_product'] = 0;
    $module_array['user_activity'] = 0;
    $module_array['report_verification'] = 0;
    $module_array['report_table'] = 0;
    $module_array['report_staff_service'] = 0;
    $module_array['verification_report'] = 0;
    $module_array['notification_template_module'] = 0;
    $module_array['settings_module'] = 0;
    $module_array['user_management_module'] = 0;
    $module_array['leads_module'] = 0;
    $module_array['leads'] = 0;
    $module_array['day_count'] = 0;
    $module_array['leads_import'] = 0;
    $module_array['leads_settings'] = 0;
    $module_array['sms_module'] = 0;
    $module_array['list_sms'] = 0;
    $module_array['smsmodule_module'] = 0;
    $module_array['status_order'] = 0;
    $module_array['list_orders'] = 0;
    $module_array['upload_orders'] = 0;
    $module_array['subcriptions'] = 0;
    $module_array['over_limit_sales'] = 0;
    $module_array['sale_module'] = 0;
    $module_array['all_sales'] = 0;
    $module_array['list_pos'] = 0;
    $module_array['list_draft'] = 0;
    $module_array['list_quotation'] = 0;
    $module_array['list_sell_return'] = 0;
    $module_array['shipment'] = 0;
    $module_array['discount'] = 0;
    $module_array['import_sale'] = 0;
    $module_array['reserved_stock'] = 0;
    $module_array['auto_services_and_repair_module'] = 0;
    $module_array['auto_service_module'] = 0;
    $module_array['autoservice_module'] = 0;
    $module_array['autoservice'] = 0;
    $module_array['beauty_saloons_module'] = 0;
    $module_array['beautysaloons_module'] = 0;
    $module_array['beauty_saloons'] = 0;
    $module_array['beautysaloons'] = 0;
    $module_array['repair_module'] = 0;
    $module_array['catalogue_qr'] = 0;
    $module_array['business_settings'] = 0;
    $module_array['business_location'] = 0;
    $module_array['invoice_settings'] = 0;
    $module_array['tax_rates'] = 0;
    $module_array['list_easy_payment'] = 0;
    $module_array['payday'] = 0;

    $module_array['patient_module'] = 0;

    $module_array['purchase_module'] = 0;
    $module_array['all_purchase'] = 0;
    $module_array['add_purchase'] = 0;
    $module_array['import_purchase'] = 0;
    $module_array['add_bulk_purchase'] = 0;
    $module_array['purchase_return'] = 0;

    $module_array['cheque_write_module'] = 0;
    $module_array['cheque_templates'] = 0;
    $module_array['chequer_dashboard'] = 0;
    $module_array['write_cheque'] = 0;
    $module_array['manage_stamps'] = 0;
    $module_array['manage_payee'] = 0;
    $module_array['cheque_number_list'] = 0;
    $module_array['deleted_cheque_details'] = 0;
    $module_array['printed_cheque_details'] = 0;
    $module_array['default_setting'] = 0;
    $module_array['petro_quota_module'] = 0;
    $module_array['stock_taking_module'] = 0;
    $module_array['installment_module'] = 0;

    $module_array['distribution_module'] = 0;
    $module_array['spreadsheet'] = 0;

    $module_array['allowance_deduction'] = 0;
    $module_array['essentials_module'] = 0;
    $module_array['essentials_todo'] = 0;
    $module_array['essentials_document'] = 0;
    $module_array['essentials_memos'] = 0;
    $module_array['essentials_reminders'] = 0;
    $module_array['essentials_messages'] = 0;
    $module_array['essentials_settings'] = 0;

    $module_array['settlement_sw_module'] = 0;

    // Newer standalone modules added after the original sidebar design.
    // Keep them here so restoring the old sidebar design does not hide new modules.
    $module_array['customers_module'] = 0;
    $module_array['suppliers_module'] = 0;
    $module_array['suppliers_dashboard'] = 0;
    $module_array['suppliers_all_suppliers'] = 0;
    $module_array['suppliers_add_supplier'] = 0;
    $module_array['suppliers_contact_group'] = 0;
    $module_array['suppliers_import_contacts'] = 0;
    $module_array['suppliers_product_mappings'] = 0;
    $module_array['suppliers_payments'] = 0;
    $module_array['suppliers_purchase_history'] = 0;
    $module_array['suppliers_ledger'] = 0;
    $module_array['suppliers_statement'] = 0;
    $module_array['suppliers_aging'] = 0;
    $module_array['suppliers_stock_report'] = 0;
    $module_array['suppliers_reports'] = 0;
    $module_array['suppliers_issue_payment_details'] = 0;
    $module_array['suppliers_settings'] = 0;
    $module_array['real_time_entries'] = 0;
    $module_array['daily_collection'] = 0;
    $module_array['daily_collection_sw'] = 0;
    $module_array['pos2'] = 0;
    $module_array['petro_pd_module'] = 0;
    $module_array['stock_report'] = 0;
    $module_array['membership_module'] = 0;
    $module_array['member_registration'] = 0;
    $module_array['sales_agent_module'] = 0;
    $module_array['agent_module'] = 0;
    $module_array['my_auto'] = 0;
    $module_array['pump_operator_dashboard'] = 0;
    $module_array['customized_reports_module'] = 0;
    $module_array['ev_charging_module'] = 0;
    $module_array['leads_new_module'] = 0;
    $module_array['management_report_module'] = 0;
    $module_array['management_report'] = 0;

    // Standalone HR Manager module visibility flag.
    // This is separate from the old/legacy HR/Essentials HRM module.
    $module_array['hr_manager_module'] = 0;

    foreach ($module_array as $key => $module_value) {
        ${$key} = 0;
    }

    $business_id = request()->session()->get('user.business_id');
    $subscription = Modules\Superadmin\Entities\Subscription::current_subscription($business_id);
    $stock_adjustment = 0;
    $pacakge_details = [];

    if (!empty($subscription)) {
        $pacakge_details = $subscription->package_details;
        $stock_adjustment = $pacakge_details['stock_adjustment'];
        $disable_all_other_module_vr = 0;

        if (array_key_exists('disable_all_other_module_vr', $pacakge_details)) {
            $disable_all_other_module_vr = $pacakge_details['disable_all_other_module_vr'];
        }

        foreach ($module_array as $key => $module_value) {
            if ($disable_all_other_module_vr == 0) {
                if (array_key_exists($key, $pacakge_details)) {
                    ${$key} = $pacakge_details[$key];
                    //logger($key." ".$pacakge_details[$key]);
                } else {
                    ${$key} = 0;
                }
            } else {
                ${$key} = 0;
                $disable_all_other_module_vr = 1;
                $visitors_registration_module = 1;
                $visitors = 1;
                $visitors_registration = 1;
                $visitors_registration_setting = 1;
                $visitors_district = 1;
                $visitors_town = 1;
            }
        }
    }


    /*
     * Keep the original package/subscription flags separate from standalone
     * module aliases. Some standalone modules have similar names (for example
     * Purchase versus the core Purchases section), and automatic alias hydration
     * must never overwrite the core package flag.
     */
    $__core_sidebar_package_flags = [
        'products' => !empty($products),
        'purchases' => !empty($purchase),
        'stock_transfer' => !empty($stock_transfer),
        'stock_adjustment' => !empty($stock_adjustment),
        'expenses' => !empty($expenses),
        'sales' => !empty($sale_module),
        'reports' => !empty($report_module),
        'settings' => !empty($settings_module),
        'user_management' => !empty($user_management_module),
        'pumper_dashboard' => !empty($pump_operator_dashboard),
        'contact_module' => !empty($contact_module),
        'accounting_module' => !empty($access_account),
    ];

    /*
     * Permanent automatic module source. Every installed module alias is
     * hydrated from business.enabled_modules. New modules default to enabled;
     * an explicit Manage Side Bar disable always wins.
     */
    try {
        foreach (\App\Services\AutomaticModuleRegistry::all() as $__auto_sidebar_module) {
            $__auto_sidebar_enabled = \App\Utils\SidebarPermissionUtil::isEnabled($__auto_sidebar_module['key']) ? 1 : 0;
            foreach (array_unique(array_merge(
                [$__auto_sidebar_module['key'], $__auto_sidebar_module['key'] . '_module'],
                $__auto_sidebar_module['aliases'] ?? []
            )) as $__auto_sidebar_alias) {
                $__auto_sidebar_alias = \App\Services\AutomaticModuleRegistry::normalizeKey($__auto_sidebar_alias);
                if ($__auto_sidebar_alias !== '') {
                    ${$__auto_sidebar_alias} = $__auto_sidebar_enabled;
                }
            }
        }
    } catch (\Throwable $__auto_sidebar_exception) {
        // Preserve the existing static sidebar if module discovery is unavailable.
    }


    /*
     * Super Admin / All Businesses / Manage Side Bar is the authoritative
     * business-level parent gate. A core section is rendered only when it is
     * included in the active package AND checked for this business.
     */
    $products = $__core_sidebar_package_flags['products']
        && \App\Utils\SidebarPermissionUtil::isEnabled('products', (int) $business_id);
    $purchase = $__core_sidebar_package_flags['purchases']
        && \App\Utils\SidebarPermissionUtil::isEnabled('purchases', (int) $business_id);
    $stock_transfer = $__core_sidebar_package_flags['stock_transfer']
        && \App\Utils\SidebarPermissionUtil::isEnabled('stock_transfer', (int) $business_id);
    $stock_adjustment = $__core_sidebar_package_flags['stock_adjustment']
        && \App\Utils\SidebarPermissionUtil::isEnabled('stock_adjustment', (int) $business_id);
    $expenses = $__core_sidebar_package_flags['expenses']
        && \App\Utils\SidebarPermissionUtil::isEnabled('expenses', (int) $business_id);
    $sale_module = $__core_sidebar_package_flags['sales']
        && \App\Utils\SidebarPermissionUtil::isEnabled('sales', (int) $business_id);
    $report_module = $__core_sidebar_package_flags['reports']
        && \App\Utils\SidebarPermissionUtil::isEnabled('reports', (int) $business_id);
    $settings_module = $__core_sidebar_package_flags['settings']
        && \App\Utils\SidebarPermissionUtil::isEnabled('settings', (int) $business_id);
    $user_management_module = $__core_sidebar_package_flags['user_management']
        && \App\Utils\SidebarPermissionUtil::isEnabled('user_management', (int) $business_id);
    $pump_operator_dashboard = $__core_sidebar_package_flags['pumper_dashboard']
        && \App\Utils\SidebarPermissionUtil::isEnabled('pumper_dashboard', (int) $business_id);
    $contact_module = $__core_sidebar_package_flags['contact_module']
        && \App\Utils\SidebarPermissionUtil::isEnabled('contact_module', (int) $business_id);
    $access_account = $__core_sidebar_package_flags['accounting_module']
        && \App\Utils\SidebarPermissionUtil::isEnabled('accounting_module', (int) $business_id);

    // Leads-New standalone module aliases for older saved package keys and new module-status keys.
    // Keep this independent from the old Leads module, so Leads-New can be enabled alone.
    $leads_new_module = !empty($leads_new_module)
        || !empty($pacakge_details['leads_new_module'])
        || !empty($pacakge_details['leadsnew_module'])
        || !empty($pacakge_details['leads_new'])
        || !empty($pacakge_details['leads-new'])
        || !empty($pacakge_details['enable_leads_new'])
        || !empty($pacakge_details['leads_new_enabled']);

    /*
     * A Super Admin inside a tenant remains a full-access Super Admin.
     * Normal tenant users continue to obey package, Manage Side Bar and page
     * permissions. The impersonation markers are cleared on Back to Superadmin.
     */
    $__superadmin_full_access = \App\Utils\SidebarPermissionUtil::hasSuperAdminBypass();
    if ($__superadmin_full_access) {
        foreach ($module_array as $key => $module_value) {
            ${$key} = 1;
        }
        $disable_all_other_module_vr = 0;
        $finance_module = 1;
        $accounting_module = 1;
        $access_account = 1;
    }

    // HR Manager standalone module visibility resolver.
    // The old $hr_module flag is kept only for legacy Essentials HRM.
    // HR Manager must appear when it is enabled in modules_statuses.json or in the package details.
    $__hr_manager_status_enabled = false;
    try {
        $__modules_status_file = base_path('modules_statuses.json');
        if (file_exists($__modules_status_file)) {
            $__modules_statuses = json_decode(file_get_contents($__modules_status_file), true);
            $__hr_manager_status_enabled = !empty($__modules_statuses['HRManager']);
        }
    } catch (\Throwable $e) {
        $__hr_manager_status_enabled = false;
    }

    $hr_manager_module = !empty($hr_manager_module)
        || !empty($pacakge_details['hr_manager_module'])
        || !empty($pacakge_details['hrmanager_module'])
        || !empty($pacakge_details['hr_manager'])
        || !empty($pacakge_details['HRManager'])
        || $__hr_manager_status_enabled;


    // Management Report standalone module visibility resolver.
    // An explicit business/package setting overrides the installed-module status.
    $__management_report_status_enabled = false;
    try {
        $__modules_status_file = base_path('modules_statuses.json');
        if (file_exists($__modules_status_file)) {
            $__modules_statuses = json_decode(file_get_contents($__modules_status_file), true);
            $__management_report_status_enabled = !empty($__modules_statuses['ManagementReport']);
        }
    } catch (\Throwable $e) {
        $__management_report_status_enabled = false;
    }

    $__management_report_explicit = array_key_exists('management_report_module', $pacakge_details)
        || array_key_exists('management_report', $pacakge_details)
        || array_key_exists('managementreport_module', $pacakge_details)
        || array_key_exists('ManagementReport', $pacakge_details);

    if ($__management_report_explicit) {
        $management_report_module = !empty($pacakge_details['management_report_module'])
            || !empty($pacakge_details['management_report'])
            || !empty($pacakge_details['managementreport_module'])
            || !empty($pacakge_details['ManagementReport']);
    } else {
        $management_report_module = $__management_report_status_enabled;
    }
@endphp
<style>
    .skin-blue .main-sidebar {
        background-color: @if (!empty($sidebar_setting->ls_side_menu_bg_color))
                {
                    {
                    $sidebar_setting->ls_side_menu_bg_color
                }
            }

        @endif
        ;
    }

    .skin-blue .sidebar a {
        color: @if (!empty($sidebar_setting->ls_side_menu_font_color))
                {
                    {
                    $sidebar_setting->ls_side_menu_font_color
                }
            }

        @endif
        ;
    }

    .skin-blue .treeview-menu>li>a {
        color: @if (!empty($sidebar_setting->sub_module_color))
                {
                    {
                    $sidebar_setting->sub_module_color
                }
            }

        @endif
        ;
    }

    .skin-blue .sidebar-menu>li>.treeview-menu {
        background: @if (!empty($sidebar_setting->sub_module_bg_color))
                {
                    {
                    $sidebar_setting->sub_module_bg_color
                }
            }

        @endif
        ;
    }

    #sidebarFilter {
        margin: 0 10px;
        background-color: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        display: block;
        width: calc(100% - 20px);
        /* Adjust width to account for margins */
    }
</style>

@php
    $user = App\User::where('id', auth()->user()->id)->first();
    $is_admin = $user->hasRole('Admin#' . request()->session()->get('business.id')) ? true : false;

    // Sidebar visibility helpers for modules added after the original sidebar.
    $is_privileged_sidebar_user = !empty($__superadmin_full_access) || auth()->user()->can('superadmin') || $is_admin;
    // Daily Collection and Daily Collection SW are mutually exclusive in the sidebar.
    // If both values exist in an older subscription record, SW wins so the system never shows two Daily Collection menus.
    // IS1641 fix: Daily Collection SW can be saved under different keys by older/newer Superadmin Manage forms.
    // Resolve all known aliases here so enabling Daily Collection SW in Manage always shows the single SW sidebar menu.
    $daily_collection_sw_enabled = !empty($daily_collection_sw)
        || !empty($pacakge_details['daily_collection_sw'])
        || !empty($pacakge_details['daily_collection_sw_module'])
        || !empty($pacakge_details['dailycollectionsw'])
        || !empty($pacakge_details['dailycollectionsw_module'])
        || !empty($pacakge_details['daily_collection_sw_enabled'])
        || !empty($pacakge_details['DailyCollectionSW'])
        || !empty($pacakge_details['daily_collection_sw_sub_menu'])
        || !empty($pacakge_details['daily_collection_settings_daily_collection_sw']);

    $daily_collection_enabled = (!empty($daily_collection)
        || !empty($pacakge_details['daily_collection'])
        || !empty($pacakge_details['daily_collection_module'])
        || !empty($pacakge_details['daily_collection_sub_menu'])
        || !empty($pacakge_details['enable_petro_daily_collection']))
        && empty($daily_collection_sw_enabled);

    $show_daily_collection_menu = !empty($daily_collection_enabled) && !empty($enable_petro_module);
    $show_petro_pd_menu = !empty($petro_pd_module);
    $show_ev_charging_menu = !empty($ev_charging_module) || !empty($petro_pd_module);
    $show_membership_menu = !empty($membership_module) && !empty($member_registration);
    $can_view_sales_agent_sidebar = !empty($sales_agent_module) && $is_privileged_sidebar_user;
    $can_view_agent_sidebar = !empty($agent_module) && $is_privileged_sidebar_user;
    $can_view_daily_collection_menu = $show_daily_collection_menu && ($is_privileged_sidebar_user || auth()->user()->can('petro.access'));
    $__daily_collection_sw_module_available = false;
    try {
        $__daily_collection_sw_module_available = Module::has('DailyCollectionSW')
            || Module::has('DailyCollectionSw')
            || Module::has('dailycollectionsw')
            || is_dir(base_path('Modules/DailyCollectionSW'))
            || is_dir(base_path('Modules/DailyCollectionSw'))
            || is_dir(base_path('Modules/dailycollectionsw'));
    } catch (\Throwable $e) {
        $__daily_collection_sw_module_available = is_dir(base_path('Modules/DailyCollectionSW'))
            || is_dir(base_path('Modules/DailyCollectionSw'))
            || is_dir(base_path('Modules/dailycollectionsw'));
    }

    // Master business enablement controls the main sidebar item. Child page permissions still apply inside the module.
    // TEMP SIDEBAR_072: force Daily Collection SW visible whenever the installed module is available.
    // This temporary bypass is independent of Manage Sidebar / Manage Page until the permanent single-source permission engine is complete.
    $daily_collection_sw_enabled = $__daily_collection_sw_module_available ? true : $daily_collection_sw_enabled;
    $daily_collection_enabled = false; // prevent duplicate legacy Daily Collection menu while SW is forced on
    $show_daily_collection_menu = false;
    $can_view_daily_collection_menu = false;
    $can_view_daily_collection_sw_menu = $__daily_collection_sw_module_available;
    $can_view_pumper_dashboard_sidebar = !empty($pump_operator_dashboard) && !empty($enable_petro_module)
        && ($is_privileged_sidebar_user || auth()->user()->can('pump_operator.dashboard') || auth()->user()->can('pumper_dashboard.dashboard') || auth()->user()->can('pump_operator.main_system'));
    $can_view_stock_reports_sidebar = !empty($stock_report) && ($is_privileged_sidebar_user || auth()->user()->can('stock_transaction_report'));
@endphp

<!-- Left side column. contains the logo and sidebar -->

@if (session()->get('business.is_patient') && $patient_module)
    <ul class="custom-overflow sidebar-menu navbar-nav bg-gradient-primary sidebar sidebar-dark accordion"
        id="accordionSidebar" style="width: 220px !important;">

        <!-- Sidebar - Brand -->
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
            <div class="sidebar-brand-text mx-3">SYZYGY</div>
        </a>
        <!-- Divider -->
        <hr class="sidebar-divider">

        @includeIf('layouts.partials.sidebar-sections.sidebar-myhealthmembers')

        @if (session()->get('business.is_patient'))
            <li class="nav-item {{ $request->segment(1) == 'patient' ? 'active' : '' }}">
                <a href="{{ action('PatientController@index') }}"> <i class="fa fa-dashboard"></i> <span>
                        @lang('home.home')</span> </a>
            </li>
            @endif @if (session()->get('business.is_hospital'))
                <li class="nav-item {{ $request->segment(1) == 'patient' ? 'active' : '' }}">
                    <a href="{{ action('HospitalController@index') }}"> <i class="fa fa-dashboard"></i> <span>
                            @lang('home.home')</span> </a>
                </li>
            @endif

            <li class="nav-item {{ $request->segment(1) == 'reports' ? 'active' : '' }}">
                <a href="/reports/user_activity">
                    <i class="fa fa-eercast"></i>
                    <span class="title">@lang('report.user_activity')</span>
                </a>
            </li>

            @if ($is_admin)
                @if (Module::has('Superadmin'))
                    @includeIf('superadmin::layouts_v2.partials.subscription')
                @endif
                @if (request()->session()->get('business.is_patient'))
                    <li class="nav-item @if (in_array($request->segment(1), ['family-members', 'superadmin', 'pay-online'])) {{ 'active active-sub' }} @endif">
                        <a class="nav-link collapsed" href="#" data-toggle="collapse"
                            data-target="#patientbs-menu" aria-expanded="true" aria-controls="patientbs-menu">
                            <i class="fa fa-cog"></i>
                            <span>@lang('business.settings')</span>
                        </a>
                        <div id="patientbs-menu"
                            class="collapse @if (in_array($request->segment(1), ['family-members', 'superadmin', 'pay-online'])) {{ 'show' }} @endif"
                            aria-labelledby="patientbs-menu" data-parent="#accordionSidebar">
                            <div class="bg-white py-2 collapse-inner rounded">
                                <h6 class="collapse-header">@lang('business.settings'):</h6>
                                <a class="collapse-item {{ $request->segment(1) == 'family-member' ? 'active' : '' }}"
                                    href="{{ action('FamilyController@index') }}">@lang('patient.family_member')</a>

                                <a class="collapse-item {{ $request->segment(2) == 'family-subscription' ? 'active' : '' }}"
                                    href="{{ action('\Modules\Superadmin\Http\Controllers\FamilySubscriptionController@index') }}">@lang('patient.family_subscription')</a>

                                <a class="collapse-item {{ $request->segment(1) == 'pay-online' && $request->segment(2) == 'create' ? 'active active-sub' : '' }}"
                                    href="{{ action('\Modules\Superadmin\Http\Controllers\PayOnlineController@create') }}">@lang('superadmin::lang.pay_online')</a>
                            </div>
                        </div>
                    </li>
                @endif
            @endif


    </ul>
@elseif(auth()->user()->hasRole('dsr_officer'))
    <ul class="custom-overflow sidebar-menu navbar-nav bg-gradient-primary sidebar sidebar-dark accordion"
        id="accordionSidebar" style="width: 220px !important;max-height: 100vh;">
        <!-- Sidebar - Brand -->
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
            <div class="sidebar-brand-text mx-3">SYZYGY</div>
        </a>
        <!-- Divider -->
        <hr class="sidebar-divider">
        @includeIf('layouts.partials.sidebar-sections.sidebar-myhealthmembers')
        @if ($dsr_module || $ns_dsr_module)
            @includeIf('dsr::layouts_v2.partials.sidebar')
        @endif
    </ul>
@else
    <ul class="custom-overflow sidebar-menu navbar-nav bg-gradient-primary sidebar sidebar-dark accordion"
        id="accordionSidebar" style="width: 220px !important;max-height: 100vh;">
        <!-- Sidebar - Brand -->
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
            <div class="sidebar-brand-text mx-3">SYZYGY</div>
        </a>
        </a>
        <!-- Divider -->
        <hr class="sidebar-divider">

        <li class="">
            <input type="text" id="sidebarFilter" placeholder="Filter menu..." class="form-control mx-5 p-4">
        </li>

        @includeIf('layouts.partials.sidebar-sections.sidebar-myhealthmembers')

        <!-- Call superadmin module if defined -->
        @if (Module::has('Superadmin') && ! \App\Utils\SidebarPermissionUtil::isSuperAdminInsideBusiness())
            @includeIf('superadmin::layouts_v2.partials.sidebar')
        @endif

        {{-- PetroPD sidebar - top-level include.
             Kept outside the Home Dashboard block because some businesses/users can have
             PetroPD enabled while home_dashboard/dashboard.data is disabled. --}}
        @php
            $__petropd_sidebar_enabled = false;
            try {
                $__petropd_business_id = request()->session()->get('user.business_id');
                $__petropd_subscription = \Modules\Superadmin\Entities\Subscription::current_subscription($__petropd_business_id);
                $__petropd_package_details = [];

                if (!empty($__petropd_subscription) && !empty($__petropd_subscription->package_details)) {
                    $__petropd_raw_package = $__petropd_subscription->package_details;

                    if (is_string($__petropd_raw_package)) {
                        $__petropd_package_details = json_decode($__petropd_raw_package, true) ?: [];
                    } elseif (is_array($__petropd_raw_package)) {
                        $__petropd_package_details = $__petropd_raw_package;
                    } elseif ($__petropd_raw_package instanceof \Illuminate\Support\Collection) {
                        $__petropd_package_details = $__petropd_raw_package->toArray();
                    } else {
                        $__petropd_package_details = (array) $__petropd_raw_package;
                    }
                }

                $__petropd_sidebar_enabled = auth()->user()->can('superadmin')
                    || !empty($petro_pd_module)
                    || !empty($__petropd_package_details['petro_pd_module'])
                    || !empty($__petropd_package_details['petropd_module'])
                    || !empty($__petropd_package_details['enable_petro_pd'])
                    || !empty($__petropd_package_details['petro_pd']);
            } catch (\Throwable $e) {
                $__petropd_sidebar_enabled = !empty($petro_pd_module);
            }
        @endphp

        @if (!empty($__petropd_sidebar_enabled) && Module::has('PetroPD'))
            @includeIf('petropd::layouts_v2.partials.sidebar')
        @endif

        {{-- FINANCE REPORTS MENU FORCE CHECK - 2026-07-03
             This block is intentionally placed near the top of sidebar to avoid being hidden by the old Finance menu conditions.
             If this menu still does not appear after replacing this file and clearing views, the running system is not using
             resources/views/layouts/partials/sidebar.blade.php or the compiled view cache was not cleared. --}}
        @php
            $__finance_reports_sidebar_enabled = false;
            $__finance_reports_package_details = [];
            try {
                $__finance_reports_business_id = request()->session()->get('user.business_id');
                $__finance_reports_subscription = null;
                if (class_exists('Modules\\Superadmin\\Entities\\Subscription') && !empty($__finance_reports_business_id)) {
                    $__finance_reports_subscription = \Modules\Superadmin\Entities\Subscription::current_subscription($__finance_reports_business_id);
                }

                if (!empty($__finance_reports_subscription) && !empty($__finance_reports_subscription->package_details)) {
                    $__finance_reports_raw_package = $__finance_reports_subscription->package_details;
                    if (is_string($__finance_reports_raw_package)) {
                        $__finance_reports_package_details = json_decode($__finance_reports_raw_package, true) ?: [];
                    } elseif (is_array($__finance_reports_raw_package)) {
                        $__finance_reports_package_details = $__finance_reports_raw_package;
                    } elseif ($__finance_reports_raw_package instanceof \Illuminate\Support\Collection) {
                        $__finance_reports_package_details = $__finance_reports_raw_package->toArray();
                    } else {
                        $__finance_reports_package_details = (array) $__finance_reports_raw_package;
                    }
                }

                foreach ([
                    'finance_reports_module', 'finance_report_module', 'finance_reports', 'FinanceReports',
                    'financereports', 'financereports_module', 'finance_module', 'accounting_module', 'access_account',
                    'banking_module'
                ] as $__finance_reports_key) {
                    if (!empty($__finance_reports_package_details[$__finance_reports_key])) {
                        $__finance_reports_sidebar_enabled = true;
                        break;
                    }
                }

                $__finance_reports_sidebar_enabled = $__finance_reports_sidebar_enabled
                    || !empty($finance_reports_module ?? null)
                    || !empty($finance_report_module ?? null)
                    || !empty($finance_module ?? null)
                    || !empty($accounting_module ?? null)
                    || !empty($access_account ?? null)
                    || !empty($banking_module ?? null)
                    || auth()->user()->can('superadmin')
                    || auth()->user()->can('finance_reports.view')
                    || auth()->user()->can('account.access');
            } catch (\Throwable $e) {
                $__finance_reports_sidebar_enabled = !empty($finance_reports_module ?? null)
                    || !empty($finance_module ?? null)
                    || !empty($access_account ?? null)
                    || auth()->user()->can('superadmin')
                    || auth()->user()->can('account.access');
            }
        @endphp

        @if (!empty($__finance_reports_sidebar_enabled))
            <li class="nav-item {{ $request->segment(1) == 'finance-reports' ? 'active active-sub' : '' }}" id="finance-reports-sidebar-menu-v3">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#finance-reports-menu-v3"
                    aria-expanded="{{ $request->segment(1) == 'finance-reports' ? 'true' : 'false' }}" aria-controls="finance-reports-menu-v3">
                    <i class="fa fa-line-chart"></i>
                    <span>Finance Reports</span>
                </a>
                <div id="finance-reports-menu-v3"
                    class="collapse {{ $request->segment(1) == 'finance-reports' ? 'show' : '' }}"
                    aria-labelledby="headingPages" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Finance Reports:</h6>
                        <a class="collapse-item {{ $request->segment(1) == 'finance-reports' && empty($request->segment(2)) ? 'active' : '' }}" href="{{ url('/finance-reports') }}">Dashboard</a>
                        <a class="collapse-item {{ $request->segment(2) == 'trial-balance-new' ? 'active' : '' }}" href="{{ url('/finance-reports/trial-balance-new') }}">Trial Balance</a>
                        <a class="collapse-item {{ $request->segment(2) == 'general-ledger-new' ? 'active' : '' }}" href="{{ url('/finance-reports/general-ledger-new') }}">General Ledger</a>
                        <a class="collapse-item {{ $request->segment(2) == 'account-ledger-new' ? 'active' : '' }}" href="{{ url('/finance-reports/account-ledger-new') }}">Account Ledger</a>
                        <a class="collapse-item {{ $request->segment(2) == 'day-book-new' ? 'active' : '' }}" href="{{ url('/finance-reports/day-book-new') }}">Day Book</a>
                        <a class="collapse-item {{ $request->segment(2) == 'income-statement-new' ? 'active' : '' }}" href="{{ url('/finance-reports/income-statement-new') }}">Income Statement</a>
                        <a class="collapse-item {{ $request->segment(2) == 'balance-sheet-new' ? 'active' : '' }}" href="{{ url('/finance-reports/balance-sheet-new') }}">Balance Sheet</a>
                        <a class="collapse-item {{ $request->segment(2) == 'profit-loss-new' ? 'active' : '' }}" href="{{ url('/finance-reports/profit-loss-new') }}">Profit & Loss</a>
                        <a class="collapse-item {{ $request->segment(2) == 'cash-flow-statement-new' ? 'active' : '' }}" href="{{ url('/finance-reports/cash-flow-statement-new') }}">Cash Flow Statement</a>
                    </div>
                </div>
            </li>
        @endif

        {{-- Spelling corrected from 'helpguild.access'. The permission is renamed
         to match by the accompanying SQL script - the view and the permission
         must change together, or the menu vanishes for everyone. --}}
        @if(\App\Utils\SidebarPermissionUtil::hasSuperAdminBypass() || auth()->user()->can('helpguide.access'))
            <li
                class="nav-item {{ in_array($request->segment(1), ['helpguide', 'my_account']) ? 'active active-sub' : '' }}">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#helpguide-menu"
                    aria-expanded="true" aria-controls="helpguide-menu">
                    <i class="ti-settings"></i>
                    <span>Help Guide</span>
                </a>
                <div id="helpguide-menu" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Help Guide:</h6>
                        @cannot('superadmin')
                            <a class="collapse-item {{ $request->segment(1) == 'helpguide' && $request->segment(2) == '' ? 'active active-sub' : '' }}"
                                href="/helpguide">Home Page</a>
                            <a class="collapse-item {{ $request->segment(1) == 'my_account' && $request->segment(2) == 'helpguide' ? 'active active-sub' : '' }}"
                                href="{{ route('my_account') }}#/tickets">Tickets</a>
                        @endcannot
                        @can('superadmin')
                            <a class="collapse-item {{ $request->segment(1) == 'helpguide' && $request->segment(2) == 'dashboard' ? 'active active-sub' : '' }}"
                                href="/helpguide/dashboard">Dashboard</a>
                            <a class="collapse-item {{ $request->segment(1) == 'helpguide' && $request->segment(2) == '' ? 'active active-sub' : '' }}"
                                href="{{ \Illuminate\Support\Facades\Route::has('frontend') ? route('frontend') : url('/helpguide') }}"
                                target="_blank">Home Page</a>
                            <a class="collapse-item {{ $request->segment(1) == 'helpguide' && $request->segment(2) == 'dashboard' && $request->segment(3) == 'tickets' ? 'active active-sub' : '' }}"
                                href="/helpguide/dashboard/tickets">Tickets</a>
                            <a class="collapse-item {{ $request->segment(1) == 'helpguide' && $request->segment(2) == 'dashboard' && $request->segment(3) == 'articles' ? 'active active-sub' : '' }}"
                                href="/helpguide/dashboard#/articles">Articles</a>
                            <a class="collapse-item {{ $request->segment(1) == 'helpguide' && $request->segment(2) == 'dashboard' && $request->segment(3) == 'categories' ? 'active active-sub' : '' }}"
                                href="/helpguide/dashboard#/categories">Categories</a>
                            <a class="collapse-item {{ $request->segment(1) == 'helpguide' && $request->segment(2) == 'dashboard' && $request->segment(3) == 'saved_replies' ? 'active active-sub' : '' }}"
                                href="/helpguide/dashboard#/saved_replies">Saved Replies</a>
                            <a class="collapse-item {{ $request->segment(1) == 'helpguide' && $request->segment(2) == 'dashboard' && $request->segment(3) == 'customers' ? 'active active-sub' : '' }}"
                                href="/helpguide/dashboard#/customers">Customers</a>
                            <a class="collapse-item {{ $request->segment(1) == 'helpguide' && $request->segment(2) == 'dashboard' && $request->segment(3) == 'employees' ? 'active active-sub' : '' }}"
                                href="/helpguide/dashboard#/employees">Employees</a>
                            <a class="collapse-item {{ $request->segment(1) == 'helpguide' && $request->segment(2) == 'dashboard' && $request->segment(3) == 'modules' ? 'active active-sub' : '' }}"
                                href="{{ route('languages.index') }}">Translations</a>
                            <a class="collapse-item {{ $request->segment(1) == 'helpguide' && $request->segment(2) == 'dashboard' && $request->segment(3) == 'modules' ? 'active active-sub' : '' }}"
                                href="/helpguide/dashboard#/modules">Modules</a>
                            <a class="collapse-item {{ $request->segment(1) == 'helpguide' && $request->segment(2) == 'dashboard' && $request->segment(3) == 'settings' ? 'active active-sub' : '' }}"
                                href="/helpguide/dashboard/settings">Settings</a>
                            <a class="collapse-item {{ $request->segment(1) == 'helpguide' && $request->segment(2) == 'dashboard' && $request->segment(3) == 'customizer' ? 'active active-sub' : '' }}"
                                href="/helpguide/dashboard/customizer">Customizer</a>
                        @endcan
                    </div>
                </div>
            </li>
        @endif

        {{-- S345: Standalone Customers Module menu is rendered from the Customers module registry.
             This keeps Super Admin -> All Business -> Manage switches synchronized
             with Login -> System -> Customers Module and avoids the old hard-coded
             menu hiding enabled Customers features. --}}
        @includeIf('customers::layouts.partials.sidebar')

        @if ($home_dashboard)
            @if (auth()->user()->can('dashboard.data') && !auth()->user()->is_pump_operator && !auth()->user()->is_property_user)
                <li class="nav-item {{ $request->segment(1) == 'home' ? 'active' : '' }}">
                    <a class="nav-link" href="{{ action('HomeController@index') }}">
                        <i class="fa fa-clone"></i>
                        <span>@lang('home.home')</span></a>
                </li>
                <li
                    class="nav-item {{ in_array($request->segment(1), ['developments']) ? 'active active-sub' : '' }}">
                    <a class="nav-link {{ in_array($request->segment(1), ['developments']) ? 'active' : '' }}"
                        href="#" data-toggle="collapse" data-target="#development-menu"
                        aria-expanded="{{ in_array($request->segment(1), ['developments']) ? 'true' : 'false' }}"
                        aria-controls="development-menu">
                        <i class="ti-settings"></i>
                        <span>@lang('development::lang.dashboard')</span>
                    </a>
                    <div id="development-menu"
                        class="collapse {{ in_array($request->segment(1), ['developments']) ? 'show' : '' }}"
                        aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">@lang('development::lang.dashboard'):</h6>
                            <a class="collapse-item {{ $request->segment(1) == 'development' && $request->segment(2) == 'settings' ? 'active' : '' }}"
                                href="{{ \Illuminate\Support\Facades\Route::has('development.settings.index') ? route('development.settings.index') : '#' }}">@lang('development::lang.settings')</a>
                            <a class="collapse-item {{ request()->routeIs('development.index') ? 'active' : '' }}"
                                href="{{ \Illuminate\Support\Facades\Route::has('development.index') ? route('development.index') : '#' }}">@lang('development::lang.add_development')</a>
                        </div>
                    </div>
                </li>
                <li class="nav-item {{ $request->segment(1) == 'home' ? 'active' : '' }}">
                    <a class="nav-link" href="{{ action('DashboardLogisticsController@index') }}">
                        <i class="fa fa-clone"></i>
                        <span>@lang('home.dashboard_logistics')</span></a>
                </li>

                @includeIf('subscription::layouts_v2.partials.sidebar')


                @if ($contact_module)
                    @if (auth()->user()->can('supplier.view') || auth()->user()->can('customer.view'))
                        <li
                            class="nav-item {{ in_array($request->segment(1), ['contacts', 'customer-group', 'contact-group', 'List_product_bind', 'product_bind', 'customer-reference', 'customer-statement', 'outstanding-received-report']) ? 'active active-sub' : '' }}">
                            <a class="nav-link collapsed" href="#" data-toggle="collapse"
                                data-target="#contacts-menu" aria-expanded="true" aria-controls="contacts-menu">
                                <i class="ti-id-badge"></i>
                                <span>@lang('contact.contacts')</span>
                            </a>
                            <div id="contacts-menu"
                                class="collapse {{ in_array($request->segment(1), ['contacts', 'customer-group', 'contact-group', 'List_product_bind', 'product_bind', 'customer-reference', 'customer-statement', 'outstanding-received-report']) ? 'show' : '' }}"
                                aria-labelledby="headingPages" data-parent="#accordionSidebar">
                                <div class="bg-white py-2 collapse-inner rounded">
                                    <h6 class="collapse-header">@lang('contact.contacts'):</h6>
                                    @if ($contact_supplier)
                                        @can('supplier.view')
                                            <a class="collapse-item {{ $request->input('type') == 'supplier' ? 'active' : '' }}"
                                                href="/contacts?type=supplier">@lang('report.supplier')</a>
                                            @endcan @endif @can('customer.view') @if ($contact_customer)
                                                {{-- @if (!$property_module) --}}
                                                <a class="collapse-item {{ $request->input('type') == 'customer' ? 'active' : '' }}"
                                                    href="/contacts?type=customer">@lang('report.customer')</a>
                                                {{-- @endif --}}
                                                @endif @if ($contact_group_customer)

                                                    @if ($contact_list_customer_loans)
                                                        <a class="collapse-item  {{ $request->segment(1) == 'contacts' ? 'active' : '' }}"
                                                            href="{{ action('ShowCustomerLoansController@index') }}">@lang('contact.list_customer_loans')</a>
                                                    @endif

                                                    @if ($contact_settings)
                                                        <a class="collapse-item {{ $request->segment(1) == 'contacts' ? 'active' : '' }}"
                                                            href="{{ action('ContactController@settings') }}">@lang('contact.settings')</a>
                                                    @endif

                                                    @if ($contact_list_supplier_map_products)
                                                        <a class="collapse-item {{ $request->segment(1) == 'List_product_bind' && $request->segment(2) == 'product_bind' ? 'active' : '' }}"
                                                            href="/product-bind-supplier">@lang('lang_v1.list_supplier_map_products')</a>
                                                    @endif

                                                    @if ($contact_add_supplier_products)
                                                        <a class="collapse-item {{ $request->segment(1) == 'product_bind' && $request->segment(2) == 'product_bind' ? 'active' : '' }}"
                                                            href="/contacts/add-supplier-map-product">@lang('lang_v1.add_supplier_map_products')</a>
                                                    @endif

                                                    <a class="collapse-item {{ $request->segment(1) == 'contact-group' ? 'active' : '' }}"
                                                        href="{{ action('ContactGroupController@index') }}">@lang('lang_v1.contact_groups')</a>
                                                    @endif @endcan @if ($import_contact)
                                                        @if (!$property_module && $contact_customer)
                                                            @if (auth()->user()->can('supplier.create') || auth()->user()->can('customer.create'))
                                                                <a class="collapse-item {{ $request->segment(1) == 'contacts' && $request->segment(2) == 'import' ? 'active' : '' }}"
                                                                    href="{{ route('contacts.import') }}">@lang('lang_v1.import_contacts')</a>
                                                            @endcan
                                                            @endif @endif @if ($customer_reference)
                                                                <a class="collapse-item {{ $request->segment(1) == 'customer-reference' ? 'active' : '' }}"
                                                                    href="{{ action('CustomerReferenceController@index') }}">@lang('lang_v1.customer_reference')</a>
                                                                @endif @if ($contact_customer)
                                                                    <a class="collapse-item {{ $request->segment(1) == 'manual-bill' ? 'active' : '' }}"
                                                                        href="{{ action([\App\Http\Controllers\ManualBillController::class, 'index']) }}">Manual
                                                                        Bill</a>
                                                                    @if ($customer_statement)
                                                                        <a class="collapse-item {{ $request->segment(1) == 'customer-statement' ? 'active' : '' }}"
                                                                            href="{{ action('CustomerStatementController@index') }}">@lang('contact.customer_statements')</a>
                                                                    @endif
                                                                    @if ($customer_statement_pmt)
                                                                        <a class="collapse-item {{ $request->segment(1) == 'customer-statement' ? 'active' : '' }}"
                                                                            href="{{ url('customer-statement/get-statement-list-pmts') }}">@lang('contact.customer_statements_with_payment')</a>
                                                                    @endif
                                                                    @if ($customer_payment)
                                                                        <a class="collapse-item {{ $request->segment(1) == 'customer-payment-simple' ? 'active' : '' }}"
                                                                            href="{{ action('CustomerPaymentController@index') }}">@lang('lang_v1.customer_payments')</a>
                                                                    @endif
                                                                    @if ($outstanding_received)
                                                                        @if (auth()->user()->can('payment_received.view'))
                                                                            <a class="collapse-item {{ $request->segment(1) == 'outstanding-received-report' ? 'active' : '' }}"
                                                                                href="{{ route('contacts.outstanding_received_report.compat') }}">@lang('lang_v1.outstanding_received')</a>
                                                                        @endif

                                                                        @if ($contact_import_opening_balalnces)
                                                                            <a class="collapse-item {{ $request->segment(1) == 'import-balance' ? 'active' : '' }}"
                                                                                href="{{ \Illuminate\Support\Facades\Route::has('contacts.import_balance.compat') ? route('contacts.import_balance.compat') : (\Illuminate\Support\Facades\Route::has('customers.import_balance') ? route('customers.import_balance') : url('/contacts/import-balance')) }}">@lang('lang_v1.import_contacts_balance')</a>
                                                                        @endif

                                                                        @endif @endif @if ($contact_supplier)
                                                                            @if ($issue_payment_detail)
                                                                                <a class="collapse-item {{ $request->segment(1) == 'issued-payment-details' ? 'active' : '' }}"
                                                                                    href="{{ route('contacts.issued_payment_details.compat') }}">@lang('lang_v1.issued_payment_details')</a>
                                                                            @endif
                                                                        @endif

                                                                        @if ($contact_returned_cheque_details)
                                                                            <a class="collapse-item {{ $request->segment(1) == 'returned-cheque-details' ? 'active' : '' }}"
                                                                                href="{{ route('contacts.returned_cheque_details.compat') }}">@lang('sale.returned_cheques_details')</a>
                                                                        @endif

                                                                        <a class="collapse-item {{ $request->segment(1) == 'contact-user-activity' ? 'active' : '' }}"
                                                                            href="{{ action('CustomerStatementController@getUserActivityReport') }}">@lang('lang_v1.contact_module_user_activity')</a>
                                </div>
                            </div>
                        </li>
                    @endif
                @endif

                @includeIf('bakery::layouts_v2.partials.sidebar')


                {{-- Membership and Sales Agent sidebar sections added after original sidebar --}}
                @includeIf('layouts.partials.sidebar-sections.sidebar-membership')
                @includeIf('layouts.partials.sidebar-sections.sidebar-sales-agent')


            @endif
        @endif
        @if (auth()->user()->is_pump_operator)
            @if (auth()->user()->can('pump_operator.dashboard'))
                <li
                    class=" nav-item {{ $request->segment(1) == 'petro' && $request->segment(2) == 'pump-operators' && $request->segment(3) == 'dashboard' ? 'active' : '' }}">
                    <a href="{{ action('\Modules\Petro\Http\Controllers\PumpOperatorController@dashboard') }}"><i
                            class="fa fa-tachometer"></i> <span>@lang('petro::lang.dashboard')</span></a>
                </li>
            @endif
            <li
                class="nav-item {{ $request->segment(1) == 'petro' && $request->segment(2) == 'pump-operators' && $request->segment(3) == 'pumper-day-entries' ? 'active' : '' }}">
                <a href="{{ action('\Modules\Petro\Http\Controllers\PumperDayEntryController@index') }}"><i
                        class="fa fa-calculator"></i> <span>@lang('petro::lang.pumper_day_entries')</span></a>
            </li>
        @endif

        @if ($is_admin && $patient_module)
            @includeIf('myhealth::layouts_v2.partials.sidebar')
        @endif


        @if (auth()->user()->is_customer == 0)
            @if (auth()->user()->can('crm.view'))
                @if ($enable_crm == 1)
                    <li class="nav-item {{ in_array($request->segment(1), ['crm']) ? 'active active-sub' : '' }}">

                        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#crm-menu"
                            aria-expanded="true" aria-controls="crm-menu">
                            <i class="fa fa-users"></i>
                            <span>@lang('lang_v1.crm')</span>
                        </a>
                        <div id="crm-menu"
                            class="collapse {{ in_array($request->segment(1), ['crm']) ? 'show' : '' }}"
                            aria-labelledby="headingPages" data-parent="#accordionSidebar">
                            <div class="bg-white py-2 collapse-inner rounded">
                                <h6 class="collapse-header">@lang('lang_v1.crm'):</h6>
                                @can('crm.view')
                                    <a class="collapse-item {{ $request->segment(1) == 'crm' && $request->input('type') == 'customer' ? 'active' : '' }}"
                                        href="{{ action('CRMController@index') }}">@lang('lang_v1.crm')</a>
                                    <a class="collapse-item {{ $request->segment(1) == 'crmgroups' ? 'active' : '' }}"
                                        href="{{ action('CrmGroupController@index') }}">@lang('lang_v1.crm_group')</a>
                                @endcan
                                <a class="collapse-item {{ $request->segment(1) == 'crm-activity' ? 'active' : '' }}"
                                    href="{{ action('CRMActivityController@index') }}">@lang('lang_v1.crm_activity')</a>
                            </div>
                        </div>
                    </li>
                @endif
            @endif
        @endif

        @if ($leads_module)
            @can('lead.access')
                @includeIf('leads::layouts_v2.partials.sidebar')
            @endcan
        @endif


        <!-- Start Task Management Module -->
        @if ($tasks_management)
            @can('tasks_management.access')
                @includeIf('tasksmanagement::layouts_v2.partials.sidebar')
            @endcan
        @endif


        @if ($installment_module)
            @can('installment_module.access')
                @includeIf('installment::layouts.partials.sidebar')
            @endcan
        @endif

        @if (Auth::guard('agent')->check())
            @includeIf('agent::layouts_v2.partials.sidebar')
        @endif



        @if ($list_credit_sales_page)
            @can('list_credit_sales_page.access')
                <li class="nav-item {{ in_array($request->segment(1), ['credit-sales']) ? 'active active-sub' : '' }}">
                    <a class="nav-link" href="{{ action('ContactCreditSales@index') }}">
                        <i class="fa fa-qrcode"></i>
                        <span>@lang('contact_credit_sales.credit_sales')</span></a>
                </li>
            @endcan
        @endif

        @if ($crm_module)
            @can('crm.access')
                @includeIf('crm::layouts.sidebar')
            @endcan
        @endif

        @if ($ezy_products)
            @can('ezy_products.access')
                <li class="nav-item ">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#ezy-products-menu"
                        aria-expanded="true" aria-controls="ezy-products-menu">
                        <i class="ti-id-badge"></i>
                        <span>@lang('petro::lang.ezy_products')</span>
                    </a>
                    <div id="ezy-products-menu" class="collapse " aria-labelledby="headingPages"
                        data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">@lang('petro::lang.ezy_products'):</h6>

                            @if ($ezy_list_products)
                                <a class="collapse-item {{ $request->segment(1) == 'products' && $request->segment(2) == '' ? 'active' : '' }}"
                                    href="{{ action('\Modules\Petro\Http\Controllers\ProductController@index') }}">@lang('lang_v1.list_products')</a>
                            @endif

                            @if ($ezy_units)
                                <a class="collapse-item {{ $request->segment(1) == 'units' ? 'active' : '' }}"
                                    href="{{ action('\Modules\Petro\Http\Controllers\UnitController@index') }}">@lang('unit.units')</a>
                            @endif

                            @if ($ezy_categories)
                                <a class="collapse-item {{ $request->segment(1) == 'categories' ? 'active' : '' }}"
                                    href="{{ action('\Modules\Petro\Http\Controllers\CategoryController@index') }}">@lang('category.categories')</a>
                            @endif

                        </div>
                    </div>
                </li>
            @endcan
        @endif

        @if ($ezyinvoice_module == 1)
            @can('ezyinvoice.access')
                @includeIf('ezyinvoice::layouts.nav')
            @endcan
        @endif

        @if ($airline_module == 1)
            @can('airline.access')
                @includeIf('airline::layouts.partials.sidebar')
            @endcan
        @endif


        @if ($shipping_module)
            @can('shipping.access')
                @includeIf('shipping::layouts_v2.partials.sidebar')
            @endcan
        @endif

        @if ($property_module)
            @includeIf('property::layouts_v2.partials.sidebar')
        @endif

        @if ($products)
            @if (auth()->user()->can('product.view') ||
                    auth()->user()->can('product.create') ||
                    auth()->user()->can('brand.view') ||
                    auth()->user()->can('unit.view') ||
                    auth()->user()->can('category.view') ||
                    auth()->user()->can('product.product_bind') ||
                    auth()->user()->can('brand.create') ||
                    auth()->user()->can('unit.create') ||
                    auth()->user()->can('category.create'))

                <li
                    class="nav-item {{ in_array($request->segment(1), ['variation-templates', 'products', 'labels', 'product_bind', 'stock_conversion', 'import-products', 'import-opening-stock', 'selling-price-group', 'brands', 'units', 'categories', 'warranties']) ? 'active active-sub' : '' }}">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#products-menu"
                        aria-expanded="true" aria-controls="products-menu">
                        <i class="ti-layout-media-right-alt"></i>
                        <span>@lang('sale.products')</span>
                    </a>
                    <div id="products-menu"
                        class="collapse {{ in_array($request->segment(1), ['variation-templates', 'products', 'labels', 'product_bind', 'stock_conversion', 'import-products', 'import-opening-stock', 'selling-price-group', 'brands', 'units', 'categories', 'warranties']) ? 'show' : '' }}"
                        aria-labelledby="headingPages" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">@lang('sale.products'):</h6>

                            @if (
                                (array_key_exists('products_list_product', $pacakge_details) &&
                                    !empty($pacakge_details['products_list_product'])) ||
                                    !array_key_exists('products_list_product', $pacakge_details))
                                @can('product.view')
                                    <a class="collapse-item {{ $request->segment(1) == 'products' && $request->segment(2) == '' ? 'active' : '' }}"
                                        href="/products">@lang('lang_v1.list_products')</a>
                                @endcan
                            @endif

                            @if (
                                (array_key_exists('products_add_edit', $pacakge_details) && !empty($pacakge_details['products_add_edit'])) ||
                                    !array_key_exists('products_add_edit', $pacakge_details))
                                @can('product.create')
                                    <a class="collapse-item {{ $request->segment(1) == 'products' && $request->segment(2) == 'create' ? 'active' : '' }}"
                                        href="/products/create">@lang('product.add_product')</a>
                                @endcan
                            @endif

                            @can('product.view')
                                @if ($product_print_labels)
                                    <a class="collapse-item {{ $request->segment(1) == 'labels' && $request->segment(2) == 'show' ? 'active' : '' }}"
                                        href="/labels/show">@lang('barcode.print_labels')</a>
                                @endif
                                @endcan @can('product.product_bind')
                            @endcan

                            @if (
                                (array_key_exists('products_variations', $pacakge_details) && !empty($pacakge_details['products_variations'])) ||
                                    !array_key_exists('products_variations', $pacakge_details))
                                @can('product.create')
                                    <a class="collapse-item {{ $request->segment(1) == 'variation-templates' ? 'active' : '' }}"
                                        href="/variation-templates">@lang('product.variations')</a>
                                @endcan
                            @endif

                            @if (
                                (array_key_exists('products_import', $pacakge_details) && !empty($pacakge_details['products_import'])) ||
                                    !array_key_exists('products_import', $pacakge_details))
                                @can('product.create')
                                    <a class="collapse-item {{ $request->segment(1) == 'import-products' ? 'active' : '' }}"
                                        href="/import-products">@lang('product.import_products')</a>
                                @endcan
                            @endif


                            @if (session()->get('business.is_pharmacy'))
                                <a class="collapse-item {{ $request->segment(1) == 'sample-medical-product-list' ? 'active' : '' }}"
                                    href="{{ action('SampleMedicalProductController@index') }}">@lang('lang_v1.sample_medical_product_list')</a>
                            @endif

                            @if (
                                (array_key_exists('products_import_opening_stock', $pacakge_details) &&
                                    !empty($pacakge_details['products_import_opening_stock'])) ||
                                    !array_key_exists('products_import_opening_stock', $pacakge_details))
                                @can('product.opening_stock')
                                    <a class="collapse-item {{ $request->segment(1) == 'import-opening-stock' ? 'active' : '' }}"
                                        href="/import-opening-stock">@lang('lang_v1.import_opening_stock')</a>
                                @endcan
                            @endif

                            @if (
                                (array_key_exists('products_selling_price_group', $pacakge_details) &&
                                    !empty($pacakge_details['products_selling_price_group'])) ||
                                    !array_key_exists('products_selling_price_group', $pacakge_details))
                                @can('product.create')
                                    <a class="collapse-item {{ $request->segment(1) == 'selling-price-group' ? 'active' : '' }}"
                                        href="/selling-price-group">@lang('lang_v1.selling_price_group')</a>
                                @endcan
                            @endif

                            @if (auth()->user()->can('unit.view') || auth()->user()->can('unit.create'))

                                @if (
                                    (array_key_exists('products_units', $pacakge_details) && !empty($pacakge_details['products_units'])) ||
                                        !array_key_exists('products_units', $pacakge_details))
                                    <a class="collapse-item {{ $request->segment(1) == 'units' ? 'active' : '' }}"
                                        href="/units">@lang('unit.units')</a>
                                @endif

                                @if (
                                    (array_key_exists('products_stock_conversion', $pacakge_details) &&
                                        !empty($pacakge_details['products_stock_conversion'])) ||
                                        !array_key_exists('products_stock_conversion', $pacakge_details))
                                    <a class="collapse-item {{ $request->segment(1) == 'stock_conversion' && $request->segment(2) == 'stock_conversion' ? 'active' : '' }}"
                                        href="/stock-conversions">@lang('Stock Conversion')</a>
                                @endif

                            @endif

                            @if (
                                (array_key_exists('products_categories', $pacakge_details) && !empty($pacakge_details['products_categories'])) ||
                                    !array_key_exists('products_categories', $pacakge_details))
                                @if (auth()->user()->can('category.view') || auth()->user()->can('category.create'))
                                    <a class="collapse-item {{ $request->segment(1) == 'categories' ? 'active' : '' }}"
                                        href="/taxonomies?type=product">@lang('category.categories')</a>
                                @endif
                            @endif

                            @if (
                                (array_key_exists('products_brand_warranties', $pacakge_details) &&
                                    !empty($pacakge_details['products_brand_warranties'])) ||
                                    !array_key_exists('products_brand_warranties', $pacakge_details))
                                @if (auth()->user()->can('brand.view') || auth()->user()->can('brand.create'))
                                    <a class="collapse-item {{ $request->segment(1) == 'brands' ? 'active' : '' }}"
                                        href="/brands">@lang('brand.brands')</a>
                                @endif

                                <a class="collapse-item {{ $request->segment(1) == 'warranties' ? 'active active-sub' : '' }}"
                                    href="/warranties">@lang('lang_v1.warranties')</a>
                            @endif

                            @if ($stock_taking_page)
                                <a class="collapse-item {{ $request->segment(1) == 'stock-taking' ? 'active' : '' }}"
                                    href="/stock-taking">@lang('mpcs::lang.StockTaking_form')</a>
                            @endif
                            @if ($enable_petro_module)
                                @if ($merge_sub_category)
                                    <a class="collapse-item {{ $request->segment(1) == 'merged-sub-categories' ? 'active active-sub' : '' }}"
                                        href="{{ action('MergedSubCategoryController@index') }}">@lang('lang_v1.merged_sub_categories')</a>
                                @endif
                            @endif
                        </div>
                    </div>
                </li>
            @endif
        @endif


        {{-- Products New standalone module sidebar - keeps old Product module untouched --}}
        @php
            $productsNewActive = $request->segment(1) == 'products-new';
            $canProductsNew = auth()->check() && (
                auth()->user()->can('products_new.access') ||
                auth()->user()->can('products_new.view') ||
                auth()->user()->can('products_new.dashboard') ||
                auth()->user()->can('products_new.products.view') ||
                auth()->user()->can('products_new.products.create') ||
                auth()->user()->can('product.view')
            );
        @endphp
        @if ($canProductsNew)
            <li class="nav-item {{ $productsNewActive ? 'active active-sub' : '' }}">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#products-new-menu"
                    aria-expanded="{{ $productsNewActive ? 'true' : 'false' }}" aria-controls="products-new-menu">
                    <i class="ti-package"></i>
                    <span>Products New</span>
                </a>
                <div id="products-new-menu" class="collapse {{ $productsNewActive ? 'show' : '' }}"
                    aria-labelledby="headingPages" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Products New:</h6>

                        <a class="collapse-item {{ $request->segment(1) == 'products-new' && $request->segment(2) == '' ? 'active' : '' }}"
                            href="{{ url('/products-new') }}">Dashboard</a>

                        <a class="collapse-item {{ $request->segment(1) == 'products-new' && $request->segment(2) == 'command-center' ? 'active' : '' }}"
                            href="{{ url('/products-new/command-center') }}">Product Command Center</a>

                        <a class="collapse-item {{ $request->segment(1) == 'products-new' && $request->segment(2) == 'products' && $request->segment(3) == '' ? 'active' : '' }}"
                            href="{{ url('/products-new/products') }}">List Products</a>

                        <a class="collapse-item {{ $request->segment(1) == 'products-new' && $request->segment(2) == 'products' && $request->segment(3) == 'create' ? 'active' : '' }}"
                            href="{{ url('/products-new/products/create') }}">Add Product</a>

                        <a class="collapse-item {{ $request->segment(1) == 'products-new' && $request->segment(2) == 'stock-center' ? 'active' : '' }}"
                            href="{{ url('/products-new/stock-center') }}">Stock Center</a>

                        <a class="collapse-item {{ $request->segment(1) == 'products-new' && $request->segment(2) == 'price-center' ? 'active' : '' }}"
                            href="{{ url('/products-new/price-center') }}">Price Center</a>

                        <a class="collapse-item {{ $request->segment(1) == 'products-new' && $request->segment(2) == 'opening-stock' ? 'active' : '' }}"
                            href="{{ url('/products-new/opening-stock') }}">Opening Stock</a>

                        <a class="collapse-item {{ $request->segment(1) == 'products-new' && $request->segment(2) == 'barcode-center' ? 'active' : '' }}"
                            href="{{ url('/products-new/barcode-center') }}">Barcode & Labels</a>

                        <a class="collapse-item {{ $request->segment(1) == 'products-new' && $request->segment(2) == 'media-center' ? 'active' : '' }}"
                            href="{{ url('/products-new/media-center') }}">Media & Documents</a>

                        <a class="collapse-item {{ $request->segment(1) == 'products-new' && $request->segment(2) == 'batch-centre' ? 'active' : '' }}"
                            href="{{ url('/products-new/batch-centre') }}">Batch / Lot Centre</a>

                        <a class="collapse-item {{ $request->segment(1) == 'products-new' && $request->segment(2) == 'serial-centre' ? 'active' : '' }}"
                            href="{{ url('/products-new/serial-centre') }}">Serial Centre</a>

                        <a class="collapse-item {{ $request->segment(1) == 'products-new' && $request->segment(2) == 'warranty-centre' ? 'active' : '' }}"
                            href="{{ url('/products-new/warranty-centre') }}">Warranty Centre</a>

                        <a class="collapse-item {{ $request->segment(1) == 'products-new' && $request->segment(2) == 'inventory-intelligence' ? 'active' : '' }}"
                            href="{{ url('/products-new/inventory-intelligence/executive') }}">Inventory Intelligence</a>

                        <a class="collapse-item {{ $request->segment(1) == 'products-new' && $request->segment(2) == 'import-export' ? 'active' : '' }}"
                            href="{{ url('/products-new/import-export') }}">Import / Export</a>

                        <a class="collapse-item {{ $request->segment(1) == 'products-new' && $request->segment(2) == 'reports' ? 'active' : '' }}"
                            href="{{ url('/products-new/reports') }}">Reports</a>

                        <a class="collapse-item {{ $request->segment(1) == 'products-new' && $request->segment(2) == 'settings' ? 'active' : '' }}"
                            href="{{ url('/products-new/settings') }}">Settings</a>
                    </div>
                </div>
            </li>
        @endif


        @if ($hms_module)
            @can('hms.access')
                <li
                    class="nav-item {{ in_array($request->segment(2), ['dashboard', 'rooms', 'pricing', 'bookings', 'calendar', 'extras', 'unavailables', 'coupons', 'reports', 'amenities', 'settings']) ? 'active active-sub' : '' }}">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#hms-menu"
                        aria-expanded="true" aria-controls="hms-menu">
                        <i class="ti-file"></i>
                        <span>HMS</span>
                    </a>
                    <div id="hms-menu"
                        class="collapse {{ in_array($request->segment(2), ['dashboard', 'rooms', 'pricing', 'bookings', 'calendar', 'extras', 'unavailables', 'coupons', 'reports', 'amenities', 'settings']) ? 'show' : '' }}"
                        aria-labelledby="headingPages" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">Hms:</h6>
                            <a class="collapse-item {{ $request->segment(2) == 'dashboard' ? 'active' : '' }}"
                                href="{{ action([Modules\Hms\Http\Controllers\HmsController::class, 'index']) }}">Dashboard</a>
                            <a class="collapse-item {{ $request->segment(2) == 'rooms' ? 'active' : '' }}"
                                href="{{ action([Modules\Hms\Http\Controllers\RoomController::class, 'index']) }}">@lang('hms::lang.rooms')</a>
                            <a class="collapse-item {{ $request->segment(2) == 'pricing' ? 'active' : '' }}"
                                href="{{ action([Modules\Hms\Http\Controllers\RoomController::class, 'pricing']) }}">@lang('hms::lang.prices')</a>
                            <a class="collapse-item {{ $request->segment(2) == 'bookings' ? 'active' : '' }}"
                                href="{{ action([Modules\Hms\Http\Controllers\HmsBookingController::class, 'index']) }}">@lang('hms::lang.bookings')</a>
                            <a class="collapse-item {{ $request->segment(2) == 'calendar' ? 'active' : '' }}"
                                href="{{ action([Modules\Hms\Http\Controllers\HmsBookingController::class, 'calendar']) }}">@lang('hms::lang.calendar')</a>
                            <a class="collapse-item {{ $request->segment(2) == 'extras' ? 'active' : '' }}"
                                href="{{ action([Modules\Hms\Http\Controllers\ExtraController::class, 'index']) }}">@lang('hms::lang.extras')</a>
                            <a class="collapse-item {{ $request->segment(2) == 'unavailables' ? 'active' : '' }}"
                                href="{{ action([Modules\Hms\Http\Controllers\UnavailableController::class, 'index']) }}">@lang('hms::lang.unavailable')</a>
                            <a class="collapse-item {{ $request->segment(2) == 'coupons' ? 'active' : '' }}"
                                href="{{ action([Modules\Hms\Http\Controllers\HmsCouponController::class, 'index']) }}">@lang('hms::lang.coupons')</a>
                            <a class="collapse-item {{ $request->segment(2) == 'reports' ? 'active' : '' }}"
                                href="{{ action([Modules\Hms\Http\Controllers\HmsReportController::class, 'index']) }}">@lang('hms::lang.reports')</a>
                            <a class="collapse-item {{ $request->segment(2) == 'amenities' ? 'active' : '' }}"
                                href="{{ action([\App\Http\Controllers\TaxonomyController::class, 'index']) . '?type=amenities' }}">@lang('hms::lang.amenities')</a>
                            <a class="collapse-item {{ $request->segment(2) == 'settings' ? 'active' : '' }}"
                                href="{{ action([Modules\Hms\Http\Controllers\HmsSettingController::class, 'index']) }}">@lang('messages.settings')</a>

                        </div>
                    </div>
                </li>
            @endcan
        @endif




        {{-- Modules added after the original sidebar design. Keep these before Petro so all enabled modules remain visible. --}}
        @if (!empty($real_time_entries) && Module::has('RealTimeEntries'))
            @can('real_time_entries.access')
                @includeIf('realtimeentries::layouts_v2.partials.sidebar')
            @endcan
        @endif

        @if (!empty($can_view_daily_collection_menu))
            @includeIf('petro::daily_collection.partials.daily_tab')
        @endif

        @if (!empty($pos2) && Module::has('Pos2'))
            @includeIf('pos2::layouts_v2.partials.sidebar')
        @endif

        {{-- PetroPD sidebar included at top-level above to avoid being hidden by Home Dashboard conditions. --}}

        @if (!empty($show_ev_charging_menu) && Module::has('EvCharging'))
            @includeIf('evcharging::layouts_v2.partials.sidebar')
        @endif

        @if (!empty($can_view_pumper_dashboard_sidebar) && Module::has('PumperDashboard'))
            @includeIf('pumperdashboard::layouts_v2.partials.sidebar')
        @endif

        @if (!empty($can_view_daily_collection_sw_menu))
            @includeIf('dailycollectionsw::partials.daily_tab')
        @endif

        @if (!empty($can_view_stock_reports_sidebar) && Module::has('StockReports'))
            @includeIf('stockreports::layouts.sidebar')
        @endif

        @if (Module::has('PetroDirect'))
            @includeIf('layouts.partials.sidebar-sections.sidebar-petrodirect')
        @endif

        {{-- TEMP SIDEBAR_060: Petro General is shown whenever the module is installed. --}}
        @if (Module::has('PetroGeneral'))
            @includeIf('petrogeneral::layouts_v2.partials.sidebar')
        @endif

        <!-- Start Petro Module -->
        @if ($enable_petro_module)
            {{-- Daily Collection is rendered once above. Do not include it again here. --}}
            @if (auth()->user()->can('petro.access'))
                @includeIf('petro::layouts_v2.partials.sidebar')
            @endif
        @endif

        @if ($dsr_module || $ns_dsr_module)
            @includeIf('dsr::layouts_v2.partials.sidebar')
        @endif

        <!--  @if ($issue_customer_bill)

    @can('issue_customer_bill.access')
    <li class="nav-item {{ in_array($request->segment(2), ['issue-customer-bill']) ? 'active active-sub' : '' }}">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#customerbill-menu" aria-expanded="true" aria-controls="customerbill-menu">
                    <i class="ti-file"></i>
                    <span>@lang('petro::lang.bill_to_customer')</span>
                </a>
                <div id="customerbill-menu " class="collapse {{ in_array($request->segment(2), ['issue-customer-bill']) ? 'show' : '' }}" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">@lang('petro::lang.bill_to_customer'):</h6>
                        <a class="collapse-item {{ $request->segment(2) == 'issue-customer-bill' ? 'active' : '' }}" href="{{ action('\Modules\Petro\Http\Controllers\IssueCustomerBillController@index') }}">@lang('petro::lang.issue_bills_customer')</a>
                    </div>
                </div>
            </li>
@endcan

    @endif -->


        @if ($issue_customer_bill)
            @can('issue_customer_bill.access')
                <li
                    class="nav-item {{ in_array($request->segment(2), ['issue-customer-bill']) ? 'active active-sub' : '' }}">
                    <a class="nav-link"
                        href="{{ action('\Modules\Petro\Http\Controllers\IssueCustomerBillController@index') }}">
                        <i class="ti-file"></i>
                        <span>@lang('petro::lang.issue_bills_customer')</span></a>
                </li>
            @endcan
        @endif


        @if ($issue_customer_bill_vat)
            @can('issue_customer_bill_vat.access')
                <li
                    class="nav-item {{ in_array($request->segment(2), ['issue-customer-bill-with-vat']) ? 'active active-sub' : '' }}">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse"
                        data-target="#customerbillwithvat-menu" aria-expanded="true"
                        aria-controls="customerbillwithvat-menu">
                        <i class="ti-file"></i>
                        <span>@lang('superadmin::lang.issue_customer_bill_vat')</span>
                    </a>
                    <div id="customerbillwithvat-menu"
                        class="collapse {{ in_array($request->segment(2), ['issue-customer-bill-with-vat']) ? 'show' : '' }}"
                        aria-labelledby="headingPages" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">@lang('superadmin::lang.issue_customer_bill_vat'):</h6>
                            <a class="collapse-item {{ $request->segment(2) == 'issue-customer-bill-with-vat' ? 'active' : '' }}"
                                href="{{ action('\Modules\Petro\Http\Controllers\IssueCustomerBillWithVATController@index') }}">@lang('superadmin::lang.issue_customer_bill_vat')</a>
                            <a class="collapse-item {{ $request->segment(2) == 'issue-customer-bill-with-vat' ? 'active' : '' }}"
                                href="{{ action('\Modules\Petro\Http\Controllers\CustomerBillVatPrefixController@index') }}">@lang('petro::lang.prefix_and_starting_nos')</a>
                        </div>
                    </div>
                </li>
            @endcan
        @endif


        <!-- End Petro Module -->

        @if ($settlement_sw_module)
            @can('issue_customer_bill_vat')
                <li class="nav-item {{ $request->segment(1) == 'settlement-sw' ? 'active' : '' }}">
                    <a class="nav-link"
                        href="{{ action('\Modules\SettlementSW\Http\Controllers\SettlementSWController@index') }}">
                        <i class="fa fa-file-invoice-dollar"></i>
                        <span>@lang('lang_v1.settlement_sw')</span>
                    </a>
                </li>
            @endcan
        @endif


        @if ($distribution_module)
            @can('distribution_module.access')
                @includeIf('layouts.partials.sidebar-sections.sidebar-distribution')
            @endcan
        @endif

        @if ($spreadsheet)
            @can('spreadsheet.access')
                <li class="nav-item {{ in_array($request->segment(1), ['spreadsheet']) ? 'active active-sub' : '' }}">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#spreadsheet-menu"
                        aria-expanded="true" aria-controls="spreadsheet-menu">
                        <i class="fas fa fa-file-excel"></i>
                        <span>Spreadsheet</span>
                    </a>
                    <div id="spreadsheet-menu"
                        class="collapse {{ in_array($request->segment(1), ['spreadsheet']) ? 'show' : '' }}"
                        aria-labelledby="headingPages" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">Spreadsheet:</h6>

                            <a class="collapse-item {{ $request->segment(1) == 'spreadsheet' && $request->segment(2) == '' ? 'active' : '' }}"
                                href="{{ action([\Modules\Spreadsheet\Http\Controllers\SpreadsheetController::class, 'index']) }}">Spreadsheet</a>
                        </div>
                    </div>
                </li>
            @endcan
        @endif

        @if ($petro_quota_module)
            <li class="nav-item {{ in_array($request->segment(1), ['vehicles']) ? 'active active-sub' : '' }}">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#petroquota-menu"
                    aria-expanded="true" aria-controls="petroquota-menu">
                    <i class="ti-car"></i>
                    <span>@lang('vehicle.petro_quota')</span>
                </a>
                <div id="petroquota-menu"
                    class="collapse {{ in_array($request->segment(1), ['vehicles']) ? 'show' : '' }}"
                    aria-labelledby="headingPages" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">@lang('vehicle.petro_quota'):</h6>

                        <a class="collapse-item {{ $request->segment(1) == 'vehicles' && $request->segment(2) == '' ? 'active' : '' }}"
                            href="{{ action('\Modules\Petro\Http\Controllers\VehicleController@vehicles_list') }}">@lang('vehicle.registered_vehicle_details')</a>
                    </div>
                </div>
            </li>
        @endif

        <!-- Start MPCS Module -->
        @if ($mpcs_module)
            @if (auth()->user()->can('mpcs.access'))
                @includeIf('mpcs::layouts_v2.partials.sidebar')
            @endif
        @endif
        <!-- End MPCS Module -->

        <!-- Start MPCS Module -->
        @if ($price_changes_module)
            @if (auth()->user()->can('pricechanges.access'))
                @includeIf('pricechanges::layouts_v2.partials.sidebar')
            @endif
        @endif
        <!-- End MPCS Module -->

        <!-- Start MPCS Module -->
        @if ($stock_taking_module)
            @if (auth()->user()->can('mpcs.access'))
                @includeIf('Stocktaking::layouts_v2.partials.sidebar')
            @endif
        @endif
        <!-- End MPCS Module -->

        <!-- Start Fleet Module -->
        @if ($fleet_module)
            @if (auth()->user()->can('fleet.access'))
                @includeIf('fleet::layouts_v2.partials.sidebar')
            @endif
        @endif
        <!-- End Fleet Module -->


        <!-- Start Ezyboat Module -->
        @if ($ezyboat_module)
            {{-- @if (auth()->user()->can('ezyboat.access')) --}}
            @includeIf('ezyboat::layouts_v2.partials.sidebar') {{-- @endif --}}
        @endif
        <!-- End Ezyboat Module -->


        <!-- Start Gold Module -->
        @if ($ran_module)
            @if (auth()->user()->can('ran.access'))
                @includeIf('ran::layouts_v2.partials.sidebar')
            @endif
        @endif
        <!-- End Gold Module -->


        @if (Module::has('Manufacturing'))
            @if ($mf_module)
                @if (auth()->user()->is_customer == 0)
                    @if (auth()->user()->can('manufacturing.access_recipe') || auth()->user()->can('manufacturing.access_production'))
                        @include('manufacturing::layouts_v2.partials.sidebar')
                    @endif
                @endif
            @endif
        @endif


        @php
            // Suppliers standalone menu resolver.
            // Read directly from subscription/package_details as well as extracted variables, because
            // older tenants may not have had the new Suppliers keys in $module_array when their
            // subscriptions were saved. This prevents the module from being enabled in Super Admin
            // but hidden from the tenant sidebar.
            $__supplierPackageDetails = [];
            if (!empty($pacakge_details)) {
                $__supplierPackageDetails = is_array($pacakge_details) ? $pacakge_details : (array) $pacakge_details;
            }
            foreach (['business.package_details', 'package_details'] as $__supplierSessionKey) {
                if (empty($__supplierPackageDetails) && function_exists('session') && session()->has($__supplierSessionKey)) {
                    $__supplierPackageDetails = session($__supplierSessionKey);
                    $__supplierPackageDetails = is_string($__supplierPackageDetails) ? (json_decode($__supplierPackageDetails, true) ?: []) : (array) $__supplierPackageDetails;
                }
            }
            // Keep all existing sidebar items intact. Only resolve Suppliers flags safely here.
            // Blade variables are local variables, not PHP $GLOBALS, therefore using $GLOBALS here
            // can hide the Suppliers menu even when Super Admin has enabled it.
            $__supplierFlags = [
                'suppliers_module' => !empty($suppliers_module),
                'supplier_module' => !empty($supplier_module),
                'suppliers_dashboard' => !empty($suppliers_dashboard),
                'suppliers_all_suppliers' => !empty($suppliers_all_suppliers),
                'suppliers_add_supplier' => !empty($suppliers_add_supplier),
                'suppliers_payments' => !empty($suppliers_payments),
                'suppliers_product_mappings' => !empty($suppliers_product_mappings),
                'suppliers_stock_report' => !empty($suppliers_stock_report),
                'suppliers_import_contacts' => !empty($suppliers_import_contacts),
                'suppliers_issue_payment_details' => !empty($suppliers_issue_payment_details),
                'suppliers_issued_payment_details' => !empty($suppliers_issued_payment_details),
                'suppliers_reports' => !empty($suppliers_reports),
            ];
            $__supplierEnabled = function ($key) use ($__supplierPackageDetails, $__supplierFlags) {
                return !empty($__supplierFlags[$key]) || !empty($__supplierPackageDetails[$key]);
            };
            // Standalone Suppliers is controlled by its own Super Admin / Manage
            // parent permission AND its own Manage Side Bar selection. Contact Module
            // and legacy Contact/Supplier page permissions never enable this menu.
            $supplierModuleEnabled = \Modules\Suppliers\Utils\SupplierPermissionUtil::isGranted($__supplierPackageDetails);
            $supplierMenuEnabled = \Modules\Suppliers\Utils\SupplierPermissionUtil::isEnabled($__supplierPackageDetails);
            $supplierShowAll = $supplierModuleEnabled;
            $supplierUrl = function ($routeName, $fallback) {
                return \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName) : url($fallback);
            };
        @endphp
        @if ((Module::has('Suppliers') || class_exists('Modules\\Suppliers\\Providers\\SuppliersServiceProvider')) && $supplierMenuEnabled && \Modules\Suppliers\Utils\SupplierPermissionUtil::userCanAccess())
            @php $__supplierMenuRendered = true; @endphp
            <li class="nav-item {{ $request->segment(1) == 'suppliers' ? 'active active-sub' : '' }}">
                <a class="nav-link collapsed" href="#" data-toggle="collapse"
                    data-target="#suppliers-menu" aria-expanded="true" aria-controls="suppliers-menu">
                    <i class="fa fa-truck"></i>
                    <span>Supplier Module</span>
                </a>
                <div id="suppliers-menu"
                    class="collapse {{ $request->segment(1) == 'suppliers' ? 'show' : '' }}"
                    aria-labelledby="headingPages" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Supplier Module:</h6>
                        @if ($supplierShowAll || !empty($suppliers_dashboard) || !empty($__supplierPackageDetails['suppliers_dashboard']))
                            <a class="collapse-item {{ request()->routeIs('suppliers.dashboard') ? 'active' : '' }}"
                                href="{{ $supplierUrl('suppliers.dashboard', 'suppliers/dashboard') }}">
                                Dashboard
                            </a>
                        @endif
                        @if ($supplierShowAll || !empty($suppliers_all_suppliers) || !empty($__supplierPackageDetails['suppliers_all_suppliers']))
                            <a class="collapse-item {{ request()->routeIs('suppliers.records.index') ? 'active' : '' }}"
                                href="{{ $supplierUrl('suppliers.records.index', 'suppliers') }}">
                                List Suppliers
                            </a>
                        @endif
                        @if ($supplierShowAll || !empty($suppliers_add_supplier) || !empty($__supplierPackageDetails['suppliers_add_supplier']))
                            <a class="collapse-item {{ request()->routeIs('suppliers.records.create') ? 'active' : '' }}"
                                href="{{ $supplierUrl('suppliers.records.create', 'suppliers/create') }}">
                                Add Supplier
                            </a>
                        @endif
                        @if ($supplierShowAll || !empty($suppliers_payments) || !empty($__supplierPackageDetails['suppliers_payments']))
                            <a class="collapse-item {{ request()->routeIs('suppliers.payments.index') ? 'active' : '' }}"
                                href="{{ $supplierUrl('suppliers.payments.index', 'suppliers/payments/list') }}">
                                Supplier Payments
                            </a>
                        @endif
                        @if ($supplierShowAll || !empty($suppliers_product_mappings) || !empty($__supplierPackageDetails['suppliers_product_mappings']))
                            <a class="collapse-item {{ request()->routeIs('suppliers.mappings.index') ? 'active' : '' }}"
                                href="{{ $supplierUrl('suppliers.mappings.index', 'suppliers/product-mappings/list') }}">
                                Supplier Product Mapping
                            </a>
                        @endif
                        @if ($supplierShowAll || !empty($suppliers_stock_report) || !empty($__supplierPackageDetails['suppliers_stock_report']))
                            <a class="collapse-item {{ request()->routeIs('suppliers.stock_report.index') ? 'active' : '' }}"
                                href="{{ $supplierUrl('suppliers.stock_report.index', 'suppliers/stock-report/list') }}">
                                Supplier Stock Report
                            </a>
                        @endif
                        @if ($supplierShowAll || !empty($suppliers_import_contacts) || !empty($__supplierPackageDetails['suppliers_import_contacts']))
                            <a class="collapse-item {{ request()->routeIs('suppliers.imports.index') ? 'active' : '' }}"
                                href="{{ $supplierUrl('suppliers.imports.index', 'suppliers/imports/contacts') }}">
                                Import Suppliers
                            </a>
                        @endif
                        @if ($supplierShowAll || $__supplierEnabled('suppliers_issue_payment_details') || $__supplierEnabled('suppliers_issued_payment_details'))
                            <a class="collapse-item {{ request()->routeIs('suppliers.issue_payment_details.index') || request()->routeIs('suppliers.issued_payment_details.index') ? 'active' : '' }}"
                                href="{{ $supplierUrl('suppliers.issue_payment_details.index', 'suppliers/issue-payment-details') }}">
                                Issued Payment Details
                            </a>
                        @endif
                        @if ($supplierShowAll || $__supplierEnabled('suppliers_user_activity'))
                            <a class="collapse-item {{ request()->routeIs('suppliers.user_activity.index') ? 'active' : '' }}"
                                href="{{ $supplierUrl('suppliers.user_activity.index', 'suppliers/user-activity') }}">
                                Supplier User Activity
                            </a>
                        @endif
                        @if ($supplierShowAll || !empty($suppliers_reports) || !empty($__supplierPackageDetails['suppliers_reports']))
                            <a class="collapse-item {{ request()->routeIs('suppliers.reports.index') ? 'active' : '' }}"
                                href="{{ $supplierUrl('suppliers.reports.index', 'suppliers/reports/index') }}">
                                Reports
                            </a>
                        @endif
                    </div>
                </div>
            </li>
        @endif

        @if ($purchase)
            @if (auth()->user()->can('purchase.view') ||
                    auth()->user()->can('purchase.create') ||
                    auth()->user()->can('purchase.update'))
                <li
                    class="nav-item {{ in_array($request->segment(1), ['purchases', 'purchase-return', 'import-purchases']) ? 'active active-sub' : '' }}">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse"
                        data-target="#purchases-menu" aria-expanded="true" aria-controls="purchases-menu">
                        <i class="ti-shopping-cart-full"></i>
                        <span>@lang('purchase.purchases')</span>
                    </a>
                    <div id="purchases-menu"
                        class="collapse {{ in_array($request->segment(1), ['purchases', 'purchase-return', 'import-purchases']) ? 'show' : '' }}"
                        aria-labelledby="headingPages" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">@lang('purchase.purchases'):</h6>
                            @if ($all_purchase)
                                <a class="collapse-item {{ $request->segment(1) == 'purchases' && $request->segment(2) == null ? 'active' : '' }}"
                                    href="/purchases">@lang('purchase.list_purchase')</a>
                            @endif
                            @if ($add_bulk_purchase)
                                <a class="collapse-item {{ $request->segment(1) == 'purchases' && $request->segment(2) == 'add-purchase-bulk' ? 'active' : '' }}"
                                    href="/purchases/add-purchase-bulk">@lang('purchase.add_purchase_bulk')</a>
                            @endif
                            @if ($add_purchase)
                                <a class="collapse-item {{ $request->segment(1) == 'purchases' && $request->segment(2) == 'create' ? 'active' : '' }}"
                                    href="/purchases/create">@lang('purchase.add_purchase')</a>
                            @endif
                            @if ($purchase_return)
                                <a class="collapse-item {{ $request->segment(1) == 'purchase-return' ? 'active' : '' }}"
                                    href="/purchase-return">@lang('lang_v1.list_purchase_return')</a>
                            @endif
                            @if ($import_purchase)
                                <a class="collapse-item {{ $request->segment(1) == 'import-purchases' ? 'active' : '' }}"
                                    href="/import-purchases">@lang('lang_v1.import_purchases')</a>
                            @endif
                        </div>
                    </div>
                </li>

            @endif
        @endif


        @if ($sale_module)
            @if (auth()->user()->can('sell.view') ||
                    auth()->user()->can('sell.create') ||
                    auth()->user()->can('direct_sell.access') ||
                    auth()->user()->can('view_own_sell_only'))
                <li
                    class="nav-item {{ in_array($request->segment(1), ['sales', 'pos', 'sell-return', 'ecommerce', 'discount', 'shipments', 'import-sales', 'reserved-stocks']) ? 'active active-sub' : '' }}">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#sale-menu"
                        aria-expanded="true" aria-controls="sale-menu">
                        <i class="ti-shopping-cart"></i>
                        <span>@lang('sale.sale')</span>
                    </a>
                    <div id="sale-menu"
                        class="collapse  {{ in_array($request->segment(1), ['sales', 'pos', 'sell-return', 'ecommerce', 'discount', 'shipments', 'import-sales', 'reserved-stocks']) ? 'show' : '' }}"
                        aria-labelledby="headingPages" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">@lang('sale.sale'):</h6>
                            @if ($all_sales)
                                @if (auth()->user()->can('direct_sell.access') || auth()->user()->can('view_own_sell_only'))
                                    <a class="collapse-item {{ $request->segment(1) == 'sales' && $request->segment(2) == null ? 'active' : '' }}"
                                        href="/sells">@lang('lang_v1.all_sales')</a>
                                @endif
                            @endif
                            <!-- Call superadmin module if defined -->
                            @if (Module::has('Ecommerce'))
                                @includeIf('ecommerce::layouts_v2.partials.sell_sidebar')
                                @endif @if ($add_sale)
                                    @can('direct_sell.access')
                                        <a class="collapse-item {{ $request->segment(1) == 'sales' && $request->segment(2) == 'create' ? 'active' : '' }}"
                                            href="/sells/create">@lang('sale.add_sale')</a>
                                    @endcan
                                    @endif @if ($list_pos)
                                        @can('sell.view')
                                            <a class="collapse-item {{ $request->segment(1) == 'pos' && $request->segment(2) == null ? 'active' : '' }}"
                                                href="/pos">@lang('sale.list_pos')</a>
                                        @endcan
                                    @endif @can('sell.create')
                                    <a class="collapse-item {{ $request->segment(1) == 'pos' && $request->segment(2) == 'create' ? 'active' : '' }}"
                                        href="/pos/create">@lang('sale.pos_sale')</a>
                                    @endcan @if ($list_draft)
                                        @can('list_drafts')
                                            <a class="collapse-item {{ $request->segment(1) == 'sales' && $request->segment(2) == 'drafts' ? 'active' : '' }}"
                                                href="/sales/drafts">@lang('lang_v1.list_drafts')</a>
                                        @endcan
                                        @endif @if ($list_quotation)
                                            @can('list_quotations')
                                                <a class="collapse-item {{ $request->segment(1) == 'sales' && $request->segment(2) == 'quotations' ? 'active' : '' }}"
                                                    href="/sales/quotations">@lang('lang_v1.list_quotations')</a>
                                            @endcan
                                            @endif @if ($customer_order_own_customer == 1 || $customer_order_general_customer == 1)
                                                @if ($list_orders)
                                                    <a class="collapse-item {{ $request->segment(1) == 'sales' && $request->segment(2) == 'customer-orders' ? 'active' : '' }}"
                                                        href="/sales/customer/orders">@lang('lang_v1.list_orders')</a>
                                                    @endif @if ($upload_orders)
                                                        <a class="collapse-item {{ $request->segment(1) == 'sales' && $request->segment(2) == 'customer-orders' ? 'active' : '' }}"
                                                            href="/sales/customer/uploaded-orders">@lang('customer.uploaded_orders')</a>
                                                        @endif @endif @if ($list_sell_return)
                                                            @can('sell.view')
                                                                <a class="collapse-item {{ $request->segment(1) == 'sell-return' && $request->segment(2) == null ? 'active' : '' }}"
                                                                    href="/sell-return">@lang('lang_v1.list_sell_return')</a>
                                                            @endcan
                                                            @endif @if ($shipment)
                                                                @can('access_shipping')
                                                                    <a class="collapse-item {{ $request->segment(1) == 'shipments' ? 'active' : '' }}"
                                                                        href="/shipments">@lang('lang_v1.shipments')</a>
                                                                @endcan
                                                                @endif @if ($discount)
                                                                    @can('discount.access')
                                                                        <a class="collapse-item {{ $request->segment(1) == 'discount' ? 'active' : '' }}"
                                                                            href="/discount">@lang('lang_v1.discounts')</a>
                                                                    @endcan
                                                                    @endif @if ($subcriptions)
                                                                        @if (auth()->user()->can('direct_sell.access'))
                                                                            <a class="collapse-item {{ $request->segment(1) == 'subscriptions' ? 'active' : '' }}"
                                                                                href="/sells/subscriptions">@lang('lang_v1.subscriptions')</a>
                                                                        @endif
                                                                    @endif
                                                                    @if ($import_sale)
                                                                        <a class="collapse-item {{ $request->segment(1) == 'import-sales' ? 'active' : '' }}"
                                                                            href="/import-sales">@lang('lang_v1.import_sales')</a>
                                                                        @endif @if ($reserved_stock)
                                                                            <a class="collapse-item {{ $request->segment(1) == 'reserved-stocks' ? 'active' : '' }}"
                                                                                href="/reserved-stocks">@lang('lang_v1.reserved_stocks')</a>
                                                                        @endif
                                                                        @if ($customer_settings)
                                                                            @if ($over_limit_sales)
                                                                                <a class="collapse-item {{ $request->segment(1) == 'sales' && $request->segment(2) == 'over-limit-sales' ? 'active' : '' }}"
                                                                                    href="/sells/over-limit-sales">@lang('sale.over_limit_sales')</a>
                                                                            @endif
                                                                        @endif
                        </div>
                    </div>
                </li>
            @endif
        @endif

        @if ($tpos_module)
            @can('tpos_module.access')
                <li
                    class="nav-item {{ in_array($request->segment(1), ['sell', 'tpos', 'fpos']) ? 'active active-sub' : '' }}">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#tpos-menu"
                        aria-expanded="true" aria-controls="sale-menu">
                        <i class="ti-shopping-cart"></i>
                        <span>@lang('tpos.tpos')</span>
                    </a>
                    <div id="tpos-menu" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">@lang('tpos.tpos'):</h6>
                            <a class="collapse-item {{ $request->segment(1) == 'sales' && $request->segment(2) == null ? 'active' : '' }}"
                                href="/tpos/create">@lang('tpos.add_tpos')</a>

                            <a class="collapse-item {{ $request->segment(1) == 'sales' && $request->segment(2) == null ? 'active' : '' }}"
                                href="/tpos">@lang('tpos.list_tpos')</a>

                            <a class="collapse-item {{ $request->segment(1) == 'sales' && $request->segment(2) == null ? 'active' : '' }}"
                                href="/fpos/create">@lang('tpos.add_fpos')</a>

                            <a class="collapse-item {{ $request->segment(1) == 'sales' && $request->segment(2) == null ? 'active' : '' }}"
                                href="/list/fpos">@lang('tpos.list_fpos')</a>
                        </div>
                    </div>
                </li>
            @endcan
        @endif




        @if (Module::has('Repair'))
            @if ($repair_module)
                @if (auth()->user()->can('repair.access'))
                    @includeIf('repair::layouts.sidebar')
                @endif
            @endif
        @endif


        @if ($auto_services_and_repair_module)
            @if (auth()->user()->can('repair.access'))
                @includeIf('autorepairservices::layouts.sidebar')
            @endif
        @endif

        {{-- Auto Service standalone module menu: do not depend on Repair module/sidebar. --}}
        @php
            $__autoservice_sidebar_enabled = false;
            try {
                $__autoservice_package_details = [];
                if (function_exists('session') && session()->has('business.package_details')) {
                    $__autoservice_package_details = session('business.package_details');
                } elseif (function_exists('session') && session()->has('package_details')) {
                    $__autoservice_package_details = session('package_details');
                }

                if (is_string($__autoservice_package_details)) {
                    $__autoservice_package_details = json_decode($__autoservice_package_details, true) ?: [];
                }

                foreach ([
                    'auto_service_module', 'autoservice_module', 'autoservice', 'AutoService',
                    'auto_services_and_repair_module', 'auto_services_module', 'auto_repair_module'
                ] as $__autoservice_key) {
                    if (!empty($__autoservice_package_details[$__autoservice_key]) || !empty($module_array[$__autoservice_key])) {
                        $__autoservice_sidebar_enabled = true;
                        break;
                    }
                }

                $__autoservice_sidebar_enabled = $__autoservice_sidebar_enabled
                    || !empty($auto_service_module ?? null)
                    || !empty($autoservice_module ?? null)
                    || !empty($autoservice ?? null)
                    || !empty($auto_services_and_repair_module ?? null)
                    || auth()->user()->can('superadmin')
                    || auth()->user()->can('autoservice.view')
                    || auth()->user()->can('autoservice.settings')
                    || auth()->user()->can('autoservice.reports');
            } catch (\Throwable $e) {
                $__autoservice_sidebar_enabled = !empty($auto_service_module ?? null)
                    || !empty($auto_services_and_repair_module ?? null)
                    || auth()->user()->can('superadmin')
                    || auth()->user()->can('autoservice.view');
            }
        @endphp

        @if (!empty($__autoservice_sidebar_enabled))
            <li class="nav-item {{ in_array($request->segment(1), ['auto-service', 'autoservice']) ? 'active active-sub' : '' }}" id="autoservice-sidebar-menu-v1">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#autoservice-menu-v1"
                    aria-expanded="{{ in_array($request->segment(1), ['auto-service', 'autoservice']) ? 'true' : 'false' }}" aria-controls="autoservice-menu-v1">
                    <i class="fa fa-car"></i>
                    <span>Auto Service</span>
                </a>
                <div id="autoservice-menu-v1"
                    class="collapse {{ in_array($request->segment(1), ['auto-service', 'autoservice']) ? 'show' : '' }}"
                    aria-labelledby="headingPages" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Auto Service:</h6>
                        <a class="collapse-item {{ $request->segment(1) == 'auto-service' && empty($request->segment(2)) ? 'active' : '' }}" href="{{ url('/auto-service') }}">Dashboard</a>
                        <a class="collapse-item {{ $request->segment(2) == 'command-centre' ? 'active' : '' }}" href="{{ url('/auto-service/command-centre') }}">Command Centre</a>
                        <a class="collapse-item {{ $request->segment(2) == 'receptions' ? 'active' : '' }}" href="{{ url('/auto-service/receptions') }}">Receptions</a>
                        <a class="collapse-item {{ $request->segment(2) == 'vehicles' ? 'active' : '' }}" href="{{ url('/auto-service/vehicles') }}">Vehicles</a>
                        <a class="collapse-item {{ $request->segment(2) == 'jobs' ? 'active' : '' }}" href="{{ url('/auto-service/jobs') }}">Jobs</a>
                        <a class="collapse-item {{ $request->segment(2) == 'appointments' ? 'active' : '' }}" href="{{ url('/auto-service/appointments') }}">Appointments</a>
                        <a class="collapse-item {{ $request->segment(2) == 'estimates' ? 'active' : '' }}" href="{{ url('/auto-service/estimates') }}">Estimates</a>
                        <a class="collapse-item {{ $request->segment(2) == 'invoices' ? 'active' : '' }}" href="{{ url('/auto-service/invoices') }}">Invoices</a>
                        <a class="collapse-item {{ $request->segment(2) == 'payments' ? 'active' : '' }}" href="{{ url('/auto-service/payments') }}">Payments</a>
                        <a class="collapse-item {{ $request->segment(2) == 'reports' ? 'active' : '' }}" href="{{ url('/auto-service/reports') }}">Reports</a>
                        <a class="collapse-item {{ $request->segment(2) == 'settings' ? 'active' : '' }}" href="{{ url('/auto-service/settings') }}">Settings</a>
                    </div>
                </div>
            </li>
        @endif

        {{-- Beauty Saloons standalone module menu: visible from package/module status without depending on other modules. --}}
        @php
            $__beauty_sidebar_enabled = false;
            try {
                $__beauty_package_details = [];
                if (function_exists('session') && session()->has('business.package_details')) {
                    $__beauty_package_details = session('business.package_details');
                } elseif (function_exists('session') && session()->has('package_details')) {
                    $__beauty_package_details = session('package_details');
                }

                if (is_string($__beauty_package_details)) {
                    $__beauty_package_details = json_decode($__beauty_package_details, true) ?: [];
                }

                foreach ([
                    'beauty_saloons_module', 'beautysaloons_module', 'beauty_saloons', 'beautysaloons',
                    'beauty_saloon_module', 'beauty_salon_module', 'BeautySaloons'
                ] as $__beauty_key) {
                    if (!empty($__beauty_package_details[$__beauty_key]) || !empty($module_array[$__beauty_key])) {
                        $__beauty_sidebar_enabled = true;
                        break;
                    }
                }

                $__beauty_sidebar_enabled = $__beauty_sidebar_enabled
                    || !empty($beauty_saloons_module ?? null)
                    || !empty($beautysaloons_module ?? null)
                    || !empty($beauty_saloons ?? null)
                    || !empty($beautysaloons ?? null)
                    || auth()->user()->can('superadmin')
                    || auth()->user()->can('beautysaloons.view')
                    || auth()->user()->can('beauty_saloons.view')
                    || auth()->user()->can('beautysaloons.settings')
                    || auth()->user()->can('beautysaloons.reports');
            } catch (\Throwable $e) {
                $__beauty_sidebar_enabled = !empty($beauty_saloons_module ?? null)
                    || !empty($beautysaloons_module ?? null)
                    || auth()->user()->can('superadmin')
                    || auth()->user()->can('beautysaloons.view')
                    || auth()->user()->can('beauty_saloons.view');
            }
        @endphp

        @if (!empty($__beauty_sidebar_enabled))
            <li class="nav-item {{ $request->segment(1) == 'beauty-saloons' ? 'active active-sub' : '' }}" id="beautysaloons-sidebar-menu-v1">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#beautysaloons-menu-v1"
                    aria-expanded="{{ $request->segment(1) == 'beauty-saloons' ? 'true' : 'false' }}" aria-controls="beautysaloons-menu-v1">
                    <i class="fa fa-scissors"></i>
                    <span>Beauty Saloons</span>
                </a>
                <div id="beautysaloons-menu-v1"
                    class="collapse {{ $request->segment(1) == 'beauty-saloons' ? 'show' : '' }}"
                    aria-labelledby="headingPages" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Beauty Saloons:</h6>
                        <a class="collapse-item {{ $request->segment(1) == 'beauty-saloons' && empty($request->segment(2)) ? 'active' : '' }}" href="{{ url('/beauty-saloons') }}">Dashboard</a>
                        <a class="collapse-item {{ $request->segment(2) == 'dashboards' ? 'active' : '' }}" href="{{ url('/beauty-saloons/dashboards/executive') }}">Executive Dashboard</a>
                        <a class="collapse-item {{ $request->segment(2) == 'appointment-calendar' || $request->segment(2) == 'appointments' ? 'active' : '' }}" href="{{ url('/beauty-saloons/appointment-calendar') }}">Appointments</a>
                        <a class="collapse-item {{ $request->segment(2) == 'customers' ? 'active' : '' }}" href="{{ url('/beauty-saloons/customers') }}">Customers</a>
                        <a class="collapse-item {{ $request->segment(2) == 'services' ? 'active' : '' }}" href="{{ url('/beauty-saloons/services') }}">Services</a>
                        <a class="collapse-item {{ $request->segment(2) == 'staff' ? 'active' : '' }}" href="{{ url('/beauty-saloons/staff') }}">Staff</a>
                        <a class="collapse-item {{ $request->segment(2) == 'pos' ? 'active' : '' }}" href="{{ url('/beauty-saloons/pos') }}">POS / Billing</a>
                        <a class="collapse-item {{ $request->segment(2) == 'inventory' ? 'active' : '' }}" href="{{ url('/beauty-saloons/inventory/products') }}">Inventory</a>
                        <a class="collapse-item {{ $request->segment(2) == 'memberships' || $request->segment(2) == 'packages' ? 'active' : '' }}" href="{{ url('/beauty-saloons/memberships') }}">Memberships & Packages</a>
                        <a class="collapse-item {{ $request->segment(2) == 'loyalty' ? 'active' : '' }}" href="{{ url('/beauty-saloons/loyalty') }}">Loyalty</a>
                        <a class="collapse-item {{ $request->segment(2) == 'finance' ? 'active' : '' }}" href="{{ url('/beauty-saloons/finance/mappings') }}">Finance</a>
                        <a class="collapse-item {{ $request->segment(2) == 'reports' ? 'active' : '' }}" href="{{ url('/beauty-saloons/reports') }}">Reports</a>
                        <a class="collapse-item {{ $request->segment(2) == 'settings' ? 'active' : '' }}" href="{{ url('/beauty-saloons/settings') }}">Settings</a>
                    </div>
                </div>
            </li>
        @endif



        {{-- Hotel Management standalone module menu: visible from package/module status without depending on other modules. --}}
        @php
            $__hotel_sidebar_enabled = false;
            try {
                $__hotel_package_details = [];
                if (function_exists('session') && session()->has('business.package_details')) {
                    $__hotel_package_details = session('business.package_details');
                } elseif (function_exists('session') && session()->has('package_details')) {
                    $__hotel_package_details = session('package_details');
                }

                if (is_string($__hotel_package_details)) {
                    $__hotel_package_details = json_decode($__hotel_package_details, true) ?: [];
                }

                foreach ([
                    'hotel_management_module', 'hotelmanagement_module', 'hotel_management', 'hotelmanagement',
                    'hotel_module', 'HotelManagement'
                ] as $__hotel_key) {
                    if (!empty($__hotel_package_details[$__hotel_key]) || !empty($module_array[$__hotel_key])) {
                        $__hotel_sidebar_enabled = true;
                        break;
                    }
                }

                $__hotel_sidebar_enabled = $__hotel_sidebar_enabled
                    || !empty($hotel_management_module ?? null)
                    || !empty($hotelmanagement_module ?? null)
                    || !empty($hotel_management ?? null)
                    || !empty($hotelmanagement ?? null)
                    || auth()->user()->can('superadmin')
                    || auth()->user()->can('hotelmanagement.view')
                    || auth()->user()->can('hotel_management.view')
                    || auth()->user()->can('hotelmanagement.settings')
                    || auth()->user()->can('hotelmanagement.reports');
            } catch (\Throwable $e) {
                $__hotel_sidebar_enabled = !empty($hotel_management_module ?? null)
                    || !empty($hotelmanagement_module ?? null)
                    || auth()->user()->can('superadmin')
                    || auth()->user()->can('hotelmanagement.view')
                    || auth()->user()->can('hotel_management.view');
            }
        @endphp

        @if (!empty($__hotel_sidebar_enabled))
            <li class="nav-item {{ $request->segment(1) == 'hotel-management' ? 'active active-sub' : '' }}" id="hotelmanagement-sidebar-menu-v1">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#hotelmanagement-menu-v1"
                    aria-expanded="{{ $request->segment(1) == 'hotel-management' ? 'true' : 'false' }}" aria-controls="hotelmanagement-menu-v1">
                    <i class="fa fa-bed"></i>
                    <span>Hotel Management</span>
                </a>
                <div id="hotelmanagement-menu-v1"
                    class="collapse {{ $request->segment(1) == 'hotel-management' ? 'show' : '' }}"
                    aria-labelledby="headingPages" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Hotel Management:</h6>
                        <a class="collapse-item {{ $request->segment(1) == 'hotel-management' && empty($request->segment(2)) ? 'active' : '' }}" href="{{ url('/hotel-management') }}">Dashboard</a>
                        <a class="collapse-item {{ $request->segment(2) == 'dashboards' ? 'active' : '' }}" href="{{ url('/hotel-management/dashboards') }}">Dashboards</a>
                        <a class="collapse-item {{ $request->segment(2) == 'hotels' ? 'active' : '' }}" href="{{ url('/hotel-management/hotels') }}">Hotels / Branch Setup</a>
                        <a class="collapse-item {{ $request->segment(2) == 'rooms' ? 'active' : '' }}" href="{{ url('/hotel-management/rooms') }}">Rooms</a>
                        <a class="collapse-item {{ $request->segment(2) == 'rate-plans' ? 'active' : '' }}" href="{{ url('/hotel-management/rate-plans') }}">Rate Plans</a>
                        <a class="collapse-item {{ $request->segment(2) == 'reservations' ? 'active' : '' }}" href="{{ url('/hotel-management/reservations') }}">Reservations</a>
                        <a class="collapse-item {{ $request->segment(2) == 'front-office' ? 'active' : '' }}" href="{{ url('/hotel-management/front-office') }}">Front Office</a>
                        <a class="collapse-item {{ $request->segment(2) == 'housekeeping' ? 'active' : '' }}" href="{{ url('/hotel-management/housekeeping') }}">Housekeeping</a>
                        <a class="collapse-item {{ $request->segment(2) == 'billing' ? 'active' : '' }}" href="{{ url('/hotel-management/billing') }}">Billing</a>
                        <a class="collapse-item {{ $request->segment(2) == 'pos' ? 'active' : '' }}" href="{{ url('/hotel-management/pos') }}">Hotel POS</a>
                        <a class="collapse-item {{ $request->segment(2) == 'inventory' ? 'active' : '' }}" href="{{ url('/hotel-management/inventory') }}">Inventory</a>
                        <a class="collapse-item {{ $request->segment(2) == 'guest-crm' ? 'active' : '' }}" href="{{ url('/hotel-management/guest-crm') }}">Guest CRM</a>
                        <a class="collapse-item {{ $request->segment(2) == 'reports' ? 'active' : '' }}" href="{{ url('/hotel-management/reports') }}">Reports</a>
                    </div>
                </div>
            </li>
        @endif

        {{-- Tailoring standalone module menu: visible from package/module status without depending on other modules. --}}
        @php
            $__tailoring_sidebar_enabled = false;
            try {
                $__tailoring_package_details = [];
                if (function_exists('session') && session()->has('business.package_details')) {
                    $__tailoring_package_details = session('business.package_details');
                } elseif (function_exists('session') && session()->has('package_details')) {
                    $__tailoring_package_details = session('package_details');
                }

                if (is_string($__tailoring_package_details)) {
                    $__tailoring_package_details = json_decode($__tailoring_package_details, true) ?: [];
                }

                foreach ([
                    'tailoring_module', 'tailoring', 'Tailoring', 'garment_module', 'tailoring_shop_module'
                ] as $__tailoring_key) {
                    if (!empty($__tailoring_package_details[$__tailoring_key]) || !empty($module_array[$__tailoring_key])) {
                        $__tailoring_sidebar_enabled = true;
                        break;
                    }
                }

                $__tailoring_sidebar_enabled = $__tailoring_sidebar_enabled
                    || !empty($tailoring_module ?? null)
                    || !empty($tailoring ?? null)
                    || auth()->user()->can('superadmin')
                    || auth()->user()->can('tailoring.view')
                    || auth()->user()->can('tailoring.settings')
                    || auth()->user()->can('tailoring.reports');
            } catch (\Throwable $e) {
                $__tailoring_sidebar_enabled = !empty($tailoring_module ?? null)
                    || !empty($tailoring ?? null)
                    || auth()->user()->can('superadmin')
                    || auth()->user()->can('tailoring.view');
            }
        @endphp

        @if (!empty($__tailoring_sidebar_enabled))
            <li class="nav-item {{ $request->segment(1) == 'tailoring' ? 'active active-sub' : '' }}" id="tailoring-sidebar-menu-v1">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#tailoring-menu-v1"
                    aria-expanded="{{ $request->segment(1) == 'tailoring' ? 'true' : 'false' }}" aria-controls="tailoring-menu-v1">
                    <i class="fa fa-cut"></i>
                    <span>Tailoring</span>
                </a>
                <div id="tailoring-menu-v1"
                    class="collapse {{ $request->segment(1) == 'tailoring' ? 'show' : '' }}"
                    aria-labelledby="headingPages" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Tailoring:</h6>
                        <a class="collapse-item {{ $request->segment(1) == 'tailoring' && empty($request->segment(2)) ? 'active' : '' }}" href="{{ url('/tailoring') }}">Dashboard</a>
                        <a class="collapse-item {{ $request->segment(2) == 'customers' ? 'active' : '' }}" href="{{ url('/tailoring/customers') }}">Customers</a>
                        <a class="collapse-item {{ $request->segment(2) == 'measurements' ? 'active' : '' }}" href="{{ url('/tailoring/measurements') }}">Measurements</a>
                        <a class="collapse-item {{ $request->segment(2) == 'measurement-profiles' ? 'active' : '' }}" href="{{ url('/tailoring/measurement-profiles') }}">Measurement Profiles</a>
                        <a class="collapse-item {{ $request->segment(2) == 'garments' ? 'active' : '' }}" href="{{ url('/tailoring/garments') }}">Garments</a>
                        <a class="collapse-item {{ $request->segment(2) == 'garment-templates' ? 'active' : '' }}" href="{{ url('/tailoring/garment-templates') }}">Garment Templates</a>
                        <a class="collapse-item {{ $request->segment(2) == 'orders' ? 'active' : '' }}" href="{{ url('/tailoring/orders') }}">Orders</a>
                        <a class="collapse-item {{ $request->segment(2) == 'quotations' ? 'active' : '' }}" href="{{ url('/tailoring/quotations') }}">Quotations</a>
                        <a class="collapse-item {{ $request->segment(2) == 'job-cards' ? 'active' : '' }}" href="{{ url('/tailoring/job-cards') }}">Job Cards</a>
                        <a class="collapse-item {{ $request->segment(2) == 'production' ? 'active' : '' }}" href="{{ url('/tailoring/production') }}">Production</a>
                        <a class="collapse-item {{ $request->segment(2) == 'quality-control' ? 'active' : '' }}" href="{{ url('/tailoring/quality-control') }}">Quality Control</a>
                        <a class="collapse-item {{ $request->segment(2) == 'delivery' ? 'active' : '' }}" href="{{ url('/tailoring/delivery') }}">Delivery</a>
                        <a class="collapse-item {{ $request->segment(2) == 'billing' ? 'active' : '' }}" href="{{ url('/tailoring/billing') }}">Billing</a>
                        <a class="collapse-item {{ $request->segment(2) == 'reports' ? 'active' : '' }}" href="{{ url('/tailoring/reports') }}">Reports</a>
                        <a class="collapse-item {{ $request->segment(2) == 'settings' ? 'active' : '' }}" href="{{ url('/tailoring/settings') }}">Settings</a>
                    </div>
                </div>
            </li>
        @endif

        {{-- Standalone POS module menu: visible from package/module status without depending on old sale POS links. --}}
        @php
            $__pos_sidebar_enabled = false;
            try {
                $__pos_package_details = [];
                if (function_exists('session') && session()->has('business.package_details')) {
                    $__pos_package_details = session('business.package_details');
                } elseif (function_exists('session') && session()->has('package_details')) {
                    $__pos_package_details = session('package_details');
                }

                if (is_string($__pos_package_details)) {
                    $__pos_package_details = json_decode($__pos_package_details, true) ?: [];
                }

                foreach ([
                    'pos_module', 'standalone_pos_module', 'pos', 'POS', 'pos2_module', 'pos_2_module'
                ] as $__pos_key) {
                    if (!empty($__pos_package_details[$__pos_key]) || !empty($module_array[$__pos_key])) {
                        $__pos_sidebar_enabled = true;
                        break;
                    }
                }

                $__pos_sidebar_enabled = $__pos_sidebar_enabled
                    || !empty($pos_module ?? null)
                    || !empty($standalone_pos_module ?? null)
                    || !empty($pos ?? null)
                    || auth()->user()->can('superadmin')
                    || auth()->user()->can('pos.view')
                    || auth()->user()->can('pos.create')
                    || auth()->user()->can('pos.settings')
                    || auth()->user()->can('pos.reports');
            } catch (\Throwable $e) {
                $__pos_sidebar_enabled = !empty($pos_module ?? null)
                    || !empty($standalone_pos_module ?? null)
                    || auth()->user()->can('superadmin')
                    || auth()->user()->can('pos.view');
            }
        @endphp

        @if (!empty($__pos_sidebar_enabled))
            <li class="nav-item {{ in_array($request->segment(1), ['pos-module', 'pos']) ? 'active active-sub' : '' }}" id="pos-standalone-sidebar-menu-v1">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#pos-standalone-menu-v1"
                    aria-expanded="{{ in_array($request->segment(1), ['pos-module', 'pos']) ? 'true' : 'false' }}" aria-controls="pos-standalone-menu-v1">
                    <i class="fa fa-cash-register"></i>
                    <span>POS</span>
                </a>
                <div id="pos-standalone-menu-v1"
                    class="collapse {{ in_array($request->segment(1), ['pos-module', 'pos']) ? 'show' : '' }}"
                    aria-labelledby="headingPages" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">POS:</h6>
                        <a class="collapse-item {{ $request->segment(1) == 'pos-module' && empty($request->segment(2)) ? 'active' : '' }}" href="{{ url('/pos-module') }}">Dashboard</a>
                        <a class="collapse-item {{ $request->segment(2) == 'registers' ? 'active' : '' }}" href="{{ url('/pos-module/registers') }}">Registers</a>
                        <a class="collapse-item {{ $request->segment(2) == 'sales' ? 'active' : '' }}" href="{{ url('/pos-module/sales') }}">Sales</a>
                        <a class="collapse-item {{ $request->segment(2) == 'holds' ? 'active' : '' }}" href="{{ url('/pos-module/holds') }}">Holds</a>
                        <a class="collapse-item {{ $request->segment(1) == 'pos-module' && $request->segment(2) == 'advanced-sales' ? 'active' : '' }}" href="{{ url('/pos-module/advanced-sales') }}">Advanced Sales</a>
                        <a class="collapse-item {{ $request->segment(1) == 'pos-module' && $request->segment(2) == 'returns' ? 'active' : '' }}" href="{{ url('/pos-module/returns') }}">Returns</a>
                        <a class="collapse-item {{ $request->segment(1) == 'pos-module' && $request->segment(2) == 'shifts' ? 'active' : '' }}" href="{{ url('/pos-module/shifts') }}">Shifts</a>
                        <a class="collapse-item {{ $request->segment(1) == 'pos-module' && $request->segment(2) == 'kitchen' ? 'active' : '' }}" href="{{ url('/pos-module/kitchen') }}">Kitchen Display</a>
                        <a class="collapse-item {{ $request->segment(2) == 'settings' ? 'active' : '' }}" href="{{ url('/pos-module/settings') }}">Settings</a>
                        <a class="collapse-item {{ $request->segment(2) == 'reports' ? 'active' : '' }}" href="{{ url('/pos-module/reports') }}">Reports</a>
                    </div>
                </div>
            </li>
        @endif

        @if ($stock_transfer)
            @if (auth()->user()->can('purchase.view') || auth()->user()->can('purchase.create'))
                <li
                    class="nav-item {{ $request->segment(1) == 'stock-transfers' || $request->segment(1) == 'stock-transfers-request' ? 'active active-sub' : '' }}">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse"
                        data-target="#stocktransfer-menu" aria-expanded="true" aria-controls="stocktransfer-menu">
                        <i class="fa fa-truck"></i>
                        <span>@lang('lang_v1.stock_transfers')</span>
                    </a>
                    <div id="stocktransfer-menu" class="collapse" aria-labelledby="headingPages"
                        data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">@lang('lang_v1.stock_transfers'):</h6>
                            @can('purchase.view')
                                <a class="collapse-item {{ $request->segment(1) == 'stock-transfers' && $request->segment(2) == null ? 'active' : '' }}"
                                    href="/stock-transfers">@lang('lang_v1.list_stock_transfers')</a>
                                @endcan @can('purchase.create')
                                <a class="collapse-item {{ $request->segment(1) == 'stock-transfers' && $request->segment(2) == 'create' ? 'active' : '' }}"
                                    href="/stock-transfers/create">@lang('lang_v1.add_stock_transfer')</a>
                            @endcan {{-- @can('purchase.create') --}}
                            <a class="collapse-item {{ $request->segment(1) == 'stock-transfers-request' && $request->segment(2) == null ? 'active' : '' }}"
                                href="/stock-transfers-request">@lang('lang_v1.stock_transfer_request')</a>
                            {{-- @endcan --}}
                        </div>
                    </div>
                </li>
            @endif
        @endif

        @if ($stock_adjustment)
            @can('stock_adjustment')


                {{-- @if (in_array('stock_adjustment', $enabled_modules)) --}}
                {{-- @if (auth()->user()->can('purchase.view') || auth()->user()->can('purchase.create')) --}}
                <li class="nav-item {{ $request->segment(1) == 'stock-adjustments' ? 'active active-sub' : '' }}">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse"
                        data-target="#stockadjustments-menu" aria-expanded="true" aria-controls="stockadjustments-menu">
                        <i class="fa fa-database"></i>
                        <span>@lang('stock_adjustment.stock_adjustment')</span>
                    </a>
                    <div id="stockadjustments-menu" class="collapse" aria-labelledby="headingPages"
                        data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">@lang('stock_adjustment.stock_adjustment'):</h6>
                            @can('purchase.view')
                                <a class="collapse-item {{ $request->segment(1) == 'stock-adjustments' && $request->segment(2) == null ? 'active' : '' }}"
                                    href="/stock-adjustments">@lang('stock_adjustment.list')</a>
                                @endcan @can('purchase.create')
                                <a class="collapse-item {{ $request->segment(1) == 'stock-adjustments' && $request->segment(2) == 'create' ? 'active' : '' }}"
                                    href="/stock-adjustments/create">@lang('stock_adjustment.add')</a>
                            @endcan

                            <a class="collapse-item {{ $request->segment(1) == 'stock-settings' && $request->segment(2) == null ? 'active' : '' }}"
                                href="{{ action('StockAdjustmentSettings@create') }}">@lang('stock_adjustment_settings.list')</a>
                        </div>
                    </div>
                </li>
                {{-- @endif --}}
                {{-- @endif --}}
            @endcan
        @endif

        @if ($expenses)
            @if (auth()->user()->can('expense.access'))
                <li
                    class="nav-item {{ in_array($request->segment(1), ['expense-categories', 'expenses']) ? 'active active-sub' : '' }}">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#expenses-menu"
                        aria-expanded="true" aria-controls="expenses-menu">
                        <i class="fa fa-money"></i>
                        <span>@lang('expense.expenses')</span>
                    </a>
                    <div id="expenses-menu" class="collapse" aria-labelledby="headingPages"
                        data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">@lang('expense.expenses'):</h6>
                            <a class="collapse-item {{ $request->segment(1) == 'expenses' && empty($request->segment(2)) ? 'active' : '' }}"
                                href="/expenses">@lang('lang_v1.list_expenses')</a>
                            <a class="collapse-item {{ $request->segment(1) == 'expenses' && $request->segment(2) == 'create' ? 'active' : '' }}"
                                href="/expenses/create">@lang('messages.add')
                                @lang('expense.expenses')</a>

                            <a class="collapse-item {{ $request->segment(1) == 'expense-categories' ? 'active' : '' }}"
                                href="/expense-categories">@lang('expense.expense_categories')</a>

                            <a class="collapse-item {{ $request->segment(1) == 'expense-categories-code' ? 'active' : '' }}"
                                href="{{ route('expense-categories-code.index') }}">@lang('expense.expense_categories_code')</a>
                        </div>
                    </div>
                </li>
            @endif
        @endif

        <!-- Start PayRoll Module -->
        @if ($payday)
            @if (auth()->user()->can('payday') && !auth()->user()->is_pump_operator && !auth()->user()->is_property_user)
                <li class="nav-item">
                    <a class="nav-link" href="#" id="login_payroll">
                        <i class="fa fa-briefcase"></i>
                        <span>PayRoll</span></a>
                </li>
            @endif
        @endif
        <!-- End PayRoll Module -->

        <!-- Start Management Report Standalone Module -->
        @if (!empty($management_report_module) && !auth()->user()->is_pump_operator && !auth()->user()->is_property_user)
            <li class="nav-item {{ $request->segment(1) === 'management-report' ? 'active active-sub' : '' }}">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#management-report-menu"
                    aria-expanded="{{ $request->segment(1) === 'management-report' ? 'true' : 'false' }}"
                    aria-controls="management-report-menu">
                    <i class="fa fa-line-chart"></i>
                    <span>Management Report</span>
                </a>
                <div id="management-report-menu"
                    class="collapse {{ $request->segment(1) === 'management-report' ? 'show' : '' }}"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Management Report:</h6>
                        <a class="collapse-item {{ $request->segment(1) === 'management-report' && empty($request->segment(2)) ? 'active active-sub' : '' }}"
                            href="{{ url('/management-report') }}">Dashboard</a>
                        <a class="collapse-item {{ $request->segment(2) === 'daily' ? 'active active-sub' : '' }}"
                            href="{{ url('/management-report/daily') }}">Daily Management Report</a>
                        <a class="collapse-item {{ $request->segment(2) === 'saved' ? 'active active-sub' : '' }}"
                            href="{{ url('/management-report/saved') }}">Saved Reports</a>
                        <a class="collapse-item {{ $request->segment(2) === 'delivery-history' ? 'active active-sub' : '' }}"
                            href="{{ url('/management-report/delivery-history') }}">Delivery History</a>
                        <a class="collapse-item {{ $request->segment(2) === 'settings' ? 'active active-sub' : '' }}"
                            href="{{ url('/management-report/settings') }}">Settings</a>
                    </div>
                </div>
            </li>
        @endif
        <!-- End Management Report Standalone Module -->

        <!-- Start HR Manager Standalone Module -->
        @if (!empty($hr_manager_module) && !auth()->user()->is_pump_operator && !auth()->user()->is_property_user)
            <li class="nav-item {{ in_array($request->segment(1), ['hr-manager', 'hr']) ? 'active active-sub' : '' }}">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#hr-manager-menu"
                    aria-expanded="{{ in_array($request->segment(1), ['hr-manager', 'hr']) ? 'true' : 'false' }}"
                    aria-controls="hr-manager-menu">
                    <i class="fa fa-users"></i>
                    <span>HR Manager</span>
                </a>
                <div id="hr-manager-menu"
                    class="collapse {{ in_array($request->segment(1), ['hr-manager', 'hr']) ? 'show' : '' }}"
                    aria-labelledby="headingPages" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">HR Manager:</h6>

                        <a class="collapse-item {{ in_array($request->path(), ['hr-manager/dashboard', 'hr/dashboard']) || $request->segment(2) == 'dashboard' ? 'active active-sub' : '' }}"
                            href="{{ url('/hr-manager/dashboard') }}">Dashboard</a>

                        <a class="collapse-item {{ in_array($request->segment(2), ['employees', 'employee']) ? 'active active-sub' : '' }}"
                            href="{{ url('/hr-manager/employees') }}">Employees</a>

                        <a class="collapse-item {{ $request->segment(2) == 'setup' ? 'active active-sub' : '' }}"
                            href="{{ url('/hr-manager/setup') }}">HR Setup</a>

                        <a class="collapse-item {{ $request->segment(2) == 'attendance' ? 'active active-sub' : '' }}"
                            href="{{ url('/hr-manager/attendance') }}">Attendance</a>

                        <a class="collapse-item {{ in_array($request->segment(2), ['face', 'face-attendance']) ? 'active active-sub' : '' }}"
                            href="{{ url('/hr-manager/face-attendance') }}">Face Attendance</a>

                        <a class="collapse-item {{ in_array($request->segment(2), ['leave', 'leave-management']) ? 'active active-sub' : '' }}"
                            href="{{ url('/hr-manager/leave') }}">Leave Management</a>

                        <a class="collapse-item {{ $request->segment(2) == 'payroll' ? 'active active-sub' : '' }}"
                            href="{{ url('/hr-manager/payroll') }}">Payroll</a>

                        <a class="collapse-item {{ in_array($request->segment(2), ['employee-records', 'records']) ? 'active active-sub' : '' }}"
                            href="{{ url('/hr-manager/employee-records') }}">Employee Records</a>
                    </div>
                </div>
            </li>
        @endif
        <!-- End HR Manager Standalone Module -->

        @if ($loan_module)
            @include('loan::layouts.nav')
        @endif

        <!-- End Task Management Module -->
        @if ($banking_module == 1 || $access_account == 1 || auth()->user()->can('account.access'))
                <li class="nav-item {{ $request->segment(1) == 'accounting-module' ? 'active active-sub' : '' }}">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#accounting-menu"
                        aria-expanded="true" aria-controls="accounting-menu">
                        <i class="fa fa-money"></i>
                        <span>Finance Module</span>
                    </a>
                    <div id="accounting-menu" class="collapse" aria-labelledby="headingPages"
                        data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">Finance Module:</h6>

                            <a class="collapse-item {{ $request->segment(1) == 'accounting-module' && $request->segment(2) == 'account' ? 'active' : '' }}"
                                href="/accounting-module/account">@lang('account.list_accounts')</a>

                            <a class="collapse-item {{ $request->segment(1) == 'accounting-module' && $request->segment(2) == 'disabled-account' ? 'active' : '' }}"
                                href="/accounting-module/disabled-account">@lang('account.disabled_account')</a>

                            <a class="collapse-item {{ $request->segment(1) == 'accounting-module' && $request->segment(2) == 'journals' ? 'active' : '' }}"
                                href="/accounting-module/journal">@lang('account.list_journals')</a>

                            <a class="collapse-item {{ $request->segment(1) == 'accounting-module' && $request->segment(2) == 'get-profit-loss-report' ? 'active' : '' }}"
                                href="/accounting-module/get-profit-loss-report">@lang('lang_v1.profit_loss_report')</a>

                            <a class="collapse-item {{ $request->segment(1) == 'accounting-module' && $request->segment(2) == 'income-statement' ? 'active' : '' }}"
                                href="/accounting-module/income-statement">@lang('account.income_statement')</a>

                            <a class="collapse-item {{ $request->segment(1) == 'accounting-module' && $request->segment(2) == 'balance-sheet' ? 'active' : '' }}"
                                href="/accounting-module/balance-sheet">@lang('account.balance_sheet')</a>

                            <a class="collapse-item {{ $request->segment(1) == 'accounting-module' && $request->segment(2) == 'balance-sheet-comparison' ? 'active' : '' }}"
                                href="/accounting-module/balance-sheet-comparison">@lang('account.balance_sheet_comparison')</a>

                            <a class="collapse-item {{ $request->segment(1) == 'accounting-module' && $request->segment(2) == 'fixed-asset' ? 'active' : '' }}"
                                href="/accounting-module/fixed-asset">@lang('account.fixed_assets')</a>

                            <a class="collapse-item {{ $request->segment(1) == 'accounting-module' && $request->segment(2) == 'trial-balance' ? 'active' : '' }}"
                                href="/accounting-module/trial-balance">@lang('account.trial_balance')</a>

                            <a class="collapse-item {{ $request->segment(1) == 'accounting-module' && $request->segment(2) == 'trial-balance-cumulative' ? 'active' : '' }}"
                                href="/accounting-module/trial-balance-cumulative">@lang('account.trial_balance_cumulative')</a>

                            <a class="collapse-item {{ $request->segment(1) == 'accounting-module' && $request->segment(2) == 'cash-flow' ? 'active' : '' }}"
                                href="/accounting-module/cash-flow">@lang('lang_v1.cash_flow')</a>

                            <a class="collapse-item {{ $request->segment(1) == 'accounting-module' && $request->segment(2) == 'payment-account-report' ? 'active' : '' }}"
                                href="/accounting-module/payment-account-report">@lang('account.payment_account_report')</a>

                            <a class="collapse-item {{ $request->segment(1) == 'accounting-module' && $request->segment(2) == 'import' ? 'active' : '' }}"
                                href="/accounting-module/import">@lang('lang_v1.import_accounts')</a>

                        </div>
                    </div>
                </li>

                @if ($post_dated_cheque)
                    <li class="nav-item {{ $request->segment(2) == 'post-dated-cheques' ? 'active active-sub' : '' }}">
                        <a class="nav-link collapsed" href="#" data-toggle="collapse"
                            data-target="#post-dated-cheques-menu" aria-expanded="true"
                            aria-controls="post-dated-cheques-menu">
                            <i class="fa fa-money"></i>
                            <span>
                                @lang('account.pd_cheques_management')

                            </span>
                        </a>
                        <div id="post-dated-cheques-menu"
                            class="collapse {{ $request->segment(2) == 'post-dated-cheques' ? 'show' : '' }}"
                            aria-labelledby="headingPages" data-parent="#accordionSidebar">
                            <div class="bg-white py-2 collapse-inner rounded">
                                <h6 class="collapse-header">
                                    @lang('account.pd_cheques_management')
                                </h6>
                                <a class="collapse-item {{ $request->segment(1) == 'accounting-module' && $request->segment(2) == 'postdated-cheques' ? 'active' : '' }}"
                                    href="/accounting-module/post-dated-cheques/create">@lang('account.add_pd_cheques')</a>
                                <a class="collapse-item {{ $request->segment(1) == 'accounting-module' && $request->segment(2) == 'postdated-cheques' ? 'active' : '' }}"
                                    href="/accounting-module/post-dated-cheques">@lang('account.post_dated_cheque')</a>

                            </div>
                        </div>
                    </li>
                @endif
            @endcan
        @endif

        @if ($deposits_module || $ns_deposits_module)
            @can('deposits_module')
                <li
                    class="nav-item {{ in_array($request->segment(1), ['deposits-module']) ? 'active active-sub' : '' }}">
                    <a class="nav-link" href="/deposits-module/account">
                        <i class="fa fa-money"></i>
                        <span>@lang('deposits.deposits_module')</span></a>
                </li>
            @endcan
        @endif

        @if ($realize_cheque)
            @can('realize_cheque.access')
                <li
                    class="nav-item {{ in_array($request->segment(1), ['accounting-module']) ? 'active active-sub' : '' }}">
                    <a class="nav-link" href="/accounting-module/realized-cheques">
                        <i class="fa fa-money"></i>
                        <span>@lang('account.list_realize_cheque')</span></a>
                </li>
            @endcan
        @endif

        @if ($docmanagement_module)
            @can('docmanagement_module.access')
                @includeIf('docmanagement::layouts.partials.sidebar')
            @endcan
        @endif
        @includeIf('salesdiscounts::layouts.partials.sidebar')
        @if ($asset_module || $ns_asset_management)
            @can('asset_module.access')
                @includeIf('assetmanagement::layouts.nav')
            @endcan
        @endif

        @if (!empty($vat_module) || !empty($vat_module_main) || !empty($ns_vat_module))
            @can('vat_module.access')
                @includeIf('vat::layouts_v2.partials.sidebar')
            @endcan
        @endif



        @if (!empty($leads_new_module))
            <li class="nav-item {{ $request->segment(1) == 'leads-new' ? 'active active-sub' : '' }}">
                <a class="nav-link collapsed" href="#" data-toggle="collapse"
                    data-target="#leads-new-menu" aria-expanded="true" aria-controls="leads-new-menu">
                    <i class="fa fa-user-plus"></i>
                    <span>Leads-New</span>
                </a>
                <div id="leads-new-menu" class="collapse {{ $request->segment(1) == 'leads-new' ? 'show' : '' }}"
                    aria-labelledby="headingPages" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Leads-New:</h6>
                        <a class="collapse-item {{ $request->segment(1) == 'leads-new' && empty($request->segment(2)) ? 'active' : '' }}" href="{{ url('/leads-new') }}">Dashboard</a>
                        <a class="collapse-item {{ $request->segment(2) == 'leads' && $request->segment(3) != 'create' ? 'active' : '' }}" href="{{ url('/leads-new/leads') }}">List Leads</a>
                        <a class="collapse-item {{ $request->segment(2) == 'leads' && $request->segment(3) == 'create' ? 'active' : '' }}" href="{{ url('/leads-new/leads/create') }}">Add Lead</a>
                        <a class="collapse-item {{ $request->segment(2) == 'reports' ? 'active' : '' }}" href="{{ url('/leads-new/reports') }}">Reports</a>
                        <a class="collapse-item {{ $request->segment(2) == 'settings' ? 'active' : '' }}" href="{{ url('/leads-new/settings') }}">Settings</a>
                    </div>
                </div>
            </li>
        @endif

        @if ($report_module)
            @if (auth()->user()->can('report.access'))

                <li class="nav-item {{ in_array($request->segment(1), ['reports']) ? 'active active-sub' : '' }}">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#reports-menu"
                        aria-expanded="true" aria-controls="reports-menu">
                        <i class="fa fa-bar-chart"></i>
                        <span>@lang('report.reports')</span>
                    </a>
                    <div id="reports-menu"
                        class="collapse {{ in_array($request->segment(1), ['reports']) ? 'show' : '' }}"
                        aria-labelledby="headingPages" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">@lang('report.reports'):</h6>
                            @if ($product_report)
                                @if (auth()->user()->can('stock_report.view') ||
                                        auth()->user()->can('stock_adjustment_report.view') ||
                                        auth()->user()->can('item_report.view') ||
                                        auth()->user()->can('product_purchase_report.view') ||
                                        auth()->user()->can('product_sell_report.view') ||
                                        auth()->user()->can('product_transaction_report.view'))
                                    <a class="collapse-item {{ $request->segment(2) == 'product' ? 'active' : '' }}"
                                        href="/reports/product">@lang('report.product_report')</a>
                                @endif
                            @endif
                            @if ($payment_status_report)
                                @if (auth()->user()->can('purchase_payment_report.view') ||
                                        auth()->user()->can('sell_payment_report.view') ||
                                        auth()->user()->can('outstanding_received_report.view') ||
                                        auth()->user()->can('aging_report.view'))
                                    <a class="collapse-item {{ $request->segment(2) == 'payment-status' ? 'active' : '' }}"
                                        href="/reports/payment-status">@lang('report.payment_status_report')</a>
                                    @endif @endif @if (auth()->user()->can('daily_report.view') ||
                                            auth()->user()->can('daily_summary_report.view') ||
                                            auth()->user()->can('register_report.view') ||
                                            auth()->user()->can('profit_loss_report.view'))
                                        <a class="collapse-item {{ $request->segment(2) == 'management' ? 'active' : '' }}"
                                            href="/reports/management">@lang('report.management_report')</a>
                                        @endif @if ($verification_report || $report_verification)
                                            <a class="collapse-item {{ $request->segment(2) == 'verification' ? 'active' : '' }}"
                                                href="/reports/verification">@lang('report.verification_reports')</a>
                                            @endif @if ($activity_report)
                                                @if (auth()->user()->can('sales_report.view') ||
                                                        auth()->user()->can('purchase_and_slae_report.view') ||
                                                        auth()->user()->can('expense_report.view') ||
                                                        auth()->user()->can('sales_representative.view') ||
                                                        auth()->user()->can('tax_report.view'))
                                                    <a class="collapse-item {{ $request->segment(2) == 'activity' ? 'active' : '' }}"
                                                        href="/reports/activity">@lang('report.activity_report')</a>
                                                @endif
                                            @endif
                                            @can('stock_report.view')
                                                @if (session('business.enable_product_expiry') == 1)
                                                    <a class="collapse-item {{ $request->segment(2) == 'stock-expiry' ? 'active' : '' }}"
                                                        href="/reports/stock-expiry">@lang('report.stock_expiry_report')</a>
                                                @endif
                                            @endcan
                                            @can('stock_report.view')
                                                @if (session('business.enable_lot_number') == 1)
                                                    <a class="collapse-item {{ $request->segment(2) == 'lot-report' ? 'active' : '' }}"
                                                        href="/reports/lot-report">@lang('lang_v1.lot_report')</a>
                                                @endif
                                            @endcan
                                            @if ($trending_product)
                                                @can('trending_products.view')
                                                    <a class="collapse-item {{ $request->segment(2) == 'trending-products' ? 'active' : '' }}"
                                                        href="/reports/trending-products">@lang('report.trending_products')</a>
                                                @endcan
                                            @endif
                                            @if ($user_activity)
                                                @can('user_activity.view')
                                                    <a class="collapse-item {{ $request->segment(2) == 'user_activity' ? 'active' : '' }}"
                                                        href="/reports/user_activity">@lang('report.user_activity')</a>
                                                @endcan
                                            @endif
                                            @if ($report_table)
                                                @can('report_table.view')
                                                    <a class="collapse-item {{ $request->segment(2) == 'table-report' ? 'active' : '' }}"
                                                        href="/reports/table-report">@lang('restaurant.table_report')</a>
                                                @endcan
                                            @endif
                                            @if ($report_staff_service)
                                                @can('sales_representative.view')
                                                    <a class="collapse-item {{ $request->segment(2) == 'service-staff-report' ? 'active' : '' }}"
                                                        href="/reports/service-staff-report">@lang('restaurant.service_staff_report')</a>
                                                @endcan
                                            @endif

                                            @if ($contact_report)
                                                @can('contact_report.view')
                                                    <a class="collapse-item {{ $request->segment(2) == 'contact' ? 'active' : '' }}"
                                                        href="/reports/contact">@lang('report.contact_report')</a>
                                                @endcan
                                            @endif

                        </div>
                    </div>
                </li>

            @endif
        @endif


        @if ($report_module)
            @if (auth()->user()->can('report.access') && $customized_report)
                <li
                    class="nav-item {{ in_array($request->segment(1), ['customized_report', '129report']) ? 'active active-sub' : '' }}">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse"
                        data-target="#customized_reports-menu" aria-expanded="true"
                        aria-controls="customized_reports-menu">
                        <i class="fa fa-bar-chart"></i>
                        <span>@lang('lang_v1.customized_report')</span>
                    </a>
                    <div id="customized_reports-menu"
                        class="collapse  {{ in_array($request->segment(1), ['customized_report', '129report']) ? 'show' : '' }}"
                        aria-labelledby="headingPages" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">@lang('lang_v1.customized_report'):</h6>

                            <a class="collapse-item {{ $request->segment(2) == '129report' ? 'active' : '' }}"
                                href="/customized_reports">@lang('lang_v1.129_reports')</a>
                        </div>
                    </div>
                </li>
                @endif @endif @if ($catalogue_qr)
                    @if (auth()->user()->can('catalogue.access'))
                        <li
                            class="nav-item {{ in_array($request->segment(1), ['backup']) ? 'active active-sub' : '' }}">
                            <a class="nav-link"
                                href="{{ action('\Modules\ProductCatalogue\Http\Controllers\ProductCatalogueController@generateQr') }}">
                                <i class="fa fa-qrcode"></i>
                                <span>@lang('lang_v1.catalogue_qr')</span></a>
                        </li>
                        @endif @endif @if ($backup_module)
                            @can('backup')
                                <li
                                    class="nav-item {{ in_array($request->segment(1), ['backup']) ? 'active active-sub' : '' }}">
                                    <a class="nav-link" href="{{ action('BackUpController@index') }}">
                                        <i class="fa fa-cloud-download"></i>
                                        <span>@lang('lang_v1.backup')</span></a>
                                </li>
                            @endcan
                        @endif
                        <!-- Call restaurant module if defined -->
                        @if ($enable_booking)
                            <!-- check if module in subscription -->
                            @if (auth()->user()->can('crud_all_bookings') || auth()->user()->can('crud_own_bookings'))
                                <li
                                    class="nav-item {{ $request->segment(1) == 'bookings' ? 'active active-sub' : '' }}">
                                    <a class="nav-link" href="/bookings">
                                        <i class="fa fa-calendar-check-o"></i>
                                        <span>@lang('restaurant.bookings')</span></a>
                                </li>
                                @endif @endif @if ($kitchen)
                                    @can('kitchen.access')
                                        <li
                                            class="nav-item {{ $request->segment(1) == 'modules' && $request->segment(2) == 'kitchen' ? 'active active-sub' : '' }}">
                                            <a class="nav-link"
                                                href="/kitchen">
                                                <i class="fa fa-coffee"></i>
                                                <span>@lang('restaurant.kitchen')</span></a>
                                        </li>
                                    @endcan

                                    @endif @if ($orders)
                                        @can('orders.access')
                                            <li
                                                class="nav-item {{ $request->segment(1) == 'modules' && $request->segment(2) == 'orders' ? 'active active-sub' : '' }}">
                                                <a class="nav-link"
                                                    href="/orders">
                                                    <i class="fa fa-clone"></i>
                                                    <span>@lang('restaurant.orders')</span></a>
                                            </li>
                                        @endcan
                                        @endif @if ($notification_template_module)
                                            @can('send_notification')


                                                <li
                                                    class="nav-item {{ $request->segment(1) == 'notification-template' ? 'active active-sub' : '' }}">
                                                    <a class="nav-link collapsed" href="#" data-toggle="collapse"
                                                        data-target="#notification-template" aria-expanded="true"
                                                        aria-controls="notification-template">
                                                        <i class="fa fa-envelope"></i>
                                                        <span>@lang('lang_v1.notification_templates')</span>
                                                    </a>
                                                    <div id="notification-template" class="collapse"
                                                        aria-labelledby="headingPages" data-parent="#accordionSidebar">
                                                        <div class="bg-white py-2 collapse-inner rounded">
                                                            <h6 class="collapse-header">
                                                                @lang('lang_v1.notification_templates'):
                                                            </h6>
                                                            <a class="collapse-item {{ $request->segment(1) == 'notification-template' && $request->segment(2) == 'email' ? 'active' : '' }}"
                                                                href="{{ url('notification-templates') }}?type=email">@lang('lang_v1.email')</a>
                                                            <a class="collapse-item {{ $request->segment(1) == 'notification-template' && $request->segment(2) == 'sms' ? 'active' : '' }}"
                                                                href="{{ url('notification-templates') }}?type=sms">@lang('lang_v1.sms')
                                                                &
                                                                @lang('lang_v1.whatsapp')</a>

                                                            <a class="collapse-item {{ $request->segment(1) == 'notification-template' && $request->segment(2) == 'sms' ? 'active' : '' }}"
                                                                href="{{ url('notification-templates') }}?type=sms&category=purchase">@lang('lang_v1.purchase_sms')
                                                            </a>

                                                            <a class="collapse-item {{ $request->segment(1) == 'notification-template' && $request->segment(2) == 'sms' ? 'active' : '' }}"
                                                                href="{{ url('notification-templates') }}?type=sms&category=expense">@lang('lang_v1.expense_sms')
                                                            </a>

                                                            <a class="collapse-item {{ $request->segment(1) == 'notification-template' && $request->segment(2) == 'sms' ? 'active' : '' }}"
                                                                href="{{ url('notification-templates') }}?type=sms&category=accounting">@lang('lang_v1.accounting_sms')
                                                            </a>

                                                        </div>
                                                    </div>
                                                </li>
                                            @endif
                                        @endif


                                        @if ($ns_discount_module || $discount_module)
                                            @can('discount_module.access')
                                                <!-- BEGIN: Discount module -->
                                                <li
                                                    class="nav-item {{ $request->segment(1) == 'discount-template' ? 'active active-sub' : '' }}">
                                                    <a class="nav-link collapsed" href="#" data-toggle="collapse"
                                                        data-target="#discount-template" aria-expanded="true"
                                                        aria-controls="discount-template">
                                                        <i class="fa fa-percent"></i>
                                                        <span>@lang('lang_v1.discount_templates')</span>
                                                    </a>
                                                    <div id="discount-template"
                                                        class="collapse {{ $request->segment(1) == 'discount-template' ? 'show' : '' }}"
                                                        aria-labelledby="headingPages" data-parent="#accordionSidebar">
                                                        <div class="bg-white py-2 collapse-inner rounded">
                                                            <h6 class="collapse-header">
                                                                @lang('lang_v1.discount_templates')
                                                            </h6>
                                                            <a class="collapse-item"
                                                                href="{{ url('discount-templates') }}">@lang('lang_v1.discount_levels')</a>
                                                            <a class="collapse-item"
                                                                href="{{ url('list-discounts') }}">@lang('lang_v1.list_discounts')</a>
                                                        </div>
                                                    </div>
                                                </li>
                                                <!-- END: DISCOUNT Module -->
                                            @endcan
                                        @endif

                                        @php $business_or_entity = App\System::getProperty('business_or_entity'); @endphp
                                        @if (!$disable_all_other_module_vr)
                                            @if (!auth()->user()->is_pump_operator)
                                                @can('disable_all_other_module_vr.access')
                                                    <li
                                                        class="nav-item @if (in_array($request->segment(1), ['invoice-schemes']) || in_array($request->segment(2), ['tables', 'modifiers'])) {{ 'active active-sub' }} @endif">
                                                        <a class="nav-link collapsed" href="#" data-toggle="collapse"
                                                            data-target="#settings-menu" aria-expanded="true"
                                                            aria-controls="settings-menu">
                                                            <i class="fa fa-cogs"></i>
                                                            <span>@lang('business.settings')</span>
                                                        </a>
                                                        <div id="settings-menu"
                                                            class="collapse @if (in_array($request->segment(1), [
                                                                    'pay-online',
                                                                    'stores',
                                                                    'business',
                                                                    'tax-rates',
                                                                    'barcodes',
                                                                    'invoice-schemes',
                                                                    'business-location',
                                                                    'invoice-layouts',
                                                                    'printers',
                                                                    'subscription',
                                                                    'types-of-service',
                                                                ]) || in_array($request->segment(2), ['tables', 'modifiers'])) {{ 'show' }} @endif"
                                                            aria-labelledby="headingPages" data-parent="#accordionSidebar">
                                                            <div class="bg-white py-2 collapse-inner rounded">
                                                                <h6 class="collapse-header">
                                                                    @lang('business.settings'):
                                                                </h6>
                                                                @if ($settings_module)
                                                                    @can('business_settings.access')
                                                                        @if ($business_settings)
                                                                            <a class="collapse-item {{ $request->segment(1) == 'business' ? 'active' : '' }}"
                                                                                href="{{ url('/business/settings') }}">
                                                                                @if ($business_or_entity == 'business')
                                                                                    {{ __('business.business_settings') }}
                                                                                @elseif($business_or_entity == 'entity')
                                                                                    {{ __('lang_v1.entity_settings') }}
                                                                                @else
                                                                                    {{ __('business.business_settings') }}
                                                                                @endif
                                                                            </a>
                                                                        @endif
                                                                        @if ($business_location)
                                                                            <a class="collapse-item {{ $request->segment(1) == 'business-location' ? 'active' : '' }}"
                                                                                href="{{ url('/business-location') }}">
                                                                                @if ($business_or_entity == 'business')
                                                                                    {{ __('business.business_locations') }}
                                                                                @elseif($business_or_entity == 'entity')
                                                                                    {{ __('lang_v1.entity_locations') }}
                                                                                @else
                                                                                    {{ __('business.business_locations') }}
                                                                                @endif
                                                                            </a>
                                                                        @endif
                                                                        <a class="collapse-item {{ $request->segment(1) == 'stores' ? 'active' : '' }}"
                                                                            href="/stores">@lang('business.stores_settings')</a>
                                                                        <a class="collapse-item {{ $request->segment(1) == 'stores' ? 'active' : '' }}"
                                                                            href="/store-permissions">@lang('store.store_permissions')</a>
                                                                    @endcan
                                                                    @can('invoice_settings.access')
                                                                        @if ($invoice_settings)
                                                                            <a class="collapse-item @if (in_array($request->segment(1), ['invoice-schemes', 'invoice-layouts'])) {{ 'active' }} @endif"
                                                                                href="{{ action('InvoiceSchemeController@index') }}">@lang('invoice.invoice_settings')</a>
                                                                        @endif
                                                                    @endcan
                                                                    @can('barcode_settings.access')
                                                                        <a class="collapse-item {{ $request->segment(1) == 'barcodes' ? 'active' : '' }}"
                                                                            href="{{ action('BarcodeController@index') }}">@lang('barcode.barcode_settings')</a>
                                                                    @endcan
                                                                    <a class="collapse-item {{ $request->segment(1) == 'printers' ? 'active' : '' }}"
                                                                        href="{{ action('PrinterController@index') }}">@lang('printer.receipt_printers')</a>
                                                                    @if (auth()->user()->can('tax_rate.view') || auth()->user()->can('tax_rate.create'))
                                                                        @if ($tax_rates)
                                                                            <a class="collapse-item {{ $request->segment(1) == 'tax-rates' ? 'active' : '' }}"
                                                                                href="/tax-rates">@lang('tax_rate.tax_rates')</a>
                                                                        @endif
                                                                    @endif
                                                                    @if ($customer_settings)
                                                                        @if (auth()->user()->can('customer_settings.access'))
                                                                            <a class="collapse-item {{ $request->segment(1) == 'customer-settings' ? 'active' : '' }}"
                                                                                href="{{ action('CustomerSettingsController@index') }}">@lang('lang_v1.customer_settings')></a>
                                                                        @endif
                                                                        @can('business_settings.access')
                                                                            <a class="collapse-item {{ $request->segment(1) == 'modules' && $request->segment(2) == 'tables' ? 'active' : '' }}"
                                                                                href="/tables">@lang('restaurant.tables')</a>
                                                                        @endcan
                                                                        @if ($expenses)
                                                                            @if (auth()->user()->can('product.view') || auth()->user()->can('product.create'))
                                                                                <a class="collapse-item {{ $request->segment(1) == 'modules' && $request->segment(2) == 'modifiers' ? 'active' : '' }}"
                                                                                    href="/modifiers">@lang('restaurant.modifiers')</a>
                                                                            @endif
                                                                        @endif
                                                                    @endif
                                                                    @if (!$property_module)
                                                                        <a class="collapse-item {{ $request->segment(1) == 'types-of-service' ? 'active active-sub' : '' }}"
                                                                            href="/types-of-service">@lang('lang_v1.types_of_service')</a>
                                                                    @endif
                                                                @endif
                                                                @if (Module::has('Superadmin'))
                                                                @endif
                                                                <a class="collapse-item {{ $request->segment(1) == 'opt-verification' && $request->segment(2) == 'index' ? 'active active-sub' : '' }}"
                                                                    href="{{ action('\App\Http\Controllers\UsersOPTController@index') }}">@lang('superadmin::lang.OTP_verification')</a>

                                                                <a class="collapse-item {{ $request->segment(1) == 'pay-online' && $request->segment(2) == 'create' ? 'active active-sub' : '' }}"
                                                                    href="{{ action('\Modules\Superadmin\Http\Controllers\PayOnlineController@create') }}">@lang('superadmin::lang.pay_online')</a>

                                                                <a class="collapse-item {{ $request->segment(1) == 'reports' ? 'active active-sub' : '' }}"
                                                                    href="{{ action('ReportConfigurationsController@index') }}">@lang('reports_configurations.reports_configurations')</a>

                                                                <a class="collapse-item {{ $request->segment(1) == 'user-locations' ? 'active active-sub' : '' }}"
                                                                    href="{{ route('userlocations.index') }}">@lang('superadmin::lang.user_locations_sidebar')</a>

                                                            </div>
                                                        </div>
                                                    </li>

                                                @endcan
                                            @endif
                                        @endif

                                        @if ($enable_sms && $list_sms)
                                            @can('sms.access')
                                                @includeIf('sms::layouts_v2.partials.sidebar')
                                            @endcan
                                        @endif

                                        @if ($enable_sms && $smsmodule_module)
                                            @includeIf('sms::layouts_v2.partials.smsmodule_sidebar')
                                        @endif


                                        {{-- Communication Hub standalone module menu: keep submenu inside Modules/CommunicationHub. --}}
                                        @includeIf('communicationhub::partials.sidebar')

                                        @if ($member_registration)
                                            @can('member.access')
                                                @includeIf('member::layouts_v2.partials.sidebar')
                                                @endcan @endif


                                            @if (auth()->user()->hasRole('Super Manager#1'))
                                                <li
                                                    class="nav-item {{ in_array($request->segment(1), ['super-manager']) ? 'active active-sub' : '' }}">
                                                    <a class="nav-link collapsed" href="#" data-toggle="collapse"
                                                        data-target="#visitors-menusup" aria-expanded="true"
                                                        aria-controls="visitors-menusup">
                                                        <i class="fa fa-group"></i>
                                                        <span>@lang('lang_v1.super_manager')</span>
                                                    </a>
                                                    <div id="visitors-menusup"
                                                        class="collapse {{ in_array($request->segment(1), ['super-manager']) ? 'show' : '' }}"
                                                        aria-labelledby="headingPages" data-parent="#accordionSidebar">
                                                        <div class="bg-white py-2 collapse-inner rounded">
                                                            <h6 class="collapse-header">
                                                                @lang('lang_v1.super_manager'):
                                                            </h6>
                                                            <a class="collapse-item {{ $request->segment(2) == 'visitors' ? 'active active-sub' : '' }}"
                                                                href="{{ action('SuperManagerVisitorController@index') }}">@lang('lang_v1.all_visitor_details')</a>
                                                        </div>
                                                    </div>
                                                </li>
                                                @endif @if ($visitors_registration_module)
                                                    @includeIf('visitor::layouts_v2.partials.sidebar')
                                                    @endif @if ($user_management_module)
                                                        @if (auth()->user()->can('user.view') || auth()->user()->can('user.create') || auth()->user()->can('roles.view'))
                                                            <li
                                                                class="nav-item {{ in_array($request->segment(1), ['roles', 'users', 'sales-commission-agents', 'user-groups']) ? 'active active-sub' : '' }}">
                                                                <a class="nav-link collapsed" href="#"
                                                                    data-toggle="collapse" data-target="#user-menu"
                                                                    aria-expanded="true" aria-controls="user-menu">
                                                                    <i class="fa fa-group"></i>
                                                                    <span>@lang('user.user_management')</span>
                                                                </a>
                                                                <div id="user-menu"
                                                                    class="collapse {{ in_array($request->segment(1), ['roles', 'users', 'sales-commission-agents', 'user-groups']) ? 'show' : '' }}"
                                                                    aria-labelledby="headingPages"
                                                                    data-parent="#accordionSidebar">
                                                                    <div class="bg-white py-2 collapse-inner rounded">
                                                                        <h6 class="collapse-header">
                                                                            @lang('user.user_management'):
                                                                        </h6>
                                                                        @can('user_group.view')
                                                                            <a class="collapse-item {{ $request->segment(1) == 'user-groups' ? 'active active-sub' : '' }}"
                                                                                href="/user-groups">@lang('user_group.user_groups')</a>
                                                                        @endcan
                                                                        @can('user.view')
                                                                            <a class="collapse-item {{ $request->segment(1) == 'users' ? 'active active-sub' : '' }}"
                                                                                href="{{ action('ManageUserController@index') }}">@lang('user.users')</a>
                                                                        @endcan
                                                                        @can('roles.view')
                                                                            <a class="collapse-item {{ $request->segment(1) == 'roles' ? 'active active-sub' : '' }}"
                                                                                href="/roles">@lang('user.roles')</a>
                                                                        @endcan
                                                                        @if ($enable_sale_cmsn_agent == 1)
                                                                            @can('user.create')
                                                                                <a class="collapse-item {{ $request->segment(1) == 'users' ? 'active active-sub' : '' }}"
                                                                                    href="{{ action('ManageUserController@list') }}">@lang('user.list')</a>
                                                                            @endcan
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </li>

                                                        @endif
                                                    @endif
                                                    <!-- call Project module if defined -->
                                                    @if (Module::has('Project'))
                                                        @can('project.access')
                                                            @includeIf('project::layouts.partials.sidebar')
                                                        @endcan
                                                    @endif
                                                    <!-- call Essentials module if defined -->
                                                    @if (Module::has('Essentials'))
                                                        @if ($hr_module)
                                                            @can('hr_module.access')
                                                                @includeIf('essentials::layouts.partials.sidebar_hrm')
                                                            @endcan
                                                        @endif

                                                        @if ($essentials_module)
                                                            @includeIf('essentials::layouts.partials.sidebar')
                                                        @endif
                                                    @endif


                                                    @if (Module::has('Woocommerce'))
                                                        @includeIf('woocommerce::layouts.partials.sidebar')
                                                    @endif
                                                    <!-- only customer accessable pages -->
                                                    @if (auth()->user()->is_customer == 1)
                                                        <li
                                                            class="nav-item {{ in_array($request->segment(1), ['customer-sales', 'customer-sell-return', 'customer-order', 'customer-order-list']) ? 'active active-sub' : '' }}">
                                                            <a class="nav-link collapsed" href="#"
                                                                data-toggle="collapse" data-target="#customer-menu"
                                                                aria-expanded="true" aria-controls="customer-menu">
                                                                <i class="fa fa-folder-open"></i>
                                                                <span>@lang('sale.sale')</span>
                                                            </a>
                                                            <div id="customer-menu"
                                                                class="collapse {{ in_array($request->segment(1), ['customer-sales', 'customer-sell-return', 'customer-order', 'customer-order-list']) ? 'show' : '' }}"
                                                                aria-labelledby="headingPages"
                                                                data-parent="#accordionSidebar">
                                                                <div class="bg-white py-2 collapse-inner rounded">
                                                                    <h6 class="collapse-header">
                                                                        @lang('sale.sale'):
                                                                    </h6>

                                                                    <a class="collapse-item {{ $request->segment(1) == 'customer-sales' ? 'active' : '' }}"
                                                                        href="{{ action('CustomerSellController@index') }}">@lang('lang_v1.all_sales')</a>

                                                                    <a class="collapse-item {{ $request->segment(1) == 'customer-sell-return' ? 'active' : '' }}"
                                                                        href="{{ action('CustomerSellReturnController@index') }}">@lang('lang_v1.list_sell_return')</a>

                                                                    <a class="collapse-item {{ $request->segment(1) == 'customer-order' ? 'active' : '' }}"
                                                                        href="{{ action('CustomerOrderController@create') }}">@lang('lang_v1.order')</a>

                                                                    <a class="collapse-item {{ $request->segment(1) == 'customer-order-list' ? 'active' : '' }}"
                                                                        href="{{ action('CustomerOrderController@getOrders') }}">@lang('lang_v1.list_order')</a>

                                                                </div>
                                                            </div>
                                                        </li>
                                                    @endif
                                                    <!-- end only customer accessable pages -->
                                                    {{-- S363: Standalone Chequer Module sidebar bridge.
                                                         This is intentionally rendered directly here so the menu is visible
                                                         even before module view namespaces/cache are refreshed. All feature
                                                         pages, controllers, routes, views and logic remain inside
                                                         Modules/Chequer. The old cheque module remains untouched. --}}
                                                    @php
                                                        $chequerStandaloneSegments = ['chequer-module'];
                                                        $isChequerStandaloneActive = in_array($request->segment(1), $chequerStandaloneSegments);
                                                    @endphp
                                                    <li class="nav-item {{ $isChequerStandaloneActive ? 'active active-sub' : '' }}">
                                                        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#chequer-standalone-menu"
                                                            aria-expanded="{{ $isChequerStandaloneActive ? 'true' : 'false' }}" aria-controls="chequer-standalone-menu">
                                                            <i class="fa fa-pencil-square-o"></i>
                                                            <span>Chequer Module</span>
                                                        </a>
                                                        <div id="chequer-standalone-menu" class="collapse {{ $isChequerStandaloneActive ? 'show' : '' }}"
                                                            aria-labelledby="headingPages" data-parent="#accordionSidebar">
                                                            <div class="bg-white py-2 collapse-inner rounded">
                                                                <h6 class="collapse-header">Chequer Module:</h6>

                                                                <a class="collapse-item {{ $request->is('chequer-module') ? 'active' : '' }}" href="{{ url('chequer-module') }}">Dashboard</a>
                                                                <a class="collapse-item {{ $request->is('chequer-module/bank-accounts*') ? 'active' : '' }}" href="{{ url('chequer-module/bank-accounts') }}">Bank Accounts</a>
                                                                <a class="collapse-item {{ $request->is('chequer-module/templates*') ? 'active' : '' }}" href="{{ url('chequer-module/templates') }}">Templates</a>
                                                                <a class="collapse-item {{ $request->is('chequer-module/cheque-books*') ? 'active' : '' }}" href="{{ url('chequer-module/cheque-books') }}">Cheque Books</a>
                                                                <a class="collapse-item {{ $request->is('chequer-module/write-cheque/create') ? 'active' : '' }}" href="{{ url('chequer-module/write-cheque/create') }}">Write Cheque</a>
                                                                <a class="collapse-item {{ $request->is('chequer-module/cheque-numbers*') ? 'active' : '' }}" href="{{ url('chequer-module/cheque-numbers') }}">Cheque Number List</a>
                                                                <a class="collapse-item {{ $request->is('chequer-module/cheque-number-entries*') ? 'active' : '' }}" href="{{ url('chequer-module/cheque-number-entries') }}">Cheque Number Entries</a>
                                                                <a class="collapse-item {{ $request->is('chequer-module/payees*') ? 'active' : '' }}" href="{{ url('chequer-module/payees') }}">Manage Payee</a>
                                                                <a class="collapse-item {{ $request->is('chequer-module/stamps*') ? 'active' : '' }}" href="{{ url('chequer-module/stamps') }}">Manage Stamps</a>
                                                                <a class="collapse-item {{ $request->is('chequer-module/cancelled-cheques*') ? 'active' : '' }}" href="{{ url('chequer-module/cancelled-cheques') }}">Cancelled Cheques</a>
                                                                <a class="collapse-item {{ $request->is('chequer-module/deleted-cheques*') ? 'active' : '' }}" href="{{ url('chequer-module/deleted-cheques') }}">Deleted Cheques</a>
                                                                <a class="collapse-item {{ $request->is('chequer-module/default-settings*') ? 'active' : '' }}" href="{{ url('chequer-module/default-settings') }}">Default Settings</a>
                                                                <a class="collapse-item {{ $request->is('chequer-module/print-calibration*') ? 'active' : '' }}" href="{{ url('chequer-module/print-calibration') }}">Print Calibration</a>
                                                                <a class="collapse-item {{ $request->is('chequer-module/print-history*') ? 'active' : '' }}" href="{{ url('chequer-module/print-history') }}">Print History</a>
                                                            </div>
                                                        </div>
                                                    </li>

                                                    @if ($enable_cheque_writing == 1)
                                                        @if (auth()->user()->can('enable_cheque_writing'))

                                                            <li
                                                                class="nav-item {{ in_array($request->segment(1), ['cheque-templates', 'cheque-write', 'stamps', 'cheque-numbers', 'payees', 'deleted_cheque_details', 'printed_cheque_details', 'default_setting', 'cheque-dashboard']) ? 'active active-sub' : '' }}">
                                                                <a class="nav-link collapsed" href="#"
                                                                    data-toggle="collapse" data-target="#cheque-menu"
                                                                    aria-expanded="true" aria-controls="cheque-menu">
                                                                    <i class="fa fa-folder-open"></i>
                                                                    <span>@lang('cheque.cheque_writing_module')</span>
                                                                </a>
                                                                <div id="cheque-menu"
                                                                    class="collapse {{ in_array($request->segment(1), ['cheque-templates', 'cheque-write', 'stamps', 'cheque-numbers', 'payees', 'deleted_cheque_details', 'printed_cheque_details', 'default_setting', 'cheque-dashboard']) ? 'show' : '' }}"
                                                                    aria-labelledby="headingPages"
                                                                    data-parent="#accordionSidebar">
                                                                    <div class="bg-white py-2 collapse-inner rounded">
                                                                        <h6 class="collapse-header">
                                                                            @lang('cheque.cheque_writing_module'):
                                                                        </h6>

                                                                        @if ($cheque_dashboard)
                                                                            <a class="collapse-item {{ $request->segment(1) == 'cheque-dashboard' && $request->segment(2) == '' ? 'active' : '' }}"
                                                                                href="{{ url('chequer-module') }}">Chequer
                                                                                Dashboard</a>
                                                                        @endif

                                                                        @if ($cheque_templates)
                                                                            <a class="collapse-item {{ $request->segment(1) == 'cheque-templates' && $request->segment(2) == '' ? 'active' : '' }}"
                                                                                href="{{ action('Chequer\ChequeTemplateController@index') }}">@lang('cheque.templates')</a>
                                                                        @endif

                                                                        @if ($cheque_add_template)
                                                                            <a class="collapse-item {{ $request->segment(1) == 'cheque-templates' && $request->segment(2) == 'create' ? 'active' : '' }}"
                                                                                href="{{ action('Chequer\ChequeTemplateController@create') }}">@lang('cheque.add_new_templates')</a>
                                                                        @endif

                                                                        @if ($write_cheque)
                                                                            <a class="collapse-item {{ $request->segment(1) == 'cheque-write' && $request->segment(2) == 'create' ? 'active' : '' }}"
                                                                                href="{{ action('Chequer\ChequeWriteController@create') }}">@lang('cheque.write_cheque')</a>
                                                                        @endif

                                                                        @if ($manage_stamps)
                                                                            <a class="collapse-item {{ $request->segment(1) == 'stamps' && $request->segment(2) == '' ? 'active' : '' }}"
                                                                                href="{{ action('Chequer\ChequerStampController@index') }}">@lang('cheque.manage_stamps')</a>
                                                                        @endif

                                                                        @if ($manage_payee)
                                                                            <a class="collapse-item {{ $request->segment(1) == 'payees' && $request->segment(2) == '' ? 'active' : '' }}"
                                                                                href="{{ url('payees') }}">Manage
                                                                                Payee</a>
                                                                        @endif

                                                                        @if ($cheque_number_list)
                                                                            <a class="collapse-item {{ $request->segment(1) == 'cheque-numbers' && $request->segment(2) == '' ? 'active' : '' }}"
                                                                                href="{{ action('Chequer\ChequeNumberController@index') }}">@lang('cheque.cheque_number_list')</a>
                                                                            <a class="collapse-item {{ $request->segment(1) == 'cheque-numbers-m-entries' && $request->segment(2) == '' ? 'active' : '' }}"
                                                                                href="{{ action('Chequer\ChequeNumbersMEntryController@index') }}">@lang('cheque.cheque_number_m_entries')</a>
                                                                        @endif
                                                                        @if ($cheque_cancelled_cheques)
                                                                            <a class="collapse-item {{ $request->segment(1) == 'cancell_cheque_details' && $request->segment(2) == '' ? 'active' : '' }}"
                                                                                href="{{ route('cancell_cheque_details.create') }}">@lang('cheque.cancel_cheque_menu')</a>
                                                                            <a class="collapse-item {{ $request->segment(1) == 'cancell_cheque_details' && $request->segment(2) == '' ? 'active' : '' }}"
                                                                                href="{{ route('cancell_cheque_details.index') }}">@lang('cheque.list_cancel_cheque_menu')</a>
                                                                        @endif

                                                                        @if ($cheque_printed_cheques)
                                                                            <a class="collapse-item {{ $request->segment(1) == 'printed_cheque_details' && $request->segment(2) == '' ? 'active' : '' }}"
                                                                                href="{{ url('printed_cheque_details') }}">Printed
                                                                                Cheque
                                                                                Details.</a>
                                                                        @endif

                                                                        @if ($default_setting)
                                                                            <a class="collapse-item {{ $request->segment(1) == 'default_setting' && $request->segment(2) == '' ? 'active' : '' }}"
                                                                                href="{{ url('default_setting') }}">Default
                                                                                Settings</a>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </li>
                                                        @endif
                                                    @endif

                                                    {{-- Standalone modules not already hard-coded above. --}}
                                                    @includeIf('layouts.partials.sidebar-sections.sidebar-new-operations-modules')
                                                    @includeIf('layouts.partials.automatic-module-sidebar')

                                                    <!-- Divider -->
                                                    <hr class="sidebar-divider d-none d-md-block">


            </ul>
            <script>
                document.addEventListener('DOMContentLoaded', (event) => {
                    const filterInput = document.getElementById('sidebarFilter');
                    const navItems = document.querySelectorAll(
                        '.sidebar .nav-item:not(:first-child)'); // Exclude the filter input

                    if (!filterInput) {
                        return;
                    }

                    // Load filter value from localStorage
                    const filterValue = localStorage.getItem('sidebarFilter') || '';
                    filterInput.value = filterValue;
                    filterMenu(filterValue);

                    // Add event listener for filter input
                    filterInput.addEventListener('input', (e) => {
                        const value = e.target.value.toLowerCase();
                        console.log('value', value);
                        localStorage.setItem('sidebarFilter', value);
                        console.log('value', value);
                        filterMenu(value);
                    });

                    function filterMenu(value) {
                        navItems.forEach(item => {
                            const text = item.textContent.toLowerCase();
                            if (text.includes(value)) {
                                item.style.display = 'block';
                            } else {
                                item.style.display = 'none';
                            }
                        });
                    }
                });
            </script>

        
