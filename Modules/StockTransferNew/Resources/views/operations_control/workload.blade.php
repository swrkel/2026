@extends('layouts.app')
@section('title', __('stocktransfernew::messages.warehouse_workload'))
@section('content')
<section class="content-header"><h1>{{ __('stocktransfernew::messages.warehouse_workload') }}</h1></section>
<section class="content"><div class="box"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>To Location</th><th>To Store</th><th>Transfer Count</th><th>Total Qty</th></tr></thead><tbody>
@foreach(($workload ?? []) as $row)
<tr><td>{{ $row->to_location_id }}</td><td>{{ $row->to_store_id }}</td><td>{{ $row->transfer_count }}</td><td>{{ number_format($row->total_qty, 3) }}</td></tr>
@endforeach
</tbody></table></div></div></section>
@endsection
