@extends('layouts.app')
@section('title', __('audit::audit.title'))
@section('css')
@parent
<link rel="stylesheet" href="{{ asset('modules/audit/css/audit.css') }}?v=1.0.20">
@endsection
@section('content')
<div class="audit-shell">
    <div class="audit-topbar">
        <div>
            <h1>@yield('audit-title', __('audit::audit.title'))</h1>
            <div class="audit-subtitle">Automated data integrity, reconciliation and system exception monitoring</div>
        </div>
        <div class="audit-nav">
            <a class="{{ request()->routeIs('audit.dashboard') ? 'active' : '' }}" href="{{ route('audit.dashboard') }}">Dashboard</a>
            <a class="{{ request()->routeIs('audit.run.*') ? 'active' : '' }}" href="{{ route('audit.run.index') }}">Run Audit</a>
            <a class="{{ request()->routeIs('audit.findings.*') ? 'active' : '' }}" href="{{ route('audit.findings.index') }}">Findings</a>
            <a class="{{ request()->routeIs('audit.rules.*') ? 'active' : '' }}" href="{{ route('audit.rules.index') }}">Rules</a>
            <a class="{{ request()->routeIs('audit.schedules.*') ? 'active' : '' }}" href="{{ route('audit.schedules.index') }}">Schedules</a>
            <a class="{{ request()->routeIs('audit.reports.*') ? 'active' : '' }}" href="{{ route('audit.reports.index') }}">Reports</a>
        </div>
    </div>
    @if(session('success'))<div class="audit-alert success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="audit-alert danger">{{ $errors->first() }}</div>@endif
    @yield('audit-content')
</div>
@endsection
@section('javascript')
@parent
<script src="{{ asset('modules/audit/js/audit.js') }}?v=1.0.20"></script>
@endsection
