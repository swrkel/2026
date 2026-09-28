@extends('customers::portal.layout')
@section('title', 'Print Statement')
@section('body')
<div class="dd-wrap">
    <div class="text-center" style="margin-bottom:18px;">
        <h2 style="margin:0;font-weight:900;">Customer Statement</h2>
        <p>{{ $customer->name }} | {{ $customer->contact_id }}</p>
    </div>
    @include('customers::portal.partials_summary')
    <div class="dd-card">
        <div class="dd-card-body">
            <div class="dd-table-wrap">
                <table class="dd-table">
                    <thead><tr><th>Transaction Date</th><th>System Entered Date & Time</th><th>Reference</th><th>Description</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th class="text-right">Balance</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->transaction_date }}</td><td>{{ $row->system_datetime }}</td><td>{{ $row->reference }}</td><td>{{ $row->description }}</td>
                            <td class="text-right">{{ number_format((float)$row->debit, 2) }}</td><td class="text-right">{{ number_format((float)$row->credit, 2) }}</td><td class="text-right">{{ number_format((float)$row->balance, 2) }}</td><td>{{ $row->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">No statement entries found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>window.print();</script>
@endsection
