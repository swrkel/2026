@extends('customers::portal.layout')
@section('title', 'My Payments')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    @include('customers::portal.partials_summary')
    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">My Payments</h3></div>
        <div class="dd-card-body dd-table-wrap">
            <table class="dd-table">
                <thead><tr><th>Date</th><th>Reference No</th><th>Invoice No</th><th>Payment Method</th><th>Remarks</th><th class="text-right">Amount</th><th class="text-center">Actions</th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ !empty($row->paid_on) ? date('Y-m-d', strtotime($row->paid_on)) : '' }}</td>
                        <td>{{ $row->payment_ref_no ?: 'PAY-'.$row->id }}</td>
                        <td>{{ $row->invoice_no }}</td>
                        <td>{{ ucwords(str_replace('_',' ', $row->method)) }}</td>
                        <td>{{ $row->note }}</td>
                        <td class="text-right">{{ number_format((float)$row->amount, 2) }}</td>
                        <td class="text-center"><a class="dd-btn dd-btn-primary" target="_blank" href="{{ route('customers.portal.payments.print', $row->id) }}">Print</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">No payments found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
