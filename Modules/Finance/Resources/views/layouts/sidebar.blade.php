@php
    use Modules\Finance\Utils\FinancePermissionHelper;

    $financeParentLabel = __('finance::account.finance');
    if ($financeParentLabel === 'finance::account.finance') {
        $financeParentLabel = 'Finance Module';
    }

    $canSeeFinance = FinancePermissionHelper::any([
        'finance.dashboard',
        'finance.accounts.view',
        'finance.account_groups.view',
        'finance.account_types.view',
        'finance.account_settings.view',
        'finance.journal.view',
        'finance.bank_reconciliation.view',
        'finance.reports.account_book',
        'finance.cheques.view',
        'finance.deposits.view',
        'finance.payments.view',
        'finance.expenses.view',
    ]);
@endphp

@if($canSeeFinance)
    <li class="treeview {{ request()->is('finance*') ? 'active menu-open' : '' }}">
        <a href="#">
            <i class="fa fa-calculator"></i>
            <span>{{ $financeParentLabel }}</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
            </span>
        </a>
        <ul class="treeview-menu">
            @include('finance::layouts.partials.menu')
        </ul>
    </li>
@endif
