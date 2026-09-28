@inject('request', 'Illuminate\Http\Request')

<li class="nav-item {{ in_array($request->segment(1), ['distribution']) ? 'active active-sub' : '' }}">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#distribution-menu"
        aria-expanded="true" aria-controls="distribution-menu">
        <i class="fa fa-truck"></i>
        <span>Distribution</span>
    </a>
    <div id="distribution-menu"
        class="collapse {{ in_array($request->segment(1), ['distribution']) ? 'show' : '' }}"
        aria-labelledby="headingPages" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Distribution:</h6>
            <a class="collapse-item {{ request()->routeIs('distribution.sales_orders.index') ? 'active' : '' }}"
                href="{{ \Illuminate\Support\Facades\Route::has('distribution.sales_orders.index') ? route('distribution.sales_orders.index') : url('/distribution/sales-orders') }}">Sales Orders</a>
            <a class="collapse-item {{ request()->routeIs('distribution.invoices.index') ? 'active' : '' }}"
                href="{{ \Illuminate\Support\Facades\Route::has('distribution.invoices.index') ? route('distribution.invoices.index') : url('/distribution/invoices') }}">Distribution Invoices</a>
            <a class="collapse-item {{ request()->routeIs('distribution.list_invoices.index') ? 'active' : '' }}"
                href="{{ \Illuminate\Support\Facades\Route::has('distribution.list_invoices.index') ? route('distribution.list_invoices.index') : url('/distribution/list-invoices') }}">List Distribution Invoices</a>
            <a class="collapse-item {{ request()->routeIs('distribution.vat-invoices.index') ? 'active' : '' }}"
                href="{{ \Illuminate\Support\Facades\Route::has('distribution.vat-invoices.index') ? route('distribution.vat-invoices.index') : url('/distribution/vat-invoices') }}">VAT Distribution Invoices</a>
            <a class="collapse-item {{ request()->routeIs('distribution.loadings.index') ? 'active' : '' }}"
                href="{{ \Illuminate\Support\Facades\Route::has('distribution.loadings.index') ? route('distribution.loadings.index') : url('/distribution/loadings') }}">Loadings</a>
            <a class="collapse-item {{ request()->routeIs('distribution.loadings.create') || ($request->segment(1) == 'distribution' && $request->segment(2) == 'loadings' && $request->segment(3) == 'create') ? 'active' : '' }}"
                href="{{ \Illuminate\Support\Facades\Route::has('distribution.loadings.create') ? route('distribution.loadings.create') : url('/distribution/loadings/create') }}">Add Loading</a>
            <a class="collapse-item {{ request()->routeIs('distribution.daily_summary.index') ? 'active' : '' }}"
                href="{{ \Illuminate\Support\Facades\Route::has('distribution.daily_summary.index') ? route('distribution.daily_summary.index') : url('/distribution/daily-summary-sheet') }}">Daily Summary Sheet</a>
            <a class="collapse-item {{ $request->segment(1) == 'distribution' && $request->segment(2) == 'settings' ? 'active' : '' }}"
                href="{{ url('/distribution/settings') }}">Settings</a>
        </div>
    </div>
</li>
