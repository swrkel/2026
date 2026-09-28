@extends('customers::portal.layout')
@section('title', 'My Invoices')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    @include('customers::portal.partials_summary')
    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">My Invoices</h3></div>
        <div class="dd-card-body dd-table-wrap">
            <table class="dd-table">
                <thead><tr><th>Invoice No</th><th>Date</th><th>Type</th><th>Status</th><th class="text-right">Amount</th><th class="text-center">Actions</th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->invoice_no ?: $row->ref_no }}</td>
                        <td>{{ !empty($row->transaction_date) ? date('Y-m-d', strtotime($row->transaction_date)) : '' }}</td>
                        <td>{{ ucwords(str_replace('_',' ', $row->type)) }}</td>
                        <td><span class="dd-badge {{ $row->payment_status == 'paid' ? 'dd-badge-paid' : 'dd-badge-due' }}">{{ ucwords(str_replace('_',' ', $row->payment_status ?: 'Due')) }}</span></td>
                        <td class="text-right">{{ number_format((float)$row->final_total, 2) }}</td>
                        <td class="text-center"><a class="dd-btn dd-btn-primary" target="_blank" href="{{ route('customers.portal.invoices.print', $row->id) }}">Print</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">No invoices found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
