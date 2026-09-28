
@php
    $total_sale_closed = (float) ($meter_sales_total ?? $day_entries->unique('id')->sum('amount'));
    $total_other_sales = (float) $other_sale;
    $total_payments = (float) ($payments->total ?? 0);
    $balance_to_settle = $total_sale_closed + $total_other_sales - $total_payments;
    $current_balance_to_operator = -1 * (float) ($payments->shortage_excess ?? 0);
@endphp
<style>
.petropd-summary-dashboard{background:#f8fafc;border:1px solid #e5edf5;border-radius:12px;padding:16px;margin-bottom:18px;box-shadow:0 2px 8px rgba(15,23,42,.06)}
.petropd-summary-title{font-weight:800;color:#334155;font-size:16px;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;gap:10px}
.petropd-summary-badge{background:#e8f7ef;color:#16834a;border-radius:20px;padding:5px 12px;font-size:12px;font-weight:700}
.petropd-summary-cards{display:grid;grid-template-columns:repeat(4,minmax(160px,1fr));gap:12px;margin-bottom:14px}
.petropd-summary-card{background:#fff;border:1px solid #edf2f7;border-radius:10px;padding:12px;min-height:72px}
.petropd-summary-label{color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.03em;margin-bottom:7px}
.petropd-summary-value{font-size:18px;font-weight:900;color:#0f172a;text-align:right}
.petropd-summary-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.petropd-summary-panel{background:#fff;border:1px solid #edf2f7;border-radius:10px;padding:12px}
.petropd-summary-panel-title{font-weight:900;color:#334155;margin-bottom:10px;border-bottom:1px solid #edf2f7;padding-bottom:8px}
.petropd-summary-row{display:flex;justify-content:space-between;gap:10px;padding:6px 0;border-bottom:1px dashed #edf2f7;color:#334155}
.petropd-summary-row:last-child{border-bottom:0}
.petropd-summary-row strong{font-weight:900}
@media(max-width:991px){.petropd-summary-cards{grid-template-columns:repeat(2,1fr)}.petropd-summary-grid{grid-template-columns:1fr}}
@media(max-width:575px){.petropd-summary-cards{grid-template-columns:1fr}}
</style>
<div class="col-md-12">
    <div class="petropd-summary-dashboard">
        <div class="petropd-summary-title">
            <span>@lang('petropd::lang.pumper_day_entries')</span>
            <span class="petropd-summary-badge">Summary</span>
        </div>
        <div class="petropd-summary-cards">
            <div class="petropd-summary-card"><div class="petropd-summary-label">Total Sale of all closed pumps</div><div class="petropd-summary-value">{{ @num_format($total_sale_closed) }}</div></div>
            <div class="petropd-summary-card"><div class="petropd-summary-label">Total Payments</div><div class="petropd-summary-value">{{ @num_format($total_payments) }}</div></div>
            <div class="petropd-summary-card"><div class="petropd-summary-label">Balance to Settle</div><div class="petropd-summary-value">{{ @num_format($balance_to_settle) }}</div></div>
            <div class="petropd-summary-card"><div class="petropd-summary-label">Current Balance to Operator</div><div class="petropd-summary-value">{{ @num_format($current_balance_to_operator) }}</div></div>
        </div>
        <div class="petropd-summary-grid">
            <div class="petropd-summary-panel">
                <div class="petropd-summary-panel-title"><i class="fa fa-info-circle"></i> Shift Details</div>
                <div class="petropd-summary-row"><span>Total Other Sales</span><strong>{{ @num_format($total_other_sales) }}</strong></div>
                <div class="petropd-summary-row"><span>Balance to Settle</span><strong>{{ @num_format($balance_to_settle) }}</strong></div>
            </div>
            <div class="petropd-summary-panel">
                <div class="petropd-summary-panel-title"><i class="fa fa-money"></i> Payment Summary</div>
                <div class="petropd-summary-row"><span>@lang('petropd::lang.cash')</span><strong>{{ @num_format($payments->cash ?? 0) }}</strong></div>
                <div class="petropd-summary-row"><span>@lang('petropd::lang.credit_sales')</span><strong>{{ @num_format($payments->credit ?? 0) }}</strong></div>
                <div class="petropd-summary-row"><span>@lang('petropd::lang.credit_cards')</span><strong>{{ @num_format($payments->card ?? 0) }}</strong></div>
                <div class="petropd-summary-row"><span>@lang('petropd::lang.cheque_sales')</span><strong>{{ @num_format($payments->cheque ?? 0) }}</strong></div>
                <div class="petropd-summary-row"><span>@lang('petropd::lang.other')</span><strong>{{ @num_format($payments->other ?? 0) }}</strong></div>
            </div>
        </div>
    </div>
</div>
