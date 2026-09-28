<div class="supplier-profile-panel">
    <h4>@lang('suppliers::lang.basic_information')</h4>
    <div class="row">
        <div class="col-md-4"><strong>@lang('suppliers::lang.supplier_no')</strong><br>{{ $summary['supplier_no'] ?? '-' }}</div>
        <div class="col-md-4"><strong>@lang('suppliers::lang.name')</strong><br>{{ $summary['name'] ?? '-' }}</div>
        <div class="col-md-4"><strong>@lang('suppliers::lang.business_name')</strong><br>{{ $summary['business_name'] ?? '-' }}</div>
    </div>
    <hr>
    <div class="row">
        <div class="col-md-4"><strong>@lang('suppliers::lang.created_at')</strong><br>{{ $summary['created_at'] ?? '-' }}</div>
        <div class="col-md-4"><strong>@lang('suppliers::lang.updated_at')</strong><br>{{ $summary['updated_at'] ?? '-' }}</div>
    </div>
</div>
