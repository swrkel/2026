@extends('layouts.app')
@section('title', __('stocktransfernew::messages.calendar'))
@section('content')
<section class="content-header"><h1>{{ __('stocktransfernew::messages.calendar') }}</h1></section>
<section class="content"><div class="box"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Transfer No</th><th>Priority</th><th>Status</th><th>Dispatch</th><th>Delivery</th></tr></thead><tbody>
@foreach(($events ?? []) as $row)
<tr><td>{{ $row->transfer_no }}</td><td>{{ $row->priority }}</td><td>{{ $row->status }}</td><td>{{ $row->expected_dispatch_at }}</td><td>{{ $row->expected_delivery_at }}</td></tr>
@endforeach
</tbody></table></div></div></section>
@endsection
