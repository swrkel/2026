@extends('layouts.app')

@section('title', 'Treasury Governance Center')

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
        background: #00c0ef;
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

        Enterprise Treasury Governance Center

        <small>
            Liquidity Intelligence • Treasury Governance • Cashflow Monitoring
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

                    {{ $total_treasury_records }}

                </h2>

                <p>
                    Treasury Records
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-bank"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#00a65a;">

                <h2 style="font-size:22px;">

                    {{ number_format($total_liquidity, 2) }}

                </h2>

                <p>
                    Available Liquidity
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-money"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#f39c12;">

                <h2 style="font-size:22px;">

                    {{ number_format($cashflow_exposure, 2) }}

                </h2>

                <p>
                    Cashflow Exposure
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-line-chart"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#dd4b39;">

                <h2 style="font-size:22px;">

                    {{ number_format($funding_gap, 2) }}

                </h2>

                <p>
                    Funding Gap
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-warning"></i>
                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- TREASURY GOVERNANCE -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-shield"></i>

                    Treasury Governance Intelligence

                </div>

                <div class="progress-group">

                    <span>
                        Liquidity Governance
                    </span>

                    <span class="pull-right">
                        Active
                    </span>

                    <div class="progress">

                        <div class="progress-bar progress-bar-success"
                             style="width:100%">
                        </div>

                    </div>

                </div>

                <br>

                <div class="progress-group">

                    <span>
                        Cashflow Forecasting
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
                        Treasury Stress Testing
                    </span>

                    <span class="pull-right">
                        Enabled
                    </span>

                    <div class="progress">

                        <div class="progress-bar progress-bar-danger"
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

                    Executive Treasury Intelligence

                </div>

                <table class="table modern-table">

                    <tbody>

                        <tr>

                            <th width="50%">
                                Liquidity Governance
                            </th>

                            <td>
                                Operational
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Treasury Risk Monitoring
                            </th>

                            <td>
                                Active
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Recovery Inflow Intelligence
                            </th>

                            <td>
                                Running
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Funding Governance
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
    <!-- TREASURY TABLE -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-database"></i>

            Enterprise Treasury Governance Records

        </div>

        <!-- ============================================= -->
        <!-- FILTERS -->
        <!-- ============================================= -->

        <div class="row"
             style="margin-bottom:15px;">

            <div class="col-md-4">

                <input type="text"
                       id="treasurySearch"
                       class="form-control"
                       placeholder="Search Treasury Records">

            </div>

            <div class="col-md-3">

                <select id="riskFilter"
                        class="form-control">

                    <option value="">
                        All Liquidity Risks
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

                <select id="governanceFilter"
                        class="form-control">

                    <option value="">
                        All Governance Status
                    </option>

                    <option value="stable">
                        Stable
                    </option>

                    <option value="escalated">
                        Escalated
                    </option>

                </select>

            </div>

        </div>

        <!-- ============================================= -->
        <!-- TABLE -->
        <!-- ============================================= -->

        <div class="table-responsive">

            <table class="table modern-table table-striped"
                   id="treasuryTable">

                <thead>

                    <tr>

                        <th>Reference No</th>
                        <th>Treasury Type</th>
                        <th>Liquidity</th>
                        <th>Projection</th>
                        <th>Exposure</th>
                        <th>Funding Gap</th>
                        <th>Risk Level</th>
                        <th>Governance</th>
                        <th width="180">Actions</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($treasury_records as $treasury)

                    <tr
                        data-risk="{{ $treasury->liquidity_risk_level }}"
                        data-governance="{{ $treasury->governance_status }}">

                        <td>

                            {{ $treasury->reference_no }}

                        </td>

                        <td>

                            {{
                                ucwords(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $treasury->treasury_type
                                    )
                                )
                            }}

                        </td>

                        <td>

                            {{
                                number_format(
                                    $treasury->available_liquidity,
                                    2
                                )
                            }}

                        </td>

                        <td>

                            {{
                                number_format(
                                    $treasury->cashflow_projection,
                                    2
                                )
                            }}

                        </td>

                        <td>

                            {{
                                number_format(
                                    $treasury->cashflow_exposure,
                                    2
                                )
                            }}

                        </td>

                        <td>

                            <span class="label label-danger">

                                {{
                                    number_format(
                                        $treasury->funding_gap,
                                        2
                                    )
                                }}

                            </span>

                        </td>

                        <td>

                            @if($treasury->liquidity_risk_level == 'critical')

                                <span class="label label-danger">
                                    Critical
                                </span>

                            @elseif($treasury->liquidity_risk_level == 'high')

                                <span class="label label-warning">
                                    High
                                </span>

                            @elseif($treasury->liquidity_risk_level == 'medium')

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

                            @if($treasury->governance_status == 'escalated')

                                <span class="label label-danger">
                                    Escalated
                                </span>

                            @else

                                <span class="label label-success">
                                    Stable
                                </span>

                            @endif

                        </td>

                        <td>

                            <a href="/loan/loan-treasury/{{ $treasury->id }}/show"
                               class="btn btn-xs btn-primary">

                                <i class="fa fa-eye"></i>

                                View

                            </a>

                            <form method="POST"
                                  action="/loan/loan-treasury/{{ $treasury->id }}/stress-test"
                                  style="display:inline;">

                                @csrf

                                <button type="submit"
                                        class="btn btn-xs btn-danger">

                                    <i class="fa fa-warning"></i>

                                    Stress Test

                                </button>

                            </form>

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="9"
                            class="text-center">

                            No treasury governance records found

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="text-right">

            {{ $treasury_records->links() }}

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- TIMELINE -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-history"></i>

            Treasury Governance Timeline

        </div>

        <ul class="timeline-modern">

            @foreach($treasury_records->take(10) as $treasury)

            <li>

                <div class="timeline-card">

                    <strong>

                        {{ $treasury->reference_no }}

                    </strong>

                    <br><br>

                    {{ $treasury->treasury_notes }}

                    <hr>

                    <small class="text-muted">

                        {{ $treasury->created_at }}

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

    $('#treasurySearch').on('keyup', function() {

        var value = $(this).val().toLowerCase();

        $('#treasuryTable tbody tr').filter(function() {

            $(this).toggle(
                $(this).text().toLowerCase().indexOf(value) > -1
            );

        });

    });

    $('#riskFilter, #governanceFilter').on('change', function() {

        applyTreasuryFilters();

    });

    function applyTreasuryFilters()
    {
        var risk =
            $('#riskFilter').val();

        var governance =
            $('#governanceFilter').val();

        $('#treasuryTable tbody tr').each(function() {

            var rowRisk =
                $(this).data('risk');

            var rowGovernance =
                $(this).data('governance');

            var riskMatch =
                (
                    risk == '' ||
                    rowRisk == risk
                );

            var governanceMatch =
                (
                    governance == '' ||
                    rowGovernance == governance
                );

            if (
                riskMatch &&
                governanceMatch
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