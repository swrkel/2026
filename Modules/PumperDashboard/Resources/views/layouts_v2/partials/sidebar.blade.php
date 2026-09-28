@php

    $module_array = [
        'pumper-dashboard_dashboard' => 0,
        'pumper-dashboard_daily_status' => 0,
        'tank_transfer' => 0,
        'pumper-dashboard_task_management' => 0,
        'pumper_management' => 0,
        'daily_collection' => 0,
        'settlement' => 0,
        'pumper-dashboard_settlement' => 0,
        'list_settlement' => 0,
        'dip_management' => 0,
        'pump_operator_dashboard' => 0,
        'list_tank_transfer' => 0,
        'pumper-dashboard_activity_report' => 0,
        'day_end_settlement' => 0,
        'pumper-dashboard_whatsapp' => 0,
        'blocked_pump_operators' => 0,
        'tanks_transaction_details' => 0,
        'tanks_transaction_summary' => 0,
        'customer_bill_vat_prefix' => 0,
        'pumper-dashboard_notification_template' => 0
    ];

    foreach ($module_array as $key => $module_value) {
        ${$key} = 0;
    }

    $business_id = request()->session()->get('user.business_id');
    $subscription = Modules\Superadmin\Entities\Subscription::current_subscription($business_id);
    $stock_adjustment = 0;

    if (!empty($subscription)) {
        $package_details = $subscription->package_details;
        $stock_adjustment = $package_details['stock_adjustment'];

        foreach ($module_array as $key => $module_value) {
            if (array_key_exists($key, $package_details)) {
                ${$key} = $package_details[$key];
            } else {
                ${$key} = 0;
            }
        }
    }

    $can_view_pumper_dashboard_sidebar = auth()->check()
        && !empty($pump_operator_dashboard)
        && (
            auth()->user()->can('superadmin')
            || auth()->user()->hasRole('Admin#' . $business_id)
            || auth()->user()->can('pump_operator.dashboard')
            || auth()->user()->can('pumper_dashboard.dashboard')
            || auth()->user()->can('pump_operator.main_system')
        );

@endphp


@if ($can_view_pumper_dashboard_sidebar)
    <li class="nav-item">
        <a class="nav-link collapsed {{ in_array($request->segment(1), ['pumper-dashboard']) && $request->segment(2) != 'issue-customer-bill' ? 'active active-sub' : '' }}"
            href="#" data-toggle="collapse" data-target="#pumper-dashboard-menu" aria-expanded="true" aria-controls="pumper-dashboard-menu">
            <i class="fa fa-tint fa-lg"></i>
            <span>@lang('pumperdashboard::lang.pump_operator_dashboard')</span>
        </a>
        <div id="pumper-dashboard-menu" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">@lang('pumperdashboard::lang.pump_operator_dashboard'):</h6>
            {{-- @if ($pumper-dashboard_dashboard)
                <a class="collapse-item {{ $request->segment(1) == 'pumper-dashboard' && $request->segment(2) == 'dashboard' ? 'active' : '' }}"
                    href="{{ action('\Modules\pumper-dashboard\Http\Controllers\pumper-dashboardController@index') }}">@lang('pumperdashboard::lang.dashboard')</a>
            @endif --}}

            @if ($pump_operator_dashboard)
                <a class="collapse-item {{ $request->segment(1) == 'pump-operator' && $request->segment(2) == 'dashboard' ? 'active' : '' }}"
                    href="{{ action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorController@dashboard') }}">@lang('pumperdashboard::lang.pump_operator_dashboard')</a>
            @endif

            {{-- @if ($pumper-dashboard_task_management)
                <a class="collapse-item {{ $request->segment(1) == 'pumper-dashboard' && $request->segment(2) == 'tank-management' ? 'active' : '' }}"
                    href="{{ action('\Modules\pumper-dashboard\Http\Controllers\FuelTankController@index') }}">@lang('pumperdashboard::lang.tank_management')</a>
            @endif
            <a class="collapse-item {{ $request->segment(1) == 'pumper-dashboard' && $request->segment(2) == 'pump-management' ? 'active' : '' }}"
                href="{{ action('\Modules\pumper-dashboard\Http\Controllers\PumpController@index') }}">@lang('pumperdashboard::lang.pump_management')</a>
            @if ($pumper_management)
                <a class="collapse-item {{ $request->segment(1) == 'pumper-dashboard' && $request->segment(2) == 'pump-operators' && $request->segment(3) == '' ? 'active' : '' }}"
                    href="{{ action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorController@index') }}">@lang('pumperdashboard::lang.pumper_management')</a>
            @endif --}}
            {{-- @if ($daily_collection)
                <a class="collapse-item {{ $request->segment(1) == 'pumper-dashboard' && $request->segment(2) == 'daily-collection' ? 'active' : '' }}" href="{{action('\Modules\pumper-dashboard\Http\Controllers\DailyCollectionController@index')}}">@lang('pumperdashboard::lang.daily_collection')</a>
            @endif --}}


            {{-- @if ($pumper-dashboard_settlement)
                <a class="collapse-item {{ $request->segment(1) == 'pumper-dashboard' && $request->segment(2) == 'settlement' && $request->segment(3) == 'create' ? 'active' : '' }}"
                    href="{{ action('\Modules\pumper-dashboard\Http\Controllers\SettlementController@create') }}">@lang('pumperdashboard::lang.settlement')</a>
            @endif
            @if ($list_settlement)
                <a class="collapse-item {{ $request->segment(1) == 'pumper-dashboard' && $request->segment(2) == 'settlement' && $request->segment(3) == '' ? 'active' : '' }}"
                    href="{{ action('\Modules\pumper-dashboard\Http\Controllers\SettlementController@index') }}">@lang('pumperdashboard::lang.list_settlement')</a>
            @endif --}}
            {{-- @php
                // Settlement PD permissions - check for settlement_pd and list_settlement_pd
                $settlement_pd = 0;
                $list_settlement_pd = 0;
                if (!empty($subscription)) {
                    $settlement_pd = $package_details['settlement_pd'] ?? 0;
                    $list_settlement_pd = $package_details['list_settlement_pd'] ?? 0;
                }
            @endphp --}}
            
            {{-- @if ($settlement_pd)
                <a class="collapse-item {{ $request->segment(1) == 'pumper-dashboard' && $request->segment(2) == 'settlement-pd' && $request->segment(3) == 'create' ? 'active' : '' }}"
                    href="{{ action('\Modules\pumper-dashboard\Http\Controllers\SettlementPDController@create') }}">
                    @lang('pumperdashboard::lang.settlement_pd')
                </a>
            @endif

            @if ($list_settlement_pd)
                <a class="collapse-item {{ $request->segment(1) == 'pumper-dashboard' && $request->segment(2) == 'settlement-pd' && $request->segment(3) == '' ? 'active' : '' }}"
                    href="{{ action('\Modules\pumper-dashboard\Http\Controllers\SettlementPDController@index') }}">
                    @lang('pumperdashboard::lang.list_settlement_pd')
                </a>
            @endif --}}

            {{-- @if ($dip_management)
                <a class="collapse-item {{ $request->segment(1) == 'pumper-dashboard' && $request->segment(2) == 'dip-management' && $request->segment(3) == '' ? 'active' : '' }}"
                    href="{{ action('\Modules\pumper-dashboard\Http\Controllers\DipManagementController@index') }}">@lang('pumperdashboard::lang.dip_management')</a>
            @endif

            @if ($pumper-dashboard_daily_status)
                <a class="collapse-item {{ $request->segment(1) == 'pumper-dashboard' && $request->segment(2) == 'daily-status_report' ? 'active' : '' }}"
                    href="{{ action('\Modules\pumper-dashboard\Http\Controllers\DailyStatusReportController@index') }}">@lang('pumperdashboard::lang.daily_status_report')</a>
            @endif --}}

            {{-- @if (!empty($tank_transfer) && $tank_transfer) --}}
            {{-- @if (!empty($list_tank_transfer) && $list_tank_transfer)
                <a class="collapse-item {{ $request->segment(1) == 'pumper-dashboard' && $request->segment(2) == 'tank-transfers' ? 'active' : '' }}"
                    href="{{ action('\Modules\pumper-dashboard\Http\Controllers\TankTransferController@index') }}">@lang('pumperdashboard::lang.list_tank_transfer')</a>
            @endif


            @if ($pump_operator_dashboard)
                <a class="collapse-item {{ $request->segment(1) == 'pump-operator' && $request->segment(2) == 'dashboard' ? 'active' : '' }}"
                    href="{{ action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorController@setting_dash') }}">@lang('pumperdashboard::lang.pump_dashboard_settings')</a>
            @endif

            @if ($pumper-dashboard_activity_report)
                <a class="collapse-item {{ $request->segment(1) == 'settlement' && $request->segment(2) == 'activity-report' ? 'active' : '' }}"
                    href="{{ action('\Modules\pumper-dashboard\Http\Controllers\SettlementController@getUserActivityReport') }}">@lang('pumperdashboard::lang.pumper-dashboard_activity_report')</a>
            @endif --}}

            {{-- @if ($day_end_settlement)
                <a class="collapse-item {{ $request->segment(1) == 'settlement' && $request->segment(2) == 'day-end-settlement' ? 'active' : '' }}"
                    href="{{ action('\Modules\pumper-dashboard\Http\Controllers\DayEndSettlementController@index') }}">@lang('pumperdashboard::lang.day_end_settlement')</a>
            @endif

            @if ($pumper-dashboard_sms_notifications)
                @can('pumper-dashboard_sms_notifications')
                    <a class="collapse-item {{ $request->segment(1) == 'settlement' && $request->segment(2) == 'pumper-dashboard_sms_notifications' ? 'active' : '' }}"
                        href="{{ action('\Modules\pumper-dashboard\Http\Controllers\pumper-dashboardNotificationTemplateController@index') }}">@lang('pumperdashboard::lang.pumper-dashboard_sms_notifications')</a>
                @endcan
            @endif
            @if ($pumper-dashboard_whatsapp)
                <a class="collapse-item {{ $request->segment(1) == 'settlement' && $request->segment(2) == 'pumper-dashboard_sms_whatsapp' ? 'active' : '' }}"
                    href="{{ action('\Modules\pumper-dashboard\Http\Controllers\pumper-dashboardWhatsAppTemplateController@index') }}">@lang('pumperdashboard::lang.pumper-dashboard_sms_whatsapp')</a>
            @endif --}}
            {{-- @if ($blocked_pump_operators)
                <a class="collapse-item {{ $request->segment(1) == 'pump-operator' && $request->segment(2) == 'blocked-pump-operators' ? 'active' : '' }}"
                    href="{{ action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorController@blockedPumperLoginAttempt') }}">Blocked
                    Pump Operator Logins</a>
            @endif
            
            @if ($tanks_transaction_details)
                <a class="collapse-item {{ $request->segment(1) == 'pumper-dashboard' && $request->segment(2) == 'tanks-transaction-details' ? 'active' : '' }}"
                    href="{{ action('\Modules\pumper-dashboard\Http\Controllers\TanksTransactionDetailController@index') }}">Tanks Transaction Details</a>
            @endif --}}
            
            {{-- @if ($tanks_transaction_summary)
                <a class="collapse-item {{ $request->segment(1) == 'pumper-dashboard' && $request->segment(2) == 'tanks-transaction-summary' ? 'active' : '' }}"
                    href="{{ action('\Modules\pumper-dashboard\Http\Controllers\TanksTransactionDetailController@tankTransactionSummary') }}">Tanks Transaction Summary</a>
            @endif
            
            @if ($customer_bill_vat_prefix)
                <a class="collapse-item {{ $request->segment(1) == 'pumper-dashboard' && $request->segment(2) == 'prefixes' ? 'active' : '' }}"
                    href="{{ action('\Modules\pumper-dashboard\Http\Controllers\CustomerBillVatPrefixController@index') }}">Customer Bill VAT Prefix</a>
            @endif
            
            @if ($pumper-dashboard_notification_template)
                <a class="collapse-item {{ $request->segment(1) == 'pumper-dashboard' && $request->segment(2) == 'notification-templates' ? 'active' : '' }}"
                    href="{{ action('\Modules\pumper-dashboard\Http\Controllers\pumper-dashboardNotificationTemplateController@index') }}">Notification Templates</a>
            @endif --}}
            </div>
        </div>
    </li>
@endif




