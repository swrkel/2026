@extends('layouts.app')
@section('title', 'Communication Hub Settings')
@section('content')
<section class="content-header"><h1>Communication Hub Settings</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if(!empty($schemaMissing))
<div class="alert alert-danger">
    <strong>Communication Hub could not prepare its Settings table automatically.</strong><br>
    You can still use the Save Settings button. If saving fails, please check the Laravel log and confirm that the tenant database user has CREATE/ALTER permission.
</div>
@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@php
    $values = $settings->pluck('value', 'key');
    $checked = function ($key, $default = false) use ($values) {
        $value = $values[$key] ?? ($default ? '1' : '0');
        return in_array(strtolower((string) $value), ['1','true','yes','on'], true) ? 'checked' : '';
    };
@endphp
<form method="POST" action="{{ route('communicationhub.settings.store') }}">
    @csrf
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">General Settings</h3></div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-4"><label>Queue Process Limit</label><input class="form-control" name="settings[queue_process_limit]" value="{{ old('settings.queue_process_limit', $values['queue_process_limit'] ?? 50) }}"></div>
                <div class="col-md-4"><label>OTP Expiry Minutes</label><input class="form-control" name="settings[otp_expiry_minutes]" value="{{ old('settings.otp_expiry_minutes', $values['otp_expiry_minutes'] ?? 5) }}"></div>
                <div class="col-md-4"><label>Max Retry Attempts</label><input class="form-control" name="settings[max_retry_attempts]" value="{{ old('settings.max_retry_attempts', $values['max_retry_attempts'] ?? 3) }}"></div>
            </div>
        </div>
    </div>

    <div class="box box-info">
        <div class="box-header with-border"><h3 class="box-title">DigitalWallet Integration Interface</h3></div>
        <div class="box-body">
            <div class="alert alert-info" style="margin-bottom:15px;">
                This is an independent wallet connector interface. It does not depend on the old Wallet module. Until the new DigitalWallet module is connected, the Null Wallet Connector can be used for testing.
            </div>
            <div class="row">
                <div class="col-md-4">
                    <label><input type="hidden" name="settings[wallet_charging_enabled]" value="0"><input type="checkbox" name="settings[wallet_charging_enabled]" value="1" {{ $checked('wallet_charging_enabled', false) }}> Enable Wallet Charging</label>
                </div>
                <div class="col-md-4">
                    <label><input type="hidden" name="settings[wallet_test_mode]" value="0"><input type="checkbox" name="settings[wallet_test_mode]" value="1" {{ $checked('wallet_test_mode', true) }}> Test Mode / Null Wallet</label>
                </div>
                <div class="col-md-4">
                    <label>Default Currency</label>
                    <input class="form-control" name="settings[default_currency]" value="{{ old('settings.default_currency', $values['default_currency'] ?? 'LKR') }}">
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-3"><label><input type="hidden" name="settings[charge_sms]" value="0"><input type="checkbox" name="settings[charge_sms]" value="1" {{ $checked('charge_sms', true) }}> Charge SMS</label></div>
                <div class="col-md-3"><label><input type="hidden" name="settings[charge_email]" value="0"><input type="checkbox" name="settings[charge_email]" value="1" {{ $checked('charge_email', false) }}> Charge Email</label></div>
                <div class="col-md-3"><label><input type="hidden" name="settings[charge_whatsapp]" value="0"><input type="checkbox" name="settings[charge_whatsapp]" value="1" {{ $checked('charge_whatsapp', true) }}> Charge WhatsApp</label></div>
                <div class="col-md-3"><label><input type="hidden" name="settings[charge_push]" value="0"><input type="checkbox" name="settings[charge_push]" value="1" {{ $checked('charge_push', false) }}> Charge Push</label></div>
            </div>
        </div>
        <div class="box-footer"><button class="btn btn-primary">Save Settings</button></div>
    </div>
</form>
</section>
@endsection
