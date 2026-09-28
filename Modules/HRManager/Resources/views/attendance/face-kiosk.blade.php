@extends('hrmanager::layouts.app')
@section('hrm_title','Face Attendance Kiosk')
@section('hrm_subtitle','Camera sign in / sign out foundation')
@section('hrm_content')
<div class="hrm-grid two">
    <div class="hrm-card"><h3>Camera</h3><video id="hrmFaceVideo" autoplay muted playsinline class="hrm-video"></video><div class="mt-16"><button class="hrm-btn primary" id="hrmStartCamera">Start Camera</button><button class="hrm-btn" id="hrmStopCamera">Stop</button></div></div>
    <div class="hrm-card"><h3>Face Punch</h3><p>This page is ready for face-api.js or external AI provider integration. Current backend routes are already separated.</p><label>Employee ID<input id="hrmEmployeeId" placeholder="Employee ID"></label><label>Punch Type<select id="hrmPunchType"><option value="sign_in">Sign In</option><option value="sign_out">Sign Out</option></select></label><button class="hrm-btn primary" id="hrmFacePunch">Verify & Punch</button><div id="hrmFaceStatus" class="hrm-status"></div></div>
</div>
@endsection
