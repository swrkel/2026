@extends('layouts.app')

@section('content')
<section class="content-header">
    <h1>{{ $title ?? 'Payments Hub' }}</h1>
</section>
<section class="content banking-payments-hub">
    @yield('banking_payments_content')
</section>
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('modules/bankingpaymentshub/css/payments-hub.css') }}">
@endsection

@section('javascript')
<script src="{{ asset('modules/bankingpaymentshub/js/payments-hub.js') }}"></script>
@endsection
