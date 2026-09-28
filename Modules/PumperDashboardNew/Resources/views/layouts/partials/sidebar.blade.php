@php
    $__poneSidebarActive = request()->routeIs('pumper-dashboard-new.admin.*')
        || request()->is('pumper-dashboard-new/admin')
        || request()->is('pumper-dashboard-new/admin/*');
@endphp

@can('pumper_dashboard_new.access')
<li class="nav-item {{ $__poneSidebarActive ? 'active active-sub' : '' }}" data-sidebar-module="pumper_dashboard_new">
    <a class="nav-link collapsed"
       href="#"
       data-toggle="collapse"
       data-target="#pumper-dashboard-new-sidebar-menu"
       aria-expanded="{{ $__poneSidebarActive ? 'true' : 'false' }}"
       aria-controls="pumper-dashboard-new-sidebar-menu">
        <i class="fa fa-tachometer"></i>
        <span>Pumper Dashboard-New</span>
    </a>
    <div id="pumper-dashboard-new-sidebar-menu"
         class="collapse {{ $__poneSidebarActive ? 'show' : '' }}"
         data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Pumper Dashboard-New:</h6>

            @can('pumper_dashboard_new.dashboard.view')
                <a class="collapse-item {{ request()->routeIs('pumper-dashboard-new.admin.dashboard*') ? 'active' : '' }}" href="{{ route('pumper-dashboard-new.admin.dashboard') }}"><i class="fa fa-dashboard mr-1"></i> Dashboard</a>
            @endcan
            @can('pumper_dashboard_new.operators.view')
                <a class="collapse-item {{ request()->routeIs('pumper-dashboard-new.admin.operators.*') ? 'active' : '' }}" href="{{ route('pumper-dashboard-new.admin.operators.index') }}"><i class="fa fa-users mr-1"></i> Pump Operators</a>
            @endcan
            @can('pumper_dashboard_new.shifts.view')
                <a class="collapse-item {{ request()->routeIs('pumper-dashboard-new.admin.shifts.*') || request()->routeIs('pumper-dashboard-new.admin.assignments.*') ? 'active' : '' }}" href="{{ route('pumper-dashboard-new.admin.shifts.index') }}"><i class="fa fa-clock-o mr-1"></i> Shifts & Assignments</a>
            @endcan
            @can('pumper_dashboard_new.collections.view')
                <a class="collapse-item {{ request()->routeIs('pumper-dashboard-new.admin.collections.*') ? 'active' : '' }}" href="{{ route('pumper-dashboard-new.admin.reports.index','collections') }}"><i class="fa fa-money mr-1"></i> Daily Collections</a>
            @endcan
            @can('pumper_dashboard_new.reconciliation.view')
                <a class="collapse-item {{ request()->routeIs('pumper-dashboard-new.admin.reconciliation.*') ? 'active' : '' }}" href="{{ route('pumper-dashboard-new.admin.reconciliation.index') }}"><i class="fa fa-balance-scale mr-1"></i> Shortage / Excess</a>
            @endcan
            @can('pumper_dashboard_new.ledger.view')
                <a class="collapse-item {{ request()->routeIs('pumper-dashboard-new.admin.ledger.*') ? 'active' : '' }}" href="{{ route('pumper-dashboard-new.admin.ledger.index') }}"><i class="fa fa-book mr-1"></i> Operator Ledger</a>
            @endcan
            @can('pumper_dashboard_new.documents.view')
                <a class="collapse-item {{ request()->routeIs('pumper-dashboard-new.admin.documents.*') ? 'active' : '' }}" href="{{ route('pumper-dashboard-new.admin.documents.index') }}"><i class="fa fa-files-o mr-1"></i> Documents & Notes</a>
            @endcan
            @can('pumper_dashboard_new.login_attempts.view')
                <a class="collapse-item {{ request()->routeIs('pumper-dashboard-new.admin.login-attempts.*') ? 'active' : '' }}" href="{{ route('pumper-dashboard-new.admin.login-attempts.index') }}"><i class="fa fa-lock mr-1"></i> Blocked Logins</a>
            @endcan
            @can('pumper_dashboard_new.print_logs.view')
                <a class="collapse-item {{ request()->routeIs('pumper-dashboard-new.admin.print-logs.*') ? 'active' : '' }}" href="{{ route('pumper-dashboard-new.admin.print-logs.index') }}"><i class="fa fa-print mr-1"></i> Print History</a>
            @endcan
            @can('pumper_dashboard_new.reports.view')
                <a class="collapse-item {{ request()->routeIs('pumper-dashboard-new.admin.reports.*') ? 'active' : '' }}" href="{{ route('pumper-dashboard-new.admin.reports.index') }}"><i class="fa fa-bar-chart mr-1"></i> Reports</a>
            @endcan
            @can('pumper_dashboard_new.integration.view')
                <a class="collapse-item {{ request()->routeIs('pumper-dashboard-new.admin.integration.*') ? 'active' : '' }}" href="{{ route('pumper-dashboard-new.admin.integration.index') }}"><i class="fa fa-exchange mr-1"></i> Petro PD-New Pairing</a>
            @endcan
            @can('pumper_dashboard_new.settings.manage')
                <a class="collapse-item {{ request()->routeIs('pumper-dashboard-new.admin.settings.*') ? 'active' : '' }}" href="{{ route('pumper-dashboard-new.admin.settings.edit') }}"><i class="fa fa-cog mr-1"></i> Settings</a>
            @endcan
        </div>
    </div>
</li>
@endcan
