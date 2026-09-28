@extends('layouts.app')

@section('title', 'Graphs - Management Dashboard Structure')

@section('css')
    @php
        // Graphs-owned styles only. Every selector remains scoped to .gr-app.
        $graphsInlineCss = @file_get_contents(base_path('Modules/Graphs/Resources/assets/css/graphs.css')) ?: '';
    @endphp
    <style>{!! $graphsInlineCss !!}</style>
@endsection

@section('content')
<section class="content-header" style="padding-bottom:0;">
    <h1 class="sr-only">Graphs - Management Dashboard Structure</h1>
</section>
<section class="content graphs-module-content">
<div class="gr-app gr-management-page">
    <div class="gr-tabs-shell">
        <nav class="gr-tabs" aria-label="Graphs module pages">
            <a href="{{ route('graphs.index') }}" class="gr-tab">Stocks &amp; Sales Analytical</a>
            <a href="{{ route('graphs.financial') }}" class="gr-tab">Financial &amp; Profitability Analytics</a>
            <a href="{{ route('graphs.operational') }}" class="gr-tab">Operational &amp; Loss Analytics</a>
            <a href="{{ route('graphs.customer-payment') }}" class="gr-tab">Customer &amp; Payment Analytics</a>
            <a href="{{ route('graphs.management-dashboard') }}" class="gr-tab active" aria-current="page">Management Dashboard Structure</a>
        </nav>
    </div>

    <div class="gr-page-head">
        <div class="gr-title-wrap">
            <div class="gr-title-row">
                <div class="gr-title-icon" aria-hidden="true">M</div>
                <div class="gr-title-copy">
                    <h1>Management Dashboard Structure</h1>
                    <p>Today's management KPIs, tank status, 30-day sales trend, credit risk and fuel variance status.</p>
                </div>
            </div>
            <div class="gr-breadcrumbs">
                <a href="{{ url('/') }}">Home</a><span>›</span><span>Graphs</span><span>›</span><strong>Management Dashboard Structure</strong>
            </div>
        </div>
        <div class="gr-head-actions">
            <span class="gr-dashboard-date"><i class="fa fa-calendar"></i> {{ \Carbon\Carbon::parse($today)->format('d M Y') }}</span>
            <button type="button" class="gr-action gr-action-primary" id="grMgmtRefresh"><span class="gr-refresh-mark" aria-hidden="true">↻</span> Refresh</button>
        </div>
    </div>

    <section class="gr-filter-panel" aria-label="Management dashboard filter">
        <div class="gr-filter-title">
            <div><strong>Dashboard Filter</strong><span>View the management dashboard for all locations or one location</span></div>
        </div>
        <div class="gr-filter-row gr-mgmt-filter-row">
            <div class="gr-field gr-field-location">
                <label for="grMgmtLocation">Location</label>
                <select id="grMgmtLocation">
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                        <option value="{{ $location['id'] }}">{{ $location['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="gr-filter-action">
                <button type="button" class="gr-action gr-action-apply" id="grMgmtApply">Apply</button>
            </div>
        </div>
    </section>

    <section class="gr-kpi-grid gr-mgmt-kpi-grid" aria-label="Top management KPI cards">
        <article class="gr-kpi-card gr-mgmt-kpi">
            <div class="gr-kpi-icon fuel" aria-hidden="true">S</div>
            <div class="gr-kpi-copy"><span>Total Sales</span><strong id="mgmtTotalSales">0.00</strong><small>Today's finalized sales</small></div>
        </article>
        <article class="gr-kpi-card gr-mgmt-kpi gr-mgmt-litres-kpi">
            <div class="gr-kpi-icon neutral" aria-hidden="true">L</div>
            <div class="gr-kpi-copy"><span>Today Sold Liters</span><strong id="mgmtSoldLitres">0.000</strong><small>Fuel product subcategory total</small></div>
        </article>
        <article class="gr-kpi-card gr-mgmt-kpi">
            <div class="gr-kpi-icon cash" aria-hidden="true">R</div>
            <div class="gr-kpi-copy"><span>Outstanding Received</span><strong id="mgmtOutstandingReceived">0.00</strong><small>Prior outstanding received today</small></div>
        </article>
        <article class="gr-kpi-card gr-mgmt-kpi">
            <div class="gr-kpi-icon bank" aria-hidden="true">B</div>
            <div class="gr-kpi-copy"><span>Bank Deposited</span><strong id="mgmtBankDeposited">0.00</strong><small>Today's finalized settlements</small></div>
        </article>
    </section>

    <section class="gr-mgmt-fuel-breakdown" id="mgmtFuelBreakdown" aria-label="Today sold litres by fuel subcategory">
        <div class="gr-mgmt-fuel-label">Today Sold Liters by Fuel Subcategory</div>
        <div class="gr-mgmt-fuel-chips" id="mgmtFuelChips"><span class="gr-mgmt-empty-chip">Loading…</span></div>
    </section>

    <main class="gr-main gr-mgmt-main">
        <section class="gr-card">
            <header class="gr-card-head">
                <div class="gr-card-title"><span class="gr-section-no">01</span><div><h2>Tank Stock Status</h2><p>Current tank stock against Storage Volume, with the re-order level marker.</p></div></div>
                <div class="gr-legend-note"><span class="gr-status-dot"></span>Live calculated tank balance</div>
            </header>
            <div class="gr-card-body">
                <div class="gr-gauges gr-mgmt-gauges" id="mgmtTankGauges"></div>
                <div class="gr-empty" id="mgmtTankEmpty" hidden><strong>No tank data found</strong><span>No fuel tanks are available for the selected location.</span></div>
            </div>
        </section>

        <section class="gr-card">
            <header class="gr-card-head">
                <div class="gr-card-title"><span class="gr-section-no">02</span><div><h2>Sales Trend</h2><p>Daily finalized sales for the last 30 days, including today.</p></div></div>
                <div class="gr-legend-note" id="mgmtSalesTrendRange">Last 30 days</div>
            </header>
            <div class="gr-card-body">
                <div class="gr-chart-frame gr-chart-frame-large"><canvas id="mgmtSalesTrendChart"></canvas></div>
            </div>
        </section>

        <section class="gr-card">
            <header class="gr-card-head">
                <div class="gr-card-title"><span class="gr-section-no">03</span><div><h2>Credit Risk</h2><p>Customers with outstanding balances older than 30 days.</p></div></div>
                <div class="gr-credit-risk-summary"><strong id="mgmtRiskCustomers">0</strong> customers · <strong id="mgmtRiskTotal">0.00</strong> outstanding</div>
            </header>
            <div class="gr-card-body gr-table-card-body">
                <div class="gr-data-table-wrap gr-mgmt-risk-wrap">
                    <table class="gr-table gr-mgmt-risk-table">
                        <thead><tr><th>Customer</th><th>Customer Code</th><th>Mobile</th><th class="gr-num">Invoices</th><th class="gr-num">Age (Days)</th><th class="gr-num">Outstanding</th></tr></thead>
                        <tbody id="mgmtCreditRiskRows"><tr><td colspan="6" class="gr-table-empty">Loading credit-risk customers…</td></tr></tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="gr-card gr-variance-card" id="mgmtVarianceCard">
            <header class="gr-card-head">
                <div class="gr-card-title"><span class="gr-section-no">04</span><div><h2>Variance Status</h2><p>Today's latest Dip Stock vs System Stock variance. An absolute variance above 100 liters is a red alert.</p></div></div>
                <span class="gr-variance-badge" id="mgmtVarianceBadge">Checking</span>
            </header>
            <div class="gr-card-body">
                <div class="gr-variance-grid">
                    <div><span>Dip Stock</span><strong id="mgmtDipStock">0.000</strong><small>Liters</small></div>
                    <div><span>System Stock</span><strong id="mgmtSystemStock">0.000</strong><small>Liters</small></div>
                    <div class="gr-variance-main"><span>Variance</span><strong id="mgmtVarianceValue">0.000</strong><small>Liters</small></div>
                    <div><span>Fuel Loss</span><strong id="mgmtFuelLoss">0.000</strong><small>Liters</small></div>
                </div>
                <div class="gr-variance-alert-message" id="mgmtVarianceMessage">Variance is within the 100 liter alert threshold.</div>
                <div class="gr-variance-reading" id="mgmtVarianceReading">No dip reading time available.</div>
            </div>
        </section>
    </main>

    <div class="gr-status-message" id="grMgmtStatusMessage" hidden role="status" aria-live="polite"></div>
</div>
</section>
@endsection

@section('model-scritps')
<script>
window.GRAPHS_MANAGEMENT_CONFIG = {
    dashboardUrl: @json(route('graphs.data.management-dashboard')),
    currencyPrecision: {{ (int) $currencyPrecision }}
};
</script>
<script src="{{ asset('modules/graphs/vendor/chart.min.js') }}?v={{ $assetVersion }}" onerror="this.onerror=null;this.src='{{ route('graphs.asset',['type'=>'vendor','file'=>'chart.min.js','v'=>$assetVersion]) }}'"></script>
<script src="{{ asset('modules/graphs/js/management_dashboard.js') }}?v={{ $assetVersion }}" onerror="this.onerror=null;this.src='{{ route('graphs.asset',['type'=>'js','file'=>'management_dashboard.js','v'=>$assetVersion]) }}'"></script>
@endsection
