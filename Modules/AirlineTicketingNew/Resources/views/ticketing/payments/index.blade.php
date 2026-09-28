@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::ticketing.payments'))
@section('atn-content')
@include('airlineticketingnew::ticketing.navigation')
<div class="atn-toolbar"><form method="GET" class="atn-ticketing-search"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="{{ __('airlineticketingnew::ticketing.search_payments') }}"></form></div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>{{ __('airlineticketingnew::ticketing.payment_no') }}</th><th>{{ __('airlineticketingnew::ticketing.payment_date') }}</th><th>{{ __('airlineticketingnew::ticketing.method') }}</th><th>{{ __('airlineticketingnew::ticketing.reference_no') }}</th><th>{{ __('airlineticketingnew::ticketing.amount') }}</th><th>{{ __('airlineticketingnew::ticketing.actions') }}</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ $record->payment_no }}</td><td>{{ optional($record->payment_date)->format('Y-m-d') }}</td><td>{{ $record->payment_method }}</td><td>{{ $record->reference_no }}</td><td class="text-right">{{ number_format((float)$record->amount,4) }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('airline-ticketing-new.payments.show',$record) }}"><i class="fa fa-eye"></i></a></td></tr>
@empty<tr><td colspan="6" class="text-center">{{ __('airlineticketingnew::ticketing.no_records') }}</td></tr>@endforelse
</tbody></table></div><div class="atn-pagination">{{ $records->links() }}</div></div>
@endsection
