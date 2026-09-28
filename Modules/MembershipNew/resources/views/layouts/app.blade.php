@extends('layouts.app')
@section('title', $title ?? __('membershipnew::messages.module_name'))

@section('content')
@include('membershipnew::partials.system-styles')
<section class="content-header mn-content-header"><h1 class="sr-only">{{ $title ?? __('membershipnew::messages.module_name') }}</h1></section>
<section class="content mn-system-ui">
<div class="mn-page">
    <div class="mn-hero">
        <div class="mn-hero-copy">
            <div class="mn-eyebrow">Membership System</div>
            <h1>{{ $title ?? __('membershipnew::messages.module_name') }}</h1>
            <p>{{ $subtitle ?? 'Manage membership, points, shares, dividends, payments and member activity from one workspace.' }}</p>
        </div>
        <div class="mn-hero-actions">
            @hasSection('page-actions')
                @yield('page-actions')
            @else
                <a class="mn-btn mn-btn-primary" href="{{ route('membership-new.dashboard') }}"><i class="fa fa-dashboard"></i> Dashboard</a>
                <a class="mn-btn mn-btn-purple" href="{{ route('membership-new.report-center.index') }}"><i class="fa fa-bar-chart"></i> Reports</a>
            @endif
        </div>
    </div>

    <nav class="mn-nav" aria-label="Membership New navigation">
        <a class="{{ request()->routeIs('membership-new.dashboard*') ? 'active' : '' }}" href="{{ route('membership-new.dashboard') }}"><i class="fa fa-dashboard"></i> Dashboard</a>
        <a class="{{ request()->routeIs('membership-new.members.*') ? 'active' : '' }}" href="{{ route('membership-new.members.index') }}"><i class="fa fa-users"></i> Members</a>
        <a class="{{ request()->routeIs('membership-new.plans.*') ? 'active' : '' }}" href="{{ route('membership-new.plans.index') }}"><i class="fa fa-id-badge"></i> Plans</a>
        <a class="{{ request()->routeIs('membership-new.payments.*') ? 'active' : '' }}" href="{{ route('membership-new.payments.index') }}"><i class="fa fa-money"></i> Payments</a>
        <a class="{{ request()->routeIs('membership-new.points.*') || request()->routeIs('membership-new.point-rules.*') ? 'active' : '' }}" href="{{ route('membership-new.points.index') }}"><i class="fa fa-star"></i> Points</a>
        <a class="{{ request()->routeIs('membership-new.shares.*') ? 'active' : '' }}" href="{{ route('membership-new.shares.index') }}"><i class="fa fa-pie-chart"></i> Shares</a>
        <a class="{{ request()->routeIs('membership-new.dividends.*') || request()->routeIs('membership-new.dividend-payouts.*') ? 'active' : '' }}" href="{{ route('membership-new.dividends.index') }}"><i class="fa fa-line-chart"></i> Dividends</a>
        <a class="{{ request()->routeIs('membership-new.report*') || request()->routeIs('membership-new.reports.*') ? 'active' : '' }}" href="{{ route('membership-new.report-center.index') }}"><i class="fa fa-bar-chart"></i> Reports</a>
        @can('membership_new.settings.view')
        <a class="{{ request()->routeIs('membership-new.settings.*') ? 'active' : '' }}" href="{{ route('membership-new.settings.index') }}"><i class="fa fa-cogs"></i> Membership Settings</a>
        @endcan
    </nav>

    @if(session('status')) <div class="alert alert-success mn-alert"><i class="fa fa-check-circle"></i> {{ session('status') }}</div> @endif
    @if(session('warning')) <div class="alert alert-warning mn-alert"><i class="fa fa-warning"></i> {{ session('warning') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger mn-alert"><i class="fa fa-exclamation-triangle"></i> {{ session('error') }}</div> @endif
    @if($errors->any()) <div class="alert alert-danger mn-alert"><i class="fa fa-exclamation-triangle"></i> {{ $errors->first() }}</div> @endif

    @yield('membership-content')
</div>
</section>
@include('membershipnew::partials.system-scripts')
@stack('scripts')
@endsection
