@extends('layouts.app')
@section('title', 'Bank Reconciliation')

@section('content')
<section class="content-header">
    <h1>Bank Reconciliation</h1>
</section>

<section class="content main-content-inner">
    @if(session('success'))
        <div class="alert alert-success finance-bankrec-success">{{ session('success') }}</div>
    @endif

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Bank Reconciliations</h3>
            <div class="box-tools pull-right">
                @if(\Modules\Finance\Utils\FinancePermissionHelper::can('finance.bank_reconciliation.create'))
                    <a href="{{ route('finance.bank-reconciliation.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-plus"></i> New Reconciliation
                    </a>
                @endif
            </div>
        </div>
        <div class="box-body">
            <form method="GET" action="{{ route('finance.bank-reconciliation.index') }}" class="row finance-bankrec-filters">
                <div class="col-md-3">
                    <label>Bank Account</label>
                    <select name="account_id" class="form-control select2" style="width:100%">
                        <option value="">All</option>
                        @foreach($bankAccounts as $account)
                            <option value="{{ $account->id }}" {{ (string)request('account_id') === (string)$account->id ? 'selected' : '' }}>
                                {{ $account->name }}{{ $account->account_number ? ' - '.$account->account_number : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">All</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="reconciled" {{ request('status') === 'reconciled' ? 'selected' : '' }}>Reconciled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label>To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
                </div>
                <div class="col-md-3 finance-bankrec-filter-actions">
                    <button type="submit" class="btn btn-info"><i class="fa fa-search"></i> Filter</button>
                    <a href="{{ route('finance.bank-reconciliation.index') }}" class="btn btn-default">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped finance-bankrec-table">
                    <thead>
                        <tr>
                            <th>Reconciliation No</th>
                            <th>Statement Date</th>
                            <th>Bank Account</th>
                            <th class="text-right">Statement Balance</th>
                            <th class="text-right">Book Balance</th>
                            <th class="text-right">Difference</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reconciliations as $rec)
                            <tr>
                                <td>{{ $rec->reconciliation_no }}</td>
                                <td>{{ optional($rec->statement_date)->format('Y-m-d') }}</td>
                                <td>{{ optional($rec->account)->name ?: 'Account #'.$rec->account_id }}</td>
                                <td class="text-right">{{ number_format((float)$rec->statement_ending_balance, 4) }}</td>
                                <td class="text-right">{{ number_format((float)$rec->book_ending_balance, 4) }}</td>
                                <td class="text-right {{ abs((float)$rec->difference) <= 0.0001 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format((float)$rec->difference, 4) }}
                                </td>
                                <td>
                                    @if($rec->status === 'reconciled')
                                        <span class="label label-success">Reconciled</span>
                                    @else
                                        <span class="label label-warning">Draft</span>
                                    @endif
                                </td>
                                <td class="finance-bankrec-actions">
                                    <a href="{{ route('finance.bank-reconciliation.show', $rec->id) }}" class="btn btn-xs btn-info"><i class="fa fa-eye"></i> View</a>
                                    @if($rec->status === 'draft' && \Modules\Finance\Utils\FinancePermissionHelper::can('finance.bank_reconciliation.update'))
                                        <a href="{{ route('finance.bank-reconciliation.edit', $rec->id) }}" class="btn btn-xs btn-warning"><i class="fa fa-pencil"></i> Edit</a>
                                        <form method="POST" action="{{ route('finance.bank-reconciliation.destroy', $rec->id) }}" class="finance-inline-form" onsubmit="return confirm('Delete this draft bank reconciliation?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i> Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">No bank reconciliations found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="text-center">{{ $reconciliations->links() }}</div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
$(function(){
    if ($.fn.select2) $('.select2').select2();
});
</script>
<style>
.finance-bankrec-success{background:#16a34a!important;color:#fff!important;border-color:#15803d!important}
.finance-bankrec-filters{margin-bottom:18px}.finance-bankrec-filters label{display:block;color:#526a86;font-weight:700}.finance-bankrec-filter-actions{padding-top:25px}.finance-bankrec-table th{color:#526a86;text-align:center;vertical-align:middle!important}.finance-bankrec-table td{vertical-align:middle!important}.finance-bankrec-actions{white-space:nowrap}.finance-inline-form{display:inline-block;margin:0}
</style>
@endsection
