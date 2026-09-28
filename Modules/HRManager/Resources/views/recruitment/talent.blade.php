@extends('hrmanager::layouts.master')
@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header',['title'=>'Recruitment & Talent Acquisition','subtitle'=>'Vacancies, candidates, applications, interviews, offers and joining workflows connected to Employee Central Record.','section'=>'Recruitment'])

<div class="hr-card-grid">
<div class="hr-kpi purple"><i class="fa fa-briefcase"></i><span>Positions</span><strong>{{ number_format($stats['positions'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-file"></i><span>Requisitions</span><strong>{{ number_format($stats['requisitions'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-bullhorn"></i><span>Vacancies</span><strong>{{ number_format($stats['vacancies'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-users"></i><span>Candidates</span><strong>{{ number_format($stats['candidates'] ?? 0) }}</strong></div>
<div class="hr-kpi purple"><i class="fa fa-paperclip"></i><span>Applications</span><strong>{{ number_format($stats['applications'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-comments"></i><span>Interviews</span><strong>{{ number_format($stats['interviews'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-handshake-o"></i><span>Offers</span><strong>{{ number_format($stats['offers'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-sign-in"></i><span>Joining</span><strong>{{ number_format($stats['joining'] ?? 0) }}</strong></div>
</div>

<div class="rec-grid">
<div class="hr-panel">
<h3>Add Candidate</h3>
<form method="POST" action="{{ route('hrmanager.recruitment_talent.candidates.store') }}" class="hr-form">@csrf
<label>Full Name</label><input name="full_name" required>
<label>Mobile</label><input name="mobile">
<label>Email</label><input type="email" name="email">
<label>NIC No</label><input name="nic_no">
<label>Source</label><select name="source"><option value="direct">Direct</option><option value="referral">Referral</option><option value="job_portal">Job Portal</option><option value="agency">Agency</option></select>
<button class="hr-primary-btn">Save Candidate</button>
</form>

<h3 class="mt">Apply Candidate</h3>
<form method="POST" action="{{ route('hrmanager.recruitment_talent.applications.store') }}" class="hr-form">@csrf
<label>Candidate</label><select name="candidate_id" required><option value="">Select Candidate</option>@foreach($candidates as $candidate)<option value="{{ $candidate->id }}">{{ $candidate->candidate_no }} - {{ $candidate->full_name }}</option>@endforeach</select>
<label>Vacancy</label><select name="vacancy_id" required><option value="">Select Vacancy</option>@foreach($vacancies as $vacancy)<option value="{{ $vacancy->id }}">{{ $vacancy->vacancy_no }} - {{ $vacancy->vacancy_title }}</option>@endforeach</select>
<button class="hr-primary-btn">Create Application</button>
</form>
</div>

<div class="hr-panel wide">
<h3>Candidate Register</h3>
@include('hrmanager::components.list-toolbar',['searchPlaceholder'=>'Search candidate no, name, mobile'])
<table class="hr-table"><thead><tr><th>Candidate No</th><th>Name</th><th>Mobile</th><th>Email</th><th>Source</th><th>Status</th></tr></thead><tbody>
@forelse($candidates as $row)
<tr><td>{{ $row->candidate_no }}</td><td>{{ $row->full_name }}</td><td>{{ $row->mobile }}</td><td>{{ $row->email }}</td><td>{{ $row->source }}</td><td><em class="status-pill">{{ ucfirst($row->candidate_status) }}</em></td></tr>
@empty<tr><td colspan="6" class="empty-row">No candidates found.</td></tr>@endforelse
</tbody></table>
@if(method_exists($candidates,'links')){{ $candidates->links() }}@endif
</div>
</div>

<div class="rec-grid bottom">
<div class="hr-panel"><h3>Vacancies</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Title</th><th>Status</th></tr></thead><tbody>@forelse($vacancies as $row)<tr><td>{{ $row->vacancy_no }}</td><td>{{ $row->vacancy_title }}</td><td><em class="status-pill">{{ $row->vacancy_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No vacancies.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Applications</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Candidate</th><th>Stage</th></tr></thead><tbody>@forelse($applications as $row)<tr><td>{{ $row->application_no }}</td><td>#{{ $row->candidate_id }}</td><td><em class="status-pill">{{ $row->current_stage }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No applications.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Interviews</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Candidate</th><th>Status</th></tr></thead><tbody>@forelse($interviews as $row)<tr><td>{{ $row->interview_no }}</td><td>#{{ $row->candidate_id }}</td><td><em class="status-pill">{{ $row->interview_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No interviews.</td></tr>@endforelse</tbody></table></div>
</div>
</div>
@endsection
