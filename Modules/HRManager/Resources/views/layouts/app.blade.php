@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/hrmanager/css/hrmanager.css') }}">
<div class="hrm-shell">
    <div class="hrm-header">
        <div>
            <h1>@yield('hrm_title', 'HR Manager')</h1>
            <p>@yield('hrm_subtitle', 'Standalone human resource management')</p>
        </div>
        <div class="hrm-actions">@yield('hrm_actions')</div>
    </div>
    @if(session('status'))<div class="hrm-alert">{{ session('status') }}</div>@endif
    @yield('hrm_content')
</div>
<script src="{{ asset('modules/hrmanager/js/hrmanager.js') }}"></script>
@endsection
