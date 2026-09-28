@extends('restaurantnew::layouts.app')
@section('rest_title','Waiter Ordering Screen')
@section('rest_subtitle','Touch-friendly ordering for dine-in, takeaway and delivery.')
@section('rest_content')
@if(!$shift)
<div class="rest-alert rest-alert-warning"><i class="fa fa-clock-o"></i><span>No open shift was found. <a href="{{ route('restaurant-new.shifts.index') }}">Open a shift</a> before saving orders.</span></div>
@endif
<form method="post" action="{{ route('restaurant-new.waiter.orders.store') }}" id="rest-order-form">
@csrf
<input type="hidden" name="send_to_kitchen" value="1">
<input type="hidden" name="location_id" value="{{ $locationId }}">
<div class="rest-order-workspace">
    <section class="rest-menu-panel rest-card">
        <div class="rest-order-toolbar">
            <div class="rest-search"><i class="fa fa-search"></i><input id="rest-menu-search" placeholder="Search menu item or code..."></div>
            <div class="rest-category-pills"><button type="button" class="active" data-rest-category="all">All</button>@foreach($categories as $category)<button type="button" data-rest-category="{{ $category->id }}">{{ $category->name }}</button>@endforeach</div>
        </div>
        <div class="rest-menu-grid" id="rest-menu-grid">
        @forelse($items as $item)
            @php
                $modifierPayload=$item->modifierGroups->map(fn($group)=>[
                    'id'=>$group->id,
                    'name'=>$group->name,
                    'required'=>(bool)$group->is_required,
                    'min'=>(int)$group->min_select,
                    'max'=>(int)$group->max_select,
                    'options'=>$group->modifiers->map(fn($modifier)=>['id'=>$modifier->id,'name'=>$modifier->name,'price'=>(float)$modifier->price_delta])->values(),
                ])->values()->toJson();
            @endphp
            <button type="button" class="rest-menu-item"
                data-id="{{ $item->id }}"
                data-code="{{ $item->item_code }}"
                data-name="{{ $item->name }}"
                data-price="{{ $item->selling_price }}"
                data-takeaway-price="{{ $item->takeaway_price }}"
                data-dine-in="{{ $item->is_dine_in?1:0 }}"
                data-takeaway="{{ $item->is_takeaway?1:0 }}" data-delivery="{{ $item->is_delivery?1:0 }}"
                data-modifiers="{{ base64_encode($modifierPayload) }}"
                data-category="{{ $item->category_id }}">
                <span class="rest-menu-icon"><i class="fa fa-cutlery"></i></span>
                <strong>{{ $item->name }}</strong>
                <small>{{ $item->item_code }} · {{ $item->category?->name }}</small>
                <b>{{ number_format((float)$item->selling_price,4) }}</b>
                <em>{{ $item->preparation_minutes }} min</em>
            </button>
        @empty<div class="rest-empty">Add menu items from Menu Setup.</div>@endforelse
        </div>
    </section>
    <aside class="rest-cart-panel rest-card">
        <div class="rest-card-head"><div><h3><i class="fa fa-shopping-cart"></i> Current Order</h3><p>Items selected for this guest.</p></div><span class="rest-cart-count" id="rest-cart-count">0</span></div>
        <div class="rest-form-grid rest-form-grid-2">
            <label>Order Type<select name="order_type" id="rest-order-type" required><option value="dine_in">Dine In</option><option value="takeaway">Takeaway</option><option value="delivery">Delivery</option></select></label>
            <label>Table<select name="table_id" id="rest-table-select"><option value="">Select table</option>@foreach($tables as $table)<option value="{{ $table->id }}" {{ $table->status!=='available'?'disabled':'' }}>{{ $table->floor?->name }} / {{ $table->name }} ({{ ucfirst($table->status) }})</option>@endforeach</select></label>
            <label>Reservation<select name="reservation_id" id="rest-reservation-select"><option value="">No reservation</option>@foreach($reservations as $reservation)<option value="{{ $reservation->id }}" data-table="{{ $reservation->table_id }}">{{ $reservation->reservation_no }} · {{ $reservation->customer_name }} · {{ $reservation->reserved_at?->format('H:i') }}</option>@endforeach</select></label><label>Customer<input name="customer_name" placeholder="Optional for dine-in"></label>
            <label>Phone<input name="customer_phone" placeholder="Takeaway / delivery phone"></label>
            <label>Guests<input type="number" name="guest_count" value="1" min="1"></label><label class="rest-delivery-field">Delivery Zone<select name="delivery_zone_id" id="rest-delivery-zone"><option value="">Select zone</option>@foreach($deliveryZones as $zone)<option value="{{ $zone->id }}" data-fee="{{ $zone->delivery_fee }}">{{ $zone->name }} · {{ number_format((float)$zone->delivery_fee,4) }}</option>@endforeach</select></label><label class="rest-delivery-field rest-span-2">Delivery Address<textarea name="delivery_address" id="rest-delivery-address" placeholder="Required for delivery"></textarea></label>
            <label>Order Note<input name="notes" placeholder="General instructions"></label>
        </div>
        <div id="rest-cart-lines" class="rest-cart-lines"><div class="rest-empty">Tap a menu item to add it.</div></div>
        <div class="rest-cart-summary"><span>Subtotal <strong id="rest-cart-subtotal">0.0000</strong></span><span class="rest-cart-total">Estimated Total <strong id="rest-cart-total">0.0000</strong></span></div>
        <button class="rest-btn rest-btn-primary rest-btn-block" type="submit" {{ !$shift?'disabled':'' }}><i class="fa fa-paper-plane"></i> Save & Send to Kitchen</button>
        <a class="rest-btn rest-btn-light rest-btn-block" href="{{ route('restaurant-new.dashboard') }}">Cancel</a>
    </aside>
</div>
</form>

<div class="rest-modal" id="rest-modifier-modal" aria-hidden="true">
    <div class="rest-modal-dialog">
        <div class="rest-card-head"><div><h3 id="rest-modifier-title">Select Modifiers</h3><p>Required choices must be completed before adding the item.</p></div><button type="button" class="rest-icon-btn" data-rest-modal-close><i class="fa fa-times"></i></button></div>
        <div id="rest-modifier-groups"></div>
        <label>Item Instructions<input id="rest-modifier-note" maxlength="500" placeholder="No onion, less spicy, allergy note..."></label>
        <div class="rest-modal-actions"><button type="button" class="rest-btn rest-btn-light" data-rest-modal-close>Cancel</button><button type="button" class="rest-btn rest-btn-primary" id="rest-add-configured-item"><i class="fa fa-plus"></i> Add Item</button></div>
    </div>
</div>
@endsection
