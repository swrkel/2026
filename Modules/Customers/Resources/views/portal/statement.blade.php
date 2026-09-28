@extends('customers::portal.layout')
@section('title', 'My Statement')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    @include('customers::portal.partials_summary')
    <div class="dd-card">
        <div class="dd-card-header clearfix">
            <h3 class="dd-card-title pull-left">My Customer Statement</h3>
            <div class="pull-right dd-no-print">
                <a class="dd-btn dd-btn-default" target="_blank" href="{{ route('customers.portal.statement.print', request()->only(['from','to'])) }}">Print</a>
                <a class="dd-btn dd-btn-default" href="{{ route('customers.portal.statement.export', request()->only(['from','to'])) }}">Export CSV</a>
            </div>
        </div>
        <div class="dd-card-body">
            <form method="GET" class="dd-filter dd-no-print">
                <div class="form-group"><label>From</label><input type="date" name="from" value="{{ request('from') }}"></div>
                <div class="form-group"><label>To</label><input type="date" name="to" value="{{ request('to') }}"></div>
                <button class="dd-btn dd-btn-primary" type="submit">Filter</button>
                <a class="dd-btn dd-btn-default" href="{{ route('customers.portal.statement') }}">Reset</a>
            </form>
            <div class="dd-table-wrap">
                <table class="dd-table">
                    <thead><tr><th>Transaction<br>Date</th><th>System Entered<br>Date & Time</th><th>Reference</th><th>Description</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th class="text-right">Balance</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->transaction_date }}</td>
                            <td>{{ $row->system_datetime }}</td>
                            <td>{{ $row->reference }}</td>
                            <td>{{ $row->description }}</td>
                            <td class="text-right">{{ number_format((float)$row->debit, 2) }}</td>
                            <td class="text-right">{{ number_format((float)$row->credit, 2) }}</td>
                            <td class="text-right"><strong>{{ number_format((float)$row->balance, 2) }}</strong></td>
                            <td><span class="dd-badge {{ strtolower($row->status) == 'paid' ? 'dd-badge-paid' : 'dd-badge-due' }}">{{ $row->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">No statement entries found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
