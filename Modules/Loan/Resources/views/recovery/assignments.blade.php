@extends('layouts.app')

@section('title', 'Enterprise Recovery Assignment Dashboard')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.loan-operational-dashboard-style')

<section class="content-header">

    <h1>
        Enterprise Recovery Assignment Dashboard

        <small>
            Multi-Branch Recovery Governance
        </small>
    </h1>

</section>

<section class="content">

    {{-- HEADER PANEL --}}
    <div class="loan-op-header-panel">

        <div class="row">

            <div class="col-md-8">

                <div class="loan-op-title">

                    <i class="fa fa-users"></i>

                    Multi-Branch Recovery Governance

                </div>

                <div class="loan-op-subtitle">

                    Assign overdue recovery accounts to officers, monitor
                    branch workload, legal severity and collection accountability.

                </div>

            </div>

            <div class="col-md-4 text-right">

                @if($isHeadOfficeUser)

                    <span class="label label-primary"
                          style="display:inline-block;padding:12px 18px;border-radius:25px;font-size:13px;">
                        HEAD OFFICE ACCESS
                    </span>

                @else

                    <span class="label label-success"
                          style="display:inline-block;padding:12px 18px;border-radius:25px;font-size:13px;">
                        BRANCH RESTRICTED ACCESS
                    </span>

                @endif

            </div>

        </div>

    </div>

    {{-- ALERTS --}}
    @if(session('success'))

        <div class="alert alert-success">
            {{ session('success') }}
        </div>

    @endif

    @if(session('error'))

        <div class="alert alert-danger">
            {{ session('error') }}
        </div>

    @endif

    @if($errors->any())

        <div class="alert alert-danger">

            <ul style="margin:0;padding-left:20px;">

                @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif

    {{-- KPI CARDS --}}
    <div class="row">

        <div class="col-md-3">

            <div class="loan-op-card loan-op-blue">

                <div class="icon text-primary">
                    <i class="fa fa-folder-open"></i>
                </div>

                <div class="title">
                    Total Overdue Accounts
                </div>

                <div class="value">
                    {{ number_format($overdueLoans->total()) }}
                </div>

                <div class="subtext">
                    Accounts awaiting assignment
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="loan-op-card loan-op-red">

                <div class="icon text-danger">
                    <i class="fa fa-gavel"></i>
                </div>

                <div class="title">
                    Critical Accounts
                </div>

                <div class="value">
                    {{
                        number_format(
                            $overdueLoans
                                ->where('dpd', '>=', 90)
                                ->count()
                        )
                    }}
                </div>

                <div class="subtext">
                    Legal escalation level
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="loan-op-card loan-op-yellow">

                <div class="icon text-warning">
                    <i class="fa fa-warning"></i>
                </div>

                <div class="title">
                    Warning Accounts
                </div>

                <div class="value">
                    {{
                        number_format(
                            $overdueLoans
                                ->where('dpd', '>=', 30)
                                ->where('dpd', '<', 90)
                                ->count()
                        )
                    }}
                </div>

                <div class="subtext">
                    Follow-up attention needed
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="loan-op-card loan-op-green">

                <div class="icon text-success">
                    <i class="fa fa-user"></i>
                </div>

                <div class="title">
                    Recovery Officers
                </div>

                <div class="value">
                    {{ number_format($recoveryOfficers->count()) }}
                </div>

                <div class="subtext">
                    Available recovery team
                </div>

            </div>

        </div>

    </div>

    {{-- BRANCH SUMMARY --}}
    <div class="loan-op-section">

        <div class="loan-op-section-title">

            <i class="fa fa-building"></i>

            Branch Recovery Summary

        </div>

        <div class="row">

            @foreach($branchAssignmentSummary as $summary)

                <div class="col-md-4">

                    <div class="portfolio-card">

                        <div class="title">
                            {{ $summary->branch_name ?? 'Unknown Branch' }}
                        </div>

                        <div class="desc">
                            Total Accounts:
                            <strong>
                                {{ $summary->total_accounts }}
                            </strong>
                        </div>

                        <div class="desc">
                            Overdue Accounts:
                            <strong>
                                {{ $summary->overdue_accounts }}
                            </strong>
                        </div>

                        <div class="desc">
                            Legal Accounts:
                            <strong>
                                {{ $summary->legal_accounts }}
                            </strong>
                        </div>

                    </div>

                </div>

            @endforeach

        </div>

    </div>

    {{-- ASSIGNMENT QUEUE --}}
    <div class="loan-op-section">

        <div class="loan-op-section-title">

            <i class="fa fa-table"></i>

            Enterprise Recovery Assignment Queue

        </div>

        <div class="table-responsive">

            <table class="table table-bordered table-hover enterprise-table">

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Branch</th>
                        <th>Customer</th>
                        <th>Application No</th>
                        <th>DPD Severity</th>
                        <th>Recovery Officers</th>
                        <th width="420">Assignment Workflow</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($overdueLoans as $loan)

                        <tr>

                            <td>
                                {{ $loan->id }}
                            </td>

                            <td>
                                <span class="label label-info">
                                    {{ $loan->branch_name ?? 'N/A' }}
                                </span>
                            </td>

                            <td>

                                <strong>
                                    {{ $loan->customer_name ?? 'N/A' }}
                                </strong>

                                <br>

                                {{ $loan->customer_mobile ?? '-' }}

                            </td>

                            <td>
                                {{ $loan->application_no }}
                            </td>

                            <td>

                                @if($loan->dpd >= 90)

                                    <span class="label label-danger">
                                        LEGAL — {{ $loan->dpd }} Days
                                    </span>

                                @elseif($loan->dpd >= 60)

                                    <span class="label label-warning">
                                        CRITICAL — {{ $loan->dpd }} Days
                                    </span>

                                @elseif($loan->dpd >= 30)

                                    <span class="label label-warning">
                                        WARNING — {{ $loan->dpd }} Days
                                    </span>

                                @else

                                    <span class="label label-primary">
                                        NORMAL — {{ $loan->dpd }} Days
                                    </span>

                                @endif

                            </td>

                            <td>

                                @foreach($recoveryOfficers as $officer)

                                    <div style="background:#f8fafc;border-radius:8px;padding:8px;margin-bottom:8px;">

                                        <strong>
                                            {{ $officer->first_name }}
                                            {{ $officer->last_name }}
                                        </strong>

                                    </div>

                                @endforeach

                            </td>

                            <td>

                                <form method="POST"
                                      action="{{ route('loan.recovery.assignments.store') }}">

                                    @csrf

                                    <input type="hidden"
                                           name="loan_application_id"
                                           value="{{ $loan->id }}">

                                    <select name="recovery_officer_id"
                                            class="form-control"
                                            required>

                                        <option value="">
                                            Select Recovery Officer
                                        </option>

                                        @foreach($recoveryOfficers as $officer)

                                            <option value="{{ $officer->id }}">

                                                {{ $officer->first_name }}
                                                {{ $officer->last_name }}

                                            </option>

                                        @endforeach

                                    </select>

                                    <textarea
                                        name="notes"
                                        class="form-control"
                                        placeholder="Assignment notes / governance instructions..."
                                        style="margin-top:10px;min-height:80px;"></textarea>

                                    <button type="submit"
                                            class="loan-op-action-btn"
                                            style="margin-top:10px;">

                                        Assign Recovery Officer

                                    </button>

                                </form>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="7"
                                class="text-center text-muted">

                                No overdue recovery accounts found.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div style="margin-top:20px;">
            {{ $overdueLoans->links() }}
        </div>

    </div>

</section>

@endsection