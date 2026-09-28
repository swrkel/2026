@php
    $tabs = [
        ['stock-taking-new.dashboard*', 'stock-taking-new.dashboard', 'fa-dashboard', 'Dashboard', 'stock_taking_new.dashboard.view'],
        ['stock-taking-new.sessions.*', 'stock-taking-new.sessions.index', 'fa-clipboard', 'Sessions', 'stock_taking_new.sessions.view'],
        ['stock-taking-new.approvals.*', 'stock-taking-new.approvals.index', 'fa-check-circle', 'Approvals', 'stock_taking_new.approvals.view'],
        ['stock-taking-new.reports.*', 'stock-taking-new.reports.index', 'fa-bar-chart', 'Reports', 'stock_taking_new.reports.view'],
        ['stock-taking-new.templates.*', 'stock-taking-new.templates.index', 'fa-copy', 'Templates', 'stock_taking_new.templates.manage'],
        ['stock-taking-new.schedules.*', 'stock-taking-new.schedules.index', 'fa-calendar', 'Schedules', 'stock_taking_new.schedules.manage'],
        ['stock-taking-new.settings.*', 'stock-taking-new.settings.index', 'fa-cogs', 'Settings', 'stock_taking_new.settings.manage'],
    ];
@endphp
<nav class="stk-tabs" aria-label="Stock Taking navigation">
    @foreach($tabs as $tab)
        @can($tab[4])
            <a href="{{ route($tab[1]) }}" class="{{ request()->routeIs($tab[0]) ? 'active' : '' }}">
                <i class="fa {{ $tab[2] }}"></i><span>{{ $tab[3] }}</span>
            </a>
        @endcan
    @endforeach
</nav>
