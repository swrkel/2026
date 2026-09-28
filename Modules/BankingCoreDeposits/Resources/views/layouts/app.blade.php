@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/banking-core-deposits/css/core-deposits.css') }}">
<div class="bkg-core-deposits">
    <div class="bkg-page-header"><h3>@yield('page-title','Banking Core Deposits')</h3></div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @yield('module-content')
</div>
<script src="{{ asset('modules/banking-core-deposits/js/core-deposits.js') }}"></script>
@endsection
