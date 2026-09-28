@extends('layouts.app')

@section('title', 'Enterprise Notification Center')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.enterprise-chart-style')

<section class="content-header">

    <h1>
        Enterprise Notification Center
        <small>Enterprise Alerts, Governance Notifications & Operational Intelligence</small>
    </h1>

</section>

<section class="content">

    <div class="enterprise-panel">

        <div class="row">

            <div class="col-md-8">

                <div class="enterprise-panel-title">
                    <i class="fa fa-bell"></i>
                    Enterprise Alert & Communication Hub
                </div>

                <p style="margin-top:-10px; color:#7f8c8d;">
                    Centralized enterprise notification engine for governance alerts,
                    recovery reminders, treasury warnings, operational escalations,
                    and workflow intelligence.
                </p>

            </div>

            <div class="col-md-4 text-right">

                <br>

                <a href="{{ route('finance.enterprise_notifications.generate') }}"
                   class="btn btn-danger">

                    <i class="fa fa-refresh"></i>
                    Generate Notifications

                </a>

            </div>

        </div>

    </div>

    <div class="row">

        <div class="col-md-3">

            <div class="enterprise-card enterprise-red">

                <div class="icon text-danger">
                    <i class="fa fa-warning"></i>
                </div>

                <div class="title">
                    Critical Alerts
                </div>

                <div class="value">
                    {{ number_format($notifications->where('severity', 'critical')->count()) }}
                </div>

                <div class="subtext">
                    Severe operational notifications
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-yellow">

                <div class="icon text-warning">
                    <i class="fa fa-exclamation-circle"></i>
                </div>

                <div class="title">
                    Warning Alerts
                </div>

                <div class="value">
                    {{ number_format($notifications->where('severity', 'warning')->count()) }}
                </div>

                <div class="subtext">
                    Governance & workflow warnings
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-blue">

                <div class="icon text-primary">
                    <i class="fa fa-envelope"></i>
                </div>

                <div class="title">
                    Open Notifications
                </div>

                <div class="value">
                    {{ number_format($notifications->where('status', 'open')->count()) }}
                </div>

                <div class="subtext">
                    Pending enterprise notifications
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-green">

                <div class="icon text-success">
                    <i class="fa fa-check-circle"></i>
                </div>

                <div class="title">
                    Resolved Notifications
                </div>

                <div class="value">
                    {{ number_format($notifications->where('status', 'resolved')->count()) }}
                </div>

                <div class="subtext">
                    Closed operational alerts
                </div>

            </div>

        </div>

    </div>

    <div class="row">

        <div class="col-md-6">

            <div class="enterprise-chart-panel">

                <div class="enterprise-chart-title">
                    <i class="fa fa-pie-chart"></i>
                    Notification Severity Distribution
                </div>

                <div class="enterprise-chart-box">
                    <canvas id="severityChart"></canvas>
                </div>

            </div>

        </div>

        <div class="col-md-6">

            <div class="enterprise-chart-panel">

                <div class="enterprise-chart-title">
                    <i class="fa fa-bar-chart"></i>
                    Module Notification Exposure
                </div>

                <div class="enterprise-chart-box">
                    <canvas id="moduleChart"></canvas>
                </div>

            </div>

        </div>

    </div>

    <div class="enterprise-panel">

        <div class="enterprise-panel-title">
            <i class="fa fa-table"></i>
            Enterprise Notification Register
        </div>

        <div class="table-responsive">

            <table class="table table-bordered table-hover enterprise-table">

                <thead>

                    <tr>

                        <th>Notification No</th>
                        <th>Module</th>
                        <th>Severity</th>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Branch</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Date</th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($notifications as $notification)

                        <tr>

                            <td>
                                <strong>
                                    {{ $notification->notification_no }}
                                </strong>
                            </td>

                            <td>
                                {{ $notification->module }}
                            </td>

                            <td>

                                @if($notification->severity == 'critical')

                                    <span class="label label-danger">
                                        Critical
                                    </span>

                                @elseif($notification->severity == 'warning')

                                    <span class="label label-warning">
                                        Warning
                                    </span>

                                @else

                                    <span class="label label-info">
                                        Info
                                    </span>

                                @endif

                            </td>

                            <td>
                                {{ $notification->title }}
                            </td>

                            <td>
                                {{ $notification->message }}
                            </td>

                            <td>
                                {{ optional($notification->location)->name }}
                            </td>

                            <td>

                                {{ optional($notification->assignedTo)->first_name }}
                                {{ optional($notification->assignedTo)->last_name }}

                            </td>

                            <td>

                                <strong>
                                    {{ ucfirst($notification->status) }}
                                </strong>

                            </td>

                            <td>

                                {{ optional($notification->created_at)->format('Y-m-d') }}

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        <div class="text-center" style="margin-top:20px;">
            {{ $notifications->links() }}
        </div>

    </div>

</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

    new Chart(document.getElementById('severityChart'), {

        type: 'doughnut',

        data: {

            labels: [

                'Critical',
                'Warning',
                'Info'

            ],

            datasets: [{

                data: [

                    {{ $notifications->where('severity', 'critical')->count() }},
                    {{ $notifications->where('severity', 'warning')->count() }},
                    {{ $notifications->where('severity', 'info')->count() }}

                ],

                backgroundColor: [

                    '#e74c3c',
                    '#f39c12',
                    '#3498db'

                ]

            }]

        },

        options: {

            responsive: true,
            maintainAspectRatio: false

        }

    });

    new Chart(document.getElementById('moduleChart'), {

        type: 'bar',

        data: {

            labels: [

                'Finance',
                'Loan',
                'Treasury'

            ],

            datasets: [{

                label: 'Notification Count',

                data: [

                    {{ $notifications->where('module', 'Finance')->count() }},
                    {{ $notifications->where('module', 'Loan')->count() }},
                    {{ $notifications->where('module', 'Treasury')->count() }}

                ],

                backgroundColor: [

                    '#3498db',
                    '#27ae60',
                    '#8e44ad'

                ]

            }]

        },

        options: {

            responsive: true,
            maintainAspectRatio: false

        }

    });

</script>

@endsection