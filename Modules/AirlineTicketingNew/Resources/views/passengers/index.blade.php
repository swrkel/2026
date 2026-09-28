@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::profiles.passengers'))
@section('atn-content')
@include('airlineticketingnew::profiles.navigation')
<div class="atn-toolbar">
<form method="GET" class="atn-profile-search">
<input class="form-control" name="search" value="{{ request('search') }}" placeholder="{{ __('airlineticketingnew::profiles.search_passengers') }}">
</form>
<a class="btn btn-primary" href="{{ route('airline-ticketing-new.passengers.create') }}"><i class="fa fa-plus"></i> {{ __('airlineticketingnew::profiles.add_passenger') }}</a>
</div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered table-striped atn-table">
<thead><tr><th>{{ __('airlineticketingnew::profiles.passenger_no') }}</th><th>{{ __('airlineticketingnew::profiles.name') }}</th><th>{{ __('airlineticketingnew::profiles.phone') }}</th><th>{{ __('airlineticketingnew::profiles.email') }}</th><th>{{ __('airlineticketingnew::profiles.nationality') }}</th><th>{{ __('airlineticketingnew::profiles.actions') }}</th></tr></thead>
<tbody>
@forelse($records as $record)
<tr><td>{{ $record->passenger_no }}</td><td>{{ trim($record->title.' '.$record->first_name.' '.$record->last_name) }}</td><td>{{ $record->phone }}</td><td>{{ $record->email }}</td><td>{{ $record->nationality_code }}</td>
<td><a class="btn btn-xs btn-primary" href="{{ route('airline-ticketing-new.passengers.edit',$record) }}"><i class="fa fa-edit"></i></a>
<a class="btn btn-xs btn-info" href="{{ route('airline-ticketing-new.passengers.documents.index',$record) }}"><i class="fa fa-id-card"></i></a>
<a class="btn btn-xs btn-warning" href="{{ route('airline-ticketing-new.passengers.visas.index',$record) }}"><i class="fa fa-globe"></i></a></td></tr>
@empty<tr><td colspan="6" class="text-center">{{ __('airlineticketingnew::profiles.no_records') }}</td></tr>@endforelse
</tbody></table></div><div class="atn-pagination">{{ $records->links() }}</div></div>
@endsection
