@php
    $eggTitle = $title ?? 'Egg Management';
    $eggCssPublic = 'modules/egg-management/css/egg.css';
    $eggJsPublic = 'modules/egg-management/js/egg.js';
    $eggCssSource = base_path('Modules/EggManagement/Resources/assets/css/egg.css');
    $eggJsSource = base_path('Modules/EggManagement/Resources/assets/js/egg.js');
@endphp
@extends('layouts.app')
@section('title', $eggTitle)

@section('css')
@parent
@if(file_exists(public_path($eggCssPublic)))
<link rel="stylesheet" href="{{ asset($eggCssPublic) }}?v=20260917-1">
@elseif(file_exists($eggCssSource))
<style>{!! file_get_contents($eggCssSource) !!}</style>
@endif
@yield('egg_styles')
@endsection

@section('content')
<div class="egg-page">
    <div class="egg-page-head">
        <div class="egg-page-heading">
            <div class="egg-kicker">MODULE</div>
            <h1>{{ $eggTitle }}</h1>
            @hasSection('egg_subtitle')
                <div class="egg-page-subtitle">@yield('egg_subtitle')</div>
            @endif
        </div>
        <div class="egg-head-actions">@yield('head_actions')</div>
    </div>

    @if(session('success'))
        <div class="egg-alert egg-alert-success"><i class="fa fa-check-circle"></i><span>{{ session('success') }}</span></div>
    @endif
    @if($errors->any())
        <div class="egg-alert egg-alert-danger"><i class="fa fa-exclamation-circle"></i><span><strong>Please correct:</strong> {{ $errors->first() }}</span></div>
    @endif

    @yield('egg_content')
</div>
@endsection

@section('javascript')
@parent
@if(file_exists(public_path($eggJsPublic)))
<script src="{{ asset($eggJsPublic) }}?v=20260917-1"></script>
@elseif(file_exists($eggJsSource))
<script>{!! file_get_contents($eggJsSource) !!}</script>
@endif
@yield('egg_scripts')
@endsection
