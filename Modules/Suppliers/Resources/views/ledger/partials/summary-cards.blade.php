<div class="row supplier-summary-cards mb-3">
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-text">@lang('suppliers::lang.purchase_total')</span><span class="info-box-number text-right">{{ number_format($summary['purchase_total'] ?? 0, config('suppliers.currency_precision', 2)) }}</span></div></div>
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-text">@lang('suppliers::lang.return_total')</span><span class="info-box-number text-right">{{ number_format($summary['return_total'] ?? 0, config('suppliers.currency_precision', 2)) }}</span></div></div>
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-text">@lang('suppliers::lang.paid_total')</span><span class="info-box-number text-right">{{ number_format($summary['paid_total'] ?? 0, config('suppliers.currency_precision', 2)) }}</span></div></div>
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-text">@lang('suppliers::lang.balance_due')</span><span class="info-box-number text-right">{{ number_format($summary['balance_due'] ?? 0, config('suppliers.currency_precision', 2)) }}</span></div></div>
</div>
