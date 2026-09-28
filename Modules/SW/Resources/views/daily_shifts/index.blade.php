{{--
    SW Shift Operations.

    Cash Status, the shifts themselves, and Collection Summary.

    Collection Summary is here as well as on SW Payments - deliberately, so
    someone reconciling a shift can see the figures without moving to another
    page. Same partial, same data, included twice.
--}}

@extends('layouts.app')

@section('title', __('sw::lang.sw_shift_operations'))

@section('content')

<section class="content-header">
    <h1>@lang('sw::lang.sw_shift_operations')
        <small>@lang('sw::lang.sw_shift_operations_subtitle')</small>
    </h1>
</section>

@php
    $swShiftTabs = [
        ['sw_daily_cash_status', 'daily_cash_status', __('sw::lang.daily_cash_status'), 'sw.daily_cash_status.view'],
        ['sw_daily_shift', 'daily_shift', __('sw::lang.daily_shift'), 'sw.daily_shift.view'],
        ['sw_collection_summary', 'collection_summary', __('sw::lang.collection_summary'), 'sw.collection_summary.view'],
    ];

    $swVisible = array_values(array_filter($swShiftTabs, function ($tab) {
        return auth()->user()->can($tab[3]) || auth()->user()->can('superadmin');
    }));

    $swActive = session('status.tab') ?: ($swVisible[0][0] ?? null);
@endphp

@if (empty($swVisible))
    <section class="content">
        <div class="alert alert-warning">
            @lang('sw::lang.no_shift_tabs_permitted')
        </div>
    </section>
@else
    <ul class="nav nav-tabs" style="margin-bottom:0">
        @foreach ($swVisible as $tab)
            <li class="@if ($swActive === $tab[0]) active @endif">
                <a style="font-size:13px" href="#{{ $tab[0] }}" data-toggle="tab">{{ $tab[2] }}</a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach ($swVisible as $tab)
            <div class="tab-pane @if ($swActive === $tab[0]) active @endif" id="{{ $tab[0] }}">
                @includeIf('sw::operators.partials.' . $tab[1])
            </div>
        @endforeach
    </div>
@endif

@endsection
