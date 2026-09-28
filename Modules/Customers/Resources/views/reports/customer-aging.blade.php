@extends('layouts.app')

@section('title', 'Customer Aging Report')

@section('content')
<section class="content-header">
    <h1>Customer Aging Report <small>Customers Module</small></h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border clearfix">
            <h3 class="box-title pull-left">Aging Summary</h3>
            <div class="pull-right">
                <a href="{{ route('customers.reports.aging.export') }}" class="btn btn-success btn-sm"><i class="fa fa-download"></i> CSV</a>
                <button type="button" onclick="window.print()" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print</button>
                <a href="{{ route('customers.reports.index') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Reports</a>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Bucket</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Current</td><td class="text-right">{{ number_format((float)($aging['current'] ?? 0), 2) }}</td></tr>
                    <tr><td>1 - 30 Days</td><td class="text-right">{{ number_format((float)($aging['days_1_30'] ?? 0), 2) }}</td></tr>
                    <tr><td>31 - 60 Days</td><td class="text-right">{{ number_format((float)($aging['days_31_60'] ?? 0), 2) }}</td></tr>
                    <tr><td>61 - 90 Days</td><td class="text-right">{{ number_format((float)($aging['days_61_90'] ?? 0), 2) }}</td></tr>
                    <tr><td>Over 90 Days</td><td class="text-right">{{ number_format((float)($aging['over_90'] ?? 0), 2) }}</td></tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th class="text-right">{{ number_format(array_sum($aging ?? []), 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Customer Aging Details</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Customer Code</th>
                        <th>Customer</th>
                        <th>Last Entry Date</th>
                        <th class="text-right">Age Days</th>
                        <th>Bucket</th>
                        <th class="text-right">Outstanding</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($aging_detail ?? collect()) as $row)
                        <tr>
                            <td>{{ $row->customer_code }}</td>
                            <td>{{ $row->customer_name }}</td>
                            <td>{{ $row->last_date }}</td>
                            <td class="text-right">{{ number_format($row->age_days) }}</td>
                            <td>{{ $row->bucket }}</td>
                            <td class="text-right">{{ number_format((float) $row->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No outstanding customer entries found.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-right">Total</th>
                        <th class="text-right">{{ number_format(collect($aging_detail ?? [])->sum('amount'), 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</section>
@endsection
