{{--
    Church Management layout.

    Structure mirrors pos::layouts.app so the two modules line up pixel for
    pixel: the ERP standard styles, then
    .communication-hub-ui .chc-erp-standard-ui > .ch-shell > .ch-hero, then
    alerts, the module tabs, and the page's own content.

    It extends the application layout, so Church Management sits inside the ERP
    shell with its sidebar and header - the decision on this ticket was that
    staff sign in once and reach this module like any other.

    Everything below that shell is the module's own. No other module's views,
    assets or classes are referenced, so removing any of them cannot change how
    this page renders.
--}}

@extends('layouts.app')

@section('title', $title ?? __('churchmanagement::lang.church_management'))

@section('content')

@include('churchmanagement::partials.erp-standard-styles')

<section class="content communication-hub-ui chc-erp-standard-ui">
    <div class="ch-shell">

        <div class="ch-hero">
            <div>
                <div class="ch-eyebrow">{{ __('churchmanagement::lang.church_management') }}</div>
                <h1>{{ $heading ?? ($title ?? __('churchmanagement::lang.church_management')) }}</h1>
                @isset($subheading)<p>{{ $subheading }}</p>@endisset
            </div>
            <div class="ch-quick-actions">
                @hasSection('chc_page_actions')
                    @yield('chc_page_actions')
                @else
                    <a href="{{ route('churchmanagement.members.index') }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-user-plus"></i> {{ __('churchmanagement::lang.members') }}</a>
                    <a href="{{ route('churchmanagement.families.index') }}" class="btn btn-default btn-sm">
                        <i class="fa fa-home"></i> {{ __('churchmanagement::lang.families') }}</a>
                @endif
            </div>
        </div>

        @include('churchmanagement::partials.nav')

        {{--
            Session messages use chc_ prefixed keys so a flash set by another
            module cannot surface on a Church Management page, and vice versa.
        --}}
        @if(session('chc_success'))
            <div class="alert alert-success"><i class="fa fa-check-circle"></i> {{ session('chc_success') }}</div>
        @endif
        @if(session('chc_error'))
            <div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> {{ session('chc_error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> {{ $errors->first() }}</div>
        @endif

        @yield('chc_content')

    </div>
</section>

@endsection

@section('javascript')
    @yield('chc_scripts')
@endsection
