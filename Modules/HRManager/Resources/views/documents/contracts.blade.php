@extends('hrmanager::layouts.master')
@section('content')
<div class="hr-page">
    <div class="hr-hero"><div><span class="hr-eyebrow">Contracts</span><h1>Employee Contracts</h1><p>Employment agreements, probation, renewal dates and contract status.</p></div><div class="hr-actions"><a href="{{ route('hr.documents.dashboard') }}" class="hr-btn hr-btn-light">Back</a></div></div>
    <div class="hr-grid-2">
        <div class="hr-card"><h3>Add Contract</h3><form method="POST" action="{{ route('hr.documents.contracts.store') }}" class="hr-form">@csrf
            <label>Employee ID</label><input type="number" name="employee_id" required>
            <label>Title</label><input name="title" required>
            <label>Type</label><input name="contract_type" value="employment">
            <label>Start Date</label><input type="date" name="start_date">
            <label>End Date</label><input type="date" name="end_date">
            <label>Salary Amount</label><input type="number" step="0.0001" name="salary_amount">
            <label>Terms</label><textarea name="terms"></textarea>
            <button class="hr-btn hr-btn-primary">Save Contract</button>
        </form></div>
        <div class="hr-card"><h3>Contracts List</h3><table class="hr-table"><thead><tr><th>No</th><th>Employee</th><th>Title</th><th>Dates</th><th>Status</th></tr></thead><tbody>
            @forelse($contracts as $row)<tr><td>{{ $row->contract_no }}</td><td>{{ $row->employee_id }}</td><td>{{ $row->title }}</td><td>{{ $row->start_date }} - {{ $row->end_date }}</td><td><span class="hr-badge">{{ $row->status }}</span></td></tr>
            @empty<tr><td colspan="5" class="hr-empty">No contracts found.</td></tr>@endforelse
        </tbody></table>{{ $contracts->links() }}</div>
    </div>
</div>
@endsection
