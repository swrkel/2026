@extends('layouts.app')
@section('title', 'Identity Access Settings')
@section('content')
<section class="content-header"><h1>Identity Access Settings</h1></section>
<section class="content"><div class="box box-primary"><form method="POST" action="{{ route('identityaccess.settings.store') }}">@csrf<div class="box-body">
<div class="form-group"><label>OTP Expiry Minutes</label><input type="number" class="form-control" value="{{ config('identityaccess.otp_expiry_minutes', 5) }}" readonly></div>
<div class="form-group"><label>Max Failed Attempts</label><input type="number" class="form-control" value="{{ config('identityaccess.max_failed_attempts', 5) }}" readonly></div>
</div><div class="box-footer"><button class="btn btn-primary">Save</button></div></form></div></section>
@endsection
