@extends('layouts.app')

@section('title', 'Enterprise Loan Dashboard')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.enterprise-chart-style')

<section class="content-header">

    <h1>
        Enterprise Loan Governance Dashboard
        <small>Executive Recovery Intelligence & Portfolio Governance</small>
    </h1>

</section>

<section class="content">

    <div class="row">

        <div class="col-md-3">
            <div class="enterprise-card enterprise-blue">
                <div class="icon text-primary">
                    <i class="fa fa-bank"></i>
                </div>

                <div class="title">Total Portfolio Exposure</div>

                <div class="value">
                    {{ number_format($total_portfolio, 2) }}
                </div>

                <div class="subtext">Consolidated loan exposure</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="enterprise-card enterprise-green">
                <div class="icon text-success">
                    <i class="fa fa-money"></i>
                </div>

                <div class="title">Recovery Collections</div>

                <div class="value">
                    {{ number_format($collections, 2) }}
                </div>

                <div class="subtext">Total collections received</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="enterprise-card enterprise-yellow">
                <div class="icon text-warning">
                    <i class="fa fa-credit-card"></i>
                </div>

                <div class="title">Active Loan Accounts</div>

                <div class="value">
                    {{ number_format($active_loans) }}
                </div>

                <div class="subtext">Currently active facilities</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="enterprise-card enterprise-red">
                <div class="icon text-danger">
                    <i class="fa fa-warning"></i>
                </div>

                <div class="title">High Risk Exposure</div>

                <div class="value">
                    {{ number_format($overdue_amount, 2) }}
                </div>

                <div class="subtext">Overdue recovery exposure</div>
            </div>
        </div>

    </div>

    <div class="row">

        <div class="col-md-8">

            <div class="enterprise-panel">

                <div class="enterprise-panel-title">
                    <i class="fa fa-line-chart"></i>
                    Portfolio Intelligence
                </div>

                <div class="row">

                    <div class="col-md-6">
                        <div class="enterprise-card enterprise-purple">
                            <div class="icon" style="color:#8e44ad;">
                                <i class="fa fa-exclamation-triangle"></i>
                            </div>

                            <div class="title">Portfolio At Risk</div>

                            <div class="value">
                                {{ number_format($par_percentage, 2) }}%
                            </div>

                            <div class="subtext">PAR ratio / delinquency pressure</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="enterprise-card enterprise-gray">
                            <div class="icon text-muted">
                                <i class="fa fa-ban"></i>
                            </div>

                            <div class="title">Written Off Accounts</div>

                            <div class="value">
                                {{ number_format($written_off_loans) }}
                            </div>

                            <div class="subtext">Written-off loan portfolio</div>
                        </div>
                    </div>

                </div>

                <table class="table table-bordered enterprise-table">

                    <thead>
                        <tr>
                            <th>KPI Metric</th>
                            <th class="text-right">Value</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>Total Portfolio</td>
                            <td class="text-right">{{ number_format($total_portfolio, 2) }}</td>
                        </tr>

                        <tr>
                            <td>Total Collections</td>
                            <td class="text-right">{{ number_format($collections, 2) }}</td>
                        </tr>

                        <tr>
                            <td>Overdue Exposure</td>
                            <td class="text-right">{{ number_format($overdue_amount, 2) }}</td>
                        </tr>

                        <tr>
                            <td>Portfolio At Risk</td>
                            <td class="text-right">{{ number_format($par_percentage, 2) }}%</td>
                        </tr>

                        <tr>
                            <td>Written Off Accounts</td>
                            <td class="text-right">{{ number_format($written_off_loans) }}</td>
                        </tr>
                    </tbody>

                </table>

            </div>

        </div>

        <div class="col-md-4">

            <div class="enterprise-panel">

                <div class="enterprise-panel-title">
                    <i class="fa fa-shield"></i>
                    Governance Risk Signals
                </div>

                <div class="progress-group">
                    <span>Portfolio Recovery Performance</span>
                    <span class="pull-right">{{ number_format($collections, 2) }}</span>

                    <div class="progress">
                        <div class="progress-bar progress-bar-success" style="width:85%"></div>
                    </div>
                </div>

                <br>

                <div class="progress-group">
                    <span>Overdue Risk Pressure</span>
                    <span class="pull-right">{{ number_format($overdue_amount, 2) }}</span>

                    <div class="progress">
                        <div class="progress-bar progress-bar-danger"
                             style="width: {{ min(100, $par_percentage) }}%">
                        </div>
                    </div>
                </div>

                <br>

                <div class="progress-group">
                    <span>Active Loan Utilization</span>
                    <span class="pull-right">{{ number_format($active_loans) }}</span>

                    <div class="progress">
                        <div class="progress-bar progress-bar-info" style="width:75%"></div>
                    </div>
                </div>

                <hr>

                <div class="enterprise-card enterprise-yellow" style="margin-bottom:0;">
                    <div class="title">Governance Intelligence</div>

                    <div class="subtext">
                        Monitor portfolio concentration risk, delinquency pressure,
                        operational recovery performance, and enterprise collection efficiency.
                    </div>
                </div>

            </div>

        </div>

    </div>
    
    <div class="row">

    <div class="col-md-6">

        <div class="enterprise-chart-panel">

            <div class="enterprise-chart-title">
                <i class="fa fa-pie-chart"></i>
                Portfolio Risk Mix
            </div>

            <div class="enterprise-chart-box">
                <canvas id="portfolioRiskChart"></canvas>
            </div>

        </div>

    </div>

    <div class="col-md-6">

        <div class="enterprise-chart-panel">

            <div class="enterprise-chart-title">
                <i class="fa fa-bar-chart"></i>
                Recovery Performance
            </div>

            <div class="enterprise-chart-box">
                <canvas id="recoveryPerformanceChart"></canvas>
            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    new Chart(document.getElementById('portfolioRiskChart'), {
        type: 'doughnut',
        data: {
            labels: ['Portfolio Exposure', 'Overdue Exposure', 'Collections'],
            datasets: [{
                data: [
                    {{ $total_portfolio ?? 0 }},
                    {{ $overdue_amount ?? 0 }},
                    {{ $collections ?? 0 }}
                ],
                backgroundColor: ['#3498db', '#e74c3c', '#27ae60']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    new Chart(document.getElementById('recoveryPerformanceChart'), {
        type: 'bar',
        data: {
            labels: ['Portfolio', 'Collections', 'Overdue'],
            datasets: [{
                label: 'Loan Recovery',
                data: [
                    {{ $total_portfolio ?? 0 }},
                    {{ $collections ?? 0 }},
                    {{ $overdue_amount ?? 0 }}
                ],
                backgroundColor: ['#3c8dbc', '#27ae60', '#e74c3c']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
</script>


</section>

@endsection