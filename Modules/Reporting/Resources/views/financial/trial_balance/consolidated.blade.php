@extends('layouts.app')

@section('title', __('Consolidated Trial Balance'))

@section('content')

@php

    $total_accounts = isset($accounts)
        ? $accounts->count()
        : 0;

    $total_debit = isset($total_debit)
        ? round($total_debit, 2)
        : 0;

    $total_credit = isset($total_credit)
        ? round($total_credit, 2)
        : 0;

    $difference = isset($difference)
        ? round($difference, 2)
        : 0;

    $grouped_accounts = collect($accounts ?? [])
        ->groupBy('location_id');

    $branch_count = $grouped_accounts->count();

@endphp

<section class="content-header">

    <h1>
        Consolidated Trial Balance
        <small>Enterprise Multi-Branch Debit & Credit Summary</small>
    </h1>

</section>

<section class="content">

    <div class="box box-success">

        <div class="box-header with-border">

            <h3 class="box-title">

                <i class="fa fa-files-o"></i>

                Consolidated Trial Balance Report

            </h3>

        </div>

        <div class="box-body">

            <div class="row">

                <div class="col-md-3">

                    <div class="small-box bg-blue">

                        <div class="inner">

                            <h3>{{ $total_accounts }}</h3>

                            <p>Total Accounts</p>

                        </div>

                        <div class="icon">
                            <i class="fa fa-book"></i>
                        </div>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="small-box bg-green">

                        <div class="inner">

                            <h3>{{ number_format($total_debit, 2) }}</h3>

                            <p>Total Debit</p>

                        </div>

                        <div class="icon">
                            <i class="fa fa-plus"></i>
                        </div>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="small-box bg-red">

                        <div class="inner">

                            <h3>{{ number_format($total_credit, 2) }}</h3>

                            <p>Total Credit</p>

                        </div>

                        <div class="icon">
                            <i class="fa fa-minus"></i>
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
                            <th>Total Accounts</th>
                            <th>Total Debit</th>
                            <th>Total Credit</th>
                            <th>Difference</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($grouped_accounts as $location_id => $branch_accounts)

                            @php

                                $branch_total_accounts = $branch_accounts->count();

                                $branch_debit = $branch_accounts->sum('debit');

                                $branch_credit = $branch_accounts->sum('credit');

                                $branch_difference =
                                    round($branch_debit - $branch_credit, 2);

                            @endphp

                            <tr>

                                <td>

                                    <strong>

                                        {{ $location_id ?: 'COMMON' }}

                                    </strong>

                                </td>

                                <td>

                                    {{ $branch_total_accounts }}

                                </td>

                                <td class="text-right">

                                    {{ number_format($branch_debit, 2) }}

                                </td>

                                <td class="text-right">

                                    {{ number_format($branch_credit, 2) }}

                                </td>

                                <td class="text-right">

                                    {{ number_format($branch_difference, 2) }}

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="5"
                                    class="text-center text-muted">

                                    No consolidated trial balance data available.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                    <tfoot>

                        <tr class="bg-gray">

                            <th>

                                Totals

                            </th>

                            <th>

                                {{ $total_accounts }}

                            </th>

                            <th class="text-right">

                                {{ number_format($total_debit, 2) }}

                            </th>

                            <th class="text-right">

                                {{ number_format($total_credit, 2) }}

                            </th>

                            <th class="text-right">

                                {{ number_format($difference, 2) }}

                            </th>

                        </tr>

                    </tfoot>

                </table>

            </div>

        </div>

    </div>

</section>

@endsection