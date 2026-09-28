<div class="row supplier-financial-summary">
    <div class="col-md-4 col-sm-6">
        <div class="info-box"><span class="info-box-icon"><i class="fa fa-list"></i></span><div class="info-box-content"><span class="info-box-text">@lang('suppliers::lang.records')</span><span class="info-box-number">{{ $summary['total_records'] ?? 0 }}</span></div></div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="info-box"><span class="info-box-icon"><i class="fa fa-money"></i></span><div class="info-box-content"><span class="info-box-text">@lang('suppliers::lang.amount')</span><span class="info-box-number text-right">{{ $summary['total_amount'] ?? 0 }}</span></div></div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="info-box"><span class="info-box-icon"><i class="fa fa-balance-scale"></i></span><div class="info-box-content"><span class="info-box-text">@lang('suppliers::lang.balance_due')</span><span class="info-box-number text-right">{{ $summary['balance_due'] ?? 0 }}</span></div></div>
    </div>
</div>
