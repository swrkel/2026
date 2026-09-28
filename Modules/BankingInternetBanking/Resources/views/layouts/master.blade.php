@extends('layouts.app')

@section('content')
<section class="content-header">
    <h1>{{ $title ?? 'Internet Banking' }}</h1>
</section>
<section class="content banking-internet-banking">
    @yield('banking_internet_content')
</section>
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('modules/bankinginternetbanking/css/internet-banking.css') }}">
@endsection

@section('javascript')
<script src="{{ asset('modules/bankinginternetbanking/js/internet-banking.js') }}"></script>
@endsection
