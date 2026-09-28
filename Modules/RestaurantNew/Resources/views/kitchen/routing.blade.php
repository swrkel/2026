@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::lang.kitchen_routing'))
@section('content')
<div class="rn-page">
    <h3>@lang('restaurantnew::lang.kitchen_routing')</h3>
    <form method="POST" action="{{ route('restaurantnew.kitchen.routing.store') }}" class="rn-card rn-form-grid">
        @csrf
        <input name="location_id" class="form-control" placeholder="Location ID" required>
        <input name="menu_category_id" class="form-control" placeholder="Menu Category ID">
        <input name="menu_item_id" class="form-control" placeholder="Menu Item ID">
        <input name="kitchen_section_id" class="form-control" placeholder="Kitchen Section ID" required>
        <select name="order_type" class="form-control"><option value="">Any Order Type</option><option>dine_in</option><option>takeaway</option><option>delivery</option></select>
        <input name="priority" class="form-control" placeholder="Priority" value="10">
        <label><input type="checkbox" name="is_active" value="1" checked> Active</label>
        <button class="btn btn-primary">@lang('restaurantnew::lang.save')</button>
    </form>
    <div class="rn-card mt-3">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Location</th><th>Category</th><th>Item</th><th>Kitchen Section</th><th>Order Type</th><th>Status</th></tr></thead>
            <tbody>@foreach($rules as $rule)<tr><td>{{ $rule->location_id }}</td><td>{{ $rule->menu_category_id }}</td><td>{{ $rule->menu_item_id }}</td><td>{{ $rule->kitchen_section_id }}</td><td>{{ $rule->order_type ?: 'Any' }}</td><td>{{ $rule->is_active ? 'Active' : 'Inactive' }}</td></tr>@endforeach</tbody>
        </table>
        {{ $rules->links() }}
    </div>
</div>
@endsection
