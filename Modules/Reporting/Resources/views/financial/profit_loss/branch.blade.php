@extends('layouts.app')

@section('title', __('Branch Wise Profit & Loss'))

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

@endphp

<section class="content-header">

    <h1>
        Branch Wise Profit & Loss
        <small>Multi-Branch Financial Performance</small>
    </h1>

</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">

                <i class="fa fa-building"></i>

                Branch Profit & Loss Report

            </h3>

        </div>

        <div class="box-body">

            <form method="GET"
                  action="{{ route('reporting.branch.pnl') }}">

                <div class="row">

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Branch</label>

                            <select name="location_id"
                                    class="form-control select2"
                                    style="width: 100%;"
                                    required>

                                <option value="">Select Branch</option>

                                @foreach($locations as $location_id => $location_name)

                                    <option value="{{ $location_id }}"
                                        @if($selected_location_id == $location_id) selected @endif>

                                        {{ $location_name }} - {{ $location_id }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>From Date</label>

                            <input type="text"
                                   name="from_date"
                                   class="form-control datepicker"
                                   value="{{ $from_date ?? '' }}"
                                   placeholder="From Date">

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>To Date</label>

                            <input type="text"
                                   name="to_date"
                                   class="form-control datepicker"
                                   value="{{ $to_date ?? '' }}"
                                   placeholder="To Date">

                        </div>

                    </div>

                    <div class="col-md-3">

                        <label>&nbsp;</label>

                        <button type="submit"
                                class="btn btn-primary btn-block">

                            <i class="fa fa-search"></i>

                            Generate Report

                        </button>

                    </div>

                </div>

            </form>

            <hr>

            <div class="row">

                <div class="col-md-4">

                    <div class="info-box">

                        <span class="info-box-icon bg-green">

                            <i class="fa fa-arrow-up"></i>

                        </span>

                        <div class="info-box-content">

                            <span class="info-box-text">

                                Total Income

                            </span>

                            <span class="info-box-number">

                                {{ number_format($total_income, 2) }}

                            </span>

                        </div>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="info-box">

                        <span class="info-box-icon bg-red">

                            <i class="fa fa-arrow-down"></i>

                        </span>

                        <div class="info-box-content">

                            <span class="info-box-text">

                                Total Expenses

                            </span>

                            <span class="info-box-number">

                                {{ number_format($total_expense, 2) }}

                            </span>

                        </div>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="info-box">

                        <span class="info-box-icon bg-aqua">

                            <i class="fa fa-line-chart"></i>

                        </span>

                        <div class="info-box-content">

                            <span class="info-box-text">

                                Net Profit / Loss

                            </span>

                            <span class="info-box-number">

                                {{ number_format($net_profit, 2) }}

                            </span>

                        </div>

                    </div>

                </div>

            </div>

            @if(empty($selected_location_id))

                <div class="alert alert-info">

                    <i class="fa fa-info-circle"></i>

                    Please select a branch to generate the Profit & Loss report.

                </div>

            @endif

            <div class="row">

                <div class="col-md-6">

                    <div class="box box-success">

                        <div class="box-header with-border">

                            <h3 class="box-title">

                                Income Accounts

                            </h3>

                        </div>

                        <div class="box-body table-responsive no-padding">

                            <table class="table table-bordered table-striped">

                                <thead>

                                    <tr class="bg-green">

                                        <th>Code</th>
                                        <th>Account</th>
                                        <th>Location</th>

                                        <th class="text-right">
                                            Amount
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    @if(
                                        !empty($selected_location_id)
                                        && isset($income_accounts)
                                        && $income_accounts->count() > 0
                                    )

                                        @foreach($income_accounts as $account)

                                            <tr>

                                                <td>
                                                    {{ $account->account_number ?? '-' }}
                                                </td>

                                                <td>
                                                    {{ $account->name }}
                                                </td>

                                                <td>
                                                    {{ $account->location_id }}
                                                </td>

                                                <td class="text-right">

                                                    {{ number_format($account->balance, 2) }}

                                                </td>

                                            </tr>

                                        @endforeach

                                    @else

                                        <tr>

                                            <td colspan="4"
                                                class="text-center text-muted">

                                                No income data available.

                                            </td>

                                        </tr>

                                    @endif

                                </tbody>

                                <tfoot>

                                    <tr>

                                        <th colspan="3"
                                            class="text-right">

                                            Total Income

                                        </th>

                                        <th class="text-right">

                                            {{ number_format($total_income, 2) }}

                                        </th>

                                    </tr>

                                </tfoot>

                            </table>

                        </div>

                    </div>

                </div>

                <div class="col-md-6">

                    <div class="box box-danger">

                        <div class="box-header with-border">

                            <h3 class="box-title">

                                Expense Accounts

                            </h3>

                        </div>

                        <div class="box-body table-responsive no-padding">

                            <table class="table table-bordered table-striped">

                                <thead>

                                    <tr class="bg-red">

                                        <th>Code</th>
                                        <th>Account</th>
                                        <th>Location</th>

                                        <th class="text-right">
                                            Amount
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    @if(
                                        !empty($selected_location_id)
                                        && isset($expense_accounts)
                                        && $expense_accounts->count() > 0
                                    )

                                        @foreach($expense_accounts as $account)

                                            <tr>

                                                <td>
                                                    {{ $account->account_number ?? '-' }}
                                                </td>

                                                <td>
                                                    {{ $account->name }}
                                                </td>

                                                <td>
                                                    {{ $account->location_id }}
                                                </td>

                                                <td class="text-right">

                                                    {{ number_format($account->balance, 2) }}

                                                </td>

                                            </tr>

                                        @endforeach

                                    @else

                                        <tr>

                                            <td colspan="4"
                                                class="text-center text-muted">

                                                No expense data available.

                                            </td>

                                        </tr>

                                    @endif

                                </tbody>

                                <tfoot>

                                    <tr>

                                        <th colspan="3"
                                            class="text-right">

                                            Total Expenses

                                        </th>

                                        <th class="text-right">

                                            {{ number_format($total_expense, 2) }}

                                        </th>

                                    </tr>

                                </tfoot>

                            </table>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

@endsection