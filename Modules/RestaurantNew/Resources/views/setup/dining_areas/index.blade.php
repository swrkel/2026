@extends('restaurantnew::layouts.app')
@section('restaurantnew_content')
@include('restaurantnew::setup.partials.header', ['title' => __('restaurantnew::lang.dining_areas')])
<section class="content restaurant-new-setup">
@include('restaurantnew::partials.toolbar')
<div class="box box-primary rn-pos-box">
    <div class="box-header with-border"><h3 class="box-title">@lang('restaurantnew::lang.dining_areas')</h3><a href="{{ route('restaurant-new.dining-areas.create') }}" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> @lang('messages.add')</a></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped rn-data-table"><thead><tr><th>@lang('messages.action')</th><th>@lang('restaurantnew::lang.name')</th><th>@lang('restaurantnew::lang.code')</th><th>@lang('restaurantnew::lang.tables')</th><th>@lang('restaurantnew::lang.status')</th></tr></thead><tbody>
        @forelse($rows as $row)<tr><td><a class="btn btn-xs btn-info" href="{{ route('restaurant-new.dining-areas.edit',$row->id) }}"><i class="fa fa-edit"></i></a></td><td>{{ $row->name }}</td><td>{{ $row->code }}</td><td>{{ $row->tables_count ?? 0 }}</td><td>{{ $row->is_active ? __('restaurantnew::lang.active') : __('restaurantnew::lang.inactive') }}</td></tr>@empty<tr><td colspan="5" class="text-center">@lang('messages.no_data')</td></tr>@endforelse
        </tbody></table>{{ $rows->links() }}
    </div>
</div>
</section>
@endsection
