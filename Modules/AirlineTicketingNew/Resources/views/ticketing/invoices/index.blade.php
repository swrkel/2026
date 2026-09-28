@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::ticketing.invoices'))
@section('atn-content')
@include('airlineticketingnew::ticketing.navigation')
<div class="atn-toolbar"><form method="GET" class="atn-ticketing-search"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="{{ __('airlineticketingnew::ticketing.search_invoices') }}"></form></div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>{{ __('airlineticketingnew::ticketing.invoice_no') }}</th><th>{{ __('airlineticketingnew::ticketing.invoice_date') }}</th><th>{{ __('airlineticketingnew::ticketing.grand_total') }}</th><th>{{ __('airlineticketingnew::ticketing.paid_total') }}</th><th>{{ __('airlineticketingnew::ticketing.due_total') }}</th><th>{{ __('airlineticketingnew::ticketing.status') }}</th><th>{{ __('airlineticketingnew::ticketing.actions') }}</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ $record->invoice_no }}</td><td>{{ optional($record->invoice_date)->format('Y-m-d') }}</td><td class="text-right">{{ number_format((float)$record->grand_total,4) }}</td><td class="text-right">{{ number_format((float)$record->paid_total,4) }}</td><td class="text-right">{{ number_format((float)$record->due_total,4) }}</td><td>{{ $record->status }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('airline-ticketing-new.invoices.show',$record) }}"><i class="fa fa-eye"></i></a></td></tr>
@empty<tr><td colspan="7" class="text-center">{{ __('airlineticketingnew::ticketing.no_records') }}</td></tr>@endforelse
</tbody></table></div><div class="atn-pagination">{{ $records->links() }}</div></div>
@endsection
