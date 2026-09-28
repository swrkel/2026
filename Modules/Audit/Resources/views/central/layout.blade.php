@extends('layouts.app')
@section('title', 'Central Audit')
@section('css')
@parent
<link rel="stylesheet" href="{{ asset('modules/audit/css/audit.css') }}?v=1.0.20">
@endsection
@section('content')
<div class="audit-shell audit-central-shell">
    <div class="audit-topbar audit-central-topbar">
        <div>
            <div class="audit-central-kicker">CENTRAL SYSTEM</div>
            <h1>@yield('audit-title', 'Central Audit')</h1>
            <div class="audit-subtitle">Read-only orchestration across the central database and tenant databases</div>
        </div>
        <div class="audit-nav">
            <a class="{{ request()->is('audit') ? 'active' : '' }}" href="{{ url('/audit') }}">Dashboard</a>
            <a class="{{ request()->is('audit/run') ? 'active' : '' }}" href="{{ url('/audit/run') }}">Run Audit</a>
            <a class="{{ request()->is('audit/findings') ? 'active' : '' }}" href="{{ url('/audit/findings') }}">Findings</a>
            <a class="{{ request()->is('audit/rules') ? 'active' : '' }}" href="{{ url('/audit/rules') }}">Rules</a>
            <a class="{{ request()->is('audit/schedules') ? 'active' : '' }}" href="{{ url('/audit/schedules') }}">Schedules</a>
            <a class="{{ request()->is('audit/reports*') ? 'active' : '' }}" href="{{ url('/audit/reports') }}">Reports</a>
        </div>
    </div>
    <div class="audit-alert central-safety"><strong>Central Audit safety:</strong> tenant databases are opened one at a time. Audit reads operational ERP data and writes only to each database's <code>audit_*</code> tables.</div>
    @if(session('success'))<div class="audit-alert success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="audit-alert danger">{{ $errors->first() }}</div>@endif
    @yield('audit-content')
</div>
@endsection
@section('javascript')
@parent
<script src="{{ asset('modules/audit/js/audit.js') }}?v=1.0.20"></script>
@endsection
