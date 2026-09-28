@extends('layouts.app')
@section('title', $title ?? 'Banking Insurance')
@section('content')
<section class="content-header banking-insurance-header">
    <h1><i class="fa fa-shield"></i> Banking Insurance <small>{{ $subtitle ?? 'Standalone module' }}</small></h1>
</section>
<section class="content banking-insurance-module">
    @include('bankinginsurance::partials.nav')
    @yield('bankinginsurance_content')
</section>
@endsection
@section('javascript')
<script src="{{ asset('modules/bankinginsurance/js/banking-insurance.js') }}"></script>
@endsection
