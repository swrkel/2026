@extends('layouts.app')

@section('title', 'Compliance Governance Center')

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
        background: #00a65a;
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

        Enterprise Compliance Governance Center

        <small>
            Regulatory Intelligence • Audit Governance • Institutional Compliance Oversight
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

                    {{ $total_compliance_records }}

                </h2>

                <p>
                    Compliance Records
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

                    {{ $open_breaches }}

                </h2>

                <p>
                    Open Breaches
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

                    {{ $pending_audits }}

                </h2>

                <p>
                    Pending Audits
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-search"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#00a65a;">

                <h2>

                    {{ $resolved_issues }}

                </h2>

                <p>
                    Resolved Issues
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

                    <i class="fa fa-balance-scale"></i>

                    Regulatory Governance Intelligence

                </div>

                <div class="progress-group">

                    <span>
                        Regulatory Governance
                    </span>

                    <span class="pull-right">
                        Active
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
                        Audit Governance
                    </span>

                    <span class="pull-right">
                        Operational
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
                        Compliance Monitoring
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
        <!-- EXECUTIVE -->
        <!-- ================================================ -->

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-line-chart"></i>

                    Executive Compliance Intelligence

                </div>

                <table class="table modern-table">

                    <tbody>

                        <tr>

                            <th width="50%">
                                Breach Governance
                            </th>

                            <td>
                                Active
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Corrective Action Monitoring
                            </th>

                            <td>
                                Operational
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Certification Governance
                            </th>

                            <td>
                                Enabled
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Executive Audit Visibility
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
    <!-- COMPLIANCE TABLE -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-database"></i>

            Enterprise Compliance Governance Records

        </div>

        <div class="row"
             style="margin-bottom:15px;">

            <div class="col-md-4">

                <input type="text"
                       id="complianceSearch"
                       class="form-control"
                       placeholder="Search Compliance Records">

            </div>

            <div class="col-md-3">

                <select id="statusFilter"
                        class="form-control">

                    <option value="">
                        All Compliance Status
                    </option>

                    <option value="compliant">
                        Compliant
                    </option>

                    <option value="warning">
                        Warning
                    </option>

                    <option value="breach">
                        Breach
                    </option>

                </select>

            </div>

            <div class="col-md-3">

                <select id="auditFilter"
                        class="form-control">

                    <option value="">
                        All Audit Status
                    </option>

                    <option value="pending">
                        Pending
                    </option>

                    <option value="completed">
                        Completed
                    </option>

                </select>

            </div>

        </div>

        <div class="table-responsive">

            <table class="table modern-table table-striped"
                   id="complianceTable">

                <thead>

                    <tr>

                        <th>Reference No</th>
                        <th>Compliance Type</th>
                        <th>Regulatory Body</th>
                        <th>Status</th>
                        <th>Risk Level</th>
                        <th>Audit Status</th>
                        <th>Certification</th>
                        <th>Resolution</th>
                        <th width="180">Actions</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($compliance_records as $compliance)

                    <tr
                        data-status="{{ $compliance->compliance_status }}"
                        data-audit="{{ $compliance->audit_status }}">

                        <td>

                            {{ $compliance->reference_no }}

                        </td>

                        <td>

                            {{
                                ucwords(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $compliance->compliance_type
                                    )
                                )
                            }}

                        </td>

                        <td>

                            {{ $compliance->regulatory_body }}

                        </td>

                        <td>

                            @if($compliance->compliance_status == 'breach')

                                <span class="label label-danger">
                                    Breach
                                </span>

                            @elseif($compliance->compliance_status == 'warning')

                                <span class="label label-warning">
                                    Warning
                                </span>

                            @else

                                <span class="label label-success">
                                    Compliant
                                </span>

                            @endif

                        </td>

                        <td>

                            @if($compliance->risk_level == 'high')

                                <span class="label label-danger">
                                    High
                                </span>

                            @elseif($compliance->risk_level == 'medium')

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

                            @if($compliance->audit_status == 'completed')

                                <span class="label label-success">
                                    Completed
                                </span>

                            @else

                                <span class="label label-warning">
                                    Pending
                                </span>

                            @endif

                        </td>

                        <td>

                            <span class="label label-primary">

                                {{
                                    ucfirst(
                                        $compliance->certification_status
                                    )
                                }}

                            </span>

                        </td>

                        <td>

                            @if($compliance->issue_resolution_status == 'resolved')

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

                            <a href="/loan/loan-compliance/{{ $compliance->id }}/show"
                               class="btn btn-xs btn-primary">

                                <i class="fa fa-eye"></i>

                                View

                            </a>

                            @if($compliance->issue_resolution_status != 'resolved')

                            <form method="POST"
                                  action="/loan/loan-compliance/{{ $compliance->id }}/resolve"
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

                            No compliance governance records found

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="text-right">

            {{ $compliance_records->links() }}

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- TIMELINE -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-history"></i>

            Compliance Governance Timeline

        </div>

        <ul class="timeline-modern">

            @foreach($compliance_records->take(10) as $compliance)

            <li>

                <div class="timeline-card">

                    <strong>

                        {{ $compliance->reference_no }}

                    </strong>

                    <br><br>

                    {{ $compliance->notes }}

                    <hr>

                    <small class="text-muted">

                        {{ $compliance->created_at }}

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

    $('#complianceSearch').on('keyup', function() {

        var value = $(this).val().toLowerCase();

        $('#complianceTable tbody tr').filter(function() {

            $(this).toggle(
                $(this).text().toLowerCase().indexOf(value) > -1
            );

        });

    });

    $('#statusFilter, #auditFilter').on('change', function() {

        applyComplianceFilters();

    });

    function applyComplianceFilters()
    {
        var status =
            $('#statusFilter').val();

        var audit =
            $('#auditFilter').val();

        $('#complianceTable tbody tr').each(function() {

            var rowStatus =
                $(this).data('status');

            var rowAudit =
                $(this).data('audit');

            var statusMatch =
                (
                    status == '' ||
                    rowStatus == status
                );

            var auditMatch =
                (
                    audit == '' ||
                    rowAudit == audit
                );

            if (
                statusMatch &&
                auditMatch
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