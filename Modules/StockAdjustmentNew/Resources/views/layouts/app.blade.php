@extends('layouts.app')

@section('title', __('stockadjustmentnew::lang.stock_adjustment_new'))

@section('content')
@php($sanAssetVersion = '20260820-is2065-fix1')
<link rel="stylesheet" href="{{ route('stock-adjustment-new.assets', ['type' => 'css', 'file' => 'stock-adjustment-new.css']) }}?v={{ $sanAssetVersion }}">

<div class="san-page">
    <div class="san-header">
        <div>
            <h1>@yield('san_title', __('stockadjustmentnew::lang.stock_adjustment_new'))</h1>
            <p>@yield('san_subtitle', __('stockadjustmentnew::lang.standalone_module'))</p>
        </div>
        <div class="san-actions">
            <a href="{{ route('stock-adjustment-new.dashboard') }}" class="btn btn-primary">Dashboard</a>
            <a href="{{ route('stock-adjustment-new.adjustments.index') }}" class="btn btn-info">Adjustments</a>
            <a href="{{ route('stock-adjustment-new.reasons.index') }}" class="btn btn-warning">Reasons</a>
            <a href="{{ route('stock-adjustment-new.settings.index') }}" class="btn btn-default san-settings-button">Settings</a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @yield('san_content')
</div>

<script src="{{ route('stock-adjustment-new.assets', ['type' => 'js', 'file' => 'stock-adjustment-new.js']) }}?v={{ $sanAssetVersion }}"></script>
@endsection
