<div class="supplier-profile-panel">
    <h4>@lang('suppliers::lang.tax_information')</h4>
    <div class="row">
        <div class="col-md-4"><strong>@lang('suppliers::lang.tax_number')</strong><br>{{ $supplier->tax_number ?: '-' }}</div>
    </div>
</div>
