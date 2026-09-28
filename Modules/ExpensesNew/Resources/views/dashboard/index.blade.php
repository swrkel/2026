@extends('expensesnew::layouts.app', ['heading'=>'Expenses-New Dashboard'])

@section('module_content')
{{--
    MA-002 (S-615 #1): the dashboard, matching the POS one.

    Same shape POS uses - ch-kpi-grid, ch-kpi, ch-icon, label-text, value -
    so the modules read as one system.

    THE CSS IS COPIED, NOT INCLUDED. POS keeps these rules in
    pos::partials.erp-standard-styles, and including that would make this
    dashboard break whenever the POS module is switched off for a business.
    Everything is scoped to .exn-dash so it cannot reach another screen.

    The figures were already being calculated by DashboardController - today,
    this month, total due and the record count. Only the presentation changes;
    no query is touched.
--}}
<div class="exn-dash">
<style>
    .exn-dash .ch-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(170px, 1fr));
        gap: 20px;
        margin-bottom: 22px;
    }

    @media (max-width: 991px) { .exn-dash .ch-kpi-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 575px) { .exn-dash .ch-kpi-grid { grid-template-columns: 1fr; } }

    .exn-dash .ch-kpi {
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        border-radius: 18px;
        border: 1px solid #dbe7f3;
        padding: 18px 20px 20px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 26px rgba(15, 23, 42, .06);
        transition: transform .18s ease, box-shadow .18s ease;
        height: 100%;
    }

    .exn-dash .ch-kpi:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 42px rgba(15, 23, 42, .12);
    }

    .exn-dash .ch-kpi:before {
        content: "";
        position: absolute;
        left: 0; right: 0; bottom: 0;
        height: 3px;
        background: #2563eb;
    }

    .exn-dash .ch-kpi.tone-today:before { background: #2563eb; }
    .exn-dash .ch-kpi.tone-month:before { background: #0ea5e9; }
    .exn-dash .ch-kpi.tone-due:before   { background: #f43f5e; }
    .exn-dash .ch-kpi.tone-count:before { background: #10b981; }

    .exn-dash .ch-kpi-top { display: flex; align-items: center; gap: 12px; }

    .exn-dash .ch-icon {
        width: 52px; height: 52px;
        border-radius: 15px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
        color: #1d4ed8; background: #eff6ff;
        flex: 0 0 52px;
    }

    .exn-dash .tone-month .ch-icon { color: #0369a1; background: #e0f2fe; }
    .exn-dash .tone-due   .ch-icon { color: #be123c; background: #ffe4e6; }
    .exn-dash .tone-count .ch-icon { color: #047857; background: #d1fae5; }

    .exn-dash .ch-kpi .label-text {
        font-size: 13px; font-weight: 600; color: #64748b; letter-spacing: .2px;
    }

    .exn-dash .ch-kpi .value {
        font-size: 30px; font-weight: 700; color: #0f172a;
        margin-top: 14px; line-height: 1.1;
    }

    .exn-dash .ch-kpi.tone-due .value { color: #be123c; }

    .exn-dash .ch-kpi .hint { font-size: 12px; color: #94a3b8; margin-top: 6px; }

    .exn-dash .exn-quick {
        background: #fff;
        border: 1px solid #dbe7f3;
        border-radius: 16px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .05);
        padding: 16px 20px 18px;
    }

    .exn-dash .exn-quick h4 {
        margin: 0 0 12px; font-size: 15px; font-weight: 700; color: #0f172a;
    }

    .exn-dash .exn-quick .btn { margin: 0 8px 8px 0; border-radius: 8px; }
</style>

@php
    /*
     * Laid out as data so the four cards cannot drift apart in markup, the
     * way the POS dashboard does it.
     *
     * The three money figures use the business currency precision - they ARE
     * money, unlike the tank counts on the Petro dashboard. The record count
     * is a plain integer.
     */
    $exnCards = [
        [
            'label' => 'Today',
            'value' => @num_format($cards['today'] ?? 0),
            'icon'  => 'fa-calendar-o',
            'tone'  => 'tone-today',
            'hint'  => 'Expenses recorded today',
        ],
        [
            'label' => 'This Month',
            'value' => @num_format($cards['month'] ?? 0),
            'icon'  => 'fa-calendar',
            'tone'  => 'tone-month',
            'hint'  => now()->format('F Y'),
        ],
        [
            'label' => 'Total Due',
            'value' => @num_format($cards['due'] ?? 0),
            'icon'  => 'fa-exclamation-circle',
            'tone'  => 'tone-due',
            'hint'  => 'Outstanding across all expenses',
        ],
        [
            'label' => 'Records',
            'value' => number_format((int) ($cards['count'] ?? 0)),
            'icon'  => 'fa-list',
            'tone'  => 'tone-count',
            'hint'  => 'Expense entries in total',
        ],
    ];
@endphp

<div class="ch-kpi-grid">
    @foreach($exnCards as $exnCard)
        <div class="ch-kpi {{ $exnCard['tone'] }}">
            <div class="ch-kpi-top">
                <div class="ch-icon"><i class="fa {{ $exnCard['icon'] }}"></i></div>
                <div class="label-text">{{ $exnCard['label'] }}</div>
            </div>
            <div class="value">{{ $exnCard['value'] }}</div>
            <div class="hint">{{ $exnCard['hint'] }}</div>
        </div>
    @endforeach
</div>

<div class="exn-quick">
    <h4>Quick actions</h4>
    <a class="btn btn-primary" href="{{ route('expensesnew.expenses.create') }}">
        <i class="fa fa-plus"></i> Add Expense
    </a>
    <a class="btn btn-info" href="{{ route('expensesnew.expenses.index') }}">
        <i class="fa fa-list"></i> List Expenses
    </a>
    <a class="btn btn-warning" href="{{ route('expensesnew.categories.index') }}">
        <i class="fa fa-tags"></i> Categories
    </a>
    <a class="btn btn-success" href="{{ route('expensesnew.reports.expense_summary') }}">
        <i class="fa fa-bar-chart"></i> Reports
    </a>
    <a class="btn btn-default" href="{{ route('expensesnew.settings.index') }}">
        <i class="fa fa-cog"></i> Settings
    </a>
</div>
</div>
@endsection
