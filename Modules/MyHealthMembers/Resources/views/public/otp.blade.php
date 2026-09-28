<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Health OTP Verification</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>body{background:#eef3fb;font-family:Arial,Helvetica,sans-serif}.login-wrap{max-width:440px;margin:55px auto}.login-card{background:#fff;border-radius:12px;border:1px solid #e4e9f2;box-shadow:0 8px 25px rgba(20,35,55,.12);overflow:hidden}.login-head{background:#0d6efd;color:#fff;padding:24px;text-align:center}.login-head h3{margin:0;font-weight:700}.login-body{padding:26px}.form-control{height:46px;text-align:center;font-size:22px;letter-spacing:5px}.btn-mh{background:#0d6efd;color:#fff;border-color:#0d6efd;height:42px;font-weight:700}.btn-mh:hover{background:#0b5ed7;color:#fff}.secure-note{background:#f7f9fc;padding:12px;border-radius:8px;margin-top:15px;color:#576579}</style>
</head>
<body>
<div class="login-wrap"><div class="login-card"><div class="login-head"><h3><i class="fa fa-key"></i> OTP Verification</h3><p style="margin:8px 0 0">Enter the 6 digit OTP</p></div><div class="login-body">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ url('/myhealth/otp') }}">@csrf<div class="form-group"><label>OTP</label><input type="text" name="otp" class="form-control" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus autocomplete="off" placeholder="000000"></div><button class="btn btn-mh btn-block"><i class="fa fa-check"></i> Verify & Continue</button></form>
<form method="POST" action="{{ url('/myhealth/otp/resend') }}" style="margin-top:10px">@csrf<button class="btn btn-default btn-block" type="submit"><i class="fa fa-refresh"></i> Resend OTP</button></form>
<div class="secure-note"><i class="fa fa-info-circle"></i> Email OTP is sent where available. SMS OTP is sent only if enabled and wallet authorization permits.</div>
</div></div></div>
</body>
</html>
