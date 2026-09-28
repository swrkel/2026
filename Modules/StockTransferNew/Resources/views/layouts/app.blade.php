@extends('layouts.app')

@section('title', __('stocktransfernew::stocktransfer.module_name'))

@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew.css') }}?v=20260712-1">

<div class="stn-module">
    <div class="stn-container">
        @if(session('status'))
            <div class="alert alert-success stn-system-alert">
                <i class="fa fa-check-circle"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger stn-system-alert">
                <i class="fa fa-exclamation-circle"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @yield('stocktransfernew_content')
    </div>
</div>

<script src="{{ asset('modules/stocktransfernew/js/stocktransfernew.js') }}?v=20260712-1"></script>
@endsection
