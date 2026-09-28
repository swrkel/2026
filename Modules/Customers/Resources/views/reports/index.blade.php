@extends('layouts.app')

@section('title', 'Customer Reports')

@section('content')
@php
    $summary = $summary ?? [];

    $stats = [
        [
            'label' => 'Total Customers',
            'value' => number_format($summary['total_customers'] ?? 0),
            'description' => 'All registered customers',
            'route' => route('customers.reports.list'),
            'icon' => 'fa-users',
            'theme' => 'blue',
        ],
        [
            'label' => 'Active Customers',
            'value' => number_format($summary['active_customers'] ?? 0),
            'description' => 'Customers currently active',
            'route' => route('customers.reports.list') . '?status=active',
            'icon' => 'fa-check-circle',
            'theme' => 'green',
        ],
        [
            'label' => 'Inactive Customers',
            'value' => number_format($summary['inactive_customers'] ?? 0),
            'description' => 'Customers requiring attention',
            'route' => route('customers.reports.inactive'),
            'icon' => 'fa-user-times',
            'theme' => 'orange',
        ],
        [
            'label' => 'Outstanding',
            'value' => number_format($summary['outstanding_total'] ?? 0, 2),
            'description' => 'Total customer receivables',
            'route' => route('customers.reports.balance'),
            'icon' => 'fa-money',
            'theme' => 'purple',
        ],
    ];

    $reports = [
        [
            'title' => 'Customer List',
            'description' => 'View customer details, contact information and current status.',
            'route' => route('customers.reports.list'),
            'icon' => 'fa-list-alt',
            'theme' => 'blue',
        ],
        [
            'title' => 'Customer Ledger',
            'description' => 'Review debit, credit and running balance movements.',
            'route' => route('customers.reports.ledger'),
            'icon' => 'fa-book',
            'theme' => 'green',
        ],
        [
            'title' => 'Customer Statement',
            'description' => 'Generate a detailed customer account statement.',
            'route' => route('customers.reports.statement'),
            'icon' => 'fa-file-text-o',
            'theme' => 'purple',
        ],
        [
            'title' => 'Customer Balance Report',
            'description' => 'Compare customer balances and outstanding receivables.',
            'route' => route('customers.reports.balance'),
            'icon' => 'fa-balance-scale',
            'theme' => 'amber',
        ],
        [
            'title' => 'Customer Aging',
            'description' => 'Analyse overdue balances by ageing period.',
            'route' => route('customers.reports.aging'),
            'icon' => 'fa-clock-o',
            'theme' => 'red',
        ],
        [
            'title' => 'Transaction Report',
            'description' => 'Review sales, invoices and account movements.',
            'route' => route('customers.reports.transactions'),
            'icon' => 'fa-exchange',
            'theme' => 'cyan',
        ],
        [
            'title' => 'Payment Report',
            'description' => 'Track receipts, settlements and payment activity.',
            'route' => route('customers.reports.payments'),
            'icon' => 'fa-credit-card',
            'theme' => 'teal',
        ],
        [
            'title' => 'Inactive Customers',
            'description' => 'Identify inactive customers for review and follow-up.',
            'route' => route('customers.reports.inactive'),
            'icon' => 'fa-user-times',
            'theme' => 'slate',
        ],
    ];
@endphp

<style>
    .customer-report-dashboard {
        --page-bg: #f4f7fb;
        --text-main: #0f172a;
        --text-muted: #64748b;
        --line: #e6edf7;
        padding-bottom: 12px;
    }

    .customer-report-dashboard .crd-shell {
        background: var(--page-bg);
        border-radius: 22px;
        padding: 22px;
    }

    .customer-report-dashboard .crd-hero {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        min-height: 138px;
        margin-bottom: 20px;
        padding: 26px 28px;
        border-radius: 22px;
        color: #fff;
        background: linear-gradient(120deg, #183d9f 0%, #2563eb 48%, #11a7d9 100%);
        box-shadow: 0 18px 36px rgba(37, 99, 235, 0.22);
    }

    .customer-report-dashboard .crd-hero::before,
    .customer-report-dashboard .crd-hero::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.10);
        pointer-events: none;
    }

    .customer-report-dashboard .crd-hero::before {
        width: 210px;
        height: 210px;
        right: -72px;
        top: -100px;
    }

    .customer-report-dashboard .crd-hero::after {
        width: 130px;
        height: 130px;
        right: 150px;
        bottom: -82px;
    }

    .customer-report-dashboard .crd-hero-copy,
    .customer-report-dashboard .crd-hero-icon {
        position: relative;
        z-index: 1;
    }

    .customer-report-dashboard .crd-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        opacity: .9;
    }

    .customer-report-dashboard .crd-title {
        margin: 0;
        color: #fff;
        font-size: 32px;
        line-height: 1.15;
        font-weight: 800;
        letter-spacing: -.02em;
    }

    .customer-report-dashboard .crd-subtitle {
        max-width: 660px;
        margin: 9px 0 0;
        color: rgba(255, 255, 255, .88);
        font-size: 15px;
        line-height: 1.6;
    }

    .customer-report-dashboard .crd-hero-icon {
        flex: 0 0 82px;
        width: 82px;
        height: 82px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 22px;
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .26);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .18);
        font-size: 36px;
    }

    .customer-report-dashboard .crd-stat-grid,
    .customer-report-dashboard .crd-report-grid {
        display: grid;
        gap: 16px;
    }

    .customer-report-dashboard .crd-stat-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin-bottom: 20px;
    }

    .customer-report-dashboard .crd-stat-card {
        position: relative;
        display: block;
        min-height: 164px;
        padding: 20px 20px 17px;
        overflow: hidden;
        border-radius: 20px;
        background: #fff;
        border: 1px solid var(--line);
        box-shadow: 0 10px 24px rgba(15, 23, 42, .07);
        text-decoration: none !important;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .customer-report-dashboard .crd-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 17px 34px rgba(15, 23, 42, .12);
        border-color: var(--accent-soft-border);
    }

    .customer-report-dashboard .crd-stat-card::after {
        content: '';
        position: absolute;
        width: 110px;
        height: 110px;
        right: -38px;
        top: -40px;
        border-radius: 50%;
        background: var(--accent-pale);
    }

    .customer-report-dashboard .crd-stat-top {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
    }

    .customer-report-dashboard .crd-stat-label {
        color: var(--text-muted);
        font-size: 14px;
        font-weight: 700;
    }

    .customer-report-dashboard .crd-stat-icon {
        flex: 0 0 48px;
        width: 48px;
        height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        color: #fff;
        background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
        box-shadow: 0 10px 18px var(--accent-shadow);
        font-size: 21px;
    }

    .customer-report-dashboard .crd-stat-value {
        position: relative;
        z-index: 1;
        margin: 16px 0 7px;
        color: var(--text-main);
        font-size: clamp(28px, 2.15vw, 39px);
        line-height: 1;
        font-weight: 800;
        letter-spacing: -.03em;
        word-break: break-word;
    }

    .customer-report-dashboard .crd-stat-description {
        position: relative;
        z-index: 1;
        margin: 0;
        color: var(--text-muted);
        font-size: 13px;
        line-height: 1.45;
    }

    .customer-report-dashboard .crd-stat-link {
        position: absolute;
        right: 18px;
        bottom: 16px;
        z-index: 1;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: var(--accent);
        background: var(--accent-pale);
        font-size: 13px;
    }

    .customer-report-dashboard .crd-panel {
        overflow: hidden;
        border-radius: 22px;
        background: #fff;
        border: 1px solid var(--line);
        box-shadow: 0 14px 32px rgba(15, 23, 42, .07);
    }

    .customer-report-dashboard .crd-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 22px 24px;
        border-bottom: 1px solid var(--line);
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    }

    .customer-report-dashboard .crd-panel-title {
        margin: 0;
        color: var(--text-main);
        font-size: 20px;
        font-weight: 800;
    }

    .customer-report-dashboard .crd-panel-text {
        margin: 6px 0 0;
        color: var(--text-muted);
        font-size: 14px;
    }

    .customer-report-dashboard .crd-count {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 15px;
        border-radius: 999px;
        color: #1d4ed8;
        background: #eef4ff;
        border: 1px solid #d6e4ff;
        font-size: 13px;
        font-weight: 800;
    }

    .customer-report-dashboard .crd-panel-body {
        padding: 22px;
    }

    .customer-report-dashboard .crd-report-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .customer-report-dashboard .crd-report-card {
        position: relative;
        display: flex;
        flex-direction: column;
        min-height: 218px;
        overflow: hidden;
        border-radius: 20px;
        background: #fff;
        border: 1px solid var(--line);
        text-decoration: none !important;
        box-shadow: 0 8px 18px rgba(15, 23, 42, .055);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .customer-report-dashboard .crd-report-card:hover {
        transform: translateY(-5px);
        border-color: var(--accent-soft-border);
        box-shadow: 0 17px 32px rgba(15, 23, 42, .12);
    }

    .customer-report-dashboard .crd-report-top {
        position: relative;
        min-height: 92px;
        padding: 17px 18px;
        color: #fff;
        background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
        overflow: hidden;
    }

    .customer-report-dashboard .crd-report-top::after {
        content: '';
        position: absolute;
        width: 100px;
        height: 100px;
        right: -26px;
        top: -34px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .13);
    }

    .customer-report-dashboard .crd-report-icon {
        position: relative;
        z-index: 1;
        width: 54px;
        height: 54px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        background: rgba(255, 255, 255, .18);
        border: 1px solid rgba(255, 255, 255, .25);
        font-size: 24px;
    }

    .customer-report-dashboard .crd-report-body {
        flex: 1 1 auto;
        padding: 17px 16px 14px;
    }

    .customer-report-dashboard .crd-report-title {
        margin: 0 0 9px;
        color: var(--text-main);
        font-size: 17px;
        line-height: 1.35;
        font-weight: 800;
    }

    .customer-report-dashboard .crd-report-description {
        margin: 0;
        color: var(--text-muted);
        font-size: 13px;
        line-height: 1.62;
    }

    .customer-report-dashboard .crd-report-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 16px;
        color: var(--accent-dark);
        background: var(--accent-pale);
        border-top: 1px solid var(--accent-soft-border);
        font-size: 13px;
        font-weight: 800;
    }

    .customer-report-dashboard .crd-open-circle {
        width: 29px;
        height: 29px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: #fff;
        background: var(--accent);
        font-size: 12px;
        box-shadow: 0 6px 12px var(--accent-shadow);
    }

    .customer-report-dashboard .theme-blue {
        --accent: #2563eb;
        --accent-dark: #1746b8;
        --accent-pale: #edf4ff;
        --accent-soft-border: #d6e4ff;
        --accent-shadow: rgba(37, 99, 235, .24);
    }

    .customer-report-dashboard .theme-green {
        --accent: #18a957;
        --accent-dark: #0d7f3d;
        --accent-pale: #ecfbf2;
        --accent-soft-border: #d2f3df;
        --accent-shadow: rgba(24, 169, 87, .24);
    }

    .customer-report-dashboard .theme-orange,
    .customer-report-dashboard .theme-amber {
        --accent: #f59e0b;
        --accent-dark: #d97800;
        --accent-pale: #fff7e6;
        --accent-soft-border: #f8e3b6;
        --accent-shadow: rgba(245, 158, 11, .24);
    }

    .customer-report-dashboard .theme-purple {
        --accent: #7c3aed;
        --accent-dark: #5b21b6;
        --accent-pale: #f4efff;
        --accent-soft-border: #e4d7ff;
        --accent-shadow: rgba(124, 58, 237, .24);
    }

    .customer-report-dashboard .theme-red {
        --accent: #ef4444;
        --accent-dark: #c62828;
        --accent-pale: #fff0f0;
        --accent-soft-border: #ffd7d7;
        --accent-shadow: rgba(239, 68, 68, .22);
    }

    .customer-report-dashboard .theme-cyan {
        --accent: #0891b2;
        --accent-dark: #0e7490;
        --accent-pale: #ecfbff;
        --accent-soft-border: #d2f2f8;
        --accent-shadow: rgba(8, 145, 178, .22);
    }

    .customer-report-dashboard .theme-teal {
        --accent: #0f9f8f;
        --accent-dark: #0b756b;
        --accent-pale: #ebfbf8;
        --accent-soft-border: #d0f1eb;
        --accent-shadow: rgba(15, 159, 143, .22);
    }

    .customer-report-dashboard .theme-slate {
        --accent: #526276;
        --accent-dark: #334155;
        --accent-pale: #f1f5f9;
        --accent-soft-border: #dce4ec;
        --accent-shadow: rgba(71, 85, 105, .20);
    }

    /* Keep four report buttons per row on desktop and laptop screens. */
    @media (max-width: 991px) {
        .customer-report-dashboard .crd-report-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 1199px) {
        .customer-report-dashboard .crd-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .customer-report-dashboard .crd-shell {
            padding: 12px;
            border-radius: 16px;
        }

        .customer-report-dashboard .crd-hero {
            min-height: 0;
            padding: 22px 20px;
        }

        .customer-report-dashboard .crd-title {
            font-size: 27px;
        }

        .customer-report-dashboard .crd-hero-icon {
            display: none;
        }

        .customer-report-dashboard .crd-stat-grid,
        .customer-report-dashboard .crd-report-grid {
            grid-template-columns: 1fr;
        }

        .customer-report-dashboard .crd-panel-head {
            align-items: flex-start;
            flex-direction: column;
        }

        .customer-report-dashboard .crd-panel-body {
            padding: 16px;
        }
    }
</style>

<section class="content customer-report-dashboard">
    <div class="crd-shell">
        <div class="crd-hero">
            <div class="crd-hero-copy">
                <span class="crd-eyebrow"><i class="fa fa-dashboard"></i> Customers Module</span>
                <h1 class="crd-title">Customer Reports Dashboard</h1>
                <p class="crd-subtitle">Open customer reports, review receivables and monitor customer activity from one professional dashboard.</p>
            </div>
            <span class="crd-hero-icon"><i class="fa fa-line-chart"></i></span>
        </div>

        <div class="crd-stat-grid">
            @foreach($stats as $stat)
                <a href="{{ $stat['route'] }}" class="crd-stat-card theme-{{ $stat['theme'] }}">
                    <div class="crd-stat-top">
                        <span class="crd-stat-label">{{ $stat['label'] }}</span>
                        <span class="crd-stat-icon"><i class="fa {{ $stat['icon'] }}"></i></span>
                    </div>
                    <div class="crd-stat-value">{{ $stat['value'] }}</div>
                    <p class="crd-stat-description">{{ $stat['description'] }}</p>
                    <span class="crd-stat-link"><i class="fa fa-arrow-right"></i></span>
                </a>
            @endforeach
        </div>

        <div class="crd-panel">
            <div class="crd-panel-head">
                <div>
                    <h2 class="crd-panel-title">Available Reports</h2>
                    <p class="crd-panel-text">Choose a report below to continue.</p>
                </div>
                <span class="crd-count"><i class="fa fa-th-large"></i> {{ count($reports) }} Reports</span>
            </div>

            <div class="crd-panel-body">
                <div class="crd-report-grid">
                    @foreach($reports as $report)
                        <a href="{{ $report['route'] }}" class="crd-report-card theme-{{ $report['theme'] }}">
                            <div class="crd-report-top">
                                <span class="crd-report-icon"><i class="fa {{ $report['icon'] }}"></i></span>
                            </div>
                            <div class="crd-report-body">
                                <h3 class="crd-report-title">{{ $report['title'] }}</h3>
                                <p class="crd-report-description">{{ $report['description'] }}</p>
                            </div>
                            <div class="crd-report-footer">
                                <span>Open Report</span>
                                <span class="crd-open-circle"><i class="fa fa-arrow-right"></i></span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
