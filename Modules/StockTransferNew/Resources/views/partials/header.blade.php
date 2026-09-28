@php
    $navigation = [
        ['route' => 'stock-transfer-new.dashboard', 'label' => 'Dashboard', 'icon' => 'fa-dashboard'],
        ['route' => 'stock-transfer-new.transfers.index', 'label' => 'Transfers', 'icon' => 'fa-exchange'],
        ['route' => 'stock-transfer-new.approvals.index', 'label' => 'Approvals', 'icon' => 'fa-check-circle'],
        ['route' => 'stock-transfer-new.dispatch.index', 'label' => 'Dispatch', 'icon' => 'fa-truck'],
        ['route' => 'stock-transfer-new.receive.index', 'label' => 'Receive', 'icon' => 'fa-download'],
        ['route' => 'stock-transfer-new.balances.index', 'label' => 'Balances', 'icon' => 'fa-cubes'],
        ['route' => 'stock-transfer-new.reports.transfer-register', 'label' => 'Reports', 'icon' => 'fa-bar-chart'],
        ['route' => 'stock-transfer-new.settings.index', 'label' => 'Settings', 'icon' => 'fa-cog'],
    ];
@endphp

<header class="stn-page-header">
    <div class="stn-title-area">
        <div class="stn-title-icon">
            <i class="fa fa-exchange"></i>
        </div>
        <div>
            <h1>{{ $title ?? 'Stock Transfer New' }}</h1>
            <p>{{ $subtitle ?? 'Standalone stock transfer operations' }}</p>
        </div>
    </div>

    <div class="stn-header-buttons">
        @if(\Illuminate\Support\Facades\Route::has('stock-transfer-new.transfers.create'))
            <a href="{{ route('stock-transfer-new.transfers.create') }}" class="stn-btn stn-btn-primary">
                <i class="fa fa-plus"></i>
                <span>New Transfer</span>
            </a>
        @endif
    </div>
</header>

<nav class="stn-module-nav" aria-label="Stock Transfer navigation">
    @foreach($navigation as $item)
        @if(\Illuminate\Support\Facades\Route::has($item['route']))
            <a href="{{ route($item['route']) }}"
               class="{{ request()->routeIs($item['route']) ? 'active' : '' }}">
                <i class="fa {{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endif
    @endforeach
</nav>
