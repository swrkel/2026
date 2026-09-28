@extends('layouts.app')

@section('title', __('Enterprise Reporting'))

@section('content')

<section class="content-header">
    <h1>
        Enterprise Reporting
        <small>Multi-Tenant & Multi-Branch Reporting Dashboard</small>
    </h1>
</section>

<section class="content">

    <div class="row">

        <div class="col-md-3 col-sm-6 col-xs-12">

            <div class="info-box">

                <span class="info-box-icon bg-aqua">
                    <i class="fa fa-building"></i>
                </span>

                <div class="info-box-content">

                    <span class="info-box-text">
                        Branch Reports
                    </span>

                    <span class="info-box-number">
                        Active
                    </span>

                </div>

            </div>

        </div>

        <div class="col-md-3 col-sm-6 col-xs-12">

            <div class="info-box">

                <span class="info-box-icon bg-green">
                    <i class="fa fa-sitemap"></i>
                </span>

                <div class="info-box-content">

                    <span class="info-box-text">
                        Consolidated Reports
                    </span>

                    <span class="info-box-number">
                        Enabled
                    </span>

                </div>

            </div>

        </div>

        <div class="col-md-3 col-sm-6 col-xs-12">

            <div class="info-box">

                <span class="info-box-icon bg-yellow">
                    <i class="fa fa-line-chart"></i>
                </span>

                <div class="info-box-content">

                    <span class="info-box-text">
                        Financial Reports
                    </span>

                    <span class="info-box-number">
                        Ready
                    </span>

                </div>

            </div>

        </div>

        <div class="col-md-3 col-sm-6 col-xs-12">

            <div class="info-box">

                <span class="info-box-icon bg-red">
                    <i class="fa fa-book"></i>
                </span>

                <div class="info-box-content">

                    <span class="info-box-text">
                        General Ledger
                    </span>

                    <span class="info-box-number">
                        Enabled
                    </span>

                </div>

            </div>

        </div>

    </div>

    <div class="row">

        <!-- Financial Reporting -->
        <div class="col-md-6">

            <div class="box box-primary">

                <div class="box-header with-border">

                    <h3 class="box-title">

                        <i class="fa fa-line-chart"></i>

                        Financial Reporting

                    </h3>

                </div>

                <div class="box-body">

                    <div class="list-group">

                        <a href="{{ route('reporting.branch.pnl') }}"
                           class="list-group-item">

                            <i class="fa fa-building text-aqua"></i>

                            Branch Wise Profit & Loss

                            <span class="pull-right">
                                <i class="fa fa-angle-right"></i>
                            </span>

                        </a>

                        <a href="{{ route('reporting.consolidated.pnl') }}"
                           class="list-group-item">

                            <i class="fa fa-sitemap text-green"></i>

                            Consolidated Profit & Loss

                            <span class="pull-right">
                                <i class="fa fa-angle-right"></i>
                            </span>

                        </a>

                        <a href="{{ route('reporting.branch.balance_sheet') }}"
                           class="list-group-item">

                            <i class="fa fa-balance-scale text-yellow"></i>

                            Branch Balance Sheet

                            <span class="pull-right">
                                <i class="fa fa-angle-right"></i>
                            </span>

                        </a>

                        <a href="{{ route('reporting.consolidated.balance_sheet') }}"
                           class="list-group-item">

                            <i class="fa fa-bank text-green"></i>

                            Consolidated Balance Sheet

                            <span class="pull-right">
                                <i class="fa fa-angle-right"></i>
                            </span>

                        </a>

                        <a href="{{ route('reporting.branch.trial_balance') }}"
                           class="list-group-item">

                            <i class="fa fa-list-alt text-aqua"></i>

                            Branch Trial Balance

                            <span class="pull-right">
                                <i class="fa fa-angle-right"></i>
                            </span>

                        </a>

                        <a href="{{ route('reporting.consolidated.trial_balance') }}"
                           class="list-group-item">

                            <i class="fa fa-files-o text-green"></i>

                            Consolidated Trial Balance

                            <span class="pull-right">
                                <i class="fa fa-angle-right"></i>
                            </span>

                        </a>

                        <a href="{{ route('reporting.general_ledger') }}"
                           class="list-group-item">

                            <i class="fa fa-book text-red"></i>

                            General Ledger

                            <span class="pull-right">
                                <i class="fa fa-angle-right"></i>
                            </span>

                        </a>

                    </div>

                </div>

            </div>

        </div>

        <!-- Operational Reporting -->
        <div class="col-md-6">

            <div class="box box-success">

                <div class="box-header with-border">

                    <h3 class="box-title">

                        <i class="fa fa-dashboard"></i>

                        Operational & Governance Reporting

                    </h3>

                </div>

                <div class="box-body">

                    <div class="list-group">

                        <a href="{{ route('reporting.recovery') }}"
                           class="list-group-item">

                            <i class="fa fa-money text-green"></i>

                            Loan Recovery Reports

                            <span class="pull-right">
                                <i class="fa fa-angle-right"></i>
                            </span>

                        </a>

                        <a href="{{ route('reporting.compliance') }}"
                           class="list-group-item">

                            <i class="fa fa-shield text-red"></i>

                            Compliance Reports

                            <span class="pull-right">
                                <i class="fa fa-angle-right"></i>
                            </span>

                        </a>

                        <a href="{{ route('reporting.governance') }}"
                           class="list-group-item">

                            <i class="fa fa-university text-yellow"></i>

                            Governance Reports

                            <span class="pull-right">
                                <i class="fa fa-angle-right"></i>
                            </span>

                        </a>

                    </div>

                </div>

            </div>

            <div class="box box-warning">

                <div class="box-header with-border">

                    <h3 class="box-title">

                        <i class="fa fa-info-circle"></i>

                        Enterprise Accounting Mode

                    </h3>

                </div>

                <div class="box-body">

                    <p>
                        Reporting module is fully prepared for enterprise multi-tenant and multi-branch accounting.
                    </p>

                    <ul>

                        <li>
                            Each tenant is controlled by
                            <code>business_id</code>
                        </li>

                        <li>
                            Each branch is controlled by
                            <code>location_id</code>
                        </li>

                        <li>
                            Real accounting balances are powered by
                            <code>account_transactions</code>
                        </li>

                        <li>
                            General Ledger supports account-level drill-down
                        </li>

                        <li>
                            Head office can view consolidated reports across all branches
                        </li>

                        <li>
                            Trial Balance, P&L and Balance Sheet are now fully transaction-driven
                        </li>

                    </ul>

                </div>

            </div>

        </div>

    </div>

</section>

@endsection