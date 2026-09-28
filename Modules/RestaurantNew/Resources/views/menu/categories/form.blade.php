@extends('restaurantnew::layouts.app')
@section('restaurantnew_content')
@include('restaurantnew::setup.partials.header', ['title' => $row->exists ? __('restaurantnew::lang.edit_menu_category') : __('restaurantnew::lang.add_menu_category')])
<section class="content restaurant-new-menu">
<form method="POST" action="{{ $row->exists ? route('restaurant-new.menu-categories.update',$row->id) : route('restaurant-new.menu-categories.store') }}">
@csrf @if($row->exists) @method('PUT') @endif
<div class="box box-primary rn-pos-box"><div class="box-body row">
    <div class="form-group col-md-4"><label>@lang('restaurantnew::lang.name') *</label><input name="name" class="form-control" value="{{ old('name',$row->name) }}" required></div>
    <div class="form-group col-md-2"><label>@lang('restaurantnew::lang.code')</label><input name="code" class="form-control" value="{{ old('code',$row->code) }}"></div>
    <div class="form-group col-md-3"><label>@lang('restaurantnew::lang.parent')</label><select name="parent_id" class="form-control"><option value="">@lang('messages.please_select')</option>@foreach($parents as $id=>$name)<option value="{{ $id }}" @selected(old('parent_id',$row->parent_id)==$id)>{{ $name }}</option>@endforeach</select></div>
    <div class="form-group col-md-3"><label>@lang('restaurantnew::lang.sort_order')</label><input type="number" name="sort_order" class="form-control" value="{{ old('sort_order',$row->sort_order ?? 0) }}"></div>
    <div class="form-group col-md-12"><label>@lang('restaurantnew::lang.description')</label><textarea name="description" class="form-control" rows="2">{{ old('description',$row->description) }}</textarea></div>
    @include('restaurantnew::menu.partials.availability-flags', ['row' => $row])
</div><div class="box-footer">@include('restaurantnew::setup.partials.form-actions', ['cancelRoute' => route('restaurant-new.menu-categories.index')])</div></div>
</form>
</section>
@endsection
