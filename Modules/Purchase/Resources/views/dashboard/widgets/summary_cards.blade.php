<div class="col-md-3 col-sm-6 col-xs-12">
    <div class="info-box">
        <span class="info-box-icon bg-aqua"><i class="fa fa-shopping-cart"></i></span>
        <div class="info-box-content">
            <span class="info-box-text">@lang('purchase::dashboard.purchase_count')</span>
            <span class="info-box-number" id="purchase_dashboard_purchase_count">{{ number_format($summary['purchase_count'] ?? 0) }}</span>
        </div>
    </div>
</div>

<div class="col-md-3 col-sm-6 col-xs-12">
    <div class="info-box">
        <span class="info-box-icon bg-green"><i class="fa fa-money"></i></span>
        <div class="info-box-content">
            <span class="info-box-text">@lang('purchase::dashboard.purchase_total')</span>
            <span class="info-box-number text-right" id="purchase_dashboard_purchase_total">{{ number_format($summary['purchase_total'] ?? 0, 2) }}</span>
        </div>
    </div>
</div>

<div class="col-md-3 col-sm-6 col-xs-12">
    <div class="info-box">
        <span class="info-box-icon bg-yellow"><i class="fa fa-check"></i></span>
        <div class="info-box-content">
            <span class="info-box-text">@lang('purchase::dashboard.paid_total')</span>
            <span class="info-box-number text-right" id="purchase_dashboard_paid_total">{{ number_format($summary['paid_total'] ?? 0, 2) }}</span>
        </div>
    </div>
</div>

<div class="col-md-3 col-sm-6 col-xs-12">
    <div class="info-box">
        <span class="info-box-icon bg-red"><i class="fa fa-warning"></i></span>
        <div class="info-box-content">
            <span class="info-box-text">@lang('purchase::dashboard.outstanding_total')</span>
            <span class="info-box-number text-right" id="purchase_dashboard_outstanding_total">{{ number_format($summary['outstanding_total'] ?? 0, 2) }}</span>
        </div>
    </div>
</div>
