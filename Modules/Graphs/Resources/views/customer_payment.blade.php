@extends('layouts.app')

@section('title', 'Graphs - Customer & Payment Analytics')

@section('css')
    @php
        // Module-owned styles only; every selector is scoped to .gr-app.
        $graphsInlineCss = @file_get_contents(base_path('Modules/Graphs/Resources/assets/css/graphs.css')) ?: '';
    @endphp
    <style>{!! $graphsInlineCss !!}</style>
@endsection

@section('content')
<section class="content-header" style="padding-bottom:0;">
    <h1 class="sr-only">Graphs - Customer &amp; Payment Analytics</h1>
</section>
<section class="content graphs-module-content">
<div class="gr-app gr-customer-page">
    <div class="gr-tabs-shell">
        <nav class="gr-tabs" aria-label="Graphs module pages">
            <a href="{{ route('graphs.index') }}" class="gr-tab">Stocks &amp; Sales Analytical</a>
            <a href="{{ route('graphs.financial') }}" class="gr-tab">Financial &amp; Profitability Analytics</a>
            <a href="{{ route('graphs.operational') }}" class="gr-tab">Operational &amp; Loss Analytics</a>
            <a href="{{ route('graphs.customer-payment') }}" class="gr-tab active" aria-current="page">Customer &amp; Payment Analytics</a>
            <a href="{{ route('graphs.management-dashboard') }}" class="gr-tab">Management Dashboard Structure</a>
        </nav>
    </div>

    <div class="gr-subtabs-shell gr-customer-subtabs-shell">
        <nav class="gr-subtabs gr-customer-subtabs" aria-label="Customer and payment analytics sections">
            <button type="button" class="gr-subtab active" data-cp-tab="payment">Payment Method Split</button>
            <button type="button" class="gr-subtab" data-cp-tab="pump">Pump &amp; Shift-wise Sales Analysis</button>
            <button type="button" class="gr-subtab" data-cp-tab="credit">Top Credit Customers / Fleet Analysis</button>
        </nav>
    </div>

    <div class="gr-page-head">
        <div class="gr-title-wrap">
            <div class="gr-title-row">
                <div class="gr-title-icon" aria-hidden="true">%</div>
                <div class="gr-title-copy">
                    <h1>Graphs</h1>
                    <p>Customer payment mix, pump/shift sales and top credit customer/fleet analytics.</p>
                </div>
            </div>
            <div class="gr-breadcrumbs">
                <a href="{{ url('/') }}">Home</a>
                <span>›</span>
                <span>Graphs</span>
                <span>›</span>
                <strong>Customer &amp; Payment Analytics</strong>
            </div>
        </div>
        <div class="gr-head-actions">
            <a href="{{ url('/') }}" class="gr-action gr-action-secondary">Back to Home</a>
            <button type="button" class="gr-action gr-action-primary" id="grCpRefresh">
                <span class="gr-refresh-mark" aria-hidden="true">↻</span> Refresh
            </button>
        </div>
    </div>

    <section class="gr-filter-panel" aria-label="Customer and payment analytics filters">
        <div class="gr-filter-title">
            <div>
                <strong>Analytics Filters</strong>
                <span>Choose the location, reporting basis and system-standard date range</span>
            </div>
        </div>
        <div class="gr-filter-row gr-cp-filter-row">
            <div class="gr-field gr-field-location">
                <label for="grCpLocation">Location</label>
                <select id="grCpLocation">
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                        <option value="{{ $location['id'] }}">{{ $location['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="gr-field">
                <label for="grCpPeriod">Period</label>
                <select id="grCpPeriod">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>
            <div class="gr-field gr-field-date-range">
                <label for="grCpDateRange">Date Range</label>
                <div class="gr-date-range-control">
                    <span class="gr-date-range-icon" aria-hidden="true"><i class="fa fa-calendar"></i></span>
                    <input type="text" id="grCpDateRange" class="form-control gr-date-range-picker" value="{{ $dateRangeDisplay }}" autocomplete="off" readonly>
                </div>
                <input type="hidden" id="grCpStartDate" value="{{ $today }}">
                <input type="hidden" id="grCpEndDate" value="{{ $today }}">
            </div>
            <div class="gr-filter-action">
                <button type="button" class="gr-action gr-action-apply" id="grCpApply">Apply Filters</button>
            </div>
        </div>
    </section>

    <main class="gr-main gr-cp-main">
        <section class="gr-cp-panel" data-cp-panel="payment">
            <section class="gr-cp-kpi-grid gr-cp-kpi-grid-five" aria-label="Payment method summary">
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon cash" aria-hidden="true">C</div><div class="gr-kpi-copy"><span>Cash</span><strong id="cpCashAmount">0.00</strong><small id="cpCashPct">0.00%</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon card" aria-hidden="true">Cd</div><div class="gr-kpi-copy"><span>Cards</span><strong id="cpCardAmount">0.00</strong><small id="cpCardPct">0.00%</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon credit" aria-hidden="true">Cr</div><div class="gr-kpi-copy"><span>Credit Sales</span><strong id="cpCreditAmount">0.00</strong><small id="cpCreditPct">0.00%</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon bank" aria-hidden="true">On</div><div class="gr-kpi-copy"><span>Online Transfers</span><strong id="cpOnlineAmount">0.00</strong><small id="cpOnlinePct">0.00%</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon neutral" aria-hidden="true">T</div><div class="gr-kpi-copy"><span>Total Analysed</span><strong id="cpPaymentTotal">0.00</strong><small>Four required payment methods</small></div></article>
            </section>

            <section class="gr-card">
                <header class="gr-card-head">
                    <div class="gr-card-title"><span class="gr-section-no">01</span><div><h2>Payment Method Split</h2><p>Cash, Cards, Credit Sales and Online Transfers as a percentage of the selected period.</p></div></div>
                    <div class="gr-legend-note"><span id="cpPaymentBasis">Daily basis</span></div>
                </header>
                <div class="gr-card-body">
                    <div class="gr-chart-frame gr-cp-pie-frame"><canvas id="paymentSplitChart"></canvas></div>
                    <div class="gr-inline-note">Credit-sale transactions are shown in the Credit Sales slice and are not counted again when later collections are posted. Cash-deposit movements are excluded from Online Transfers because they represent banking of cash already counted as Cash.</div>
                </div>
            </section>

            <section class="gr-card">
                <header class="gr-card-head">
                    <div class="gr-card-title"><span class="gr-section-no">02</span><div><h2>Payment Method Verification</h2><p>Period-wise amounts behind the pie chart.</p></div></div>
                </header>
                <div class="gr-card-body gr-table-card-body">
                    <div class="gr-data-table-wrap">
                        <table class="gr-table gr-cp-table">
                            <thead><tr><th>Period</th><th class="gr-num">Cash</th><th class="gr-num">Cards</th><th class="gr-num">Credit Sales</th><th class="gr-num">Online Transfers</th><th class="gr-num">Total</th></tr></thead>
                            <tbody id="cpPaymentRows"><tr><td colspan="6" class="gr-table-empty">Loading payment method data…</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </section>
        </section>

        <section class="gr-cp-panel" data-cp-panel="pump" hidden>
            <section class="gr-cp-kpi-grid" aria-label="Pump and shift sales summary">
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon fuel" aria-hidden="true">S</div><div class="gr-kpi-copy"><span>Sales Amount</span><strong id="cpPumpSalesAmount">0.00</strong><small>Finalized meter sales</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon neutral" aria-hidden="true">Q</div><div class="gr-kpi-copy"><span>Sold Quantity</span><strong id="cpPumpSoldQty">0.000</strong><small>Meter sold quantity</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon pump" aria-hidden="true">P</div><div class="gr-kpi-copy"><span>Pumps</span><strong id="cpPumpCount">0</strong><small>Pumps with sales</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon shift" aria-hidden="true">Sh</div><div class="gr-kpi-copy"><span>Shifts</span><strong id="cpShiftCount">0</strong><small>Shifts with sales</small></div></article>
            </section>

            <section class="gr-card">
                <header class="gr-card-head">
                    <div class="gr-card-title"><span class="gr-section-no">01</span><div><h2>Pump &amp; Shift-wise Sales Analysis</h2><p>Bar chart of finalized meter sales on Daily, Weekly, Monthly or Yearly basis.</p></div></div>
                    <div class="gr-legend-note">Each colour represents a pump</div>
                </header>
                <div class="gr-card-body">
                    <div class="gr-chart-frame gr-cp-pump-frame"><canvas id="cpPumpShiftChart"></canvas></div>
                    <div class="gr-inline-note">Uses the same finalized meter-sale source as Operational &amp; Loss Analytics. Duplicate synchronized meter lines are suppressed before totals are calculated.</div>
                </div>
            </section>

            <section class="gr-card">
                <header class="gr-card-head">
                    <div class="gr-card-title"><span class="gr-section-no">02</span><div><h2>Pump &amp; Shift Sales Detail</h2><p>Period-wise quantity and sales amount for verification.</p></div></div>
                </header>
                <div class="gr-card-body gr-table-card-body">
                    <div class="gr-data-table-wrap">
                        <table class="gr-table gr-cp-table">
                            <thead><tr><th>Period</th><th>Pump</th><th>Shift</th><th class="gr-num">Sold Qty</th><th class="gr-num">Sales Amount</th></tr></thead>
                            <tbody id="cpPumpShiftRows"><tr><td colspan="5" class="gr-table-empty">Loading pump &amp; shift sales…</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </section>
        </section>

        <section class="gr-cp-panel" data-cp-panel="credit" hidden>
            <section class="gr-cp-kpi-grid" aria-label="Top credit customer summary">
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon credit" aria-hidden="true">Cr</div><div class="gr-kpi-copy"><span>Credit Sales</span><strong id="cpCreditSalesTotal">0.00</strong><small>Selected date range</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon neutral" aria-hidden="true">Cu</div><div class="gr-kpi-copy"><span>Credit Customers</span><strong id="cpCreditCustomers">0</strong><small>Customers with credit sales</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon commission" aria-hidden="true">Tx</div><div class="gr-kpi-copy"><span>Transactions</span><strong id="cpCreditTransactions">0</strong><small>Finalized credit sales</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon pump" aria-hidden="true">F</div><div class="gr-kpi-copy"><span>Fleet Vehicles</span><strong id="cpFleetCount">0</strong><small>Linked fleet vehicles</small></div></article>
            </section>

            <section class="gr-card">
                <header class="gr-card-head">
                    <div class="gr-card-title"><span class="gr-section-no">01</span><div><h2>Top Credit Customers / Fleet Analysis</h2><p>Horizontal stacked bar chart of the top credit customers, segmented by the selected reporting basis.</p></div></div>
                    <div class="gr-legend-note"><span id="cpCreditBasis">Daily basis</span></div>
                </header>
                <div class="gr-card-body">
                    <div class="gr-chart-frame gr-cp-credit-frame"><canvas id="topCreditCustomersChart"></canvas></div>
                    <div class="gr-inline-note">The chart ranks the top 10 customers by finalized credit sales in the selected date range. Where a sale is linked to a fleet vehicle, the vehicle is included in the verification table below.</div>
                </div>
            </section>

            <section class="gr-card">
                <header class="gr-card-head">
                    <div class="gr-card-title"><span class="gr-section-no">02</span><div><h2>Top Credit Customer Detail</h2><p>Customer, credit-sale and linked fleet information used for the analysis.</p></div></div>
                </header>
                <div class="gr-card-body gr-table-card-body">
                    <div class="gr-data-table-wrap">
                        <table class="gr-table gr-cp-credit-table">
                            <thead><tr><th>Rank</th><th>Customer</th><th>Customer Code</th><th>Mobile</th><th class="gr-num">Credit Sales</th><th class="gr-num">Transactions</th><th class="gr-num">Fleet Count</th><th>Fleet / Vehicle Nos.</th></tr></thead>
                            <tbody id="cpCreditRows"><tr><td colspan="8" class="gr-table-empty">Loading top credit customers…</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="gr-card">
                <header class="gr-card-head">
                    <div class="gr-card-title"><span class="gr-section-no">03</span><div><h2>Credit Sales by Reporting Period</h2><p>Period breakdown for the customers shown in the Top Credit Customers chart.</p></div></div>
                </header>
                <div class="gr-card-body gr-table-card-body">
                    <div class="gr-data-table-wrap">
                        <table class="gr-table gr-cp-table">
                            <thead><tr><th>Period</th><th>Customer</th><th class="gr-num">Credit Sales</th></tr></thead>
                            <tbody id="cpCreditPeriodRows"><tr><td colspan="3" class="gr-table-empty">Loading credit sales period detail…</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </section>
        </section>
    </main>

    <div class="gr-status-message" id="grCpStatusMessage" hidden role="status" aria-live="polite"></div>
</div>
</section>
@endsection

@section('model-scritps')
<script>
window.GRAPHS_CUSTOMER_PAYMENT_CONFIG = {
    paymentMethodSplitUrl: @json(route('graphs.data.payment-method-split')),
    pumpShiftSalesUrl: @json(route('graphs.data.customer-pump-shift-sales')),
    topCreditCustomersUrl: @json(route('graphs.data.top-credit-customers')),
    currencyPrecision: {{ (int) $currencyPrecision }}
};
</script>
<script src="{{ asset('modules/graphs/vendor/chart.min.js') }}?v={{ $assetVersion }}" onerror="this.onerror=null;this.src='{{ route('graphs.asset',['type'=>'vendor','file'=>'chart.min.js','v'=>$assetVersion]) }}'"></script>
<script src="{{ asset('modules/graphs/js/customer_payment.js') }}?v={{ $assetVersion }}" onerror="this.onerror=null;this.src='{{ route('graphs.asset',['type'=>'js','file'=>'customer_payment.js','v'=>$assetVersion]) }}'"></script>
@endsection
