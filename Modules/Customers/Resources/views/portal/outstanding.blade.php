@extends('customers::portal.layout')
@section('title', 'Outstanding Invoices')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    @include('customers::portal.partials_summary', ['customer' => $customer, 'summary' => $summary])

    <div class="dd-card">
        <div class="dd-card-header clearfix">
            <h3 class="dd-card-title pull-left">Outstanding Invoices</h3>
            <div class="pull-right dd-no-print">
                <button type="button" onclick="window.print()" class="dd-btn dd-btn-default">Print</button>
            </div>
        </div>
        <div class="dd-card-body">
            <div class="dd-table-wrap">
                <table class="dd-table">
                    <thead>
                        <tr>
                            <th>Invoice No</th>
                            <th>Date</th>
                            <th>Due Date</th>
                            <th class="text-center">Days Outstanding</th>
                            <th class="text-center">Ageing</th>
                            <th class="text-right">Invoice Amount</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalBalance = 0; @endphp
                        @forelse($rows as $row)
                            @php $totalBalance += (float)($row->balance ?? 0); @endphp
                            <tr>
                                <td>{{ $row->invoice_no ?: ($row->ref_no ?: 'INV-' . $row->id) }}</td>
                                <td>{{ !empty($row->transaction_date) ? date('Y-m-d', strtotime($row->transaction_date)) : '' }}</td>
                                <td>{{ !empty($row->transaction_date) ? date('Y-m-d', strtotime($row->transaction_date)) : '' }}</td>
                                <td class="text-center">{{ (int)($row->days_outstanding ?? 0) }}</td>
                                <td class="text-center"><span class="dd-aging {{ $row->aging_class ?? 'dd-aging-good' }}">{{ $row->aging_bucket ?? '0-30 Days' }}</span></td>
                                <td class="text-right">{{ number_format((float)($row->final_total ?? 0), 2) }}</td>
                                <td class="text-right">{{ number_format((float)($row->paid_amount ?? 0), 2) }}</td>
                                <td class="text-right"><strong>{{ number_format((float)($row->balance ?? 0), 2) }}</strong></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No outstanding invoices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="7" class="text-right">Total Outstanding</th>
                            <th class="text-right">{{ number_format($totalBalance, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
