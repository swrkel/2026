@extends('layouts.app')

@section('title', __('Consolidated Profit & Loss'))

@section('content')

@php

    $total_income = isset($total_income)
        ? round($total_income, 2)
        : 0;

    $total_expense = isset($total_expense)
        ? round($total_expense, 2)
        : 0;

    $net_profit = isset($net_profit)
        ? round($net_profit, 2)
        : 0;

    $grouped_income = collect($income_accounts ?? [])
        ->groupBy('location_id');

    $grouped_expense = collect($expense_accounts ?? [])
        ->groupBy('location_id');

    $all_locations = $grouped_income->keys()
        ->merge($grouped_expense->keys())
        ->unique()
        ->sort();

    $branch_count = $all_locations->count();

@endphp

<section class="content-header">

    <h1>
        Consolidated Profit & Loss
        <small>Enterprise Multi-Branch Consolidated Reporting</small>
    </h1>

</section>

<section class="content">

    <div class="box box-success">

        <div class="box-header with-border">

            <h3 class="box-title">

                <i class="fa fa-sitemap"></i>

                Consolidated Profit & Loss Report

            </h3>

        </div>

        <div class="box-body">

            <div class="row">

                <div class="col-md-3">

                    <div class="small-box bg-green">

                        <div class="inner">

                            <h3>{{ number_format($total_income, 2) }}</h3>

                            <p>Total Income</p>

                        </div>

                        <div class="icon">
                            <i class="fa fa-arrow-up"></i>
                        </div>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="small-box bg-red">

                        <div class="inner">

                            <h3>{{ number_format($total_expense, 2) }}</h3>

                            <p>Total Expenses</p>

                        </div>

                        <div class="icon">
                            <i class="fa fa-arrow-down"></i>
                        </div>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="small-box bg-aqua">

                        <div class="inner">

                            <h3>{{ number_format($net_profit, 2) }}</h3>

                            <p>Net Profit / Loss</p>

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
                            <th>Total Income</th>
                            <th>Total Expenses</th>
                            <th>Net Profit / Loss</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($all_locations as $location_id)

                            @php

                                $income_total =
                                    isset($grouped_income[$location_id])
                                    ? $grouped_income[$location_id]->sum('balance')
                                    : 0;

                                $expense_total =
                                    isset($grouped_expense[$location_id])
                                    ? $grouped_expense[$location_id]->sum('balance')
                                    : 0;

                                $branch_net =
                                    round($income_total - $expense_total, 2);

                            @endphp

                            <tr>

                                <td>

                                    <strong>

                                        {{ $location_id ?: 'COMMON' }}

                                    </strong>

                                </td>

                                <td class="text-right">

                                    {{ number_format($income_total, 2) }}

                                </td>

                                <td class="text-right">

                                    {{ number_format($expense_total, 2) }}

                                </td>

                                <td class="text-right">

                                    {{ number_format($branch_net, 2) }}

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="4"
                                    class="text-center text-muted">

                                    No consolidated financial data available.

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

                                {{ number_format($total_income, 2) }}

                            </th>

                            <th class="text-right">

                                {{ number_format($total_expense, 2) }}

                            </th>

                            <th class="text-right">

                                {{ number_format($net_profit, 2) }}

                            </th>

                        </tr>

                    </tfoot>

                </table>

            </div>

        </div>

    </div>

</section>

@endsection