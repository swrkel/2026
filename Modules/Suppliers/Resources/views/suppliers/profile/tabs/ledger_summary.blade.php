<div class="supplier-profile-panel">
    <h4>@lang('suppliers::lang.ledger_summary')</h4>
    <div class="row supplier-summary-cards">
        <div class="col-md-4"><div class="small-box bg-aqua"><div class="inner"><h3>{{ number_format((float)($ledgerSummary['purchase_total'] ?? 0), 2) }}</h3><p>@lang('suppliers::lang.purchase_total')</p></div></div></div>
        <div class="col-md-4"><div class="small-box bg-green"><div class="inner"><h3>{{ number_format((float)($ledgerSummary['paid_total'] ?? 0), 2) }}</h3><p>@lang('suppliers::lang.paid_total')</p></div></div></div>
        <div class="col-md-4"><div class="small-box bg-yellow"><div class="inner"><h3>{{ number_format((float)($ledgerSummary['balance'] ?? 0), 2) }}</h3><p>@lang('suppliers::lang.balance')</p></div></div></div>
    </div>
    <a href="{{ route('suppliers.ledger.index', $supplier->id) }}" class="btn btn-primary btn-sm"><i class="fa fa-list"></i> @lang('suppliers::lang.open_supplier_ledger')</a>
</div>
