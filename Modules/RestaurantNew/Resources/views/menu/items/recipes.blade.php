@extends('restaurantnew::layouts.app')
@section('restaurantnew_content')
@include('restaurantnew::setup.partials.header', ['title' => __('restaurantnew::lang.recipe_setup') . ' - ' . $row->name])
<section class="content restaurant-new-menu">
<form method="POST" action="{{ route('restaurant-new.menu-items.recipes.save',$row->id) }}">@csrf
<div class="box box-primary rn-pos-box"><div class="box-body">
    <p class="text-muted">@lang('restaurantnew::lang.recipe_help')</p>
    @php($recipes = old('recipes', $row->recipes ?? collect()))
    @for($i = 0; $i < max(10, count($recipes)); $i++)
        @php($recipe = $recipes[$i] ?? null)
        <div class="row rn-repeat-row">
            <div class="form-group col-md-4"><input name="recipes[{{ $i }}][ingredient_name]" class="form-control" placeholder="@lang('restaurantnew::lang.ingredient_name')" value="{{ data_get($recipe,'ingredient_name') }}"></div>
            <div class="form-group col-md-2"><input name="recipes[{{ $i }}][ingredient_sku]" class="form-control" placeholder="@lang('restaurantnew::lang.sku')" value="{{ data_get($recipe,'ingredient_sku') }}"></div>
            <div class="form-group col-md-2"><input name="recipes[{{ $i }}][unit]" class="form-control" placeholder="@lang('restaurantnew::lang.unit')" value="{{ data_get($recipe,'unit') }}"></div>
            <div class="form-group col-md-2"><input type="number" step="0.0001" name="recipes[{{ $i }}][quantity]" class="form-control" placeholder="@lang('restaurantnew::lang.quantity')" value="{{ data_get($recipe,'quantity') }}"></div>
            <div class="form-group col-md-2"><input type="number" step="0.0001" name="recipes[{{ $i }}][wastage_percent]" class="form-control" placeholder="@lang('restaurantnew::lang.wastage_percent')" value="{{ data_get($recipe,'wastage_percent') }}"></div>
        </div>
    @endfor
</div><div class="box-footer">@include('restaurantnew::setup.partials.form-actions', ['cancelRoute' => route('restaurant-new.menu-items.index')])</div></div>
</form>
</section>
@endsection
