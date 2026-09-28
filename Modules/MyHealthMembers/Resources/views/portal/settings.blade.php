@extends('myhealthmembers::portal.layout')
@section('title', 'Portal Settings')
@section('content')
<h3 class="page-title">Portal Settings</h3>
<div class="row">
    <div class="col-md-6"><div class="mh-card"><div class="mh-card-header">Security</div><div class="mh-card-body">
        <p><b>Access scope:</b> {{ $portal_security['access_scope'] ?? 'Own records only' }}</p>
        <p><b>Logged in at:</b> {{ $portal_security['logged_in_at'] ?? '-' }}</p>
        <p><b>Last activity:</b> {{ $portal_security['last_activity_at'] ?? '-' }}</p>
        <p><b>ERP administration access:</b> {{ $portal_security['erp_access'] ?? 'Disabled' }}</p>
        <p class="text-muted">Authentication is IdentityAccess-ready. OTP can be enabled from My Health / platform settings.</p>
    </div></div></div>
    <div class="col-md-6"><div class="mh-card"><div class="mh-card-header">Communication Preferences</div><div class="mh-card-body">
        <p><b>Email:</b> {{ $member->email ?? 'Not available' }}</p>
        <p><b>Mobile:</b> {{ $member->mobile ?? 'Not available' }}</p>
        <p class="text-muted">Email OTP is always attempted where an email exists. SMS OTP is sent through CommunicationHub only when enabled and wallet authorization permits.</p>
    </div></div></div>
</div>
<div class="row">
    <div class="col-md-6"><div class="mh-card"><div class="mh-card-header">Change Passcode</div><div class="mh-card-body">
        <form method="POST" action="{{ route('myhealth.member.portal.settings.passcode') }}">
            @csrf
            <div class="form-group"><label>Current Passcode *</label><input type="password" name="current_passcode" class="form-control" required autocomplete="off"></div>
            <div class="form-group"><label>New Passcode *</label><input type="password" name="new_passcode" class="form-control" required autocomplete="off"></div>
            <div class="form-group"><label>Confirm New Passcode *</label><input type="password" name="new_passcode_confirmation" class="form-control" required autocomplete="off"></div>
            <button class="btn btn-mh" type="submit"><i class="fa fa-key"></i> Change Passcode</button>
        </form>
        <p class="text-muted" style="margin-top:12px">Keep your passcode secure and confidential. Do not share it with anyone.</p>
    </div></div></div>
</div>
@endsection
