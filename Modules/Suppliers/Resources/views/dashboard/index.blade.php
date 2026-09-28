@extends('suppliers::layouts.app')
@section('title', __('suppliers::lang.supplier_module'))
@section('module_title', 'Supplier Module Dashboard')

@push('suppliers_styles')
<style>
    /* Supplier Dashboard - POS Dashboard card standard (standalone copy; no POS dependency). */
    .supplier-pos-dashboard {
        color: #0f172a;
    }

    .supplier-pos-intro {
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 55%, #eef6ff 100%);
        border: 1px solid #dbe7f3;
        border-radius: 18px;
        padding: 20px 22px;
        margin-bottom: 22px;
        box-shadow: 0 14px 35px rgba(15, 23, 42, .08);
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
    }

    .supplier-pos-intro:before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        width: 6px;
        height: 100%;
        background: linear-gradient(180deg, #2563eb, #06b6d4);
    }

    .supplier-pos-intro h2 {
        margin: 0;
        color: #102033;
        font-size: 24px;
        font-weight: 800;
        letter-spacing: -.02em;
    }

    .supplier-pos-intro p {
        margin: 7px 0 0;
        color: #64748b;
        font-size: 13px;
        line-height: 1.55;
    }

    .supplier-pos-intro-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        flex: 0 0 auto;
    }

    .supplier-pos-intro-actions .btn {
        border-radius: 10px;
        font-weight: 700;
        box-shadow: 0 6px 16px rgba(2, 6, 23, .08);
    }

    .supplier-pos-card-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(180px, 1fr));
        gap: 22px;
        margin-bottom: 26px;
    }

    .supplier-pos-card-link {
        display: block;
        color: inherit;
        text-decoration: none !important;
        min-width: 0;
    }

    .supplier-pos-card-link:hover,
    .supplier-pos-card-link:focus {
        color: inherit;
        text-decoration: none !important;
    }

    .supplier-pos-card {
        height: 100%;
        min-height: 158px;
        padding: 18px 18px 15px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        border: 1px solid #cfe0f3;
        border-radius: 18px;
        box-shadow: 0 16px 36px rgba(15, 23, 42, .10);
        position: relative;
        overflow: hidden;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .supplier-pos-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 42px rgba(15, 23, 42, .12);
    }

    .supplier-pos-card:before {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 3px;
        background: var(--supplier-card-accent, #2563eb);
    }

    .supplier-pos-card-top {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .supplier-pos-card-icon {
        width: 52px;
        height: 52px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 52px;
        color: #fff;
        font-size: 20px;
        background: var(--supplier-card-gradient, linear-gradient(135deg, #2563eb, #38bdf8));
        box-shadow: 0 10px 20px rgba(37, 99, 235, .22);
    }

    .supplier-pos-card-label {
        min-width: 0;
        color: #334155;
        font-size: 13px;
        font-weight: 800;
        line-height: 1.2;
    }

    .supplier-pos-card-value {
        margin-top: 14px;
        color: var(--supplier-card-accent, #0f172a);
        font-size: 30px;
        font-weight: 900;
        line-height: 1;
        letter-spacing: -.02em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .supplier-pos-card-hint {
        min-height: 18px;
        margin-top: 8px;
        color: #64748b;
        font-size: 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .supplier-pos-card-drill {
        float: right;
        color: var(--supplier-card-accent, #2563eb);
        font-size: 11px;
        font-weight: 900;
        margin-left: 8px;
    }

    .supplier-pos-card-spark {
        height: 20px;
        margin-top: 12px;
        border-radius: 12px;
        background: var(--supplier-card-spark, linear-gradient(90deg, rgba(37,99,235,.08), rgba(37,99,235,.20), rgba(37,99,235,.08)));
        position: relative;
        overflow: hidden;
    }

    .supplier-pos-card-spark:after {
        content: '';
        position: absolute;
        left: 10%;
        right: 10%;
        top: 10px;
        border-top: 2px solid var(--supplier-card-line, rgba(37,99,235,.75));
        transform: skewY(-7deg);
    }

    .supplier-pos-card-link:focus .supplier-pos-card {
        outline: 2px solid rgba(37, 99, 235, .35);
        outline-offset: 2px;
    }

    .supplier-pos-card.tone-success {
        --supplier-card-accent: #16a34a;
        --supplier-card-gradient: linear-gradient(135deg, #16a34a, #86efac);
        --supplier-card-spark: linear-gradient(90deg, rgba(22,163,74,.08), rgba(22,163,74,.20), rgba(22,163,74,.08));
        --supplier-card-line: rgba(22,163,74,.75);
    }

    .supplier-pos-card.tone-warning {
        --supplier-card-accent: #f59e0b;
        --supplier-card-gradient: linear-gradient(135deg, #f59e0b, #fde68a);
        --supplier-card-spark: linear-gradient(90deg, rgba(245,158,11,.08), rgba(245,158,11,.22), rgba(245,158,11,.08));
        --supplier-card-line: rgba(245,158,11,.78);
    }

    .supplier-pos-card.tone-purple {
        --supplier-card-accent: #7c3aed;
        --supplier-card-gradient: linear-gradient(135deg, #7c3aed, #c084fc);
        --supplier-card-spark: linear-gradient(90deg, rgba(124,58,237,.08), rgba(124,58,237,.20), rgba(124,58,237,.08));
        --supplier-card-line: rgba(124,58,237,.75);
    }

    .supplier-pos-card.tone-cyan {
        --supplier-card-accent: #0891b2;
        --supplier-card-gradient: linear-gradient(135deg, #0891b2, #67e8f9);
        --supplier-card-spark: linear-gradient(90deg, rgba(8,145,178,.08), rgba(8,145,178,.20), rgba(8,145,178,.08));
        --supplier-card-line: rgba(8,145,178,.75);
    }

    .supplier-pos-card.tone-danger {
        --supplier-card-accent: #ef4444;
        --supplier-card-gradient: linear-gradient(135deg, #ef4444, #fca5a5);
        --supplier-card-spark: linear-gradient(90deg, rgba(239,68,68,.08), rgba(239,68,68,.20), rgba(239,68,68,.08));
        --supplier-card-line: rgba(239,68,68,.78);
    }

    @media (max-width: 1199px) {
        .supplier-pos-card-grid {
            grid-template-columns: repeat(2, minmax(220px, 1fr));
        }
    }

    @media (max-width: 767px) {
        .supplier-pos-intro {
            display: block;
            padding: 18px 18px 18px 22px;
        }

        .supplier-pos-intro-actions {
            margin-top: 14px;
            justify-content: flex-start;
        }

        .supplier-pos-card-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
    }
</style>
@endpush

@section('suppliers_content')
@php
    $cards = [
        ['title' => 'Total Suppliers', 'value' => number_format($totalSuppliers ?? 0), 'route' => route('suppliers.records.index'), 'icon' => 'fa-truck', 'tone' => '', 'hint' => 'Active supplier records'],
        ['title' => 'List Suppliers', 'value' => 'Open', 'route' => route('suppliers.records.index'), 'icon' => 'fa-list', 'tone' => 'tone-cyan', 'hint' => 'Browse and manage suppliers'],
        ['title' => 'Add Supplier', 'value' => 'Open', 'route' => route('suppliers.records.create'), 'icon' => 'fa-plus', 'tone' => 'tone-success', 'hint' => 'Create a supplier profile'],
        ['title' => 'Supplier Payments', 'value' => 'Open', 'route' => route('suppliers.payments.index'), 'icon' => 'fa-money', 'tone' => 'tone-purple', 'hint' => 'Payments and balances'],
        ['title' => 'Supplier Settings', 'value' => 'Open', 'route' => route('suppliers.settings.payment_references.index'), 'icon' => 'fa-cogs', 'tone' => 'tone-cyan', 'hint' => 'Payment reference prefixes and starting numbers'],
        ['title' => 'Product Mapping', 'value' => 'Open', 'route' => route('suppliers.mappings.index'), 'icon' => 'fa-link', 'tone' => 'tone-warning', 'hint' => 'Map supplier products'],
        ['title' => 'Stock Report', 'value' => 'Open', 'route' => route('suppliers.stock_report.index'), 'icon' => 'fa-cubes', 'tone' => 'tone-danger', 'hint' => 'Supplier stock movement'],
        ['title' => 'Import Suppliers', 'value' => 'Open', 'route' => route('suppliers.imports.index'), 'icon' => 'fa-upload', 'tone' => 'tone-cyan', 'hint' => 'Bulk import suppliers'],
        ['title' => 'Issued Payments', 'value' => 'Open', 'route' => route('suppliers.issue_payment_details.index'), 'icon' => 'fa-credit-card', 'tone' => '', 'hint' => 'Issued payment details'],
        ['title' => 'User Activity', 'value' => 'Open', 'route' => route('suppliers.user_activity.index'), 'icon' => 'fa-history', 'tone' => 'tone-purple', 'hint' => 'Supplier activity history'],
        ['title' => 'Reports', 'value' => 'Open', 'route' => route('suppliers.reports.index'), 'icon' => 'fa-bar-chart', 'tone' => 'tone-warning', 'hint' => 'Supplier reports and analysis'],
    ];
@endphp

<div class="supplier-pos-dashboard">
    <div class="supplier-pos-intro">
        <div>
            <h2>Supplier Dashboard</h2>
            <p>Quick access to supplier records, payments, product mapping, stock reports, imports and reports.</p>
        </div>
        <div class="supplier-pos-intro-actions">
            <a href="{{ route('suppliers.records.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Add Supplier</a>
            <a href="{{ route('suppliers.records.index') }}" class="btn btn-default"><i class="fa fa-list"></i> List Suppliers</a>
        </div>
    </div>

    <div class="supplier-pos-card-grid">
        @foreach($cards as $card)
            <a href="{{ $card['route'] }}" class="supplier-pos-card-link">
                <div class="supplier-pos-card {{ $card['tone'] }}">
                    <div class="supplier-pos-card-top">
                        <div class="supplier-pos-card-icon"><i class="fa {{ $card['icon'] }}"></i></div>
                        <div class="supplier-pos-card-label">{{ $card['title'] }}</div>
                    </div>
                    <div class="supplier-pos-card-value">{{ $card['value'] }}</div>
                    <div class="supplier-pos-card-hint">
                        {{ $card['hint'] }}
                        <span class="supplier-pos-card-drill">Open <i class="fa fa-angle-right"></i></span>
                    </div>
                    <div class="supplier-pos-card-spark"></div>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endsection
