@extends('suppliers::layouts.app')
@section('title', __('suppliers::lang.purchase_history'))
@section('suppliers_content')
@php
    $supplierCurrencySymbol = (string) (session('currency.symbol') ?: session('business.currency_symbol') ?: '');
    $supplierCurrencyPrecision = max(0, min(6, (int) (session('business.currency_precision') ?? 2)));
@endphp
<section class="content-header supplier-purchase-history-heading">
    <h1>
        <span>@lang('suppliers::lang.purchase_history')</span>
        <span class="supplier-tab-supplier-name">{{ $supplier->name }} @if($supplier->contact_id) ({{ $supplier->contact_id }}) @endif</span>
    </h1>
</section>
<section class="content main-content-inner">
    <div class="box box-primary">
        <div class="box-body">
            @include('suppliers::partials.tabs', ['supplier' => $supplier, 'active' => 'purchase_history'])

            <form method="GET" action="{{ route('suppliers.purchase_history.index', $supplier->id) }}" class="supplier-history-toolbar">
                <div class="supplier-history-search">
                    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search purchase invoice, reference or status">
                </div>
                <div class="supplier-history-length">
                    <select name="per_page" class="form-control" onchange="this.form.submit()">
                        @foreach([10, 25, 50, 100, 250] as $size)
                            <option value="{{ $size }}" {{ (int)($filters['per_page'] ?? 25) === $size ? 'selected' : '' }}>{{ $size }} records</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Search</button>
            </form>

            <div class="table-responsive supplier-full-table-shell supplier-purchase-history-table-shell">
                <table class="table table-bordered table-striped supplier-purchase-history-table supplier-full-width-table supplier-history-layout-v2" style="width:100%">
                    <colgroup>
                        <col class="supplier-history-col-system-date">
                        <col class="supplier-history-col-invoice-date">
                        <col class="supplier-history-col-invoice-view">
                        <col class="supplier-history-col-reference">
                        <col class="supplier-history-col-location">
                        <col class="supplier-history-col-status">
                        <col class="supplier-history-col-payment-status">
                        <col class="supplier-history-col-total">
                        <col class="supplier-history-col-paid">
                        <col class="supplier-history-col-due">
                    </colgroup>
                    <thead>
                        <tr>
                            <th><span class="supplier-history-th-label">System Entered<br>Date</span></th>
                            <th><span class="supplier-history-th-label">Invoice<br>Date</span></th>
                            <th><span class="supplier-history-th-label">Purchase Invoice<br>No. / View</span></th>
                            <th><span class="supplier-history-th-label">Supplier Invoice /<br>Reference No.</span></th>
                            <th><span class="supplier-history-th-label">Location</span></th>
                            <th><span class="supplier-history-th-label">Status</span></th>
                            <th><span class="supplier-history-th-label">Payment<br>Status</span></th>
                            <th class="text-right"><span class="supplier-history-th-label">Purchase<br>Total</span></th>
                            <th class="text-right"><span class="supplier-history-th-label">Paid</span></th>
                            <th class="text-right"><span class="supplier-history-th-label">Due</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                            @php
                                $purchaseTotal = (float) ($purchase->final_total ?? 0);
                                $paidTotal = (float) ($purchase->paid_total ?? 0);
                                $dueTotal = max(0, $purchaseTotal - $paidTotal);
                                $viewUrl = \Illuminate\Support\Facades\Route::has('purchase.entries.show')
                                    ? route('purchase.entries.show', $purchase->id)
                                    : url('/purchases/' . $purchase->id);
                            @endphp
                            <tr>
                                <td>
                                    @if($purchase->created_at)
                                        <span class="supplier-history-date-part">{{ date('Y-m-d', strtotime($purchase->created_at)) }}</span>
                                        <span class="supplier-history-time-part">{{ date('H:i', strtotime($purchase->created_at)) }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($purchase->transaction_date)
                                        <span class="supplier-history-date-part">{{ date('Y-m-d', strtotime($purchase->transaction_date)) }}</span>
                                        <span class="supplier-history-time-part">{{ date('H:i', strtotime($purchase->transaction_date)) }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <div class="supplier-history-invoice-cell">
                                        <span class="supplier-history-invoice-number">{{ $purchase->invoice_no ?: '-' }}</span>
                                        <a href="{{ $viewUrl }}" class="btn btn-xs btn-info supplier-history-view-btn">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                    </div>
                                </td>
                                <td>{{ $purchase->ref_no ?: '-' }}</td>
                                <td>{{ $purchase->location_name ?: '-' }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', (string) $purchase->status)) }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', (string) $purchase->payment_status)) }}</td>
                                <td class="text-right supplier-history-amount">{{ $supplierCurrencySymbol }}{{ $supplierCurrencySymbol !== '' ? ' ' : '' }}{{ number_format($purchaseTotal, $supplierCurrencyPrecision) }}</td>
                                <td class="text-right supplier-history-amount">{{ $supplierCurrencySymbol }}{{ $supplierCurrencySymbol !== '' ? ' ' : '' }}{{ number_format($paidTotal, $supplierCurrencyPrecision) }}</td>
                                <td class="text-right supplier-history-amount">{{ $supplierCurrencySymbol }}{{ $supplierCurrencySymbol !== '' ? ' ' : '' }}{{ number_format($dueTotal, $supplierCurrencyPrecision) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted">No purchase invoices found for this supplier.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="supplier-pagination-wrap">{{ $purchases->links() }}</div>
        </div>
    </div>
</section>
@endsection
