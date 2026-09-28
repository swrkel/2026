@extends('hrmanager::layouts.master')

@section('content')
<div class="hr-page">
    <div class="hr-hero">
        <div>
            <span class="hr-eyebrow">Attendance Kiosk</span>
            <h1>Employee Sign In / Sign Out</h1>
            <p>Manual attendance now; face recognition foundation is ready for provider integration.</p>
        </div>
        <div class="hr-actions">
            <a href="{{ route('hr.attendance.index') }}" class="hr-btn hr-btn-light">Back to Attendance</a>
        </div>
    </div>

    <div class="hr-kiosk-grid">
        <div class="hr-card hr-kiosk-card">
            <h3>Manual Attendance</h3>
            <form method="POST" action="{{ route('hr.attendance.sign_in') }}">
                @csrf
                <input type="number" name="employee_id" placeholder="Employee ID" required>
                <input type="hidden" name="source" value="manual">
                <button class="hr-btn hr-btn-primary">Sign In</button>
            </form>

            <form method="POST" action="{{ route('hr.attendance.sign_out') }}">
                @csrf
                <input type="number" name="employee_id" placeholder="Employee ID" required>
                <input type="hidden" name="source" value="manual">
                <button class="hr-btn hr-btn-danger">Sign Out</button>
            </form>
        </div>

        <div class="hr-card hr-kiosk-card">
            <h3>Face Recognition</h3>
            <div class="hr-camera-box">
                <span>Camera Preview</span>
            </div>
            <p class="hr-muted">Face provider can be connected in HR-005 without changing manual attendance.</p>
            <button class="hr-btn hr-btn-primary" disabled>Start Face Scan</button>
        </div>
    </div>
</div>
@endsection
