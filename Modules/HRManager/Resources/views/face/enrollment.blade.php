@extends('hrmanager::layouts.master')

@section('content')
<div class="hr-page">
    <div class="hr-hero">
        <div>
            <span class="hr-eyebrow">Face Enrollment</span>
            <h1>Register Employee Face Profile</h1>
            <p>Employee consent is recorded before starting the enrollment session.</p>
        </div>
        <div class="hr-actions">
            <a href="{{ route('hr.face.dashboard') }}" class="hr-btn hr-btn-light">Back</a>
        </div>
    </div>

    <div class="hr-grid-2">
        <div class="hr-card">
            <h3>Enrollment Form</h3>
            <form method="POST" action="{{ route('hr.face.enrollment.start') }}" class="hr-form">
                @csrf
                <label>Employee ID</label>
                <input type="number" name="employee_id" required placeholder="Enter Employee ID">

                <label>Device ID</label>
                <input type="number" name="device_id" placeholder="Optional">

                <div class="hr-consent-box">
                    <strong>Consent Notice</strong>
                    <p>Employee agrees to use face verification only for HR attendance sign-in/sign-out.</p>
                </div>

                <button class="hr-btn hr-btn-primary">Accept Consent & Start Enrollment</button>
            </form>
        </div>

        <div class="hr-card">
            <h3>Camera Capture</h3>
            <div class="hr-camera-box">
                <span>Camera preview placeholder</span>
            </div>
            <p class="hr-muted">Actual browser camera/provider integration will be connected without changing the HR tables.</p>
        </div>
    </div>
</div>
@endsection
