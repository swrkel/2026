@php
    $ranEnabled = auth()->check() && \App\Utils\SidebarPermissionUtil::isEnabled('ran', (int) session('user.business_id'));
@endphp
@if($ranEnabled)
<li class="treeview {{ request()->is('ran*') ? 'active' : '' }}">
    <a href="#"><i class="fa fa-diamond"></i> <span>Ran</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
    <ul class="treeview-menu">
        <li class="{{ request()->routeIs('ran.dashboard') ? 'active' : '' }}"><a href="{{ route('ran.dashboard') }}"><i class="fa fa-dashboard"></i> Dashboard</a></li>
        <li><a href="{{ route('ran.items.index') }}"><i class="fa fa-diamond"></i> Jewellery Items</a></li>
        <li><a href="{{ route('ran.purchases.index') }}"><i class="fa fa-truck"></i> Purchases</a></li>
        <li><a href="{{ route('ran.production.index') }}"><i class="fa fa-cogs"></i> Production</a></li>
        <li><a href="{{ route('ran.inventory.index') }}"><i class="fa fa-cubes"></i> Inventory & Storage</a></li>
        <li><a href="{{ route('ran.sales.index') }}"><i class="fa fa-shopping-bag"></i> Sales</a></li>
        <li><a href="{{ route('ran.documents.index') }}"><i class="fa fa-file-text-o"></i> Documents</a></li>
        <li><a href="{{ route('ran.reports.index') }}"><i class="fa fa-bar-chart"></i> Reports</a></li>
        <li><a href="{{ route('ran.settings.index') }}"><i class="fa fa-cog"></i> Settings</a></li>
    </ul>
</li>
@endif
