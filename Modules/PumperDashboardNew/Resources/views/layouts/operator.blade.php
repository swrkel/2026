<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('pumperdashboardnew::lang.module_name'))</title>
    <link rel="stylesheet" href="{{ route('pumper-dashboard-new.assets.show', ['type' => 'css', 'file' => 'pone.css', 'v' => config('pumperdashboardnew.asset_version')]) }}">
    <link rel="stylesheet" href="{{ route('pumper-dashboard-new.assets.show', ['type' => 'css', 'file' => 'operator-login-dashboard.css', 'v' => config('pumperdashboardnew.asset_version')]) }}">
    @stack('pone_css')
</head>
<body @yield('body_attributes')>
@php
    $hasShift = (int) session('pone.shift_id') > 0;
    $isDashboard = request()->routeIs('pumper-dashboard-new.operator.dashboard', 'pumper-dashboard-new.operator.dashboard.index');
@endphp
<div class="pone-shell{{ $isDashboard ? ' pone-shell-dashboard' : '' }}">
    @unless($isDashboard)
        <header class="pone-topbar pone-no-print">
            <div class="pone-topbar-inner">
                <a href="{{ route('pumper-dashboard-new.operator.dashboard') }}" class="pone-brand">
                    <span class="pone-brand-mark">P2</span>
                    <span>
                        <span class="pone-brand-title">{{ __('pumperdashboardnew::lang.module_name') }}</span>
                        <span class="pone-brand-subtitle">{{ session('business.name') ?: 'Pump Operator Display 2' }}</span>
                    </span>
                </a>
                <nav class="pone-nav">
                    <a class="{{ request()->routeIs('pumper-dashboard-new.operator.dashboard*') ? 'is-active' : '' }}" href="{{ route('pumper-dashboard-new.operator.dashboard') }}">Dashboard</a>
                    @if($hasShift)
                        <a class="{{ request()->routeIs('pumper-dashboard-new.operator.pumps.*') ? 'is-active' : '' }}" href="{{ route('pumper-dashboard-new.operator.pumps.index') }}">Pumps</a>
                        <a class="{{ request()->routeIs('pumper-dashboard-new.operator.payments.*') ? 'is-active' : '' }}" href="{{ route('pumper-dashboard-new.operator.payments.index') }}">Payments</a>
                        <a class="{{ request()->routeIs('pumper-dashboard-new.operator.other-sales.*') ? 'is-active' : '' }}" href="{{ route('pumper-dashboard-new.operator.other-sales.index') }}">Other Sales</a>
                        <a class="{{ request()->routeIs('pumper-dashboard-new.operator.unload-stock.*') ? 'is-active' : '' }}" href="{{ route('pumper-dashboard-new.operator.unload-stock.index') }}">Unload</a>
                        <a class="{{ request()->routeIs('pumper-dashboard-new.operator.day-entries.*') ? 'is-active' : '' }}" href="{{ route('pumper-dashboard-new.operator.day-entries.index') }}">Day Entries</a>
                        <a class="{{ request()->routeIs('pumper-dashboard-new.operator.shift.*') ? 'is-active' : '' }}" href="{{ route('pumper-dashboard-new.operator.shift.summary') }}">Shift</a>
                    @endif
                </nav>
                <div class="pone-user"><strong>{{ session('pone.operator_name') }}</strong><span>{{ $hasShift ? 'Active operator session' : 'Waiting for shift' }}</span></div>
                <form method="post" action="{{ route('pumper-dashboard-new.operator.logout') }}" data-processing-text="Signing out…">
                    @csrf
                    <button class="pone-logout" type="submit">Logout</button>
                </form>
            </div>
        </header>
    @endunless

    <main class="pone-main{{ $isDashboard ? ' pone-main-dashboard' : '' }}">
        @include('pumperdashboardnew::partials.flash')
        @if($errors->any())
            <div class="pone-alert pone-alert-danger">
                <strong>Please correct the following:</strong>
                <ul class="pone-form-errors">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('pone_content')
    </main>

    @unless($isDashboard)
        <footer class="pone-footer pone-no-print">Pumper Dashboard-New · Independent PONE operations · Petro PD-New pairing</footer>
    @endunless
</div>
<div class="pone-processing" data-pone-processing>
    <div class="pone-processing-card"><span class="pone-spinner"></span><span data-pone-processing-text>Processing your request…</span></div>
</div>
<script src="{{ route('pumper-dashboard-new.assets.show', ['type' => 'js', 'file' => 'pone.js', 'v' => config('pumperdashboardnew.asset_version')]) }}"></script>
<script src="{{ route('pumper-dashboard-new.assets.show', ['type' => 'js', 'file' => 'operator-login-dashboard.js', 'v' => config('pumperdashboardnew.asset_version')]) }}"></script>
@stack('pone_js')
</body>
</html>
