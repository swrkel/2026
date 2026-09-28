@php
    $routeTabMap = [
        'finance-reports.trial-balance-new' => 'trial-balance',
        'finance-reports.trial-balance' => 'trial-balance',
        'finance-reports.balance-sheet-new' => 'balance-sheet',
        'finance-reports.balance-sheet' => 'balance-sheet',
        'finance-reports.profit-loss-new' => 'profit-loss',
        'finance-reports.profit-loss' => 'profit-loss',
        'finance-reports.income-statement-new' => 'income-statement',
        'finance-reports.income-statement' => 'income-statement',
    ];

    $currentRouteName = optional(request()->route())->getName();
    $activeReportTab = $active_report_tab ?? ($routeTabMap[$currentRouteName] ?? null);

    $statementTabs = [
        'trial-balance' => [
            'label' => 'Trial Balance - New',
            'route' => 'finance-reports.trial-balance-new',
        ],
        'balance-sheet' => [
            'label' => 'Balance Sheet - New',
            'route' => 'finance-reports.balance-sheet-new',
        ],
        'profit-loss' => [
            'label' => 'Profit & Loss - New',
            'route' => 'finance-reports.profit-loss-new',
        ],
        'income-statement' => [
            'label' => 'Income Statement - New',
            'route' => 'finance-reports.income-statement-new',
        ],
    ];
@endphp

<div class="nav-tabs-custom finance-report-statement-tabs">
    <ul class="nav nav-tabs">
        @foreach($statementTabs as $tabKey => $tab)
            <li class="{{ $activeReportTab === $tabKey ? 'active' : '' }}">
                <a href="{{ route($tab['route']) }}">
                    {{ $tab['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</div>

<style>
    .finance-report-statement-tabs {
        margin-bottom: 15px;
        box-shadow: none;
        background: transparent;
    }

    .finance-report-statement-tabs > .nav-tabs {
        border-bottom: 1px solid #d2d6de;
    }

    .finance-report-statement-tabs > .nav-tabs > li > a {
        margin-right: 4px;
        border: 1px solid #d2d6de;
        border-bottom-color: transparent;
        border-radius: 4px 4px 0 0;
        background: #f7f7f7;
        color: #444;
        font-weight: 600;
    }

    .finance-report-statement-tabs > .nav-tabs > li.active > a,
    .finance-report-statement-tabs > .nav-tabs > li.active > a:hover,
    .finance-report-statement-tabs > .nav-tabs > li.active > a:focus {
        background: #fff;
        color: #111;
        border-top: 3px solid #3c8dbc;
        border-left-color: #d2d6de;
        border-right-color: #d2d6de;
    }
</style>
