@extends('layouts.app')

@section('title', 'Customer Configuration')

@section('content')
@php
    $items = [
        [
            'route' => 'customers.settings.payment_references.index',
            'icon' => 'fa-hashtag',
            'title' => 'Payment Reference Numbers',
            'eyebrow' => 'PAYMENT NUMBERING',
            'desc' => 'Configure prefixes and starting numbers for Customer Pay Due and Customer Payments.',
            'theme' => 'cyan',
        ],
        [
            'route' => 'customers.settings.numbering.index',
            'icon' => 'fa-sort-numeric-asc',
            'title' => 'Customer Numbering',
            'eyebrow' => 'IDENTIFICATION',
            'desc' => 'Configure the customer-code prefix, automatic numbering sequence and manual numbering rules.',
            'theme' => 'blue',
        ],
        [
            'route' => 'customers.settings.defaults.index',
            'icon' => 'fa-check-square-o',
            'title' => 'Customer Defaults',
            'eyebrow' => 'DEFAULT VALUES',
            'desc' => 'Set the default customer type, status, credit terms and credit-limit values used on new records.',
            'theme' => 'green',
        ],
        [
            'route' => 'customers.settings.preferences.index',
            'icon' => 'fa-sliders',
            'title' => 'Customer Preferences',
            'eyebrow' => 'DISPLAY & BEHAVIOUR',
            'desc' => 'Manage Customer Register, statement and ledger display preferences for daily operations.',
            'theme' => 'purple',
        ],
        [
            'route' => 'customers.settings.portal.index',
            'icon' => 'fa-globe',
            'title' => 'Dealer Portal Settings',
            'eyebrow' => 'PORTAL ACCESS',
            'desc' => 'Control dealer-portal features, access options and customer-facing portal permissions.',
            'theme' => 'cyan',
        ],
        [
            'route' => 'customers.settings.credit.index',
            'icon' => 'fa-credit-card',
            'title' => 'Credit Settings',
            'eyebrow' => 'CREDIT CONTROL',
            'desc' => 'Configure credit approvals, warnings, transaction blocks and review rules for customers.',
            'theme' => 'orange',
        ],
        [
            'route' => 'customers.settings.notifications.index',
            'icon' => 'fa-bell',
            'title' => 'Notification Settings',
            'eyebrow' => 'COMMUNICATION',
            'desc' => 'Set email, SMS, portal and overdue-alert preferences for customer communication.',
            'theme' => 'red',
        ],
    ];

    $overviewCards = [
        [
            'label' => 'Configuration Areas',
            'value' => count($items),
            'hint' => 'Customer settings sections',
            'icon' => 'fa-cogs',
            'theme' => 'blue',
            'route' => '#customer-settings-grid',
        ],
        [
            'label' => 'Numbering & Defaults',
            'value' => '3',
            'hint' => 'Core customer setup',
            'icon' => 'fa-list-ol',
            'theme' => 'green',
            'route' => route('customers.settings.numbering.index'),
        ],
        [
            'label' => 'Credit & Alerts',
            'value' => '2',
            'hint' => 'Risk and communication rules',
            'icon' => 'fa-shield',
            'theme' => 'orange',
            'route' => route('customers.settings.credit.index'),
        ],
        [
            'label' => 'Portal & Display',
            'value' => '2',
            'hint' => 'User experience controls',
            'icon' => 'fa-desktop',
            'theme' => 'purple',
            'route' => route('customers.settings.preferences.index'),
        ],
    ];
@endphp

<style>
    .customer-settings-dashboard {
        --cs-text: #0f172a;
        --cs-muted: #64748b;
        --cs-border: #e5edf7;
        --cs-surface: #ffffff;
        --cs-page: #f6f9fd;
        padding-bottom: 18px;
    }

    .customer-settings-dashboard .cs-page-hero {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        padding: 27px 30px;
        margin-bottom: 22px;
        border: 1px solid #dce8fb;
        border-radius: 20px;
        overflow: hidden;
        color: #ffffff;
        background:
            radial-gradient(circle at 90% 15%, rgba(255, 255, 255, .20), transparent 25%),
            linear-gradient(135deg, #0f4cbb 0%, #2868e8 57%, #4b8cf7 100%);
        box-shadow: 0 16px 38px rgba(37, 99, 235, .18);
    }

    .customer-settings-dashboard .cs-page-hero::after {
        content: '\f013';
        position: absolute;
        right: 34px;
        bottom: -47px;
        font: normal normal normal 150px/1 FontAwesome;
        color: rgba(255, 255, 255, .08);
        pointer-events: none;
    }

    .customer-settings-dashboard .cs-hero-copy,
    .customer-settings-dashboard .cs-hero-actions {
        position: relative;
        z-index: 1;
    }

    .customer-settings-dashboard .cs-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 8px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .14em;
        color: #dbeafe;
    }

    .customer-settings-dashboard .cs-page-hero h1 {
        margin: 0;
        font-size: 30px;
        line-height: 1.15;
        font-weight: 800;
        color: #ffffff;
    }

    .customer-settings-dashboard .cs-page-hero p {
        max-width: 720px;
        margin: 9px 0 0;
        font-size: 14px;
        line-height: 1.6;
        color: rgba(255, 255, 255, .86);
    }

    .customer-settings-dashboard .cs-hero-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 42px;
        padding: 10px 18px;
        border: 1px solid rgba(255, 255, 255, .42);
        border-radius: 10px;
        color: #ffffff !important;
        background: rgba(255, 255, 255, .13);
        font-size: 13px;
        font-weight: 700;
        text-decoration: none !important;
        transition: background .18s ease, transform .18s ease;
        backdrop-filter: blur(6px);
    }

    .customer-settings-dashboard .cs-hero-button:hover {
        color: #174ea6 !important;
        background: #ffffff;
        transform: translateY(-1px);
    }

    .customer-settings-dashboard .cs-overview-grid,
    .customer-settings-dashboard .cs-settings-grid {
        display: grid;
        gap: 18px;
    }

    .customer-settings-dashboard .cs-overview-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin-bottom: 22px;
    }

    .customer-settings-dashboard .cs-overview-card,
    .customer-settings-dashboard .cs-settings-card {
        --theme: #2563eb;
        --theme-soft: #eff6ff;
        --theme-shadow: rgba(37, 99, 235, .14);
        position: relative;
        display: block;
        overflow: hidden;
        border: 1px solid var(--cs-border);
        border-radius: 17px;
        color: inherit !important;
        background: var(--cs-surface);
        text-decoration: none !important;
        box-shadow: 0 9px 25px rgba(15, 23, 42, .055);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .customer-settings-dashboard .cs-overview-card:hover,
    .customer-settings-dashboard .cs-settings-card:hover {
        transform: translateY(-3px);
        border-color: var(--theme);
        box-shadow: 0 16px 34px var(--theme-shadow);
    }

    .customer-settings-dashboard .cs-overview-card::before,
    .customer-settings-dashboard .cs-settings-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--theme);
    }

    .customer-settings-dashboard .cs-overview-card {
        min-height: 150px;
        padding: 21px 22px 18px;
    }

    .customer-settings-dashboard .cs-overview-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
    }

    .customer-settings-dashboard .cs-overview-icon,
    .customer-settings-dashboard .cs-settings-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        color: var(--theme);
        background: var(--theme-soft);
    }

    .customer-settings-dashboard .cs-overview-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        font-size: 21px;
    }

    .customer-settings-dashboard .cs-overview-label {
        margin-top: 12px;
        color: var(--cs-muted);
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .025em;
        text-transform: uppercase;
    }

    .customer-settings-dashboard .cs-overview-value {
        margin-top: 3px;
        color: var(--cs-text);
        font-size: 29px;
        line-height: 1.1;
        font-weight: 800;
    }

    .customer-settings-dashboard .cs-overview-hint {
        margin-top: 7px;
        color: var(--cs-muted);
        font-size: 12px;
    }

    .customer-settings-dashboard .cs-section {
        overflow: hidden;
        border: 1px solid var(--cs-border);
        border-radius: 19px;
        background: #ffffff;
        box-shadow: 0 11px 30px rgba(15, 23, 42, .055);
    }

    .customer-settings-dashboard .cs-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 22px 25px;
        border-bottom: 1px solid #edf2f8;
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    }

    .customer-settings-dashboard .cs-section-heading {
        display: flex;
        align-items: center;
        gap: 13px;
    }

    .customer-settings-dashboard .cs-section-heading-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        border-radius: 12px;
        color: #2563eb;
        background: #eff6ff;
        font-size: 18px;
    }

    .customer-settings-dashboard .cs-section-title {
        margin: 0 0 3px;
        color: var(--cs-text);
        font-size: 18px;
        font-weight: 800;
    }

    .customer-settings-dashboard .cs-section-subtitle {
        margin: 0;
        color: var(--cs-muted);
        font-size: 13px;
    }

    .customer-settings-dashboard .cs-section-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 36px;
        padding: 8px 14px;
        border: 1px solid #cfe0ff;
        border-radius: 999px;
        color: #1d4ed8;
        background: #eff6ff;
        font-size: 12px;
        font-weight: 800;
    }

    .customer-settings-dashboard .cs-section-body {
        padding: 22px;
        background: #fbfdff;
    }

    .customer-settings-dashboard .cs-settings-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .customer-settings-dashboard .cs-settings-card {
        min-height: 228px;
    }

    .customer-settings-dashboard .cs-settings-card-body {
        padding: 22px 20px 17px;
    }

    .customer-settings-dashboard .cs-settings-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 18px;
    }

    .customer-settings-dashboard .cs-settings-icon {
        width: 55px;
        height: 55px;
        border-radius: 16px;
        font-size: 23px;
    }

    .customer-settings-dashboard .cs-settings-number {
        color: #94a3b8;
        font-size: 12px;
        font-weight: 800;
    }

    .customer-settings-dashboard .cs-settings-eyebrow {
        display: block;
        margin-bottom: 5px;
        color: var(--theme);
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .11em;
    }

    .customer-settings-dashboard .cs-settings-title {
        margin: 0 0 9px;
        color: var(--cs-text);
        font-size: 16px;
        line-height: 1.35;
        font-weight: 800;
    }

    .customer-settings-dashboard .cs-settings-desc {
        margin: 0;
        color: var(--cs-muted);
        font-size: 12.5px;
        line-height: 1.58;
    }

    .customer-settings-dashboard .cs-settings-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        min-height: 49px;
        padding: 13px 20px;
        border-top: 1px solid #eef2f7;
        color: var(--theme);
        background: var(--theme-soft);
        font-size: 12px;
        font-weight: 800;
    }

    .customer-settings-dashboard .theme-blue   { --theme: #2563eb; --theme-soft: #eff6ff; --theme-shadow: rgba(37, 99, 235, .15); }
    .customer-settings-dashboard .theme-green  { --theme: #16a34a; --theme-soft: #edfdf3; --theme-shadow: rgba(22, 163, 74, .15); }
    .customer-settings-dashboard .theme-purple { --theme: #7c3aed; --theme-soft: #f5f0ff; --theme-shadow: rgba(124, 58, 237, .15); }
    .customer-settings-dashboard .theme-cyan   { --theme: #0891b2; --theme-soft: #ecfeff; --theme-shadow: rgba(8, 145, 178, .15); }
    .customer-settings-dashboard .theme-orange { --theme: #ea8a09; --theme-soft: #fff8e9; --theme-shadow: rgba(234, 138, 9, .15); }
    .customer-settings-dashboard .theme-red    { --theme: #dc3545; --theme-soft: #fff1f2; --theme-shadow: rgba(220, 53, 69, .15); }

    @media (max-width: 1099px) {
        .customer-settings-dashboard .cs-overview-grid,
        .customer-settings-dashboard .cs-settings-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .customer-settings-dashboard .cs-page-hero {
            align-items: flex-start;
            flex-direction: column;
            padding: 23px 20px;
            border-radius: 15px;
        }

        .customer-settings-dashboard .cs-page-hero h1 {
            font-size: 25px;
        }

        .customer-settings-dashboard .cs-overview-grid,
        .customer-settings-dashboard .cs-settings-grid {
            grid-template-columns: 1fr;
        }

        .customer-settings-dashboard .cs-section-header {
            align-items: flex-start;
            flex-direction: column;
            padding: 19px;
        }

        .customer-settings-dashboard .cs-section-body {
            padding: 15px;
        }
    }
</style>

<section class="content customer-settings-dashboard">
    <div class="cs-page-hero">
        <div class="cs-hero-copy">
            <span class="cs-eyebrow"><i class="fa fa-users"></i> CUSTOMERS MODULE</span>
            <h1>Customer Configuration Center</h1>
            <p>Manage customer numbering, defaults, display preferences, portal access, credit controls and notification rules from one central workspace.</p>
        </div>
        <div class="cs-hero-actions">
            <a href="{{ route('customers.index') }}" class="cs-hero-button">
                <i class="fa fa-arrow-left"></i> Customer Register
            </a>
        </div>
    </div>

    <div class="cs-overview-grid">
        @foreach($overviewCards as $card)
            <a href="{{ $card['route'] }}" class="cs-overview-card theme-{{ $card['theme'] }}">
                <div class="cs-overview-top">
                    <div>
                        <div class="cs-overview-label">{{ $card['label'] }}</div>
                        <div class="cs-overview-value">{{ $card['value'] }}</div>
                    </div>
                    <span class="cs-overview-icon"><i class="fa {{ $card['icon'] }}"></i></span>
                </div>
                <div class="cs-overview-hint">{{ $card['hint'] }} <i class="fa fa-angle-right"></i></div>
            </a>
        @endforeach
    </div>

    <div class="cs-section" id="customer-settings-grid">
        <div class="cs-section-header">
            <div class="cs-section-heading">
                <span class="cs-section-heading-icon"><i class="fa fa-cog"></i></span>
                <div>
                    <h2 class="cs-section-title">Configuration Areas</h2>
                    <p class="cs-section-subtitle">Open a section below to review and update its customer-module settings.</p>
                </div>
            </div>
            <span class="cs-section-count">{{ count($items) }} SETTINGS AREAS</span>
        </div>

        <div class="cs-section-body">
            <div class="cs-settings-grid">
                @foreach($items as $item)
                    <a href="{{ route($item['route']) }}" class="cs-settings-card theme-{{ $item['theme'] }}">
                        <div class="cs-settings-card-body">
                            <div class="cs-settings-card-top">
                                <span class="cs-settings-icon"><i class="fa {{ $item['icon'] }}"></i></span>
                                <span class="cs-settings-number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            </div>
                            <span class="cs-settings-eyebrow">{{ $item['eyebrow'] }}</span>
                            <h3 class="cs-settings-title">{{ $item['title'] }}</h3>
                            <p class="cs-settings-desc">{{ $item['desc'] }}</p>
                        </div>
                        <div class="cs-settings-card-footer">
                            <span>Open Settings</span>
                            <i class="fa fa-arrow-right"></i>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endsection
