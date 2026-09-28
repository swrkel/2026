@extends('restaurantnew::layouts.app')
@section('restaurantnew_content')
@include('restaurantnew::setup.partials.header', ['title' => __('restaurantnew::lang.menu_items')])
<section class="content restaurant-new-menu">
@include('restaurantnew::partials.toolbar')
<div class="box box-primary rn-pos-box">
    <div class="box-header with-border"><h3 class="box-title">@lang('restaurantnew::lang.menu_items')</h3><a href="{{ route('restaurant-new.menu-items.create') }}" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> @lang('messages.add')</a></div>
    <div class="box-body">
        <form method="GET" class="row rn-filter-row">
            <div class="form-group col-md-4"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="@lang('restaurantnew::lang.search_menu')"></div>
            <div class="form-group col-md-4"><select name="menu_category_id" class="form-control"><option value="">@lang('restaurantnew::lang.all_categories')</option>@foreach($categories as $id=>$name)<option value="{{ $id }}" @selected(request('menu_category_id')==$id)>{{ $name }}</option>@endforeach</select></div>
            <div class="form-group col-md-2"><button class="btn btn-primary btn-block"><i class="fa fa-search"></i> @lang('messages.search')</button></div>
        </form>
        <div class="table-responsive"><table class="table table-bordered table-striped rn-data-table"><thead><tr><th>@lang('messages.action')</th><th>@lang('restaurantnew::lang.name')</th><th>@lang('restaurantnew::lang.category')</th><th>@lang('restaurantnew::lang.kitchen_section')</th><th>@lang('restaurantnew::lang.price')</th><th>@lang('restaurantnew::lang.preparation_time')</th><th>@lang('restaurantnew::lang.status')</th></tr></thead><tbody>
        @forelse($rows as $row)<tr>
            <td class="rn-action-cell"><a class="btn btn-xs btn-info" href="{{ route('restaurant-new.menu-items.edit',$row->id) }}"><i class="fa fa-edit"></i></a> <a class="btn btn-xs btn-warning" href="{{ route('restaurant-new.menu-items.recipes',$row->id) }}"><i class="fa fa-list"></i></a><form method="POST" action="{{ route('restaurant-new.menu-items.toggle',$row->id) }}" class="rn-inline-form">@csrf<button class="btn btn-xs btn-default"><i class="fa fa-power-off"></i></button></form></td>
            <td><strong>{{ $row->name }}</strong><br><small>{{ $row->sku }}</small></td><td>{{ optional($row->category)->name }}</td><td>{{ optional($row->kitchenSection)->name }}</td><td>{{ number_format((float)$row->price, 4) }}</td><td>{{ $row->preparation_time_minutes }} @lang('restaurantnew::lang.minutes')</td><td>{{ $row->is_active ? __('restaurantnew::lang.active') : __('restaurantnew::lang.inactive') }}</td>
        </tr>@empty<tr><td colspan="7" class="text-center">@lang('messages.no_data')</td></tr>@endforelse
        </tbody></table></div>{{ $rows->links() }}
    </div>
</div>
</section>
@endsection
