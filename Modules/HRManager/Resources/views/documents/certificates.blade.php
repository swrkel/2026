@extends('hrmanager::layouts.master')
@section('content')
<div class="hr-page">
    <div class="hr-hero"><div><span class="hr-eyebrow">Certificates</span><h1>Employee Certificates & Licences</h1><p>Track certifications, expiry dates and verification status.</p></div><div class="hr-actions"><a href="{{ route('hr.documents.dashboard') }}" class="hr-btn hr-btn-light">Back</a></div></div>
    <div class="hr-grid-2">
        <div class="hr-card"><h3>Add Certificate</h3><form method="POST" action="{{ route('hr.documents.certificates.store') }}" class="hr-form">@csrf
            <label>Employee ID</label><input type="number" name="employee_id" required>
            <label>Type</label><input name="certificate_type" required>
            <label>Title</label><input name="title" required>
            <label>Issued Date</label><input type="date" name="issued_date">
            <label>Expiry Date</label><input type="date" name="expiry_date">
            <label>Authority</label><input name="issuing_authority">
            <button class="hr-btn hr-btn-primary">Save Certificate</button>
        </form></div>
        <div class="hr-card"><h3>Certificates List</h3><table class="hr-table"><thead><tr><th>No</th><th>Employee</th><th>Title</th><th>Expiry</th><th>Status</th></tr></thead><tbody>
            @forelse($certificates as $row)<tr><td>{{ $row->certificate_no }}</td><td>{{ $row->employee_id }}</td><td>{{ $row->title }}</td><td>{{ $row->expiry_date }}</td><td><span class="hr-badge">{{ $row->verification_status }}</span></td></tr>
            @empty<tr><td colspan="5" class="hr-empty">No certificates found.</td></tr>@endforelse
        </tbody></table>{{ $certificates->links() }}</div>
    </div>
</div>
@endsection
