@php
    $pageTitle = $title ?? 'Products New';
    $pageSubtitle = $subtitle ?? 'Standalone product intelligence, inventory, barcode and reporting module.';
    $currentRoute = (string) optional(request()->route())->getName();
    $navItems = [
        ['route' => 'products-new.dashboard', 'match' => 'products-new.dashboard', 'icon' => 'fa-dashboard', 'label' => 'Dashboard'],
        ['route' => 'products-new.products.index', 'match' => 'products-new.products.', 'icon' => 'fa-cube', 'label' => 'Products'],
        ['route' => 'products-new.settings.categories.index', 'match' => 'products-new.settings.categories', 'icon' => 'fa-folder-open', 'label' => 'Categories'],
        ['route' => 'products-new.stock-center.index', 'match' => 'products-new.stock-center', 'icon' => 'fa-archive', 'label' => 'Stock Centre'],
        ['route' => 'products-new.stock-history.index', 'match' => 'products-new.stock-history', 'icon' => 'fa-history', 'label' => 'Stock History'],
        ['route' => 'products-new.intelligence.index', 'match' => 'products-new.intelligence', 'icon' => 'fa-line-chart', 'label' => 'Intelligence'],
        ['route' => 'products-new.reports.index', 'match' => 'products-new.reports', 'icon' => 'fa-file-text-o', 'label' => 'Reports'],
    ];
@endphp

<header class="productsnew-page-header no-print">
    <div class="productsnew-page-heading">
        <div class="productsnew-page-icon" aria-hidden="true"><i class="fa fa-cubes"></i></div>
        <div class="productsnew-page-copy">
            <h1>{{ $pageTitle }}</h1>
            @if($pageSubtitle)<p>{{ $pageSubtitle }}</p>@endif
        </div>
    </div>

    <nav class="productsnew-header-actions" aria-label="Products New navigation">
        @foreach($navItems as $item)
            @if(\Illuminate\Support\Facades\Route::has($item['route']))
                <a class="pn-nav-btn {{ str_starts_with($currentRoute, $item['match']) ? 'active' : '' }}"
                   href="{{ route($item['route']) }}">
                    <i class="fa {{ $item['icon'] }}" aria-hidden="true"></i><span>{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>
</header>
