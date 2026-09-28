@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::profiles.corporate_customers'))
@section('atn-content')
@include('airlineticketingnew::profiles.navigation')
<div class="atn-toolbar"><form method="GET" class="atn-profile-search"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="{{ __('airlineticketingnew::profiles.search_companies') }}"></form><a class="btn btn-primary" href="{{ route('airline-ticketing-new.corporate-customers.create') }}"><i class="fa fa-plus"></i> {{ __('airlineticketingnew::profiles.add_company') }}</a></div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>{{ __('airlineticketingnew::profiles.customer_no') }}</th><th>{{ __('airlineticketingnew::profiles.company_name') }}</th><th>{{ __('airlineticketingnew::profiles.contact_person') }}</th><th>{{ __('airlineticketingnew::profiles.phone') }}</th><th>{{ __('airlineticketingnew::profiles.credit_limit') }}</th><th>{{ __('airlineticketingnew::profiles.actions') }}</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ $record->customer_no }}</td><td>{{ $record->company_name }}</td><td>{{ $record->contact_person }}</td><td>{{ $record->phone }}</td><td class="text-right">{{ number_format((float)$record->credit_limit,4) }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('airline-ticketing-new.corporate-customers.edit',$record) }}"><i class="fa fa-edit"></i></a><a class="btn btn-xs btn-info" href="{{ route('airline-ticketing-new.corporate-customers.contacts.index',$record) }}"><i class="fa fa-users"></i></a></td></tr>
@empty<tr><td colspan="6" class="text-center">{{ __('airlineticketingnew::profiles.no_records') }}</td></tr>@endforelse
</tbody></table></div><div class="atn-pagination">{{ $records->links() }}</div></div>
@endsection
