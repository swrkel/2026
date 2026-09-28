@once
@php
    /*
     * SW standalone sidebar.
     *
     * Parent visibility is controlled only by the shared Manage Side Bar
     * resolver (plus the genuine Super Admin bypass inside that resolver).
     * Child links keep the SW module's own application permissions; the
     * global sidebar/page guard then applies Manage Page New and managed-role
     * restrictions as the second and third levels.
     */
    $__swBusinessId = (int) session('user.business_id');
    $__swUser = auth()->user();
    $__swModuleEnabled = false;
    $__swIsAdmin = false;
    $__swSuperAdmin = false;

    try {
        $__swModuleEnabled = \App\Utils\SidebarPermissionUtil::isVisibleInSidebar('sw', $__swBusinessId);
        $__swSuperAdmin = \App\Utils\SidebarPermissionUtil::hasSuperAdminBypass();
        $__swIsAdmin = $__swUser
            && $__swBusinessId > 0
            && $__swUser->hasRole('Admin#' . $__swBusinessId);
    } catch (\Throwable $__swSidebarContextException) {
        $__swModuleEnabled = false;
    }

    $__swPrivileged = $__swSuperAdmin || $__swIsAdmin;

    $__swCan = static function (array $permissions = []) use ($__swUser, $__swPrivileged): bool {
        if ($__swPrivileged) {
            return true;
        }

        if (! $__swUser) {
            return false;
        }

        foreach ($permissions as $__swPermission) {
            try {
                if ($__swUser->can($__swPermission)) {
                    return true;
                }
            } catch (\Throwable $__swPermissionException) {
                // Continue checking the remaining compatible SW permissions.
            }
        }

        return false;
    };

    $__swPages = [
        'operators' => $__swCan([
            'sw.operators.view',
            'sw.operators.edit',
            'sw.excess_shortage.view',
            'sw.excess_shortage.edit',
        ]),
        'payments' => $__swCan([
            'sw.daily_cash.view',
            'sw.daily_cash.edit',
            'sw.daily_credit_sales.view',
            'sw.daily_credit_sales.edit',
            'sw.daily_cards.view',
            'sw.daily_cards.edit',
            'sw.daily_shortage_excess.view',
            'sw.daily_shortage_excess.edit',
            'sw.daily_cheques.view',
            'sw.daily_cheques.edit',
            'sw.collection_summary.view',
        ]),
        'daily_shifts' => $__swCan([
            'sw.daily_cash_status.view',
            'sw.daily_shift.view',
            'sw.daily_shift.edit',
            'sw.collection_summary.view',
            'sw.settlement.view',
        ]),
        'shifts' => $__swCan([
            'sw.daily_shift.view',
            'sw.daily_shift.edit',
            'sw.daily_cash_status.view',
        ]),
        'settlements' => $__swCan([
            'sw.settlement.view',
            'sw.settlement.create',
        ]),
        'logs' => $__swCan([
            'sw.logs.view',
        ]),
    ];

    $__swActive = request()->is('sw') || request()->is('sw/*');

    $__swLink = static function (string $routeName): ?string {
        try {
            return \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName) : null;
        } catch (\Throwable $__swRouteException) {
            return null;
        }
    };
@endphp

@if($__swModuleEnabled)
<li class="nav-item {{ $__swActive ? 'active active-sub' : '' }}"
    id="sw-standalone-sidebar-module"
    data-sidebar-module="sw">
    <a class="nav-link {{ $__swActive ? '' : 'collapsed' }}"
       href="#"
       data-toggle="collapse"
       data-target="#sw-standalone-menu"
       aria-expanded="{{ $__swActive ? 'true' : 'false' }}"
       aria-controls="sw-standalone-menu">
        <i class="fa fa-clock-o"></i>
        <span>@lang('sw::lang.sw_module')</span>
    </a>

    <div id="sw-standalone-menu"
         class="collapse {{ $__swActive ? 'show' : '' }}"
         aria-labelledby="headingPages"
         data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">@lang('sw::lang.sw_module'):</h6>

            @if($__swPages['operators'] && ($__swUrl = $__swLink('sw.operators.index')))
                <a class="collapse-item {{ request()->routeIs('sw.operators.*') ? 'active' : '' }}"
                   href="{{ $__swUrl }}">@lang('sw::lang.sw_operators')</a>
            @endif

            @if($__swPages['payments'] && ($__swUrl = $__swLink('sw.payments.index')))
                <a class="collapse-item {{ request()->routeIs('sw.payments.*') ? 'active' : '' }}"
                   href="{{ $__swUrl }}">@lang('sw::lang.sw_payments')</a>
            @endif

            @if($__swPages['daily_shifts'] && ($__swUrl = $__swLink('sw.daily-shifts.index')))
                <a class="collapse-item {{ request()->routeIs('sw.daily-shifts.*') || request()->routeIs('sw.list-shifts.*') ? 'active' : '' }}"
                   href="{{ $__swUrl }}">@lang('sw::lang.sw_daily_shifts')</a>
            @endif

            @if($__swPages['shifts'] && ($__swUrl = $__swLink('sw.shifts.index')))
                <a class="collapse-item {{ request()->routeIs('sw.shifts.*') ? 'active' : '' }}"
                   href="{{ $__swUrl }}">@lang('sw::lang.sw_shifts')</a>
            @endif

            @if($__swPages['settlements'] && ($__swUrl = $__swLink('sw.settlements.index')))
                <a class="collapse-item {{ request()->routeIs('sw.settlements.*') ? 'active' : '' }}"
                   href="{{ $__swUrl }}">@lang('sw::lang.sw_settlements')</a>
            @endif

            @if($__swPages['logs'] && ($__swUrl = $__swLink('sw.logs.index')))
                <a class="collapse-item {{ request()->routeIs('sw.logs.*') ? 'active' : '' }}"
                   href="{{ $__swUrl }}">@lang('sw::lang.sw_logs')</a>
            @endif
        </div>
    </div>
</li>
@endif
@endonce
