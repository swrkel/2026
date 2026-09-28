@extends('layouts.app')

@section('title', 'Day Book')

@section('content')
<section class="content-header"><h1>Finance Reports <small>Day Book</small></h1></section>
<section class="content">
    @include('finance::finance_reports.partials.navigation')

    <form method="GET" action="{{ route('finance.reports.day_book') }}" class="finance-report-filter">
        <div class="row">
            <div class="col-md-3"><div class="form-group"><label>Location</label><select name="location_id" class="form-control select2" style="width:100%"><option value="all">All Locations</option>@foreach($locations as $id => $name)<option value="{{ $id }}" {{ (string)$location_id === (string)$id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div></div>
            <div class="col-md-3"><div class="form-group"><label>Account</label><select name="account_id" class="form-control select2" style="width:100%"><option value="">All Accounts</option>@foreach($accounts as $id => $name)<option value="{{ $id }}" {{ (int)$account_id === (int)$id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div></div>
            <div class="col-md-2"><div class="form-group"><label>From Date</label><input type="date" name="from_date" class="form-control" value="{{ $from_date }}"></div></div>
            <div class="col-md-2"><div class="form-group"><label>To Date</label><input type="date" name="to_date" class="form-control" value="{{ $to_date }}"></div></div>
            <div class="col-md-2"><div class="form-group"><label>&nbsp;</label><button class="btn btn-primary btn-block"><i class="fa fa-filter"></i> Apply</button></div></div>
        </div>
    </form>

    <div class="finance-report-summary">
        <div class="finance-report-card"><span class="label">Total Debit</span><span class="value">{{ number_format($report['total_debit'], 4) }}</span></div>
        <div class="finance-report-card"><span class="label">Total Credit</span><span class="value">{{ number_format($report['total_credit'], 4) }}</span></div>
        <div class="finance-report-card"><span class="label">Difference</span><span class="value">{{ number_format($report['total_debit'] - $report['total_credit'], 4) }}</span></div>
    </div>

    <div class="box box-primary"><div class="box-body table-responsive">
        <table class="table table-bordered table-striped finance-report-table">
            <thead><tr><th>Date</th><th>Account</th><th>Account No.</th><th>Reference</th><th>Description</th><th>Location</th><th class="text-right">Debit</th><th class="text-right">Credit</th></tr></thead>
            <tbody>
            @forelse($report['rows'] as $row)
                <tr><td>{{ $row->operation_date ? date('Y-m-d', strtotime($row->operation_date)) : '' }}</td><td>{{ $row->account_name }}</td><td>{{ $row->account_number }}</td><td>{{ $row->reference ?: ($row->cheque_number ?: $row->slip_no) }}</td><td>{{ $row->note }}</td><td>{{ $row->location_name ?: 'All' }}</td><td class="text-right">{{ $row->type === 'debit' ? number_format($row->amount, 4) : '' }}</td><td class="text-right">{{ $row->type === 'credit' ? number_format($row->amount, 4) : '' }}</td></tr>
            @empty
                <tr><td colspan="8" class="text-center">No day-book entries found.</td></tr>
            @endforelse
            </tbody>
        </table>
        @include('finance::finance_reports.partials.pagination', ['paginator' => $report['rows']])
    </div></div>
</section>
@endsection
