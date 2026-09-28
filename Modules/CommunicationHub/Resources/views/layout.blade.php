@extends('layouts.app')

@section('content')
@include('communicationhub::partials.professional-styles')
<section class="content-header" style="padding-bottom:0;">
    <h1 class="sr-only">@yield('communicationhub_title', 'Communication Hub')</h1>
</section>
<section class="content communication-hub-ui">
    <div class="ch-shell">
        <div class="ch-hero">
            <div>
                <div class="ch-eyebrow">Communication Hub</div>
                <h1>@yield('communicationhub_title', 'Communication Hub')</h1>
                <p>Commercial communication platform for SMS, Email, WhatsApp, campaigns, wallets, billing and versioned APIs.</p>
            </div>
            <div class="ch-quick-actions">
                <a href="{{ route('communicationhub.dashboard') }}" class="btn btn-default btn-sm"><i class="fa fa-dashboard"></i> Dashboard</a>
                <a href="{{ route('communicationhub.commercial.send_sms') }}" class="btn btn-success btn-sm"><i class="fa fa-paper-plane"></i> Send SMS</a>
                <a href="{{ route('communicationhub.commercial.sms_packages') }}" class="btn btn-primary btn-sm"><i class="fa fa-cubes"></i> Packages</a>
            </div>
        </div>

        @if(session('status'))<div class="alert alert-success"><i class="fa fa-check-circle"></i> {{ session('status') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> {{ session('error') }}</div>@endif
        @yield('communicationhub_content')
    </div>
</section>
@endsection
