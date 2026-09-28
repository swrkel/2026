@php
    use Illuminate\Support\Facades\Route;

    /*
     * Petro Direct owns many routes used by tabs, Ajax calls and workflow
     * actions. Only these supported primary destinations belong in the global
     * sidebar; internal routes stay inside their owning pages.
     */
    $petroDirectRouteCandidates = [
        'dashboard' => [
            'petrodirect.dashboard',
            'petro-direct.dashboard',
            'petrodirect.index',
            'petro-direct.index',
        ],
        'direct_settlement' => [
            'petrodirect.direct-settlement',
            'petro-direct.direct-settlement',
            'petrodirect.settlement.create',
            'petro-direct.settlement.create',
            'petrodirect.settlement',
            'petro-direct.settlement',
            'petrodirect.settlement.index',
            'petro-direct.settlement.index',
        ],
        'list_direct_settlement' => [
            'petrodirect.list-direct-settlement',
            'petro-direct.list-direct-settlement',
            'petrodirect.settlement.index',
            'petro-direct.settlement.index',
            'petrodirect.settlements.index',
            'petro-direct.settlements.index',
        ],
        'pumper_management' => [
            'petrodirect.pumper-management.index',
            'petro-direct.pumper-management.index',
            'petrodirect.pumper_management.index',
            'petro-direct.pumper_management.index',
            'petrodirect.pumpers.index',
            'petro-direct.pumpers.index',
            'petrodirect.pump-operators.index',
            'petro-direct.pump-operators.index',
            'petrodirect.pump_operators.index',
            'petro-direct.pump_operators.index',
        ],
        'reports' => [
            'petrodirect.reports.index',
            'petro-direct.reports.index',
        ],
    ];

    $petroDirectRoutes = [];
    foreach ($petroDirectRouteCandidates as $key => $candidates) {
        foreach ($candidates as $routeName) {
            if (Route::has($routeName)) {
                $petroDirectRoutes[$key] = $routeName;
                break;
            }
        }
    }

    $petroDirectActive = request()->is('petrodirect*')
        || request()->is('petro-direct*')
        || request()->routeIs('petrodirect.*')
        || request()->routeIs('petro-direct.*');
@endphp

@if(!empty($petroDirectRoutes))
    <li class="nav-item {{ $petroDirectActive ? 'active active-sub' : '' }}"
        data-sidebar-module="petro_direct">
        <a class="nav-link collapsed"
           href="#"
           data-toggle="collapse"
           data-target="#petro-direct-menu"
           aria-expanded="{{ $petroDirectActive ? 'true' : 'false' }}"
           aria-controls="petro-direct-menu">
            <i class="fa fa-truck"></i>
            <span>Petro Direct</span>
        </a>

        <div id="petro-direct-menu"
             class="collapse {{ $petroDirectActive ? 'show' : '' }}"
             aria-labelledby="headingPages"
             data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Petro Direct:</h6>

                @if(!empty($petroDirectRoutes['dashboard']))
                    <a class="collapse-item {{ request()->routeIs($petroDirectRoutes['dashboard']) ? 'active' : '' }}"
                       href="{{ route($petroDirectRoutes['dashboard']) }}">Dashboard</a>
                @endif

                @if(!empty($petroDirectRoutes['direct_settlement']))
                    <a class="collapse-item {{ request()->routeIs($petroDirectRoutes['direct_settlement']) ? 'active' : '' }}"
                       href="{{ route($petroDirectRoutes['direct_settlement']) }}">Direct Settlement</a>
                @endif

                @if(!empty($petroDirectRoutes['list_direct_settlement']))
                    <a class="collapse-item {{ request()->routeIs($petroDirectRoutes['list_direct_settlement']) ? 'active' : '' }}"
                       href="{{ route($petroDirectRoutes['list_direct_settlement']) }}">List Direct Settlements</a>
                @endif

                @if(!empty($petroDirectRoutes['pumper_management']))
                    <a class="collapse-item {{ request()->routeIs($petroDirectRoutes['pumper_management']) ? 'active' : '' }}"
                       href="{{ route($petroDirectRoutes['pumper_management']) }}">Pumper Management</a>
                @endif

                @if(!empty($petroDirectRoutes['reports']))
                    <a class="collapse-item {{ request()->routeIs($petroDirectRoutes['reports']) ? 'active' : '' }}"
                       href="{{ route($petroDirectRoutes['reports']) }}">Reports</a>
                @endif
            </div>
        </div>
    </li>
@endif
