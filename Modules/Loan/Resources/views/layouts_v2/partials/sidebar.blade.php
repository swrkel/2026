@php
    $loan_active = in_array(request()->segment(1), ['loan']) || (request()->segment(1) == 'report' && request()->segment(2) == 'contact_loan');
@endphp

@if($__is_loan_enabled ?? true)
<li class="treeview {{ $loan_active ? 'active active-sub' : '' }}" id="tour_step_loan_module">
    <a href="#">
        <i class="fa fa-bank"></i>
        <span>{{ __('loan::lang.loan') }}</span>
        <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
        </span>
    </a>
    <ul class="treeview-menu">
        <li class="{{ request()->segment(1) == 'loan' && request()->segment(2) == 'dashboard' ? 'active' : '' }}">
            <a href="{{ url('loan/dashboard') }}"><i class="fa fa-dashboard"></i> {{ __('messages.dashboard') }}</a>
        </li>

        <li class="{{ request()->segment(1) == 'loan' && request()->segment(2) == 'customers' ? 'active' : '' }}">
            <a href="{{ url('loan/customers') }}"><i class="fa fa-users"></i> Loan Customers</a>
        </li>

        <li class="{{ request()->segment(1) == 'loan' && request()->segment(2) == '' ? 'active' : '' }}">
            <a href="{{ url('loan') }}"><i class="fa fa-list"></i> @lang('loan::lang.view_loans')</a>
        </li>

        <li class="{{ request()->segment(1) == 'loan' && request()->segment(2) == 'create' ? 'active' : '' }}">
            <a href="{{ url('loan/create') }}"><i class="fa fa-plus-circle"></i> @lang('loan::lang.create_loan')</a>
        </li>

        <li class="{{ request()->segment(1) == 'loan' && request()->segment(2) == 'loan-applications' ? 'active' : '' }}">
            <a href="{{ url('loan/loan-applications') }}"><i class="fa fa-file-text-o"></i> Loan Applications</a>
        </li>


        <li class="{{ request()->segment(1) == 'loan' && request()->segment(2) == 'disbursements' ? 'active' : '' }}">
            <a href="{{ url('loan/disbursements') }}"><i class="fa fa-money"></i> Loan Disbursements</a>
        </li>

        <li class="{{ request()->segment(1) == 'loan' && request()->segment(2) == 'repaymentbulk' ? 'active' : '' }}">
            <a href="{{ url('loan/repaymentbulk') }}"><i class="fa fa-money"></i> @lang('loan::lang.bulk_repayments')</a>
        </li>

        <li class="{{ request()->segment(1) == 'loan' && request()->segment(2) == 'calculator' ? 'active' : '' }}">
            <a href="{{ url('loan/calculator') }}"><i class="fa fa-calculator"></i> {{ trans_choice('loan::general.calculator', 1) }}</a>
        </li>

        <li class="{{ request()->segment(1) == 'loan' && in_array(request()->segment(2), ['loan-products', 'products']) ? 'active' : '' }}">
            <a href="{{ url('loan/loan-products') }}"><i class="fa fa-cubes"></i> Loan Products</a>
        </li>

        <li class="{{ request()->segment(1) == 'loan' && in_array(request()->segment(2), ['loan-settings', 'setup']) ? 'active' : '' }}">
            <a href="{{ url('loan/loan-settings') }}"><i class="fa fa-cogs"></i> Loan Setup</a>
        </li>

        <li class="{{ request()->segment(1) == 'report' && request()->segment(2) == 'contact_loan' ? 'active' : '' }}">
            <a href="{{ url('report/contact_loan') }}"><i class="fa fa-bar-chart"></i> @lang('report.reports')</a>
        </li>
    </ul>
</li>
@endif
