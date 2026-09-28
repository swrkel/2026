@extends('layouts.app')
@section('title', $title ?? __('stocktakingnew::lang.module'))
@section('content')
@php($stkAssetVersion = config('stocktakingnew.asset_version', '20260728'))
<link rel="stylesheet" href="{{ asset('modules/stocktakingnew/css/stock-taking-new.css') }}?v={{ $stkAssetVersion }}">
<div class="stk-shell">
    <header class="stk-page-hero">
        <div>
            <div class="stk-eyebrow">INVENTORY CONTROL</div>
            <h1>@yield('stk_title', __('stocktakingnew::lang.module'))</h1>
            <p>@yield('stk_subtitle', __('stocktakingnew::lang.standalone'))</p>
        </div>
        @can('stock_taking_new.sessions.create')
            <a href="{{ route('stock-taking-new.sessions.create') }}" class="stk-btn stk-btn-primary">
                <i class="fa fa-plus"></i> New Stock Take
            </a>
        @endcan
    </header>
    @include('stocktakingnew::partials.navigation')
    @include('stocktakingnew::partials.alerts')
    @yield('stk_content')
</div>
<script src="{{ asset('modules/stocktakingnew/js/stock-taking-new.js') }}?v={{ $stkAssetVersion }}"></script>
@stack('stk_scripts')
@endsection
