@extends('layouts.app')

@section('title', 'Consolidated Profit & Loss')

@section('content')

<section class="content-header">
    <h1>
        Consolidated Profit & Loss
    </h1>
</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">
                Consolidated Financial Performance
            </h3>

        </div>

        <div class="box-body">

            <form method="GET"
                  action="{{ route('finance.profit_loss.consolidated') }}">

                <div class="row">

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>From Date</label>

                            <input type="date"
                                   name="from_date"
                                   class="form-control"
                                   value="{{ $from_date }}">

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>To Date</label>

                            <input type="date"
                                   name="to_date"
                                   class="form-control"
                                   value="{{ $to_date }}">

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>&nbsp;</label>

                            <button type="submit"
                                    class="btn btn-primary btn-block">

                                <i class="fa fa-search"></i>
                                Generate Consolidated Report

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
                        Consolidated Income
                    </h3>

                </div>

                <div class="box-body table-responsive">

                    <table class="table table-bordered table-striped">

                        <thead>

                            <tr>

                                <th>Account</th>
                                <th>Branch</th>
                                <th class="text-right">Amount</th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach($income_accounts as $account)

                                <tr>

                                    <td>

                                        {{ $account->name }}

                                    </td>

                                    <td>

                                        {{ $account->location_name ?: 'All / Unassigned' }}

                                    </td>

                                    <td class="text-right">

                                        {{ number_format($account->balance, 2) }}

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                        <tfoot>

                            <tr>

                                <th colspan="2">

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
                        Consolidated Expenses
                    </h3>

                </div>

                <div class="box-body table-responsive">

                    <table class="table table-bordered table-striped">

                        <thead>

                            <tr>

                                <th>Account</th>
                                <th>Branch</th>
                                <th class="text-right">Amount</th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach($expense_accounts as $account)

                                <tr>

                                    <td>

                                        {{ $account->name }}

                                    </td>

                                    <td>

                                        {{ $account->location_name ?: 'All / Unassigned' }}

                                    </td>

                                    <td class="text-right">

                                        {{ number_format($account->balance, 2) }}

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                        <tfoot>

                            <tr>

                                <th colspan="2">

                                    Total Expense

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

    <div class="row">

        <div class="col-md-12">

            <div class="box box-info">

                <div class="box-header with-border">

                    <h3 class="box-title">
                        Consolidated Net Profit / Loss
                    </h3>

                </div>

                <div class="box-body">

                    <h2 class="text-center">

                        {{ number_format($net_profit, 2) }}

                    </h2>

                </div>

            </div>

        </div>

    </div>

</section>

@endsection