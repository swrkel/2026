{{-- PG029 Petro General clean sidebar partial (classic layout) --}}
@php
    $canAccessPetroGeneral = \Modules\PetroGeneral\Support\PetroGeneralAccess::isVisibleInSidebar();
    $pgSegmentActive = request()->segment(1) === 'petro-general';
    $petroGeneralUrl = static function (string $routeName, string $path): string {
        return \Illuminate\Support\Facades\Route::has($routeName)
            ? route($routeName)
            : url($path);
    };
@endphp

@if($canAccessPetroGeneral)
<li class="treeview {{ $pgSegmentActive ? 'active active-sub' : '' }}">
    <a href="#">
        <i class="fa fa-tint"></i>
        <span>@lang('petrogeneral::lang.petro_general')</span>
        <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
    </a>
    <ul class="treeview-menu">
        <li class="{{ request()->is('petro-general/petro-dashboard*') ? 'active' : '' }}">
            @if(\App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petrogeneral_petro_dashboard_new'))
<a href="{{ $petroGeneralUrl('petrogeneral.petro_dashboard.index', '/petro-general/petro-dashboard') }}"><i class="fa fa-tachometer"></i> @lang('petrogeneral::lang.petro_dashboard')</a>
@endif
        </li>
        <li class="{{ request()->is('petro-general/dashboard*') ? 'active' : '' }}">
            @if(\App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petrogeneral_dashboard'))
<a href="{{ $petroGeneralUrl('petrogeneral.dashboard.index', '/petro-general/dashboard') }}"><i class="fa fa-dashboard"></i> @lang('petrogeneral::lang.dashboard')</a>
@endif
        </li>
        <li class="{{ request()->is('petro-general/tank-management*') ? 'active' : '' }}">
            @if(\App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petrogeneral_tank_management'))
<a href="{{ $petroGeneralUrl('petrogeneral.tank_management.index', '/petro-general/tank-management') }}"><i class="fa fa-database"></i> @lang('petrogeneral::lang.tank_management')</a>
@endif
        </li>
        <li class="{{ request()->is('petro-general/pump-management*') ? 'active' : '' }}">
            @if(\App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petrogeneral_pump_management'))
<a href="{{ $petroGeneralUrl('petrogeneral.pump_management.index', '/petro-general/pump-management') }}"><i class="fa fa-road"></i> @lang('petrogeneral::lang.pump_management')</a>
@endif
        </li>
        <li class="{{ request()->is('petro-general/pump-operators*') || request()->is('petro-general/pumper-management*') ? 'active' : '' }}">
            @if(\App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petrogeneral_pumper_management'))
<a href="{{ $petroGeneralUrl('petrogeneral.pumper_management.index', '/petro-general/pumper-management') }}"><i class="fa fa-users"></i> @lang('petrogeneral::lang.pumper_management')</a>
@endif
        </li>
        <li class="{{ request()->is('petro-general/dip-management*') || request()->is('petro-general/dip-management-general*') ? 'active' : '' }}">
            @if(\App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petrogeneral_dip_management'))
<a href="{{ $petroGeneralUrl('petrogeneral.dip_management.index', '/petro-general/dip-management-general') }}"><i class="fa fa-bar-chart"></i> @lang('petrogeneral::lang.dip_management')</a>
@endif
        </li>
        <li class="{{ request()->is('petro-general/daily-status*') || request()->is('petro-general/daily-status-general*') ? 'active' : '' }}">
            @if(\App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petrogeneral_daily_status_general'))
<a href="{{ $petroGeneralUrl('petrogeneral.daily_status.index', '/petro-general/daily-status-general') }}"><i class="fa fa-calendar-check-o"></i> @lang('petrogeneral::lang.daily_status_report')</a>
@endif
        </li>
        <li class="{{ request()->is('petro-general/tank-transfers*') || request()->is('petro-general/tank-transfer*') ? 'active' : '' }}">
        {{-- HIDDEN ON REQUEST (11 Aug 2026).

                 Tank Transfers is reachable from Petro General / Tank Management,
                 so the sidebar entry was asked to be taken off the menu.

                 TO RESTORE: delete the two marker lines below - the @if line and
                 its matching @endif - leaving the <a> untouched. Nothing else has
                 been removed, and the original pgEnabled('tank_transfer') gate on
                 the outer line is still in place, so the Manage page switch keeps
                 working exactly as before once this is reverted. --}}
        @if(false) {{-- MARKER: delete this line and its @endif to restore Tank Transfers --}}
            @if(\App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petrogeneral_tank_transfer'))
<a href="{{ $petroGeneralUrl('petrogeneral.tank_transfer.index', '/petro-general/tank-transfers-general') }}"><i class="fa fa-exchange"></i> @lang('petrogeneral::lang.list_tank_transfers')</a>
@endif
        @endif {{-- MARKER: delete this line too to restore Tank Transfers --}}
        </li>
        <li class="{{ request()->is('petro-general/user-activity*') ? 'active' : '' }}">
            @if(\App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petrogeneral_user_activity'))
<a href="{{ $petroGeneralUrl('petrogeneral.user_activity.index', '/petro-general/user-activity-general') }}"><i class="fa fa-history"></i> @lang('petrogeneral::lang.user_activity_petro')</a>
@endif
        </li>
        <li class="{{ request()->is('petro-general/sms-notifications*') || request()->is('petro-general/notification-templates*') ? 'active' : '' }}">
            @if(\App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petrogeneral_sms_notifications'))
<a href="{{ $petroGeneralUrl('petrogeneral.sms_notifications.index', '/petro-general/sms-notifications-general') }}"><i class="fa fa-envelope"></i> @lang('petrogeneral::lang.petro_sms_notifications')</a>
@endif
        </li>
        <li class="{{ request()->is('petro-general/day-end-settlement*') ? 'active' : '' }}">
            @if(\App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petrogeneral_day_end_settlement'))
<a href="{{ $petroGeneralUrl('petrogeneral.day_end_settlement.index', '/petro-general/day-end-settlement') }}"><i class="fa fa-calendar-check-o"></i> @lang('petrogeneral::lang.day_end_settlement')</a>
@endif
        </li>
        <li class="{{ request()->is('petro-general/settings*') ? 'active' : '' }}">
            @if(\App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petrogeneral_settings'))
<a href="{{ $petroGeneralUrl('petrogeneral.settings.index', '/petro-general/settings') }}"><i class="fa fa-cog"></i> @lang('petrogeneral::lang.petro_settings')</a>
@endif
        </li>
    </ul>
</li>
@endif
