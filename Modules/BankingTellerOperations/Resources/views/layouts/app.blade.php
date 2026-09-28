@extends('layouts.app')
@section('content')
<div class="container-fluid bkg-teller">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">Banking Teller Operations</h3>
            <small class="text-muted">BKG-CORE-002 standalone module</small>
        </div>
        <a href="{{ route('banking.teller.dashboard') }}" class="btn btn-primary">Dashboard</a>
    </div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @yield('module-content')
</div>
@endsection
