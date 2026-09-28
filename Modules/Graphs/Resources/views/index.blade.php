@extends('layouts.app')

@section('title', 'Graphs')

@section('css')
    @php
        // Graphs styles remain module-owned, but are inlined here so the page
        // is not dependent on a web-server static asset rule. Every selector
        // in graphs.css is scoped to .gr-app so the ERP sidebar/header/global
        // controls keep their existing styles and behaviour.
        $graphsInlineCss = @file_get_contents(base_path('Modules/Graphs/Resources/assets/css/graphs.css')) ?: '';
    @endphp
    <style>{!! $graphsInlineCss !!}</style>
@endsection

@section('content')
<section class="content-header" style="padding-bottom:0;">
    <h1 class="sr-only">Graphs - Stocks &amp; Sales Analytical</h1>
</section>
<section class="content graphs-module-content">
<div class="gr-app">
    <div class="gr-tabs-shell">
        <nav class="gr-tabs" aria-label="Graphs module pages">
            <a href="{{ route('graphs.index') }}" class="gr-tab active" aria-current="page">Stocks &amp; Sales Analytical</a>
            <a href="{{ route('graphs.financial') }}" class="gr-tab">Financial &amp; Profitability Analytics</a>
            <a href="{{ route('graphs.operational') }}" class="gr-tab">Operational &amp; Loss Analytics</a>
            <a href="{{ route('graphs.customer-payment') }}" class="gr-tab">Customer &amp; Payment Analytics</a>
            <a href="{{ route('graphs.management-dashboard') }}" class="gr-tab">Management Dashboard Structure</a>
        </nav>
    </div>

    <div class="gr-page-head">
        <div class="gr-title-wrap">
            <div class="gr-title-row">
                <div class="gr-title-icon" aria-hidden="true">▥</div>
                <div class="gr-title-copy">
                    <h1>Graphs</h1>
                    <p>Stock, tank and sales analytics for the selected business period.</p>
                </div>
            </div>
            <div class="gr-breadcrumbs">
                <a href="{{ url('/') }}">Home</a>
                <span>›</span>
                <span>Graphs</span>
                <span>›</span>
                <strong>Stocks &amp; Sales Analytical</strong>
            </div>
        </div>
        <div class="gr-head-actions">
            <a href="{{ url('/') }}" class="gr-action gr-action-secondary">Back to Home</a>
            <button type="button" class="gr-action gr-action-primary" id="grRefresh">
                <span class="gr-refresh-mark" aria-hidden="true">↻</span> Refresh
            </button>
        </div>
    </div>

    @if(count($alerts))
    <section class="gr-alert" id="grAlertBox" aria-live="polite">
        <div class="gr-alert-symbol" aria-hidden="true">!</div>
        <div class="gr-alert-content">
            <div class="gr-alert-heading">
                <strong>Re-order attention required</strong>
                <span>{{ count($alerts) }} tank{{ count($alerts) === 1 ? '' : 's' }} at or below the configured re-order level</span>
            </div>
            <div class="gr-alert-items">
                @foreach($alerts as $a)
                    <div class="gr-alert-item">
                        <span class="gr-alert-tank">{{ $a['tank'] }}</span>
                        <span class="gr-alert-product">{{ $a['product'] }}</span>
                        <span>Current <strong>{{ number_format($a['current'],3) }}</strong></span>
                        <span>Re-order <strong>{{ number_format($a['reorder'],3) }}</strong></span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <section class="gr-filter-panel" aria-label="Analytics filters">
        <div class="gr-filter-title">
            <div>
                <strong>Analytics Filters</strong>
                <span>Choose the location and reporting period</span>
            </div>
        </div>
        <div class="gr-filter-row">
            <div class="gr-field gr-field-location">
                <label for="grLocation">Location</label>
                <select id="grLocation">
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                        <option value="{{ $location['id'] }}">{{ $location['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="gr-field">
                <label for="grPeriod">Period</label>
                <select id="grPeriod">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </div>
            <div class="gr-field gr-field-date-range">
                <label for="grDateRange">Date Range</label>
                <div class="gr-date-range-control">
                    <span class="gr-date-range-icon" aria-hidden="true"><i class="fa fa-calendar"></i></span>
                    <input type="text" id="grDateRange" class="form-control gr-date-range-picker" value="{{ $dateRangeDisplay }}" autocomplete="off" readonly>
                </div>
                <input type="hidden" id="grStartDate" value="{{ $today }}">
                <input type="hidden" id="grEndDate" value="{{ $today }}">
            </div>
            <div class="gr-filter-action">
                <button type="button" class="gr-action gr-action-apply" id="grApply">Apply Filters</button>
            </div>
        </div>
    </section>

    <section class="gr-kpi-grid" aria-label="Analytics summary">
        <article class="gr-kpi-card">
            <div class="gr-kpi-icon neutral" aria-hidden="true">T</div>
            <div class="gr-kpi-copy"><span>Total Tanks</span><strong id="summaryTankCount">—</strong><small>Configured fuel tanks</small></div>
        </article>
        <article class="gr-kpi-card">
            <div class="gr-kpi-icon alert" aria-hidden="true">!</div>
            <div class="gr-kpi-copy"><span>Re-order Attention</span><strong id="summaryReorderCount">—</strong><small>Tanks at/below re-order level</small></div>
        </article>
        <article class="gr-kpi-card">
            <div class="gr-kpi-icon fuel" aria-hidden="true">F</div>
            <div class="gr-kpi-copy"><span>Fuel Sales</span><strong id="summaryFuelAmount">—</strong><small id="summaryFuelPct">Selected period</small></div>
        </article>
        <article class="gr-kpi-card">
            <div class="gr-kpi-icon nonfuel" aria-hidden="true">N</div>
            <div class="gr-kpi-copy"><span>Non-Fuel Sales</span><strong id="summaryNonFuelAmount">—</strong><small id="summaryNonFuelPct">Selected period</small></div>
        </article>
    </section>

    <main class="gr-main" id="stock-sales">
        <section class="gr-card gr-card-tanks">
            <header class="gr-card-head">
                <div class="gr-card-title">
                    <span class="gr-section-no">01</span>
                    <div>
                        <h2>Tank Levels vs. Re-order Level</h2>
                        <p>Storage volume, calculated current stock and the configured re-order threshold.</p>
                    </div>
                </div>
                <div class="gr-legend-note"><span class="gr-status-dot"></span> Live stock position</div>
            </header>
            <div class="gr-card-body">
                <div class="gr-chart-frame gr-chart-frame-large">
                    <canvas id="tankChart"></canvas>
                </div>
                <div class="gr-subhead">
                    <div><h3>Tank Status</h3><p>Re-order marker is shown on each tank level bar.</p></div>
                </div>
                <div class="gr-gauges" id="tankGauges"></div>
            </div>
        </section>

        <section class="gr-card">
            <header class="gr-card-head">
                <div class="gr-card-title">
                    <span class="gr-section-no">02</span>
                    <div>
                        <h2>Fuel Sales Trend</h2>
                        <p id="fuelSubtitle">Sold quantity of all fuel products by time period.</p>
                    </div>
                </div>
                <div class="gr-segmented" role="group" aria-label="Fuel chart type">
                    <button type="button" class="active" data-fuel-chart="bar">Bar Chart</button>
                    <button type="button" data-fuel-chart="line">Line Chart</button>
                </div>
            </header>
            <div class="gr-card-body">
                <div class="gr-chart-frame gr-chart-frame-large" id="fuelChartFrame"><canvas id="fuelSalesChart"></canvas></div>
                <div class="gr-empty" id="fuelEmpty" hidden>
                    <strong>No fuel sales found</strong><span>There is no fuel sale quantity for the selected filters.</span>
                </div>
            </div>
        </section>

        <section class="gr-card">
            <header class="gr-card-head">
                <div class="gr-card-title">
                    <span class="gr-section-no">03</span>
                    <div>
                        <h2>Non-Fuel Sales Breakdown</h2>
                        <p>Sub-category sales distribution and the sales-value mix against fuel sales.</p>
                    </div>
                </div>
            </header>
            <div class="gr-card-body">
                <div class="gr-sales-strip">
                    <article class="gr-sales-stat">
                        <span>Fuel Sales</span><strong id="fuelAmount">0.00</strong><small id="fuelPct">0%</small>
                    </article>
                    <article class="gr-sales-stat">
                        <span>Non-Fuel Sales</span><strong id="nonFuelAmount">0.00</strong><small id="nonFuelPct">0%</small>
                    </article>
                </div>
                <div class="gr-two-charts">
                    <article class="gr-chart-panel" id="nonFuelCategoryPanel">
                        <div class="gr-chart-panel-head"><h3>Non-Fuel by Sub-category</h3><span>Sales amount</span></div>
                        <div class="gr-chart-frame gr-chart-frame-donut"><canvas id="nonFuelChart"></canvas></div>
                    </article>
                    <article class="gr-chart-panel" id="salesMixPanel">
                        <div class="gr-chart-panel-head"><h3>Fuel vs. Non-Fuel</h3><span>Sales mix</span></div>
                        <div class="gr-chart-frame gr-chart-frame-donut"><canvas id="mixChart"></canvas></div>
                    </article>
                </div>
                <div class="gr-empty" id="nonFuelEmpty" hidden>
                    <strong>No non-fuel sales found</strong><span>There is no non-fuel sale amount for the selected filters.</span>
                </div>
            </div>
        </section>
    </main>

    <div class="gr-status-message" id="grStatusMessage" hidden role="status" aria-live="polite"></div>
</div>
</section>
@endsection

@section('model-scritps')
<script>
window.GRAPHS_CONFIG = {
    tanksUrl: @json(route('graphs.data.tanks')),
    fuelSalesUrl: @json(route('graphs.data.fuel-sales')),
    nonFuelSalesUrl: @json(route('graphs.data.non-fuel-sales'))
};
</script>
<script src="{{ asset('modules/graphs/vendor/chart.min.js') }}?v={{ $assetVersion }}" onerror="this.onerror=null;this.src='{{ route('graphs.asset',['type'=>'vendor','file'=>'chart.min.js','v'=>$assetVersion]) }}'"></script>
<script src="{{ asset('modules/graphs/js/graphs.js') }}?v={{ $assetVersion }}" onerror="this.onerror=null;this.src='{{ route('graphs.asset',['type'=>'js','file'=>'graphs.js','v'=>$assetVersion]) }}'"></script>
@endsection
