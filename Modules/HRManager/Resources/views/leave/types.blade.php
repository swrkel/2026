@extends('hrmanager::layouts.master')

@section('content')
<div class="hr-page">
    <div class="hr-hero">
        <div>
            <span class="hr-eyebrow">Leave Setup</span>
            <h1>Leave Types</h1>
            <p>Create annual leave, casual leave, medical leave and other company leave rules.</p>
        </div>
        <div class="hr-actions"><a href="{{ route('hr.leave.dashboard') }}" class="hr-btn hr-btn-light">Back</a></div>
    </div>

    <div class="hr-grid-2">
        <div class="hr-card">
            <h3>Add Leave Type</h3>
            <form method="POST" action="{{ route('hr.leave.types.store') }}" class="hr-form">
                @csrf
                <label>Code</label><input name="code" placeholder="AL / CL / ML">
                <label>Name</label><input name="name" required placeholder="Annual Leave">
                <label>Paid Status</label>
                <select name="paid_status"><option value="paid">Paid</option><option value="unpaid">Unpaid</option></select>
                <label>Annual Entitlement</label><input type="number" step="0.01" name="annual_entitlement" value="0">
                <label class="hr-check"><input type="checkbox" name="carry_forward_allowed" value="1"> Carry forward allowed</label>
                <label class="hr-check"><input type="checkbox" name="requires_attachment" value="1"> Attachment required</label>
                <button class="hr-btn hr-btn-primary">Save Leave Type</button>
            </form>
        </div>

        <div class="hr-card">
            <h3>Leave Types List</h3>
            <table class="hr-table">
                <thead><tr><th>Code</th><th>Name</th><th>Paid</th><th>Entitlement</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($types as $row)
                        <tr><td>{{ $row->code }}</td><td>{{ $row->name }}</td><td>{{ $row->paid_status }}</td><td>{{ $row->annual_entitlement }}</td><td><span class="hr-badge">{{ $row->status ? 'Active' : 'Inactive' }}</span></td></tr>
                    @empty
                        <tr><td colspan="5" class="hr-empty">No leave types found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $types->links() }}
        </div>
    </div>
</div>
@endsection
