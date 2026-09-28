@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::transactions.quotations'))
@section('atn-content')
@include('airlineticketingnew::transactions.navigation')
<div class="atn-toolbar">
<form method="GET" class="atn-transaction-search"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="{{ __('airlineticketingnew::transactions.search_quotations') }}"></form>
@can('airline_ticketing_new.quotations.create')
<a class="btn btn-primary" href="{{ route('airline-ticketing-new.quotations.create') }}"><i class="fa fa-plus"></i> {{ __('airlineticketingnew::transactions.new_quotation') }}</a>
@endcan
</div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered table-striped atn-table">
<thead><tr><th>{{ __('airlineticketingnew::transactions.quotation_no') }}</th><th>{{ __('airlineticketingnew::transactions.date') }}</th><th>{{ __('airlineticketingnew::transactions.customer_type') }}</th><th>{{ __('airlineticketingnew::transactions.currency') }}</th><th>{{ __('airlineticketingnew::transactions.grand_total') }}</th><th>{{ __('airlineticketingnew::transactions.status') }}</th><th>{{ __('airlineticketingnew::transactions.actions') }}</th></tr></thead>
<tbody>
@forelse($records as $record)
<tr><td>{{ $record->quotation_no }}</td><td>{{ optional($record->quotation_date)->format('Y-m-d') }}</td><td>{{ $record->customer_type }}</td><td>{{ $record->currency_code }}</td><td class="text-right">{{ number_format((float)$record->grand_total,4) }}</td><td>{{ $record->status }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('airline-ticketing-new.quotations.show',$record) }}"><i class="fa fa-eye"></i></a></td></tr>
@empty<tr><td colspan="7" class="text-center">{{ __('airlineticketingnew::transactions.no_records') }}</td></tr>@endforelse
</tbody></table></div><div class="atn-pagination">{{ $records->links() }}</div></div>
@endsection
