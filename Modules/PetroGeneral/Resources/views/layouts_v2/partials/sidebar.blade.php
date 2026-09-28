{{-- PG029 Petro General clean sidebar partial (v2 layout) --}}
@php
    $canAccessPetroGeneral = \Modules\PetroGeneral\Support\PetroGeneralAccess::isVisibleInSidebar();

    /*
     * MA-002 (IS-1917): each menu entry now honours its Manage Page toggle.
     *
     * Until now only the MODULE was gated - once Petro General was enabled,
     * all ten entries appeared whatever was ticked on Superadmin / Manage.
     * Turning off Tank Transfers or Daily Status on the Manage page changed
     * the permission but left the menu item sitting there.
     *
     * The toggles live in the business subscription's package_details, which
     * is the same store the main sidebar reads for every other module - so
     * this follows the existing pattern rather than inventing a second one.
     *
     * DELIBERATELY PERMISSIVE ON ABSENCE: a key that is not present in
     * package_details counts as ENABLED. Businesses subscribed before a
     * toggle existed have no entry for it, and treating "missing" as "off"
     * would silently remove menus that have always worked. Only an explicit
     * 0 hides an entry.
     */
    $pgPackage = [];
    try {
        $pgBusinessId = request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id');
        $pgSubscription = \Modules\Superadmin\Entities\Subscription::current_subscription($pgBusinessId);
        $pgPackage = ! empty($pgSubscription) ? (array) $pgSubscription->package_details : [];
    } catch (\Throwable $e) {
        // No subscription resolved - fall through and show everything, which is
        // how this partial behaved before.
        $pgPackage = [];
    }

    $pgEnabled = static function (?string $key) use ($pgPackage): bool {
        if ($key === null || $key === '') {
            return true;
        }

        // Missing means enabled. Only an explicit falsy value hides the entry.
        return ! array_key_exists($key, $pgPackage) || ! empty($pgPackage[$key]);
    };
    $pgSegmentActive = request()->segment(1) === 'petro-general';
    $petroGeneralUrl = static function (string $routeName, string $path): string {
        return \Illuminate\Support\Facades\Route::has($routeName)
            ? route($routeName)
            : url($path);
    };
@endphp

@if($canAccessPetroGeneral)
<li class="nav-item">
    <a class="nav-link collapsed {{ $pgSegmentActive ? 'active active-sub' : '' }}" href="#" data-toggle="collapse" data-target="#petro-general-menu" aria-expanded="true" aria-controls="petro-general-menu">
        <i class="fa fa-tint fa-lg"></i>
        <span>@lang('petrogeneral::lang.petro_general')</span>
    </a>
    <div id="petro-general-menu" class="collapse {{ $pgSegmentActive ? 'show' : '' }}" aria-labelledby="headingPetroGeneral" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">@lang('petrogeneral::lang.petro_general'):</h6>
            @if(\App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petrogeneral_petro_dashboard_new'))
            <a class="collapse-item {{ request()->is('petro-general/petro-dashboard*') ? 'active' : '' }}" href="{{ $petroGeneralUrl('petrogeneral.petro_dashboard.index', '/petro-general/petro-dashboard') }}">@lang('petrogeneral::lang.petro_dashboard')</a>
            @endif
            @if($pgEnabled('petro_dashboard'))
            <a class="collapse-item {{ request()->is('petro-general/dashboard*') ? 'active' : '' }}" href="{{ $petroGeneralUrl('petrogeneral.dashboard.index', '/petro-general/dashboard') }}">@lang('petrogeneral::lang.dashboard')</a>
            @endif
            <a class="collapse-item {{ request()->is('petro-general/tank-management*') ? 'active' : '' }}" href="{{ $petroGeneralUrl('petrogeneral.tank_management.index', '/petro-general/tank-management') }}">@lang('petrogeneral::lang.tank_management')</a>
            @if($pgEnabled('pump_management'))
            <a class="collapse-item {{ request()->is('petro-general/pump-management*') ? 'active' : '' }}" href="{{ $petroGeneralUrl('petrogeneral.pump_management.index', '/petro-general/pump-management') }}">@lang('petrogeneral::lang.pump_management')</a>
            @endif
            @if($pgEnabled('pumper_management'))
            <a class="collapse-item {{ request()->is('petro-general/pump-operators*') || request()->is('petro-general/pumper-management*') ? 'active' : '' }}" href="{{ $petroGeneralUrl('petrogeneral.pumper_management.index', '/petro-general/pumper-management') }}">@lang('petrogeneral::lang.pumper_management')</a>
            @endif
            @if($pgEnabled('dip_management'))
            <a class="collapse-item {{ request()->is('petro-general/dip-management*') || request()->is('petro-general/dip-management-general*') ? 'active' : '' }}" href="{{ $petroGeneralUrl('petrogeneral.dip_management.index', '/petro-general/dip-management-general') }}">@lang('petrogeneral::lang.dip_management')</a>
            @endif
            @if(\App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petrogeneral_daily_status_general'))
            <a class="collapse-item {{ request()->is('petro-general/daily-status*') || request()->is('petro-general/daily-status-general*') ? 'active' : '' }}" href="{{ $petroGeneralUrl('petrogeneral.daily_status.index', '/petro-general/daily-status-general') }}">@lang('petrogeneral::lang.daily_status_report')</a>
            @endif
            @if($pgEnabled('tank_transfer'))
            {{-- HIDDEN ON REQUEST (11 Aug 2026).

                 Tank Transfers is reachable from Petro General / Tank Management,
                 so the sidebar entry was asked to be taken off the menu.

                 TO RESTORE: delete the two marker lines below - the @if line and
                 its matching @endif - leaving the <a> untouched. Nothing else has
                 been removed, and the original pgEnabled('tank_transfer') gate on
                 the outer line is still in place, so the Manage page switch keeps
                 working exactly as before once this is reverted. --}}
            @if(false) {{-- MARKER: delete this line and its @endif to restore Tank Transfers --}}
            <a class="collapse-item {{ request()->is('petro-general/tank-transfers*') || request()->is('petro-general/tank-transfer*') ? 'active' : '' }}" href="{{ $petroGeneralUrl('petrogeneral.tank_transfer.index', '/petro-general/tank-transfers-general') }}">@lang('petrogeneral::lang.list_tank_transfers')</a>
            @endif {{-- MARKER: delete this line too to restore Tank Transfers --}}
            @endif
            <a class="collapse-item {{ request()->is('petro-general/user-activity*') ? 'active' : '' }}" href="{{ $petroGeneralUrl('petrogeneral.user_activity.index', '/petro-general/user-activity-general') }}">@lang('petrogeneral::lang.user_activity_petro')</a>
            @if($pgEnabled('petro_sms_notifications'))
            <a class="collapse-item {{ request()->is('petro-general/sms-notifications*') || request()->is('petro-general/notification-templates*') ? 'active' : '' }}" href="{{ $petroGeneralUrl('petrogeneral.sms_notifications.index', '/petro-general/sms-notifications-general') }}">@lang('petrogeneral::lang.petro_sms_notifications')</a>
            @endif
            @if(\App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petrogeneral_day_end_settlement'))
            <a class="collapse-item {{ request()->is('petro-general/day-end-settlement*') ? 'active' : '' }}" href="{{ $petroGeneralUrl('petrogeneral.day_end_settlement.index', '/petro-general/day-end-settlement') }}">@lang('petrogeneral::lang.day_end_settlement')</a>
            @endif
            @if(\App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled('petrogeneral_settings'))
            <a class="collapse-item {{ request()->is('petro-general/settings*') ? 'active' : '' }}" href="{{ $petroGeneralUrl('petrogeneral.settings.index', '/petro-general/settings') }}">@lang('petrogeneral::lang.petro_settings')</a>
            @endif
        </div>
    </div>
</li>
@endif
