{{--
    SW Operators.

    Just the operators now. The six recording tabs moved to SW Payments and the
    shift tabs to SW Daily Shifts - eleven tabs on one page was two unrelated
    jobs sharing a screen.

    Settings is hidden until it does something. A tab that opens on nothing
    teaches people to distrust the others.
--}}

@extends('layouts.app')

@section('title', __('sw::lang.sw_operators'))

@section('content')

<section class="content-header">
    <h1>@lang('sw::lang.sw_operators')
        <small>@lang('sw::lang.sw_operators_subtitle')</small>
    </h1>
</section>

@php
    $swOperatorTabs = [
        ['sw_pump_operators', 'pump_operators', __('sw::lang.pump_operators'), 'sw.operators.view'],
        ['sw_excess_shortage', 'excess_shortage_payments', __('sw::lang.excess_shortage_payments'), 'sw.excess_shortage.view'],
    ];

    $swVisible = array_values(array_filter($swOperatorTabs, function ($tab) {
        return auth()->user()->can($tab[3]) || auth()->user()->can('superadmin');
    }));

    $swActive = session('status.tab') ?: ($swVisible[0][0] ?? null);
@endphp

@if (empty($swVisible))
    <section class="content">
        <div class="alert alert-warning">@lang('sw::lang.no_operator_tabs_permitted')</div>
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
