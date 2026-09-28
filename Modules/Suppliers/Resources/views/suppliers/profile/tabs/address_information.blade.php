<div class="supplier-profile-panel">
    <h4>@lang('suppliers::lang.address_information')</h4>
    <div class="row">
        <div class="col-md-6"><strong>@lang('suppliers::lang.address_line_1')</strong><br>{{ $address['address_line_1'] ?? '-' }}</div>
        <div class="col-md-6"><strong>@lang('suppliers::lang.address_line_2')</strong><br>{{ $address['address_line_2'] ?? '-' }}</div>
    </div>
    <hr>
    <div class="row">
        <div class="col-md-3"><strong>@lang('suppliers::lang.city')</strong><br>{{ $address['city'] ?? '-' }}</div>
        <div class="col-md-3"><strong>@lang('suppliers::lang.state')</strong><br>{{ $address['state'] ?? '-' }}</div>
        <div class="col-md-3"><strong>@lang('suppliers::lang.country')</strong><br>{{ $address['country'] ?? '-' }}</div>
        <div class="col-md-3"><strong>@lang('suppliers::lang.zip_code')</strong><br>{{ $address['zip_code'] ?? '-' }}</div>
    </div>
</div>
