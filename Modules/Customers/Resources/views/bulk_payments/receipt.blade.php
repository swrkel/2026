@extends('layouts.app')

@section('title', 'Bulk Payment Receipt ' . $reference)

@section('content')
<section class="content bulk-payment-receipt-page">
    <style>
        .bulk-payment-receipt-page .receipt-sheet{max-width:900px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 14px 36px rgba(15,23,42,.08);overflow:hidden}
        .bulk-payment-receipt-page .receipt-head{padding:25px 28px;color:#fff;background:linear-gradient(135deg,#2367f2,#174fc4);display:flex;justify-content:space-between;gap:20px;align-items:flex-start}
        .bulk-payment-receipt-page .receipt-head h1{margin:0 0 6px;font-size:26px;font-weight:800}.bulk-payment-receipt-page .receipt-head p{margin:0;color:rgba(255,255,255,.82)}
        .bulk-payment-receipt-page .receipt-ref{font-size:20px;font-weight:800;background:rgba(255,255,255,.15);padding:10px 14px;border-radius:10px;white-space:nowrap}
        .bulk-payment-receipt-page .receipt-body{padding:26px 28px}.bulk-payment-receipt-page .receipt-meta{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:22px}
        .bulk-payment-receipt-page .meta-card{padding:14px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0}.bulk-payment-receipt-page .meta-card span{display:block;color:#64748b;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px}.bulk-payment-receipt-page .meta-card strong{color:#0f172a;font-size:15px}
        .bulk-payment-receipt-page table{width:100%;border-collapse:collapse}.bulk-payment-receipt-page th{padding:11px;color:#fff;background:#3159b8;text-align:left;font-size:12px}.bulk-payment-receipt-page td{padding:11px;border-bottom:1px solid #e2e8f0;color:#334155}.bulk-payment-receipt-page .text-right{text-align:right}.bulk-payment-receipt-page .receipt-total{display:flex;justify-content:flex-end;padding-top:18px}.bulk-payment-receipt-page .receipt-total div{min-width:280px;padding:15px 18px;border-radius:12px;background:#ecfdf3;border:1px solid #bbf7d0;display:flex;justify-content:space-between;color:#166534;font-size:18px;font-weight:800}
        .bulk-payment-receipt-page .receipt-actions{max-width:900px;margin:16px auto 0;display:flex;justify-content:flex-end;gap:10px}.bulk-payment-receipt-page .receipt-btn{padding:10px 16px;border-radius:10px;border:1px solid #d8e1ee;background:#fff;color:#334155;font-weight:750;text-decoration:none}.bulk-payment-receipt-page .receipt-btn-primary{border-color:#2367f2;background:#2367f2;color:#fff}
        @media(max-width:700px){.bulk-payment-receipt-page .receipt-meta{grid-template-columns:1fr}.bulk-payment-receipt-page .receipt-head{flex-direction:column}.bulk-payment-receipt-page .receipt-body{padding:18px}.bulk-payment-receipt-page .receipt-table-wrap{overflow-x:auto}.bulk-payment-receipt-page table{min-width:650px}}
        @media print{.main-header,.main-sidebar,.content-header,.receipt-actions,.control-sidebar{display:none!important}.content-wrapper{margin-left:0!important}.bulk-payment-receipt-page{padding:0!important}.bulk-payment-receipt-page .receipt-sheet{max-width:none;border:0;box-shadow:none;border-radius:0}}
    </style>

    <div class="receipt-sheet">
        <div class="receipt-head">
            <div>
                <h1>Customer Bulk Payment Receipt</h1>
                <p>Customers Module</p>
            </div>
            <div class="receipt-ref">{{ $reference }}</div>
        </div>

        <div class="receipt-body">
            <div class="receipt-meta">
                <div class="meta-card">
                    <span>Customer</span>
                    <strong>{{ $customer->name ?? ('Customer #' . $customer->id) }}</strong>
                </div>
                <div class="meta-card">
                    <span>Customer Code</span>
                    <strong>{{ $customer->contact_id ?? '-' }}</strong>
                </div>
                <div class="meta-card">
                    <span>Payment Date</span>
                    <strong>{{ !empty($payment->paid_on) ? \Carbon\Carbon::parse($payment->paid_on)->format('d/m/Y') : '-' }}</strong>
                </div>
                <div class="meta-card">
                    <span>Payment Method</span>
                    <strong>{{ ucwords(str_replace('_', ' ', (string) ($payment->method ?? ''))) }}</strong>
                </div>
                <div class="meta-card">
                    <span>Cheque / Card No</span>
                    <strong>{{ $payment->cheque_number ?? ($payment->card_number ?? '-') }}</strong>
                </div>
                <div class="meta-card">
                    <span>Bank</span>
                    <strong>{{ $payment->bank_name ?? '-' }}</strong>
                </div>
            </div>

            <div class="receipt-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Invoice / Allocation</th>
                            <th class="text-right">Interest</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allocations as $allocation)
                            <tr>
                                <td>{{ !empty($allocation->transaction_date) ? \Carbon\Carbon::parse($allocation->transaction_date)->format('d/m/Y') : '-' }}</td>
                                <td>{{ $allocation->is_advance ? 'Customer Advance' : $allocation->invoice_no }}</td>
                                <td class="text-right">{{ number_format((float) $allocation->interest, 2) }}</td>
                                <td class="text-right">{{ number_format((float) $allocation->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="receipt-total">
                <div><span>Total Payment</span><span>{{ number_format((float) $total, 2) }}</span></div>
            </div>
        </div>
    </div>

    <div class="receipt-actions">
        <a href="{{ route('customers.bulk_payment.index') }}" class="receipt-btn"><i class="fa fa-arrow-left"></i> Back</a>
        <button type="button" class="receipt-btn receipt-btn-primary" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
    </div>
</section>
@endsection
