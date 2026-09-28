@php
    $business_id = (int) request()->session()->get('user.business_id');
    $user = auth()->check() ? auth()->user() : null;
    $is_superadmin = $user && $user->can('superadmin');
    $is_business_admin = $user && $user->hasRole('Admin#' . $business_id);

    /* S717: parent = Manage Side Bar, children = Manage New, user = role. */
    $petro_pd_module = \App\Utils\SidebarPermissionUtil::isManageSidebarEnabled('petro_pd', $business_id)
        && \App\Utils\SidebarPermissionUtil::isModuleAllowedForCurrentManagedRole('petro_pd', $business_id);

    $petro_pd_pd_settlement = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petro_pd_pd_settlement', $business_id);
    $petro_pd_pd_operators = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petro_pd_pd_operators', $business_id);
    $petro_pd_user_activity = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petro_pd_user_activity', $business_id);
    $petro_pd_adjusted_amounts_report = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petro_pd_adjusted_amounts_report', $business_id);
    $petro_pd_payment_reconciliation_report = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petro_pd_payment_reconciliation_report', $business_id);
    $petro_pd_list_pd_settlement = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petro_pd_list_pd_settlement', $business_id);
    $petro_pd_settings = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petro_pd_settings', $business_id);
    $petro_pd_sms_notifications = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petro_pd_sms_notifications', $business_id);

    $has_petro_pd_access = $is_superadmin || $is_business_admin || ($user && $user->can('petro_pd.access'));

    $can_pd_settlement = $petro_pd_module && $petro_pd_pd_settlement && $has_petro_pd_access && ($is_superadmin || $is_business_admin || ($user && $user->can('petro_pd.create_settlement')));
    $can_pd_operators = $petro_pd_module && $petro_pd_pd_operators && $has_petro_pd_access && ($is_superadmin || $is_business_admin || ($user && $user->can('petro_pd.view_operators')));
    $can_pd_user_activity = $petro_pd_module && $petro_pd_user_activity && $has_petro_pd_access && ($is_superadmin || $is_business_admin || ($user && $user->can('petro_pd.view_report')));
    $can_adjusted_amounts_report = $petro_pd_module && $petro_pd_adjusted_amounts_report && $has_petro_pd_access && ($is_superadmin || $is_business_admin || ($user && ($user->can('petro_pd.view_adjusted_amounts_report') || $user->can('petro_pd.view_report'))));
    $can_payment_reconciliation_report = $petro_pd_module && $petro_pd_payment_reconciliation_report && $has_petro_pd_access && ($is_superadmin || $is_business_admin || ($user && ($user->can('petro_pd.view_payment_reconciliation_report') || $user->can('petro_pd.view_report'))));
    $can_list_pd_settlement = $petro_pd_module && $petro_pd_list_pd_settlement && $has_petro_pd_access && ($is_superadmin || $is_business_admin || ($user && $user->can('petro_pd.list_settlement')));
    $can_pd_settings = $petro_pd_module && $petro_pd_settings && ($is_superadmin || $is_business_admin);
    $can_sms_notifications = $petro_pd_module && $petro_pd_sms_notifications && $has_petro_pd_access && ($is_superadmin || $is_business_admin || ($user && ($user->can('petro_pd_sms_notifications') || $user->can('petro_pd.access'))));

    // Parent visibility no longer depends on any legacy package flag or on at
    // least one child being enabled. That keeps Manage Side Bar authoritative.
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

            @if($can_pd_settlement)
                <a class="collapse-item {{ request()->routeIs('petropd.pd-settlement') ? 'active' : '' }}"
                    href="{{ route('petropd.settlement-pd.create') }}">
                    @lang('petropd::lang.pd_settlement')
                </a>
            @endif

            @if($can_pd_operators)
                <a class="collapse-item {{ request()->routeIs('petropd.pd-operators') ? 'active' : '' }}"
                    href="{{ route('petropd.pd-operators') }}">
                    @lang('petropd::lang.pd_operators')
                </a>
            @endif

            @if($can_pd_user_activity)
                <a class="collapse-item {{ request()->routeIs('petropd.user-activity-report') ? 'active' : '' }}"
                    href="{{ route('petropd.user-activity-report') }}">
                    @lang('petropd::lang.user_activities')
                </a>
            @endif


            @if($can_adjusted_amounts_report)
                <a class="collapse-item {{ request()->routeIs('petropd.adjusted-amounts-report') ? 'active' : '' }}"
                    href="{{ route('petropd.adjusted-amounts-report') }}">
                    @lang('petropd::lang.adjusted_amounts_report')
                </a>
            @endif


            @if($can_payment_reconciliation_report)
                <a class="collapse-item {{ request()->routeIs('petropd.payment-reconciliation-report*') ? 'active' : '' }}"
                    href="{{ route('petropd.payment-reconciliation-report') }}">
                    @lang('petropd::lang.payment_reconciliation_report')
                </a>
            @endif

            @if($can_list_pd_settlement)
                <a class="collapse-item {{ request()->routeIs('petropd.list-pd-settlement') ? 'active' : '' }}"
                    href="{{ route('petropd.list-pd-settlement') }}">
                    @lang('petropd::lang.list_pd_settlement')
                </a>
            @endif


            @if($can_sms_notifications)
                <a class="collapse-item {{ request()->routeIs('petropd.sms-notifications') ? 'active' : '' }}"
                    href="{{ route('petropd.sms-notifications') }}">
                    Petro PD SMS Notifications
                </a>
            @endif

            @if($can_pd_settings)
                <a class="collapse-item {{ request()->routeIs('petropd.get-settings') ? 'active' : '' }}"
                    href="{{ route('petropd.get-settings') }}">
                    @lang('petropd::lang.petro_pd_settings')
                </a>
            @endif
        </div>
    </div>
</li>
@endif
