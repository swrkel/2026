@extends('layouts.app')
@section('title', $title ?? 'Banking Tester UI')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'Banking Tester UI' }}</h1></section>
<section class="content banking-tester-ui">
    @yield('banking_tester_content')
</section>
@endsection
@section('css')
<link rel="stylesheet" href="{{ asset('modules/bankingtesterui/css/banking-tester-ui.css') }}">
@endsection
@section('javascript')
<script src="{{ asset('modules/bankingtesterui/js/banking-tester-ui.js') }}"></script>
@endsection
