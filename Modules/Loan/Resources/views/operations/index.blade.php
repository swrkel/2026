@extends('layouts.app')
@section('title', 'Loan Operations')

@section('content')
<section class="content-header no-print">
    <h1>Loan Operations <small>Portfolio overview</small></h1>
</section>

<section class="content no-print">
    <div class="row">
        <div class="col-md-3 col-sm-6 col-xs-12"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-list"></i></span><div class="info-box-content"><span class="info-box-text">Total Loans</span><span class="info-box-number">{{ number_format($summary['total_loans'] ?? 0) }}</span></div></div></div>
        <div class="col-md-3 col-sm-6 col-xs-12"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-check"></i></span><div class="info-box-content"><span class="info-box-text">Active Loans</span><span class="info-box-number">{{ number_format($summary['active_loans'] ?? 0) }}</span></div></div></div>
        <div class="col-md-3 col-sm-6 col-xs-12"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-clock-o"></i></span><div class="info-box-content"><span class="info-box-text">Pending</span><span class="info-box-number">{{ number_format($summary['pending_loans'] ?? 0) }}</span></div></div></div>
        <div class="col-md-3 col-sm-6 col-xs-12"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-money"></i></span><div class="info-box-content"><span class="info-box-text">Total Disbursed</span><span class="info-box-number">{{ number_format($summary['total_disbursed'] ?? 0, 2) }}</span></div></div></div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Recent Loans</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr><th>ID</th><th>Customer</th><th>Status</th><th>Amount</th><th>Created</th><th>Action</th></tr>
                </thead>
                <tbody>
                    @forelse($recentLoans as $loan)
                        <tr>
                            <td>{{ $loan->id ?? '' }}</td>
                            <td>{{ $loan->customer_id ?? $loan->contact_id ?? $loan->loan_customer_id ?? '-' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $loan->status ?? '-')) }}</td>
                            <td>{{ number_format($loan->principal_amount ?? $loan->loan_amount ?? $loan->approved_amount ?? $loan->amount ?? 0, 2) }}</td>
                            <td>{{ $loan->created_at ?? '-' }}</td>
                            <td><a class="btn btn-xs btn-primary" href="{{ url('loan/loans/' . ($loan->id ?? 0) . '/statement') }}">Statement</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No loan records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
