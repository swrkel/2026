<div class="col-md-6">
    <div class="box box-warning">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('purchase::dashboard.outstanding_payables')</h3>
        </div>
        <div class="box-body">
            <h3 class="text-right" id="purchase_dashboard_outstanding_widget">
                {{ number_format($summary['outstanding_total'] ?? 0, 2) }}
            </h3>
        </div>
    </div>
</div>
