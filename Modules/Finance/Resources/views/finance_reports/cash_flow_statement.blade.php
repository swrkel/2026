@extends('layouts.app')

@section('title', 'Cash Flow Statement')

@section('content')
<section class="content-header"><h1>Finance Reports <small>Cash Flow Statement</small></h1></section>
<section class="content">
    @include('finance::finance_reports.partials.navigation')

    <form method="GET" action="{{ route('finance.reports.cash_flow_statement') }}" class="finance-report-filter">
        <div class="row">
            <div class="col-md-4"><div class="form-group"><label>Location</label><select name="location_id" class="form-control select2" style="width:100%"><option value="all">All Locations</option>@foreach($locations as $id => $name)<option value="{{ $id }}" {{ (string)$location_id === (string)$id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div></div>
            <div class="col-md-3"><div class="form-group"><label>From Date</label><input type="date" name="from_date" class="form-control" value="{{ $from_date }}"></div></div>
            <div class="col-md-3"><div class="form-group"><label>To Date</label><input type="date" name="to_date" class="form-control" value="{{ $to_date }}"></div></div>
            <div class="col-md-2"><div class="form-group"><label>&nbsp;</label><button class="btn btn-primary btn-block"><i class="fa fa-filter"></i> Apply</button></div></div>
        </div>
    </form>

    <div class="finance-report-summary">
        <div class="finance-report-card"><span class="label">Opening Cash</span><span class="value">{{ number_format($report['opening_balance'], 4) }}</span></div>
        <div class="finance-report-card"><span class="label">Cash Inflow</span><span class="value">{{ number_format($report['total_inflow'], 4) }}</span></div>
        <div class="finance-report-card"><span class="label">Cash Outflow</span><span class="value">{{ number_format($report['total_outflow'], 4) }}</span></div>
        <div class="finance-report-card"><span class="label">Net Cash Flow</span><span class="value">{{ number_format($report['net_cash_flow'], 4) }}</span></div>
        <div class="finance-report-card"><span class="label">Closing Cash</span><span class="value">{{ number_format($report['closing_balance'], 4) }}</span></div>
    </div>

    <div class="box box-primary"><div class="box-body table-responsive">
        <table class="table table-bordered table-striped finance-report-table">
            <thead><tr><th>Date</th><th class="text-right">Cash Inflow</th><th class="text-right">Cash Outflow</th><th class="text-right">Net Movement</th></tr></thead>
            <tbody>
            @forelse($report['rows'] as $row)
                <tr><td>{{ $row->flow_date }}</td><td class="text-right">{{ number_format($row->inflow, 4) }}</td><td class="text-right">{{ number_format($row->outflow, 4) }}</td><td class="text-right"><strong>{{ number_format($row->inflow - $row->outflow, 4) }}</strong></td></tr>
            @empty
                <tr><td colspan="4" class="text-center">No cash or bank movements found for the selected period.</td></tr>
            @endforelse
            </tbody>
        </table>
        @include('finance::finance_reports.partials.pagination', ['paginator' => $report['rows']])
    </div></div>
</section>
@endsection
