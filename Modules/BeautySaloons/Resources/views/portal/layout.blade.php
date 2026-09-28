@extends('beautysaloons::layout')

@section('content')
<div class="bs-portal-wrapper">
    <div class="bs-portal-header">
        <h3>@yield('portal_title', 'Customer Portal')</h3>
        <nav class="bs-portal-nav">
            <a href="{{ route('beautysaloons.portal.dashboard') }}">Dashboard</a>
            <a href="{{ route('beautysaloons.portal.appointments.index') }}">Appointments</a>
            <a href="{{ route('beautysaloons.portal.wallet.index') }}">Wallet</a>
            <a href="{{ route('beautysaloons.portal.membership.index') }}">Membership</a>
            <a href="{{ route('beautysaloons.portal.packages.index') }}">Packages</a>
            <a href="{{ route('beautysaloons.portal.vouchers.index') }}">Vouchers</a>
            <a href="{{ route('beautysaloons.portal.loyalty.index') }}">Loyalty</a>
            <a href="{{ route('beautysaloons.portal.profile.edit') }}">Profile</a>
        </nav>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @yield('portal_content')
</div>
@endsection
