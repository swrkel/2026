@extends('beautysaloons::layout')
@section('beauty_content')
<div class="container-fluid bs-notifications">
    <h3>{{ __('beautysaloons::notifications.settings') }}</h3>
    <form method="POST" action="{{ route('beautysaloons.notifications.settings.save') }}" class="card card-body">
        @csrf
        <div class="row">
            <div class="col-md-3 form-group"><label><input type="checkbox" name="sms_enabled" value="1" {{ $setting->sms_enabled ? 'checked' : '' }}> SMS Enabled</label></div>
            <div class="col-md-3 form-group"><label><input type="checkbox" name="email_enabled" value="1" {{ $setting->email_enabled ? 'checked' : '' }}> Email Enabled</label></div>
            <div class="col-md-3 form-group"><label><input type="checkbox" name="push_enabled" value="1" {{ $setting->push_enabled ? 'checked' : '' }}> Push Enabled</label></div>
            <div class="col-md-3 form-group"><label><input type="checkbox" name="whatsapp_enabled" value="1" {{ $setting->whatsapp_enabled ? 'checked' : '' }}> WhatsApp Enabled</label></div>
            <div class="col-md-3 form-group"><label>Reminder Hours</label><input type="number" name="appointment_reminder_hours" class="form-control" value="{{ $setting->appointment_reminder_hours }}"></div>
            <div class="col-md-3 form-group"><label>Max Retry Count</label><input type="number" name="max_retry_count" class="form-control" value="{{ $setting->max_retry_count }}"></div>
            <div class="col-md-3 form-group"><label>SMS Sender Name</label><input name="sms_sender_name" class="form-control" value="{{ $setting->sms_sender_name }}"></div>
            <div class="col-md-3 form-group"><label>Email From Name</label><input name="email_from_name" class="form-control" value="{{ $setting->email_from_name }}"></div>
            <div class="col-md-6 form-group"><label>Email From Address</label><input name="email_from_address" class="form-control" value="{{ $setting->email_from_address }}"></div>
        </div>
        <button class="btn btn-success">Save Settings</button>
    </form>
</div>
@endsection
