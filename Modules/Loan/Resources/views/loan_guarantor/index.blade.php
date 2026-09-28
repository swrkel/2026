@extends('layouts.app')

@section('title', 'Guarantor & Co-Borrower Governance Center')

@section('content')

<section class="content-header">

    <h1>
        Guarantor & Co-Borrower Governance Center
        <small>
            Enterprise Exposure, Liability & Recovery Intelligence
        </small>
    </h1>

</section>

<section class="content">

<!-- ===================================================== -->
<!-- GUARANTOR KPI DASHBOARD -->
<!-- ===================================================== -->

<div class="row">

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-aqua">

            <span class="info-box-icon">

                <i class="fa fa-users"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Total Guarantors

                </span>

                <span class="info-box-number">

                    {{ $total_guarantors }}

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-green">

            <span class="info-box-icon">

                <i class="fa fa-check-circle"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Active Guarantors

                </span>

                <span class="info-box-number">

                    {{ $active_guarantors }}

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-yellow">

            <span class="info-box-icon">

                <i class="fa fa-line-chart"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    High Exposure

                </span>

                <span class="info-box-number">

                    {{ $high_exposure_guarantors }}

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-red">

            <span class="info-box-icon">

                <i class="fa fa-gavel"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Legal Actions

                </span>

                <span class="info-box-number">

                    {{ $legal_action_guarantors }}

                </span>

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- GOVERNANCE PANELS -->
<!-- ===================================================== -->

<div class="row">

    <div class="col-md-6">

        <div class="box box-primary">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Guarantor Governance Intelligence

                </h3>

            </div>

            <div class="box-body">

                <div class="progress-group">

                    <span class="progress-text">

                        Exposure Monitoring

                    </span>

                    <span class="progress-number">

                        Active

                    </span>

                    <div class="progress sm">

                        <div class="progress-bar progress-bar-aqua"
                             style="width:100%">

                        </div>

                    </div>

                </div>

                <div class="progress-group">

                    <span class="progress-text">

                        Liability Governance

                    </span>

                    <span class="progress-number">

                        Operational

                    </span>

                    <div class="progress sm">

                        <div class="progress-bar progress-bar-green"
                             style="width:100%">

                        </div>

                    </div>

                </div>

                <div class="progress-group">

                    <span class="progress-text">

                        Legal Recovery Governance

                    </span>

                    <span class="progress-number">

                        Enabled

                    </span>

                    <div class="progress sm">

                        <div class="progress-bar progress-bar-red"
                             style="width:100%">

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- ================================================ -->
    <!-- EXECUTIVE -->
    <!-- ================================================ -->

    <div class="col-md-6">

        <div class="box box-warning">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Executive Guarantor Intelligence

                </h3>

            </div>

            <div class="box-body">

                <table class="table table-bordered">

                    <tr>

                        <th width="50%">
                            Exposure Aggregation
                        </th>

                        <td>

                            Operational

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Co-Borrower Intelligence
                        </th>

                        <td>

                            Active

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Liability Enforcement
                        </th>

                        <td>

                            Running

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Executive Exposure Visibility
                        </th>

                        <td>

                            Enabled

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- GUARANTOR TABLE -->
<!-- ===================================================== -->

<div class="box box-danger">

    <div class="box-header with-border">

        <h3 class="box-title">

            Enterprise Guarantor Governance Records

        </h3>

    </div>

    <div class="box-body">

        <!-- ============================================= -->
        <!-- FILTERS -->
        <!-- ============================================= -->

        <div class="row"
             style="margin-bottom:15px;">

            <div class="col-md-4">

                <input type="text"
                       id="guarantorSearch"
                       class="form-control"
                       placeholder="Search Guarantor Records">

            </div>

            <div class="col-md-3">

                <select id="riskFilter"
                        class="form-control">

                    <option value="">
                        All Exposure Risks
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
                        All Status
                    </option>

                    <option value="active">
                        Active
                    </option>

                    <option value="inactive">
                        Inactive
                    </option>

                </select>

            </div>

        </div>

        <!-- ============================================= -->
        <!-- TABLE -->
        <!-- ============================================= -->

        <div class="table-responsive">

            <table class="table table-bordered table-striped"
                   id="guarantorTable">

                <thead>

                    <tr>

                        <th>
                            Reference No
                        </th>

                        <th>
                            Guarantor
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Guarantee Amount
                        </th>

                        <th>
                            Total Exposure
                        </th>

                        <th>
                            Coverage Ratio
                        </th>

                        <th>
                            Risk Level
                        </th>

                        <th>
                            Legal Status
                        </th>

                        <th width="220">
                            Actions
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($guarantors as $guarantor)

                    <tr
                        data-risk="{{ $guarantor->exposure_risk_level }}"
                        data-status="{{ $guarantor->status }}">

                        <td>

                            {{ $guarantor->reference_no }}

                        </td>

                        <td>

                            {{ $guarantor->guarantor_name }}

                            @if($guarantor->co_borrower_flag)

                                <br>

                                <span class="label label-info">

                                    Co-Borrower

                                </span>

                            @endif

                        </td>

                        <td>

                            {{
                                ucwords(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $guarantor->guarantor_type
                                    )
                                )
                            }}

                        </td>

                        <td>

                            {{
                                number_format(
                                    $guarantor->guarantee_amount,
                                    2
                                )
                            }}

                        </td>

                        <td>

                            <span class="label label-primary">

                                {{
                                    number_format(
                                        $guarantor->total_exposure,
                                        2
                                    )
                                }}

                            </span>

                        </td>

                        <td>

                            {{
                                number_format(
                                    $guarantor->coverage_ratio,
                                    2
                                )
                            }}%

                        </td>

                        <td>

                            @if($guarantor->exposure_risk_level == 'critical')

                                <span class="label label-danger">
                                    Critical
                                </span>

                            @elseif($guarantor->exposure_risk_level == 'high')

                                <span class="label label-warning">
                                    High
                                </span>

                            @elseif($guarantor->exposure_risk_level == 'medium')

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

                            @if($guarantor->legal_action_status == 'initiated')

                                <span class="label label-danger">
                                    Legal Action
                                </span>

                            @elseif($guarantor->legal_action_status == 'review_pending')

                                <span class="label label-warning">
                                    Review Pending
                                </span>

                            @else

                                <span class="label label-success">
                                    Normal
                                </span>

                            @endif

                        </td>

                        <td>

                            <a href="/loan/loan-guarantor/{{ $guarantor->id }}/show"
                               class="btn btn-xs btn-primary">

                                <i class="fa fa-eye"></i>

                                View

                            </a>

                            @if($guarantor->legal_action_status != 'initiated')

                            <form method="POST"
                                  action="/loan/loan-guarantor/{{ $guarantor->id }}/initiate-legal-action"
                                  style="display:inline;">

                                @csrf

                                <button type="submit"
                                        class="btn btn-xs btn-danger">

                                    <i class="fa fa-gavel"></i>

                                    Legal Action

                                </button>

                            </form>

                            @endif

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="9"
                            class="text-center">

                            No guarantor governance records found

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="text-right">

            {{ $guarantors->links() }}

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- GUARANTOR TIMELINE -->
<!-- ===================================================== -->

<div class="box box-info">

    <div class="box-header with-border">

        <h3 class="box-title">

            Guarantor Governance Timeline

        </h3>

    </div>

    <div class="box-body">

        <ul class="timeline">

            @foreach($guarantors->take(10) as $guarantor)

            <li>

                <i class="fa fa-users bg-aqua"></i>

                <div class="timeline-item">

                    <span class="time">

                        <i class="fa fa-clock-o"></i>

                        {{ $guarantor->created_at }}

                    </span>

                    <h3 class="timeline-header">

                        {{ $guarantor->reference_no }}

                    </h3>

                    <div class="timeline-body">

                        {{ $guarantor->notes }}

                    </div>

                </div>

            </li>

            @endforeach

        </ul>

    </div>

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

    $('#guarantorSearch').on('keyup', function() {

        var value = $(this).val().toLowerCase();

        $('#guarantorTable tbody tr').filter(function() {

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

    $('#riskFilter, #statusFilter').on('change', function() {

        applyGuarantorFilters();

    });

    function applyGuarantorFilters()
    {
        var risk =
            $('#riskFilter').val();

        var status =
            $('#statusFilter').val();

        $('#guarantorTable tbody tr').each(function() {

            var rowRisk =
                $(this).data('risk');

            var rowStatus =
                $(this).data('status');

            var riskMatch =
                (
                    risk == '' ||
                    rowRisk == risk
                );

            var statusMatch =
                (
                    status == '' ||
                    rowStatus == status
                );

            if (
                riskMatch &&
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