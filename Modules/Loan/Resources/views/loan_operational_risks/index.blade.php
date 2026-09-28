@extends('layouts.app')

@section('title', 'Operational Risk Command Center')

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

        Enterprise Operational Risk Command Center

        <small>
            Fraud Intelligence • Internal Controls • Enterprise Risk Governance
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

                    {{ $total_risk_records }}

                </h2>

                <p>
                    Risk Records
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-shield"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#dd4b39;">

                <h2>

                    {{ $critical_incidents }}

                </h2>

                <p>
                    Critical Incidents
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-warning"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#f39c12;">

                <h2>

                    {{ $open_incidents }}

                </h2>

                <p>
                    Open Incidents
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-exclamation-circle"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#00a65a;">

                <h2>

                    {{ $fraud_cases }}

                </h2>

                <p>
                    Fraud Cases
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-user-secret"></i>
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

                    <i class="fa fa-balance-scale"></i>

                    Operational Governance Intelligence

                </div>

                <div class="progress-group">

                    <span>
                        Internal Controls
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
                        Fraud Monitoring
                    </span>

                    <span class="pull-right">
                        Active
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
                        Risk Escalation Governance
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
        <!-- EXECUTIVE -->
        <!-- ================================================= -->

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-line-chart"></i>

                    Executive Operational Risk Intelligence

                </div>

                <table class="table modern-table">

                    <tbody>

                        <tr>

                            <th width="50%">
                                Fraud Intelligence
                            </th>

                            <td>
                                Active
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Operational Loss Monitoring
                            </th>

                            <td>
                                Enabled
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Corrective Action Governance
                            </th>

                            <td>
                                Operational
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Executive Risk Visibility
                            </th>

                            <td>
                                Running
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- RISK TABLE -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-database"></i>

            Enterprise Operational Risk Records

        </div>

        <div class="row"
             style="margin-bottom:15px;">

            <div class="col-md-4">

                <input type="text"
                       id="riskSearch"
                       class="form-control"
                       placeholder="Search Operational Risk Records">

            </div>

            <div class="col-md-3">

                <select id="severityFilter"
                        class="form-control">

                    <option value="">
                        All Severity Levels
                    </option>

                    <option value="critical">
                        Critical
                    </option>

                    <option value="high">
                        High
                    </option>

                    <option value="medium">
                        Medium
                    </option>

                    <option value="low">
                        Low
                    </option>

                </select>

            </div>

            <div class="col-md-3">

                <select id="statusFilter"
                        class="form-control">

                    <option value="">
                        All Incident Status
                    </option>

                    <option value="open">
                        Open
                    </option>

                    <option value="resolved">
                        Resolved
                    </option>

                </select>

            </div>

        </div>

        <div class="table-responsive">

            <table class="table modern-table table-striped"
                   id="riskTable">

                <thead>

                    <tr>

                        <th>Reference No</th>
                        <th>Risk Category</th>
                        <th>Incident Title</th>
                        <th>Severity</th>
                        <th>Risk Score</th>
                        <th>Fraud Flag</th>
                        <th>Incident Status</th>
                        <th>Control Status</th>
                        <th width="180">Actions</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($risk_records as $risk)

                    <tr
                        data-severity="{{ $risk->severity_level }}"
                        data-status="{{ $risk->incident_status }}">

                        <td>

                            {{ $risk->reference_no }}

                        </td>

                        <td>

                            {{
                                ucwords(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $risk->risk_category
                                    )
                                )
                            }}

                        </td>

                        <td>

                            {{ $risk->incident_title }}

                        </td>

                        <td>

                            @if($risk->severity_level == 'critical')

                                <span class="label label-danger">
                                    Critical
                                </span>

                            @elseif($risk->severity_level == 'high')

                                <span class="label label-warning">
                                    High
                                </span>

                            @elseif($risk->severity_level == 'medium')

                                <span class="label label-primary">
                                    Medium
                                </span>

                            @else

                                <span class="label label-success">
                                    Low
                                </span>

                            @endif

                        </td>

                        <td>

                            <span class="label label-info">

                                {{ $risk->risk_score }}

                            </span>

                        </td>

                        <td>

                            @if($risk->fraud_flag)

                                <span class="label label-danger">
                                    Fraud Alert
                                </span>

                            @else

                                <span class="label label-success">
                                    Normal
                                </span>

                            @endif

                        </td>

                        <td>

                            @if($risk->incident_status == 'resolved')

                                <span class="label label-success">
                                    Resolved
                                </span>

                            @else

                                <span class="label label-danger">
                                    Open
                                </span>

                            @endif

                        </td>

                        <td>

                            @if($risk->control_status == 'effective')

                                <span class="label label-success">
                                    Effective
                                </span>

                            @else

                                <span class="label label-warning">
                                    Pending
                                </span>

                            @endif

                        </td>

                        <td>

                            <a href="/loan/loan-operational-risks/{{ $risk->id }}/show"
                               class="btn btn-xs btn-primary">

                                <i class="fa fa-eye"></i>

                                View

                            </a>

                            @if($risk->incident_status != 'resolved')

                            <form method="POST"
                                  action="/loan/loan-operational-risks/{{ $risk->id }}/resolve"
                                  style="display:inline;">

                                @csrf

                                <button type="submit"
                                        class="btn btn-xs btn-success">

                                    <i class="fa fa-check"></i>

                                    Resolve

                                </button>

                            </form>

                            @endif

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="9"
                            class="text-center">

                            No operational risk records found

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="text-right">

            {{ $risk_records->links() }}

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- TIMELINE -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-history"></i>

            Operational Risk Governance Timeline

        </div>

        <ul class="timeline-modern">

            @foreach($risk_records->take(10) as $risk)

            <li>

                <div class="timeline-card">

                    <strong>

                        {{ $risk->reference_no }}

                    </strong>

                    <br><br>

                    {{ $risk->incident_description }}

                    <hr>

                    <small class="text-muted">

                        {{ $risk->created_at }}

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

    $('#riskSearch').on('keyup', function() {

        var value = $(this).val().toLowerCase();

        $('#riskTable tbody tr').filter(function() {

            $(this).toggle(
                $(this).text().toLowerCase().indexOf(value) > -1
            );

        });

    });

    $('#severityFilter, #statusFilter').on('change', function() {

        applyRiskFilters();

    });

    function applyRiskFilters()
    {
        var severity =
            $('#severityFilter').val();

        var status =
            $('#statusFilter').val();

        $('#riskTable tbody tr').each(function() {

            var rowSeverity =
                $(this).data('severity');

            var rowStatus =
                $(this).data('status');

            var severityMatch =
                (
                    severity == '' ||
                    rowSeverity == severity
                );

            var statusMatch =
                (
                    status == '' ||
                    rowStatus == status
                );

            if (
                severityMatch &&
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