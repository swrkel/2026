@extends('suppliers::layouts.app')

@section('title', 'Issued Payment Details')
@section('module_title', 'Issued Payment Details')

@section('suppliers_content')
<div class="box box-primary supplier-list-box">
    <div class="box-header with-border">
        <h3 class="box-title">Supplier Issued Payment Details</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('suppliers.dashboard') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
        </div>
    </div>
    <div class="box-body table-responsive supplier-table-wrap">
        <table class="table table-bordered table-striped supplier-standard-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Supplier Code</th>
                    <th>Supplier</th>
                    <th>Method</th>
                    <th>Reference No</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ !empty($payment->paid_on) ? date('Y-m-d H:i', strtotime($payment->paid_on)) : '' }}</td>
                        <td>{{ $payment->supplier_code }}</td>
                        <td>{{ $payment->supplier_business_name ?: $payment->supplier_name }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $payment->method ?? '')) }}</td>
                        <td>{{ $payment->payment_ref_no }}</td>
                        <td class="text-right">{{ number_format((float) $payment->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No supplier issued payment details found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
