@extends('layouts.app')

@section('title', $pageTitle ?? 'Banking CRM')

@push('css')
<link rel="stylesheet" href="{{ asset('modules/bankingcrm/css/bankingcrm.css') }}">
@endpush

@section('content')
    @yield('content')
@endsection

@push('javascript')
<script src="{{ asset('modules/bankingcrm/js/bankingcrm.js') }}"></script>
@endpush
