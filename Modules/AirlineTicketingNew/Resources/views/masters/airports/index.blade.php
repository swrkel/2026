@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::messages.airports'))
@section('atn-content')
<div class="atn-toolbar">
<form method="GET" class="atn-master-search"><input name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ __('airlineticketingnew::messages.search') }}"></form>
<a class="btn btn-primary" href="{{ route('airline-ticketing-new.masters.airports.create') }}"><i class="fa fa-plus"></i> {{ __('airlineticketingnew::messages.add') }}</a>
</div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered table-striped atn-table">
<thead><tr><th>#</th><th>{{ __('airlineticketingnew::messages.code') }}</th><th>{{ __('airlineticketingnew::messages.name') }}</th><th>{{ __('airlineticketingnew::messages.status') }}</th><th>{{ __('airlineticketingnew::messages.actions') }}</th></tr></thead>
<tbody>@forelse($records as $record)<tr><td>{{ $record->id }}</td><td>{{ $record->code ?? $record->iata_code ?? '-' }}</td><td>{{ $record->name ?? '-' }}</td><td>{{ $record->is_active ? __('airlineticketingnew::messages.active') : __('airlineticketingnew::messages.inactive') }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('airline-ticketing-new.masters.airports.edit', $record) }}"><i class="fa fa-edit"></i></a></td></tr>@empty<tr><td colspan="5" class="text-center">{{ __('airlineticketingnew::messages.no_records') }}</td></tr>@endforelse</tbody>
</table></div><div class="atn-pagination">{{ $records->links() }}</div></div>
@endsection
