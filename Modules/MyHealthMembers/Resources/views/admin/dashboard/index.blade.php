@extends('layouts.app')
@section('title', 'My Health Administration')
@section('content')
<section class="content-header"><h1>My Health Administration</h1></section>
<section class="content">
    <div class="row">
        @foreach([
            'Total Members'=>'total_members','Active Members'=>'active_members','Total Doctors'=>'total_doctors','Consultations'=>'total_consultations','Pending Consents'=>'pending_consents','Pending Lab Requests'=>'pending_lab_requests','Insurance Claims'=>'insurance_claims','Audit Logs'=>'audit_alerts'
        ] as $label => $key)
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-aqua"><i class="fa fa-heartbeat"></i></span>
                    <div class="info-box-content"><span class="info-box-text">{{ $label }}</span><span class="info-box-number">{{ number_format($summary[$key] ?? 0) }}</span></div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Administration Shortcuts</h3></div>
        <div class="box-body">
            <a class="btn btn-app" href="{{ route('myhealth.admin.doctors.index') }}"><i class="fa fa-user-md"></i> Doctors</a>
            <a class="btn btn-app" href="{{ route('myhealth.admin.businesses.index') }}"><i class="fa fa-building"></i> Businesses</a>
            <a class="btn btn-app" href="{{ route('myhealth.admin.permissions.index') }}"><i class="fa fa-lock"></i> Permissions</a>
            <a class="btn btn-app" href="{{ route('myhealth.admin.settings.index') }}"><i class="fa fa-cogs"></i> Settings</a>
            <a class="btn btn-app" href="{{ route('myhealth.admin.qr.index') }}"><i class="fa fa-qrcode"></i> QR Manager</a>
            <a class="btn btn-app" href="{{ route('myhealth.admin.notifications.index') }}"><i class="fa fa-bell"></i> Notifications</a>
        </div>
    </div>
</section>
@endsection
