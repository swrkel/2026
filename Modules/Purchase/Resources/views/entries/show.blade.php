@extends('layouts.app')
@section('title', 'View Purchase Entry')

@section('content')
<style>{!! file_get_contents(module_path('Purchase', 'Resources/assets/css/purchase-entry.css')) !!}</style>
<style>{!! file_get_contents(module_path('Purchase', 'Resources/assets/css/purchase-workspace.css')) !!}</style>

@php
    $access = app(\Modules\Purchase\Utils\PurchaseAccessUtil::class);
    $money = static fn ($value) => ($currency_symbol ? $currency_symbol.' ' : '').number_format((float)$value, $currency_precision);
    $qty = static fn ($value) => number_format((float)$value, $quantity_precision);
    $dateTime = static function ($value) {
        if (empty($value)) return '-';
        try { return \Carbon\Carbon::parse($value)->format('d/m/Y H:i'); } catch (\Throwable $e) { return (string)$value; }
    };
    $dateOnly = static function ($value) {
        if (empty($value)) return '-';
        try { return \Carbon\Carbon::parse($value)->format('d/m/Y'); } catch (\Throwable $e) { return (string)$value; }
    };
@endphp

<section class="content purchase-workspace purchase-entry-view-page">
    <div class="purchase-workspace-header">
        <div>
            <h1><i class="fa fa-eye"></i> View Purchase Entry</h1>
            <div class="purchase-breadcrumb">Purchase (New) / List Purchase Entries / View</div>
        </div>
        <div class="purchase-workspace-actions">
            <a href="{{ route('purchase.entries.index') }}" class="btn btn-default"><i class="fa fa-list"></i> List Purchase Entries</a>
            @if($access->canEdit())
                <a href="{{ route('purchase.entries.edit', $purchase->id) }}" class="btn btn-warning"><i class="fa fa-edit"></i> Edit</a>
            @endif
            @if($access->canPrint())
                <a href="{{ route('purchase.entries.print', $purchase->id) }}" target="_blank" rel="noopener" class="btn btn-primary"><i class="fa fa-print"></i> Print</a>
            @endif
        </div>
    </div>

    <div class="purchase-view-status-row">
        <span class="status-label status-{{ strtolower((string)($purchase->status ?? '')) }}">{{ ucfirst((string)($purchase->status ?: 'Unknown')) }}</span>
        <span class="status-label status-{{ strtolower((string)($purchase->payment_status ?? 'due')) }}">Payment: {{ ucfirst((string)($purchase->payment_status ?: 'Due')) }}</span>
    </div>

    <div class="purchase-grid purchase-grid-2 purchase-view-grid">
        <div class="purchase-card">
            <div class="purchase-card-title"><span><i class="fa fa-file-text-o"></i> Purchase Details</span></div>
            <div class="purchase-card-body purchase-view-details">
                <div><span>Purchase No.</span><strong>{{ $purchase->invoice_no ?: ('PUR-'.$purchase->id) }}</strong></div>
                <div><span>Supplier Reference</span><strong>{{ $purchase->ref_no ?: '-' }}</strong></div>
                <div><span>Purchase Order No.</span><strong>{{ $purchase->order_no ?: '-' }}</strong></div>
                <div><span>Received Date & Time</span><strong>{{ $dateTime($purchase->transaction_date ?? null) }}</strong></div>
                <div><span>Invoice Date</span><strong>{{ $dateOnly($purchase->invoice_date ?? null) }}</strong></div>
                <div><span>Business Location</span><strong>{{ $purchase->location_name ?: '-' }}</strong></div>
                <div><span>Store</span><strong>{{ $purchase->store_name ?: '-' }}</strong></div>
                <div><span>Pay Term</span><strong>{{ $purchase->pay_term_number !== null ? $purchase->pay_term_number.' '.ucfirst((string)($purchase->pay_term_type ?: 'days')) : '-' }}</strong></div>
                <div><span>Exchange Rate</span><strong>{{ number_format((float)($purchase->exchange_rate ?: 1), 6) }}</strong></div>
                <div><span>VAT Purchase</span><strong>{{ !empty($purchase->is_vat) ? 'Yes' : 'No' }}</strong></div>
                @if(!empty($purchase->document))
                    <div class="purchase-view-full"><span>Document</span><strong><a href="{{ asset('uploads/documents/'.basename($purchase->document)) }}" target="_blank" rel="noopener"><i class="fa fa-paperclip"></i> Open document</a></strong></div>
                @endif
            </div>
        </div>

        <div class="purchase-card">
            <div class="purchase-card-title"><span><i class="fa fa-truck"></i> Supplier Details</span></div>
            <div class="purchase-card-body purchase-view-details">
                <div class="purchase-view-full"><span>Supplier</span><strong>{{ $purchase->supplier_name ?: '-' }}</strong></div>
                <div><span>Supplier Code</span><strong>{{ $purchase->supplier_code ?: '-' }}</strong></div>
                <div><span>Mobile</span><strong>{{ $purchase->supplier_mobile ?: '-' }}</strong></div>
                <div><span>Email</span><strong>{{ $purchase->supplier_email ?: '-' }}</strong></div>
                <div><span>Tax Number</span><strong>{{ $purchase->supplier_tax_number ?: '-' }}</strong></div>
                <div class="purchase-view-full"><span>Shipping Details</span><strong>{{ $purchase->shipping_details ?: '-' }}</strong></div>
                <div class="purchase-view-full"><span>Additional Notes</span><strong class="purchase-view-notes">{{ $purchase->additional_notes ?: '-' }}</strong></div>
            </div>
        </div>
    </div>

    <div class="purchase-card">
        <div class="purchase-card-title">
            <span><i class="fa fa-cubes"></i> Purchased Products</span>
            <span>{{ number_format($lines->count()) }} line(s)</span>
        </div>
        <div class="purchase-card-body">
            <div class="purchase-table-scroll">
                <table class="table table-bordered purchase-view-lines-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product / Variation</th>
                            <th>SKU</th>
                            <th>Qty</th>
                            <th>Free Qty</th>
                            <th>Unit</th>
                            <th>Unit Cost</th>
                            <th>Discount %</th>
                            <th>Tax</th>
                            <th>Cost Inc. Tax</th>
                            <th>Line Total</th>
                            <th>Lot / Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lines as $index => $line)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td><strong>{{ $line->product_name ?: 'Product #'.$line->product_id }}</strong>@if(!empty($line->variation_name))<small>{{ $line->variation_name }}</small>@endif</td>
                                <td>{{ $line->sku ?: '-' }}</td>
                                <td class="amount">{{ $qty($line->display_quantity) }}</td>
                                <td class="amount">{{ $qty($line->display_bonus_quantity) }}</td>
                                <td>{{ $line->display_unit }}</td>
                                <td class="amount">{{ $money((float)($line->pp_without_discount ?? 0) * max(0.000001, (float)($line->sub_unit_multiplier ?: 1))) }}</td>
                                <td class="amount">{{ number_format((float)($line->discount_percent ?? 0), 2) }}</td>
                                <td>{{ $line->tax_name ? $line->tax_name.' ('.number_format((float)$line->tax_rate, 2).'%)' : '-' }}</td>
                                <td class="amount">{{ $money((float)($line->purchase_price_inc_tax ?? 0) * max(0.000001, (float)($line->sub_unit_multiplier ?: 1))) }}</td>
                                <td class="amount"><strong>{{ $money($line->line_total) }}</strong></td>
                                <td>
                                    @if(!empty($line->lot_number))<span>Lot: {{ $line->lot_number }}</span>@endif
                                    @if(!empty($line->mfg_date))<span>Mfg: {{ $dateOnly($line->mfg_date) }}</span>@endif
                                    @if(!empty($line->exp_date))<span>Exp: {{ $dateOnly($line->exp_date) }}</span>@endif
                                    @if(empty($line->lot_number) && empty($line->mfg_date) && empty($line->exp_date))-@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="purchase-empty-state">No product lines are available for this purchase.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="purchase-grid purchase-grid-2 purchase-view-grid">
        <div class="purchase-card">
            <div class="purchase-card-title"><span><i class="fa fa-credit-card"></i> Payments</span></div>
            <div class="purchase-card-body">
                <div class="purchase-table-scroll">
                    <table class="table table-bordered purchase-view-payments-table">
                        <thead><tr><th>Paid On</th><th>Method</th><th>Account</th><th>Reference</th><th>Amount</th></tr></thead>
                        <tbody>
                            @forelse($payments as $payment)
                                <tr>
                                    <td>{{ $dateTime($payment->paid_on ?? null) }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', (string)($payment->method ?? ''))) }}</td>
                                    <td>{{ $payment->account_name ?: '-' }}</td>
                                    <td>{{ $payment->payment_ref_no ?? $payment->reference_no ?? $payment->transaction_no ?? '-' }}</td>
                                    <td class="amount">{{ $money($payment->amount ?? 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="purchase-empty-state">No actual payment has been recorded. The purchase remains due.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="purchase-card purchase-view-total-card">
            <div class="purchase-card-title"><span><i class="fa fa-calculator"></i> Purchase Summary</span></div>
            <div class="purchase-card-body">
                <div class="purchase-summary-row"><span>Subtotal before tax</span><strong>{{ $money($purchase->total_before_tax ?? 0) }}</strong></div>
                <div class="purchase-summary-row"><span>Product tax</span><strong>{{ $money($line_tax_total) }}</strong></div>
                <div class="purchase-summary-row"><span>Purchase discount</span><strong>- {{ $money($purchase->discount_amount ?? 0) }}</strong></div>
                <div class="purchase-summary-row"><span>Additional tax</span><strong>{{ $money($purchase->tax_amount ?? 0) }}</strong></div>
                <div class="purchase-summary-row"><span>Shipping</span><strong>{{ $money($purchase->shipping_charges ?? 0) }}</strong></div>
                <div class="purchase-summary-row"><span>Price adjustment</span><strong>{{ $money($purchase->price_adjustment ?? 0) }}</strong></div>
                <div class="purchase-summary-row purchase-grand-total"><span>Purchase Total</span><strong>{{ $money($purchase->final_total ?? 0) }}</strong></div>
                <div class="purchase-summary-row"><span>Paid</span><strong>{{ $money($paid_total) }}</strong></div>
                <div class="purchase-summary-row"><span>Due</span><strong>{{ $money($due_total) }}</strong></div>
            </div>
        </div>
    </div>
</section>
@endsection
