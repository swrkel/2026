@extends('layouts.app')

@section('title', 'Graphs - Operational & Loss Analytics')

@section('css')
    @php
        // Module-owned styles only; every selector is scoped to .gr-app.
        $graphsInlineCss = @file_get_contents(base_path('Modules/Graphs/Resources/assets/css/graphs.css')) ?: '';
    @endphp
    <style>{!! $graphsInlineCss !!}</style>
@endsection

@section('content')
<section class="content-header" style="padding-bottom:0;">
    <h1 class="sr-only">Graphs - Operational &amp; Loss Analytics</h1>
</section>
<section class="content graphs-module-content">
<div class="gr-app gr-operational-page">
    <div class="gr-tabs-shell">
        <nav class="gr-tabs" aria-label="Graphs module pages">
            <a href="{{ route('graphs.index') }}" class="gr-tab">Stocks &amp; Sales Analytical</a>
            <a href="{{ route('graphs.financial') }}" class="gr-tab">Financial &amp; Profitability Analytics</a>
            <a href="{{ route('graphs.operational') }}" class="gr-tab active" aria-current="page">Operational &amp; Loss Analytics</a>
            <a href="{{ route('graphs.customer-payment') }}" class="gr-tab">Customer &amp; Payment Analytics</a>
            <a href="{{ route('graphs.management-dashboard') }}" class="gr-tab">Management Dashboard Structure</a>
        </nav>
    </div>

    <div class="gr-subtabs-shell gr-operational-subtabs-shell">
        <nav class="gr-subtabs gr-operational-subtabs" aria-label="Operational analytics sections">
            <button type="button" class="gr-subtab active" data-op-tab="dip">Fuel Loss / Dip vs. Meter Variance Report</button>
            <button type="button" class="gr-subtab" data-op-tab="pump">Pump &amp; Shift-wise Sales Analysis</button>
        </nav>
    </div>

    <div class="gr-page-head">
        <div class="gr-title-wrap">
            <div class="gr-title-row">
                <div class="gr-title-icon" aria-hidden="true">↕</div>
                <div class="gr-title-copy">
                    <h1>Graphs</h1>
                    <p>Operational performance, fuel variance and pump/shift sales analytics.</p>
                </div>
            </div>
            <div class="gr-breadcrumbs">
                <a href="{{ url('/') }}">Home</a>
                <span>›</span>
                <span>Graphs</span>
                <span>›</span>
                <strong>Operational &amp; Loss Analytics</strong>
            </div>
        </div>
        <div class="gr-head-actions">
            <a href="{{ url('/') }}" class="gr-action gr-action-secondary">Back to Home</a>
            <button type="button" class="gr-action gr-action-primary" id="grOpRefresh">
                <span class="gr-refresh-mark" aria-hidden="true">↻</span> Refresh
            </button>
        </div>
    </div>

    <section class="gr-filter-panel" aria-label="Operational analytics filters">
        <div class="gr-filter-title">
            <div>
                <strong>Analytics Filters</strong>
                <span>Choose the location, reporting basis and system-standard date range</span>
            </div>
        </div>
        <div class="gr-filter-row gr-op-filter-row">
            <div class="gr-field gr-field-location">
                <label for="grOpLocation">Location</label>
                <select id="grOpLocation">
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                        <option value="{{ $location['id'] }}">{{ $location['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="gr-field">
                <label for="grOpPeriod">Period</label>
                <select id="grOpPeriod">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </div>
            <div class="gr-field gr-field-date-range">
                <label for="grOpDateRange">Date Range</label>
                <div class="gr-date-range-control">
                    <span class="gr-date-range-icon" aria-hidden="true"><i class="fa fa-calendar"></i></span>
                    <input type="text" id="grOpDateRange" class="form-control gr-date-range-picker" value="{{ $dateRangeDisplay }}" autocomplete="off" readonly>
                </div>
                <input type="hidden" id="grOpStartDate" value="{{ $today }}">
                <input type="hidden" id="grOpEndDate" value="{{ $today }}">
            </div>
            <div class="gr-filter-action">
                <button type="button" class="gr-action gr-action-apply" id="grOpApply">Apply Filters</button>
            </div>
        </div>
    </section>

    <main class="gr-main gr-op-main">
        <section class="gr-op-panel" data-op-panel="dip">
            <section class="gr-op-kpi-grid" aria-label="Fuel variance summary">
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon fuel" aria-hidden="true">D</div><div class="gr-kpi-copy"><span>Dip Stock</span><strong id="opDipStock">0.000</strong><small>Latest period reading</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon neutral" aria-hidden="true">S</div><div class="gr-kpi-copy"><span>System Stock</span><strong id="opSystemStock">0.000</strong><small>Latest period reading</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon variance" aria-hidden="true">±</div><div class="gr-kpi-copy"><span>Variance</span><strong id="opVariance">0.000</strong><small>Dip stock − system stock</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon alert" aria-hidden="true">L</div><div class="gr-kpi-copy"><span>Fuel Loss</span><strong id="opFuelLoss">0.000</strong><small>System stock above dip stock</small></div></article>
            </section>

            <section class="gr-card">
                <header class="gr-card-head">
                    <div class="gr-card-title"><span class="gr-section-no">01</span><div><h2>Fuel Loss / Dip vs. Meter Variance Report</h2><p>Dip Stock compared with System Stock on the selected Daily, Weekly or Monthly basis.</p></div></div>
                    <div class="gr-legend-note"><span class="gr-status-dot"></span><span id="opDipReadingAt">Latest recorded dip</span></div>
                </header>
                <div class="gr-card-body">
                    <div class="gr-chart-frame gr-op-chart-frame"><canvas id="dipVarianceChart"></canvas></div>
                </div>
            </section>

            <section class="gr-card">
                <header class="gr-card-head">
                    <div class="gr-card-title"><span class="gr-section-no">02</span><div><h2>Dip &amp; System Stock Detail</h2><p>Tank-level readings used to build the variance chart.</p></div></div>
                </header>
                <div class="gr-card-body gr-table-card-body">
                    <div class="gr-data-table-wrap">
                        <table class="gr-table gr-op-table">
                            <thead><tr><th>Period</th><th>Tank</th><th class="gr-num">Dip Stock</th><th class="gr-num">System Stock</th><th class="gr-num">Variance</th><th class="gr-num">Fuel Loss</th><th>Reading At</th></tr></thead>
                            <tbody id="dipVarianceRows"><tr><td colspan="7" class="gr-table-empty">Loading dip variance data…</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </section>
        </section>

        <section class="gr-op-panel" data-op-panel="pump" hidden>
            <section class="gr-op-kpi-grid" aria-label="Pump and shift sales summary">
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon fuel" aria-hidden="true">S</div><div class="gr-kpi-copy"><span>Sales Amount</span><strong id="opPumpSalesAmount">0.00</strong><small>Finalized meter sales</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon neutral" aria-hidden="true">Q</div><div class="gr-kpi-copy"><span>Sold Quantity</span><strong id="opPumpSoldQty">0.000</strong><small>Meter sold quantity</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon pump" aria-hidden="true">P</div><div class="gr-kpi-copy"><span>Pumps</span><strong id="opPumpCount">0</strong><small>Pumps with sales</small></div></article>
                <article class="gr-kpi-card gr-fin-kpi"><div class="gr-kpi-icon shift" aria-hidden="true">Sh</div><div class="gr-kpi-copy"><span>Shifts</span><strong id="opShiftCount">0</strong><small>Shifts with sales</small></div></article>
            </section>

            <section class="gr-card">
                <header class="gr-card-head">
                    <div class="gr-card-title"><span class="gr-section-no">01</span><div><h2>Pump &amp; Shift-wise Sales Analysis</h2><p>Bar chart of finalized meter sales by reporting period, shift and pump.</p></div></div>
                    <div class="gr-legend-note">Each colour represents a pump</div>
                </header>
                <div class="gr-card-body">
                    <div class="gr-chart-frame gr-op-chart-frame gr-op-pump-chart-frame"><canvas id="pumpShiftChart"></canvas></div>
                    <div class="gr-inline-note">The chart uses saved/finalized settlement meter readings. Duplicate synced meter lines are suppressed before totals are calculated.</div>
                </div>
            </section>

            <section class="gr-card">
                <header class="gr-card-head">
                    <div class="gr-card-title"><span class="gr-section-no">02</span><div><h2>Pump &amp; Shift Sales Detail</h2><p>Period-wise quantity and sales amount for verification.</p></div></div>
                </header>
                <div class="gr-card-body gr-table-card-body">
                    <div class="gr-data-table-wrap">
                        <table class="gr-table gr-op-table">
                            <thead><tr><th>Period</th><th>Pump</th><th>Shift</th><th class="gr-num">Sold Qty</th><th class="gr-num">Sales Amount</th></tr></thead>
                            <tbody id="pumpShiftRows"><tr><td colspan="5" class="gr-table-empty">Loading pump &amp; shift sales…</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </section>
        </section>
    </main>

    <div class="gr-status-message" id="grOpStatusMessage" hidden role="status" aria-live="polite"></div>
</div>
</section>
@endsection

@section('model-scritps')
<script>
window.GRAPHS_OPERATIONAL_CONFIG = {
    dipVarianceUrl: @json(route('graphs.data.dip-variance')),
    pumpShiftSalesUrl: @json(route('graphs.data.pump-shift-sales')),
    currencyPrecision: {{ (int) $currencyPrecision }}
};
</script>
<script src="{{ asset('modules/graphs/vendor/chart.min.js') }}?v={{ $assetVersion }}" onerror="this.onerror=null;this.src='{{ route('graphs.asset',['type'=>'vendor','file'=>'chart.min.js','v'=>$assetVersion]) }}'"></script>
<script src="{{ asset('modules/graphs/js/operational.js') }}?v={{ $assetVersion }}" onerror="this.onerror=null;this.src='{{ route('graphs.asset',['type'=>'js','file'=>'operational.js','v'=>$assetVersion]) }}'"></script>
@endsection
