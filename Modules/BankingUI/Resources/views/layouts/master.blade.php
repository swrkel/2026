@extends('layouts.app')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'Banking UI' }}</h1></section>
<section class="content banking-ui-rc3">@yield('banking_content')</section>
@endsection
@section('javascript')
<script src="{{ asset('modules/bankingui/js/navigation-audit.js') }}"></script>
<script src="{{ asset('modules/bankingui/js/banking-test-manager.js') }}"></script>
@endsection
@section('css')
<link rel="stylesheet" href="{{ asset('modules/bankingui/css/banking-ui.css') }}">
<link rel="stylesheet" href="{{ asset('modules/bankingui/css/banking-test-manager.css') }}">
@endsection
