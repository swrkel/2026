@extends('layouts.app')

@section('title', 'Write-Off Governance Center')

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

        Enterprise Write-Off Governance Center

        <small>
            Financial Loss Governance • Recovery Closure • Provisioning Intelligence
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

                    {{ $total_write_offs }}

                </h2>

                <p>
                    Total Write-Offs
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-times-circle"></i>
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

                    {{ $approved_write_offs }}

                </h2>

                <p>
                    Approved Write-Offs
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-check-circle"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#00c0ef;">

                <h2 style="font-size:22px;">

                    {{ number_format($total_loss_amount, 2) }}

                </h2>

                <p>
                    Total Loss Amount
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-money"></i>
                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- Governance -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-shield"></i>

                    Write-Off Governance Intelligence

                </div>

                <div class="progress-group">

                    <span>
                        Write-Off Governance
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
                        Provisioning Intelligence
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
                        Recovery Closure Monitoring
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
        <!-- Audit -->
        <!-- ================================================= -->

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-gavel"></i>

                    Board & Audit Governance

                </div>

                <table class="table modern-table">

                    <tbody>

                        <tr>

                            <th width="50%">
                                Board Approval Workflow
                            </th>

                            <td>
                                Operational
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Financial Loss Monitoring
                            </th>

                            <td>
                                Active
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Legal Governance
                            </th>

                            <td>
                                Enabled
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Autonomous Audit Intelligence
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
    <!-- TABLE -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-database"></i>

            Enterprise Write-Off Records

        </div>

        <!-- ============================================= -->
        <!-- FILTERS -->
        <!-- ============================================= -->

        <div class="row"
             style="margin-bottom:15px;">

            <div class="col-md-4">

                <input type="text"
                       id="writeOffSearch"
                       class="form-control"
                       placeholder="Search Write-Off Records">

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

                <select id="closureFilter"
                        class="form-control">

                    <option value="">
                        All Closure Status
                    </option>

                    <option value="pending">
                        Pending
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
                   id="writeOffTable">

                <thead>

                    <tr>

                        <th>Write-Off No</th>
                        <th>Loan No</th>
                        <th>Customer</th>
                        <th>Write-Off Amount</th>
                        <th>Recovered Amount</th>
                        <th>Loss Amount</th>
                        <th>Provisioning</th>
                        <th>Approval</th>
                        <th>Closure</th>
                        <th width="180">Actions</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($write_offs as $write_off)

                    <tr
                        data-approval="{{ $write_off->approval_status }}"
                        data-closure="{{ $write_off->recovery_closure_status }}">

                        <td>

                            {{ $write_off->write_off_no }}

                        </td>

                        <td>

                            {{
                                optional($write_off->loan)
                                    ->loan_no
                            }}

                        </td>

                        <td>

                            {{
                                optional($write_off->customer)
                                    ->name
                            }}

                        </td>

                        <td>

                            {{
                                number_format(
                                    $write_off->write_off_amount,
                                    2
                                )
                            }}

                        </td>

                        <td>

                            {{
                                number_format(
                                    $write_off->recovered_amount,
                                    2
                                )
                            }}

                        </td>

                        <td>

                            <span class="label label-danger">

                                {{
                                    number_format(
                                        $write_off->loss_amount,
                                        2
                                    )
                                }}

                            </span>

                        </td>

                        <td>

                            <span class="label label-warning">

                                {{
                                    $write_off->provisioning_score
                                }}%

                            </span>

                        </td>

                        <td>

                            @if($write_off->approval_status == 'approved')

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

                            @if($write_off->recovery_closure_status == 'closed')

                                <span class="label label-success">
                                    Closed
                                </span>

                            @else

                                <span class="label label-danger">
                                    Pending
                                </span>

                            @endif

                        </td>

                        <td>

                            <a href="/loan/loan-write-offs/{{ $write_off->id }}/show"
                               class="btn btn-xs btn-primary">

                                <i class="fa fa-eye"></i>

                                View

                            </a>

                            @if($write_off->approval_status != 'approved')

                            <form method="POST"
                                  action="/loan/loan-write-offs/{{ $write_off->id }}/approve"
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

                            No write-off records found

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="text-right">

            {{ $write_offs->links() }}

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- Timeline -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-history"></i>

            Recovery Closure & Loss Timeline

        </div>

        <ul class="timeline-modern">

            @foreach($write_offs->take(10) as $write_off)

            <li>

                <div class="timeline-card">

                    <strong>

                        {{ $write_off->write_off_no }}

                    </strong>

                    <br><br>

                    {{ $write_off->notes }}

                    <hr>

                    <small class="text-muted">

                        {{ $write_off->created_at }}

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

    $('#writeOffSearch').on('keyup', function() {

        var value = $(this).val().toLowerCase();

        $('#writeOffTable tbody tr').filter(function() {

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

    $('#approvalFilter, #closureFilter').on('change', function() {

        applyWriteOffFilters();

    });

    function applyWriteOffFilters()
    {
        var approval =
            $('#approvalFilter').val();

        var closure =
            $('#closureFilter').val();

        $('#writeOffTable tbody tr').each(function() {

            var rowApproval =
                $(this).data('approval');

            var rowClosure =
                $(this).data('closure');

            var approvalMatch =
                (
                    approval == '' ||
                    rowApproval == approval
                );

            var closureMatch =
                (
                    closure == '' ||
                    rowClosure == closure
                );

            if (
                approvalMatch &&
                closureMatch
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