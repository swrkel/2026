<div class="supplier-profile-panel">
    <h4>@lang('suppliers::lang.contact_information')</h4>
    <div class="row">
        <div class="col-md-3"><strong>@lang('suppliers::lang.mobile')</strong><br>{{ $supplier->mobile ?: '-' }}</div>
        <div class="col-md-3"><strong>@lang('suppliers::lang.alternate_number')</strong><br>{{ $supplier->alternate_number ?: '-' }}</div>
        <div class="col-md-3"><strong>@lang('suppliers::lang.landline')</strong><br>{{ $supplier->landline ?: '-' }}</div>
        <div class="col-md-3"><strong>@lang('suppliers::lang.email')</strong><br>{{ $supplier->email ?: '-' }}</div>
    </div>
</div>
