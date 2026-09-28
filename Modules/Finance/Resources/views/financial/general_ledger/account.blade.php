@extends('layouts.app')

@section('title', 'Account Ledger')

@section('content')

<section class="content-header">

    <h1>
        Account Ledger
    </h1>

</section>

<section class="content">

    @include('finance::finance_reports.partials.navigation')

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">

                {{ $account->name }}

            </h3>

        </div>

        <div class="box-body">

            <form method="GET">

                <div class="row">

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Branch</label>

                            <select name="location_id"
                                    class="form-control">

                                <option value="all">
                                    All Branches
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
                                Filter Ledger

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
                Ledger Transactions
            </h3>

        </div>

        <div class="box-body table-responsive">

            <table class="table table-bordered table-striped">

                <thead>

                    <tr>

                        <th>Date</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Running Balance</th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($transactions as $transaction)

                        <tr>

                            <td>

                                {{ @format_date($transaction->operation_date) }}

                            </td>

                            <td>

                                {{ $transaction->note }}

                            </td>

                            <td>

                                {{ ucfirst($transaction->type) }}

                            </td>

                            <td class="text-right">

                                {{ number_format($transaction->amount, 2) }}

                            </td>

                            <td class="text-right">

                                {{ number_format($transaction->running_balance, 2) }}

                            </td>

                        </tr>

                    @endforeach

                </tbody>

                <tfoot>

                    <tr>

                        <th colspan="4" class="text-right">

                            Closing Balance

                        </th>

                        <th class="text-right">

                            {{ number_format($running_balance, 2) }}

                        </th>

                    </tr>

                </tfoot>

            </table>

        </div>

    </div>

</section>

@endsection