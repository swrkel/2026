@extends('layouts.app')

@hasSection('title')
@else
    @section('title', __('leadsnew::messages.leads_new'))
@endif

@section('content')
    @include('leadsnew::partials.styles')

    <section class="content leads-new-erp-ui communication-hub-ui">
        <div class="ch-shell">
            <div class="ch-hero leads-new-module-hero">
                <div class="ch-hero-copy">
                    <div class="ch-eyebrow">{{ __('leadsnew::messages.lead_management') }}</div>
                    <h1>@yield('title', __('leadsnew::messages.leads_new'))</h1>
                    <p>@yield('leadsnew_subtitle', __('leadsnew::messages.dashboard_welcome'))</p>
                </div>
                <div class="ch-quick-actions">
                    <a href="{{ url('/leads-new') }}" class="btn btn-default btn-sm">
                        <i class="fa fa-dashboard"></i> {{ __('leadsnew::messages.dashboard') }}
                    </a>
                    <a href="{{ url('/leads-new/leads/create') }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-plus-circle"></i> {{ __('leadsnew::messages.add_lead') }}
                    </a>
                    <a href="{{ url('/leads-new/reports') }}" class="btn btn-success btn-sm">
                        <i class="fa fa-bar-chart"></i> {{ __('leadsnew::messages.reports') }}
                    </a>
                </div>
            </div>

            @if(session('status'))
                <div class="alert alert-success alert-dismissible ln-alert">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="fa fa-check-circle"></i>
                    {{ is_array(session('status')) ? (session('status')['msg'] ?? json_encode(session('status'))) : session('status') }}
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible ln-alert">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="fa fa-warning"></i> {{ session('warning') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible ln-alert">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="fa fa-exclamation-triangle"></i> {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible ln-alert">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>{{ __('messages.something_went_wrong') }}</strong>
                    <ul class="ln-error-list">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="ln-page-body">
                @yield('leadsnew_content')
                @hasSection('content_inner')
                    @yield('content_inner')
                @endif
            </div>
        </div>
    </section>
@endsection

@section('javascript')
    @parent
    <script>
        (function ($) {
            'use strict';

            $(function () {
                $('.leads-new-erp-ui select.select2').each(function () {
                    if ($.fn.select2 && !$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({ width: '100%' });
                    }
                });

                $(document).on('input', '.ln-instant-search, .js-ln-table-search, .leads-new-search', function () {
                    var term = String($(this).val() || '').toLowerCase();
                    var target = $(this).data('target');
                    var selector = target ? target + ' tbody tr' : '.ln-searchable-table tbody tr, .leads-new-datatable tbody tr';
                    if (target && target.indexOf(' tbody') !== -1) { selector = target; }
                    $(selector).each(function () {
                        $(this).toggle($(this).text().toLowerCase().indexOf(term) !== -1);
                    });
                });

                $(document).on('click', '.leads-new-print', function () {
                    window.print();
                });
            });
        })(window.jQuery || function () {});
    </script>
    @yield('leadsnew_javascript')
@endsection
