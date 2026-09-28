@extends('layouts.app')

@section('title', $pageTitle ?? __('managementreport::lang.module_name'))

@section('css')
    @parent
    <link rel="stylesheet" href="{{ asset('modules/management-report/css/management-report.css?v=2.6.2') }}">
    @yield('managementreport_css')
@endsection

@section('content')
<section class="content-header" style="padding-bottom:0;">
    <h1 class="sr-only">{{ $pageTitle ?? __('managementreport::lang.module_name') }}</h1>
</section>
<section class="content mgmt-pos-standard-ui communication-hub-ui">
    <div class="mgmt-shell ch-shell">
        <div class="mgmt-page-header ch-hero mgmt-module-hero">
            <div class="mgmt-page-heading">
                <div class="mgmt-eyebrow">Module</div>
                <h1>{{ $pageTitle ?? __('managementreport::lang.module_name') }}</h1>
                <p>{{ $pageSubtitle ?? __('managementreport::lang.module_subtitle') }}</p>
            </div>
            <div class="mgmt-page-actions ch-quick-actions">@yield('page_actions')</div>
        </div>

        @if(session('success'))
            <div class="alert alert-success mgmt-alert"><i class="fa fa-check-circle"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger mgmt-alert"><i class="fa fa-exclamation-triangle"></i> {{ session('error') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning mgmt-alert"><i class="fa fa-warning"></i> {{ session('warning') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger mgmt-alert">
                <i class="fa fa-exclamation-triangle"></i>
                <strong>Please correct the following:</strong>
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @yield('managementreport_content')
    </div>
</section>
@endsection

@section('javascript')
    @parent
    <script src="{{ asset('modules/management-report/js/management-report.js?v=2.6.0') }}"></script>
    @yield('managementreport_js')
@endsection
