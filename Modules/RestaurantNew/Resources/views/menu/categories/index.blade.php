@extends('restaurantnew::layouts.app')
@section('restaurantnew_content')
@include('restaurantnew::setup.partials.header', ['title' => __('restaurantnew::lang.menu_categories')])
<section class="content restaurant-new-menu">
@include('restaurantnew::partials.toolbar')
<div class="box box-primary rn-pos-box">
    <div class="box-header with-border">
        <h3 class="box-title">@lang('restaurantnew::lang.menu_categories')</h3>
        <a href="{{ route('restaurant-new.menu-categories.create') }}" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> @lang('messages.add')</a>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped rn-data-table">
            <thead><tr><th>@lang('messages.action')</th><th>@lang('restaurantnew::lang.name')</th><th>@lang('restaurantnew::lang.code')</th><th>@lang('restaurantnew::lang.parent')</th><th>@lang('restaurantnew::lang.items')</th><th>@lang('restaurantnew::lang.status')</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="rn-action-cell">
                        <a class="btn btn-xs btn-info" href="{{ route('restaurant-new.menu-categories.edit',$row->id) }}"><i class="fa fa-edit"></i></a>
                        <form method="POST" action="{{ route('restaurant-new.menu-categories.toggle',$row->id) }}" class="rn-inline-form">@csrf<button class="btn btn-xs btn-default"><i class="fa fa-power-off"></i></button></form>
                    </td>
                    <td>{{ $row->name }}</td><td>{{ $row->code }}</td><td>{{ optional($row->parent)->name }}</td><td>{{ $row->items_count ?? 0 }}</td><td>{{ $row->is_active ? __('restaurantnew::lang.active') : __('restaurantnew::lang.inactive') }}</td>
                </tr>
            @empty<tr><td colspan="6" class="text-center">@lang('messages.no_data')</td></tr>@endforelse
            </tbody>
        </table>
        {{ $rows->links() }}
    </div>
</div>
</section>
@endsection
