@extends('layouts.app')

@section('title', 'Customer Transaction Report')

@section('content')
<section class="content-header">
    <h1>Customer Transaction Report <small>Customers Module</small></h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border clearfix">
            <h3 class="box-title pull-left">Customer Transactions</h3>
            <div class="pull-right">
                <a href="{{ route('customers.reports.transactions.export') }}" class="btn btn-success btn-sm"><i class="fa fa-download"></i> CSV</a>
                <button type="button" onclick="window.print()" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print</button>
                <a href="{{ route('customers.reports.index') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Reports</a>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Reference</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ !empty($row->transaction_date) ? date('Y-m-d', strtotime($row->transaction_date)) : '-' }}</td>
                            <td>{{ $row->customer_name ?? '-' }}<br><small>{{ $row->customer_code ?? '' }}</small></td>
                            <td>{{ $row->invoice_no ?? $row->ref_no ?? '-' }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $row->type ?? '-')) }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $row->payment_status ?? $row->status ?? '-')) }}</td>
                            <td class="text-right">{{ number_format((float)($row->final_total ?? 0), 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No transaction records found.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-right">Total</th>
                        <th class="text-right">{{ number_format((float)($summary['total_amount'] ?? 0), 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</section>
@endsection
