@extends('autoservice::layouts.master')
@section('title','Auto Service Dashboard')
@section('autoservice_content')

@php
    $dashboardCards = [
        [
            'title' => 'Jobs Today',
            'value' => $jobs_today,
            'subtitle' => 'No jobs created today',
            'icon' => 'fa fa-clipboard',
            'theme' => 'blue',
            'url' => route('autoservice.jobs.index'),
            'action' => 'View all',
        ],
        [
            'title' => 'Open Jobs',
            'value' => $open_jobs,
            'subtitle' => 'Jobs in progress',
            'icon' => 'fa fa-briefcase',
            'theme' => 'green',
            'url' => route('autoservice.jobs.index'),
            'action' => 'View all',
        ],
        [
            'title' => 'Vehicles',
            'value' => $vehicles,
            'subtitle' => 'Active vehicles',
            'icon' => 'fa fa-car',
            'theme' => 'cyan',
            'url' => route('autoservice.vehicles.index'),
            'action' => 'View all',
        ],
        [
            'title' => 'Due Reminders',
            'value' => $due_reminders,
            'subtitle' => 'Upcoming reminders',
            'icon' => 'fa fa-bell',
            'theme' => 'orange',
            'url' => route('autoservice.reminders.index'),
            'action' => 'View all',
        ],
    ];

    $quickActions = [
        [
            'title' => 'Appointments',
            'subtitle' => 'Manage customer appointments',
            'icon' => 'fa fa-calendar',
            'theme' => 'blue',
            'url' => route('autoservice.appointments.index'),
        ],
        [
            'title' => 'Estimates / Quotations',
            'subtitle' => 'Create and manage estimates',
            'icon' => 'fa fa-file-text',
            'theme' => 'cyan',
            'url' => route('autoservice.estimates.index'),
        ],
        [
            'title' => 'Digital Inspections',
            'subtitle' => 'Vehicle inspection reports',
            'icon' => 'fa fa-list-alt',
            'theme' => 'blue',
            'url' => route('autoservice.inspections.index'),
        ],
        [
            'title' => 'Customer Lookup',
            'subtitle' => 'Search customer information',
            'icon' => 'fa fa-user-plus',
            'theme' => 'purple',
            'url' => route('autoservice.customer_portal.lookup'),
        ],
    ];
@endphp

<div class="autoservice-dashboard-page">
    <div class="autoservice-dashboard-header">
        <div>
            <span class="autoservice-eyebrow">AUTO SERVICE</span>
            <h2>Auto Service Dashboard</h2>
            <p>Overview of your workshop operations and key performance indicators.</p>
        </div>
        <div class="autoservice-header-actions">
            <a href="{{ route('autoservice.jobs.create') }}" class="autoservice-primary-action">
                <i class="fa fa-plus"></i> New Job
            </a>
        </div>
    </div>

    <div class="row autoservice-kpi-row">
        @foreach($dashboardCards as $card)
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <div class="autoservice-kpi-card autoservice-kpi-{{ $card['theme'] }}">
                    <div class="autoservice-kpi-top">
                        <div class="autoservice-kpi-icon"><i class="{{ $card['icon'] }}"></i></div>
                        <div class="autoservice-kpi-title">{{ $card['title'] }}</div>
                    </div>
                    <div class="autoservice-kpi-value">{{ number_format((float) $card['value'], 0) }}</div>
                    <div class="autoservice-kpi-footer">
                        <span>{{ $card['subtitle'] }}</span>
                        <a href="{{ $card['url'] }}">{{ $card['action'] }} <i class="fa fa-angle-right"></i></a>
                    </div>
                    <div class="autoservice-kpi-progress"><span></span></div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="autoservice-panel autoservice-recent-jobs-panel">
        <div class="autoservice-panel-header">
            <h3><i class="fa fa-list"></i> Recent Jobs</h3>
            <a class="autoservice-btn-success" href="{{ route('autoservice.jobs.create') }}">
                <i class="fa fa-plus"></i> Add Job
            </a>
        </div>
        <div class="autoservice-panel-body table-responsive">
            <table class="table autoservice-modern-table">
                <thead>
                    <tr>
                        <th>Job No</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Vehicle</th>
                        <th>Status</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Balance</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent_jobs as $job)
                        <tr>
                            <td>{{ $job->job_no }}</td>
                            <td>{{ $job->job_date }}</td>
                            <td>{{ $job->customer_name ?? $job->customer_id ?? '-' }}</td>
                            <td>{{ $job->vehicle_no ?? $job->vehicle_id ?? '-' }}</td>
                            <td><span class="autoservice-status-pill">{{ ucfirst($job->status) }}</span></td>
                            <td class="text-right">{{ number_format((float) $job->total_amount, 2) }}</td>
                            <td class="text-right">{{ number_format((float) $job->balance_amount, 2) }}</td>
                            <td class="text-center"><a href="{{ route('autoservice.jobs.index') }}" class="autoservice-table-link">Open</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="autoservice-empty-state">
                                    <i class="fa fa-inbox"></i>
                                    <strong>No jobs found</strong>
                                    <span>Create a new job to get started</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row autoservice-quick-row">
        @foreach($quickActions as $action)
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <a class="autoservice-quick-card autoservice-quick-{{ $action['theme'] }}" href="{{ $action['url'] }}">
                    <span class="autoservice-quick-icon"><i class="{{ $action['icon'] }}"></i></span>
                    <span class="autoservice-quick-copy">
                        <strong>{{ $action['title'] }}</strong>
                        <small>{{ $action['subtitle'] }}</small>
                    </span>
                    <i class="fa fa-arrow-right autoservice-quick-arrow"></i>
                </a>
            </div>
        @endforeach
    </div>
</div>

@endsection
