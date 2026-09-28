@extends('layouts.app')
@section('title', __('home.home'))

@section('css')
    <link rel="stylesheet" href="{{ asset('css/home-dashboard-modern.css?v=' . $asset_v) }}">
@endsection

@section('content')
    @php
        $canRenderDashboard = auth()->check();
        $canViewSummary = auth()->user()->can('DashboardSummaryCards');
        $canViewComparisonGraphs = auth()->user()->can('DashboardCurrentPastGraph');
        $canViewCurrentPayments = auth()->user()->can('DashboardPaymentMethods');
        $canViewPreviousPayments = auth()->user()->can('DashboardCurrentPastPayments');
        $dashboardName = session('business.name') ?: config('app.name');
    @endphp

    <div class="page-title-area home-dashboard-page-title">
        <div class="row align-items-center">
            <div class="col-sm-12">
                <div class="breadcrumbs-area clearfix">
                    <h4 class="page-title pull-left">
                        <strong>{{ $dashboardName }}</strong> Dashboard
                    </h4>
                    <ul class="breadcrumbs pull-left">
                        <li><a href="{{ action('HomeController@index') }}">Home</a></li>
                        <li><span>Dashboard</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @if ($home_dashboard && $canRenderDashboard)
        <section class="content main-content-inner no-print home-dashboard" id="home-dashboard">
            <div class="home-dashboard-filters" aria-label="Dashboard filters">
                <div class="home-dashboard-filter">
                    <label for="category_filter">Category grouping</label>
                    <select class="form-control" id="category_filter">
                        <option value="category" selected>Category wise</option>
                        <option value="sub">Sub Category wise</option>
                    </select>
                </div>

                <div class="home-dashboard-filter">
                    <label for="location_id">Business location</label>
                    <select class="form-control" id="location_id">
                        @foreach ($business_locations as $key => $bl)
                            <option value="{{ $key }}" @if ($loop->first) selected @endif>{{ $bl }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="home-dashboard-filter">
                    <label for="period_1">Reporting period</label>
                    <select class="form-control" id="period_1">
                        <option value="today" selected>Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="year">This Year</option>
                        <option value="financial-year">Financial Year</option>
                    </select>
                </div>

                <button type="button" id="product-reorder-btn" class="btn home-dashboard-reorder-btn">
                    <i class="fa fa-cubes" aria-hidden="true"></i>
                    <span>Products Reorder Level Report</span>
                </button>
            </div>

            <div class="home-dashboard-summary" aria-label="Dashboard summary">
                <article class="home-summary-card home-summary-purchases">
                    <span class="home-summary-label">Purchases</span>
                    <strong class="home-summary-value" id="purchases">{{ $canViewSummary ? '0.00' : 'Restricted' }}</strong>
                </article>
                <article class="home-summary-card home-summary-sales">
                    <span class="home-summary-label">Sales</span>
                    <strong class="home-summary-value" id="sales">{{ $canViewSummary ? '0.00' : 'Restricted' }}</strong>
                </article>
                <article class="home-summary-card home-summary-stocks">
                    <span class="home-summary-label">Stocks</span>
                    <strong class="home-summary-value" id="stocks">{{ $canViewSummary ? '0.00' : 'Restricted' }}</strong>
                </article>
                <article class="home-summary-card home-summary-expenses">
                    <span class="home-summary-label">Expenses</span>
                    <strong class="home-summary-value" id="expenses">{{ $canViewSummary ? '0.00' : 'Restricted' }}</strong>
                </article>
                <article class="home-summary-card home-summary-credit-given">
                    <span class="home-summary-label">Credit given</span>
                    <strong class="home-summary-value" id="credit_given">{{ $canViewSummary ? '0.00' : 'Restricted' }}</strong>
                </article>
                <article class="home-summary-card home-summary-credit-received">
                    <span class="home-summary-label">Credit received</span>
                    <strong class="home-summary-value" id="credit_received">{{ $canViewSummary ? '0.00' : 'Restricted' }}</strong>
                </article>
            </div>

            <div class="home-dashboard-chart-grid home-dashboard-comparison-grid">
                <article class="home-dashboard-chart-card">
                    <header class="home-dashboard-chart-header">
                        <h3 id="previous-period-chart-title">Last Day</h3>
                    </header>
                    <div class="home-dashboard-chart-body home-dashboard-bar-body">
                        <canvas id="home_previous_bar_chart"></canvas>
                        <div class="home-dashboard-chart-state" data-chart-state="previous-bar" hidden></div>
                    </div>
                </article>

                <article class="home-dashboard-chart-card">
                    <header class="home-dashboard-chart-header">
                        <h3 id="current-period-chart-title">Today</h3>
                    </header>
                    <div class="home-dashboard-chart-body home-dashboard-bar-body">
                        <canvas id="home_current_bar_chart"></canvas>
                        <div class="home-dashboard-chart-state" data-chart-state="current-bar" hidden></div>
                    </div>
                </article>
            </div>

            <div class="home-dashboard-date-divider">
                <span id="home-dashboard-date-label">{{ now()->format('Y-m-d') }}</span>
            </div>

            <div class="home-dashboard-chart-grid home-dashboard-payment-grid">
                <article class="home-dashboard-chart-card">
                    <header class="home-dashboard-chart-header">
                        <h3 id="current-payment-chart-title">Payment Methods</h3>
                    </header>
                    <div class="home-dashboard-chart-body home-dashboard-payment-body">
                        <canvas id="home_current_payment_chart"></canvas>
                        <div class="home-dashboard-chart-state" data-chart-state="current-payment" hidden></div>
                    </div>
                </article>

                <article class="home-dashboard-chart-card">
                    <header class="home-dashboard-chart-header">
                        <h3 id="previous-payment-chart-title">Payment Methods (Last Day)</h3>
                    </header>
                    <div class="home-dashboard-chart-body home-dashboard-payment-body">
                        <canvas id="home_previous_payment_chart"></canvas>
                        <div class="home-dashboard-chart-state" data-chart-state="previous-payment" hidden></div>
                    </div>
                </article>
            </div>

            <div class="home-dashboard-error alert alert-danger" id="home-dashboard-error" role="alert" hidden></div>

            @include('home.reorder-level-report')
        </section>
    @endif
@endsection

@section('model-scritps')
    <script src="{{ asset('js/vendor/chart-3.7.1.min.js?v=' . $asset_v) }}"></script>
    <script>
        window.HomeDashboardConfig = {
            dataUrl: @json(action('HomeController@index')),
            permissions: {
                summary: @json($canViewSummary),
                currentGraph: @json($canViewComparisonGraphs),
                previousGraph: @json($canViewComparisonGraphs),
                currentPayments: @json($canViewCurrentPayments),
                previousPayments: @json($canViewPreviousPayments)
            },
            labels: {
                restricted: @json(__('lang_v1.unauthorized_action')),
                loadError: 'Unable to load dashboard data. Please refresh the page and try again.'
            }
        };
    </script>
    <script src="{{ asset('js/home-dashboard-modern.js?v=' . $asset_v) }}"></script>

    @if (!empty($all_locations))
        {!! $sells_chart_1->script() !!}
        {!! $sells_chart_2->script() !!}
    @endif

    @if (!empty($customer_name_payment))
        <script>
            swal({
                title: 'Payment Received',
                text: 'Pending direct payment is Done by {{ $customer_name_payment }}. Please check and approve.',
                icon: 'success',
                buttons: true,
                dangerMode: false
            });
        </script>
    @endif
@endsection
