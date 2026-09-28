{{--
    Rice Mill Module - module-owned sidebar.
    Keep this file independent from the main application sidebar so future
    Rice Mill menu changes remain inside Modules/RiceMill.
--}}
@php
    $__rcmUser = auth()->user();
    $__rcmIsSuperAdmin = false;

    if ($__rcmUser) {
        try {
            $__rcmIsSuperAdmin = $__rcmUser->can('superadmin');
        } catch (\Throwable $e) {
            $__rcmIsSuperAdmin = false;
        }

        if (!$__rcmIsSuperAdmin && method_exists($__rcmUser, 'hasRole')) {
            try {
                $__rcmIsSuperAdmin = $__rcmUser->hasRole('Super Admin')
                    || $__rcmUser->hasRole('superadmin');
            } catch (\Throwable $e) {
                // Keep the permission result above.
            }
        }
    }

    $__rcmCan = function ($permission) use ($__rcmUser, $__rcmIsSuperAdmin) {
        if (!$__rcmUser) {
            return false;
        }
        if ($__rcmIsSuperAdmin) {
            return true;
        }
        try {
            return $__rcmUser->can($permission);
        } catch (\Throwable $e) {
            return false;
        }
    };

    $__rcmMenuItems = [
        'dashboard' => [
            'label' => 'Dashboard',
            'icon' => 'fa fa-dashboard',
            'route' => 'rice-mill.dashboard',
            'permission' => 'rice_mill.dashboard.view',
        ],
        'purchases' => [
            'label' => 'Purchase Orders',
            'icon' => 'fa fa-shopping-cart',
            'route' => 'rice-mill.purchases.index',
            'permission' => 'rice_mill.paddy_purchase.view',
        ],
        'receipts' => [
            'label' => 'Paddy Receiving',
            'icon' => 'fa fa-download',
            'route' => 'rice-mill.receipts.index',
            'permission' => 'rice_mill.paddy_receipt.view',
        ],
        'weighbridge' => [
            'label' => 'Weighbridge',
            'icon' => 'fa fa-balance-scale',
            'route' => 'rice-mill.weighbridge.index',
            'permission' => 'rice_mill.paddy_receipt.view',
        ],
        'paddy_stock' => [
            'label' => 'Paddy Stock',
            'icon' => 'fa fa-database',
            'route' => 'rice-mill.paddy-stock.index',
            'permission' => 'rice_mill.paddy_stock.view',
        ],
        'production' => [
            'label' => 'Production / Milling',
            'icon' => 'fa fa-cogs',
            'route' => 'rice-mill.production.entry',
            'permission' => 'rice_mill.production.view',
        ],
        'byproducts' => [
            'label' => 'By-Products',
            'icon' => 'fa fa-recycle',
            'route' => 'rice-mill.byproducts.index',
            'permission' => 'rice_mill.production.view',
        ],
        'packing' => [
            'label' => 'Packing',
            'icon' => 'fa fa-cubes',
            'route' => 'rice-mill.packing.index',
            'permission' => 'rice_mill.packing.view',
        ],
        'packaging_materials' => [
            'label' => 'Packaging Materials',
            'icon' => 'fa fa-dropbox',
            'route' => 'rice-mill.packaging-materials.index',
            'permission' => 'rice_mill.packing.view',
        ],
        'packaging_material_mappings' => [
            'label' => 'Material Usage Mapping',
            'icon' => 'fa fa-random',
            'route' => 'rice-mill.packaging-material-mappings.index',
            'permission' => 'rice_mill.packing.view',
        ],
        'finished_stock' => [
            'label' => 'Finished Rice Stock',
            'icon' => 'fa fa-archive',
            'route' => 'rice-mill.finished-stock.index',
            'permission' => 'rice_mill.finished_stock.view',
        ],
        'dispatch' => [
            'label' => 'Sales / Dispatch',
            'icon' => 'fa fa-truck',
            'route' => 'rice-mill.dispatch.index',
            'permission' => 'rice_mill.dispatch.view',
        ],
        'reports' => [
            'label' => 'Rice Mill Reports',
            'icon' => 'fa fa-bar-chart',
            'route' => 'rice-mill.reports.index',
            'permission' => 'rice_mill.reports.view',
        ],
        'settings' => [
            'label' => 'Settings',
            'icon' => 'fa fa-cog',
            'route' => 'rice-mill.settings.index',
            'permission' => 'rice_mill.settings.view',
        ],
    ];

    foreach ($__rcmMenuItems as $__rcmKey => $__rcmItem) {
        $__rcmMenuItems[$__rcmKey]['visible'] = \Illuminate\Support\Facades\Route::has($__rcmItem['route'])
            && $__rcmCan($__rcmItem['permission']);
    }

    $__rcmGroups = [
        'PADDY OPERATIONS' => ['purchases', 'receipts', 'weighbridge', 'paddy_stock'],
        'PRODUCTION & STOCK' => ['production', 'byproducts', 'packing', 'packaging_materials', 'packaging_material_mappings', 'finished_stock'],
        'SALES & DISPATCH' => ['dispatch'],
        'REPORTS' => ['reports'],
        'ADMINISTRATION' => ['settings'],
    ];

    $__rcmHasAny = collect($__rcmMenuItems)->contains(function ($item) {
        return !empty($item['visible']);
    });
    $__rcmOpen = request()->is('rice-mill') || request()->is('rice-mill/*');
@endphp

@if($__rcmHasAny)
<li id="rice-mill-module-nav" class="nav-item {{ $__rcmOpen ? 'active active-sub' : '' }}">
    <a class="nav-link {{ $__rcmOpen ? '' : 'collapsed' }}"
       href="#"
       data-toggle="collapse"
       data-target="#rice-mill-module-menu"
       aria-expanded="{{ $__rcmOpen ? 'true' : 'false' }}"
       aria-controls="rice-mill-module-menu">
        <i class="fa fa-industry"></i>
        <span>Rice Mill Module</span>
    </a>

    <div id="rice-mill-module-menu"
         class="collapse {{ $__rcmOpen ? 'show' : '' }}"
         data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Rice Mill Module:</h6>

            @if(!empty($__rcmMenuItems['dashboard']['visible']))
                <a class="collapse-item {{ request()->routeIs('rice-mill.dashboard') ? 'active' : '' }}"
                   href="{{ route('rice-mill.dashboard') }}">
                    <i class="fa fa-dashboard"></i>&nbsp; Dashboard
                </a>
            @endif

            @foreach($__rcmGroups as $__rcmGroupLabel => $__rcmGroupKeys)
                @php
                    $__rcmVisibleGroup = collect($__rcmGroupKeys)->filter(function ($key) use ($__rcmMenuItems) {
                        return !empty($__rcmMenuItems[$key]['visible']);
                    });
                @endphp

                @if($__rcmVisibleGroup->isNotEmpty())
                    <h6 class="collapse-header" style="margin-top:8px;">{{ $__rcmGroupLabel }}</h6>

                    @foreach($__rcmVisibleGroup as $__rcmKey)
                        @php $__rcmItem = $__rcmMenuItems[$__rcmKey]; @endphp
                        <a class="collapse-item {{ request()->routeIs($__rcmItem['route']) ? 'active' : '' }}"
                           href="{{ route($__rcmItem['route']) }}">
                            <i class="{{ $__rcmItem['icon'] }}"></i>&nbsp; {{ $__rcmItem['label'] }}
                        </a>
                    @endforeach
                @endif
            @endforeach
        </div>
    </div>
</li>
@endif


{{-- RCM_DUPLICATE_SIDEBAR_GUARD: when the host automatic module renderer also
     creates a flat /rice-mill entry, keep only this module-owned expandable menu. --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    var own = document.getElementById('rice-mill-module-nav');
    if (!own) return;

    var root = document.getElementById('accordionSidebar') || document;
    root.querySelectorAll('a[href]').forEach(function (link) {
        if (own.contains(link)) return;

        try {
            var parsed = new URL(link.href, window.location.origin);
            var path = (parsed.pathname || '').replace(/\/+$/, '');
            if (path !== '/rice-mill') return;

            var item = link.closest('li.nav-item, li.treeview, li');
            if (item && item !== own && !own.contains(item)) {
                item.style.display = 'none';
                item.setAttribute('data-rcm-duplicate-hidden', '1');
            }
        } catch (e) {
            // Ignore malformed or non-HTTP links.
        }
    });
});
</script>
