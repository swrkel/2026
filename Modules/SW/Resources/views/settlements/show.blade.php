@extends('sw::layouts.app', [
    'title' => 'SW Settlement ' . $settlement->settlement_no,
    'heading' => 'SW Settlement ' . $settlement->settlement_no,
    'subheading' => 'Settlement payment details and linked SW Shift operators',
])

@section('sw_content')
<div class="sw-settlement-detail-print">
    <div class="sw-card sw-settlement-summary-card">
        <div class="sw-detail-head">
            <div>
                <span>Settlement No</span>
                <strong>{{ $settlement->settlement_no ?: '—' }}</strong>
            </div>
            <div>
                <span>Date</span>
                <strong>{{ $settlement->transaction_date?->format('d/m/Y') ?: '—' }}</strong>
            </div>
            <div>
                <span>Business Location</span>
                <strong>{{ $location_name ?: '—' }}</strong>
            </div>
            <div>
                <span>SW Shift No</span>
                <strong>{{ $settlement->shifts->pluck('sw_shift_no')->filter()->implode(', ') ?: '—' }}</strong>
            </div>
            <div class="sw-detail-operators">
                <span>Operators linked to SW Shift</span>
                <strong>{{ $operators->implode(', ') ?: '—' }}</strong>
            </div>
            <div>
                <span>Status</span>
                <strong>{{ $settlement->statusLabel() }}</strong>
            </div>
        </div>
    </div>

    <div class="row sw-settlement-stats">
        <div class="col-md-4 col-sm-4">
            <div class="sw-stat">
                <div class="k">Total Sales</div>
                <div class="v">{{ number_format((float) $settlement->total_sales, 2) }}</div>
            </div>
        </div>
        <div class="col-md-4 col-sm-4">
            <div class="sw-stat total">
                <div class="k">Total Collected</div>
                <div class="v">{{ number_format((float) $settlement->total_collected, 2) }}</div>
            </div>
        </div>
        <div class="col-md-4 col-sm-4">
            <div class="sw-stat {{ abs((float)$settlement->variance) < 0.005 ? 'total' : '' }}">
                <div class="k">Variance</div>
                <div class="v">{{ number_format((float) $settlement->variance, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="sw-card">
        <h3>Meter Sales</h3>
        <div class="table-responsive">
            <table class="sw-table sw-snapshot-table">
                <thead><tr>
                    <th>Pump No</th><th>Product</th><th class="num">Starting Meter</th><th class="num">Closing Meter</th>
                    <th class="num">Sold Qty</th><th class="num">Testing Qty</th><th class="num">Net Qty</th>
                    <th class="num">Unit Price</th><th class="num">Amount</th>
                </tr></thead>
                <tbody>
                @forelse($settlement->lines as $line)
                    <tr>
                        <td>{{ $pumpMap[(int)$line->pump_id] ?? ('Pump #' . $line->pump_id) }}</td>
                        <td>{{ $productMap[(int)$line->product_id] ?? ($line->product_id ? ('Product #' . $line->product_id) : '—') }}</td>
                        <td class="num">{{ number_format((float)$line->opening_meter, 3) }}</td>
                        <td class="num">{{ number_format((float)$line->closing_meter, 3) }}</td>
                        <td class="num">{{ number_format((float)$line->computeSoldQuantity(), 3) }}</td>
                        <td class="num">{{ number_format((float)$line->testing_qty, 3) }}</td>
                        <td class="num">{{ number_format((float)$line->quantity, 3) }}</td>
                        <td class="num">{{ number_format((float)$line->rate, 2) }}</td>
                        <td class="num">{{ number_format((float)$line->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="sw-empty">No Meter Sales are recorded for this settlement.</td></tr>
                @endforelse
                </tbody>
                <tfoot><tr><th colspan="8" class="text-right">Meter Sales Total</th><th class="num">{{ number_format((float)$settlement->total_meter_sales, 2) }}</th></tr></tfoot>
            </table>
        </div>
    </div>

    <div class="sw-card">
        <h3>Other Sales</h3>
        <div class="table-responsive">
            <table class="sw-table sw-snapshot-table">
                <thead><tr><th>Product</th><th>Store</th><th class="num">Qty</th><th class="num">Unit Price</th><th class="num">Before Discount</th><th class="num">Amount</th></tr></thead>
                <tbody>
                @forelse($settlement->otherSales as $row)
                    <tr>
                        <td>{{ $productMap[(int)$row->product_id] ?? ('Product #' . $row->product_id) }}</td>
                        <td>{{ $storeMap[(int)$row->store_id] ?? ($row->store_id ? ('Store #' . $row->store_id) : '—') }}</td>
                        <td class="num">{{ number_format((float)$row->quantity, 3) }}</td>
                        <td class="num">{{ number_format((float)$row->rate, 2) }}</td>
                        <td class="num">{{ number_format((float)$row->amount_before_discount, 2) }}</td>
                        <td class="num">{{ number_format((float)$row->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="sw-empty">No Other Sales are recorded for this settlement.</td></tr>
                @endforelse
                </tbody>
                <tfoot><tr><th colspan="5" class="text-right">Other Sales Total</th><th class="num">{{ number_format((float)$settlement->total_other_sales, 2) }}</th></tr></tfoot>
            </table>
        </div>
    </div>

    <div class="sw-card">
        <h3>Other Income</h3>
        <div class="table-responsive">
            <table class="sw-table sw-snapshot-table">
                <thead><tr><th>Service</th><th>Details</th><th class="num">Qty</th><th class="num">Rate</th><th class="num">Amount</th></tr></thead>
                <tbody>
                @forelse($settlement->otherIncome as $row)
                    <tr>
                        <td>{{ $productMap[(int)$row->product_id] ?? ('Service #' . $row->product_id) }}</td>
                        <td>{{ $row->details ?: '—' }}</td>
                        <td class="num">{{ number_format((float)$row->quantity, 3) }}</td>
                        <td class="num">{{ number_format((float)$row->rate, 2) }}</td>
                        <td class="num">{{ number_format((float)$row->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="sw-empty">No Other Income is recorded for this settlement.</td></tr>
                @endforelse
                </tbody>
                <tfoot><tr><th colspan="4" class="text-right">Other Income Total</th><th class="num">{{ number_format((float)$settlement->total_other_income, 2) }}</th></tr></tfoot>
            </table>
        </div>
    </div>

    <div class="sw-card">
        <h3>Credit Sales</h3>
        <div class="table-responsive">
            <table class="sw-table sw-snapshot-table">
                <thead><tr><th>Customer</th><th>Order No</th><th>Vehicle</th><th>Product</th><th class="num">Qty</th><th class="num">Unit Price</th><th class="num">Amount</th></tr></thead>
                <tbody>
                @forelse($settlement->creditSales as $row)
                    <tr>
                        <td>{{ $creditCustomerMap[(int)$row->contact_id] ?? ($row->contact_id ? ('Customer #' . $row->contact_id) : '—') }}</td>
                        <td>{{ $row->order_no ?: ($row->reference ?: '—') }}</td>
                        <td>{{ $row->vehicle_no ?: '—' }}</td>
                        <td>{{ $productMap[(int)$row->product_id] ?? ($row->product_id ? ('Product #' . $row->product_id) : '—') }}</td>
                        <td class="num">{{ number_format((float)$row->quantity, 3) }}</td>
                        <td class="num">{{ number_format((float)$row->unit_price, 2) }}</td>
                        <td class="num">{{ number_format((float)$row->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="sw-empty">No Credit Sales are recorded for this settlement.</td></tr>
                @endforelse
                </tbody>
                <tfoot><tr><th colspan="6" class="text-right">Credit Sales Total</th><th class="num">{{ number_format((float)$settlement->total_credit_sales, 2) }}</th></tr></tfoot>
            </table>
        </div>
    </div>

    <div class="sw-card">
        <h3>Payment Details</h3>
        <div class="table-responsive">
            <table class="sw-table sw-payment-detail-table">
                <thead>
                    <tr>
                        <th>Payment Type</th>
                        <th>Customer</th>
                        <th>Account / Bank</th>
                        <th>Expense Category</th>
                        <th>Reference</th>
                        <th class="num">Amount</th>
                        <th>Payment Note</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td><strong>{{ $payment->type }}</strong></td>
                        <td>{{ $payment->customer }}</td>
                        <td>{{ $payment->account }}</td>
                        <td>{{ $payment->expense_category }}</td>
                        <td>{{ $payment->reference }}</td>
                        <td class="num">{{ number_format((float) $payment->amount, 2) }}</td>
                        <td>{{ $payment->note !== '' ? $payment->note : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="sw-empty">No payment details are recorded for this settlement.</td></tr>
                @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-right">Total Collected</th>
                        <th class="num">{{ number_format((float) $settlement->total_collected, 2) }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if(!empty($settlement->note))
        <div class="sw-card sw-settlement-note-card">
            <h3>Settlement Note</h3>
            <div class="sw-settlement-note">{{ $settlement->note }}</div>
        </div>
    @endif
</div>

@if(!$printMode)
<div class="sw-actions sw-detail-actions no-print" style="margin-top:10px">
    <a href="{{ route('sw.settlements.index') }}" class="sw-btn secondary">
        <i class="fa fa-arrow-left"></i> List SW Settlements
    </a>
    <a href="{{ route('sw.settlements.print', $settlement->id) }}" target="_blank" class="sw-btn">
        <i class="fa fa-print"></i> Print
    </a>
</div>
@endif
@endsection

@push('css')
<style>
.sw-settlement-detail-print{font-size:14px}
.sw-detail-head{display:grid;grid-template-columns:repeat(3,minmax(180px,1fr));gap:16px}
.sw-detail-head>div{min-width:0}
.sw-detail-head span{display:block;color:#68758b;font-size:11px;text-transform:uppercase;letter-spacing:.04em;font-weight:700;margin-bottom:4px}
.sw-detail-head strong{font-size:14px;color:#172033;line-height:1.45;word-break:break-word}
.sw-detail-operators{grid-column:span 2}
.sw-settlement-stats{margin-bottom:16px}
.sw-payment-detail-table th,.sw-payment-detail-table td{font-size:13px}
.sw-snapshot-table th,.sw-snapshot-table td{font-size:12.5px}
.sw-snapshot-table tfoot th{border-top:2px solid #dfe5ee;padding-top:8px}
.sw-payment-detail-table tfoot th{border-top:2px solid #dfe5ee;padding-top:11px}
.sw-settlement-note{white-space:pre-wrap;line-height:1.55;color:#354052}

@media(max-width:767px){
    .sw-detail-head{grid-template-columns:1fr}
    .sw-detail-operators{grid-column:auto}
}

@media print{
    @page{size:A4 landscape;margin:10mm}
    .main-header,.main-sidebar,.content-header,.sw-head,.sw-actions,.main-footer,.no-print{display:none!important}
    .content-wrapper,.content{margin:0!important;padding:0!important;background:#fff!important}
    .content-wrapper{min-height:0!important}
    .sw-settlement-detail-print{font-size:10.5pt;color:#000}
    .sw-card{box-shadow:none!important;border:1px solid #d8dde6!important;border-radius:4px!important;break-inside:avoid;padding:11px!important;margin-bottom:10px!important}
    .sw-detail-head{grid-template-columns:repeat(3,1fr);gap:9px}
    .sw-detail-head span{font-size:8pt;color:#555!important}
    .sw-detail-head strong{font-size:10pt;color:#000!important}
    .sw-stat{box-shadow:none!important;border:1px solid #d8dde6!important;padding:9px!important}
    .sw-stat .k{font-size:8pt!important}
    .sw-stat .v{font-size:12pt!important;color:#000!important}
    .sw-payment-detail-table th,.sw-payment-detail-table td,.sw-snapshot-table th,.sw-snapshot-table td{font-size:8.2pt!important;padding:4px 5px!important;color:#000!important}
    a[href]:after{content:""!important}
}
</style>
@endpush

@if($printMode)
@push('javascript')
<script>
$(function(){ setTimeout(function(){ window.print(); }, 180); });
</script>
@endpush
@endif
