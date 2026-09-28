{{--
    MA-002: the three summary cards, matching the POS dashboard.

    Same shape POS uses - ch-kpi-grid, ch-kpi, ch-icon, label-text, value,
    hint - so the two dashboards read as one system rather than two.

    THE CSS IS COPIED, NOT INCLUDED. POS keeps these rules in
    pos::partials.erp-standard-styles. Pulling that in would make the Petro
    General dashboard depend on the POS module being enabled - exactly the
    kind of cross-module coupling that produced the "module disabled" error
    on the tank form. Everything below is scoped to .pg-dash, so it cannot
    reach any other screen either.
--}}
<style>
    .pg-dash .ch-kpi-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(170px, 1fr));
        gap: 20px;
        margin-bottom: 22px;
    }

    @media (max-width: 767px) {
        .pg-dash .ch-kpi-grid { grid-template-columns: 1fr; }
    }

    .pg-dash .ch-kpi {
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

    .pg-dash .ch-kpi:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 42px rgba(15, 23, 42, .12);
    }

    .pg-dash .ch-kpi:before {
        content: "";
        position: absolute;
        left: 0; right: 0; bottom: 0;
        height: 3px;
        background: #2563eb;
    }

    .pg-dash .ch-kpi.tone-tank:before  { background: #0ea5e9; }
    .pg-dash .ch-kpi.tone-pump:before  { background: #f59e0b; }
    .pg-dash .ch-kpi.tone-staff:before { background: #10b981; }

    .pg-dash .ch-kpi-top { display: flex; align-items: center; gap: 12px; }

    .pg-dash .ch-icon {
        width: 52px;
        height: 52px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #1d4ed8;
        background: #eff6ff;
        flex: 0 0 52px;
    }

    .pg-dash .tone-tank  .ch-icon { color: #0369a1; background: #e0f2fe; }
    .pg-dash .tone-pump  .ch-icon { color: #b45309; background: #fef3c7; }
    .pg-dash .tone-staff .ch-icon { color: #047857; background: #d1fae5; }

    .pg-dash .ch-kpi .label-text {
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        letter-spacing: .2px;
    }

    .pg-dash .ch-kpi .value {
        font-size: 32px;
        font-weight: 700;
        color: #0f172a;
        margin-top: 14px;
        line-height: 1.1;
    }

    .pg-dash .ch-kpi .hint {
        font-size: 12px;
        color: #94a3b8;
        margin-top: 6px;
    }
</style>

@php
    // Laid out as data so the three cards cannot drift apart in markup.
    $pgCards = [
        [
            'label' => __('petrogeneral::lang.active_tanks'),
            'value' => (int) ($summary['active_tanks'] ?? 0),
            'icon'  => 'fa-database',
            'tone'  => 'tone-tank',
        ],
        [
            'label' => __('petrogeneral::lang.active_pumps'),
            'value' => (int) ($summary['active_pumps'] ?? 0),
            'icon'  => 'fa-tint',
            'tone'  => 'tone-pump',
        ],
        [
            'label' => __('petrogeneral::lang.pump_operators'),
            'value' => (int) ($summary['pump_operators'] ?? 0),
            'icon'  => 'fa-users',
            'tone'  => 'tone-staff',
        ],
    ];
@endphp

<div class="ch-kpi-grid">
    @foreach($pgCards as $pgCard)
        <div class="ch-kpi {{ $pgCard['tone'] }}">
            <div class="ch-kpi-top">
                <div class="ch-icon"><i class="fa {{ $pgCard['icon'] }}"></i></div>
                <div class="label-text">{{ $pgCard['label'] }}</div>
            </div>
            <div class="value">{{ number_format($pgCard['value']) }}</div>
        </div>
    @endforeach
</div>
