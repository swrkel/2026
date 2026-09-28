@php
    $pcnUser = auth()->user();
    $pcnBusinessId = (int) (session('business.id') ?: session('user.business_id'));
    $pcnAdmin = $pcnUser && ($pcnUser->can('superadmin') || ($pcnBusinessId > 0 && $pcnUser->hasRole('Admin#' . $pcnBusinessId)));
    $pcnCanAccess = $pcnAdmin || ($pcnUser && $pcnUser->canAny([
        'pricechangenew.dashboard.view',
        'pricechangenew.changes.view',
        'pricechangenew.changes.create',
        'pricechangenew.approvals.view',
        'pricechangenew.approvals.approve',
        'pricechangenew.approvals.reject',
        'pricechangenew.reports.view',
        'pricechangenew.settings.manage',
    ]));
    $pcnActive = request()->is('pricechangenew') || request()->is('pricechangenew/*');
@endphp

@if($pcnCanAccess)
<li class="nav-item {{ $pcnActive ? 'active active-sub' : '' }}" data-auto-module="price_change_new">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#pricechangenew-menu" aria-expanded="{{ $pcnActive ? 'true' : 'false' }}">
        <i class="fa fa-tags"></i>
        <span>Price Change - New</span>
    </a>
    <div id="pricechangenew-menu" class="collapse {{ $pcnActive ? 'show' : '' }}" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Price Change - New:</h6>
            @if($pcnAdmin || $pcnUser->can('pricechangenew.dashboard.view'))
                <a class="collapse-item {{ request()->routeIs('pricechangenew.dashboard') ? 'active' : '' }}" href="{{ route('pricechangenew.dashboard') }}">Dashboard</a>
            @endif
            @if($pcnAdmin || $pcnUser->can('pricechangenew.changes.view'))
                <a class="collapse-item {{ request()->routeIs('pricechangenew.changes.*') ? 'active' : '' }}" href="{{ route('pricechangenew.changes.index') }}">List Price Changes</a>
            @endif
            @if($pcnAdmin || $pcnUser->can('pricechangenew.changes.create'))
                <a class="collapse-item {{ request()->routeIs('pricechangenew.changes.create') ? 'active' : '' }}" href="{{ route('pricechangenew.changes.create') }}">Add Price Change</a>
            @endif
            @if($pcnAdmin || $pcnUser->canAny(['pricechangenew.approvals.view','pricechangenew.approvals.approve','pricechangenew.approvals.reject']))
                <a class="collapse-item {{ request()->routeIs('pricechangenew.approvals.*') ? 'active' : '' }}" href="{{ route('pricechangenew.approvals.index') }}">Approval Queue</a>
            @endif
            @if($pcnAdmin || $pcnUser->can('pricechangenew.reports.view'))
                <a class="collapse-item {{ request()->routeIs('pricechangenew.reports.*') ? 'active' : '' }}" href="{{ route('pricechangenew.reports.history') }}">Application History</a>
            @endif
            @if($pcnAdmin || $pcnUser->can('pricechangenew.settings.manage'))
                <a class="collapse-item {{ request()->routeIs('pricechangenew.settings.*') ? 'active' : '' }}" href="{{ route('pricechangenew.settings.index') }}">Settings</a>
            @endif
        </div>
    </div>
</li>
@endif
