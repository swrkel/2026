@extends('layouts.app')

@section('title', 'Finance Reports')

@section('content')
<section class="content-header">
    <h1>Finance Reports <small>Dashboard</small></h1>
</section>

<section class="content">
    @include('finance::finance_reports.partials.navigation')

    <form method="GET" action="{{ route('finance.reports.dashboard') }}" class="finance-report-filter">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Location</label>
                    <select name="location_id" class="form-control select2" style="width:100%">
                        <option value="all">All Locations</option>
                        @foreach($locations as $id => $name)
                            <option value="{{ $id }}" {{ (string)$location_id === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>From Date</label>
                    <input type="date" name="from_date" class="form-control" value="{{ $from_date }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>To Date</label>
                    <input type="date" name="to_date" class="form-control" value="{{ $to_date }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-filter"></i> Apply</button>
                </div>
            </div>
        </div>
    </form>

    <div class="finance-report-summary">
        <div class="finance-report-card"><span class="label">Active Accounts</span><span class="value">{{ number_format($metrics['active_accounts']) }}</span></div>
        <div class="finance-report-card"><span class="label">Entries for Period</span><span class="value">{{ number_format($metrics['entry_count']) }}</span></div>
        <div class="finance-report-card"><span class="label">Total Debit</span><span class="value">{{ number_format($metrics['total_debit'], 4) }}</span></div>
        <div class="finance-report-card"><span class="label">Total Credit</span><span class="value">{{ number_format($metrics['total_credit'], 4) }}</span></div>
        <div class="finance-report-card"><span class="label">Cash &amp; Bank Balance</span><span class="value">{{ number_format($metrics['cash_balance'], 4) }}</span></div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Available Reports</h3></div>
        <div class="box-body">
            <div class="row">
                @php
                    $reportLinks = [
                        ['Trial Balance', 'finance.reports.trial_balance', 'fa-balance-scale'],
                        ['General Ledger', 'finance.reports.general_ledger', 'fa-book'],
                        ['Account Ledger', 'finance.reports.account_ledger', 'fa-list-alt'],
                        ['Day Book', 'finance.reports.day_book', 'fa-calendar'],
                        ['Income Statement', 'finance.reports.income_statement', 'fa-line-chart'],
                        ['Balance Sheet', 'finance.reports.balance_sheet', 'fa-table'],
                        ['Profit & Loss', 'finance.reports.profit_loss', 'fa-area-chart'],
                        ['Cash Flow Statement', 'finance.reports.cash_flow_statement', 'fa-money'],
                    ];
                @endphp
                @foreach($reportLinks as [$label, $routeName, $icon])
                    <div class="col-md-3 col-sm-6">
                        <a href="{{ route($routeName) }}" class="finance-report-card" style="display:block;margin-bottom:15px;text-decoration:none">
                            <span class="label"><i class="fa {{ $icon }}"></i> Finance Report</span>
                            <span class="value" style="font-size:17px">{{ $label }}</span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endsection
