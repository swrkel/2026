@inject('request', 'Illuminate\Http\Request')

@php
    $__newOpsStatuses = [];
    try {
        $__statusFile = base_path('modules_statuses.json');
        if (is_file($__statusFile)) {
            $__newOpsStatuses = json_decode(file_get_contents($__statusFile), true) ?: [];
        }
    } catch (\Throwable $e) {
        $__newOpsStatuses = [];
    }

    $__distributionInstalled = !empty($__newOpsStatuses['DistributionNew'])
        || is_dir(base_path('Modules/DistributionNew'));
    $__stockTransferNewInstalled = !empty($__newOpsStatuses['StockTransferNew'])
        || is_dir(base_path('Modules/StockTransferNew'));
    $__stockAdjustmentNewInstalled = !empty($__newOpsStatuses['StockAdjustmentNew'])
        || is_dir(base_path('Modules/StockAdjustmentNew'));
    $__suppliersInstalled = !empty($__newOpsStatuses['Suppliers'])
        || is_dir(base_path('Modules/Suppliers'));
    $__swInstalled = !empty($__newOpsStatuses['SW'])
        || is_dir(base_path('Modules/SW'));
    $__purchaseStandaloneInstalled = !empty($__newOpsStatuses['Purchase'])
        || is_dir(base_path('Modules/Purchase'));

    /* Resolve each parent independently: one optional module error must never hide SW. */
    $__newOpsSuperAdminOwnBusiness = false;
    try {
        $__newOpsSuperAdminOwnBusiness = \App\Utils\SidebarPermissionUtil::isGenuineSuperAdmin();
    } catch (\Throwable $e) {}

    $__showDistributionNew = false;
    $__showStockTransferNew = false;
    $__showStockAdjustmentNew = false;
    $__showSWStandalone = false;
    $__showPurchaseStandalone = false;
    $__showSupplierFallback = false;

    try {
        $__showDistributionNew = $__distributionInstalled
            && \App\Utils\SidebarPermissionUtil::isVisibleInSidebar('distribution_new');
    } catch (\Throwable $e) {}

    try {
        $__showStockTransferNew = $__stockTransferNewInstalled
            && \App\Utils\SidebarPermissionUtil::isVisibleInSidebar('stock_transfer_new');
    } catch (\Throwable $e) {}

    try {
        $__showStockAdjustmentNew = $__stockAdjustmentNewInstalled
            && \App\Utils\SidebarPermissionUtil::isVisibleInSidebar('stock_adjustment_new');
    } catch (\Throwable $e) {}

    try {
        $__showSWStandalone = $__swInstalled
            && \App\Utils\SidebarPermissionUtil::isVisibleInSidebar('sw');
    } catch (\Throwable $e) {
        $__showSWStandalone = false;
    }

    try {
        $__showPurchaseStandalone = $__purchaseStandaloneInstalled
            && \App\Utils\SidebarPermissionUtil::isVisibleInSidebar('purchase');
    } catch (\Throwable $e) {}

    try {
        $__showSupplierFallback = $__suppliersInstalled
            && empty($__supplierMenuRendered)
            && \App\Utils\SidebarPermissionUtil::isVisibleInSidebar('suppliers')
            && ($__newOpsSuperAdminOwnBusiness
                || (class_exists(\Modules\Suppliers\Utils\SupplierPermissionUtil::class)
                    && \Modules\Suppliers\Utils\SupplierPermissionUtil::userCanAccess()));
    } catch (\Throwable $e) {
        $__showSupplierFallback = false;
    }

    /* Prefer SW's own current sidebar view, but never let a missing/empty view erase the enabled parent. */
    $__swSidebarHtml = '';
    if ($__showSWStandalone) {
        try {
            if (view()->exists('sw::layouts.sidebar')) {
                $__swSidebarHtml = trim((string) view('sw::layouts.sidebar')->render());
            }
        } catch (\Throwable $e) {
            $__swSidebarHtml = '';
        }
    }
@endphp


{{-- Standalone SW and Purchase parents are rendered explicitly so they can
     never disappear because a legacy sidebar-name collision fooled automatic
     module discovery. --}}
@if($__showSWStandalone)
    @if($__swSidebarHtml !== '')
        {!! $__swSidebarHtml !!}
    @else
        <li class="nav-item {{ $request->segment(1) === 'sw' ? 'active active-sub' : '' }}"
            id="sw-sidebar-menu" data-sidebar-module="sw" data-module-key="sw">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#sw-menu"
               aria-expanded="{{ $request->segment(1) === 'sw' ? 'true' : 'false' }}"
               aria-controls="sw-menu" data-sidebar-title="SW Module">
                <i class="fa fa-clock-o"></i>
                <span>SW Module</span>
            </a>
            <div id="sw-menu" class="collapse {{ $request->segment(1) === 'sw' ? 'show' : '' }}"
                 aria-labelledby="headingPages" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <h6 class="collapse-header">SW Module:</h6>

                    @if (\Illuminate\Support\Facades\Route::has('sw.operators.index'))
                        <a class="collapse-item {{ $request->segment(2) === 'operators' ? 'active' : '' }}"
                           href="{{ route('sw.operators.index') }}">@lang('sw::lang.sw_operators')</a>
                    @endif

                    @if (\Illuminate\Support\Facades\Route::has('sw.payments.index')
                        && (auth()->user()->can('superadmin')
                            || auth()->user()->can('sw.daily_cash.view')
                            || auth()->user()->can('sw.daily_credit_sales.view')
                            || auth()->user()->can('sw.daily_cards.view')
                            || auth()->user()->can('sw.daily_shortage_excess.view')
                            || auth()->user()->can('sw.daily_cheques.view')
                            || auth()->user()->can('sw.collection_summary.view')))
                        <a class="collapse-item {{ $request->segment(2) === 'payments' ? 'active' : '' }}"
                           href="{{ route('sw.payments.index') }}">@lang('sw::lang.sw_payments')</a>
                    @endif

                    @if (\Illuminate\Support\Facades\Route::has('sw.daily-shifts.index')
                        && (auth()->user()->can('superadmin')
                            || auth()->user()->can('sw.daily_cash_status.view')
                            || auth()->user()->can('sw.daily_shift.view')
                            || auth()->user()->can('sw.collection_summary.view')))
                        <a class="collapse-item {{ $request->segment(2) === 'daily-shifts' ? 'active' : '' }}"
                           href="{{ route('sw.daily-shifts.index') }}">@lang('sw::lang.sw_daily_shifts')</a>
                    @endif

                    @if (\Illuminate\Support\Facades\Route::has('sw.shifts.index'))
                        <a class="collapse-item {{ $request->segment(2) === 'shifts' ? 'active' : '' }}"
                           href="{{ route('sw.shifts.index') }}">@lang('sw::lang.sw_shifts')</a>
                    @endif

                    @if (\Illuminate\Support\Facades\Route::has('sw.settlements.index'))
                        <a class="collapse-item {{ $request->segment(2) === 'settlements' ? 'active' : '' }}"
                           href="{{ route('sw.settlements.index') }}">@lang('sw::lang.sw_settlements')</a>
                    @endif

                    @if (\Illuminate\Support\Facades\Route::has('sw.logs.index'))
                        <a class="collapse-item {{ $request->segment(2) === 'logs' ? 'active' : '' }}"
                           href="{{ route('sw.logs.index') }}">@lang('sw::lang.sw_logs')</a>
                    @endif
                </div>
            </div>
        </li>
    @endif
@endif

@if($__showPurchaseStandalone)
    @includeIf('purchase::layouts.sidebar')
@endif

@if($__showSupplierFallback)
    <li class="nav-item {{ $request->segment(1) === 'suppliers' ? 'active active-sub' : '' }}">
        <a class="nav-link" href="{{ \Illuminate\Support\Facades\Route::has('suppliers.records.index') ? route('suppliers.records.index') : url('/suppliers') }}">
            <i class="fa fa-truck"></i>
            <span>Supplier Module</span>
        </a>
    </li>
@endif

@if($__showDistributionNew)
    <li class="nav-item {{ $request->segment(1) === 'distribution-new' ? 'active active-sub' : '' }}">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#distribution-new-menu"
           aria-expanded="{{ $request->segment(1) === 'distribution-new' ? 'true' : 'false' }}"
           aria-controls="distribution-new-menu">
            <i class="fa fa-truck-loading"></i>
            <span>Distribution New</span>
        </a>
        <div id="distribution-new-menu"
             class="collapse {{ $request->segment(1) === 'distribution-new' ? 'show' : '' }}"
             data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Distribution New:</h6>
                <a class="collapse-item {{ request()->routeIs('distributionnew.dashboard*') ? 'active' : '' }}"
                   href="{{ \Illuminate\Support\Facades\Route::has('distributionnew.dashboard') ? route('distributionnew.dashboard') : url('/distribution-new') }}">Dashboard</a>
                <a class="collapse-item {{ request()->routeIs('distributionnew.sales-orders.*') ? 'active' : '' }}"
                   href="{{ \Illuminate\Support\Facades\Route::has('distributionnew.sales-orders.index') ? route('distributionnew.sales-orders.index') : url('/distribution-new/sales-orders') }}">Sales Orders</a>
                <a class="collapse-item {{ request()->routeIs('distributionnew.sales-invoices.*') ? 'active' : '' }}"
                   href="{{ \Illuminate\Support\Facades\Route::has('distributionnew.sales-invoices.index') ? route('distributionnew.sales-invoices.index') : url('/distribution-new/sales-invoices') }}">Sales Invoices</a>
                <a class="collapse-item {{ request()->routeIs('distributionnew.loading.*') ? 'active' : '' }}"
                   href="{{ \Illuminate\Support\Facades\Route::has('distributionnew.loading.index') ? route('distributionnew.loading.index') : url('/distribution-new/loading') }}">Loading</a>
                <a class="collapse-item {{ request()->routeIs('distributionnew.unloading.*') ? 'active' : '' }}"
                   href="{{ \Illuminate\Support\Facades\Route::has('distributionnew.unloading.index') ? route('distributionnew.unloading.index') : url('/distribution-new/unloading') }}">Unloading</a>
                <a class="collapse-item {{ request()->routeIs('distributionnew.reports.*') ? 'active' : '' }}"
                   href="{{ \Illuminate\Support\Facades\Route::has('distributionnew.reports.index') ? route('distributionnew.reports.index') : url('/distribution-new/reports') }}">Reports</a>
                <a class="collapse-item {{ request()->routeIs('distributionnew.settings.*') ? 'active' : '' }}"
                   href="{{ \Illuminate\Support\Facades\Route::has('distributionnew.settings.index') ? route('distributionnew.settings.index') : url('/distribution-new/settings') }}">Settings</a>
            </div>
        </div>
    </li>
@endif

@if($__showStockTransferNew)
    <li class="nav-item {{ $request->segment(1) === 'stock-transfer-new' ? 'active active-sub' : '' }}">
        <a class="nav-link" href="{{ url('/stock-transfer-new') }}">
            <i class="fa fa-exchange"></i>
            <span>Stock Transfer New</span>
        </a>
    </li>
@endif

@if($__showStockAdjustmentNew)
    <li class="nav-item {{ $request->segment(1) === 'stock-adjustment-new' ? 'active active-sub' : '' }}">
        <a class="nav-link" href="{{ url('/stock-adjustment-new') }}">
            <i class="fa fa-sliders"></i>
            <span>Stock Adjustment New</span>
        </a>
    </li>
@endif
