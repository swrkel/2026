@extends('layouts.app')

@section('title', 'Litigation Governance Center')

@section('content')

<style>

    .dashboard-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 22px;
        margin-bottom: 20px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        border-top: 4px solid #3c8dbc;
        min-height: 135px;
    }

    .dashboard-card h2 {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
        color: #2c3e50;
    }

    .dashboard-card p {
        margin-top: 10px;
        color: #7f8c8d;
        font-size: 14px;
        font-weight: 600;
    }

    .dashboard-icon {
        float: right;
        font-size: 40px;
        opacity: 0.10;
        margin-top: -45px;
    }

    .governance-box {
        background: #ffffff;
        border-radius: 14px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }

    .governance-title {
        font-size: 22px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 20px;
    }

    .modern-table thead {
        background: #f4f6f9;
    }

    .modern-table th {
        border: none !important;
        text-transform: uppercase;
        font-size: 12px;
        color: #7f8c8d;
    }

    .modern-table td {
        vertical-align: middle !important;
        font-size: 14px;
    }

    .progress {
        height: 10px !important;
        border-radius: 20px;
        background: #ecf0f1;
    }

    .timeline-modern {
        list-style: none;
        padding: 0;
    }

    .timeline-modern li {
        position: relative;
        padding-left: 30px;
        margin-bottom: 25px;
        border-left: 2px solid #d2d6de;
    }

    .timeline-modern li:before {
        content: '';
        width: 12px;
        height: 12px;
        background: #dd4b39;
        border-radius: 50%;
        position: absolute;
        left: -7px;
        top: 5px;
    }

    .timeline-card {
        background: #f9fafc;
        padding: 15px;
        border-radius: 10px;
    }

</style>

<section class="content-header">

    <h1>

        Enterprise Litigation Governance Center

        <small>
            Legal Recovery Intelligence • Court Governance • Litigation Operations
        </small>

    </h1>

</section>

<section class="content">

    <!-- ===================================================== -->
    <!-- KPI DASHBOARD -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#dd4b39;">

                <h2>

                    {{ $total_cases }}

                </h2>

                <p>
                    Total Legal Cases
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-balance-scale"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#f39c12;">

                <h2>

                    {{ $active_cases }}

                </h2>

                <p>
                    Active Cases
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-folder-open"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#00c0ef;">

                <h2>

                    {{ $court_cases }}

                </h2>

                <p>
                    Court Cases
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-university"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#00a65a;">

                <h2>

                    {{ $closed_cases }}

                </h2>

                <p>
                    Closed Cases
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-check-circle"></i>
                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- GOVERNANCE -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-gavel"></i>

                    Litigation Governance Intelligence

                </div>

                <div class="progress-group">

                    <span>
                        Legal Recovery Engine
                    </span>

                    <span class="pull-right">
                        Operational
                    </span>

                    <div class="progress">

                        <div class="progress-bar progress-bar-danger"
                             style="width:100%">
                        </div>

                    </div>

                </div>

                <br>

                <div class="progress-group">

                    <span>
                        Court Workflow Monitoring
                    </span>

                    <span class="pull-right">
                        Active
                    </span>

                    <div class="progress">

                        <div class="progress-bar progress-bar-warning"
                             style="width:100%">
                        </div>

                    </div>

                </div>

                <br>

                <div class="progress-group">

                    <span>
                        Settlement Governance
                    </span>

                    <span class="pull-right">
                        Enabled
                    </span>

                    <div class="progress">

                        <div class="progress-bar progress-bar-success"
                             style="width:100%">
                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- ================================================ -->
        <!-- WORKFORCE -->
        <!-- ================================================ -->

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-users"></i>

                    Legal Workforce Optimization

                </div>

                <table class="table modern-table">

                    <tbody>

                        <tr>

                            <th width="50%">
                                Legal Recovery Team
                            </th>

                            <td>
                                Active
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Litigation Monitoring
                            </th>

                            <td>
                                Operational
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Court Scheduling Engine
                            </th>

                            <td>
                                Running
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Autonomous Legal Monitoring
                            </th>

                            <td>
                                Enabled
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- LEGAL CASES -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-database"></i>

            Enterprise Legal Recovery Cases

        </div>

        <!-- ============================================= -->
        <!-- FILTERS -->
        <!-- ============================================= -->

        <div class="row"
             style="margin-bottom:15px;">

            <div class="col-md-4">

                <input type="text"
                       id="legalSearch"
                       class="form-control"
                       placeholder="Search Legal Cases">

            </div>

            <div class="col-md-3">

                <select id="stageFilter"
                        class="form-control">

                    <option value="">
                        All Legal Stages
                    </option>

                    <option value="notice">
                        Notice
                    </option>

                    <option value="settlement">
                        Settlement
                    </option>

                    <option value="court">
                        Court
                    </option>

                    <option value="litigation">
                        Litigation
                    </option>

                </select>

            </div>

            <div class="col-md-3">

                <select id="statusFilter"
                        class="form-control">

                    <option value="">
                        All Statuses
                    </option>

                    <option value="active">
                        Active
                    </option>

                    <option value="closed">
                        Closed
                    </option>

                </select>

            </div>

        </div>

        <!-- ============================================= -->
        <!-- TABLE -->
        <!-- ============================================= -->

        <div class="table-responsive">

            <table class="table modern-table table-striped"
                   id="legalTable">

                <thead>

                    <tr>

                        <th>Case No</th>
                        <th>Loan No</th>
                        <th>Customer</th>
                        <th>Legal Stage</th>
                        <th>Assigned To</th>
                        <th>Court Hearing</th>
                        <th>Claim Amount</th>
                        <th>Recovery %</th>
                        <th>Status</th>
                        <th width="180">Actions</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($legal_cases as $case)

                    <tr
                        data-stage="{{ $case->legal_stage }}"
                        data-status="{{ $case->status }}">

                        <td>

                            {{ $case->case_no }}

                        </td>

                        <td>

                            {{
                                optional($case->loan)
                                    ->loan_no
                            }}

                        </td>

                        <td>

                            {{
                                optional($case->customer)
                                    ->name
                            }}

                        </td>

                        <td>

                            <span class="label label-danger">

                                {{
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $case->legal_stage
                                        )
                                    )
                                }}

                            </span>

                        </td>

                        <td>

                            {{ $case->assigned_to ?? 'Unassigned' }}

                        </td>

                        <td>

                            {{ $case->court_hearing_date ?? '-' }}

                        </td>

                        <td>

                            {{
                                number_format(
                                    $case->claim_amount,
                                    2
                                )
                            }}

                        </td>

                        <td>

                            <span class="label label-success">

                                {{ $case->recovery_probability ?? 50 }}%

                            </span>

                        </td>

                        <td>

                            @if($case->status == 'active')

                                <span class="label label-warning">
                                    Active
                                </span>

                            @else

                                <span class="label label-success">
                                    Closed
                                </span>

                            @endif

                        </td>

                        <td>

                            <a href="/loan/loan-legal-recoveries/{{ $case->id }}/show"
                               class="btn btn-xs btn-primary">

                                <i class="fa fa-eye"></i>

                                View

                            </a>

                            @if($case->status == 'active')

                            <form method="POST"
                                  action="/loan/loan-legal-recoveries/{{ $case->id }}/close"
                                  style="display:inline;">

                                @csrf

                                <button type="submit"
                                        class="btn btn-xs btn-success">

                                    <i class="fa fa-check"></i>

                                    Close

                                </button>

                            </form>

                            @endif

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="10"
                            class="text-center">

                            No legal recovery cases found

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="text-right">

            {{ $legal_cases->links() }}

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- TIMELINE -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-history"></i>

            Court Hearing & Litigation Timeline

        </div>

        <ul class="timeline-modern">

            @foreach($legal_cases->take(10) as $case)

            <li>

                <div class="timeline-card">

                    <strong>

                        {{ $case->case_title }}

                    </strong>

                    <br><br>

                    {{ $case->notes }}

                    <hr>

                    <small class="text-muted">

                        {{ $case->created_at }}

                    </small>

                </div>

            </li>

            @endforeach

        </ul>

    </div>

</section>

@endsection

@section('javascript')

<script>

$(document).ready(function() {

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    $('#legalSearch').on('keyup', function() {

        var value = $(this).val().toLowerCase();

        $('#legalTable tbody tr').filter(function() {

            $(this).toggle(
                $(this).text().toLowerCase().indexOf(value) > -1
            );

        });

    });

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    $('#stageFilter, #statusFilter').on('change', function() {

        applyLegalFilters();

    });

    function applyLegalFilters()
    {
        var stage =
            $('#stageFilter').val();

        var status =
            $('#statusFilter').val();

        $('#legalTable tbody tr').each(function() {

            var rowStage =
                $(this).data('stage');

            var rowStatus =
                $(this).data('status');

            var stageMatch =
                (
                    stage == '' ||
                    rowStage == stage
                );

            var statusMatch =
                (
                    status == '' ||
                    rowStatus == status
                );

            if (
                stageMatch &&
                statusMatch
            ) {

                $(this).show();

            } else {

                $(this).hide();
            }

        });
    }

});

</script>

@endsection