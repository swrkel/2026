@extends('airlineticketingnew::layouts.app')
@section('atn-title', __('airlineticketingnew::postticket.refunds'))
@section('atn-content')
@include('airlineticketingnew::post-ticket.navigation')
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>{{ __('airlineticketingnew::postticket.refund_no') }}</th><th>{{ __('airlineticketingnew::postticket.request_date') }}</th><th>{{ __('airlineticketingnew::postticket.status') }}</th><th>{{ __('airlineticketingnew::postticket.refund_amount') }}</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ $record->refund_no }}</td><td>{{ $record->request_date }}</td><td>{{ $record->status }}</td><td>{{ $record->refund_amount }}</td></tr>@empty<tr><td colspan="4" class="text-center">{{ __('airlineticketingnew::postticket.no_records') }}</td></tr>@endforelse
</tbody></table></div><div class="atn-pagination">{{ $records->links() }}</div></div>
@endsection
