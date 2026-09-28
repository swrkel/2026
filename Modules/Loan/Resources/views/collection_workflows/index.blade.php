@extends('layouts.app')

@section('title', 'Collection Workflow Engine')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.enterprise-chart-style')
@include('layouts.partials.loan-operational-dashboard-style')

<section class="content-header">

    <h1>
        Collection Workflow Engine

        <small>
            Enterprise Recovery Workflow & Follow-up Management
        </small>
    </h1>

</section>

<section class="content">

    {{-- TOP CONTROL PANEL --}}
    <div class="loan-op-header-panel">

        <div class="row">

            <div class="col-md-8">

                <div class="loan-op-title">

                    <i class="fa fa-tasks"></i>

                    Collection Workflow Control Center

                </div>

                <div class="loan-op-subtitle">

                    Manage recovery workflows, follow-up actions,
                    escalation stages, promise-to-pay tracking and
                    collection operational governance.

                </div>

            </div>

            <div class="col-md-4 text-right">

                <a href="{{ route('loan.collection.workflows.generate') }}"
                   class="loan-op-action-btn">

                    <i class="fa fa-refresh"></i>

                    Generate Workflows

                </a>

            </div>

        </div>

    </div>

    {{-- KPI CARDS --}}
    <div class="row">

        <div class="col-md-3">

            <div class="loan-op-card loan-op-red">

                <div class="icon text-danger">

                    <i class="fa fa-warning"></i>

                </div>

                <div class="title">
                    Critical Workflows
                </div>

                <div class="value">

                    {{ number_format($workflows->where('priority_level', 'critical')->count()) }}

                </div>

                <div class="subtext">
                    Legal / urgent recovery cases
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="loan-op-card loan-op-yellow">

                <div class="icon text-warning">

                    <i class="fa fa-exclamation-circle"></i>

                </div>

                <div class="title">
                    High Priority
                </div>

                <div class="value">

                    {{ number_format($workflows->where('priority_level', 'high')->count()) }}

                </div>

                <div class="subtext">
                    Urgent collection follow-ups
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="loan-op-card loan-op-blue">

                <div class="icon text-primary">

                    <i class="fa fa-calendar"></i>

                </div>

                <div class="title">
                    Follow-up Workflows
                </div>

                <div class="value">

                    {{ number_format($workflows->where('status', 'followup')->count()) }}

                </div>

                <div class="subtext">
                    Scheduled follow-up cases
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="loan-op-card loan-op-green">

                <div class="icon text-success">

                    <i class="fa fa-check-circle"></i>

                </div>

                <div class="title">
                    Resolved Workflows
                </div>

                <div class="value">

                    {{ number_format($workflows->where('status', 'resolved')->count()) }}

                </div>

                <div class="subtext">
                    Completed recovery workflows
                </div>

            </div>

        </div>

    </div>

    {{-- CHARTS --}}
    <div class="row">

        <div class="col-md-6">

            <div class="loan-op-section">

                <div class="loan-op-section-title">

                    <i class="fa fa-pie-chart"></i>

                    Workflow Priority Distribution

                </div>

                <div class="loan-op-chart-box">

                    <canvas id="workflowPriorityChart"></canvas>

                </div>

            </div>

        </div>

        <div class="col-md-6">

            <div class="loan-op-section">

                <div class="loan-op-section-title">

                    <i class="fa fa-bar-chart"></i>

                    Workflow Status Summary

                </div>

                <div class="loan-op-chart-box">

                    <canvas id="workflowStatusChart"></canvas>

                </div>

            </div>

        </div>

    </div>

    {{-- WORKFLOW TABLE --}}
    <div class="loan-op-section">

        <div class="loan-op-section-title">

            <i class="fa fa-table"></i>

            Collection Workflow Register

        </div>

        <div class="table-responsive">

            <table class="table table-bordered table-hover enterprise-table">

                <thead>

                    <tr>

                        <th>Workflow No</th>
                        <th>Loan</th>
                        <th>Customer</th>
                        <th>Branch</th>
                        <th>Priority</th>
                        <th>Escalation</th>
                        <th>Stage</th>
                        <th>Follow-up Date</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Next Action</th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($workflows as $workflow)

                        <tr>

                            <td>
                                <strong>
                                    {{ $workflow->workflow_no }}
                                </strong>
                            </td>

                            <td>
                                {{ optional($workflow->loan)->id }}
                            </td>

                            <td>
                                {{ optional($workflow->customer)->name }}
                            </td>

                            <td>
                                {{ optional($workflow->location)->name }}
                            </td>

                            <td>

                                @if($workflow->priority_level == 'critical')

                                    <span class="label label-danger">
                                        Critical
                                    </span>

                                @elseif($workflow->priority_level == 'high')

                                    <span class="label label-warning">
                                        High
                                    </span>

                                @elseif($workflow->priority_level == 'medium')

                                    <span class="label label-info">
                                        Medium
                                    </span>

                                @else

                                    <span class="label label-success">
                                        Low
                                    </span>

                                @endif

                            </td>

                            <td>
                                {{ ucfirst(str_replace('_', ' ', $workflow->escalation_level)) }}
                            </td>

                            <td>
                                {{ $workflow->workflow_stage }}
                            </td>

                            <td>
                                {{ $workflow->followup_date }}
                            </td>

                            <td>

                                {{ optional($workflow->assignedTo)->first_name }}

                                {{ optional($workflow->assignedTo)->last_name }}

                            </td>

                            <td>

                                <strong>
                                    {{ ucfirst($workflow->status) }}
                                </strong>

                            </td>

                            <td>
                                {{ $workflow->next_action }}
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        <div class="text-center" style="margin-top:20px;">

            {{ $workflows->links() }}

        </div>

    </div>

</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

new Chart(document.getElementById('workflowPriorityChart'), {

    type: 'doughnut',

    data: {

        labels: ['Critical', 'High', 'Medium', 'Low'],

        datasets: [{

            data: [

                {{ $workflows->where('priority_level', 'critical')->count() }},
                {{ $workflows->where('priority_level', 'high')->count() }},
                {{ $workflows->where('priority_level', 'medium')->count() }},
                {{ $workflows->where('priority_level', 'low')->count() }}

            ],

            backgroundColor: [

                '#e74c3c',
                '#f39c12',
                '#3498db',
                '#27ae60'

            ]

        }]
    },

    options: {

        responsive: true,
        maintainAspectRatio: false
    }
});

new Chart(document.getElementById('workflowStatusChart'), {

    type: 'bar',

    data: {

        labels: [

            'Open',
            'Follow-up',
            'Escalated',
            'Resolved',
            'Legal'

        ],

        datasets: [{

            label: 'Workflow Count',

            data: [

                {{ $workflows->where('status', 'open')->count() }},
                {{ $workflows->where('status', 'followup')->count() }},
                {{ $workflows->where('status', 'escalated')->count() }},
                {{ $workflows->where('status', 'resolved')->count() }},
                {{ $workflows->where('status', 'legal')->count() }}

            ],

            backgroundColor: [

                '#3498db',
                '#f39c12',
                '#8e44ad',
                '#27ae60',
                '#e74c3c'

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