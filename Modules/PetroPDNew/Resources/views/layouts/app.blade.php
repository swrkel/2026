@extends('layouts.app')

@section('css')
    @include('petropdnew::layouts.partials.styles')
    @stack('pdnew_css')
@endsection

@section('content')
@php
    $__pdnewPageTitle = trim($__env->yieldContent('page_title')) ?: 'Petro PD-New';
@endphp

<div class="page-title-area no-print pdn-system-page-title">
    <div class="row align-items-center">
        <div class="col-sm-12">
            <div class="breadcrumbs-area clearfix">
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('petro-pd-new.dashboard') }}">Petro PD-New</a></li>
                    <li><span>{{ $__pdnewPageTitle }}</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<section class="content main-content-inner pdn-system-page">
    @include('petropdnew::partials.flash')

    @if($errors->any())
        <div class="alert alert-danger pdn-system-alert" role="alert">
            <strong>Please correct the following:</strong>
            <ul class="pdn-error-list">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('pdnew_content')
</section>
@endsection

@section('javascript')
    @include('petropdnew::layouts.partials.scripts')
    @stack('pdnew_js')
@endsection
