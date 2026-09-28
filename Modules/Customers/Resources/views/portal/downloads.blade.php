@extends('customers::portal.layout')
@section('title', 'Download Center')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    @include('customers::portal.partials_summary')

    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">Download Center</h3></div>
        <div class="dd-card-body">
            <p>Download or print your own statements, invoices and payment receipts.</p>
        </div>
    </div>

    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">Statements</h3></div>
        <div class="dd-card-body dd-table-wrap">
            <table class="dd-table">
                <thead><tr><th>Document</th><th>Date</th><th class="text-center">Actions</th></tr></thead>
                <tbody>
                @foreach($items['statements'] as $item)
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td>{{ $item->date }}</td>
                        <td class="text-center">
                            <a class="dd-btn dd-btn-default" href="{{ $item->view_url }}">View</a>
                            <a class="dd-btn dd-btn-primary" target="_blank" href="{{ $item->print_url }}">Print</a>
                            <a class="dd-btn dd-btn-default" href="{{ $item->download_url }}">CSV</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">Invoices</h3></div>
        <div class="dd-card-body dd-table-wrap">
            <table class="dd-table">
                <thead><tr><th>Date</th><th>Invoice No</th><th>Status</th><th class="text-right">Amount</th><th class="text-center">Actions</th></tr></thead>
                <tbody>
                @forelse($items['invoices'] as $row)
                    <tr>
                        <td>{{ !empty($row->transaction_date) ? date('Y-m-d', strtotime($row->transaction_date)) : '' }}</td>
                        <td>{{ $row->invoice_no ?: $row->ref_no }}</td>
                        <td>{{ ucwords(str_replace('_',' ', $row->payment_status ?: 'Due')) }}</td>
                        <td class="text-right">{{ number_format((float)$row->final_total, 2) }}</td>
                        <td class="text-center"><a class="dd-btn dd-btn-primary" target="_blank" href="{{ route('customers.portal.invoices.print', $row->id) }}">Print</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No invoices found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">Payment Receipts</h3></div>
        <div class="dd-card-body dd-table-wrap">
            <table class="dd-table">
                <thead><tr><th>Date</th><th>Reference No</th><th>Method</th><th class="text-right">Amount</th><th class="text-center">Actions</th></tr></thead>
                <tbody>
                @forelse($items['payments'] as $row)
                    <tr>
                        <td>{{ !empty($row->paid_on) ? date('Y-m-d', strtotime($row->paid_on)) : '' }}</td>
                        <td>{{ $row->payment_ref_no ?: 'PAY-'.$row->id }}</td>
                        <td>{{ ucwords(str_replace('_',' ', $row->method)) }}</td>
                        <td class="text-right">{{ number_format((float)$row->amount, 2) }}</td>
                        <td class="text-center"><a class="dd-btn dd-btn-primary" target="_blank" href="{{ route('customers.portal.payments.print', $row->id) }}">Print</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No payment receipts found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">Credit Notes</h3></div>
        <div class="dd-card-body dd-table-wrap">
            <table class="dd-table">
                <thead><tr><th>Date</th><th>Reference</th><th>Type</th><th class="text-right">Amount</th></tr></thead>
                <tbody>
                @forelse($items['credit_notes'] as $row)
                    <tr>
                        <td>{{ !empty($row->transaction_date) ? date('Y-m-d', strtotime($row->transaction_date)) : '' }}</td>
                        <td>{{ $row->invoice_no ?: $row->ref_no }}</td>
                        <td>{{ ucwords(str_replace('_',' ', $row->type)) }}</td>
                        <td class="text-right">{{ number_format((float)$row->final_total, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">No credit notes found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
