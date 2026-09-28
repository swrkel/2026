@php
    $ranBusinessId = (int) session('user.business_id');
    $ranEnabled = auth()->check() && \App\Utils\SidebarPermissionUtil::isEnabled('ran', $ranBusinessId);
    $ranPageEnabled = static fn (string $permission): bool => \App\Utils\SidebarPermissionUtil::isEnabled($permission, $ranBusinessId);
@endphp
@if($ranEnabled)
<li class="treeview {{ request()->is('ran*') ? 'active' : '' }}">
    <a href="#"><i class="fa fa-diamond"></i> <span>Ran</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
    <ul class="treeview-menu">
        @if(Route::has('ran.dashboard') && $ranPageEnabled('ran.dashboard.view'))
            <li class="{{ request()->routeIs('ran.dashboard') ? 'active' : '' }}"><a href="{{ route('ran.dashboard') }}"><i class="fa fa-dashboard"></i> Dashboard</a></li>
        @endif
        @if(Route::has('ran.items.index') && $ranPageEnabled('ran.items.view'))
            <li><a href="{{ route('ran.items.index') }}"><i class="fa fa-diamond"></i> Jewellery Items</a></li>
        @endif
        @if(Route::has('ran.purchases.index') && $ranPageEnabled('ran.purchases.view'))
            <li><a href="{{ route('ran.purchases.index') }}"><i class="fa fa-truck"></i> Purchases</a></li>
        @endif
        @if(Route::has('ran.production.index') && $ranPageEnabled('ran.production.view'))
            <li><a href="{{ route('ran.production.index') }}"><i class="fa fa-cogs"></i> Production</a></li>
        @endif
        @if(Route::has('ran.inventory.index') && $ranPageEnabled('ran.inventory.view'))
            <li><a href="{{ route('ran.inventory.index') }}"><i class="fa fa-cubes"></i> Inventory & Storage</a></li>
        @endif
        @if(Route::has('ran.sales.index') && $ranPageEnabled('ran.sales.view'))
            <li><a href="{{ route('ran.sales.index') }}"><i class="fa fa-shopping-bag"></i> Sales</a></li>
        @endif
        @if(Route::has('ran.documents.index') && $ranPageEnabled('ran.documents.view'))
            <li><a href="{{ route('ran.documents.index') }}"><i class="fa fa-file-text-o"></i> Documents</a></li>
        @endif
        @if(Route::has('ran.reports.index') && $ranPageEnabled('ran.reports.view'))
            <li><a href="{{ route('ran.reports.index') }}"><i class="fa fa-bar-chart"></i> Reports</a></li>
        @endif
        @if(Route::has('ran.settings.index') && $ranPageEnabled('ran.settings.manage'))
            <li><a href="{{ route('ran.settings.index') }}"><i class="fa fa-cog"></i> Settings</a></li>
        @endif
    </ul>
</li>
@endif
