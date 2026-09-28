@extends('layouts.app')

@php
    /*
     * Browser-tab title for every Rice Mill page.
     * Each child view already provides rcm-title; expose that same page name
     * to the host layout and also keep a small client-side fallback below so
     * older host layouts that do not render @yield('title') still show the
     * correct page name beside the favicon.
     */
    $__rcmPageTitle = trim($__env->yieldContent('rcm-title', 'Rice Mill Module'));
    $__rcmBrowserTitle = $__rcmPageTitle === 'Rice Mill Module'
        ? 'Rice Mill Module'
        : $__rcmPageTitle . ' | Rice Mill Module';
@endphp

@section('title', $__rcmBrowserTitle)

@section('content')
@php
    /*
     * v27 performance: use browser-cacheable public assets on normal installs.
     * Older parcels embedded the complete CSS/JS into every HTML response via
     * file_get_contents(), increasing response size and preventing browser cache.
     * Keep the source-file fallback only for installations that have not copied
     * the public module assets yet.
     */
    $rcmCssPublic = public_path('modules/ricemill/css/ricemill.css');
    $rcmJsPublic  = public_path('modules/ricemill/js/ricemill.js');
    $rcmCssSource = base_path('Modules/RiceMill/Resources/assets/css/ricemill.css');
    $rcmJsSource  = base_path('Modules/RiceMill/Resources/assets/js/ricemill.js');
@endphp

@php
    // If a newly deployed module CSS is newer than the previously published public
    // asset, use the module source immediately. This prevents an old public CSS
    // file from masking current Rice Mill UI fixes before assets are republished.
    $rcmUseSourceCss = is_file($rcmCssSource)
        && (!is_file($rcmCssPublic) || filemtime($rcmCssSource) > filemtime($rcmCssPublic));
@endphp

@if($rcmUseSourceCss)
    <style id="ricemill-module-styles">{!! file_get_contents($rcmCssSource) !!}</style>
@elseif(is_file($rcmCssPublic))
    <link rel="stylesheet" href="{{ asset('modules/ricemill/css/ricemill.css') }}?v={{ filemtime($rcmCssPublic) }}">
@elseif(is_file($rcmCssSource))
    <style id="ricemill-module-styles">{!! file_get_contents($rcmCssSource) !!}</style>
@else
    <link rel="stylesheet" href="{{ asset('modules/ricemill/css/ricemill.css') }}">
@endif

<div class="rcm-shell">
    <div class="rcm-topbar">
        <div class="rcm-topbar-main">
            <div class="rcm-brand-mark" aria-hidden="true">
                <i class="fa fa-industry"></i>
            </div>
            <div>
                <h1>@yield('rcm-title', 'Rice Mill Module')</h1>
                <small>@yield('rcm-subtitle', 'Rice Mill Management')</small>
            </div>
        </div>
        <div class="rcm-top-actions">@yield('rcm-actions')</div>
    </div>

    @if(session('status'))
        <div class="rcm-alert rcm-alert-success">
            <i class="fa fa-check-circle"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="rcm-alert rcm-alert-danger">
            <i class="fa fa-exclamation-circle"></i>
            <div>
                <strong>Please correct the following:</strong>
                <ul>
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @yield('rcm-content')
</div>

{{-- Prefer the module-owned JS source when present. This keeps changed-file
     deployments plug-and-play even if an older published public asset remains. --}}
@if(is_file($rcmJsSource))
    <script id="ricemill-module-script">{!! file_get_contents($rcmJsSource) !!}</script>
@elseif(is_file($rcmJsPublic))
    <script src="{{ asset('modules/ricemill/js/ricemill.js') }}?v={{ filemtime($rcmJsPublic) }}"></script>
@else
    <script src="{{ asset('modules/ricemill/js/ricemill.js') }}"></script>
@endif

<script id="ricemill-browser-title">
    (function () {
        var pageName = @json($__rcmPageTitle);
        var fallbackTitle = @json($__rcmBrowserTitle);
        var currentTitle = (document.title || '').trim();

        if (!pageName) {
            return;
        }

        // Preserve the host application's business/system suffix whenever it
        // already renders @yield('title'). If an older host layout ignores the
        // title section, prepend the Rice Mill page name to its existing title.
        if (currentTitle.toLowerCase().indexOf(pageName.toLowerCase()) === -1) {
            document.title = currentTitle
                ? pageName + ' | ' + currentTitle
                : fallbackTitle;
        }
    })();
</script>
@endsection
