@extends('myhealthmembers::portal.layout')
@section('title', 'My Health Dashboard')
@section('content')
@if(empty($member))
    <div class="alert alert-warning">No My Health member profile is linked to this session.</div>
@else
    <div class="portal-hero">
        <div class="row">
            <div class="col-sm-8">
                <h2>Welcome, {{ $member->name }}</h2>
                <p class="text-muted">Member Code: <span class="label-soft">{{ $member->myhealth_code ?? $member->member_code ?? '' }}</span></p>
                <p>{{ $portal_branding['welcome_message'] ?? 'Your secure health dashboard shows only your own records.' }}</p>
            </div>
            <div class="col-sm-4 text-right">
                <span class="health-pill"><i class="fa fa-tint"></i> Blood Group: {{ $health_summary['blood_group'] ?? 'Not set' }}</span><br>
                <span class="health-pill"><i class="fa fa-warning"></i> Allergies: {{ $health_summary['allergies'] ?? 0 }}</span><br>
                <span class="health-pill"><i class="fa fa-heartbeat"></i> Chronic Conditions: {{ $health_summary['chronic_conditions'] ?? 0 }}</span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6 col-lg-3"><div class="mh-stat"><i class="fa fa-stethoscope"></i><h3>{{ $consultation_count }}</h3><p>Consultations</p></div></div>
        <div class="col-sm-6 col-lg-3"><div class="mh-stat"><i class="fa fa-medkit"></i><h3>{{ $prescription_count }}</h3><p>Prescriptions</p></div></div>
        <div class="col-sm-6 col-lg-3"><div class="mh-stat"><i class="fa fa-flask"></i><h3>{{ $lab_result_count }}</h3><p>Lab Results</p></div></div>
        <div class="col-sm-6 col-lg-3"><div class="mh-stat"><i class="fa fa-file-text"></i><h3>{{ $document_count }}</h3><p>Documents</p></div></div>
    </div>

    <div class="row">
        <div class="col-md-3"><a class="quick-link" href="{{ route('myhealth.member.portal.appointments') }}"><i class="fa fa-calendar"></i> Book / View Appointment</a></div>
        <div class="col-md-3"><a class="quick-link" href="{{ route('myhealth.member.portal.timeline') }}"><i class="fa fa-list-alt"></i> Health Timeline</a></div>
        <div class="col-md-3"><a class="quick-link" href="{{ route('myhealth.member.portal.labs') }}"><i class="fa fa-flask"></i> Lab Reports</a></div>
        <div class="col-md-3"><a class="quick-link" href="{{ route('myhealth.member.portal.documents') }}"><i class="fa fa-download"></i> My Documents</a></div>
    </div>

    <div class="row">
        <div class="col-md-8"><div class="mh-card"><div class="mh-card-header">Care Journey</div><div class="mh-card-body">
            <div class="row text-center">
                <div class="col-xs-6 col-sm-3"><span class="health-pill"><i class="fa fa-user-plus"></i> Registration</span></div>
                <div class="col-xs-6 col-sm-3"><span class="health-pill"><i class="fa fa-calendar"></i> Appointment</span></div>
                <div class="col-xs-6 col-sm-3"><span class="health-pill"><i class="fa fa-user-md"></i> Consultation</span></div>
                <div class="col-xs-6 col-sm-3"><span class="health-pill"><i class="fa fa-flask"></i> Lab / Radiology</span></div>
                <div class="col-xs-6 col-sm-3"><span class="health-pill"><i class="fa fa-medkit"></i> Prescription</span></div>
                <div class="col-xs-6 col-sm-3"><span class="health-pill"><i class="fa fa-credit-card"></i> Billing</span></div>
                <div class="col-xs-6 col-sm-3"><span class="health-pill"><i class="fa fa-bell"></i> Follow-up</span></div>
                <div class="col-xs-6 col-sm-3"><span class="health-pill"><i class="fa fa-shield"></i> Wellness</span></div>
            </div>
        </div></div></div>
        <div class="col-md-4"><div class="mh-card"><div class="mh-card-header">Security Status</div><div class="mh-card-body">
            <p><b>Access:</b> {{ $portal_security['access_scope'] ?? 'Own records only' }}</p>
            <p><b>Logged in:</b> {{ $portal_security['logged_in_at'] ?? '-' }}</p>
            <p><b>ERP Access:</b> {{ $portal_security['erp_access'] ?? 'Disabled' }}</p>
        </div></div></div>
    </div>

    <div class="row">
        <div class="col-md-6"><div class="mh-card"><div class="mh-card-header">Upcoming / Recent Appointments</div><div class="mh-card-body">@include('myhealthmembers::portal.partials.simple_list', ['rows' => $appointments ?? collect(), 'empty' => 'No appointments found.'])</div></div></div>
        <div class="col-md-6"><div class="mh-card"><div class="mh-card-header">Recent Prescriptions</div><div class="mh-card-body">@include('myhealthmembers::portal.partials.simple_list', ['rows' => $recent_prescriptions ?? collect(), 'empty' => 'No recent prescriptions.'])</div></div></div>
    </div>
    <div class="row">
        <div class="col-md-6"><div class="mh-card"><div class="mh-card-header">Recent Laboratory Results</div><div class="mh-card-body">@include('myhealthmembers::portal.partials.simple_list', ['rows' => $recent_labs ?? collect(), 'empty' => 'No recent lab results.'])</div></div></div>
        <div class="col-md-6"><div class="mh-card"><div class="mh-card-header">Health Timeline</div><div class="mh-card-body">@include('myhealthmembers::portal.partials.timeline', ['timeline' => $timeline ?? collect()])</div></div></div>
    </div>
@endif
@endsection
