{{--
    Layout for the Help Guide SUPER ADMIN pages.

    IS2166: restyled to the ERP Dashboard Standard, the same visual language the
    POS Dashboard uses - hero band, cards, toolbar, table and button treatments.

    HISTORY WORTH KEEPING
        These pages once extended helpguide::layouts.standalone, a complete
        <html> document with its own header. It rendered, but as a separate
        application: no ERP sidebar, no ERP header, different typography.

        A later attempt to adopt the house look borrowed the ch-* classes from
        POS directly. Those classes were not in the live tree at the time, so the
        hero rendered as an empty white band and the work was reverted with a
        note to keep styling local to this module.

        That note still holds, and this change respects it: the standard is
        included from helpguide::partials.erp-standard-styles - a copy owned by
        this module - not from pos::. Help Guide therefore keeps its appearance
        on an install where POS is absent, disabled or restyled.

    STRUCTURE
        .communication-hub-ui .hg-erp-standard-ui > .ch-shell > .ch-hero, then
        alerts, then the page's own content. This mirrors pos::layouts.app so
        the two modules line up pixel for pixel.

    The public Help Guide still uses layouts.standalone, deliberately - a
    full-width reading surface is right for articles.
--}}

@extends('layouts.app')

@section('title', $title ?? 'Help Guide')

@section('content')

@push('css')
{{--
    IS2153: the asset path had "public/" twice.

    asset('public/plugins/...') resolved to
        https://nivasa.shop/public/public/plugins/summernote/summernote-lite.min.js
    because APP_URL already points at the public root. That 404s, so Summernote
    never loaded and the editor fell back to the plain <textarea> underneath.

    A missing script tag fails silently and the textarea still accepts text, so
    the editor looked "basic" rather than broken. Every core layout uses
    asset('plugins/...') without the prefix - that is the convention here.
--}}
<link rel="stylesheet" href="{{ asset('plugins/summernote/summernote-lite.min.css') }}">

@include('helpguide::partials.erp-standard-styles')

<style>
/*
 * Layout-only rules. Everything that carries the house look now lives in the
 * partial above; what remains here is structural - page padding, the grid the
 * article form uses, and a couple of spacing helpers.
 */
.hg-page{padding:0}
.hg-head-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.hg-field{margin-bottom:14px}
.hg-field textarea{min-height:90px}
.hg-filter{position:relative;min-width:240px}
.hg-filter .fa{position:absolute;left:12px;top:12px;color:#94a3b8;font-size:12px;z-index:2}
.hg-table{width:100%;border-collapse:collapse;font-size:13px}
@media(max-width:900px){.hg-head-actions{width:100%}}
</style>
@endpush

{{--
    communication-hub-ui carries the ERP standard itself; hg-erp-standard-ui
    carries the bridge that restyles this module's own classes. Both are needed,
    and both are scoped, so nothing leaks into other modules.
--}}
<section class="content communication-hub-ui hg-erp-standard-ui">
    <div class="ch-shell">

        {{-- Hero band, matching the POS Dashboard. --}}
        <div class="ch-hero">
            <div>
                <div class="ch-eyebrow">Help Guide</div>
                <h1>{{ $heading ?? ($title ?? 'Help Guide') }}</h1>
                @isset($subheading)<p>{{ $subheading }}</p>@endisset
            </div>
            <div class="ch-quick-actions hg-head-actions">
                <a href="{{ route('helpguide.superadmin.index') }}" class="btn btn-default btn-sm">
                    <i class="fa fa-th-large"></i> Modules</a>
                <a href="{{ route('helpguide.superadmin.articles.list') }}" class="btn btn-default btn-sm">
                    <i class="fa fa-file-text-o"></i> List Articles</a>
                <a href="{{ route('helpguide.superadmin.articles') }}" class="btn btn-default btn-sm">
                    <i class="fa fa-plus"></i> Add Article</a>
                <a href="{{ url('/help-guide') }}" class="btn btn-primary btn-sm" target="_blank">
                    <i class="fa fa-external-link"></i> View Help Guide</a>
            </div>
        </div>

        @if(session('hg_success'))
            <div class="alert alert-success"><i class="fa fa-check-circle"></i> {{ session('hg_success') }}</div>
        @endif
        @if(session('hg_error'))
            <div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> {{ session('hg_error') }}</div>
        @endif

        <div class="hg-page">
            @yield('hg_content')
        </div>

    </div>
</section>

@endsection

@push('javascript')
{{-- IS2153: see the stylesheet note above - the "public/" prefix is dropped. --}}
<script src="{{ asset('plugins/summernote/summernote-lite.min.js') }}"></script>
@stack('hg_scripts')
@endpush
