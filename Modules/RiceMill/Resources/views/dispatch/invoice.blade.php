@extends('RiceMill::layout')
@section('rcm-title','View Sales Invoice')
@section('rcm-actions')
    @if(!$isPreview)
        <a class="rcm-btn" href="{{ route('rice-mill.dispatch.index') }}"><i class="fa fa-list"></i> Sales / Dispatch</a>
    @endif
    <button type="button" class="rcm-btn" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
@endsection
@section('rcm-content')
<style>
@page { size: A4 landscape; margin: 10mm; }
@media print {
    html,body{background:#fff!important;width:100%!important;margin:0!important;padding:0!important}
    body *{visibility:hidden!important}
    .rcm-invoice-card,.rcm-invoice-card *{visibility:visible!important}
    .rcm-invoice-card{
        position:absolute!important;
        left:0!important;
        top:0!important;
        width:100%!important;
        max-width:none!important;
        margin:0!important;
        padding:0!important;
        box-shadow:none!important;
        border:0!important;
        background:#fff!important;
    }
    .rcm-invoice-actions,.rcm-preview-note{display:none!important}
    .rcm-invoice-title{margin-bottom:10px!important;padding-bottom:9px!important}
    .rcm-invoice-title h2{font-size:36pt!important}
    .rcm-invoice-meta{font-size:19pt!important}
    .rcm-invoice-party{grid-template-columns:repeat(5,1fr)!important;gap:6px!important;margin-bottom:10px!important}
    .rcm-invoice-party>div{padding:7px!important;border-radius:4px!important;background:#fff!important}
    .rcm-invoice-party span{font-size:16pt!important}
    .rcm-invoice-party strong{font-size:18pt!important}
    .rcm-table-wrap{overflow:visible!important;width:100%!important}
    .rcm-invoice-card table{width:100%!important;table-layout:fixed!important;border-collapse:collapse!important;font-size:16pt!important}
    .rcm-invoice-card th,.rcm-invoice-card td{padding:8px 5px!important;white-space:normal!important;word-break:break-word!important;line-height:1.25!important}
    .rcm-invoice-card thead{display:table-header-group!important}
    .rcm-invoice-card tr{page-break-inside:avoid!important}
    .rcm-invoice-total{width:48%!important;max-width:none!important;margin-top:10px!important}
    .rcm-invoice-total .rowx{padding:7px 0!important;font-size:18pt!important}
    .rcm-invoice-total .grand{font-size:22pt!important}
    .rcm-muted{font-size:17pt!important}
    a[href]:after{content:none!important}
}

.rcm-invoice-title{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;border-bottom:2px solid #e7ecef;padding-bottom:14px;margin-bottom:16px}
.rcm-invoice-title h2{margin:0;color:#243647;font-size:24px}
.rcm-invoice-meta{text-align:right;color:#5b6875;line-height:1.6}
.rcm-invoice-party{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:16px}
.rcm-invoice-party>div{border:1px solid #e3e9ee;border-radius:10px;padding:12px;background:#fbfcfd}
.rcm-invoice-party span{display:block;font-size:12px;color:#73808c;font-weight:700;margin-bottom:4px}
.rcm-invoice-party strong{color:#253849}
.rcm-invoice-total{max-width:520px;margin-left:auto;margin-top:16px}
.rcm-invoice-total .rowx{display:flex;justify-content:space-between;gap:20px;padding:7px 0;border-bottom:1px solid #edf1f4}
.rcm-invoice-total .taxable{font-weight:700;background:#fafbfd;padding-left:8px;padding-right:8px}
.rcm-invoice-total .grand{font-size:18px;font-weight:800;color:#243647;border-top:2px solid #cfd8df;border-bottom:none;margin-top:5px;padding-top:10px}
.rcm-invoice-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px;align-items:center}
.rcm-preview-note{border:1px solid #ead9aa;background:#fff9e8;color:#6b5722;border-radius:10px;padding:10px 12px;margin-bottom:14px}
</style>

<div class="rcm-card rcm-invoice-card">
    @if($isPreview)
        <div class="rcm-preview-note"><strong>Invoice Review:</strong> This Sales Invoice has not been saved yet. Review the Unit Discount, Invoice Discount, Taxable Amount and Tax below, then choose <strong>Save Draft</strong> or <strong>Approve Sale</strong>.</div>
    @endif

    <div class="rcm-invoice-title">
        <div>
            <h2>Sales Invoice</h2>
            <div class="rcm-muted">Rice Mill Sales / Dispatch</div>
        </div>
        <div class="rcm-invoice-meta">
            <div><strong>Invoice No:</strong> {{ $invoiceNo }}@if($isPreview) <span class="rcm-muted">(preview)</span>@endif</div>
            <div><strong>Date &amp; Time:</strong> {{ $invoiceDateTime->format('Y-m-d H:i:s') }}</div>
            <div><strong>Status:</strong> {{ ucfirst($status) }}</div>
        </div>
    </div>

    <div class="rcm-invoice-party">
        <div><span>Customer</span><strong>{{ $customerName }}</strong></div>
        <div><span>Location</span><strong>{{ $locationName ?: '-' }}</strong></div>
        <div><span>Store</span><strong>{{ $storeName ?: '-' }}</strong></div>
        <div><span>Vehicle No.</span><strong>{{ $vehicleNo ?: '-' }}</strong></div>
        <div><span>Driver</span><strong>{{ $driverName ?: '-' }}</strong></div>
    </div>

    <div class="rcm-table-wrap">
        <table class="rcm-table">
            <thead>
                <tr>
                    <th>Rice Product</th>
                    <th class="rcm-num">Quantity (kg)</th>
                    <th class="rcm-num">Unit Price</th>
                    <th>Per Unit Discount</th>
                    <th class="rcm-num">Discount / Unit</th>
                    <th class="rcm-num">Net Unit Price</th>
                    <th class="rcm-num">Gross</th>
                    <th class="rcm-num">Unit Discount Total</th>
                    <th class="rcm-num">Line Total</th>
                </tr>
            </thead>
            <tbody>
            @foreach($invoiceLines as $line)
                <tr>
                    <td>{{ $line['product_name'] }}</td>
                    <td class="rcm-num">{{ number_format($line['quantity'],$rcmQuantityPrecision) }}</td>
                    <td class="rcm-num">{{ number_format($line['unit_price'],$rcmCurrencyPrecision) }}</td>
                    <td>
                        @if(($line['unit_discount_type'] ?? 'fixed')==='percentage')
                            Percentage ({{ number_format($line['unit_discount_value'] ?? 0,4) }}%)
                        @else
                            Fixed
                        @endif
                    </td>
                    <td class="rcm-num">{{ number_format($line['unit_discount_per_unit'] ?? 0,$rcmCurrencyPrecision) }}</td>
                    <td class="rcm-num">{{ number_format($line['net_unit_price'] ?? $line['unit_price'],$rcmCurrencyPrecision) }}</td>
                    <td class="rcm-num">{{ number_format($line['gross_line_total'] ?? ($line['quantity']*$line['unit_price']),$rcmCurrencyPrecision) }}</td>
                    <td class="rcm-num">{{ number_format($line['unit_discount_amount'] ?? 0,$rcmCurrencyPrecision) }}</td>
                    <td class="rcm-num">{{ number_format($line['line_total'],$rcmCurrencyPrecision) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="rcm-invoice-total">
        <div class="rowx"><span>Gross Subtotal</span><strong>{{ number_format($subtotal,$rcmCurrencyPrecision) }}</strong></div>
        <div class="rowx"><span>Less: Total Per Unit Discount</span><strong>{{ number_format($unitDiscountAmount ?? 0,$rcmCurrencyPrecision) }}</strong></div>
        <div class="rowx"><span>Subtotal After Unit Discount</span><strong>{{ number_format($subtotalAfterUnitDiscount ?? $subtotal,$rcmCurrencyPrecision) }}</strong></div>
        <div class="rowx">
            <span>Invoice Discount Type</span>
            <strong>
                @if($discountType==='percentage')
                    Percentage ({{ number_format($discountValue,4) }}%)
                @else
                    Fixed
                @endif
            </strong>
        </div>
        <div class="rowx"><span>Less: Invoice Discount Amount</span><strong>{{ number_format($discountAmount,$rcmCurrencyPrecision) }}</strong></div>
        <div class="rowx taxable"><span>Taxable Amount</span><strong>{{ number_format($taxableAmount,$rcmCurrencyPrecision) }}</strong></div>
        <div class="rowx"><span>Tax ({{ number_format($taxPercent,4) }}%)</span><strong>{{ number_format($taxAmount,$rcmCurrencyPrecision) }}</strong></div>
        <div class="rowx grand"><span>Net Total</span><span>{{ number_format($netTotal,$rcmCurrencyPrecision) }}</span></div>
    </div>

    <div class="rcm-muted" style="margin-top:10px;text-align:right">
        Tax is calculated only as: Taxable Amount × Tax %.
    </div>

    @if($note)
        <div style="margin-top:16px"><strong>Note:</strong><div class="rcm-muted">{{ $note }}</div></div>
    @endif

    <div class="rcm-invoice-actions">
        @if($isPreview)
            <button type="button" class="rcm-btn" onclick="history.back()"><i class="fa fa-arrow-left"></i> Back to Edit</button>
            <form method="post" action="{{ route('rice-mill.dispatch.preview.save',$previewToken) }}">@csrf<button class="rcm-btn"><i class="fa fa-save"></i> Save Draft</button></form>
            @if($canApproveDispatch)
                <form method="post" action="{{ route('rice-mill.dispatch.preview.approve',$previewToken) }}">@csrf<button class="rcm-btn"><i class="fa fa-check"></i> Approve Sale</button></form>
            @else
                <span class="rcm-muted">Approval permission is required to approve this sale.</span>
            @endif
        @elseif($dispatch && $dispatch->status==='draft')
            @if($canApproveDispatch)
                <form method="post" action="{{ route('rice-mill.dispatch.approve',$dispatch->id) }}">@csrf<button class="rcm-btn"><i class="fa fa-check"></i> Approve Sale</button></form>
            @else
                <span class="rcm-muted">Approval permission is required to approve this sale.</span>
            @endif
        @endif
    </div>
</div>
@endsection
