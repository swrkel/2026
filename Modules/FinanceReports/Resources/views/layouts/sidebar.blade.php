<li class="treeview {{ request()->is('finance-reports*') ? 'active' : '' }}">
    <a href="#"><i class="fa fa-line-chart"></i> <span>Finance Reports</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
    <ul class="treeview-menu">
        <li><a href="{{ route('finance-reports.dashboard') }}">Dashboard</a></li>
        <li><a href="{{ route('finance-reports.executive-bi-dashboard-new') }}">Executive BI Dashboard - New</a></li>
        <li><a href="{{ route('finance-reports.report-engine-status') }}">Report Engine Status</a></li>
        <li class="header">Enterprise RC-1</li>
        <li><a href="{{ route('finance-reports.enterprise-center') }}">Enterprise Center</a></li>
        <li><a href="{{ route('finance-reports.consolidation-center-new') }}">Consolidation Center - New</a></li>
        <li><a href="{{ route('finance-reports.performance-center-new') }}">Performance Center - New</a></li>
        <li><a href="{{ route('finance-reports.cash-flow-forecast-new') }}">Cash Flow Forecast - New</a></li>
        <li><a href="{{ route('finance-reports.revenue-forecast-new') }}">Revenue Forecast - New</a></li>
        <li><a href="{{ route('finance-reports.expense-forecast-new') }}">Expense Forecast - New</a></li>
        <li><a href="{{ route('finance-reports.profit-forecast-new') }}">Profit Forecast - New</a></li>

        <li class="{{ request()->routeIs('finance-reports.trial-balance-new') ? 'active' : '' }}"><a href="{{ route('finance-reports.trial-balance-new') }}">Trial Balance - New</a></li>
        <li class="{{ request()->routeIs('finance-reports.balance-sheet-new') ? 'active' : '' }}"><a href="{{ route('finance-reports.balance-sheet-new') }}">Balance Sheet - New</a></li>
        <li class="{{ request()->routeIs('finance-reports.profit-loss-new') ? 'active' : '' }}"><a href="{{ route('finance-reports.profit-loss-new') }}">Profit & Loss - New</a></li>
        <li class="{{ request()->routeIs('finance-reports.income-statement-new') ? 'active' : '' }}"><a href="{{ route('finance-reports.income-statement-new') }}">Income Statement - New</a></li>
        <li class="{{ request()->routeIs('finance-reports.account-ledger-new') ? 'active' : '' }}"><a href="{{ route('finance-reports.account-ledger-new') }}">Account Ledger - New</a></li>
        <li class="{{ request()->routeIs('finance-reports.general-ledger-new') ? 'active' : '' }}"><a href="{{ route('finance-reports.general-ledger-new') }}">General Ledger - New</a></li>
        <li class="{{ request()->routeIs('finance-reports.cash-book-new') ? 'active' : '' }}"><a href="{{ route('finance-reports.cash-book-new') }}">Cash Book - New</a></li>
        <li class="{{ request()->routeIs('finance-reports.bank-book-new') ? 'active' : '' }}"><a href="{{ route('finance-reports.bank-book-new') }}">Bank Book - New</a></li>
        <li><a href="{{ route('finance-reports.journal-register-new') }}">Journal Register - New</a></li>
        <li><a href="{{ route('finance-reports.day-book-new') }}">Day Book - New</a></li>
        <li><a href="{{ route('finance-reports.financial-dashboard-new') }}">Financial Dashboard - New</a></li>
        <li><a href="{{ route('finance-reports.budget-vs-actual-new') }}">Budget vs Actual - New</a></li>
        <li><a href="{{ route('finance-reports.revenue-analysis-new') }}">Revenue Analysis - New</a></li>
        <li><a href="{{ route('finance-reports.expense-analysis-new') }}">Expense Analysis - New</a></li>
        <li><a href="{{ route('finance-reports.branch-performance-new') }}">Branch Performance - New</a></li>
        <li><a href="{{ route('finance-reports.financial-ratios-new') }}">Financial Ratios - New</a></li>
        <li><a href="{{ route('finance-reports.comparative-report-new') }}">Comparative Report - New</a></li>
        <li class="header">Receivables & Payables</li>
        <li><a href="{{ route('finance-reports.customer-outstanding-new') }}">Customer Outstanding - New</a></li>
        <li><a href="{{ route('finance-reports.supplier-outstanding-new') }}">Supplier Outstanding - New</a></li>
        <li><a href="{{ route('finance-reports.customer-aging-new') }}">Customer Aging - New</a></li>
        <li><a href="{{ route('finance-reports.supplier-aging-new') }}">Supplier Aging - New</a></li>
        <li><a href="{{ route('finance-reports.collection-analysis-new') }}">Collection Analysis - New</a></li>
        <li><a href="{{ route('finance-reports.payment-analysis-new') }}">Payment Analysis - New</a></li>
        <li><a href="{{ route('finance-reports.receivable-summary-new') }}">Receivable Summary - New</a></li>
        <li><a href="{{ route('finance-reports.payable-summary-new') }}">Payable Summary - New</a></li>
        <li><a href="{{ route('finance-reports.customer-statement-new') }}">Customer Statement - New</a></li>
        <li><a href="{{ route('finance-reports.supplier-statement-new') }}">Supplier Statement - New</a></li>
        <li class="header">Cash, Banking & Treasury</li>
        <li><a href="{{ route('finance-reports.cash-flow-statement-new') }}">Cash Flow Statement - New</a></li>
        <li><a href="{{ route('finance-reports.cash-position-report-new') }}">Cash Position Report - New</a></li>
        <li><a href="{{ route('finance-reports.bank-position-report-new') }}">Bank Position Report - New</a></li>
        <li><a href="{{ route('finance-reports.bank-reconciliation-new') }}">Bank Reconciliation - New</a></li>
        <li><a href="{{ route('finance-reports.cheque-register-new') }}">Cheque Register - New</a></li>
        <li><a href="{{ route('finance-reports.post-dated-cheque-register-new') }}">Post-Dated Cheque Register - New</a></li>
        <li><a href="{{ route('finance-reports.cash-movement-analysis-new') }}">Cash Movement Analysis - New</a></li>

        <li class="header">Audit, Compliance & Controls</li>
        <li><a href="{{ route('finance-reports.audit-trail-new') }}">Audit Trail - New</a></li>
        <li><a href="{{ route('finance-reports.transaction-history-new') }}">Transaction History - New</a></li>
        <li><a href="{{ route('finance-reports.user-financial-activity-new') }}">User Financial Activity - New</a></li>
        <li><a href="{{ route('finance-reports.deleted-transactions-new') }}">Deleted Transactions - New</a></li>
        <li><a href="{{ route('finance-reports.edited-transactions-new') }}">Edited Transactions - New</a></li>
        <li><a href="{{ route('finance-reports.voucher-approval-history-new') }}">Voucher Approval History - New</a></li>
        <li><a href="{{ route('finance-reports.exception-report-new') }}">Exception Report - New</a></li>
        <li><a href="{{ route('finance-reports.financial-log-viewer-new') }}">Financial Log Viewer - New</a></li>


        <li class="header">Fixed Assets & Capital Assets</li>
        <li><a href="{{ route('finance-reports.fixed-asset-dashboard-new') }}">Fixed Asset Dashboard - New</a></li>
        <li><a href="{{ route('finance-reports.fixed-asset-register-new') }}">Fixed Asset Register - New</a></li>
        <li><a href="{{ route('finance-reports.depreciation-register-new') }}">Depreciation Register - New</a></li>
        <li><a href="{{ route('finance-reports.asset-movement-register-new') }}">Asset Movement Register - New</a></li>
        <li><a href="{{ route('finance-reports.asset-transfer-report-new') }}">Asset Transfer Report - New</a></li>
        <li><a href="{{ route('finance-reports.asset-disposal-register-new') }}">Asset Disposal Register - New</a></li>
        <li><a href="{{ route('finance-reports.asset-category-summary-new') }}">Asset Category Summary - New</a></li>
        <li><a href="{{ route('finance-reports.asset-valuation-report-new') }}">Asset Valuation Report - New</a></li>
    </ul>
</li>
