@extends('restaurantnew::layouts.app')
@section('rest_title','Menu Modifiers')
@section('rest_subtitle','Create optional or required choices and attach them to Restaurant-New menu items.')
@section('rest_actions')
<a class="rest-btn rest-btn-light" href="{{ route('restaurant-new.menu.index') }}"><i class="fa fa-book"></i> Menu Setup</a>
@endsection
@section('rest_content')
<div class="rest-grid-3">
    <section class="rest-card">
        <div class="rest-card-head"><div><h3>Add Modifier Group</h3><p>Example: Size, Extras or Cooking Preference.</p></div></div>
        <form method="post" action="{{ route('restaurant-new.modifiers.groups.store') }}">
            @csrf
            <div class="rest-form-grid rest-form-grid-2">
                <label class="rest-span-2">Group Name<input name="name" required maxlength="120"></label>
                <label>Minimum<input type="number" name="min_select" min="0" value="0" required></label>
                <label>Maximum<input type="number" name="max_select" min="1" value="1" required></label>
                <label class="rest-check"><input type="checkbox" name="is_required" value="1"> Required</label>
                <label class="rest-check"><input type="checkbox" name="is_active" value="1" checked> Active</label>
            </div>
            <button class="rest-btn rest-btn-primary"><i class="fa fa-plus"></i> Add Group</button>
        </form>
    </section>

    <section class="rest-card">
        <div class="rest-card-head"><div><h3>Add Modifier Option</h3><p>Price may be positive, zero or negative.</p></div></div>
        <form method="post" action="{{ route('restaurant-new.modifiers.options.store') }}">
            @csrf
            <div class="rest-form-grid rest-form-grid-2">
                <label class="rest-span-2">Modifier Group<select name="modifier_group_id" required><option value="">Select group</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></label>
                <label>Name<input name="name" required maxlength="120"></label>
                <label>Price Change<input type="number" name="price_delta" step="0.0001" value="0"></label>
                <label class="rest-check"><input type="checkbox" name="is_active" value="1" checked> Active</label>
            </div>
            <button class="rest-btn rest-btn-success"><i class="fa fa-plus"></i> Add Option</button>
        </form>
    </section>

    <section class="rest-card">
        <div class="rest-card-head"><div><h3>Attach to Menu Item</h3><p>One item can use multiple modifier groups.</p></div></div>
        <form method="post" id="rest-attach-modifier-form" data-route-template="{{ route('restaurant-new.modifiers.attach',['item'=>'__ITEM__']) }}">
            @csrf
            <div class="rest-form-grid">
                <label>Menu Item<select id="rest-modifier-menu-item" required><option value="">Select item</option>@foreach($items as $item)<option value="{{ $item->id }}">{{ $item->item_code }} · {{ $item->name }}</option>@endforeach</select></label>
                <label>Modifier Group<select name="modifier_group_id" required><option value="">Select group</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></label>
                <label>Sort Order<input type="number" name="sort_order" value="0" min="0"></label>
            </div>
            <button class="rest-btn rest-btn-warning"><i class="fa fa-link"></i> Attach Group</button>
        </form>
    </section>
</div>

<div class="rest-grid-2">
    <section class="rest-card">
        <div class="rest-card-head"><div><h3>Modifier Groups</h3><p>{{ $groups->count() }} groups</p></div></div>
        <div class="table-responsive">
            <table class="rest-table">
                <thead><tr><th>Group</th><th>Rule</th><th>Options</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($groups as $group)
                    <tr>
                        <td><strong>{{ $group->name }}</strong></td>
                        <td>{{ $group->is_required ? 'Required' : 'Optional' }} · {{ $group->min_select }}–{{ $group->max_select }}</td>
                        <td>@forelse($group->modifiers as $modifier)<span class="rest-table-chip">{{ $modifier->name }} ({{ number_format((float)$modifier->price_delta,4) }})</span>@empty<span class="text-muted">No options</span>@endforelse</td>
                        <td>@include('restaurantnew::partials.status',['status'=>$group->is_active?'active':'inactive'])</td>
                    </tr>
                @empty<tr><td colspan="4" class="rest-empty">No modifier groups yet.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="rest-card">
        <div class="rest-card-head"><div><h3>Menu Item Assignments</h3><p>Groups shown to the waiter when an item is selected.</p></div></div>
        <div class="table-responsive">
            <table class="rest-table">
                <thead><tr><th>Menu Item</th><th>Attached Groups</th></tr></thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td><strong>{{ $item->item_code }}</strong><small>{{ $item->name }}</small></td>
                        <td>
                            @forelse($item->modifierGroups as $group)
                                <form method="post" action="{{ route('restaurant-new.modifiers.detach',[$item,$group]) }}" class="rest-chip-form">
                                    @csrf @method('delete')
                                    <button class="rest-table-chip" title="Detach {{ $group->name }}">{{ $group->name }} <i class="fa fa-times"></i></button>
                                </form>
                            @empty<span class="text-muted">No modifier groups</span>@endforelse
                        </td>
                    </tr>
                @empty<tr><td colspan="2" class="rest-empty">Add menu items first.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
