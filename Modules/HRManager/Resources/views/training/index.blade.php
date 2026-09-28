@extends('hrmanager::layouts.master')
@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header',['title'=>'Learning & Training Management','subtitle'=>'Training courses, sessions, enrollments, attendance, assessments, certificates and skill gaps connected to Employee Central Record.','section'=>'Training'])
<div class="hr-card-grid">
<div class="hr-kpi purple"><i class="fa fa-users"></i><span>Employees</span><strong>{{ number_format($stats['employees'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-folder"></i><span>Categories</span><strong>{{ number_format($stats['categories'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-book"></i><span>Courses</span><strong>{{ number_format($stats['courses'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-calendar"></i><span>Sessions</span><strong>{{ number_format($stats['sessions'] ?? 0) }}</strong></div>
<div class="hr-kpi purple"><i class="fa fa-user-plus"></i><span>Enrollments</span><strong>{{ number_format($stats['enrollments'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-certificate"></i><span>Certificates</span><strong>{{ number_format($stats['certificates'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-warning"></i><span>Skill Gaps</span><strong>{{ number_format($stats['skill_gaps'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-check"></i><span>Assessments</span><strong>{{ number_format($stats['assessments'] ?? 0) }}</strong></div>
</div>

<div class="training-grid">
<div class="hr-panel"><h3>Enroll Employee</h3><form method="POST" action="{{ route('hrmanager.training.enroll') }}" class="hr-form">@csrf<label>Employee</label><select name="employee_id" required><option value="">Select Employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>@endforeach</select><label>Course</label><select name="training_course_id" required><option value="">Select Course</option>@foreach($courses as $course)<option value="{{ $course->id }}">{{ $course->course_name }}</option>@endforeach</select><label>Session</label><select name="training_session_id" required><option value="">Select Session</option>@foreach($sessions as $session)<option value="{{ $session->id }}">{{ $session->session_title }}</option>@endforeach</select><button class="hr-primary-btn">Enroll Employee</button></form></div>
<div class="hr-panel wide"><h3>Training Sessions</h3>@include('hrmanager::components.list-toolbar',['searchPlaceholder'=>'Search session no, title, status'])<table class="hr-table"><thead><tr><th>Session No</th><th>Title</th><th>Course</th><th>Start</th><th>End</th><th>Status</th></tr></thead><tbody>@forelse($sessions as $row)<tr><td>{{ $row->session_no }}</td><td>{{ $row->session_title }}</td><td>{{ $row->training_course_id }}</td><td>{{ $row->start_datetime }}</td><td>{{ $row->end_datetime }}</td><td><em class="status-pill">{{ ucfirst($row->session_status) }}</em></td></tr>@empty<tr><td colspan="6" class="empty-row">No training sessions found.</td></tr>@endforelse</tbody></table>@if(method_exists($sessions,'links')){{ $sessions->links() }}@endif</div>
</div>

<div class="training-grid bottom">
<div class="hr-panel"><h3>Courses</h3><table class="hr-table compact-table"><thead><tr><th>Code</th><th>Course</th><th>Mode</th></tr></thead><tbody>@forelse($courses as $row)<tr><td>{{ $row->course_code }}</td><td>{{ $row->course_name }}</td><td>{{ $row->delivery_mode }}</td></tr>@empty<tr><td colspan="3" class="empty-row">No courses found.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Enrollments</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Employee</th><th>Status</th></tr></thead><tbody>@forelse($enrollments as $row)<tr><td>{{ $row->enrollment_no }}</td><td>#{{ $row->employee_id }}</td><td><em class="status-pill">{{ $row->enrollment_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No enrollments found.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Certificates</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Employee</th><th>Status</th></tr></thead><tbody>@forelse($certificates as $row)<tr><td>{{ $row->certificate_no }}</td><td>#{{ $row->employee_id }}</td><td><em class="status-pill">{{ $row->certificate_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No certificates found.</td></tr>@endforelse</tbody></table></div>
</div>
</div>
@endsection
