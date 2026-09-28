@php
    $loan_segments = ['loan'];
    $loan_report_segments = request()->segment(1) == 'report' && request()->segment(2) == 'contact_loan';

    $is_loan_dashboard = request()->segment(1) == 'loan' && in_array(request()->segment(2), [null, '', 'dashboard']);
    $is_loan_customers = request()->segment(1) == 'loan' && request()->segment(2) == 'customers';
    $is_loan_setup = request()->segment(1) == 'loan' && in_array(request()->segment(2), [
        'loan-products', 'loan-settings', 'purpose', 'collateral_type', 'charge', 'status', 'aml-kyc', 'sanctions-watchlist'
    ]);
    $is_loan_operations = request()->segment(1) == 'loan' && in_array(request()->segment(2), [
        'loan-applications', 'loans', 'create', 'create_client_loan', 'import', 'write-offs', 'treasury', 'compliance'
    ]);
    $is_loan_repayments = request()->segment(1) == 'loan' && in_array(request()->segment(2), [
        'repayments', 'repaymentbulk', 'bulk_import_repayments', 'servicing', 'settlements'
    ]);
    $is_loan_recovery = request()->segment(1) == 'loan' && in_array(request()->segment(2), [
        'collections', 'recovery', 'recovery-performance', 'recovery-priorities', 'collection-workflows', 'recovery-officer-productivity', 'promise-to-pay'
    ]);
    $is_loan_reports = (request()->segment(1) == 'loan' && in_array(request()->segment(2), [
        'reports', 'mis', 'portfolio-intelligence', 'par-analytics', 'risk-analytics'
    ])) || $loan_report_segments;
    $is_loan_utilities = request()->segment(1) == 'loan' && in_array(request()->segment(2), ['calculator', 'install']);
@endphp

<section class="no-print">

    <li class="nav-item {{ $is_loan_dashboard ? 'active' : '' }}">
        <a class="nav-link" href="{{ url('loan/dashboard') }}">
            <i class="fa fa-dashboard"></i>
            <span>Loan Dashboard</span>
        </a>
    </li>

    <li class="nav-item {{ $is_loan_customers ? 'active active-sub' : '' }}">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#loan-customers-menu"
           aria-expanded="{{ $is_loan_customers ? 'true' : 'false' }}" aria-controls="loan-customers-menu">
            <i class="fa fa-users"></i>
            <span>Loan Customers</span>
        </a>
        <div id="loan-customers-menu" class="collapse {{ $is_loan_customers ? 'show' : '' }}" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Loan Customers:</h6>
                <a class="collapse-item {{ request()->segment(2) == 'customers' && empty(request()->segment(3)) ? 'active' : '' }}" href="{{ url('loan/customers') }}">Customer List</a>
                <a class="collapse-item {{ request()->segment(2) == 'customers' && request()->segment(3) == 'create' ? 'active' : '' }}" href="{{ url('loan/customers/create') }}">Add Customer</a>
            </div>
        </div>
    </li>

    <li class="nav-item {{ $is_loan_operations ? 'active active-sub' : '' }}">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#loan-operations-menu"
           aria-expanded="{{ $is_loan_operations ? 'true' : 'false' }}" aria-controls="loan-operations-menu">
            <i class="fa fa-file-text-o"></i>
            <span>Loan Operations</span>
        </a>
        <div id="loan-operations-menu" class="collapse {{ $is_loan_operations ? 'show' : '' }}" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Loan Operations:</h6>
                <a class="collapse-item {{ request()->segment(2) == 'loan-applications' && empty(request()->segment(3)) ? 'active' : '' }}" href="{{ url('loan/loan-applications') }}">Loan Applications</a>
                <a class="collapse-item {{ request()->segment(2) == 'loan-applications' && request()->segment(3) == 'create' ? 'active' : '' }}" href="{{ url('loan/loan-applications/create') }}">New Application</a>
                <a class="collapse-item {{ request()->is('loan') ? 'active' : '' }}" href="{{ url('loan') }}">Active Loans</a>
                <a class="collapse-item {{ request()->segment(2) == 'create' ? 'active' : '' }}" href="{{ url('loan/create') }}">Create Loan</a>
                <a class="collapse-item {{ request()->segment(2) == 'import' ? 'active' : '' }}" href="{{ url('loan/import') }}">Import Loans</a>
                <a class="collapse-item {{ request()->segment(2) == 'write-offs' ? 'active' : '' }}" href="{{ url('loan/write-offs') }}">Write-Offs</a>
                <a class="collapse-item {{ request()->segment(2) == 'treasury' ? 'active' : '' }}" href="{{ url('loan/treasury') }}">Treasury</a>
                <a class="collapse-item {{ request()->segment(2) == 'compliance' ? 'active' : '' }}" href="{{ url('loan/compliance') }}">Compliance</a>
            </div>
        </div>
    </li>

    <li class="nav-item {{ $is_loan_repayments ? 'active active-sub' : '' }}">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#loan-repayments-menu"
           aria-expanded="{{ $is_loan_repayments ? 'true' : 'false' }}" aria-controls="loan-repayments-menu">
            <i class="fa fa-money"></i>
            <span>Repayments & Settlements</span>
        </a>
        <div id="loan-repayments-menu" class="collapse {{ $is_loan_repayments ? 'show' : '' }}" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Repayments:</h6>
                <a class="collapse-item {{ request()->segment(2) == 'servicing' ? 'active' : '' }}" href="{{ url('loan/servicing') }}">Loan Servicing</a>
                <a class="collapse-item {{ request()->segment(2) == 'repayments' ? 'active' : '' }}" href="{{ url('loan/repayments') }}">Repayments</a>
                <a class="collapse-item {{ request()->segment(2) == 'repaymentbulk' ? 'active' : '' }}" href="{{ url('loan/repaymentbulk') }}">Bulk Repayments</a>
                <a class="collapse-item {{ request()->segment(2) == 'bulk_import_repayments' ? 'active' : '' }}" href="{{ url('loan/bulk_import_repayments') }}">Import Repayments</a>
                <a class="collapse-item {{ request()->segment(2) == 'settlements' ? 'active' : '' }}" href="{{ url('loan/settlements') }}">Settlements</a>
            </div>
        </div>
    </li>

    <li class="nav-item {{ $is_loan_recovery ? 'active active-sub' : '' }}">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#loan-recovery-menu"
           aria-expanded="{{ $is_loan_recovery ? 'true' : 'false' }}" aria-controls="loan-recovery-menu">
            <i class="fa fa-line-chart"></i>
            <span>Collections & Recovery</span>
        </a>
        <div id="loan-recovery-menu" class="collapse {{ $is_loan_recovery ? 'show' : '' }}" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Collections:</h6>
                <a class="collapse-item {{ request()->segment(2) == 'collections' && request()->segment(3) == 'dashboard' ? 'active' : '' }}" href="{{ url('loan/collections/dashboard') }}">Collections Dashboard</a>
                <a class="collapse-item {{ request()->segment(2) == 'recovery' && request()->segment(3) == 'dashboard' ? 'active' : '' }}" href="{{ url('loan/recovery/dashboard') }}">Recovery Dashboard</a>
                <a class="collapse-item {{ request()->segment(2) == 'promise-to-pay' ? 'active' : '' }}" href="{{ url('loan/promise-to-pay') }}">Promise To Pay</a>
                <a class="collapse-item {{ request()->segment(2) == 'recovery' && request()->segment(3) == 'assignments' ? 'active' : '' }}" href="{{ url('loan/recovery/assignments') }}">Recovery Assignments</a>
                <a class="collapse-item {{ request()->segment(2) == 'recovery-performance' ? 'active' : '' }}" href="{{ url('loan/recovery-performance') }}">Recovery Performance</a>
                <a class="collapse-item {{ request()->segment(2) == 'recovery-priorities' ? 'active' : '' }}" href="{{ url('loan/recovery-priorities') }}">Smart Recovery Priorities</a>
                <a class="collapse-item {{ request()->segment(2) == 'collection-workflows' ? 'active' : '' }}" href="{{ url('loan/collection-workflows') }}">Collection Workflows</a>
                <a class="collapse-item {{ request()->segment(2) == 'recovery-officer-productivity' ? 'active' : '' }}" href="{{ url('loan/recovery-officer-productivity') }}">Officer Productivity</a>
            </div>
        </div>
    </li>

    <li class="nav-item {{ $is_loan_setup ? 'active active-sub' : '' }}">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#loan-setup-menu"
           aria-expanded="{{ $is_loan_setup ? 'true' : 'false' }}" aria-controls="loan-setup-menu">
            <i class="fa fa-cogs"></i>
            <span>Loan Setup</span>
        </a>
        <div id="loan-setup-menu" class="collapse {{ $is_loan_setup ? 'show' : '' }}" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Loan Setup:</h6>
                <a class="collapse-item {{ request()->segment(2) == 'loan-products' ? 'active' : '' }}" href="{{ url('loan/loan-products') }}">Loan Products</a>
                <a class="collapse-item {{ request()->segment(2) == 'purpose' ? 'active' : '' }}" href="{{ url('loan/purpose') }}">Loan Purposes</a>
                <a class="collapse-item {{ request()->segment(2) == 'collateral_type' ? 'active' : '' }}" href="{{ url('loan/collateral_type') }}">Collateral Types</a>
                <a class="collapse-item {{ request()->segment(2) == 'charge' ? 'active' : '' }}" href="{{ url('loan/charge') }}">Loan Charges</a>
                <a class="collapse-item {{ request()->segment(2) == 'status' ? 'active' : '' }}" href="{{ url('loan/status') }}">Loan Status</a>
                <a class="collapse-item {{ request()->segment(2) == 'loan-settings' ? 'active' : '' }}" href="{{ url('loan/loan-settings') }}">Loan Settings</a>
                <a class="collapse-item {{ request()->segment(2) == 'aml-kyc' ? 'active' : '' }}" href="{{ url('loan/aml-kyc') }}">AML / KYC</a>
                <a class="collapse-item {{ request()->segment(2) == 'sanctions-watchlist' ? 'active' : '' }}" href="{{ url('loan/sanctions-watchlist') }}">Sanctions Watchlist</a>
            </div>
        </div>
    </li>

    <li class="nav-item {{ $is_loan_reports ? 'active active-sub' : '' }}">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#loan-reports-menu"
           aria-expanded="{{ $is_loan_reports ? 'true' : 'false' }}" aria-controls="loan-reports-menu">
            <i class="fa fa-bar-chart"></i>
            <span>Reports & Analytics</span>
        </a>
        <div id="loan-reports-menu" class="collapse {{ $is_loan_reports ? 'show' : '' }}" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Loan Reports:</h6>
                <a class="collapse-item {{ $loan_report_segments && empty(request()->segment(3)) ? 'active' : '' }}" href="{{ url('report/contact_loan') }}">Loan Reports</a>
                <a class="collapse-item {{ request()->segment(2) == 'reports' && request()->segment(3) == 'portfolio-summary' ? 'active' : '' }}" href="{{ url('loan/reports/portfolio-summary') }}">Portfolio Summary / PAR</a>
                <a class="collapse-item {{ request()->segment(2) == 'mis' ? 'active' : '' }}" href="{{ url('loan/mis') }}">Executive MIS</a>
                <a class="collapse-item {{ request()->segment(2) == 'portfolio-intelligence' ? 'active' : '' }}" href="{{ url('loan/portfolio-intelligence') }}">Portfolio Intelligence</a>
                <a class="collapse-item {{ request()->segment(2) == 'par-analytics' ? 'active' : '' }}" href="{{ url('loan/par-analytics') }}">PAR Analytics</a>
                <a class="collapse-item {{ request()->segment(2) == 'risk-analytics' ? 'active' : '' }}" href="{{ url('loan/risk-analytics') }}">Operational Risk</a>
                <a class="collapse-item {{ request()->segment(3) == 'repayment' ? 'active' : '' }}" href="{{ url('report/contact_loan/repayment') }}">Repayment Report</a>
                <a class="collapse-item {{ request()->segment(3) == 'arrears' ? 'active' : '' }}" href="{{ url('report/contact_loan/arrears') }}">Arrears Report</a>
                <a class="collapse-item {{ request()->segment(3) == 'account_statement' ? 'active' : '' }}" href="{{ url('report/contact_loan/account_statement') }}">Account Statement</a>
            </div>
        </div>
    </li>

    <li class="nav-item {{ $is_loan_utilities ? 'active active-sub' : '' }}">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#loan-utilities-menu"
           aria-expanded="{{ $is_loan_utilities ? 'true' : 'false' }}" aria-controls="loan-utilities-menu">
            <i class="fa fa-calculator"></i>
            <span>Loan Utilities</span>
        </a>
        <div id="loan-utilities-menu" class="collapse {{ $is_loan_utilities ? 'show' : '' }}" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Utilities:</h6>
                <a class="collapse-item {{ request()->segment(2) == 'calculator' ? 'active' : '' }}" href="{{ url('loan/calculator') }}">Calculator</a>
            </div>
        </div>
    </li>

</section>
