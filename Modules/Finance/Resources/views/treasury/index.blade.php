@extends('layouts.app')

@section('title', 'Treasury Dashboard')

@section('content')

<section class="content-header">
    <h1>
        Treasury Dashboard
    </h1>
</section>

<section class="content">

    <div class="row">

        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-green">
                    <i class="fa fa-arrow-down"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">Total Cash In</span>
                    <span class="info-box-number">
                        {{ number_format($total_cash_in, 2) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-red">
                    <i class="fa fa-arrow-up"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">Total Cash Out</span>
                    <span class="info-box-number">
                        {{ number_format($total_cash_out, 2) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-aqua">
                    <i class="fa fa-balance-scale"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">Net Cash Position</span>
                    <span class="info-box-number">
                        {{ number_format($net_cash_position, 2) }}
                    </span>
                </div>
            </div>
        </div>

    </div>

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">
                Treasury Filters
            </h3>

            <div class="box-tools pull-right">

                <a href="{{ route('finance.treasury.create') }}"
                   class="btn btn-primary btn-sm">

                    <i class="fa fa-plus"></i>
                    Add Treasury Transaction

                </a>

            </div>

        </div>

        <div class="box-body">

            <form method="GET">

                <div class="row">

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Branch</label>

                            <select name="location_id" class="form-control">
                                <option value="all">All Branches</option>

                                @foreach($locations as $id => $name)
                                    <option value="{{ $id }}"
                                        {{ request()->location_id == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Account</label>

                            <select name="account_id" class="form-control">
                                <option value="">All Accounts</option>

                                @foreach($accounts as $id => $name)
                                    <option value="{{ $id }}"
                                        {{ request()->account_id == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Type</label>

                            <select name="treasury_type" class="form-control">
                                <option value="">All Types</option>
                                <option value="cash_in">Cash In</option>
                                <option value="cash_out">Cash Out</option>
                                <option value="bank_deposit">Bank Deposit</option>
                                <option value="bank_withdrawal">Bank Withdrawal</option>
                                <option value="transfer">Transfer</option>
                                <option value="adjustment">Adjustment</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>From Date</label>

                            <input type="date"
                                   name="from_date"
                                   class="form-control"
                                   value="{{ request()->from_date }}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>To Date</label>

                            <input type="date"
                                   name="to_date"
                                   class="form-control"
                                   value="{{ request()->to_date }}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fa fa-search"></i>
                                Filter
                            </button>
                        </div>
                    </div>

                </div>

            </form>

        </div>

    </div>

    <div class="box box-success">

        <div class="box-header with-border">
            <h3 class="box-title">
                Treasury Transaction Register
            </h3>
        </div>

        <div class="box-body table-responsive">

            <table class="table table-bordered table-striped">

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Date</th>
                        <th>Branch</th>
                        <th>Account</th>
                        <th>Type</th>
                        <th class="text-right">Amount</th>
                        <th>Created By</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_no }}</td>
                            <td>{{ $transaction->transaction_date }}</td>
                            <td>{{ optional($transaction->location)->name }}</td>
                            <td>{{ optional($transaction->account)->name }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $transaction->treasury_type)) }}</td>
                            <td class="text-right">
                                {{ number_format($transaction->amount, 2) }}
                            </td>
                            <td>{{ optional($transaction->createdBy)->username }}</td>
                        </tr>
                    @endforeach
                </tbody>

            </table>

            <div class="text-center">
                {{ $transactions->links() }}
            </div>

        </div>

    </div>

</section>

@endsection