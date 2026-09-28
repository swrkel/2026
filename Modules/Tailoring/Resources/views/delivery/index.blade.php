@extends('tailoring::layouts.app')
@section('page_title', 'Delivery Management')
@section('tailoring_content')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Delivery Schedule</h3></div><div class="box-body">
<form method="POST" action="{{ route('tailoring.delivery.store') }}">@csrf
<div class="row"><div class="col-md-2"><input type="date" class="form-control" name="delivery_date" value="{{ date('Y-m-d') }}" required></div><div class="col-md-2"><input type="time" class="form-control" name="delivery_time"></div><div class="col-md-2"><select name="delivery_status" class="form-control"><option value="scheduled">Scheduled</option><option value="ready">Ready</option><option value="delivered">Delivered</option></select></div><div class="col-md-2"><input class="form-control" name="balance_to_collect" placeholder="Balance"></div><div class="col-md-4"><input class="form-control" name="delivery_notes" placeholder="Notes"></div></div><br><button class="btn btn-primary">Save Delivery</button>
</form><hr><table class="table table-bordered"><thead><tr><th>Date</th><th>Time</th><th>Status</th><th>Balance</th><th>Notes</th></tr></thead><tbody>@forelse($deliveries as $d)<tr><td>{{ optional($d->delivery_date)->format('Y-m-d') }}</td><td>{{ $d->delivery_time }}</td><td>{{ $d->delivery_status }}</td><td>{{ number_format($d->balance_to_collect, 2) }}</td><td>{{ $d->delivery_notes }}</td></tr>@empty<tr><td colspan="5" class="text-center">No deliveries</td></tr>@endforelse</tbody></table>
</div></div>
@endsection
