@extends('layouts.app')
@section('title', __('stocktransfernew::messages.workload_balance'))
@section('content')
<section class="content-header"><h1>{{ __('stocktransfernew::messages.workload_balance') }}</h1></section>
<section class="content stn-043"><div class="box box-solid"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>To Location</th><th>To Store</th><th>Open Transfers</th><th>Open Qty</th><th>Balance Remark</th></tr></thead><tbody>
@foreach($rows as $row)<tr><td>{{ $row->to_location_id }}</td><td>{{ $row->to_store_id }}</td><td>{{ $row->open_transfers }}</td><td>{{ number_format($row->open_qty, 4) }}</td><td>{{ $row->open_transfers > 20 ? 'High workload - consider rebalancing' : 'Normal' }}</td></tr>@endforeach
</tbody></table>
</div></div></section>
@endsection
