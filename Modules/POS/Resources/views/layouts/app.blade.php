@extends('layouts.app')

@section('title', $title ?? (trans()->has('pos::messages.pos_module') ? __('pos::messages.pos_module') : 'POS Module'))

@section('content')
@include('pos::partials.erp-standard-styles')
@yield('pos_styles')
<section class="content-header" style="padding-bottom:0;">
    <h1 class="sr-only">{{ $title ?? 'POS Module' }}</h1>
</section>
<section class="content pos-erp-standard-ui communication-hub-ui">
    <div class="ch-shell">
        <div class="ch-hero pos-module-hero">
            <div>
                <div class="ch-eyebrow">{{ trans()->has('pos::messages.module') ? __('pos::messages.module') : 'Module' }}</div>
                <h1>{{ $title ?? 'POS Module' }}</h1>
                <p>@yield('pos_page_description', "Welcome to POS Module. Here's what's happening in your business today.")</p>
            </div>
            <div class="ch-quick-actions">
                @hasSection('pos_page_actions')
                    @yield('pos_page_actions')
                @else
                    <a href="{{ route('pos.dashboard', [], false) }}" class="btn btn-default btn-sm"><i class="fa fa-dashboard"></i> {{ trans()->has('pos::messages.dashboard') ? __('pos::messages.dashboard') : 'Dashboard' }}</a>
                    <a href="{{ route('pos.sales.workspace', [], false) }}" class="btn btn-primary btn-sm"><i class="fa fa-shopping-cart"></i> {{ trans()->has('pos::messages.new_sale') ? __('pos::messages.new_sale') : 'New Sale' }}</a>
                    <a href="{{ route('pos.reports.index', [], false) }}" class="btn btn-success btn-sm"><i class="fa fa-bar-chart"></i> {{ trans()->has('pos::messages.reports') ? __('pos::messages.reports') : 'Reports' }}</a>
                @endif
            </div>
        </div>

        @if(session('status'))<div class="alert alert-success"><i class="fa fa-check-circle"></i> {{ session('status') }}</div>@endif
        @if(session('warning'))<div class="alert alert-warning"><i class="fa fa-warning"></i> {{ session('warning') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> {{ session('error') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> {{ $errors->first() }}</div>@endif

        @yield('pos_content')
        @yield('pos-content')
    </div>
</section>
@endsection

@section('javascript')
<script>window.POS_ROUTES={csrf:'{{ csrf_token() }}'};</script>
<script src="{{ route('pos.assets', ['type' => 'js', 'file' => 'pos.js', 'v' => 'is2238-20260910'], false) }}"></script>
<script src="{{ route('pos.assets', ['type' => 'js', 'file' => 'pos_page_001.js', 'v' => 'is2238-20260910'], false) }}"></script>
<script src="{{ route('pos.assets', ['type' => 'js', 'file' => 'pos_page_002.js', 'v' => 'is2238-20260910'], false) }}"></script>
<script src="{{ route('pos.assets', ['type' => 'js', 'file' => 'pos_page_003.js', 'v' => 'is2238-20260910'], false) }}"></script>
@yield('pos_scripts')
@endsection
