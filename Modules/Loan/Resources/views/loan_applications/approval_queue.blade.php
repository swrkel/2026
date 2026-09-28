@extends('layouts.app')

@section('title', 'Loan Approval Queue')

@section('content')

<style>
    .loan-approval-queue .queue-card {
        background: #fff;
        border: 1px solid #e7edf3;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
        margin-bottom: 18px;
    }
    .loan-approval-queue .queue-card-header {
        padding: 14px 16px;
        border-bottom: 1px solid #e7edf3;
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    .loan-approval-queue .queue-card-body { padding: 16px; }
    .loan-approval-queue .summary-card {
        background: #fff;
        border: 1px solid #e7edf3;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 15px;
        min-height: 88px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
    }
    .loan-approval-queue .summary-label {
        color: #607d8b;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .3px;
        margin-bottom: 8px;
    }
    .loan-approval-queue .summary-value {
        font-size: 26px;
        font-weight: 700;
        color: #263238;
        line-height: 1;
    }
    .loan-approval-queue .table-responsive,
    .loan-approval-queue .queue-card-body {
        overflow: visible !important;
    }
    .loan-approval-queue .dropdown-menu { z-index: 99999 !important; }
    .loan-approval-queue table td,
    .loan-approval-queue table th {
        vertical-align: middle !important;
        white-space: nowrap;
    }
    .loan-approval-queue .amount-cell { text-align: right; font-weight: 600; }
</style>

<section class="content-header">
    <h1>
        Loan Approval Queue
        <small>Consistent workflow for applications by business and location</small>
    </h1>
</section>

<section class="content loan-approval-queue">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="summary-card">
                <div class="summary-label">Draft</div>
                <div class="summary-value">{{ number_format($summary['draft'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="summary-card">
                <div class="summary-label">Submitted</div>
                <div class="summary-value">{{ number_format($summary['submitted'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="summary-card">
                <div class="summary-label">Under Review</div>
                <div class="summary-value">{{ number_format($summary['under_review'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="summary-card">
                <div class="summary-label">Approved</div>
                <div class="summary-value">{{ number_format($summary['approved'] ?? 0) }}</div>
            </div>
        </div>
    </div>

    <div class="queue-card">
        <div class="queue-card-header">
            <h3 style="margin:0;font-size:17px;font-weight:600;">
                <i class="fa fa-check-square-o"></i> Approval Worklist
            </h3>
            <a href="{{ url('/loan/loan-applications') }}" class="btn btn-default btn-sm">
                <i class="fa fa-list"></i> All Applications
            </a>
        </div>

        <div class="queue-card-body">
            <form method="GET" action="{{ url('/loan/approval-queue') }}" class="row" style="margin-bottom:15px;">
                <div class="col-md-3">
                    <select name="status" class="form-control input-sm">
                        <option value="">All Workflow Statuses</option>
                        @foreach($workflowStatuses as $key => $label)
                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="location_id" class="form-control input-sm">
                        <option value="">All Locations</option>
                        @foreach($locations as $id => $name)
                            <option value="{{ $id }}" {{ request('location_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa fa-search"></i> Filter
                    </button>
                    <a href="{{ url('/loan/approval-queue') }}" class="btn btn-default btn-sm">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" style="width:100%;">
                    <thead>
                        <tr>
                            <th style="width:95px;">Actions</th>
                            <th>Application No</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Date</th>
                            <th class="text-right">Amount</th>
                            <th>Risk</th>
                            <th>Workflow</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $application)
                            @php
                                $status = $application->status ?? 'draft';
                                $customerName = optional($application->customer)->display_name
                                    ?? optional($application->customer)->name
                                    ?? '-';
                                $productName = optional($application->loanProduct)->name ?? '-';
                            @endphp
                            <tr>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown">
                                            Actions <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu" role="menu">
                                            <li>
                                                <a href="{{ url('/loan/loan-applications/' . $application->id . '/show') }}">
                                                    <i class="fa fa-eye"></i> View
                                                </a>
                                            </li>
                                            @if($status === 'draft')
                                                <li>
                                                    <form method="POST" action="{{ url('/loan/approval-queue/' . $application->id . '/submit') }}" style="margin:0;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-link btn-block text-left" style="padding:3px 20px;color:#333;text-align:left;">
                                                            <i class="fa fa-send text-primary"></i> Submit
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                            @if(in_array($status, ['submitted', 'draft']))
                                                <li>
                                                    <form method="POST" action="{{ url('/loan/approval-queue/' . $application->id . '/review') }}" style="margin:0;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-link btn-block text-left" style="padding:3px 20px;color:#333;text-align:left;">
                                                            <i class="fa fa-search text-info"></i> Mark Under Review
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                            @if(in_array($status, ['draft', 'submitted', 'under_review']))
                                                <li>
                                                    <form method="POST" action="{{ url('/loan/approval-queue/' . $application->id . '/approve') }}" style="margin:0;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-link btn-block text-left" style="padding:3px 20px;color:#333;text-align:left;">
                                                            <i class="fa fa-check text-success"></i> Approve
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="POST" action="{{ url('/loan/approval-queue/' . $application->id . '/reject') }}" style="margin:0;" onsubmit="return confirm('Reject this loan application?');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-link btn-block text-left" style="padding:3px 20px;color:#333;text-align:left;">
                                                            <i class="fa fa-times text-danger"></i> Reject
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                                <td>{{ $application->application_no ?? $application->id }}</td>
                                <td>{{ $customerName }}</td>
                                <td>{{ $productName }}</td>
                                <td>{{ $application->application_date ?? optional($application->created_at)->format('Y-m-d') }}</td>
                                <td class="amount-cell">{{ number_format((float) $application->principal_amount, 2) }}</td>
                                <td>{{ ucfirst($application->risk_level ?? 'low') }}</td>
                                <td>{{ str_replace('_', ' ', ucfirst($application->workflow_stage ?? '-')) }}</td>
                                <td>{{ $workflowStatuses[$status] ?? ucfirst($status) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No applications found in the approval queue.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="text-center">
                {{ $applications->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</section>
@endsection
