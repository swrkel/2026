@extends('layouts.app')

@section('title', $report_title ?? 'Profit & Loss')

@section('content')

<section class="content-header">
    <h1>
        {{ $report_title ?? 'Profit & Loss' }}
    </h1>
</section>

<section class="content">

    @include('finance::finance_reports.partials.navigation')

    <div class="box box-primary">

        <div class="box-header with-border">
            <h3 class="box-title">
                Branch Wise / Consolidated {{ $report_title ?? 'Profit & Loss' }}
            </h3>
        </div>

        <div class="box-body">

            <form method="GET"
                  action="{{ url()->current() }}">

                <div class="row">

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Branch</label>

                            <select name="location_id"
                                    class="form-control">

                                <option value="all" {{ (string)$selected_location_id === 'all' ? 'selected' : '' }}>
                                    All Locations / Consolidated
                                </option>

                                @foreach($locations as $id => $name)

                                    <option value="{{ $id }}"
                                        {{ $selected_location_id == $id ? 'selected' : '' }}>

                                        {{ $name }}

                                    </option>

                                @endforeach

                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">

                            <label>From Date</label>

                            <input type="date"
                                   name="from_date"
                                   class="form-control"
                                   value="{{ $from_date }}">

                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">

                            <label>To Date</label>

                            <input type="date"
                                   name="to_date"
                                   class="form-control"
                                   value="{{ $to_date }}">

                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">

                            <label>&nbsp;</label>

                            <button type="submit"
                                    class="btn btn-primary btn-block">

                                <i class="fa fa-search"></i>
                                Generate Report

                            </button>

                        </div>
                    </div>

                </div>

            </form>

        </div>

    </div>

    <div class="row">

        <div class="col-md-6">

            <div class="box box-success">

                <div class="box-header with-border">

                    <h3 class="box-title">
                        Income
                    </h3>

                </div>

                <div class="box-body table-responsive">

                    <table class="table table-bordered table-striped">

                        <thead>

                            <tr>

                                <th>Account</th>
                                <th class="text-right">Amount</th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach($income_accounts as $account)

                                <tr>

                                    <td>
                                        {{ $account->name }}
                                    </td>

                                    <td class="text-right">

                                        {{ number_format($account->balance, 4) }}

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                        <tfoot>

                            <tr>

                                <th>Total Income</th>

                                <th class="text-right">

                                    {{ number_format($total_income, 4) }}

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
                        Expenses
                    </h3>

                </div>

                <div class="box-body table-responsive">

                    <table class="table table-bordered table-striped">

                        <thead>

                            <tr>

                                <th>Account</th>
                                <th class="text-right">Amount</th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach($expense_accounts as $account)

                                <tr>

                                    <td>
                                        {{ $account->name }}
                                    </td>

                                    <td class="text-right">

                                        {{ number_format($account->balance, 4) }}

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                        <tfoot>

                            <tr>

                                <th>Total Expense</th>

                                <th class="text-right">

                                    {{ number_format($total_expense, 4) }}

                                </th>

                            </tr>

                        </tfoot>

                    </table>

                </div>

            </div>

        </div>

    </div>

    <div class="row">

        <div class="col-md-12">

            <div class="box box-info">

                <div class="box-header with-border">

                    <h3 class="box-title">
                        Net Profit / Loss
                    </h3>

                </div>

                <div class="box-body">

                    <h2 class="text-center">

                        {{ number_format($net_profit, 4) }}

                    </h2>

                </div>

            </div>

        </div>

    </div>

</section>

@endsection