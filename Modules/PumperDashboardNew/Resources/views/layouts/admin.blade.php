@extends('layouts.app')

@section('title', __('pumperdashboardnew::lang.module_name'))

@section('css')
<link rel="stylesheet" href="{{ route('pumper-dashboard-new.assets.show', ['type'=>'css','file'=>'pone.css','v'=>config('pumperdashboardnew.asset_version')]) }}">
@endsection

@section('content')
@php
    $__ponePageTitle = 'Operations';
    if (request()->routeIs('pumper-dashboard-new.admin.dashboard*')) $__ponePageTitle = 'Dashboard';
    elseif (request()->routeIs('pumper-dashboard-new.admin.operators.*')) $__ponePageTitle = 'Pump Operators';
    elseif (request()->routeIs('pumper-dashboard-new.admin.shifts.*') || request()->routeIs('pumper-dashboard-new.admin.assignments.*')) $__ponePageTitle = 'Shifts & Assignments';
    elseif (request()->routeIs('pumper-dashboard-new.admin.reconciliation.*')) $__ponePageTitle = 'Shortage / Excess';
    elseif (request()->routeIs('pumper-dashboard-new.admin.ledger.*')) $__ponePageTitle = 'Operator Ledger';
    elseif (request()->routeIs('pumper-dashboard-new.admin.documents.*')) $__ponePageTitle = 'Documents & Notes';
    elseif (request()->routeIs('pumper-dashboard-new.admin.login-attempts.*')) $__ponePageTitle = 'Blocked Logins';
    elseif (request()->routeIs('pumper-dashboard-new.admin.print-logs.*')) $__ponePageTitle = 'Print History';
    elseif (request()->routeIs('pumper-dashboard-new.admin.reports.*')) $__ponePageTitle = 'Reports';
    elseif (request()->routeIs('pumper-dashboard-new.admin.integration.*')) $__ponePageTitle = 'Petro PD-New Pairing';
    elseif (request()->routeIs('pumper-dashboard-new.admin.settings.*')) $__ponePageTitle = 'Settings';
@endphp
<div class="page-title-area no-print pone-system-page-title">
    <div class="row align-items-center">
        <div class="col-sm-12">
            <div class="breadcrumbs-area clearfix">
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('pumper-dashboard-new.admin.dashboard') }}">Pumper Dashboard-New</a></li>
                    <li><span>{{ $__ponePageTitle }}</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<section class="content main-content-inner pone-system-admin-page">
    <div class="pone-admin-wrap">
        @include('pumperdashboardnew::partials.flash')

        @if($errors->any())
            <div class="alert alert-danger pone-system-alert">
                <strong>Please correct the following:</strong>
                <ul class="pone-form-errors">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('pone_content')
    </div>
</section>

<div class="pone-processing" data-pone-processing>
    <div class="pone-processing-card">
        <span class="pone-spinner"></span>
        <span data-pone-processing-text>Processing your request…</span>
    </div>
</div>
@endsection

@section('javascript')
<script src="{{ route('pumper-dashboard-new.assets.show', ['type'=>'js','file'=>'pone.js','v'=>config('pumperdashboardnew.asset_version')]) }}"></script>
@stack('pone_js')
@endsection
