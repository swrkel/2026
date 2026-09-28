@extends('layouts.app')
@section('title', 'Auto Service Payment History')
@section('content')
<section class="content-header"><h1>Auto Service Payment History <small>Customer Portal</small></h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ $selectedVehicle->registration_no ?? 'Vehicle' }}</h3>
            <div class="box-tools"><a href="{{ route('autoservice.customer_portal.lookup', ['q' => $keyword]) }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back to Portal</a></div>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-3"><div class="well well-sm"><small>Total Invoices</small><h3>{{ $invoices->count() }}</h3></div></div>
                <div class="col-md-3"><div class="well well-sm"><small>Total Payments</small><h3>{{ $payments->count() }}</h3></div></div>
                <div class="col-md-3"><div class="well well-sm"><small>Paid Amount</small><h3>{{ number_format((float)$payments->sum('amount'), 2) }}</h3></div></div>
                <div class="col-md-3"><div class="well well-sm"><small>Outstanding</small><h3>{{ number_format((float)$invoices->sum('balance_amount'), 2) }}</h3></div></div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Date</th><th>Receipt / Reference</th><th>Invoice No</th><th>Job No</th><th>Method</th><th class="text-right">Amount</th><th>Status</th><th>Note</th></tr></thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date ?? $payment->created_at }}</td>
                                <td>{{ $payment->payment_ref_no ?? $payment->reference_no ?? $payment->id }}</td>
                                <td>{{ $payment->invoice_no }}</td>
                                <td>{{ $payment->job_no }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method ?? $payment->method ?? '')) }}</td>
                                <td class="text-right">{{ number_format((float)($payment->amount ?? 0), 2) }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $payment->status ?? 'posted')) }}</td>
                                <td>{{ $payment->note ?? $payment->remarks ?? '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center">No payment history found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
