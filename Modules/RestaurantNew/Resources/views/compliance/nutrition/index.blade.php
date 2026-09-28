@extends('restaurantnew::compliance.layout')
@section('compliance_body')
@include('restaurantnew::compliance.partials.nav')
<h3>{{ __('restaurantnew::compliance.nutrition_profiles') }}</h3>
<div class="table-responsive">
<table class="table table-bordered table-striped restaurantnew-datatable">
<thead><tr><th>#</th><th>{{ __('restaurantnew::compliance.name') }}</th><th>{{ __('restaurantnew::compliance.status') }}</th><th>{{ __('restaurantnew::compliance.created_at') }}</th></tr></thead>
<tbody>
@foreach($profiles ?? [] as $row)
<tr><td>{{ $row->id }}</td><td>{{ $row->name ?? $row->title ?? $row->menu_item_id ?? '-' }}</td><td>{{ $row->status ?? $row->warning_status ?? ($row->is_active ? 'active' : 'inactive') }}</td><td>{{ optional($row->created_at)->format('Y-m-d H:i') }}</td></tr>
@endforeach
</tbody>
</table>
</div>
@endsection
