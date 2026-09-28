@extends('layouts.app')

@section('title', $pageTitle ?? 'AML & Compliance')

@push('css')
<link rel="stylesheet" href="{{ asset('modules/bankingaml/css/bankingaml.css') }}">
@endpush

@section('content')
    @yield('content')
@endsection

@push('javascript')
<script src="{{ asset('modules/bankingaml/js/bankingaml.js') }}"></script>
@endpush
