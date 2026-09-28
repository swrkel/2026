@extends('restaurantnew::layouts.app')
@section('restaurantnew_content')
@include('restaurantnew::setup.partials.header', ['title' => $row->exists ? __('restaurantnew::lang.edit_menu_item') : __('restaurantnew::lang.add_menu_item')])
<section class="content restaurant-new-menu">
<form method="POST" action="{{ $row->exists ? route('restaurant-new.menu-items.update',$row->id) : route('restaurant-new.menu-items.store') }}">
@csrf @if($row->exists) @method('PUT') @endif
<div class="box box-primary rn-pos-box"><div class="box-body row">
    <div class="form-group col-md-3"><label>@lang('restaurantnew::lang.category') *</label><select name="menu_category_id" class="form-control" required><option value="">@lang('messages.please_select')</option>@foreach($categories as $id=>$name)<option value="{{ $id }}" @selected(old('menu_category_id',$row->menu_category_id)==$id)>{{ $name }}</option>@endforeach</select></div>
    <div class="form-group col-md-3"><label>@lang('restaurantnew::lang.kitchen_section')</label><select name="kitchen_section_id" class="form-control"><option value="">@lang('messages.please_select')</option>@foreach($kitchenSections as $id=>$name)<option value="{{ $id }}" @selected(old('kitchen_section_id',$row->kitchen_section_id)==$id)>{{ $name }}</option>@endforeach</select></div>
    <div class="form-group col-md-3"><label>@lang('restaurantnew::lang.name') *</label><input name="name" class="form-control" value="{{ old('name',$row->name) }}" required></div>
    <div class="form-group col-md-3"><label>@lang('restaurantnew::lang.sku')</label><input name="sku" class="form-control" value="{{ old('sku',$row->sku) }}"></div>
    <div class="form-group col-md-3"><label>@lang('restaurantnew::lang.price') *</label><input type="number" step="0.0001" name="price" class="form-control" value="{{ old('price',$row->price ?? 0) }}" required></div>
    <div class="form-group col-md-3"><label>@lang('restaurantnew::lang.cost_price')</label><input type="number" step="0.0001" name="cost_price" class="form-control" value="{{ old('cost_price',$row->cost_price ?? 0) }}"></div>
    <div class="form-group col-md-2"><label>@lang('restaurantnew::lang.tax_percent')</label><input type="number" step="0.0001" name="tax_percent" class="form-control" value="{{ old('tax_percent',$row->tax_percent ?? 0) }}"></div>
    <div class="form-group col-md-2"><label>@lang('restaurantnew::lang.preparation_time')</label><input type="number" name="preparation_time_minutes" class="form-control" value="{{ old('preparation_time_minutes',$row->preparation_time_minutes ?? 0) }}"></div>
    <div class="form-group col-md-2"><label>@lang('restaurantnew::lang.sort_order')</label><input type="number" name="sort_order" class="form-control" value="{{ old('sort_order',$row->sort_order ?? 0) }}"></div>
    <div class="form-group col-md-12"><label>@lang('restaurantnew::lang.description')</label><textarea name="description" class="form-control" rows="2">{{ old('description',$row->description) }}</textarea></div>
    @include('restaurantnew::menu.partials.availability-flags', ['row' => $row])
    <div class="form-group col-md-12 rn-checkbox-line"><label><input type="checkbox" name="allow_discount" value="1" {{ old('allow_discount',$row->allow_discount ?? true) ? 'checked' : '' }}> @lang('restaurantnew::lang.allow_discount')</label><label><input type="checkbox" name="track_recipe_stock" value="1" {{ old('track_recipe_stock',$row->track_recipe_stock ?? false) ? 'checked' : '' }}> @lang('restaurantnew::lang.track_recipe_stock')</label><label><input type="checkbox" name="is_modifier_required" value="1" {{ old('is_modifier_required',$row->is_modifier_required ?? false) ? 'checked' : '' }}> @lang('restaurantnew::lang.modifier_required')</label></div>
    @include('restaurantnew::menu.partials.variants', ['row' => $row])
    @include('restaurantnew::menu.partials.modifiers', ['row' => $row])
</div><div class="box-footer">@include('restaurantnew::setup.partials.form-actions', ['cancelRoute' => route('restaurant-new.menu-items.index')])</div></div>
</form>
</section>
@endsection
