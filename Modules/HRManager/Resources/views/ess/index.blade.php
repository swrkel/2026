@extends('hrmanager::layouts.master')
@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header',['title'=>'Employee Self Service Portal','subtitle'=>'Employee requests, notifications, documents, payslips, profile changes and helpdesk tickets connected to Employee Central Record.','section'=>'ESS'])

<div class="hr-card-grid">
<div class="hr-kpi purple"><i class="fa fa-users"></i><span>Employees</span><strong>{{ number_format($stats['employees'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-id-card"></i><span>ESS Profiles</span><strong>{{ number_format($stats['profiles'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-paper-plane"></i><span>Requests</span><strong>{{ number_format($stats['requests'] ?? 0) }}</strong></div>
<div class="hr-kpi red"><i class="fa fa-hourglass"></i><span>Pending</span><strong>{{ number_format($stats['pending'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-bell"></i><span>Notifications</span><strong>{{ number_format($stats['notifications'] ?? 0) }}</strong></div>
<div class="hr-kpi purple"><i class="fa fa-file"></i><span>Documents</span><strong>{{ number_format($stats['documents'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-edit"></i><span>Profile Changes</span><strong>{{ number_format($stats['changes'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-ticket"></i><span>Helpdesk</span><strong>{{ number_format($stats['tickets'] ?? 0) }}</strong></div>
</div>

<div class="ess-grid">
<div class="hr-panel">
<h3>Submit ESS Request</h3>
<form method="POST" action="{{ route('hrmanager.ess.requests.store') }}" class="hr-form">@csrf
<label>Employee</label><select name="employee_id" required><option value="">Select Employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>@endforeach</select>
<label>Request Type</label><select name="request_type" required><option value="leave">Leave</option><option value="payslip">Payslip</option><option value="profile_change">Profile Change</option><option value="document">Document</option><option value="claim">Claim</option></select>
<label>Title</label><input name="request_title" required>
<label>Description</label><textarea name="request_description"></textarea>
<button class="hr-primary-btn">Submit Request</button>
</form>

<h3 class="mt">Create Helpdesk Ticket</h3>
<form method="POST" action="{{ route('hrmanager.ess.tickets.store') }}" class="hr-form">@csrf
<label>Employee</label><select name="employee_id" required><option value="">Select Employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>@endforeach</select>
<label>Subject</label><input name="subject" required>
<label>Priority</label><select name="priority"><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option></select>
<button class="hr-primary-btn">Create Ticket</button>
</form>
</div>

<div class="hr-panel wide">
<h3>ESS Requests</h3>
@include('hrmanager::components.list-toolbar',['searchPlaceholder'=>'Search request no, title, status'])
<table class="hr-table"><thead><tr><th>Request No</th><th>Employee</th><th>Type</th><th>Title</th><th>Status</th><th>Approval</th></tr></thead><tbody>
@forelse($requests as $row)
<tr><td>{{ $row->request_no }}</td><td>#{{ $row->employee_id }}</td><td>{{ $row->request_type }}</td><td>{{ $row->request_title }}</td><td><em class="status-pill">{{ ucfirst($row->request_status) }}</em></td><td><em class="status-pill">{{ ucfirst($row->approval_status) }}</em></td></tr>
@empty<tr><td colspan="6" class="empty-row">No ESS requests found.</td></tr>@endforelse
</tbody></table>
@if(method_exists($requests,'links')){{ $requests->links() }}@endif
</div>
</div>

<div class="ess-grid bottom">
<div class="hr-panel"><h3>Notifications</h3><table class="hr-table compact-table"><thead><tr><th>Employee</th><th>Title</th><th>Read</th></tr></thead><tbody>@forelse($notifications as $row)<tr><td>#{{ $row->employee_id }}</td><td>{{ $row->title }}</td><td>{{ $row->read_status ? 'Yes' : 'No' }}</td></tr>@empty<tr><td colspan="3" class="empty-row">No notifications.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Documents</h3><table class="hr-table compact-table"><thead><tr><th>Employee</th><th>Document</th><th>Type</th></tr></thead><tbody>@forelse($documents as $row)<tr><td>#{{ $row->employee_id }}</td><td>{{ $row->document_name }}</td><td>{{ $row->document_type }}</td></tr>@empty<tr><td colspan="3" class="empty-row">No documents.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Helpdesk Tickets</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Employee</th><th>Status</th></tr></thead><tbody>@forelse($tickets as $row)<tr><td>{{ $row->ticket_no }}</td><td>#{{ $row->employee_id }}</td><td><em class="status-pill">{{ $row->ticket_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No tickets.</td></tr>@endforelse</tbody></table></div>
</div>
</div>
@endsection
