@extends('layouts.app')

@section('title', 'Graphs - Financial & Profitability Analytics')

@section('css')
    @php
        // Graphs styles are module-owned and every selector is scoped to .gr-app.
        // This keeps the ERP sidebar/header/global components completely untouched.
        $graphsInlineCss = @file_get_contents(base_path('Modules/Graphs/Resources/assets/css/graphs.css')) ?: '';
    @endphp
    <style>{!! $graphsInlineCss !!}</style>
@endsection

@section('content')
<section class="content-header" style="padding-bottom:0;">
    <h1 class="sr-only">Graphs - Financial &amp; Profitability Analytics</h1>
</section>
<section class="content graphs-module-content">
<div class="gr-app gr-financial-page">
    <div class="gr-tabs-shell">
        <nav class="gr-tabs" aria-label="Graphs module pages">
            <a href="{{ route('graphs.index') }}" class="gr-tab">Stocks &amp; Sales Analytical</a>
            <a href="{{ route('graphs.financial') }}" class="gr-tab active" aria-current="page">Financial &amp; Profitability Analytics</a>
            <a href="{{ route('graphs.operational') }}" class="gr-tab">Operational &amp; Loss Analytics</a>
            <a href="{{ route('graphs.customer-payment') }}" class="gr-tab">Customer &amp; Payment Analytics</a>
            <a href="{{ route('graphs.management-dashboard') }}" class="gr-tab">Management Dashboard Structure</a>
        </nav>
    </div>

    <div class="gr-subtabs-shell">
        <nav class="gr-subtabs" aria-label="Financial analytics sections">
            <button type="button" class="gr-subtab active" data-fin-tab="reconciliation">Daily Reconciliation &amp; Cash Flow</button>
            <button type="button" class="gr-subtab" data-fin-tab="profitability">Profit Margin per Fuel Type</button>
            <button type="button" class="gr-subtab" data-fin-tab="ageing">Debtors Ageing Analysis</button>
        </nav>
    </div>

    <div class="gr-page-head">
        <div class="gr-title-wrap">
            <div class="gr-title-row">
                <div class="gr-title-icon" aria-hidden="true">▦</div>
                <div class="gr-title-copy">
                    <h1>Graphs</h1>
                    <p>Financial reconciliation, profitability and debtor ageing analytics.</p>
                </div>
            </div>
            <div class="gr-breadcrumbs">
                <a href="{{ url('/') }}">Home</a>
                <span>›</span>
                <span>Graphs</span>
                <span>›</span>
                <strong>Financial &amp; Profitability Analytics</strong>
            </div>
        </div>
        <div class="gr-head-actions">
            <a href="{{ url('/') }}" class="gr-action gr-action-secondary">Back to Home</a>
            <button type="button" class="gr-action gr-action-primary" id="grFinRefresh">
                <span class="gr-refresh-mark" aria-hidden="true">↻</span> Refresh
            </button>
        </div>
    </div>

    <section class="gr-filter-panel" aria-label="Financial analytics filters">
        <div class="gr-filter-title">
            <div>
                <strong>Analytics Filters</strong>
                <span>Choose the location and system-standard date range</span>
            </div>
        </div>
        <div class="gr-filter-row gr-fin-filter-row">
            <div class="gr-field gr-field-location">
                <label for="grFinLocation">Location</label>
                <select id="grFinLocation">
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                        <option value="{{ $location['id'] }}">{{ $location['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="gr-field gr-field-date-range">
                <label for="grFinDateRange">Date Range</label>
                <div class="gr-date-range-control">
                    <span class="gr-date-range-icon" aria-hidden="true"><i class="fa fa-calendar"></i></span>
                    <input type="text" id="grFinDateRange" class="form-control gr-date-range-picker" value="{{ $dateRangeDisplay }}" autocomplete="off" readonly>
                </div>
                <input type="hidden" id="grFinStartDate" value="{{ $today }}">
                <input type="hidden" id="grFinEndDate" value="{{ $today }}">
            </div>
            <div class="gr-filter-action">
                <button type="button" class="gr-action gr-action-apply" id="grFinApply">Apply Filters</button>
            </div>
        </div>
    </section>

    <main class="gr-main gr-fin-main">
        <section class="gr-fin-panel" data-fin-panel="reconciliation">
            <section class="gr-fin-kpi-grid" aria-label="Reconciliation summary">
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon fuel" aria-hidden="true">F</div><div class="gr-kpi-copy"><span>Fuel Sales</span><strong id="finFuelSales">0.00</strong><small>Selected date range</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon cash" aria-hidden="true">C</div><div class="gr-kpi-copy"><span>Cash Sales</span><strong id="finCashSales">0.00</strong><small>Finalized settlements</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon card" aria-hidden="true">D</div><div class="gr-kpi-copy"><span>Card Sales</span><strong id="finCardSales">0.00</strong><small>Finalized settlements</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon credit" aria-hidden="true">Cr</div><div class="gr-kpi-copy"><span>Credit Sales</span><strong id="finCreditSales">0.00</strong><small>Finalized fuel credit sales</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon bank" aria-hidden="true">B</div><div class="gr-kpi-copy"><span>Bank Deposited</span><strong id="finBankDeposited">0.00</strong><small>Settlement deposits</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon variance" aria-hidden="true">±</div><div class="gr-kpi-copy"><span>Variation</span><strong id="finVariation">0.00</strong><small>Recorded cash − expected cash</small></div></article>
            </section>

            <section class="gr-card">
                <header class="gr-card-head">
                    <div class="gr-card-title"><span class="gr-section-no">01</span><div><h2>Cash Flow Waterfall / Reconciliation Summary</h2><p>Follow fuel sales through card, credit, expected cash, variation and bank deposit.</p></div></div>
                    <div class="gr-legend-note"><span class="gr-status-dot"></span> Finalized records only</div>
                </header>
                <div class="gr-card-body">
                    <div class="gr-chart-frame gr-fin-chart-frame"><canvas id="reconciliationChart"></canvas></div>
                    <div class="gr-fin-summary-grid" aria-label="Reconciliation verification totals">
                        <div><span>Fuel Sales</span><strong id="recFuel">0.00</strong></div>
                        <div><span>Card Sales</span><strong id="recCard">0.00</strong></div>
                        <div><span>Credit Sales</span><strong id="recCredit">0.00</strong></div>
                        <div><span>Expected Cash</span><strong id="recExpected">0.00</strong></div>
                        <div><span>Recorded Cash</span><strong id="recCash">0.00</strong></div>
                        <div><span>Bank Deposited</span><strong id="recBank">0.00</strong></div>
                        <div><span>Cash After Deposit</span><strong id="recCashAfter">0.00</strong></div>
                        <div class="gr-fin-summary-variance"><span>Variation</span><strong id="recVariation">0.00</strong></div>
                    </div>
                </div>
            </section>

            <section class="gr-card">
                <header class="gr-card-head">
                    <div class="gr-card-title"><span class="gr-section-no">02</span><div><h2>Daily Reconciliation Detail</h2><p>Daily values for quick verification within the selected date range.</p></div></div>
                </header>
                <div class="gr-card-body gr-table-card-body">
                    <div class="gr-data-table-wrap">
                        <table class="gr-table">
                            <thead><tr><th>Date</th><th class="gr-num">Fuel Sales</th><th class="gr-num">Cash</th><th class="gr-num">Card</th><th class="gr-num">Credit</th><th class="gr-num">Expected Cash</th><th class="gr-num">Bank Deposit</th><th class="gr-num">Variation</th></tr></thead>
                            <tbody id="reconciliationRows"><tr><td colspan="8" class="gr-table-empty">Loading reconciliation data…</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </section>
        </section>

        <section class="gr-fin-panel" data-fin-panel="profitability" hidden>
            <section class="gr-fin-kpi-grid gr-fin-kpi-grid-five" aria-label="Fuel profitability summary">
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon fuel" aria-hidden="true">S</div><div class="gr-kpi-copy"><span>Fuel Sales</span><strong id="profitSales">0.00</strong><small>Total sale amount</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon neutral" aria-hidden="true">C</div><div class="gr-kpi-copy"><span>Cost of Sales</span><strong id="profitCost">0.00</strong><small>Linked/fallback fuel cost</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon commission" aria-hidden="true">CI</div><div class="gr-kpi-copy"><span>Commission Income</span><strong id="profitCommission">0.00</strong><small>Sales less fuel cost</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon profit" aria-hidden="true">NP</div><div class="gr-kpi-copy"><span>Net Profit</span><strong id="profitNet">0.00</strong><small>Auto-calculated</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon margin" aria-hidden="true">%</div><div class="gr-kpi-copy"><span>Profit Margin</span><strong id="profitMargin">0.00%</strong><small>Net profit / fuel sales</small></div></article>
            </section>

            <section class="gr-card">
                <header class="gr-card-head">
                    <div class="gr-card-title"><span class="gr-section-no">01</span><div><h2>Profit Margin per Fuel Type</h2><p>Stacked bar chart by Fuel Product subcategory.</p></div></div>
                </header>
                <div class="gr-card-body">
                    <div class="gr-chart-frame gr-fin-chart-frame"><canvas id="profitabilityChart"></canvas></div>
                    <div class="gr-inline-note">Net profit is auto-calculated from realized commission income: finalized fuel sales less the linked purchase cost (with product purchase-cost fallback where a historical sell line has no purchase-line link).</div>
                </div>
            </section>

            <section class="gr-card">
                <header class="gr-card-head">
                    <div class="gr-card-title"><span class="gr-section-no">02</span><div><h2>Fuel Subcategory Profit Detail</h2><p>Sales, cost, commission and net-profit verification by subcategory.</p></div></div>
                </header>
                <div class="gr-card-body gr-table-card-body">
                    <div class="gr-data-table-wrap">
                        <table class="gr-table">
                            <thead><tr><th>Fuel Product Subcategory</th><th class="gr-num">Sold Qty</th><th class="gr-num">Fuel Sales</th><th class="gr-num">Cost of Sales</th><th class="gr-num">Commission Income</th><th class="gr-num">Net Profit</th><th class="gr-num">Margin %</th><th class="gr-num">Commission / Unit</th></tr></thead>
                            <tbody id="profitabilityRows"><tr><td colspan="8" class="gr-table-empty">Loading profitability data…</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </section>
        </section>

        <section class="gr-fin-panel" data-fin-panel="ageing" hidden>
            <section class="gr-ageing-head-grid">
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon credit" aria-hidden="true">AR</div><div class="gr-kpi-copy"><span>Total Outstanding</span><strong id="ageingOutstanding">0.00</strong><small id="ageingAsOf">As of selected end date</small></div></article>
                <div class="gr-ageing-guide"><strong>Interactive Ageing Analysis</strong><span>Click an ageing bar to automatically load the customers included in that period.</span></div>
            </section>

            <section class="gr-card">
                <header class="gr-card-head">
                    <div class="gr-card-title"><span class="gr-section-no">01</span><div><h2>Debtors Ageing Analysis</h2><p>Outstanding credit sales grouped into 30, 60, 90 days and older.</p></div></div>
                    <div class="gr-legend-note">Click a bar to view customers</div>
                </header>
                <div class="gr-card-body">
                    <div class="gr-chart-frame gr-fin-chart-frame gr-clickable-chart"><canvas id="ageingChart"></canvas></div>
                    <div class="gr-ageing-badges" id="ageingBadges"></div>
                </div>
            </section>

            <section class="gr-card">
                <header class="gr-card-head">
                    <div class="gr-card-title"><span class="gr-section-no">02</span><div><h2 id="ageingCustomerTitle">Customers — 0-30 Days</h2><p id="ageingCustomerSubtitle">Customers included in the selected ageing bar.</p></div></div>
                    <div class="gr-ageing-total">Bucket Total <strong id="ageingBucketTotal">0.00</strong></div>
                </header>
                <div class="gr-card-body gr-table-card-body">
                    <div class="gr-data-table-wrap">
                        <table class="gr-table">
                            <thead><tr><th>Customer Code</th><th>Customer</th><th>Mobile</th><th class="gr-num">Invoices</th><th class="gr-num">Age Range</th><th class="gr-num">Outstanding</th></tr></thead>
                            <tbody id="ageingCustomerRows"><tr><td colspan="6" class="gr-table-empty">Select an ageing bar to load customers.</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </section>
        </section>
    </main>

    <div class="gr-status-message" id="grFinStatusMessage" hidden role="status" aria-live="polite"></div>
</div>
</section>
@endsection

@section('model-scritps')
<script>
window.GRAPHS_FINANCIAL_CONFIG = {
    reconciliationUrl: @json(route('graphs.data.reconciliation')),
    profitabilityUrl: @json(route('graphs.data.profitability')),
    ageingUrl: @json(route('graphs.data.debtors-ageing')),
    ageingCustomersUrl: @json(route('graphs.data.debtors-ageing.customers')),
    currencyPrecision: {{ (int) $currencyPrecision }}
};
</script>
<script src="{{ asset('modules/graphs/vendor/chart.min.js') }}?v={{ $assetVersion }}" onerror="this.onerror=null;this.src='{{ route('graphs.asset',['type'=>'vendor','file'=>'chart.min.js','v'=>$assetVersion]) }}'"></script>
<script src="{{ asset('modules/graphs/js/financial.js') }}?v={{ $assetVersion }}" onerror="this.onerror=null;this.src='{{ route('graphs.asset',['type'=>'js','file'=>'financial.js','v'=>$assetVersion]) }}'"></script>
@endsection
