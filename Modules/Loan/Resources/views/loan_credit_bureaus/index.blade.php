@extends('layouts.app')

@section('title', 'Credit Bureau Operations Center')

@section('content')

<section class="content-header">

    <h1>
        Credit Bureau Operations Center
        <small>
            Enterprise External Risk & Credit Intelligence
        </small>
    </h1>

</section>

<section class="content">

<!-- ===================================================== -->
<!-- KPI DASHBOARD -->
<!-- ===================================================== -->

<div class="row">

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-aqua">

            <span class="info-box-icon">

                <i class="fa fa-database"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Bureau Records

                </span>

                <span class="info-box-number">

                    {{ $total_records }}

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-red">

            <span class="info-box-icon">

                <i class="fa fa-warning"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    High Risk Customers

                </span>

                <span class="info-box-number">

                    {{ $high_risk_customers }}

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-yellow">

            <span class="info-box-icon">

                <i class="fa fa-exclamation-circle"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Bureau Defaulters

                </span>

                <span class="info-box-number">

                    {{ $bureau_defaulters }}

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-green">

            <span class="info-box-icon">

                <i class="fa fa-refresh"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Active Syncs

                </span>

                <span class="info-box-number">

                    {{ $active_syncs }}

                </span>

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- GOVERNANCE -->
<!-- ===================================================== -->

<div class="row">

    <div class="col-md-6">

        <div class="box box-primary">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Bureau Governance Intelligence

                </h3>

            </div>

            <div class="box-body">

                <div class="progress-group">

                    <span class="progress-text">

                        Bureau Synchronization

                    </span>

                    <span class="progress-number">

                        Operational

                    </span>

                    <div class="progress sm">

                        <div class="progress-bar progress-bar-aqua"
                             style="width:100%">

                        </div>

                    </div>

                </div>

                <div class="progress-group">

                    <span class="progress-text">

                        External Risk Intelligence

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

                <div class="progress-group">

                    <span class="progress-text">

                        Multi-Lender Monitoring

                    </span>

                    <span class="progress-number">

                        Active

                    </span>

                    <div class="progress sm">

                        <div class="progress-bar progress-bar-green"
                             style="width:100%">

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- ================================================ -->
    <!-- ANALYTICS -->
    <!-- ================================================ -->

    <div class="col-md-6">

        <div class="box box-warning">

            <div class="box-header with-border">

                <h3 class="box-title">

                    External Credit Analytics

                </h3>

            </div>

            <div class="box-body">

                <table class="table table-bordered">

                    <tr>

                        <th width="50%">
                            External Delinquency Monitoring
                        </th>

                        <td>

                            Active

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Credit Behavior Intelligence
                        </th>

                        <td>

                            Enabled

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Collections Intelligence
                        </th>

                        <td>

                            Operational

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Autonomous Bureau Monitoring
                        </th>

                        <td>

                            Running

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- TABLE -->
<!-- ===================================================== -->

<div class="box box-danger">

    <div class="box-header with-border">

        <h3 class="box-title">

            Enterprise Credit Bureau Profiles

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
                       id="bureauSearch"
                       class="form-control"
                       placeholder="Search Bureau Profiles">

            </div>

            <div class="col-md-3">

                <select id="riskFilter"
                        class="form-control">

                    <option value="">
                        All Risk Levels
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
                        All Bureau Status
                    </option>

                    <option value="active">
                        Active
                    </option>

                    <option value="defaulted">
                        Defaulted
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

            <table class="table table-bordered table-striped"
                   id="bureauTable">

                <thead>

                    <tr>

                        <th>
                            Reference No
                        </th>

                        <th>
                            Customer
                        </th>

                        <th>
                            Loan No
                        </th>

                        <th>
                            Bureau
                        </th>

                        <th>
                            Credit Score
                        </th>

                        <th>
                            Risk Level
                        </th>

                        <th>
                            Exposure
                        </th>

                        <th>
                            Delinquency
                        </th>

                        <th>
                            Sync Status
                        </th>

                        <th width="180">
                            Actions
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($bureau_records as $bureau)

                    <tr
                        data-risk="{{ $bureau->risk_level }}"
                        data-status="{{ $bureau->bureau_status }}">

                        <td>

                            {{ $bureau->reference_no }}

                        </td>

                        <td>

                            {{
                                optional($bureau->customer)
                                    ->name
                            }}

                        </td>

                        <td>

                            {{
                                optional($bureau->loan)
                                    ->loan_no
                            }}

                        </td>

                        <td>

                            {{ $bureau->bureau_name }}

                        </td>

                        <td>

                            <span class="label label-primary">

                                {{ $bureau->credit_score }}

                            </span>

                        </td>

                        <td>

                            @if($bureau->risk_level == 'high')

                                <span class="label label-danger">
                                    High
                                </span>

                            @elseif($bureau->risk_level == 'medium')

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

                            {{
                                number_format(
                                    $bureau->total_exposure,
                                    2
                                )
                            }}

                        </td>

                        <td>

                            <span class="label label-danger">

                                {{
                                    $bureau->delinquency_days
                                }} Days

                            </span>

                        </td>

                        <td>

                            @if($bureau->sync_status == 'synced')

                                <span class="label label-success">
                                    Synced
                                </span>

                            @else

                                <span class="label label-warning">
                                    Pending
                                </span>

                            @endif

                        </td>

                        <td>

                            <a href="/loan/loan-credit-bureaus/{{ $bureau->id }}/show"
                               class="btn btn-xs btn-primary">

                                <i class="fa fa-eye"></i>

                                View

                            </a>

                            <form method="POST"
                                  action="/loan/loan-credit-bureaus/{{ $bureau->id }}/resync"
                                  style="display:inline;">

                                @csrf

                                <button type="submit"
                                        class="btn btn-xs btn-success">

                                    <i class="fa fa-refresh"></i>

                                    Sync

                                </button>

                            </form>

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="10"
                            class="text-center">

                            No bureau records found

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="text-right">

            {{ $bureau_records->links() }}

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- TIMELINE -->
<!-- ===================================================== -->

<div class="box box-info">

    <div class="box-header with-border">

        <h3 class="box-title">

            Bureau Synchronization Timeline

        </h3>

    </div>

    <div class="box-body">

        <ul class="timeline">

            @foreach($bureau_records->take(10) as $bureau)

            <li>

                <i class="fa fa-database bg-aqua"></i>

                <div class="timeline-item">

                    <span class="time">

                        <i class="fa fa-clock-o"></i>

                        {{ $bureau->created_at }}

                    </span>

                    <h3 class="timeline-header">

                        {{ $bureau->reference_no }}

                    </h3>

                    <div class="timeline-body">

                        {{ $bureau->notes }}

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

    $('#bureauSearch').on('keyup', function() {

        var value = $(this).val().toLowerCase();

        $('#bureauTable tbody tr').filter(function() {

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

        applyFilters();

    });

    function applyFilters()
    {
        var risk =
            $('#riskFilter').val();

        var status =
            $('#statusFilter').val();

        $('#bureauTable tbody tr').each(function() {

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