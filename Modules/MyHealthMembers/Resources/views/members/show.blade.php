@extends('layouts.app')
@section('title', __('My Health Member Profile'))

@section('content')
<section class="content-header">
    <h1>{{ __('My Health Member Profile') }} <small>{{ $member->myhealth_code }}</small></h1>
</section>

<section class="content">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row">
        <div class="col-md-3">
            <div class="box box-primary">
                <div class="box-body box-profile">
                    @if(!empty($member->photo_path))
                        <img class="profile-user-img img-responsive img-circle" src="{{ asset($member->photo_path) }}" alt="{{ $member->name }}">
                    @else
                        <div class="text-center" style="font-size:64px;color:#999;"><i class="fa fa-user-circle"></i></div>
                    @endif
                    <h3 class="profile-username text-center">{{ $member->name }}</h3>
                    <p class="text-muted text-center">{{ __('Member Code') }}: <strong>{{ $member->myhealth_code }}</strong></p>
                    <ul class="list-group list-group-unbordered">
                        <li class="list-group-item"><b>{{ __('Login Code') }}</b> <span class="pull-right">{{ optional($member->login)->login_code }}</span></li>
                        <li class="list-group-item"><b>{{ __('Mobile') }}</b> <span class="pull-right">{{ $member->mobile }}</span></li>
                        <li class="list-group-item"><b>{{ __('Blood Group') }}</b> <span class="pull-right">{{ $member->blood_group }}</span></li>
                        <li class="list-group-item"><b>{{ __('Status') }}</b> <span class="pull-right label label-success">{{ $member->status_label }}</span></li>
                    </ul>
                    @if(\Illuminate\Support\Facades\Route::has('myhealth.members.edit'))
                        <a href="{{ route('myhealth.members.edit', $member->id) }}" class="btn btn-primary btn-block"><i class="fa fa-edit"></i> {{ __('Edit Profile') }}</a>
                    @endif
                </div>
            </div>

            <div class="box box-solid">
                <div class="box-header with-border"><h3 class="box-title">{{ __('QR Access') }}</h3></div>
                <div class="box-body text-center">
                    @if(!empty($qrUrl))
                        <p class="text-muted">{{ __('Secure QR profile link') }}</p>
                        <input type="text" class="form-control" value="{{ $qrUrl }}" readonly>
                    @else
                        <p class="text-muted">{{ __('QR token not available') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#personal" data-toggle="tab">{{ __('Personal Information') }}</a></li>
                    <li><a href="#medical" data-toggle="tab">{{ __('Medical Summary') }}</a></li>
                    <li><a href="#timeline" data-toggle="tab">{{ __('Timeline') }}</a></li>
                    <li><a href="#documents" data-toggle="tab">{{ __('Documents') }}</a></li>
                    <li><a href="#audit" data-toggle="tab">{{ __('Audit Log') }}</a></li>
                </ul>
                <div class="tab-content">
                    <div class="active tab-pane" id="personal">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr><th>{{ __('Full Name') }}</th><td>{{ $member->name }}</td></tr>
                                    <tr><th>{{ __('Date of Birth') }}</th><td>{{ optional($member->date_of_birth)->format('Y-m-d') }} @if($member->age) ({{ $member->age }} {{ __('years') }}) @endif</td></tr>
                                    <tr><th>{{ __('Gender') }}</th><td>{{ ucfirst((string) $member->gender) }}</td></tr>
                                    <tr><th>{{ __('NIC No') }}</th><td>{{ $member->nic_no }}</td></tr>
                                    <tr><th>{{ __('Passport No') }}</th><td>{{ $member->passport_no }}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr><th>{{ __('Mobile') }}</th><td>{{ $member->mobile }}</td></tr>
                                    <tr><th>{{ __('Email') }}</th><td>{{ $member->email }}</td></tr>
                                    <tr><th>{{ __('Emergency Contact') }}</th><td>{{ $member->emergency_contact_name }} {{ $member->emergency_contact_mobile }}</td></tr>
                                    <tr><th>{{ __('Guardian') }}</th><td>{{ $member->guardian_name }} {{ $member->guardian_mobile }}</td></tr>
                                    <tr><th>{{ __('Address') }}</th><td>{{ $member->address }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="medical">
                        <div class="row">
                            <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-heartbeat"></i></span><div class="info-box-content"><span class="info-box-text">{{ __('Blood Group') }}</span><span class="info-box-number">{{ $member->blood_group ?: '-' }}</span></div></div></div>
                            <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-blue"><i class="fa fa-arrows-v"></i></span><div class="info-box-content"><span class="info-box-text">{{ __('Height') }}</span><span class="info-box-number">{{ $member->height_feet ? $member->height_feet . ' ft ' . ($member->height_inches ?? 0) . ' in' : '-' }}</span></div></div></div>
                            <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-balance-scale"></i></span><div class="info-box-content"><span class="info-box-text">{{ __('Weight') }}</span><span class="info-box-number">{{ $member->weight_kg ? $member->weight_kg . ' Kg' : '-' }}</span></div></div></div>
                        </div>
                        <table class="table table-bordered">
                            <tr><th style="width:220px;">{{ __('Allergies') }}</th><td>{{ optional($member->medicalHistory)->allergies }}</td></tr>
                            <tr><th>{{ __('Chronic Diseases') }}</th><td>{{ optional($member->medicalHistory)->chronic_conditions }}</td></tr>
                            <tr><th>{{ __('Current Medications') }}</th><td>{{ optional($member->medicalHistory)->current_medications }}</td></tr>
                            <tr><th>{{ __('Family History') }}</th><td>{{ optional($member->medicalHistory)->family_history }}</td></tr>
                        </table>
                    </div>

                    <div class="tab-pane" id="timeline">
                        <ul class="timeline timeline-inverse">
                            @foreach($member->consultations as $item)
                                <li><i class="fa fa-stethoscope bg-blue"></i><div class="timeline-item"><span class="time"><i class="fa fa-clock-o"></i> {{ optional($item->created_at)->format('Y-m-d') }}</span><h3 class="timeline-header">{{ __('Consultation') }}</h3><div class="timeline-body">{{ $item->chief_complaint ?? $item->notes ?? __('Consultation record') }}</div></div></li>
                            @endforeach
                            @foreach($member->diagnoses as $item)
                                <li><i class="fa fa-user-md bg-red"></i><div class="timeline-item"><span class="time">{{ $item->diagnosis_date }}</span><h3 class="timeline-header">{{ __('Diagnosis') }} - {{ $item->title }}</h3><div class="timeline-body">{{ $item->diagnosis }}</div></div></li>
                            @endforeach
                            @foreach($member->prescriptions as $item)
                                <li><i class="fa fa-medkit bg-green"></i><div class="timeline-item"><span class="time">{{ $item->prescription_date }}</span><h3 class="timeline-header">{{ __('Prescription') }}</h3><div class="timeline-body">{{ $item->prescription_details }}</div></div></li>
                            @endforeach
                            @if($member->consultations->isEmpty() && $member->diagnoses->isEmpty() && $member->prescriptions->isEmpty())
                                <li><i class="fa fa-info bg-gray"></i><div class="timeline-item"><div class="timeline-body text-muted">{{ __('No medical timeline records found yet.') }}</div></div></li>
                            @endif
                        </ul>
                    </div>

                    <div class="tab-pane" id="documents">
                        <table class="table table-bordered table-striped">
                            <thead><tr><th>{{ __('Type') }}</th><th>{{ __('Title') }}</th><th>{{ __('Uploaded') }}</th><th>{{ __('Action') }}</th></tr></thead>
                            <tbody>
                                @forelse($member->documents as $document)
                                    <tr><td>{{ $document->document_type }}</td><td>{{ $document->title }}</td><td>{{ optional($document->created_at)->format('Y-m-d') }}</td><td>@if($document->file_path)<a href="{{ asset($document->file_path) }}" target="_blank" class="btn btn-xs btn-default">{{ __('Open') }}</a>@endif</td></tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">{{ __('No documents uploaded') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="tab-pane" id="audit">
                        <table class="table table-bordered table-striped">
                            <thead><tr><th>{{ __('Section') }}</th><th>{{ __('Action') }}</th><th>{{ __('User') }}</th><th>{{ __('Date & Time') }}</th></tr></thead>
                            <tbody>
                                @forelse($member->auditLogs as $log)
                                    <tr><td>{{ $log->section }}</td><td>{{ $log->action }}</td><td>{{ $log->user_id }}</td><td>{{ optional($log->created_at)->format('Y-m-d H:i') }}</td></tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">{{ __('No audit records found') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
