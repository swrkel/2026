<!DOCTYPE html>
@php
    $mhSetting = function ($key, $default = '') {
        try {
            $value = \App\System::getProperty($key);
            return $value !== null && $value !== '' ? $value : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    };
    $mhPortalName = $mhSetting('myhealth_portal_name', 'My Health Member Portal');
    $mhBrowserTitle = $mhSetting('myhealth_browser_title', $mhPortalName);
    $mhFooterText = $mhSetting('myhealth_footer_text', 'My Health Member Portal • Secure access to your own records only • Powered by standalone MyHealthMembers');
    $mhCopyrightText = $mhSetting('myhealth_copyright_text', '');
    $mhPrimaryColor = $mhSetting('myhealth_primary_color', '#0d6efd');
    $mhSecondaryColor = $mhSetting('myhealth_secondary_color', '#00a6a6');
    $mhLogo = $mhSetting('myhealth_portal_logo', '');
    $mhSupportEmail = $mhSetting('myhealth_support_email', '');
    $mhSupportPhone = $mhSetting('myhealth_support_phone', '');
@endphp

<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $mhBrowserTitle)</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body{background:#f5f8fc;font-family:Arial,Helvetica,sans-serif;color:#233044}.mh-top{background:linear-gradient(135deg,{{ $mhPrimaryColor }},{{ $mhSecondaryColor }});color:#fff;padding:20px 0;box-shadow:0 5px 18px rgba(13,110,253,.18)}.mh-top h3{margin:0;font-weight:700}.mh-shell{padding:24px 0}.mh-card{background:#fff;border-radius:14px;border:1px solid #e6edf6;box-shadow:0 5px 18px rgba(29,45,75,.06);margin-bottom:20px;overflow:hidden}.mh-card-header{padding:16px 20px;border-bottom:1px solid #edf2f8;font-weight:700;font-size:16px;background:#fff}.mh-card-body{padding:20px}.mh-nav{background:#fff;border-radius:14px;border:1px solid #e6edf6;padding:12px;margin-bottom:20px;box-shadow:0 5px 18px rgba(29,45,75,.05);position:sticky;top:15px}.mh-nav a{display:block;padding:11px 13px;border-radius:9px;color:#26364a;text-decoration:none;margin-bottom:3px}.mh-nav a:hover,.mh-nav a.active{background:#eef6ff;color:{{ $mhPrimaryColor }}}.mh-nav i{width:20px}.mh-stat{padding:20px;border-radius:14px;background:#fff;border:1px solid #e6edf6;margin-bottom:15px;box-shadow:0 4px 15px rgba(29,45,75,.05)}.mh-stat h3{margin:0;font-weight:700;font-size:28px}.mh-stat p{margin:7px 0 0;color:#6b778d}.mh-stat i{float:right;font-size:28px;color:#b7c5d8}.table>thead>tr>th{background:#f7f9fc}.mh-footer{color:#8792a2;font-size:12px;text-align:center;padding:20px}.btn-mh{background:{{ $mhPrimaryColor }};color:#fff;border-color:{{ $mhPrimaryColor }}}.btn-mh:hover{color:#fff;filter:brightness(0.92)}.label-soft{background:#eef6ff;color:{{ $mhPrimaryColor }};padding:5px 9px;border-radius:20px}.member-name{font-size:14px;opacity:.95}.logout-form{display:inline}.empty-state{padding:25px;text-align:center;color:#697386}.empty-state i{font-size:36px;margin-bottom:10px;color:#b5c0cf}.timeline{position:relative;margin:0;padding:0;list-style:none}.timeline:before{content:"";position:absolute;left:18px;top:0;bottom:0;width:2px;background:#e6edf6}.timeline li{position:relative;padding-left:55px;margin-bottom:18px}.timeline .dot{position:absolute;left:0;top:0;width:36px;height:36px;border-radius:50%;background:#eef6ff;color:{{ $mhPrimaryColor }};text-align:center;line-height:36px;border:1px solid #d8e9ff}.timeline .time{color:#7a8798;font-size:12px}.health-pill{display:inline-block;background:#f7f9fc;border:1px solid #e6edf6;border-radius:999px;padding:7px 12px;margin:3px 3px 6px}.portal-hero{background:linear-gradient(135deg,#ffffff,#eef9ff);border-radius:14px;border:1px solid #e6edf6;padding:20px;margin-bottom:20px}.portal-hero h2{margin-top:0;font-weight:700}.quick-link{display:block;background:#fff;border:1px solid #e6edf6;border-radius:12px;padding:15px;text-decoration:none;color:#233044;margin-bottom:15px}.quick-link:hover{box-shadow:0 6px 18px rgba(29,45,75,.09);text-decoration:none}.quick-link i{font-size:24px;color:{{ $mhPrimaryColor }};margin-right:8px}.scope-badge{display:inline-block;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.35);border-radius:999px;padding:5px 10px;margin-top:7px;font-size:12px}.page-title{font-weight:700;margin-top:0;margin-bottom:18px}.security-mini{font-size:12px;color:#6b778d;margin-top:10px}.security-mini i{color:#12a982}@media(max-width:767px){.mh-shell{padding:15px}.mh-top{text-align:center}.mh-top .text-right{text-align:center!important;margin-top:10px}.mh-nav{position:static}.timeline:before{left:14px}.timeline li{padding-left:46px}.timeline .dot{width:30px;height:30px;line-height:30px}}
    </style>
</head>
<body>
@php $current = Route::currentRouteName(); @endphp
<div class="mh-top">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-7"><h3>@if($mhLogo)<img src="{{ asset($mhLogo) }}" style="max-height:34px;margin-right:8px;vertical-align:middle;">@else<i class="fa fa-heartbeat"></i>@endif {{ $mhPortalName }}</h3><div class="member-name">{{ session('myhealth_member_name') }} @if(session('myhealth_member_code')) • {{ session('myhealth_member_code') }} @endif</div><span class="scope-badge"><i class="fa fa-lock"></i> Own records only</span></div>
            <div class="col-sm-5 text-right">
                <form method="POST" action="{{ url('/myhealth/logout') }}" class="logout-form">@csrf<button class="btn btn-default btn-sm"><i class="fa fa-sign-out"></i> Logout</button></form>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid mh-shell">
    <div class="row">
        <div class="col-md-3 col-lg-2">
            <div class="mh-nav">
                <a class="{{ $current === 'myhealth.member.portal.dashboard' ? 'active' : '' }}" href="{{ route('myhealth.member.portal.dashboard') }}"><i class="fa fa-dashboard"></i> Dashboard</a>
                <a class="{{ $current === 'myhealth.member.portal.profile' ? 'active' : '' }}" href="{{ route('myhealth.member.portal.profile') }}"><i class="fa fa-user"></i> My Profile</a>
                <a class="{{ $current === 'myhealth.member.portal.timeline' ? 'active' : '' }}" href="{{ route('myhealth.member.portal.timeline') }}"><i class="fa fa-list-alt"></i> Health Timeline</a>
                <a class="{{ $current === 'myhealth.member.portal.history' ? 'active' : '' }}" href="{{ route('myhealth.member.portal.history') }}"><i class="fa fa-history"></i> Medical History</a>
                <a class="{{ $current === 'myhealth.member.portal.prescriptions' ? 'active' : '' }}" href="{{ route('myhealth.member.portal.prescriptions') }}"><i class="fa fa-medkit"></i> Prescriptions</a>
                <a class="{{ $current === 'myhealth.member.portal.labs' ? 'active' : '' }}" href="{{ route('myhealth.member.portal.labs') }}"><i class="fa fa-flask"></i> Laboratory</a>
                <a class="{{ $current === 'myhealth.member.portal.radiology' ? 'active' : '' }}" href="{{ route('myhealth.member.portal.radiology') }}"><i class="fa fa-file-image-o"></i> Radiology</a>
                <a class="{{ $current === 'myhealth.member.portal.vaccinations' ? 'active' : '' }}" href="{{ route('myhealth.member.portal.vaccinations') }}"><i class="fa fa-shield"></i> Vaccinations</a>
                <a class="{{ $current === 'myhealth.member.portal.billing' ? 'active' : '' }}" href="{{ route('myhealth.member.portal.billing') }}"><i class="fa fa-credit-card"></i> Billing</a>
                <a class="{{ $current === 'myhealth.member.portal.appointments' ? 'active' : '' }}" href="{{ route('myhealth.member.portal.appointments') }}"><i class="fa fa-calendar"></i> Appointments</a>
                <a class="{{ $current === 'myhealth.member.portal.documents' ? 'active' : '' }}" href="{{ route('myhealth.member.portal.documents') }}"><i class="fa fa-folder-open"></i> Documents</a>
                <a class="{{ $current === 'myhealth.member.portal.notifications' ? 'active' : '' }}" href="{{ route('myhealth.member.portal.notifications') }}"><i class="fa fa-bell"></i> Notifications</a>
                <a class="{{ $current === 'myhealth.member.portal.settings' ? 'active' : '' }}" href="{{ route('myhealth.member.portal.settings') }}"><i class="fa fa-cog"></i> Settings</a>
                <div class="security-mini"><i class="fa fa-check-circle"></i> IdentityAccess-ready session<br><i class="fa fa-check-circle"></i> No ERP menu access</div>
            </div>
        </div>
        <div class="col-md-9 col-lg-10">
            @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger"><ul style="margin-bottom:0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </div>
    </div>
</div>
<div class="mh-footer">{{ $mhFooterText }}@if($mhCopyrightText)<br>{{ $mhCopyrightText }}@endif @if($mhSupportEmail || $mhSupportPhone)<br>@if($mhSupportEmail)<i class="fa fa-envelope"></i> {{ $mhSupportEmail }} @endif @if($mhSupportPhone)<i class="fa fa-phone"></i> {{ $mhSupportPhone }}@endif @endif</div>
</body>
</html>
