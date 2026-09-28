@extends('layouts.app')
@section('title', 'Deposit Statement')
@section('content')
<section class="content-header no-print">
    <h1>Deposit Statement <small>{{ $account->account_no }}</small></h1>
</section>
<section class="content">
@include('deposits::layouts.nav')
<div class="box box-primary no-print">
    <div class="box-header with-border"><h3 class="box-title">Statement Filters</h3></div>
    <div class="box-body">
        <form method="GET" class="row">
            <div class="col-md-3 form-group"><label>From Date</label><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
            <div class="col-md-3 form-group"><label>To Date</label><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
            <div class="col-md-3 form-group"><label>&nbsp;</label><button class="btn btn-primary btn-block"><i class="fa fa-search"></i> View Statement</button></div>
            <div class="col-md-3 form-group"><label>&nbsp;</label><a class="btn btn-success btn-block" href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}"><i class="fa fa-download"></i> CSV</a></div>
        </form>
    </div>
</div>
<div class="box box-solid">
    <div class="box-header with-border">
        <h3 class="box-title">Statement</h3>
        <div class="box-tools no-print">
            <button onclick="window.print()" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print</button>
            <a href="{{ route('deposits.accounts.show', $account->id) }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-xs-6">
                <strong>Account No:</strong> {{ $account->account_no }}<br>
                <strong>Customer:</strong> {{ $account->customer_name ?: '-' }}<br>
                <strong>Product:</strong> {{ optional($account->product)->name ?: '-' }}
            </div>
            <div class="col-xs-6 text-right">
                <strong>Opened:</strong> {{ $account->opened_on }}<br>
                <strong>Maturity:</strong> {{ $account->maturity_on ?: '-' }}<br>
                <strong>Status:</strong> {{ ucfirst($account->status) }}
            </div>
        </div>
        <hr>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transaction No</th>
                        <th>Type</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Balance After</th>
                        <th>Reference</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                        <tr>
                            <td>{{ $tx->transaction_date }}</td>
                            <td>{{ $tx->transaction_no }}</td>
                            <td>{{ ucfirst($tx->type) }}</td>
                            <td class="text-right">{{ number_format($tx->amount, 2) }}</td>
                            <td class="text-right">{{ number_format($tx->balance_after, 2) }}</td>
                            <td>{{ $tx->reference_no }}</td>
                            <td>{{ $tx->notes }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No transactions found</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr><th colspan="3" class="text-right">Total Credits</th><th class="text-right">{{ number_format($totals['credits'], 2) }}</th><th colspan="3"></th></tr>
                    <tr><th colspan="3" class="text-right">Total Debits</th><th class="text-right">{{ number_format($totals['debits'], 2) }}</th><th colspan="3"></th></tr>
                    <tr><th colspan="3" class="text-right">Closing Balance</th><th class="text-right">{{ number_format($totals['closing_balance'] ?? $account->current_balance, 2) }}</th><th colspan="3"></th></tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
</section>
@endsection
