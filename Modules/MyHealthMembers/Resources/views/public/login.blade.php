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
    $mhBrowserTitle = $mhSetting('myhealth_browser_title', 'My Health Member Login');
    $mhLoginSubtitle = $mhSetting('myhealth_login_subtitle', 'Passcode-only secure portal access');
    $mhWelcomeMessage = $mhSetting('myhealth_welcome_message', '{{ $mhWelcomeMessage }}');
    $mhFooterText = $mhSetting('myhealth_footer_text', '');
    $mhPrimaryColor = $mhSetting('myhealth_primary_color', '#0d6efd');
    $mhSecondaryColor = $mhSetting('myhealth_secondary_color', '#00a6a6');
    $mhLogo = $mhSetting('myhealth_portal_logo', '');
    $mhBanner = $mhSetting('myhealth_login_banner', '');
    $mhSupportEmail = $mhSetting('myhealth_support_email', '');
    $mhSupportPhone = $mhSetting('myhealth_support_phone', '');
    $mhPrivacyUrl = $mhSetting('myhealth_privacy_url', '');
    $mhTermsUrl = $mhSetting('myhealth_terms_url', '');
@endphp

<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mhBrowserTitle }}</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body{background:linear-gradient(135deg,#edf5ff,#f7fbff);font-family:Arial,Helvetica,sans-serif;color:#253044}.login-wrap{max-width:480px;margin:45px auto;padding:0 15px}.login-card{background:#fff;border-radius:16px;border:1px solid #e4e9f2;box-shadow:0 10px 28px rgba(20,35,55,.14);overflow:hidden}.login-head{background:linear-gradient(135deg,{{ $mhPrimaryColor }},{{ $mhSecondaryColor }});color:#fff;padding:28px;text-align:center}.login-head h3{margin:0;font-weight:700}.login-head p{margin:9px 0 0;opacity:.94}.login-body{padding:28px}.form-control{height:44px;border-radius:8px}.btn-mh{background:{{ $mhPrimaryColor }};color:#fff;border-color:{{ $mhPrimaryColor }};height:44px;font-weight:700;border-radius:8px}.btn-mh:hover{filter:brightness(0.92);color:#fff}.help{font-size:13px;color:#697386;margin-top:14px}.secure-note{background:#f7f9fc;padding:13px;border-radius:10px;margin-top:16px;color:#576579;border:1px solid #e7eef7}.portal-option{padding:13px;border-radius:10px;background:#eef9ff;border:1px solid #d7edf9;margin-bottom:18px}.small-muted{font-size:12px;color:#748095}
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="login-head">
            <h3>@if($mhLogo)<img src="{{ asset($mhLogo) }}" style="max-height:42px;margin-right:8px;vertical-align:middle;">@else<i class="fa fa-heartbeat"></i>@endif {{ $mhPortalName }}</h3>
            <p>{{ $mhLoginSubtitle }}</p>
        </div>
        @if($mhBanner)<img src="{{ asset($mhBanner) }}" style="width:100%;max-height:180px;object-fit:cover;">@endif
        <div class="login-body">
            @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
            <div class="portal-option"><b>Member Portal</b><div class="small-muted">{{ $mhWelcomeMessage }}</div></div>
            <form method="POST" action="{{ url('/myhealth/login') }}">
                @csrf
                <div class="form-group">
                    <label>Passcode</label>
                    <input type="password" name="passcode" class="form-control" required autofocus autocomplete="off" placeholder="Enter your My Health passcode">
                </div>
                <button type="submit" class="btn btn-mh btn-block"><i class="fa fa-lock"></i> Login to My Health</button>
            </form>
            <div class="secure-note"><i class="fa fa-shield"></i> Your session is restricted to your own records only. ERP menus are not available in the member portal.</div>
            <div class="help text-center"><a href="{{ url('/myhealth-register') }}">Register as a new My Health Member</a>@if($mhPrivacyUrl) &nbsp; | &nbsp; <a href="{{ $mhPrivacyUrl }}" target="_blank">Privacy Policy</a>@endif @if($mhTermsUrl) &nbsp; | &nbsp; <a href="{{ $mhTermsUrl }}" target="_blank">Terms</a>@endif</div>
            @if($mhSupportEmail || $mhSupportPhone || $mhFooterText)<div class="help text-center">@if($mhFooterText){{ $mhFooterText }}<br>@endif @if($mhSupportEmail)<i class="fa fa-envelope"></i> {{ $mhSupportEmail }} @endif @if($mhSupportPhone)<i class="fa fa-phone"></i> {{ $mhSupportPhone }}@endif</div>@endif
        </div>
    </div>
</div>
</body>
</html>
