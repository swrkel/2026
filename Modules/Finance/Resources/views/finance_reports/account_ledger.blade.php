@extends('layouts.app')

@section('title', 'Account Ledger')

@section('content')
<section class="content-header"><h1>Finance Reports <small>Account Ledger</small></h1></section>
<section class="content">
    @include('finance::finance_reports.partials.navigation')

    <form method="GET" action="{{ route('finance.reports.account_ledger') }}" class="finance-report-filter">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Account</label>
                    <select name="account_id" class="form-control select2" style="width:100%" required>
                        <option value="">Please Select</option>
                        @foreach($accounts as $id => $name)
                            <option value="{{ $id }}" {{ (int)$account_id === (int)$id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Location</label>
                    <select name="location_id" class="form-control select2" style="width:100%">
                        <option value="all">All Locations</option>
                        @foreach($locations as $id => $name)
                            <option value="{{ $id }}" {{ (string)$location_id === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2"><div class="form-group"><label>From Date</label><input type="date" name="from_date" class="form-control" value="{{ $from_date }}"></div></div>
            <div class="col-md-2"><div class="form-group"><label>To Date</label><input type="date" name="to_date" class="form-control" value="{{ $to_date }}"></div></div>
            <div class="col-md-2"><div class="form-group"><label>&nbsp;</label><button class="btn btn-primary btn-block" type="submit"><i class="fa fa-search"></i> Show</button></div></div>
        </div>
    </form>

    @if($ledger)
        <div class="finance-report-summary">
            <div class="finance-report-card"><span class="label">Account</span><span class="value" style="font-size:17px">{{ $ledger['account']->name }}</span></div>
            <div class="finance-report-card"><span class="label">Opening Balance</span><span class="value">{{ number_format($ledger['opening_balance'], 4) }}</span></div>
            <div class="finance-report-card"><span class="label">Total Debit</span><span class="value">{{ number_format($ledger['total_debit'], 4) }}</span></div>
            <div class="finance-report-card"><span class="label">Total Credit</span><span class="value">{{ number_format($ledger['total_credit'], 4) }}</span></div>
            <div class="finance-report-card"><span class="label">Closing Balance</span><span class="value">{{ number_format($ledger['closing_balance'], 4) }}</span></div>
        </div>

        <div class="box box-primary">
            <div class="box-body table-responsive">
                <table class="table table-bordered table-striped finance-report-table">
                    <thead><tr><th>Date</th><th>Reference</th><th>Description</th><th>Location</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th class="text-right">Running Balance</th></tr></thead>
                    <tbody>
                    @forelse($ledger['rows'] as $row)
                        <tr>
                            <td>{{ $row->operation_date ? date('Y-m-d', strtotime($row->operation_date)) : '' }}</td>
                            <td>{{ $row->reference ?: ($row->cheque_number ?: $row->slip_no) }}</td>
                            <td>{{ $row->note }}</td>
                            <td>{{ $row->location_name ?: 'All' }}</td>
                            <td class="text-right">{{ $row->type === 'debit' ? number_format($row->amount, 4) : '' }}</td>
                            <td class="text-right">{{ $row->type === 'credit' ? number_format($row->amount, 4) : '' }}</td>
                            <td class="text-right"><strong>{{ number_format($row->running_balance, 4) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No transactions found for the selected period.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                @include('finance::finance_reports.partials.pagination', ['paginator' => $ledger['rows']])
            </div>
        </div>
    @else
        <div class="finance-report-empty"><i class="fa fa-info-circle"></i> Select an account and click <strong>Show</strong>.</div>
    @endif
</section>
@endsection
