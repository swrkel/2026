@extends('layouts.app')

@section('content')
<section class="content-header">
    <h1>{{ $title ?? 'Banking Treasury' }}</h1>
</section>
<section class="content banking-treasury">
    @yield('banking_treasury_content')
</section>
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('modules/bankingtreasury/css/treasury.css') }}">
@endsection

@section('javascript')
<script src="{{ asset('modules/bankingtreasury/js/treasury.js') }}"></script>
@endsection
