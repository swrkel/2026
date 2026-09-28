@extends('layouts.app')

@section('title', 'Customer Balance Report')

@section('content')
<style>
.customers-balance-print-header {
    display: none;
}

@media print {
    .customers-balance-page-heading,
    .customers-balance-report-actions,
    .customers-hide-global-print-meta {
        display: none !important;
    }

    .customers-balance-print-header {
        display: block !important;
        text-align: center;
        margin: 0 0 14px;
        padding: 0 0 10px;
        border-bottom: 1px solid #d7dee8;
    }

    .customers-balance-print-header .business-name {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        line-height: 1.25;
    }

    .customers-balance-print-header .location-name {
        margin: 4px 0 0;
        font-size: 12px;
        font-weight: 600;
    }
}
</style>

<div class="customers-balance-print-header" aria-hidden="true">
    <div class="business-name">{{ $printBusinessName ?? config('app.name') }}</div>
    <div class="location-name">Location: {{ $printLocationName ?? 'All Locations' }}</div>
</div>

<section class="content-header customers-balance-page-heading">
    <h1>Customer Balance Report <small>Customers Module</small></h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fa fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Customers</span>
                    <span class="info-box-number">{{ number_format($summary['total_customers'] ?? 0) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-red"><i class="fa fa-money"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Balance</span>
                    <span class="info-box-number">{{ number_format((float)($summary['total_balance'] ?? 0), 2) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-credit-card"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Credit Limit</span>
                    <span class="info-box-number">{{ number_format((float)($summary['total_credit_limit'] ?? 0), 2) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Available Credit</span>
                    <span class="info-box-number">{{ number_format((float)($summary['total_available_credit'] ?? 0), 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border clearfix">
            <h3 class="box-title pull-left">Customer Balance</h3>
            <div class="pull-right customers-balance-report-actions">
                <a href="{{ route('customers.reports.balance.export') }}" class="btn btn-success btn-sm"><i class="fa fa-download"></i> CSV</a>
                <button type="button" id="customers_balance_print_button" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print</button>
                <a href="{{ route('customers.reports.index') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Reports</a>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>Customer Code</th>
                        <th>Customer</th>
                        <th>Mobile</th>
                        <th class="text-right">Credit Limit</th>
                        <th class="text-right">Balance</th>
                        <th class="text-right">Available Credit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->customer_code ?? '-' }}</td>
                            <td>{{ $row->customer_name ?? '-' }}</td>
                            <td>{{ $row->mobile ?? '-' }}</td>
                            <td class="text-right">{{ number_format((float)($row->credit_limit ?? 0), 2) }}</td>
                            <td class="text-right">{{ number_format((float)($row->balance ?? 0), 2) }}</td>
                            <td class="text-right">{{ number_format((float)($row->available_credit ?? 0), 2) }}</td>
                            <td>{{ $row->status ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No balance records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
(function ($) {
    function markLegacyPrintMetadata() {
        $('table').each(function () {
            var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
            var isLegacyMetadata = text.indexOf('page title / name') !== -1
                && text.indexOf('date range selected') !== -1
                && text.indexOf('page no') !== -1;

            if (isLegacyMetadata) {
                $(this).addClass('customers-hide-global-print-meta');
            }
        });
    }

    $(function () {
        markLegacyPrintMetadata();

        $('#customers_balance_print_button').off('click.customersBalancePrint')
            .on('click.customersBalancePrint', function () {
                markLegacyPrintMetadata();
                window.setTimeout(function () {
                    window.print();
                }, 0);
            });
    });

    if (window.addEventListener) {
        window.addEventListener('beforeprint', markLegacyPrintMetadata);
    }

    if (window.MutationObserver) {
        var observer = new MutationObserver(markLegacyPrintMetadata);
        observer.observe(document.documentElement, {childList: true, subtree: true});
    }
})(jQuery);
</script>
@endsection
