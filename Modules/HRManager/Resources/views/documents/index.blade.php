@extends('hrmanager::layouts.master')
@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header',['title'=>'Employee Documents & Digital Personnel File','subtitle'=>'Employee e-file, document versions, expiry alerts, requests, digital signatures and access logs.','section'=>'Documents'])

<div class="hr-card-grid">
<div class="hr-kpi purple"><i class="fa fa-users"></i><span>Employees</span><strong>{{ number_format($stats['employees'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-folder"></i><span>Categories</span><strong>{{ number_format($stats['categories'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-file"></i><span>Documents</span><strong>{{ number_format($stats['documents'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-paper-plane"></i><span>Requests</span><strong>{{ number_format($stats['requests'] ?? 0) }}</strong></div>
<div class="hr-kpi purple"><i class="fa fa-hourglass"></i><span>Pending Requests</span><strong>{{ number_format($stats['pending_requests'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-bell"></i><span>Expiry Alerts</span><strong>{{ number_format($stats['expiry_alerts'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-pencil"></i><span>Signatures</span><strong>{{ number_format($stats['signatures'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-eye"></i><span>Access Logs</span><strong>{{ number_format($stats['access_logs'] ?? 0) }}</strong></div>
</div>

<div class="doc-grid">
<div class="hr-panel">
<h3>Add Document</h3>
<form method="POST" action="{{ route('hrmanager.documents.files.store') }}" class="hr-form">@csrf
<label>Employee</label><select name="employee_id" required><option value="">Select Employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>@endforeach</select>
<label>Category</label><select name="document_category_id"><option value="">Select Category</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->category_name }}</option>@endforeach</select>
<label>Document Name</label><input name="document_name" required>
<label>Document Type</label><input name="document_type">
<label>File Path</label><input name="file_path" placeholder="pending-upload">
<label>Expiry Date</label><input type="date" name="expiry_date">
<button class="hr-primary-btn">Save Document</button>
</form>

<h3 class="mt">Request Document</h3>
<form method="POST" action="{{ route('hrmanager.documents.requests.store') }}" class="hr-form">@csrf
<label>Employee</label><select name="employee_id" required><option value="">Select Employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>@endforeach</select>
<label>Request Title</label><input name="request_title" required>
<label>Request Note</label><input name="request_note">
<button class="hr-primary-btn">Create Request</button>
</form>
</div>

<div class="hr-panel wide">
<h3>Employee Documents</h3>
@include('hrmanager::components.list-toolbar',['searchPlaceholder'=>'Search document no, name, status'])
<table class="hr-table"><thead><tr><th>Document No</th><th>Employee</th><th>Name</th><th>Type</th><th>Expiry</th><th>Status</th><th>Approval</th></tr></thead><tbody>
@forelse($documents as $row)
<tr><td>{{ $row->document_no }}</td><td>#{{ $row->employee_id }}</td><td>{{ $row->document_name }}</td><td>{{ $row->document_type }}</td><td>{{ $row->expiry_date }}</td><td><em class="status-pill">{{ ucfirst($row->document_status) }}</em></td><td><em class="status-pill">{{ ucfirst($row->approval_status) }}</em></td></tr>
@empty<tr><td colspan="7" class="empty-row">No employee documents found.</td></tr>@endforelse
</tbody></table>
@if(method_exists($documents,'links')){{ $documents->links() }}@endif
</div>
</div>

<div class="doc-grid bottom">
<div class="hr-panel"><h3>Document Requests</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Employee</th><th>Status</th></tr></thead><tbody>@forelse($requests as $row)<tr><td>{{ $row->request_no }}</td><td>#{{ $row->employee_id }}</td><td><em class="status-pill">{{ $row->request_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No requests.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Expiry Alerts</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Employee</th><th>Expiry</th></tr></thead><tbody>@forelse($alerts as $row)<tr><td>{{ $row->alert_no }}</td><td>#{{ $row->employee_id }}</td><td>{{ $row->expiry_date }}</td></tr>@empty<tr><td colspan="3" class="empty-row">No alerts.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Signatures</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Employee</th><th>Status</th></tr></thead><tbody>@forelse($signatures as $row)<tr><td>{{ $row->signature_no }}</td><td>#{{ $row->employee_id }}</td><td><em class="status-pill">{{ $row->signature_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No signatures.</td></tr>@endforelse</tbody></table></div>
</div>
</div>
@endsection
