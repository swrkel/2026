{{--
|--------------------------------------------------------------------------
| Loan Module Sidebar - LOAN-36 Final Cleanup & Optimization
|--------------------------------------------------------------------------
| File path:
| Modules/Loan/Resources/views/layouts/partials/sidebar.blade.php
|
| Notes:
| - Clean Loan Module sidebar for UAT.
| - Removes obsolete old governance screens from the visible menu.
| - Keeps the sidebar inside the Loan module.
| - The global sidebar should include this file once only:
|   @includeIf('loan::layouts.partials.sidebar')
|--------------------------------------------------------------------------
--}}

@php
    $loan_segment_1 = request()->segment(1);
    $loan_segment_2 = request()->segment(2);
    $loan_segment_3 = request()->segment(3);
@endphp

@if (!empty($loan_module))
    @can('loan_module.access')
        <li class="nav-item {{ $loan_segment_1 == 'loan' ? 'active active-sub' : '' }}">
            <a class="nav-link collapsed"
               href="#"
               data-toggle="collapse"
               data-target="#loan-module-menu"
               aria-expanded="{{ $loan_segment_1 == 'loan' ? 'true' : 'false' }}"
               aria-controls="loan-module-menu">
                <i class="fa fa-bank"></i>
                <span>Loan Module</span>
            </a>

            <div id="loan-module-menu"
                 class="collapse {{ $loan_segment_1 == 'loan' ? 'show' : '' }}"
                 data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <h6 class="collapse-header">Loan Operations</h6>

                    <a class="collapse-item {{ $loan_segment_2 == 'dashboard' ? 'active' : '' }}"
                       href="{{ url('/loan/dashboard') }}">
                        Dashboard
                    </a>

                    <a class="collapse-item {{ $loan_segment_2 == 'customers' ? 'active' : '' }}"
                       href="{{ url('/loan/customers') }}">
                        Loan Customers
                    </a>

                    <a class="collapse-item {{ $loan_segment_2 == 'loan-products' ? 'active' : '' }}"
                       href="{{ url('/loan/loan-products') }}">
                        Loan Products
                    </a>

                    <a class="collapse-item {{ $loan_segment_2 == 'loan-applications' ? 'active' : '' }}"
                       href="{{ url('/loan/loan-applications') }}">
                        Loan Applications
                    </a>

                    <a class="collapse-item {{ $loan_segment_2 == 'loans' ? 'active' : '' }}"
                       href="{{ url('/loan/loans') }}">
                        Active Loans
                    </a>

                    <a class="collapse-item {{ $loan_segment_2 == 'disbursements' ? 'active' : '' }}"
                       href="{{ url('/loan/disbursements') }}">
                        Disbursement Workflow
                    </a>

                    <a class="collapse-item {{ $loan_segment_2 == 'repayments' ? 'active' : '' }}"
                       href="{{ url('/loan/repayments') }}">
                        Repayment Processing
                    </a>

                    <hr class="sidebar-divider">
                    <h6 class="collapse-header">Collections</h6>

                    <a class="collapse-item {{ $loan_segment_2 == 'collections' ? 'active' : '' }}"
                       href="{{ url('/loan/collections/dashboard') }}">
                        Collections & Recovery
                    </a>

                    <a class="collapse-item {{ $loan_segment_2 == 'promise-to-pay' ? 'active' : '' }}"
                       href="{{ url('/loan/promise-to-pay') }}">
                        Promise To Pay
                    </a>

                    <a class="collapse-item {{ $loan_segment_2 == 'recovery' && $loan_segment_3 == 'assignments' ? 'active' : '' }}"
                       href="{{ url('/loan/recovery/assignments') }}">
                        Recovery Assignments
                    </a>

                    <a class="collapse-item {{ $loan_segment_2 == 'write-offs' ? 'active' : '' }}"
                       href="{{ url('/loan/write-offs') }}">
                        Write-Offs
                    </a>

                    <hr class="sidebar-divider">
                    <h6 class="collapse-header">Reports</h6>

                    <a class="collapse-item {{ $loan_segment_2 == 'reports' ? 'active' : '' }}"
                       href="{{ url('/loan/reports') }}">
                        Reports & Analytics
                    </a>

                    <a class="collapse-item {{ $loan_segment_2 == 'mis' ? 'active' : '' }}"
                       href="{{ url('/loan/mis') }}">
                        MIS Dashboard
                    </a>

                    <a class="collapse-item {{ $loan_segment_2 == 'portfolio-intelligence' ? 'active' : '' }}"
                       href="{{ url('/loan/portfolio-intelligence') }}">
                        Portfolio Intelligence
                    </a>

                    <a class="collapse-item {{ $loan_segment_2 == 'par-analytics' ? 'active' : '' }}"
                       href="{{ url('/loan/par-analytics') }}">
                        PAR Analytics
                    </a>

                    <a class="collapse-item {{ $loan_segment_2 == 'risk-analytics' ? 'active' : '' }}"
                       href="{{ url('/loan/risk-analytics') }}">
                        Risk Analytics
                    </a>

                    <hr class="sidebar-divider">
                    <h6 class="collapse-header">Setup</h6>

                    <a class="collapse-item {{ $loan_segment_2 == 'loan-settings' ? 'active' : '' }}"
                       href="{{ url('/loan/loan-settings') }}">
                        Loan Setup
                    </a>
                </div>
            </div>
        </li>
    @endcan
@endif
