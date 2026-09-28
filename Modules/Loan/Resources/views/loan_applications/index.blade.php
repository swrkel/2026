@extends('layouts.app')

@section('title', 'Loan Applications')

@section('content')

<style>
    .loan-application-page .la-card {
        background: #fff;
        border: 1px solid #e6edf5;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
        margin-bottom: 18px;
    }
    .loan-application-page .la-card-header {
        padding: 14px 16px;
        border-bottom: 1px solid #e6edf5;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }
    .loan-application-page .la-card-title {
        margin: 0;
        font-size: 17px;
        font-weight: 600;
        color: #263238;
    }
    .loan-application-page .la-card-body {
        padding: 16px;
    }
    .loan-application-page .la-summary-card {
        background: #fff;
        border: 1px solid #e6edf5;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 15px;
        min-height: 92px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
    }
    .loan-application-page .la-summary-label {
        color: #607d8b;
        font-size: 12px;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: .3px;
        margin-bottom: 8px;
    }
    .loan-application-page .la-summary-value {
        font-size: 26px;
        font-weight: 700;
        color: #263238;
        line-height: 1;
    }
    .loan-application-page .la-table-wrapper,
    .loan-application-page .table-responsive,
    .loan-application-page .la-card-body {
        overflow: visible !important;
    }
    .loan-application-page table td,
    .loan-application-page table th {
        vertical-align: middle !important;
        white-space: nowrap;
    }
    .loan-application-page .dropdown-menu {
        z-index: 99999 !important;
    }
    .loan-application-page .amount-cell {
        text-align: right;
        font-weight: 600;
    }
    .loan-application-page .status-label {
        display: inline-block;
        min-width: 78px;
    }
    @media (max-width: 767px) {
        .loan-application-page .la-card-header {
            display: block;
        }
        .loan-application-page .la-card-header .btn {
            margin-top: 8px;
        }
    }
</style>

<section class="content-header">
    <h1>
        Loan Applications
        <small>Multi-branch origination, approval and disbursement workflow</small>
    </h1>
</section>

<section class="content loan-application-page">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="la-summary-card">
                <div class="la-summary-label">Total Applications</div>
                <div class="la-summary-value">{{ number_format($total_applications) }}</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="la-summary-card">
                <div class="la-summary-label">Approved</div>
                <div class="la-summary-value">{{ number_format($approved_applications) }}</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="la-summary-card">
                <div class="la-summary-label">Rejected</div>
                <div class="la-summary-value">{{ number_format($rejected_applications) }}</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="la-summary-card">
                <div class="la-summary-label">Disbursed</div>
                <div class="la-summary-value">{{ number_format($disbursed_applications) }}</div>
            </div>
        </div>
    </div>

    <div class="la-card">
        <div class="la-card-header">
            <h3 class="la-card-title">
                <i class="fa fa-list"></i> Loan Application List
            </h3>
            <a href="{{ url('/loan/loan-applications/create') }}" class="btn btn-primary btn-sm">
                <i class="fa fa-plus"></i> Add Application
            </a>
        </div>

        <div class="la-card-body">
            <div class="row" style="margin-bottom: 14px;">
                <div class="col-md-4">
                    <input type="text" id="applicationSearch" class="form-control input-sm" placeholder="Search application, customer, product or status">
                </div>
                <div class="col-md-3">
                    <select id="statusFilter" class="form-control input-sm">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="disbursed">Disbursed</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive la-table-wrapper">
                <table class="table table-bordered table-striped" id="loanApplicationTable" style="width:100%;">
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
                                $risk = $application->risk_level ?? 'low';
                                $customerName = optional($application->customer)->display_name
                                    ?? optional($application->customer)->name
                                    ?? '-';
                                $productName = optional($application->loanProduct)->name ?? '-';
                            @endphp
                            <tr data-status="{{ $status }}">
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                            Actions <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu" role="menu">
                                            <li>
                                                <a href="{{ url('/loan/loan-applications/' . $application->id . '/show') }}">
                                                    <i class="fa fa-eye"></i> View
                                                </a>
                                            </li>
                                            @if(in_array($status, ['draft', 'rejected']))
                                                <li>
                                                    <a href="{{ url('/loan/loan-applications/' . $application->id . '/edit') }}">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </a>
                                                </li>
                                            @endif
                                            @if($status == 'draft')
                                                <li class="divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ url('/loan/loan-applications/' . $application->id . '/approve') }}" style="margin:0;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-link btn-block text-left" style="padding:3px 20px; color:#333; text-align:left;">
                                                            <i class="fa fa-check text-success"></i> Approve
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="POST" action="{{ url('/loan/loan-applications/' . $application->id . '/reject') }}" style="margin:0;" onsubmit="return confirm('Reject this loan application?');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-link btn-block text-left" style="padding:3px 20px; color:#333; text-align:left;">
                                                            <i class="fa fa-times text-danger"></i> Reject
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                            @if($status == 'approved')
                                                <li class="divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ url('/loan/loan-applications/' . $application->id . '/disburse') }}" style="margin:0;" onsubmit="return confirm('Disburse this approved loan?');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-link btn-block text-left" style="padding:3px 20px; color:#333; text-align:left;">
                                                            <i class="fa fa-money text-warning"></i> Disburse
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                                <td><strong>{{ $application->application_no ?? '-' }}</strong></td>
                                <td>{{ $customerName }}</td>
                                <td>{{ $productName }}</td>
                                <td>{{ $application->application_date ?? '-' }}</td>
                                <td class="amount-cell">{{ number_format((float) ($application->principal_amount ?? 0), 2) }}</td>
                                <td>
                                    @if($risk == 'critical')
                                        <span class="label label-danger">Critical</span>
                                    @elseif($risk == 'high')
                                        <span class="label label-warning">High</span>
                                    @elseif($risk == 'medium')
                                        <span class="label label-primary">Medium</span>
                                    @else
                                        <span class="label label-success">Low</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="label label-info">
                                        {{ ucwords(str_replace('_', ' ', $application->workflow_stage ?? 'Application Submitted')) }}
                                    </span>
                                </td>
                                <td>
                                    @if($status == 'draft')
                                        <span class="label label-warning status-label">Draft</span>
                                    @elseif($status == 'approved')
                                        <span class="label label-success status-label">Approved</span>
                                    @elseif($status == 'rejected')
                                        <span class="label label-danger status-label">Rejected</span>
                                    @elseif($status == 'disbursed')
                                        <span class="label label-primary status-label">Disbursed</span>
                                    @else
                                        <span class="label label-default status-label">{{ ucfirst($status) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No applications found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="text-right">
                {{ $applications->links() }}
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="la-card">
                <div class="la-card-header">
                    <h3 class="la-card-title"><i class="fa fa-shield"></i> Underwriting Governance</h3>
                </div>
                <div class="la-card-body">
                    <table class="table table-bordered">
                        <tr><th>Approved Applications</th><td>{{ number_format($approved_applications) }}</td></tr>
                        <tr><th>Rejected Applications</th><td>{{ number_format($rejected_applications) }}</td></tr>
                        <tr><th>Disbursed Applications</th><td>{{ number_format($disbursed_applications) }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="la-card">
                <div class="la-card-header">
                    <h3 class="la-card-title"><i class="fa fa-building"></i> Branch Summary</h3>
                </div>
                <div class="la-card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Approved</th>
                                <th class="text-right">Disbursed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($branchSummary as $summary)
                                <tr>
                                    <td>{{ $summary->branch_name ?? 'Unassigned' }}</td>
                                    <td class="text-right">{{ number_format($summary->total_accounts ?? 0) }}</td>
                                    <td class="text-right">{{ number_format($summary->approved_accounts ?? 0) }}</td>
                                    <td class="text-right">{{ number_format($summary->disbursed_accounts ?? 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">No branch summary available</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</section>

@endsection

@section('javascript')
<script>
$(document).ready(function() {
    $('#applicationSearch').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#loanApplicationTable tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    $('#statusFilter').on('change', function() {
        var status = $(this).val();
        $('#loanApplicationTable tbody tr').each(function() {
            if (status === '' || $(this).data('status') === status) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
</script>
@endsection
