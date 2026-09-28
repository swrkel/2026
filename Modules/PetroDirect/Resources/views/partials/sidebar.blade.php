@php
    use Illuminate\Support\Facades\Route;

    // PetroDirect exposes only its five supported navigation pages. Operational
    // forms remain internal routes and must not become duplicate sidebar pages.
    $petroDirectMenu = [
        [
            'route' => 'petrodirect.dashboard',
            'match' => ['petrodirect.dashboard', 'petrodirect.dashboard.index'],
            'icon' => 'fa-dashboard',
            'label' => __('petrodirect::lang.dashboard'),
        ],
        [
            'route' => 'petrodirect.settlement.create',
            'match' => ['petrodirect.settlement.create'],
            'icon' => 'fa-plus-circle',
            'label' => __('petrodirect::lang.direct_settlement'),
        ],
        [
            'route' => 'petrodirect.settlement.index',
            'match' => ['petrodirect.settlement.index'],
            'icon' => 'fa-list-alt',
            'label' => __('petrodirect::lang.list_direct_settlements'),
        ],
        [
            'route' => 'petrodirect.pumper-management.index',
            'match' => ['petrodirect.pumper-management.*'],
            'icon' => 'fa-user-circle',
            'label' => __('petrodirect::lang.pumper_management'),
        ],
        [
            'route' => 'petrodirect.reports.index',
            'match' => ['petrodirect.reports.*'],
            'icon' => 'fa-bar-chart',
            'label' => __('petrodirect::lang.reports'),
        ],
    ];

    $visiblePetroDirectMenu = collect($petroDirectMenu)->filter(function ($item) {
        return Route::has($item['route']);
    });
@endphp

@if($visiblePetroDirectMenu->isNotEmpty())
<li class="treeview {{ request()->is('petrodirect*') ? 'active menu-open' : '' }}">
    <a href="#">
        <i class="fa fa-tint"></i>
        <span>@lang('petrodirect::lang.petro_direct')</span>
        <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
    </a>
    <ul class="treeview-menu">
        @foreach($visiblePetroDirectMenu as $item)
            <li class="{{ request()->routeIs($item['match']) ? 'active' : '' }}">
                <a href="{{ route($item['route']) }}">
                    <i class="fa {{ $item['icon'] }}"></i> {{ $item['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</li>
@endif
