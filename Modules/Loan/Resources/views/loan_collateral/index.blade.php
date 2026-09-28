@extends('layouts.app')

@section('title', 'Collateral & Security Governance Center')

@section('content')

<section class="content-header">

    <h1>
        Collateral & Security Governance Center
        <small>
            Enterprise Asset Security, Valuation & LTV Intelligence
        </small>
    </h1>

</section>

<section class="content">

<!-- ===================================================== -->
<!-- COLLATERAL KPI DASHBOARD -->
<!-- ===================================================== -->

<div class="row">

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-aqua">

            <span class="info-box-icon">

                <i class="fa fa-building"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Collateral Records

                </span>

                <span class="info-box-number">

                    {{ $total_collaterals }}

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-green">

            <span class="info-box-icon">

                <i class="fa fa-money"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Collateral Value

                </span>

                <span class="info-box-number"
                      style="font-size:18px;">

                    {{
                        number_format(
                            $total_collateral_value,
                            2
                        )
                    }}

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

                    Impaired Assets

                </span>

                <span class="info-box-number">

                    {{ $impaired_collaterals }}

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-yellow">

            <span class="info-box-icon">

                <i class="fa fa-file-text"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Expired Security Docs

                </span>

                <span class="info-box-number">

                    {{ $expired_security_docs }}

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

                    Collateral Governance Intelligence

                </h3>

            </div>

            <div class="box-body">

                <div class="progress-group">

                    <span class="progress-text">

                        Asset Valuation Governance

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

                        Security Documentation

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

                <div class="progress-group">

                    <span class="progress-text">

                        Collateral Monitoring

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

                    Executive Collateral Intelligence

                </h3>

            </div>

            <div class="box-body">

                <table class="table table-bordered">

                    <tr>

                        <th width="50%">
                            LTV Governance
                        </th>

                        <td>

                            Operational

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Impairment Monitoring
                        </th>

                        <td>

                            Active

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Insurance Governance
                        </th>

                        <td>

                            Running

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Executive Asset Visibility
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
<!-- COLLATERAL TABLE -->
<!-- ===================================================== -->

<div class="box box-danger">

    <div class="box-header with-border">

        <h3 class="box-title">

            Enterprise Collateral Governance Records

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
                       id="collateralSearch"
                       class="form-control"
                       placeholder="Search Collateral Records">

            </div>

            <div class="col-md-3">

                <select id="riskFilter"
                        class="form-control">

                    <option value="">
                        All Risk Levels
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

                <select id="impairmentFilter"
                        class="form-control">

                    <option value="">
                        All Impairment Status
                    </option>

                    <option value="healthy">
                        Healthy
                    </option>

                    <option value="impaired">
                        Impaired
                    </option>

                </select>

            </div>

        </div>

        <!-- ============================================= -->
        <!-- TABLE -->
        <!-- ============================================= -->

        <div class="table-responsive">

            <table class="table table-bordered table-striped"
                   id="collateralTable">

                <thead>

                    <tr>

                        <th>
                            Reference No
                        </th>

                        <th>
                            Collateral Type
                        </th>

                        <th>
                            Market Value
                        </th>

                        <th>
                            Forced Sale Value
                        </th>

                        <th>
                            LTV Ratio
                        </th>

                        <th>
                            Risk Level
                        </th>

                        <th>
                            Security Status
                        </th>

                        <th>
                            Impairment
                        </th>

                        <th width="200">
                            Actions
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($collaterals as $collateral)

                    <tr
                        data-risk="{{ $collateral->risk_level }}"
                        data-impairment="{{ $collateral->impairment_status }}">

                        <td>

                            {{ $collateral->reference_no }}

                        </td>

                        <td>

                            {{
                                ucwords(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $collateral->collateral_type
                                    )
                                )
                            }}

                        </td>

                        <td>

                            {{
                                number_format(
                                    $collateral->market_value,
                                    2
                                )
                            }}

                        </td>

                        <td>

                            {{
                                number_format(
                                    $collateral->forced_sale_value,
                                    2
                                )
                            }}

                        </td>

                        <td>

                            <span class="label label-primary">

                                {{ $collateral->ltv_ratio }}%

                            </span>

                        </td>

                        <td>

                            @if($collateral->risk_level == 'critical')

                                <span class="label label-danger">
                                    Critical
                                </span>

                            @elseif($collateral->risk_level == 'high')

                                <span class="label label-warning">
                                    High
                                </span>

                            @elseif($collateral->risk_level == 'medium')

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

                            <span class="label label-default">

                                {{
                                    ucfirst(
                                        $collateral->security_status
                                    )
                                }}

                            </span>

                        </td>

                        <td>

                            @if($collateral->impairment_status == 'impaired')

                                <span class="label label-danger">
                                    Impaired
                                </span>

                            @else

                                <span class="label label-success">
                                    Healthy
                                </span>

                            @endif

                        </td>

                        <td>

                            <a href="/loan/loan-collateral/{{ $collateral->id }}/show"
                               class="btn btn-xs btn-primary">

                                <i class="fa fa-eye"></i>

                                View

                            </a>

                            <button type="button"
                                    class="btn btn-xs btn-warning"
                                    data-toggle="modal"
                                    data-target="#revalueModal{{ $collateral->id }}">

                                <i class="fa fa-refresh"></i>

                                Revalue

                            </button>

                        </td>

                    </tr>

                    <!-- ========================================= -->
                    <!-- REVALUATION MODAL -->
                    <!-- ========================================= -->

                    <div class="modal fade"
                         id="revalueModal{{ $collateral->id }}">

                        <div class="modal-dialog">

                            <div class="modal-content">

                                <form method="POST"
                                      action="/loan/loan-collateral/{{ $collateral->id }}/revalue">

                                    @csrf

                                    <div class="modal-header">

                                        <button type="button"
                                                class="close"
                                                data-dismiss="modal">

                                            &times;

                                        </button>

                                        <h4 class="modal-title">

                                            Revalue Collateral

                                        </h4>

                                    </div>

                                    <div class="modal-body">

                                        <div class="form-group">

                                            <label>

                                                Market Value

                                            </label>

                                            <input type="number"
                                                   step="0.01"
                                                   name="market_value"
                                                   class="form-control"
                                                   required>

                                        </div>

                                        <div class="form-group">

                                            <label>

                                                Forced Sale Value

                                            </label>

                                            <input type="number"
                                                   step="0.01"
                                                   name="forced_sale_value"
                                                   class="form-control"
                                                   required>

                                        </div>

                                    </div>

                                    <div class="modal-footer">

                                        <button type="submit"
                                                class="btn btn-primary">

                                            Submit Revaluation

                                        </button>

                                    </div>

                                </form>

                            </div>

                        </div>

                    </div>

                    @empty

                    <tr>

                        <td colspan="9"
                            class="text-center">

                            No collateral governance records found

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="text-right">

            {{ $collaterals->links() }}

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- COLLATERAL TIMELINE -->
<!-- ===================================================== -->

<div class="box box-info">

    <div class="box-header with-border">

        <h3 class="box-title">

            Collateral Governance Timeline

        </h3>

    </div>

    <div class="box-body">

        <ul class="timeline">

            @foreach($collaterals->take(10) as $collateral)

            <li>

                <i class="fa fa-building bg-aqua"></i>

                <div class="timeline-item">

                    <span class="time">

                        <i class="fa fa-clock-o"></i>

                        {{ $collateral->created_at }}

                    </span>

                    <h3 class="timeline-header">

                        {{ $collateral->reference_no }}

                    </h3>

                    <div class="timeline-body">

                        {{ $collateral->description }}

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

    $('#collateralSearch').on('keyup', function() {

        var value = $(this).val().toLowerCase();

        $('#collateralTable tbody tr').filter(function() {

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

    $('#riskFilter, #impairmentFilter').on('change', function() {

        applyCollateralFilters();

    });

    function applyCollateralFilters()
    {
        var risk =
            $('#riskFilter').val();

        var impairment =
            $('#impairmentFilter').val();

        $('#collateralTable tbody tr').each(function() {

            var rowRisk =
                $(this).data('risk');

            var rowImpairment =
                $(this).data('impairment');

            var riskMatch =
                (
                    risk == '' ||
                    rowRisk == risk
                );

            var impairmentMatch =
                (
                    impairment == '' ||
                    rowImpairment == impairment
                );

            if (
                riskMatch &&
                impairmentMatch
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