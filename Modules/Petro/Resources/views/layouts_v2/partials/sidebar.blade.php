@php

    $module_array = [
        'petro_dashboard' => 0,
        'petro_daily_status' => 0,
        'tank_transfer' => 0,
        'petro_task_management' => 0,
        'pumper_management' => 0,
        'daily_collection' => 0,
        'settlement' => 0,
        'petro_settlement' => 0,
        'list_settlement' => 0,
        'dip_management' => 0,
        'pump_operator_dashboard' => 0,
        'pumper_dashboard_settings' => 0,
        'list_tank_transfer' => 0,
        'petro_activity_report' => 0,
        'day_end_settlement' => 0,
        'petro_whatsapp' => 0,
        'blocked_pump_operators' => 0,
        'tanks_transaction_details' => 0,
        'tanks_transaction_summary' => 0,
        'customer_bill_vat_prefix' => 0,
        'petro_notification_template' => 0
    ];

    foreach ($module_array as $key => $module_value) {
        ${$key} = 0;
    }

    $business_id = request()->session()->get('user.business_id');
    $subscription = Modules\Superadmin\Entities\Subscription::current_subscription($business_id);
    $stock_adjustment = 0;

    $package_details = [];
    $petro_module_enabled = 0;

    if (!empty($subscription)) {
        $package_details = is_array($subscription->package_details) ? $subscription->package_details : (array) $subscription->package_details;
        $petro_module_enabled = !empty($package_details['enable_petro_module']) || !empty($package_details['petro_module']) || !empty($package_details['petro']);
        $stock_adjustment = $package_details['stock_adjustment'] ?? 0;

        foreach ($module_array as $key => $module_value) {
            $matched = false;
            foreach ([$key, 'enable_' . $key, 'enable_petro_' . $key, 'enable_petro_' . str_replace('petro_', '', $key)] as $possible_key) {
                if (array_key_exists($possible_key, $package_details)) {
                    ${$key} = $package_details[$possible_key];
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                ${$key} = 0;
            }
        }
    }


    /* TEMP SIDEBAR_060: bypass package/manage filters for Petro. */
    $petro_module_enabled = 1;
    foreach (array_keys($module_array) as $__petro060_key) {
        ${$__petro060_key} = 1;
    }
    $settlement_pd = 1;
    $list_settlement_pd = 1;
@endphp

@if (\Modules\Petro\Support\PetroAccess::isVisibleInSidebar((int) $business_id))

<li class="nav-item">
    <a class="nav-link collapsed {{ in_array($request->segment(1), ['petro']) && $request->segment(2) != 'issue-customer-bill' ? 'active active-sub' : '' }}"
        href="#" data-toggle="collapse" data-target="#petro-menu" aria-expanded="true" aria-controls="petro-menu">
        <i class="fa fa-tint fa-lg"></i>
        <span>@lang('petro::lang.petro')</span>
    </a>
    <div id="petro-menu" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">@lang('petro::lang.petro'):</h6>
            @if ($petro_dashboard)
                <a class="collapse-item {{ $request->segment(1) == 'petro' && $request->segment(2) == 'dashboard' ? 'active' : '' }}"
                    href="{{ action('\Modules\Petro\Http\Controllers\PetroController@index') }}">@lang('petro::lang.dashboard')</a>
            @endif

            {{-- @if ($pump_operator_dashboard)
                <a class="collapse-item {{ $request->segment(1) == 'pump-operator' && $request->segment(2) == 'dashboard' ? 'active' : '' }}"
                    href="{{ action('\Modules\Petro\Http\Controllers\PumpOperatorController@dashboard') }}">@lang('petro::lang.pump_operator_dashboard')</a>
            @endif --}}

            @if ($petro_task_management)
                <a class="collapse-item {{ $request->segment(1) == 'petro' && $request->segment(2) == 'tank-management' ? 'active' : '' }}"
                    href="{{ action('\Modules\Petro\Http\Controllers\FuelTankController@index') }}">@lang('petro::lang.tank_management')</a>
            @endif
            @if (!empty($pumper_management) || !empty($package_details['pump_management']) || !empty($package_details['enable_petro_pump_management']))
                <a class="collapse-item {{ $request->segment(1) == 'petro' && $request->segment(2) == 'pump-management' ? 'active' : '' }}"
                    href="{{ action('\Modules\Petro\Http\Controllers\PumpController@index') }}">@lang('petro::lang.pump_management')</a>
            @endif
            @if ($pumper_management)
                <a class="collapse-item {{ $request->segment(1) == 'petro' && $request->segment(2) == 'pump-operators' && $request->segment(3) == '' ? 'active' : '' }}"
                    href="{{ action('\Modules\Petro\Http\Controllers\PumpOperatorController@index') }}">@lang('petro::lang.pumper_management')</a>
            @endif
            {{-- @if ($daily_collection)
                <a class="collapse-item {{ $request->segment(1) == 'petro' && $request->segment(2) == 'daily-collection' ? 'active' : '' }}" href="{{action('\Modules\Petro\Http\Controllers\DailyCollectionController@index')}}">@lang('petro::lang.daily_collection')</a>
            @endif --}}


            @if ($petro_settlement)
                <a class="collapse-item {{ $request->segment(1) == 'petro' && $request->segment(2) == 'settlement' && $request->segment(3) == 'create' ? 'active' : '' }}"
                    href="{{ action('\Modules\Petro\Http\Controllers\SettlementController@create') }}">@lang('petro::lang.settlement')</a>
            @endif
            @if ($list_settlement)
                <a class="collapse-item {{ $request->segment(1) == 'petro' && $request->segment(2) == 'settlement' && $request->segment(3) == '' ? 'active' : '' }}"
                    href="{{ action('\Modules\Petro\Http\Controllers\SettlementController@index') }}">@lang('petro::lang.list_settlement')</a>
            @endif
            @php
                // Settlement PD permissions - check for settlement_pd and list_settlement_pd
                $settlement_pd = 0;
                $list_settlement_pd = 0;
                if (!empty($subscription)) {
                    $settlement_pd = $package_details['settlement_pd'] ?? 0;
                    $list_settlement_pd = $package_details['list_settlement_pd'] ?? 0;
                }
            @endphp
            
            @if ($settlement_pd)
                <a class="collapse-item {{ $request->segment(1) == 'petro' && $request->segment(2) == 'settlement-pd' && $request->segment(3) == 'create' ? 'active' : '' }}"
                    href="{{ action('\Modules\Petro\Http\Controllers\SettlementPDController@create') }}">
                    @lang('petro::lang.settlement_pd')
                </a>
            @endif

            @if ($list_settlement_pd)
                <a class="collapse-item {{ $request->segment(1) == 'petro' && $request->segment(2) == 'settlement-pd' && $request->segment(3) == '' ? 'active' : '' }}"
                    href="{{ action('\Modules\Petro\Http\Controllers\SettlementPDController@index') }}">
                    @lang('petro::lang.list_settlement_pd')
                </a>
            @endif

            @if ($dip_management)
                <a class="collapse-item {{ $request->segment(1) == 'petro' && $request->segment(2) == 'dip-management' && $request->segment(3) == '' ? 'active' : '' }}"
                    href="{{ action('\Modules\Petro\Http\Controllers\DipManagementController@index') }}">@lang('petro::lang.dip_management')</a>
            @endif

            @if ($petro_daily_status)
                <a class="collapse-item {{ $request->segment(1) == 'petro' && $request->segment(2) == 'daily-status_report' ? 'active' : '' }}"
                    href="{{ action('\Modules\Petro\Http\Controllers\DailyStatusReportController@index') }}">@lang('petro::lang.daily_status_report')</a>
            @endif

            {{-- @if (!empty($tank_transfer) && $tank_transfer) --}}
            @if (!empty($list_tank_transfer) && $list_tank_transfer)
                <a class="collapse-item {{ $request->segment(1) == 'petro' && $request->segment(2) == 'tank-transfers' ? 'active' : '' }}"
                    href="{{ action('\Modules\Petro\Http\Controllers\TankTransferController@index') }}">@lang('petro::lang.list_tank_transfer')</a>
            @endif


            @if ($pump_operator_dashboard && $pumper_dashboard_settings)
                <a class="collapse-item {{ $request->segment(1) == 'pump-operator' && $request->segment(2) == 'dashboard' ? 'active' : '' }}"
                    href="{{ action('\Modules\Petro\Http\Controllers\PumpOperatorController@setting_dash') }}">@lang('petro::lang.pump_dashboard_settings')</a>
            @endif

            @if ($petro_activity_report)
                <a class="collapse-item {{ $request->segment(1) == 'settlement' && $request->segment(2) == 'activity-report' ? 'active' : '' }}"
                    href="{{ action('\Modules\Petro\Http\Controllers\SettlementController@getUserActivityReport') }}">@lang('petro::lang.petro_activity_report')</a>
            @endif

            @if ($day_end_settlement)
                <a class="collapse-item {{ $request->segment(1) == 'settlement' && $request->segment(2) == 'day-end-settlement' ? 'active' : '' }}"
                    href="{{ action('\Modules\Petro\Http\Controllers\DayEndSettlementController@index') }}">@lang('petro::lang.day_end_settlement')</a>
            @endif

            @if ($petro_sms_notifications)
                @can('petro_sms_notifications')
                    <a class="collapse-item {{ $request->segment(1) == 'settlement' && $request->segment(2) == 'petro_sms_notifications' ? 'active' : '' }}"
                        href="{{ action('\Modules\Petro\Http\Controllers\PetroNotificationTemplateController@index') }}">@lang('petro::lang.petro_sms_notifications')</a>
                @endcan
            @endif
            @if ($petro_whatsapp)
                <a class="collapse-item {{ $request->segment(1) == 'settlement' && $request->segment(2) == 'petro_sms_whatsapp' ? 'active' : '' }}"
                    href="{{ action('\Modules\Petro\Http\Controllers\PetroWhatsAppTemplateController@index') }}">@lang('petro::lang.petro_sms_whatsapp')</a>
            @endif
            @if ($blocked_pump_operators)
                <a class="collapse-item {{ $request->segment(1) == 'pump-operator' && $request->segment(2) == 'blocked-pump-operators' ? 'active' : '' }}"
                    href="{{ action('\Modules\Petro\Http\Controllers\PumpOperatorController@blockedPumperLoginAttempt') }}">Blocked
                    Pump Operator Logins</a>
            @endif
            
            @if ($customer_bill_vat_prefix)
                <a class="collapse-item {{ $request->segment(1) == 'petro' && $request->segment(2) == 'prefixes' ? 'active' : '' }}"
                    href="{{ action('\Modules\Petro\Http\Controllers\CustomerBillVatPrefixController@index') }}">@lang('petro::lang.petro_settings')</a>
            @endif
        </div>
    </div>
</li>
@endif
