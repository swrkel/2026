@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::transactions.reservations'))
@section('atn-content')
@include('airlineticketingnew::transactions.navigation')
<div class="atn-toolbar"><form method="GET" class="atn-transaction-search"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="{{ __('airlineticketingnew::transactions.search_reservations') }}"></form></div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered table-striped atn-table"><thead><tr><th>{{ __('airlineticketingnew::transactions.reservation_no') }}</th><th>{{ __('airlineticketingnew::transactions.pnr_code') }}</th><th>{{ __('airlineticketingnew::transactions.date') }}</th><th>{{ __('airlineticketingnew::transactions.deadline') }}</th><th>{{ __('airlineticketingnew::transactions.grand_total') }}</th><th>{{ __('airlineticketingnew::transactions.due_total') }}</th><th>{{ __('airlineticketingnew::transactions.status') }}</th><th>{{ __('airlineticketingnew::transactions.actions') }}</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ $record->reservation_no }}</td><td>{{ $record->pnr_code }}</td><td>{{ optional($record->reservation_date)->format('Y-m-d') }}</td><td>{{ $record->ticketing_deadline }}</td><td class="text-right">{{ number_format((float)$record->grand_total,4) }}</td><td class="text-right">{{ number_format((float)$record->due_total,4) }}</td><td>{{ $record->status }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('airline-ticketing-new.reservations.show',$record) }}"><i class="fa fa-eye"></i></a></td></tr>
@empty<tr><td colspan="8" class="text-center">{{ __('airlineticketingnew::transactions.no_records') }}</td></tr>@endforelse
</tbody></table></div><div class="atn-pagination">{{ $records->links() }}</div></div>
@endsection
