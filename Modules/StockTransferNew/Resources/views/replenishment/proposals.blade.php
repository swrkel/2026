@extends('layouts.app')
@section('title', __('stocktransfernew::lang.replenishment_proposals'))
@section('content')
<section class="content-header"><h1>@lang('stocktransfernew::lang.replenishment_proposals')</h1></section>
<section class="content stn-page">
<form method="post" action="{{ route('stock-transfer-new.replenishment.generate') }}">@csrf<button class="btn btn-primary">@lang('stocktransfernew::lang.generate_proposals')</button></form><br>
<div class="box box-solid"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Product</th><th>From</th><th>To</th><th>Current</th><th>Min</th><th>Proposed</th><th>Status</th><th>Action</th></tr></thead><tbody>
@foreach($proposals as $proposal)<tr><td>{{ $proposal->product_id }}</td><td>{{ $proposal->from_location_id }} / {{ $proposal->from_store_id }}</td><td>{{ $proposal->to_location_id }} / {{ $proposal->to_store_id }}</td><td>{{ $proposal->current_qty }}</td><td>{{ $proposal->min_qty }}</td><td>{{ $proposal->proposed_qty }}</td><td>{{ $proposal->status }}</td><td>@if($proposal->status === 'open')<form method="post" action="{{ route('stock-transfer-new.replenishment.ignore',$proposal) }}">@csrf<button class="btn btn-xs btn-warning">Ignore</button></form>@endif</td></tr>@endforeach
</tbody></table>{{ $proposals->links() }}</div></div>
</section>
@endsection
