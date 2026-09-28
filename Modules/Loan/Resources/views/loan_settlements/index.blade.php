@extends('layouts.app')

@section('title', 'Settlement Governance Center')

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
        background: #3c8dbc;
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

        Enterprise Settlement Governance Center

        <small>
            Settlement Intelligence • Recovery Optimization • Restructuring Governance
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
                 style="border-top-color:#00c0ef;">

                <h2>

                    {{ $total_settlements }}

                </h2>

                <p>
                    Total Settlements
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-handshake-o"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#f39c12;">

                <h2>

                    {{ $pending_approvals }}

                </h2>

                <p>
                    Pending Approvals
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-clock-o"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#00a65a;">

                <h2>

                    {{ $approved_settlements }}

                </h2>

                <p>
                    Approved Settlements
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-check-circle"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#dd4b39;">

                <h2>

                    {{ $restructured_loans }}

                </h2>

                <p>
                    Restructured Loans
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-refresh"></i>
                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- Governance & Risk -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-shield"></i>

                    Settlement Governance Intelligence

                </div>

                <div class="progress-group">

                    <span>
                        Settlement Governance
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
                        Committee Approval Workflow
                    </span>

                    <span class="pull-right">
                        Operational
                    </span>

                    <div class="progress">

                        <div class="progress-bar progress-bar-info"
                             style="width:100%">
                        </div>

                    </div>

                </div>

                <br>

                <div class="progress-group">

                    <span>
                        Recovery Intelligence Engine
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

        <!-- ================================================= -->
        <!-- Risk -->
        <!-- ================================================= -->

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-warning"></i>

                    Settlement Risk Monitoring

                </div>

                <table class="table modern-table">

                    <tbody>

                        <tr>

                            <th width="50%">
                                Settlement Risk Engine
                            </th>

                            <td>
                                Running
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Restructuring Intelligence
                            </th>

                            <td>
                                Operational
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Legal Clearance Workflow
                            </th>

                            <td>
                                Enabled
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Autonomous Governance
                            </th>

                            <td>
                                Active
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- Settlement Table -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-database"></i>

            Enterprise Settlement & Restructuring Records

        </div>

        <!-- ============================================= -->
        <!-- Filters -->
        <!-- ============================================= -->

        <div class="row"
             style="margin-bottom:15px;">

            <div class="col-md-4">

                <input type="text"
                       id="settlementSearch"
                       class="form-control"
                       placeholder="Search Settlements">

            </div>

            <div class="col-md-3">

                <select id="approvalFilter"
                        class="form-control">

                    <option value="">
                        All Approval Status
                    </option>

                    <option value="pending">
                        Pending
                    </option>

                    <option value="approved">
                        Approved
                    </option>

                </select>

            </div>

            <div class="col-md-3">

                <select id="typeFilter"
                        class="form-control">

                    <option value="">
                        All Settlement Types
                    </option>

                    <option value="settlement">
                        Settlement
                    </option>

                    <option value="restructure">
                        Restructure
                    </option>

                    <option value="discount">
                        Discount
                    </option>

                </select>

            </div>

        </div>

        <!-- ============================================= -->
        <!-- Table -->
        <!-- ============================================= -->

        <div class="table-responsive">

            <table class="table modern-table table-striped"
                   id="settlementTable">

                <thead>

                    <tr>

                        <th>Settlement No</th>
                        <th>Loan No</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Proposed Amount</th>
                        <th>Recovery Score</th>
                        <th>Risk Level</th>
                        <th>Approval</th>
                        <th>Due Date</th>
                        <th width="180">Actions</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($settlements as $settlement)

                    <tr
                        data-approval="{{ $settlement->approval_status }}"
                        data-type="{{ $settlement->settlement_type }}">

                        <td>

                            {{ $settlement->settlement_no }}

                        </td>

                        <td>

                            {{
                                optional($settlement->loan)
                                    ->loan_no
                            }}

                        </td>

                        <td>

                            {{
                                optional($settlement->customer)
                                    ->name
                            }}

                        </td>

                        <td>

                            <span class="label label-primary">

                                {{
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $settlement->settlement_type
                                        )
                                    )
                                }}

                            </span>

                        </td>

                        <td>

                            {{
                                number_format(
                                    $settlement->proposed_amount,
                                    2
                                )
                            }}

                        </td>

                        <td>

                            <span class="label label-success">

                                {{
                                    $settlement->recovery_score
                                }}%

                            </span>

                        </td>

                        <td>

                            @if(($settlement->settlement_risk_level ?? 'medium') == 'high')

                                <span class="label label-danger">
                                    High
                                </span>

                            @elseif(($settlement->settlement_risk_level ?? 'medium') == 'medium')

                                <span class="label label-warning">
                                    Medium
                                </span>

                            @else

                                <span class="label label-success">
                                    Low
                                </span>

                            @endif

                        </td>

                        <td>

                            @if($settlement->approval_status == 'approved')

                                <span class="label label-success">
                                    Approved
                                </span>

                            @else

                                <span class="label label-warning">
                                    Pending
                                </span>

                            @endif

                        </td>

                        <td>

                            {{
                                $settlement->settlement_due_date
                                ?? '-'
                            }}

                        </td>

                        <td>

                            <a href="/loan/loan-settlements/{{ $settlement->id }}/show"
                               class="btn btn-xs btn-primary">

                                <i class="fa fa-eye"></i>

                                View

                            </a>

                            @if($settlement->approval_status != 'approved')

                            <form method="POST"
                                  action="/loan/loan-settlements/{{ $settlement->id }}/approve"
                                  style="display:inline;">

                                @csrf

                                <button type="submit"
                                        class="btn btn-xs btn-success">

                                    <i class="fa fa-check"></i>

                                    Approve

                                </button>

                            </form>

                            @endif

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="10"
                            class="text-center">

                            No settlement records found

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="text-right">

            {{ $settlements->links() }}

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- Timeline -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-history"></i>

            Settlement & Restructuring Timeline

        </div>

        <ul class="timeline-modern">

            @foreach($settlements->take(10) as $settlement)

            <li>

                <div class="timeline-card">

                    <strong>

                        {{ $settlement->settlement_no }}

                    </strong>

                    <br><br>

                    {{ $settlement->notes }}

                    <hr>

                    <small class="text-muted">

                        {{ $settlement->created_at }}

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

    $('#settlementSearch').on('keyup', function() {

        var value = $(this).val().toLowerCase();

        $('#settlementTable tbody tr').filter(function() {

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

    $('#approvalFilter, #typeFilter').on('change', function() {

        applySettlementFilters();

    });

    function applySettlementFilters()
    {
        var approval =
            $('#approvalFilter').val();

        var type =
            $('#typeFilter').val();

        $('#settlementTable tbody tr').each(function() {

            var rowApproval =
                $(this).data('approval');

            var rowType =
                $(this).data('type');

            var approvalMatch =
                (
                    approval == '' ||
                    rowApproval == approval
                );

            var typeMatch =
                (
                    type == '' ||
                    rowType == type
                );

            if (
                approvalMatch &&
                typeMatch
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