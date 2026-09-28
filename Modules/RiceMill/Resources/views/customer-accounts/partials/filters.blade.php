@php
    $filterAction = $filterAction ?? url()->current();
    $showCustomer = $showCustomer ?? true;
    $requireCustomer = $requireCustomer ?? false;
@endphp
<form method="get" action="{{ $filterAction }}" class="rcm-toolbar" style="margin-bottom:14px;align-items:end;">
    @foreach(['q','range','from','to','date_range','per_page'] as $key)
        @if(request()->has($key))<input type="hidden" name="{{ $key }}" value="{{ request($key) }}">@endif
    @endforeach

    @if($showCustomer)
        <div class="rcm-field" style="min-width:260px;flex:1;">
            <label>Customer{{ $requireCustomer ? ' *' : '' }}</label>
            <select class="rcm-searchable" name="customer_id" {{ $requireCustomer ? 'required' : '' }}>
                <option value="">{{ $requireCustomer ? 'Select Customer' : 'All Customers' }}</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer['id'] }}" {{ (int)$selectedCustomerId===(int)$customer['id']?'selected':'' }}>{{ $customer['name'] }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="rcm-field" style="min-width:220px;">
        <label>Location</label>
        <select class="rcm-searchable" name="location_id">
            <option value="">All Permitted Locations</option>
            @foreach($locations as $location)
                <option value="{{ $location['id'] }}" {{ (int)$selectedLocationId===(int)$location['id']?'selected':'' }}>{{ $location['name'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="rcm-field" style="min-width:220px;">
        <label>Store</label>
        <select class="rcm-searchable" name="store_id">
            <option value="">All Stores</option>
            @foreach($stores as $store)
                @if(!$selectedLocationId || empty($store['location_id']) || (int)$store['location_id']===(int)$selectedLocationId)
                    <option value="{{ $store['id'] }}" {{ (int)$selectedStoreId===(int)$store['id']?'selected':'' }}>{{ $store['name'] }}</option>
                @endif
            @endforeach
        </select>
    </div>

    <button class="rcm-btn" type="submit"><i class="fa fa-filter"></i> Apply Customer / Location / Store</button>
</form>
