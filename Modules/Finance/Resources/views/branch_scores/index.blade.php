@extends('layouts.app')

@section('title', 'Branch Financial Performance Scores')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')

<section class="content-header">

    <h1>
        Branch Financial Performance Scores
        <small>Enterprise Branch Intelligence Dashboard</small>
    </h1>

</section>

<section class="content">

    {{-- =========================================================
       HEADER ACTION PANEL
    ========================================================== --}}

    <div class="enterprise-panel">

        <div class="row">

            <div class="col-md-8">

                <div class="enterprise-panel-title">

                    <i class="fa fa-line-chart"></i>

                    Branch Ranking Dashboard

                </div>

                <p style="margin-top:-10px; color:#7f8c8d;">

                    Consolidated branch financial intelligence,
                    liquidity analytics,
                    operational efficiency scoring,
                    and governance ranking overview.

                </p>

            </div>

            <div class="col-md-4 text-right">

                <br>

                <a href="{{ route('finance.branch_scores.generate') }}"
                   class="btn btn-primary">

                    <i class="fa fa-refresh"></i>

                    Generate Scores

                </a>

            </div>

        </div>

    </div>

    {{-- =========================================================
       SUMMARY KPI ROW
    ========================================================== --}}

    <div class="row">

        <div class="col-md-3">

            <div class="enterprise-card enterprise-blue">

                <div class="icon text-primary">
                    <i class="fa fa-building"></i>
                </div>

                <div class="title">
                    Total Branches
                </div>

                <div class="value">
                    {{ number_format($scores->total()) }}
                </div>

                <div class="subtext">
                    Active ranked branches
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-green">

                <div class="icon text-success">
                    <i class="fa fa-trophy"></i>
                </div>

                <div class="title">
                    Top Overall Score
                </div>

                <div class="value">

                    {{ number_format(optional($scores->first())->overall_score ?? 0, 2) }}

                </div>

                <div class="subtext">
                    Highest performing branch
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-yellow">

                <div class="icon text-warning">
                    <i class="fa fa-balance-scale"></i>
                </div>

                <div class="title">
                    Avg Liquidity Score
                </div>

                <div class="value">

                    {{ number_format($scores->avg('liquidity_score'), 2) }}

                </div>

                <div class="subtext">
                    Consolidated liquidity rating
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-red">

                <div class="icon text-danger">
                    <i class="fa fa-warning"></i>
                </div>

                <div class="title">
                    Avg Risk Score
                </div>

                <div class="value">

                    {{ number_format($scores->avg('risk_score'), 2) }}

                </div>

                <div class="subtext">
                    Consolidated branch risk exposure
                </div>

            </div>

        </div>

    </div>

    {{-- =========================================================
       MAIN ANALYTICS TABLE
    ========================================================== --}}

    <div class="enterprise-panel">

        <div class="enterprise-panel-title">

            <i class="fa fa-table"></i>

            Branch Financial Score Analytics

        </div>

        <div class="table-responsive">

            <table class="table table-bordered table-hover enterprise-table">

                <thead>

                    <tr>

                        <th width="6%">
                            Rank
                        </th>

                        <th>
                            Branch
                        </th>

                        <th class="text-right">
                            Profitability
                        </th>

                        <th class="text-right">
                            Liquidity
                        </th>

                        <th class="text-right">
                            Collections
                        </th>

                        <th class="text-right">
                            Treasury
                        </th>

                        <th class="text-right">
                            Efficiency
                        </th>

                        <th class="text-right">
                            Risk
                        </th>

                        <th class="text-right">
                            Overall Score
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($scores as $score)

                        <tr>

                            <td>

                                <span class="label label-primary">

                                    {{ $score->ranking_position }}

                                </span>

                            </td>

                            <td>

                                <strong>

                                    {{ optional($score->location)->name }}

                                </strong>

                            </td>

                            <td class="text-right">

                                {{ number_format($score->profitability_score, 2) }}

                            </td>

                            <td class="text-right">

                                {{ number_format($score->liquidity_score, 2) }}

                            </td>

                            <td class="text-right">

                                {{ number_format($score->collection_score, 2) }}

                            </td>

                            <td class="text-right">

                                {{ number_format($score->treasury_score, 2) }}

                            </td>

                            <td class="text-right">

                                {{ number_format($score->efficiency_score, 2) }}

                            </td>

                            <td class="text-right">

                                {{ number_format($score->risk_score, 2) }}

                            </td>

                            <td class="text-right">

                                <span class="enterprise-metric">

                                    {{ number_format($score->overall_score, 2) }}

                                </span>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        <div class="text-center" style="margin-top:20px;">

            {{ $scores->links() }}

        </div>

    </div>

</section>

@endsection