@php
    $business_id = (int) request()->session()->get('user.business_id');
    $user = auth()->check() ? auth()->user() : null;
    $is_superadmin = $user && $user->can('superadmin');
    $is_business_admin = $user && $user->hasRole('Admin#' . $business_id);

    /*
    |--------------------------------------------------------------------------
    | S717 - Petro PD authority hierarchy
    |--------------------------------------------------------------------------
    | Parent module visibility: Manage Side Bar ONLY.
    | Page/tab/feature visibility: Manage New.
    | User-specific access: User Management / role permissions.
    |
    | Do not read package_details['petro_pd_module'] here. That parent flag is
    | owned by the retiring legacy Manage page and was able to hide Petro PD
    | even when Manage Side Bar explicitly enabled it.
    */
    $petro_pd_module = \App\Utils\SidebarPermissionUtil::isManageSidebarEnabled('petro_pd', $business_id)
        && \App\Utils\SidebarPermissionUtil::isModuleAllowedForCurrentManagedRole('petro_pd', $business_id);

    // These are the existing Manage New permission keys. Absent keys retain the
    // standard Manage New default (enabled); an explicit OFF hides that page.
    $petro_pd_pd_settlement = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petro_pd_pd_settlement', $business_id);
    $petro_pd_pd_operators = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petro_pd_pd_operators', $business_id);
    $petro_pd_list_assigned_operators = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petro_pd_list_assigned_operators', $business_id);
    $petro_pd_user_activity = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petro_pd_user_activity', $business_id);
    $petro_pd_adjusted_amounts_report = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petro_pd_adjusted_amounts_report', $business_id);
    $petro_pd_payment_reconciliation_report = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petro_pd_payment_reconciliation_report', $business_id);
    $petro_pd_list_pd_settlement = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petro_pd_list_pd_settlement', $business_id);
    $petro_pd_settings = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petro_pd_settings', $business_id);
    $petro_pd_sms_notifications = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petro_pd_sms_notifications', $business_id);
    $petro_pd_whatsapp_notifications = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petro_pd_whatsapp', $business_id);

    $has_petro_pd_access = $is_superadmin
        || $is_business_admin
        || ($user && $user->can('petro_pd.access'));

    $can_petro_pd_settlement = $petro_pd_module && $petro_pd_pd_settlement && $has_petro_pd_access;
    $can_petro_pd_operators = $petro_pd_module && $petro_pd_pd_operators && $has_petro_pd_access && ($is_superadmin || $is_business_admin || ($user && $user->can('petro_pd.view_operators')));
    $can_list_assigned_operators = $petro_pd_module && $petro_pd_list_assigned_operators && $has_petro_pd_access && ($is_superadmin || $is_business_admin || ($user && $user->can('petro_pd.view_operators')));
    $can_petro_pd_reports = $petro_pd_module && $petro_pd_user_activity && $has_petro_pd_access && ($is_superadmin || $is_business_admin || ($user && $user->can('petro_pd.view_report')));
    $can_adjusted_amounts_report = $petro_pd_module && $petro_pd_adjusted_amounts_report && $has_petro_pd_access && ($is_superadmin || $is_business_admin || ($user && ($user->can('petro_pd.view_adjusted_amounts_report') || $user->can('petro_pd.view_report'))));
    $can_payment_reconciliation_report = $petro_pd_module && $petro_pd_payment_reconciliation_report && $has_petro_pd_access && ($is_superadmin || $is_business_admin || ($user && ($user->can('petro_pd.view_payment_reconciliation_report') || $user->can('petro_pd.view_report'))));
    $can_petro_pd_list_settlement = $petro_pd_module && $petro_pd_list_pd_settlement && $has_petro_pd_access && ($is_superadmin || $is_business_admin || ($user && $user->can('petro_pd.list_settlement')));
    $can_petro_pd_settings = $petro_pd_module && $petro_pd_settings && ($is_superadmin || $is_business_admin);

    $can_create_pd_settlement = $can_petro_pd_settlement && ($is_superadmin || $is_business_admin || ($user && $user->can('petro_pd.create_settlement')));
    $can_edit_pd_settlement = $can_petro_pd_settlement && ($is_superadmin || $is_business_admin || ($user && $user->can('petro_pd.edit_settlement')));
    $can_delete_pd_settlement = $can_petro_pd_settlement && ($is_superadmin || $is_business_admin || ($user && $user->can('petro_pd.delete_settlement')));
    $can_manual_entry = $can_petro_pd_settlement && ($is_superadmin || $is_business_admin || ($user && $user->can('petro_pd.manual_entry')));
    $can_meter_sale_tab = $can_petro_pd_settlement && ($is_superadmin || $is_business_admin || ($user && $user->can('petro_pd.meter_sale_tab')));
    $can_other_sale_tab = $can_petro_pd_settlement && ($is_superadmin || $is_business_admin || ($user && $user->can('petro_pd.other_sale_tab')));
    $can_other_income_tab = $can_petro_pd_settlement && ($is_superadmin || $is_business_admin || ($user && $user->can('petro_pd.other_income_tab')));
    $can_customer_payment_tab = $can_petro_pd_settlement && ($is_superadmin || $is_business_admin || ($user && $user->can('petro_pd.customer_payment_tab')));
    $can_payment_tab = $can_petro_pd_settlement && ($is_superadmin || $is_business_admin || ($user && $user->can('petro_pd.payment_tab')));
    $can_sms_notifications = $petro_pd_module && $petro_pd_sms_notifications && $has_petro_pd_access && ($is_superadmin || $is_business_admin || ($user && $user->can('petro_pd_sms_notifications')));
    $can_whatsapp_notifications = $petro_pd_module && $petro_pd_whatsapp_notifications && $has_petro_pd_access && ($is_superadmin || $is_business_admin || ($user && $user->can('petro_pd_whatsapp')));

    $safeRoute = function ($routeName, $fallback = '#') {
        try {
            return \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName) : $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    };

    $pdSettlementUrl = $safeRoute('petropd.settlement-pd.create', url('/petropd/settlement-pd/create'));
    $pdOperatorsUrl = $safeRoute('petropd.pd-operators', url('/petropd/pd-operators'));
    $listAssignedOperatorsUrl = $safeRoute('petropd.list-assigned-operators', url('/petropd/list-assigned-operators'));
    $userActivityUrl = $safeRoute('petropd.user-activity-report', url('/petropd/user-activity-report'));
    $adjustedAmountsReportUrl = $safeRoute('petropd.adjusted-amounts-report', url('/petropd/adjusted-amounts-report'));
    $paymentReconciliationReportUrl = $safeRoute('petropd.payment-reconciliation-report', url('/petropd/payment-reconciliation-report'));
    $listSettlementUrl = $safeRoute('petropd.list-pd-settlement', url('/petropd/list-pd-settlement'));
    $settingsUrl = $safeRoute('petropd.get-settings', url('/petropd/pd-operators/get-settings'));

    // S717: the parent itself follows Manage Side Bar. Manage New may hide all
    // child links, but it must not make an enabled parent look disabled.
    $show_petro_pd_sidebar = $petro_pd_module && $has_petro_pd_access;
@endphp

@if ($show_petro_pd_sidebar)
<li class="nav-item">
    <a class="nav-link collapsed {{ request()->routeIs('petropd.*') ? 'active active-sub' : '' }}"
        href="#"
        data-toggle="collapse"
        data-target="#petro-pd-menu"
        aria-expanded="{{ request()->routeIs('petropd.*') ? 'true' : 'false' }}"
        aria-controls="petro-pd-menu">
        <i class="fa fa-tint fa-lg"></i>
        <span>@lang('petropd::lang.petro_pd')</span>
    </a>

    <div id="petro-pd-menu"
        class="collapse {{ request()->routeIs('petropd.*') ? 'show' : '' }}"
        data-parent="#accordionSidebar">

        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">@lang('petropd::lang.petro_pd'):</h6>

            @if ($can_petro_pd_settlement || $can_create_pd_settlement || $can_manual_entry || $can_meter_sale_tab || $can_other_sale_tab || $can_other_income_tab || $can_customer_payment_tab || $can_payment_tab)
                <a class="collapse-item {{ request()->routeIs('petropd.pd-settlement') ? 'active' : '' }}"
                    href="{{ $pdSettlementUrl }}">
                    @lang('petropd::lang.pd_settlement')
                </a>
            @endif

            @if ($can_create_pd_settlement)
                <a class="collapse-item" href="{{ $pdSettlementUrl }}?action=create">
                    Create PD Settlement
                </a>
            @endif

            @if ($can_edit_pd_settlement)
                <a class="collapse-item" href="{{ $listSettlementUrl }}?action=edit">
                    Edit PD Settlement
                </a>
            @endif

            @if ($can_delete_pd_settlement)
                <a class="collapse-item" href="{{ $listSettlementUrl }}?action=delete">
                    Delete PD Settlement
                </a>
            @endif

            @if ($can_manual_entry)
                <a class="collapse-item" href="{{ $pdSettlementUrl }}#manual-entry-tab">
                    Manual Entry in Settlement
                </a>
            @endif

            @if ($can_meter_sale_tab)
                <a class="collapse-item" href="{{ $pdSettlementUrl }}#meter-sale-tab">
                    Meter Sale Tab
                </a>
            @endif

            @if ($can_other_sale_tab)
                <a class="collapse-item" href="{{ $pdSettlementUrl }}#other-sale-tab">
                    Other Sale Tab
                </a>
            @endif

            @if ($can_other_income_tab)
                <a class="collapse-item" href="{{ $pdSettlementUrl }}#other-income-tab">
                    Other Income Tab
                </a>
            @endif

            @if ($can_customer_payment_tab)
                <a class="collapse-item" href="{{ $pdSettlementUrl }}#customer-payment-tab">
                    Customer Payment Tab
                </a>
            @endif

            @if ($can_payment_tab)
                <a class="collapse-item" href="{{ $pdSettlementUrl }}#payment-tab">
                    Payment Tab
                </a>
            @endif

            @if ($can_petro_pd_operators)
                <a class="collapse-item {{ request()->routeIs('petropd.pd-operators') ? 'active' : '' }}"
                    href="{{ $pdOperatorsUrl }}">
                    @lang('petropd::lang.pd_operators')
                </a>
            @endif

            @if ($can_list_assigned_operators)
                <a class="collapse-item {{ request()->routeIs('petropd.list-assigned-operators*') ? 'active' : '' }}"
                    href="{{ $listAssignedOperatorsUrl }}">
                    @lang('petropd::lang.list_assigned_operators')
                </a>
            @endif

            @if ($can_petro_pd_reports)
                <a class="collapse-item {{ request()->routeIs('petropd.user-activity-report') ? 'active' : '' }}"
                    href="{{ $userActivityUrl }}">
                    @lang('petropd::lang.user_activities')
                </a>
            @endif


            @if ($can_adjusted_amounts_report)
                <a class="collapse-item {{ request()->routeIs('petropd.adjusted-amounts-report') ? 'active' : '' }}"
                    href="{{ $adjustedAmountsReportUrl }}">
                    @lang('petropd::lang.adjusted_amounts_report')
                </a>
            @endif


            @if ($can_payment_reconciliation_report)
                <a class="collapse-item {{ request()->routeIs('petropd.payment-reconciliation-report*') ? 'active' : '' }}"
                    href="{{ $paymentReconciliationReportUrl }}">
                    @lang('petropd::lang.payment_reconciliation_report')
                </a>
            @endif

            @if ($can_petro_pd_list_settlement)
                <a class="collapse-item {{ request()->routeIs('petropd.list-pd-settlement') ? 'active' : '' }}"
                    href="{{ $listSettlementUrl }}">
                    @lang('petropd::lang.list_pd_settlement')
                </a>
            @endif

            @if ($can_sms_notifications)
                <a class="collapse-item {{ request()->routeIs('petropd.sms-notifications') ? 'active' : '' }}" href="{{ $safeRoute('petropd.sms-notifications', $settingsUrl . '#sms-notifications') }}">
                    Petro PD SMS Notifications
                </a>
            @endif

            @if ($can_whatsapp_notifications)
                <a class="collapse-item" href="{{ $safeRoute('petropd.whatsapp-notifications', $settingsUrl . '#whatsapp-notifications') }}">
                    WhatsApp Notifications
                </a>
            @endif

            @if ($can_petro_pd_settings)
                <a class="collapse-item {{ request()->routeIs('petropd.get-settings') ? 'active' : '' }}"
                    href="{{ $settingsUrl }}">
                    @lang('petropd::lang.petro_pd_settings')
                </a>
            @endif
        </div>
    </div>
</li>
@endif
