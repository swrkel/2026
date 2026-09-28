{{--
    SW Payments.

    The six recording tabs, moved off SW Operators - that page had eleven tabs
    and did two unrelated jobs. Managing operators and recording a shift's cash
    are different work done by different people, so they are now different
    pages.

    The partials themselves are unchanged and shared: Collection Summary appears
    here and on SW Daily Shifts, from the same file.
--}}

@extends('layouts.app')

@section('title', __('sw::lang.sw_payments'))

@section('content')

<section class="content-header">
    <h1>@lang('sw::lang.sw_payments')
        <small>@lang('sw::lang.sw_payments_subtitle')</small>
    </h1>
</section>

@php
    /*
     | One entry per tab, in display order.
     |
     |   [ pane id, partial, label, permission ]
     |
     | A tab whose permission the user lacks is not rendered at all - neither
     | the heading nor the pane. Showing a heading that opens an empty panel is
     | worse than not showing it.
    */
    $swPaymentTabs = [
        ['sw_daily_cash', 'daily_cash', __('sw::lang.daily_cash'), 'sw.daily_cash.view', 'daily_cash'],
        ['sw_daily_credit_sales', 'daily_credit_sales', __('sw::lang.daily_credit_sales'), 'sw.daily_credit_sales.view', 'daily_credit_sales'],
        ['sw_daily_cards', 'daily_cards', __('sw::lang.daily_cards'), 'sw.daily_cards.view', 'daily_cards'],
        ['sw_daily_shortage_excess', 'daily_shortage_excess', __('sw::lang.daily_shortage_excess'), 'sw.daily_shortage_excess.view', 'daily_shortage_excess'],
        ['sw_daily_cheques', 'daily_cheques', __('sw::lang.daily_cheques'), 'sw.daily_cheques.view', 'daily_cheques'],
        ['sw_collection_summary', 'collection_summary', __('sw::lang.collection_summary'), 'sw.collection_summary.view', null],
    ];

    $swBusinessTabStates = $swBusinessTabStates ?? [];

    $swVisible = array_values(array_filter($swPaymentTabs, function ($tab) use ($swBusinessTabStates) {
        // Manage New is the business-level authority for the five recording
        // tabs. Collection Summary is intentionally outside that requested set.
        if (! empty($tab[4]) && empty($swBusinessTabStates[$tab[4]])) {
            return false;
        }

        return auth()->user()->can($tab[3]) || auth()->user()->can('superadmin');
    }));

    // The tab that opens: whatever a save asked for, else the first allowed.
    $swActive = session('status.tab') ?: ($swVisible[0][0] ?? null);
@endphp

@if (empty($swVisible))
    <section class="content">
        <div class="alert alert-warning">
            @lang('sw::lang.no_payment_tabs_permitted')
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
