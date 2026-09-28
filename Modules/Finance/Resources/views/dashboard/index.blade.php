@extends('layouts.app')

@section('title', 'Finance Dashboard')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')

<section class="content-header">
    <h1>
        Finance Dashboard
        <small>Enterprise Financial Control Center</small>
    </h1>
</section>

<section class="content">

    <div class="enterprise-panel">

        <div class="enterprise-panel-title">
            <i class="fa fa-filter"></i>
            Dashboard Filter
        </div>

        <form method="GET" action="{{ route('finance.dashboard') }}">

            <div class="row">

                <div class="col-md-4">

                    <div class="form-group">
                        <label>Branch</label>

                        <select name="location_id" class="form-control">

                            <option value="all">
                                All Branches / Consolidated
                            </option>

                            @foreach($locations as $id => $name)

                                <option value="{{ $id }}"
                                    {{ $location_id == $id ? 'selected' : '' }}>

                                    {{ $name }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="form-group">

                        <label>&nbsp;</label>

                        <button type="submit"
                                class="btn btn-primary btn-block">

                            <i class="fa fa-filter"></i>
                            Apply Filter

                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>

    {{-- ROW 1 --}}

    <div class="row">

        <div class="col-md-4">

            <div class="enterprise-card enterprise-blue">

                <div class="icon text-primary">
                    <i class="fa fa-money"></i>
                </div>

                <div class="title">
                    Total Assets
                </div>

                <div class="value">
                    {{ number_format($asset_total, 2) }}
                </div>

                <div class="subtext">
                    Consolidated enterprise assets
                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="enterprise-card enterprise-red">

                <div class="icon text-danger">
                    <i class="fa fa-credit-card"></i>
                </div>

                <div class="title">
                    Total Liabilities
                </div>

                <div class="value">
                    {{ number_format($liability_total, 2) }}
                </div>

                <div class="subtext">
                    Current liability exposure
                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="enterprise-card enterprise-green">

                <div class="icon text-success">
                    <i class="fa fa-bank"></i>
                </div>

                <div class="title">
                    Total Equity
                </div>

                <div class="value">
                    {{ number_format($equity_total, 2) }}
                </div>

                <div class="subtext">
                    Shareholder equity position
                </div>

            </div>

        </div>

    </div>

    {{-- ROW 2 --}}

    <div class="row">

        <div class="col-md-4">

            <div class="enterprise-card enterprise-purple">

                <div class="icon" style="color:#8e44ad;">
                    <i class="fa fa-line-chart"></i>
                </div>

                <div class="title">
                    Net Profit / Loss
                </div>

                <div class="value">
                    {{ number_format($net_profit, 2) }}
                </div>

                <div class="subtext">
                    Current profitability position
                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="enterprise-card enterprise-yellow">

                <div class="icon text-warning">
                    <i class="fa fa-shopping-cart"></i>
                </div>

                <div class="title">
                    Sales Revenue
                </div>

                <div class="value">
                    {{ number_format($total_sales, 2) }}
                </div>

                <div class="subtext">
                    Consolidated sales revenue
                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="enterprise-card enterprise-red">

                <div class="icon text-danger">
                    <i class="fa fa-file-text-o"></i>
                </div>

                <div class="title">
                    Expenses
                </div>

                <div class="value">
                    {{ number_format($total_expenses, 2) }}
                </div>

                <div class="subtext">
                    Enterprise operational expenses
                </div>

            </div>

        </div>

    </div>

    {{-- ROW 3 --}}

    <div class="row">

        <div class="col-md-4">

            <div class="enterprise-card enterprise-green">

                <div class="icon text-success">
                    <i class="fa fa-users"></i>
                </div>

                <div class="title">
                    Customer Receipts
                </div>

                <div class="value">
                    {{ number_format($customer_receipts, 2) }}
                </div>

                <div class="subtext">
                    Customer collection inflows
                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="enterprise-card enterprise-purple">

                <div class="icon" style="color:#8e44ad;">
                    <i class="fa fa-truck"></i>
                </div>

                <div class="title">
                    Purchases
                </div>

                <div class="value">
                    {{ number_format($total_purchases, 2) }}
                </div>

                <div class="subtext">
                    Enterprise procurement activity
                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="enterprise-card enterprise-gray">

                <div class="icon text-muted">
                    <i class="fa fa-credit-card"></i>
                </div>

                <div class="title">
                    Supplier Payments
                </div>

                <div class="value">
                    {{ number_format($supplier_payments, 2) }}
                </div>

                <div class="subtext">
                    Supplier payment outflows
                </div>

            </div>

        </div>

    </div>

    {{-- ROW 4 --}}

    <div class="row">

        <div class="col-md-4">

            <div class="enterprise-card enterprise-green">

                <div class="icon text-success">
                    <i class="fa fa-arrow-down"></i>
                </div>

                <div class="title">
                    Treasury Cash In
                </div>

                <div class="value">
                    {{ number_format($treasury_cash_in, 2) }}
                </div>

                <div class="subtext">
                    Treasury inflows
                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="enterprise-card enterprise-red">

                <div class="icon text-danger">
                    <i class="fa fa-arrow-up"></i>
                </div>

                <div class="title">
                    Treasury Cash Out
                </div>

                <div class="value">
                    {{ number_format($treasury_cash_out, 2) }}
                </div>

                <div class="subtext">
                    Treasury outflows
                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="enterprise-card enterprise-yellow">

                <div class="icon text-warning">
                    <i class="fa fa-balance-scale"></i>
                </div>

                <div class="title">
                    Treasury Net Position
                </div>

                <div class="value">
                    {{ number_format($treasury_net_position, 2) }}
                </div>

                <div class="subtext">
                    Treasury liquidity overview
                </div>

            </div>

        </div>

    </div>

    {{-- ROW 5 --}}

    <div class="row">

        <div class="col-md-4">

            <div class="enterprise-card enterprise-yellow">

                <div class="icon text-warning">
                    <i class="fa fa-hourglass-half"></i>
                </div>

                <div class="title">
                    Receivables Exposure
                </div>

                <div class="value">
                    {{ number_format($total_receivables, 2) }}
                </div>

                <div class="subtext">
                    Outstanding receivables
                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="enterprise-card enterprise-red">

                <div class="icon text-danger">
                    <i class="fa fa-exclamation-circle"></i>
                </div>

                <div class="title">
                    Payables Exposure
                </div>

                <div class="value">
                    {{ number_format($total_payables, 2) }}
                </div>

                <div class="subtext">
                    Pending supplier obligations
                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="enterprise-card enterprise-blue">

                <div class="icon text-primary">
                    <i class="fa fa-bell"></i>
                </div>

                <div class="title">
                    Open Escalations
                </div>

                <div class="value">
                    {{ number_format($open_escalations) }}
                </div>

                <div class="subtext">
                    Governance alerts pending
                </div>

            </div>

        </div>

    </div>

    <div class="row">

        <div class="col-md-6">

            <div class="enterprise-panel">

                <div class="enterprise-panel-title">
                    <i class="fa fa-line-chart"></i>
                    Income vs Expenses
                </div>

                <table class="table table-bordered enterprise-table">

                    <tr>
                        <th>Total Income</th>
                        <td class="text-right">
                            {{ number_format($income_total, 2) }}
                        </td>
                    </tr>

                    <tr>
                        <th>Total Expenses</th>
                        <td class="text-right">
                            {{ number_format($expense_total, 2) }}
                        </td>
                    </tr>

                    <tr>
                        <th>Net Profit / Loss</th>
                        <td class="text-right">
                            <strong>
                                {{ number_format($net_profit, 2) }}
                            </strong>
                        </td>
                    </tr>

                </table>

            </div>

        </div>

        <div class="col-md-6">

            <div class="enterprise-panel">

                <div class="enterprise-panel-title">
                    <i class="fa fa-balance-scale"></i>
                    Balance Sheet Control Check
                </div>

                <table class="table table-bordered enterprise-table">

                    <tr>
                        <th>Assets</th>
                        <td class="text-right">
                            {{ number_format($asset_total, 2) }}
                        </td>
                    </tr>

                    <tr>
                        <th>Liabilities + Equity</th>
                        <td class="text-right">
                            {{ number_format($liability_total + $equity_total, 2) }}
                        </td>
                    </tr>

                    <tr>
                        <th>Difference</th>
                        <td class="text-right">
                            <strong>
                                {{ number_format($balance_difference, 2) }}
                            </strong>
                        </td>
                    </tr>

                </table>

            </div>

        </div>

    </div>

</section>

@endsection