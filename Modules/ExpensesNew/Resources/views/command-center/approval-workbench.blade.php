@extends('layouts.app')
@section('title', $title ?? 'Approval Workbench')
@section('content')
<div class="expnew-page">
    <div class="expnew-header"><h1>{{ $title ?? 'Approval Workbench' }}</h1></div>
    <div class="expnew-toolbar">
        <button class="btn btn-success expnew-bulk-action" data-action="approve">Approve</button>
        <button class="btn btn-danger expnew-bulk-action" data-action="reject">Reject</button>
        <button class="btn btn-warning expnew-bulk-action" data-action="return">Return</button>
    </div>
    <table class="table table-bordered table-striped" id="expnew-approval-table">
        <thead><tr><th><input type="checkbox" id="expnew-check-all"></th><th>Expense</th><th>Business</th><th>Amount</th><th>Priority</th><th>Status</th></tr></thead>
        <tbody>
            @forelse(($queues ?? []) as $row)
                <tr><td><input type="checkbox" value="{{ $row->id }}"></td><td>{{ $row->expense_no ?? $row->id }}</td><td>{{ $row->business_id ?? '' }}</td><td>{{ number_format($row->amount ?? 0, 2) }}</td><td>{{ $row->priority ?? '' }}</td><td>{{ $row->status ?? '' }}</td></tr>
            @empty
                <tr><td colspan="6">No approvals pending.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
