@extends('layouts.app')

@section('title', __('Consolidated Balance Sheet'))

@section('content')

@php

    $total_assets = isset($total_assets)
        ? round($total_assets, 2)
        : 0;

    $total_liabilities = isset($total_liabilities)
        ? round($total_liabilities, 2)
        : 0;

    $total_equity = isset($total_equity)
        ? round($total_equity, 2)
        : 0;

    $asset_groups = collect($asset_accounts ?? [])
        ->groupBy('location_id');

    $liability_groups = collect($liability_accounts ?? [])
        ->groupBy('location_id');

    $equity_groups = collect($equity_accounts ?? [])
        ->groupBy('location_id');

    $all_locations = $asset_groups->keys()
        ->merge($liability_groups->keys())
        ->merge($equity_groups->keys())
        ->unique()
        ->sort();

    $branch_count = $all_locations->count();

@endphp

<section class="content-header">

    <h1>
        Consolidated Balance Sheet
        <small>Enterprise Multi-Branch Consolidated Financial Position</small>
    </h1>

</section>

<section class="content">

    <div class="box box-success">

        <div class="box-header with-border">

            <h3 class="box-title">

                <i class="fa fa-bank"></i>

                Consolidated Balance Sheet Report

            </h3>

        </div>

        <div class="box-body">

            <div class="row">

                <div class="col-md-3">

                    <div class="small-box bg-aqua">

                        <div class="inner">

                            <h3>{{ number_format($total_assets, 2) }}</h3>

                            <p>Total Assets</p>

                        </div>

                        <div class="icon">
                            <i class="fa fa-bank"></i>
                        </div>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="small-box bg-red">

                        <div class="inner">

                            <h3>{{ number_format($total_liabilities, 2) }}</h3>

                            <p>Total Liabilities</p>

                        </div>

                        <div class="icon">
                            <i class="fa fa-credit-card"></i>
                        </div>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="small-box bg-green">

                        <div class="inner">

                            <h3>{{ number_format($total_equity, 2) }}</h3>

                            <p>Total Equity</p>

                        </div>

                        <div class="icon">
                            <i class="fa fa-line-chart"></i>
                        </div>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="small-box bg-yellow">

                        <div class="inner">

                            <h3>{{ $branch_count }}</h3>

                            <p>Total Branches</p>

                        </div>

                        <div class="icon">
                            <i class="fa fa-building"></i>
                        </div>

                    </div>

                </div>

            </div>

            <div class="table-responsive">

                <table class="table table-bordered table-striped">

                    <thead>

                        <tr class="bg-success">

                            <th>Branch</th>
                            <th>Total Assets</th>
                            <th>Total Liabilities</th>
                            <th>Total Equity</th>
                            <th>Total Liabilities & Equity</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($all_locations as $location_id)

                            @php

                                $branch_assets =
                                    isset($asset_groups[$location_id])
                                    ? $asset_groups[$location_id]->sum('balance')
                                    : 0;

                                $branch_liabilities =
                                    isset($liability_groups[$location_id])
                                    ? abs($liability_groups[$location_id]->sum('balance'))
                                    : 0;

                                $branch_equity =
                                    isset($equity_groups[$location_id])
                                    ? abs($equity_groups[$location_id]->sum('balance'))
                                    : 0;

                            @endphp

                            <tr>

                                <td>

                                    <strong>

                                        {{ $location_id ?: 'COMMON' }}

                                    </strong>

                                </td>

                                <td class="text-right">

                                    {{ number_format($branch_assets, 2) }}

                                </td>

                                <td class="text-right">

                                    {{ number_format($branch_liabilities, 2) }}

                                </td>

                                <td class="text-right">

                                    {{ number_format($branch_equity, 2) }}

                                </td>

                                <td class="text-right">

                                    {{ number_format($branch_liabilities + $branch_equity, 2) }}

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="5"
                                    class="text-center text-muted">

                                    No consolidated balance sheet data available.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                    <tfoot>

                        <tr class="bg-gray">

                            <th>

                                Totals

                            </th>

                            <th class="text-right">

                                {{ number_format($total_assets, 2) }}

                            </th>

                            <th class="text-right">

                                {{ number_format($total_liabilities, 2) }}

                            </th>

                            <th class="text-right">

                                {{ number_format($total_equity, 2) }}

                            </th>

                            <th class="text-right">

                                {{ number_format($total_liabilities + $total_equity, 2) }}

                            </th>

                        </tr>

                    </tfoot>

                </table>

            </div>

        </div>

    </div>

</section>

@endsection