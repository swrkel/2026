@once
@php
    $__purchaseBusinessId = (int) session('user.business_id');
    $__purchaseUser = auth()->user();
    $__purchaseModuleEnabled = false;
    $__purchaseIsAdmin = false;
    $__purchaseSuperAdmin = false;

    try {
        $__purchaseModuleEnabled = \App\Utils\SidebarPermissionUtil::isVisibleInSidebar('purchase', $__purchaseBusinessId);
        $__purchaseSuperAdmin = \App\Utils\SidebarPermissionUtil::hasSuperAdminBypass();
        $__purchaseIsAdmin = $__purchaseUser
            && $__purchaseBusinessId > 0
            && $__purchaseUser->hasRole('Admin#' . $__purchaseBusinessId);
    } catch (\Throwable $__purchaseSidebarContextException) {
        $__purchaseModuleEnabled = false;
    }

    $__purchasePrivileged = $__purchaseSuperAdmin || $__purchaseIsAdmin;

    $__purchaseCan = static function (array $permissions = []) use ($__purchaseUser, $__purchasePrivileged): bool {
        if ($__purchasePrivileged) {
            return true;
        }

        if (! $__purchaseUser) {
            return false;
        }

        foreach ($permissions as $__purchasePermission) {
            try {
                if ($__purchaseUser->can($__purchasePermission)) {
                    return true;
                }
            } catch (\Throwable $__purchasePermissionException) {
                // Continue checking compatible legacy permissions.
            }
        }

        return false;
    };

    $__purchasePages = [
        'dashboard' => $__purchaseCan(['purchase.dashboard.view', 'purchase.view']),
        'entries_list' => $__purchaseCan(['purchase.entry.view', 'purchase.view']),
        'entries_create' => $__purchaseCan(['purchase.entry.create', 'purchase.create']),
        'orders_list' => $__purchaseCan(['purchase.order.view', 'purchase.view']),
        'orders_create' => $__purchaseCan(['purchase.order.create', 'purchase.create']),
        'returns_list' => $__purchaseCan(['purchase.return.view', 'purchase.view', 'access_purchase_return']),
        'returns_create' => $__purchaseCan(['purchase.return.create', 'purchase.create', 'access_purchase_return']),
        'bills_list' => $__purchaseCan(['purchase.bill.view', 'purchase.view']),
        'bills_create' => $__purchaseCan(['purchase.bill.create', 'purchase.create']),
        'payments_list' => $__purchaseCan(['purchase.supplier_payment.view', 'purchase.payment.view', 'purchase.view']),
        'payments_create' => $__purchaseCan(['purchase.supplier_payment.create', 'purchase.payment.create', 'purchase.create']),
        'reports' => $__purchaseCan(['purchase.report.view', 'purchase.view', 'purchase_n_sell_report.view']),
        'settings' => $__purchaseCan(['purchase.settings.view', 'purchase.settings.manage', 'purchase.update']),
    ];

    $__purchaseHasVisiblePage = in_array(true, $__purchasePages, true);
    $__purchaseActive = request()->is('purchase-module-live') || request()->is('purchase-module-live/*') || request()->is('purchase') || request()->is('purchase/*') || request()->is('purchase-module') || request()->is('purchase-module/*');

    $__purchaseLink = static function (string $routeName): ?string {
        try {
            return \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName) : null;
        } catch (\Throwable $__purchaseRouteException) {
            return null;
        }
    };
@endphp

@if($__purchaseModuleEnabled)
<li class="nav-item {{ $__purchaseActive ? 'active active-sub' : '' }}"
    id="purchase-standalone-sidebar-module"
    data-sidebar-module="purchase">
    <a class="nav-link {{ $__purchaseActive ? '' : 'collapsed' }}"
       href="#"
       data-toggle="collapse"
       data-target="#purchase-standalone-menu"
       aria-expanded="{{ $__purchaseActive ? 'true' : 'false' }}"
       aria-controls="purchase-standalone-menu">
        <i class="fa fa-shopping-cart"></i>
        <span>Purchase</span>
    </a>

    <div id="purchase-standalone-menu"
         class="collapse {{ $__purchaseActive ? 'show' : '' }}"
         aria-labelledby="headingPages"
         data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Purchase Module:</h6>

            @if($__purchasePages['dashboard'] && ($__purchaseUrl = $__purchaseLink('purchase.dashboard.index')))
                <a class="collapse-item {{ request()->routeIs('purchase.dashboard.*') || request()->routeIs('purchase.index') ? 'active' : '' }}"
                   href="{{ $__purchaseUrl }}">Dashboard</a>
            @endif

            @if($__purchasePages['entries_list'] || $__purchasePages['entries_create'])
                <h6 class="collapse-header mt-2">Purchase Entries:</h6>
                @if($__purchasePages['entries_list'] && ($__purchaseUrl = $__purchaseLink('purchase.entries.index')))
                    <a class="collapse-item {{ request()->routeIs('purchase.entries.index') || request()->routeIs('purchase.entries.show') || request()->routeIs('purchase.entries.edit') ? 'active' : '' }}"
                       href="{{ $__purchaseUrl }}">List Purchase Entries</a>
                @endif
                @if($__purchasePages['entries_create'] && ($__purchaseUrl = $__purchaseLink('purchase.entries.create')))
                    <a class="collapse-item {{ request()->routeIs('purchase.entries.create') ? 'active' : '' }}"
                       href="{{ $__purchaseUrl }}">Add Purchase Entry</a>
                @endif
            @endif

            @if($__purchasePages['orders_list'] || $__purchasePages['orders_create'])
                <h6 class="collapse-header mt-2">Purchase Orders:</h6>
                @if($__purchasePages['orders_list'] && ($__purchaseUrl = $__purchaseLink('purchase.orders.index')))
                    <a class="collapse-item {{ request()->routeIs('purchase.orders.index') || request()->routeIs('purchase.orders.show') || request()->routeIs('purchase.orders.edit') ? 'active' : '' }}"
                       href="{{ $__purchaseUrl }}">List Purchase Orders</a>
                @endif
                @if($__purchasePages['orders_create'] && ($__purchaseUrl = $__purchaseLink('purchase.orders.create')))
                    <a class="collapse-item {{ request()->routeIs('purchase.orders.create') ? 'active' : '' }}"
                       href="{{ $__purchaseUrl }}">Add Purchase Order</a>
                @endif
            @endif

            @if($__purchasePages['returns_list'] || $__purchasePages['returns_create'])
                <h6 class="collapse-header mt-2">Purchase Returns:</h6>
                @if($__purchasePages['returns_list'] && ($__purchaseUrl = $__purchaseLink('purchase.returns.index')))
                    <a class="collapse-item {{ request()->routeIs('purchase.returns.index') || request()->routeIs('purchase.returns.show') || request()->routeIs('purchase.returns.edit') ? 'active' : '' }}"
                       href="{{ $__purchaseUrl }}">List Purchase Returns</a>
                @endif
                @if($__purchasePages['returns_create'] && ($__purchaseUrl = $__purchaseLink('purchase.returns.create')))
                    <a class="collapse-item {{ request()->routeIs('purchase.returns.create') ? 'active' : '' }}"
                       href="{{ $__purchaseUrl }}">Add Purchase Return</a>
                @endif
            @endif

            @if($__purchasePages['bills_list'] || $__purchasePages['bills_create'])
                <h6 class="collapse-header mt-2">Purchase Bills:</h6>
                @if($__purchasePages['bills_list'] && ($__purchaseUrl = $__purchaseLink('purchase.bills.index')))
                    <a class="collapse-item {{ request()->routeIs('purchase.bills.index') || request()->routeIs('purchase.bills.show') || request()->routeIs('purchase.bills.edit') ? 'active' : '' }}"
                       href="{{ $__purchaseUrl }}">List Purchase Bills</a>
                @endif
                @if($__purchasePages['bills_create'] && ($__purchaseUrl = $__purchaseLink('purchase.bills.create')))
                    <a class="collapse-item {{ request()->routeIs('purchase.bills.create') ? 'active' : '' }}"
                       href="{{ $__purchaseUrl }}">Add Purchase Bill</a>
                @endif
            @endif

            @if($__purchasePages['payments_list'] || $__purchasePages['payments_create'])
                <h6 class="collapse-header mt-2">Supplier Payments:</h6>
                @if($__purchasePages['payments_list'] && ($__purchaseUrl = $__purchaseLink('purchase.supplier-payments.index')))
                    <a class="collapse-item {{ request()->routeIs('purchase.supplier-payments.index') || request()->routeIs('purchase.supplier-payments.edit') ? 'active' : '' }}"
                       href="{{ $__purchaseUrl }}">List Supplier Payments</a>
                @endif
                @if($__purchasePages['payments_create'] && ($__purchaseUrl = $__purchaseLink('purchase.supplier-payments.create')))
                    <a class="collapse-item {{ request()->routeIs('purchase.supplier-payments.create') ? 'active' : '' }}"
                       href="{{ $__purchaseUrl }}">Add Supplier Payment</a>
                @endif
            @endif

            @if($__purchasePages['reports'])
                <h6 class="collapse-header mt-2">Purchase Reports:</h6>
                @if(($__purchaseUrl = $__purchaseLink('purchase.reports.index')))
                    <a class="collapse-item {{ request()->routeIs('purchase.reports.index') ? 'active' : '' }}" href="{{ $__purchaseUrl }}">Reports Dashboard</a>
                @endif
                @if(($__purchaseUrl = $__purchaseLink('purchase.reports.purchase-register')))
                    <a class="collapse-item {{ request()->routeIs('purchase.reports.purchase-register') ? 'active' : '' }}" href="{{ $__purchaseUrl }}">Purchase Register</a>
                @endif
                @if(($__purchaseUrl = $__purchaseLink('purchase.reports.purchase-payment')))
                    <a class="collapse-item {{ request()->routeIs('purchase.reports.purchase-payment') ? 'active' : '' }}" href="{{ $__purchaseUrl }}">Purchase Payment Report</a>
                @endif
                @if(($__purchaseUrl = $__purchaseLink('purchase.reports.product-purchase')))
                    <a class="collapse-item {{ request()->routeIs('purchase.reports.product-purchase') ? 'active' : '' }}" href="{{ $__purchaseUrl }}">Product Purchase Report</a>
                @endif
                @if(($__purchaseUrl = $__purchaseLink('purchase.reports.purchase-sell')))
                    <a class="collapse-item {{ request()->routeIs('purchase.reports.purchase-sell') ? 'active' : '' }}" href="{{ $__purchaseUrl }}">Purchase &amp; Sale Report</a>
                @endif
                @if(($__purchaseUrl = $__purchaseLink('purchase.reports.stock-purchase-sale')))
                    <a class="collapse-item {{ request()->routeIs('purchase.reports.stock-purchase-sale') ? 'active' : '' }}" href="{{ $__purchaseUrl }}">Stock Purchase/Sale Report</a>
                @endif
                @if(($__purchaseUrl = $__purchaseLink('purchase.reports.supplier-outstanding')))
                    <a class="collapse-item {{ request()->routeIs('purchase.reports.supplier-outstanding') ? 'active' : '' }}" href="{{ $__purchaseUrl }}">Supplier Outstanding</a>
                @endif
            @endif

            @if($__purchasePages['settings'])
                <h6 class="collapse-header mt-2">Purchase Settings:</h6>
                @if(($__purchaseUrl = $__purchaseLink('purchase.settings.general')))
                    <a class="collapse-item {{ request()->routeIs('purchase.settings.general') ? 'active' : '' }}" href="{{ $__purchaseUrl }}">General Settings</a>
                @endif
                @if(($__purchaseUrl = $__purchaseLink('purchase.settings.numbering')))
                    <a class="collapse-item {{ request()->routeIs('purchase.settings.numbering') ? 'active' : '' }}" href="{{ $__purchaseUrl }}">Numbering Settings</a>
                @endif
                @if(($__purchaseUrl = $__purchaseLink('purchase.settings.approval')))
                    <a class="collapse-item {{ request()->routeIs('purchase.settings.approval') ? 'active' : '' }}" href="{{ $__purchaseUrl }}">Approval Settings</a>
                @endif
                @if(($__purchaseUrl = $__purchaseLink('purchase.settings.tax')))
                    <a class="collapse-item {{ request()->routeIs('purchase.settings.tax') ? 'active' : '' }}" href="{{ $__purchaseUrl }}">Tax Settings</a>
                @endif
                @if(($__purchaseUrl = $__purchaseLink('purchase.settings.supplier')))
                    <a class="collapse-item {{ request()->routeIs('purchase.settings.supplier') ? 'active' : '' }}" href="{{ $__purchaseUrl }}">Supplier Settings</a>
                @endif
            @endif
        </div>
    </div>
</li>
@endif
@endonce
