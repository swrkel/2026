@extends('layouts.app')
@section('title', 'Bank Reconciliation '.$reconciliation->reconciliation_no)

@section('content')
<section class="content-header finance-bankrec-print-hide">
    <h1>Bank Reconciliation <small>{{ $reconciliation->reconciliation_no }}</small></h1>
</section>

<section class="content main-content-inner">
    @if(session('success'))
        <div class="alert alert-success finance-bankrec-success finance-bankrec-print-hide">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger finance-bankrec-print-hide">{{ session('error') }}</div>
    @endif

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Reconciliation Summary</h3>
            <div class="box-tools pull-right finance-bankrec-print-hide">
                <a href="{{ route('finance.bank-reconciliation.index') }}" class="btn btn-default btn-sm"><i class="fa fa-list"></i> List</a>
                <button type="button" onclick="window.print()" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print</button>
                @if($reconciliation->status === 'draft' && \Modules\Finance\Utils\FinancePermissionHelper::can('finance.bank_reconciliation.update'))
                    <a href="{{ route('finance.bank-reconciliation.edit', $reconciliation->id) }}" class="btn btn-warning btn-sm"><i class="fa fa-pencil"></i> Edit Draft</a>
                @endif
            </div>
        </div>
        <div class="box-body">
            <div class="row finance-bankrec-meta">
                <div class="col-md-3"><span>Reconciliation No</span><strong>{{ $reconciliation->reconciliation_no }}</strong></div>
                <div class="col-md-3"><span>Bank Account</span><strong>{{ optional($reconciliation->account)->name ?: 'Account #'.$reconciliation->account_id }}</strong></div>
                <div class="col-md-3"><span>Statement Date</span><strong>{{ optional($reconciliation->statement_date)->format('Y-m-d') }}</strong></div>
                <div class="col-md-3"><span>Status</span><strong>{{ $reconciliation->status === 'reconciled' ? 'Reconciled' : 'Draft' }}</strong></div>
            </div>

            <div class="row finance-bankrec-summary">
                <div class="col-md-2"><div class="finance-value"><span>Statement Balance</span><strong>{{ number_format((float)$reconciliation->statement_ending_balance,4) }}</strong></div></div>
                <div class="col-md-2"><div class="finance-value"><span>Outstanding Deposits</span><strong>{{ number_format((float)$reconciliation->outstanding_deposits,4) }}</strong></div></div>
                <div class="col-md-2"><div class="finance-value"><span>Outstanding Payments</span><strong>{{ number_format((float)$reconciliation->outstanding_payments,4) }}</strong></div></div>
                <div class="col-md-2"><div class="finance-value"><span>Adjusted Bank</span><strong>{{ number_format((float)$reconciliation->adjusted_bank_balance,4) }}</strong></div></div>
                <div class="col-md-2"><div class="finance-value"><span>Book Balance</span><strong>{{ number_format((float)$reconciliation->book_ending_balance,4) }}</strong></div></div>
                <div class="col-md-2"><div class="finance-value {{ abs((float)$reconciliation->difference) <= 0.0001 ? 'is-zero' : 'has-difference' }}"><span>Difference</span><strong>{{ number_format((float)$reconciliation->difference,4) }}</strong></div></div>
            </div>

            @if($reconciliation->notes)
                <div class="well well-sm"><strong>Note:</strong> {{ $reconciliation->notes }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-striped finance-bankrec-lines">
                    <thead><tr><th>Cleared</th><th>Date</th><th>Reference</th><th>Description</th><th class="text-right">Deposit / Debit</th><th class="text-right">Payment / Credit</th></tr></thead>
                    <tbody>
                    @forelse($reconciliation->lines as $line)
                        <tr>
                            <td class="text-center">@if($line->is_cleared)<span class="label label-success">Yes</span>@else<span class="label label-default">Outstanding</span>@endif</td>
                            <td>{{ optional($line->transaction_date)->format('Y-m-d') }}</td>
                            <td>{{ $line->reference }}</td>
                            <td>{{ $line->description }}</td>
                            <td class="text-right">{{ $line->transaction_type === 'debit' ? number_format((float)$line->amount,4) : '0.0000' }}</td>
                            <td class="text-right">{{ $line->transaction_type === 'credit' ? number_format((float)$line->amount,4) : '0.0000' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No bank transactions were included in this reconciliation.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="box-footer text-right finance-bankrec-print-hide">
            @if($reconciliation->status === 'draft' && \Modules\Finance\Utils\FinancePermissionHelper::can('finance.bank_reconciliation.finalize'))
                @if(abs((float)$reconciliation->difference) <= 0.0001)
                    <form method="POST" action="{{ route('finance.bank-reconciliation.finalize', $reconciliation->id) }}" style="display:inline" onsubmit="return confirm('Finalize this bank reconciliation? Cleared transactions will be marked reconciled.');">
                        @csrf
                        <button class="btn btn-success"><i class="fa fa-check"></i> Finalize Reconciliation</button>
                    </form>
                @else
                    <button class="btn btn-success" disabled title="Difference must be 0.0000"><i class="fa fa-check"></i> Finalize Reconciliation</button>
                @endif
            @elseif($reconciliation->status === 'reconciled' && \Modules\Finance\Utils\FinancePermissionHelper::can('finance.bank_reconciliation.reopen'))
                <form method="POST" action="{{ route('finance.bank-reconciliation.reopen', $reconciliation->id) }}" style="display:inline" onsubmit="return confirm('Reopen this reconciliation? Its cleared transactions will become unreconciled again.');">
                    @csrf
                    <button class="btn btn-warning"><i class="fa fa-undo"></i> Reopen Reconciliation</button>
                </form>
            @endif
        </div>
    </div>
</section>
@endsection

@section('javascript')
<style>
.finance-bankrec-success{background:#16a34a!important;color:#fff!important;border-color:#15803d!important}.finance-bankrec-meta{margin-bottom:18px}.finance-bankrec-meta span,.finance-value span{display:block;color:#526a86;font-size:12px;font-weight:700}.finance-bankrec-meta strong{display:block;margin-top:4px;font-size:15px}.finance-bankrec-summary{margin-bottom:18px}.finance-value{min-height:88px;padding:13px;background:#f8fbff;border:1px solid #dbe7f3;border-radius:9px}.finance-value strong{display:block;text-align:right;margin-top:12px;font-size:17px}.finance-value.is-zero{background:#ecfdf3;border-color:#86efac}.finance-value.has-difference{background:#fff1f2;border-color:#fda4af}.finance-bankrec-lines th{color:#526a86;text-align:center}.finance-bankrec-lines td{vertical-align:middle!important}
@media print{.finance-bankrec-print-hide,.main-header,.main-sidebar,.control-sidebar,.content-header{display:none!important}.content-wrapper{margin-left:0!important}.box{border:0!important;box-shadow:none!important}.finance-bankrec-lines{font-size:11px}.finance-value{border:1px solid #aaa!important}}
</style>
@endsection
