@php
    use Modules\Finance\Utils\FinancePermissionHelper;

    // Never expose a raw translation key in the sidebar if a locale file is incomplete.
    $financeSidebarLabel = static function ($key, $fallback) {
        $translated = __($key);
        return $translated === $key ? $fallback : $translated;
    };

    $financeMenuItems = [
        [
            'label' => $financeSidebarLabel('finance::account.list_accounts', 'List Accounts'),
            'route' => 'finance.list-accounts.live',
            'permission' => 'finance.accounts.view',
            'icon' => 'fa fa-book',
        ],
        [
            'label' => $financeSidebarLabel('finance::account.account_groups', 'Account Groups'),
            'route' => 'finance.account-groups.index',
            'permission' => 'finance.account_groups.view',
            'icon' => 'fa fa-object-group',
        ],
        [
            'label' => $financeSidebarLabel('finance::account.account_types', 'Account Types'),
            'route' => 'finance.account-types.index',
            'permission' => 'finance.account_types.view',
            'icon' => 'fa fa-tags',
        ],
        [
            'label' => $financeSidebarLabel('finance::account.journal_entries', 'Journal Entries'),
            'route' => 'finance.journal.index',
            'permission' => 'finance.journal.view',
            'icon' => 'fa fa-exchange',
        ],
        [
            'label' => 'Bank Reconciliation',
            'route' => 'finance.bank-reconciliation.index',
            'permission' => 'finance.bank_reconciliation.view',
            'icon' => 'fa fa-balance-scale',
        ],
        [
            'label' => $financeSidebarLabel('finance::account.reports', 'Reports'),
            'route' => 'finance.reports.index',
            'permission' => 'finance.reports.account_book',
            'icon' => 'fa fa-bar-chart',
        ],
        [
            'label' => $financeSidebarLabel('finance::cheque.cheques', 'Cheques'),
            'route' => 'finance.cheques.index',
            'permission' => 'finance.cheques.view',
            'icon' => 'fa fa-money',
        ],
        [
            'label' => $financeSidebarLabel('finance::cheque.cheque_deposits', 'Cheque Deposits'),
            'route' => 'finance.deposits.index',
            'permission' => 'finance.deposits.view',
            'icon' => 'fa fa-bank',
        ],
        [
            'label' => $financeSidebarLabel('finance::account.settings', 'Settings'),
            'route' => 'finance.settings.index',
            'permission' => 'finance.account_settings.view',
            'icon' => 'fa fa-cog',
        ],
    ];
@endphp

@foreach($financeMenuItems as $item)
    @if(FinancePermissionHelper::can($item['permission']))
        <li class="{{ request()->routeIs($item['route']) ? 'active' : '' }}">
            <a href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}">
                <i class="{{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        </li>
    @endif
@endforeach
