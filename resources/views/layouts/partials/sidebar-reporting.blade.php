{{--
|--------------------------------------------------------------------------
| Enterprise Reporting Sidebar
|--------------------------------------------------------------------------
| This file was separated from the main sidebar to keep the ERP sidebar
| smaller, safer, and easier to maintain.
| File path:
| resources/views/layouts/partials/sidebar-reporting.blade.php
|--------------------------------------------------------------------------
--}}

@if ($loan_module)

    <li class="nav-item {{ in_array($request->segment(1), ['reporting']) ? 'active active-sub' : '' }}">

        <a class="nav-link collapsed"
           href="#"
           data-toggle="collapse"
           data-target="#enterprise-reporting-menu"
           aria-expanded="{{ in_array($request->segment(1), ['reporting']) ? 'true' : 'false' }}"
           aria-controls="enterprise-reporting-menu">

            <i class="fa fa-line-chart"></i>

            <span>
                Enterprise Reporting
            </span>

        </a>

        <div id="enterprise-reporting-menu"
             class="collapse {{ in_array($request->segment(1), ['reporting']) ? 'show' : '' }}"
             aria-labelledby="enterprise-reporting-menu"
             data-parent="#accordionSidebar">

            <div class="bg-white py-2 collapse-inner rounded">

                <h6 class="collapse-header">
                    Reporting Console
                </h6>

                <a class="collapse-item {{ $request->segment(1) == 'reporting' && $request->segment(2) == 'dashboard' ? 'active' : '' }}"
                   href="{{ url('/reporting/dashboard') }}">
                    Reporting Dashboard
                </a>

                <hr class="sidebar-divider">

                <h6 class="collapse-header">
                    Branch Accounting
                </h6>

                <a class="collapse-item {{ $request->segment(1) == 'reporting' && $request->segment(2) == 'branch-profit-loss' ? 'active' : '' }}"
                   href="{{ url('/reporting/branch-profit-loss') }}">
                    Branch Wise Profit &amp; Loss
                </a>

                <a class="collapse-item {{ $request->segment(1) == 'reporting' && $request->segment(2) == 'branch-balance-sheet' ? 'active' : '' }}"
                   href="{{ url('/reporting/branch-balance-sheet') }}">
                    Branch Balance Sheet
                </a>

                <a class="collapse-item {{ $request->segment(1) == 'reporting' && $request->segment(2) == 'branch-trial-balance' ? 'active' : '' }}"
                   href="{{ url('/reporting/branch-trial-balance') }}">
                    Branch Trial Balance
                </a>

                <a class="collapse-item {{ $request->segment(1) == 'reporting' && $request->segment(2) == 'branch-cash-bank' ? 'active' : '' }}"
                   href="{{ url('/reporting/branch-cash-bank') }}">
                    Branch Cash / Bank
                </a>

                <hr class="sidebar-divider">

                <h6 class="collapse-header">
                    Consolidated Accounting
                </h6>

                <a class="collapse-item {{ $request->segment(1) == 'reporting' && $request->segment(2) == 'consolidated-profit-loss' ? 'active' : '' }}"
                   href="{{ url('/reporting/consolidated-profit-loss') }}">
                    Consolidated Profit &amp; Loss
                </a>

                <a class="collapse-item {{ $request->segment(1) == 'reporting' && $request->segment(2) == 'consolidated-balance-sheet' ? 'active' : '' }}"
                   href="{{ url('/reporting/consolidated-balance-sheet') }}">
                    Consolidated Balance Sheet
                </a>

                <a class="collapse-item {{ $request->segment(1) == 'reporting' && $request->segment(2) == 'consolidated-trial-balance' ? 'active' : '' }}"
                   href="{{ url('/reporting/consolidated-trial-balance') }}">
                    Consolidated Trial Balance
                </a>

                <hr class="sidebar-divider">

                <h6 class="collapse-header">
                    Loan &amp; Recovery Reports
                </h6>

                <a class="collapse-item {{ $request->segment(1) == 'reporting' && $request->segment(2) == 'loan-portfolio' ? 'active' : '' }}"
                   href="{{ url('/reporting/loan-portfolio') }}">
                    Loan Portfolio Reports
                </a>

                <a class="collapse-item {{ $request->segment(1) == 'reporting' && $request->segment(2) == 'recovery' ? 'active' : '' }}"
                   href="{{ url('/reporting/recovery') }}">
                    Recovery Reports
                </a>

                <a class="collapse-item {{ $request->segment(1) == 'reporting' && $request->segment(2) == 'collections' ? 'active' : '' }}"
                   href="{{ url('/reporting/collections') }}">
                    Collections Reports
                </a>

                <hr class="sidebar-divider">

                <h6 class="collapse-header">
                    Governance Reports
                </h6>

                <a class="collapse-item {{ $request->segment(1) == 'reporting' && $request->segment(2) == 'compliance' ? 'active' : '' }}"
                   href="{{ url('/reporting/compliance') }}">
                    Compliance Reports
                </a>

                <a class="collapse-item {{ $request->segment(1) == 'reporting' && $request->segment(2) == 'governance' ? 'active' : '' }}"
                   href="{{ url('/reporting/governance') }}">
                    Governance Reports
                </a>

                <a class="collapse-item {{ $request->segment(1) == 'reporting' && $request->segment(2) == 'audit' ? 'active' : '' }}"
                   href="{{ url('/reporting/audit') }}">
                    Audit Reports
                </a>

            </div>

        </div>

    </li>

@endif
