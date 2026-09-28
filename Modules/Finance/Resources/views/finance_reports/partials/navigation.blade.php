<style>
    .finance-report-nav {display:flex;flex-wrap:wrap;gap:8px;margin-bottom:15px}
    .finance-report-nav .btn {border-radius:6px;font-weight:600;box-shadow:0 1px 2px rgba(0,0,0,.08)}
    .finance-report-nav .btn.active {background:#fff!important;color:#222!important;border-color:#777!important}
    .finance-report-filter {background:#fff;border:1px solid #e7e7e7;border-radius:8px;padding:15px;margin-bottom:15px;box-shadow:0 1px 4px rgba(0,0,0,.05)}
    .finance-report-summary {display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:15px}
    .finance-report-card {background:#fff;border:1px solid #e6e6e6;border-radius:8px;padding:15px;min-height:92px;box-shadow:0 1px 4px rgba(0,0,0,.05)}
    .finance-report-card .label {display:block;color:#666;font-size:12px;text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px}
    .finance-report-card .value {font-size:22px;font-weight:700;color:#222;word-break:break-word}
    .finance-report-table th {white-space:nowrap;background:#f7f7f7}
    .finance-report-table td {vertical-align:middle!important}
    .finance-report-empty {padding:35px;text-align:center;color:#777;background:#fafafa;border:1px dashed #ccc;border-radius:8px}
    .finance-report-pagination {display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:12px}
</style>

<div class="finance-report-nav">
    <a href="{{ route('finance.reports.dashboard') }}" class="btn btn-info {{ request()->routeIs('finance.reports.dashboard*') ? 'active' : '' }}">Dashboard</a>
    <a href="{{ route('finance.reports.trial_balance') }}" class="btn btn-primary {{ request()->routeIs('finance.reports.trial_balance') ? 'active' : '' }}">Trial Balance</a>
    <a href="{{ route('finance.reports.general_ledger') }}" class="btn btn-success {{ request()->routeIs('finance.reports.general_ledger*') ? 'active' : '' }}">General Ledger</a>
    <a href="{{ route('finance.reports.account_ledger') }}" class="btn btn-warning {{ request()->routeIs('finance.reports.account_ledger*') ? 'active' : '' }}">Account Ledger</a>
    <a href="{{ route('finance.reports.day_book') }}" class="btn btn-danger {{ request()->routeIs('finance.reports.day_book') ? 'active' : '' }}">Day Book</a>
    <a href="{{ route('finance.reports.income_statement') }}" class="btn btn-default {{ request()->routeIs('finance.reports.income_statement') ? 'active' : '' }}">Income Statement</a>
    <a href="{{ route('finance.reports.balance_sheet') }}" class="btn btn-info {{ request()->routeIs('finance.reports.balance_sheet') ? 'active' : '' }}">Balance Sheet</a>
    <a href="{{ route('finance.reports.profit_loss') }}" class="btn btn-success {{ request()->routeIs('finance.reports.profit_loss') ? 'active' : '' }}">Profit &amp; Loss</a>
    <a href="{{ route('finance.reports.cash_flow_statement') }}" class="btn btn-primary {{ request()->routeIs('finance.reports.cash_flow_statement') ? 'active' : '' }}">Cash Flow Statement</a>
</div>
