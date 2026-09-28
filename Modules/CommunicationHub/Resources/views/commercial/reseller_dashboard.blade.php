@extends('communicationhub::layout')

@section('communicationhub_title', 'Reseller Dashboard')

@section('communicationhub_content')
@php
    $overview = [
        [
            'label' => 'Downline Clients',
            'value' => number_format((float) ($stats['clients'] ?? 0), 0),
            'hint' => 'Registered reseller clients',
            'icon' => 'fa-users',
            'tone' => 'primary',
        ],
        [
            'label' => 'Available Credits',
            'value' => number_format((float) ($stats['available_credits'] ?? 0), 0),
            'hint' => 'Credits ready for allocation',
            'icon' => 'fa-database',
            'tone' => 'success',
        ],
        [
            'label' => 'Credits Used',
            'value' => number_format((float) ($stats['used_credits'] ?? 0), 0),
            'hint' => 'Total reseller usage',
            'icon' => 'fa-paper-plane',
            'tone' => 'warning',
        ],
        [
            'label' => 'Reseller Profit',
            'value' => 'LKR ' . number_format((float) ($stats['profit'] ?? 0), 2),
            'hint' => 'Recorded communication margin',
            'icon' => 'fa-line-chart',
            'tone' => 'purple',
        ],
    ];

    $actions = [
        [
            'title' => 'Business Wallets',
            'description' => 'View wallet balances and allocated reseller credits.',
            'route' => 'communicationhub.commercial.business_wallets',
            'icon' => 'fa-credit-card',
            'tone' => 'primary',
            'meta' => number_format((float) ($stats['wallets'] ?? 0), 0) . ' wallets',
        ],
        [
            'title' => 'Downline Clients',
            'description' => 'Add and manage reseller-linked client businesses.',
            'route' => 'communicationhub.commercial.sms_clients',
            'icon' => 'fa-users',
            'tone' => 'success',
            'meta' => number_format((float) ($stats['clients'] ?? 0), 0) . ' clients',
        ],
        [
            'title' => 'SMS Packages',
            'description' => 'Configure credit packages, prices and reseller margins.',
            'route' => 'communicationhub.commercial.sms_packages',
            'icon' => 'fa-cubes',
            'tone' => 'purple',
            'meta' => number_format((float) ($stats['packages'] ?? 0), 0) . ' packages',
        ],
        [
            'title' => 'Refill Credits',
            'description' => 'Allocate or refill credits for reseller clients.',
            'route' => 'communicationhub.commercial.credit_refills',
            'icon' => 'fa-plus-circle',
            'tone' => 'warning',
            'meta' => 'New refill',
        ],
        [
            'title' => 'Credit Transactions',
            'description' => 'Review purchases, usage, adjustments and balances.',
            'route' => 'communicationhub.commercial.credit_transactions',
            'icon' => 'fa-exchange',
            'tone' => 'cyan',
            'meta' => number_format((float) ($stats['transactions'] ?? 0), 0) . ' entries',
        ],
        [
            'title' => 'Usage Summary',
            'description' => 'Check delivery activity and communication usage.',
            'route' => 'communicationhub.commercial.delivery_reports',
            'icon' => 'fa-bar-chart',
            'tone' => 'teal',
            'meta' => 'View usage',
        ],
        [
            'title' => 'Profit Report',
            'description' => 'Analyse revenue, cost and reseller profitability.',
            'route' => 'communicationhub.commercial.profit_reports',
            'icon' => 'fa-line-chart',
            'tone' => 'danger',
            'meta' => 'View report',
        ],
        [
            'title' => 'API Access',
            'description' => 'Manage API tokens for reseller integrations.',
            'route' => 'communicationhub.commercial.api_tokens',
            'icon' => 'fa-key',
            'tone' => 'slate',
            'meta' => 'Manage tokens',
        ],
    ];
@endphp

<div class="ch-toolbar ch-reseller-toolbar">
    <div>
        <strong><i class="fa fa-sitemap"></i> Reseller Control Centre</strong>
        <div class="ch-page-note">Manage reseller clients, credits, packages, transactions and profitability from one dashboard.</div>
    </div>
    <div class="ch-reseller-toolbar-actions">
        <span class="ch-filter-pill"><i class="fa fa-calendar"></i> {{ date('d M Y') }}</span>
        <a href="{{ route('communicationhub.commercial.credit_refills') }}" class="btn btn-primary btn-sm">
            <i class="fa fa-plus-circle"></i> Refill Credits
        </a>
    </div>
</div>

<div class="ch-reseller-overview-grid">
    @foreach($overview as $item)
        <div class="ch-reseller-overview-card ch-reseller-overview-card--{{ $item['tone'] }}">
            <div class="ch-reseller-overview-icon"><i class="fa {{ $item['icon'] }}"></i></div>
            <div class="ch-reseller-overview-copy">
                <span>{{ $item['label'] }}</span>
                <strong>{{ $item['value'] }}</strong>
                <small>{{ $item['hint'] }}</small>
            </div>
        </div>
    @endforeach
</div>

<div class="ch-card ch-pos-launchpad">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-th-large"></i> Reseller Operations</h3>
            <div class="ch-card-subtitle">Select a card to open the related reseller function.</div>
        </div>
        <span class="ch-badge-soft info">{{ count($actions) }} Functions</span>
    </div>

    <div class="ch-card-body">
        <div class="ch-pos-action-grid">
            @foreach($actions as $action)
                <a href="{{ route($action['route']) }}"
                   class="ch-pos-action-card ch-pos-action-card--{{ $action['tone'] }}"
                   aria-label="Open {{ $action['title'] }}">
                    <span class="ch-pos-action-icon"><i class="fa {{ $action['icon'] }}"></i></span>
                    <span class="ch-pos-action-copy">
                        <strong>{{ $action['title'] }}</strong>
                        <small>{{ $action['description'] }}</small>
                    </span>
                    <span class="ch-pos-action-footer">
                        <span>{{ $action['meta'] }}</span>
                        <i class="fa fa-arrow-right"></i>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
