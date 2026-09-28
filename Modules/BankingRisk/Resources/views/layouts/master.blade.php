@extends('layouts.app')

@section('title', $pageTitle ?? 'Enterprise Risk Management')

@push('css')
<link rel="stylesheet" href="{{ asset('modules/bankingrisk/css/bankingrisk.css') }}">
@endpush

@section('content')
    @yield('content')
@endsection

@push('javascript')
<script src="{{ asset('modules/bankingrisk/js/bankingrisk.js') }}"></script>
@endpush
