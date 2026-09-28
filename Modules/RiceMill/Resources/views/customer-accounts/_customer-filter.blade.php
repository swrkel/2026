@php
    $selectedCustomerId = $customerId ?? 0;
    $excludeKeys = ['customer_id','page'];
@endphp
<div class="rcm-card" style="margin-bottom:14px;">
    <form method="get" class="rcm-form-grid" style="align-items:end;">
        @foreach(request()->except($excludeKeys) as $key=>$value)
            @if(is_scalar($value) && $key !== 'date_range')
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <div class="rcm-field" style="grid-column:span 2;">
            <label>Customer</label>
            <select class="rcm-searchable" name="customer_id" {{ !empty($customerRequired) ? 'required' : '' }}>
                <option value="">{{ !empty($customerRequired) ? 'Select customer' : 'All Customers' }}</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer['id'] }}" {{ (int)$selectedCustomerId===(int)$customer['id']?'selected':'' }}>{{ $customer['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="rcm-field" style="align-self:end;">
            <button class="rcm-btn primary" type="submit"><i class="fa fa-filter"></i> Apply Customer</button>
        </div>
    </form>
</div>
