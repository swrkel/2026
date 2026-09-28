@extends('layouts.app')

@section('title', trim($__env->yieldContent('pcn_page_title')) ?: __('pricechangenew::lang.module_name'))

@section('content')
@include('pricechangenew::partials.erp-standard-styles')
<link rel="stylesheet" href="{{ asset('modules/pricechangenew/css/pricechangenew.css?v=' . config('pricechangenew.asset_version')) }}">

<section class="content-header" style="padding-bottom:0;">
    <h1 class="sr-only">@yield('pcn_page_title', __('pricechangenew::lang.module_name'))</h1>
</section>

<section class="content pcn-module pcn-erp-standard-ui pos-erp-standard-ui communication-hub-ui">
    <div class="ch-shell">
        <div class="ch-hero pcn-module-hero">
            <div>
                <div class="ch-eyebrow">Price Change Module</div>
                <h1>@yield('pcn_page_title', __('pricechangenew::lang.module_name'))</h1>
                <p>@yield('pcn_page_subtitle', 'Controlled product pricing for the logged-in business and permitted locations.')</p>
            </div>
            <div class="ch-quick-actions no-print">
                @hasSection('pcn_page_actions')
                    @yield('pcn_page_actions')
                @else
                    <a href="{{ route('pricechangenew.dashboard') }}" class="btn btn-default btn-sm"><i class="fa fa-dashboard"></i> Dashboard</a>
                    @can('pricechangenew.changes.create')
                        <a href="{{ route('pricechangenew.changes.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Price Change</a>
                    @endcan
                    @can('pricechangenew.changes.view')
                        <a href="{{ route('pricechangenew.changes.index') }}" class="btn btn-success btn-sm"><i class="fa fa-list"></i> List Price Changes</a>
                    @endcan
                @endif
            </div>
        </div>

        @if(session('status'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <i class="fa fa-check-circle"></i> {{ session('status') }}
            </div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <i class="fa fa-warning"></i> {{ session('warning') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <i class="fa fa-exclamation-triangle"></i> {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <strong><i class="fa fa-exclamation-triangle"></i> Please correct the following:</strong>
                <ul class="pcn-error-list">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @yield('pcn_content')
    </div>
</section>
@endsection

@section('javascript')
<script>
window.PCN_ROUTES = {
    csrf: @json(csrf_token()),
    dashboard: @json(route('pricechangenew.dashboard')),
    changes: @json(route('pricechangenew.changes.index'))
};
</script>
<script src="{{ asset('modules/pricechangenew/js/pricechangenew.js?v=' . config('pricechangenew.asset_version')) }}"></script>
@yield('pcn_scripts')
@endsection
