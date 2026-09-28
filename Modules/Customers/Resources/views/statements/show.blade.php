@extends('customers::layouts.action', ['title' => 'Customer Statement'])

@section('customer_action_body')
<div class="customers-statement-action-wrapper">
    <style>
        .customers-statement-action-wrapper .statement-header-card{
            border:1px solid #e5e7eb;
            border-radius:14px;
            padding:14px 16px;
            background:#f8fafc;
            margin-bottom:14px;
        }
        .customers-statement-action-wrapper .statement-title{
            font-size:17px;
            font-weight:700;
            color:#0f172a;
            margin:0;
        }
        .customers-statement-action-wrapper .statement-subtitle{
            color:#64748b;
            font-size:13px;
            margin-top:4px;
        }
        .customers-statement-action-wrapper .statement-table th{
            background:#f8fafc;
            color:#334155;
            font-weight:700;
            white-space:nowrap;
            vertical-align:middle !important;
        }
        .customers-statement-action-wrapper .statement-table td{
            vertical-align:middle !important;
        }
        .customers-statement-action-wrapper .amount-cell{
            text-align:right;
            white-space:nowrap;
            font-weight:600;
        }
        .customers-statement-action-wrapper .statement-scroll{
            overflow-x:auto;
            -webkit-overflow-scrolling:touch;
        }
        .customers-statement-action-wrapper .statement-table{
            min-width:900px;
        }
    </style>

    <div class="statement-header-card">
        <h4 class="statement-title">{{ $customer->name }}</h4>
        <div class="statement-subtitle">
            Customer Code: {{ $customer->contact_id ?? '-' }}
            @if(!empty($customer->mobile)) | Mobile: {{ $customer->mobile }} @endif
        </div>
    </div>

    @php $runningBalance = 0; @endphp
    <div class="statement-scroll">
        <table class="table table-bordered table-striped statement-table">
            <thead>
                <tr>
                    <th>Transaction Date</th>
                    <th>System Entered Date & Time</th>
                    <th>Reference</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Credit</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $amount = (float) ($row->final_total ?? 0);
                        $type = strtolower((string) ($row->type ?? ''));
                        $isCredit = in_array($type, ['payment', 'sell_return', 'purchase_return', 'refund', 'credit']);
                        $debit = $isCredit ? 0 : $amount;
                        $credit = $isCredit ? $amount : 0;
                        $runningBalance += ($debit - $credit);
                    @endphp
                    <tr>
                        <td>{{ !empty($row->transaction_date) ? date('Y-m-d', strtotime($row->transaction_date)) : '-' }}</td>
                        <td>{{ !empty($row->created_at) ? date('Y-m-d H:i', strtotime($row->created_at)) : '-' }}</td>
                        <td>{{ $row->invoice_no ?: ($row->ref_no ?: '-') }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $row->type ?? '-')) }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $row->payment_status ?? '-')) }}</td>
                        <td class="amount-cell">{{ $debit != 0 ? number_format($debit, 2) : '-' }}</td>
                        <td class="amount-cell">{{ $credit != 0 ? number_format($credit, 2) : '-' }}</td>
                        <td class="amount-cell">{{ number_format($runningBalance, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center">No statement entries found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
