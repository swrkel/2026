@extends('layouts.app')
@section('title', 'Repair Invoices')

@section('content')
<div class="container">
    <h3>Repair Job Invoices</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Invoice No</th>
                <th>Job Sheet No</th>
                <th>Customer</th>
                <th>Location</th>
                <th>Total</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        @foreach($invoices as $invoice)
            <tr>
                <td>{{ $invoice->invoice_no }}</td>
                <td>{{ $invoice->job_sheet_no }}</td>
                <td>{{ $invoice->customer_name }}</td>
                <td>{{ $invoice->location_name }}</td>
                <td class="text-end">{{ number_format($invoice->final_total, 2) }}</td>
                <td>{{ \Carbon\Carbon::parse($invoice->transaction_date)->format('Y-m-d') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{ $invoices->links() }}
</div>
@endsection
