@extends('distributionnew::layouts.app')
@section('content')
<div class="disnew-page">

@include('distributionnew::partials.erp-standard-styles')
<div class="disnew-card"><div class="disnew-card-header"><h3>Sales Rep Settlements</h3><a class="btn btn-primary" href="{{ route('distribution-new.settlements.create') }}">Create Settlement</a></div>
<table class="table table-bordered table-striped"><thead><tr><th>No</th><th>Date</th><th>Sales Rep</th><th>Vehicle</th><th>Loaded</th><th>Sold</th><th>Collections</th><th>Short/Excess</th><th>Status</th><th>Action</th></tr></thead><tbody>
@foreach($settlements as $row)<tr><td>{{ $row->settlement_no }}</td><td>{{ $row->settlement_date }}</td><td>{{ $row->sales_rep_id }}</td><td>{{ $row->vehicle_id }}</td><td class="text-right">{{ number_format($row->loaded_value,4) }}</td><td class="text-right">{{ number_format($row->sold_value,4) }}</td><td class="text-right">{{ number_format($row->collection_total,4) }}</td><td class="text-right">{{ number_format($row->shortage_value - $row->excess_value,4) }}</td><td>{{ ucfirst($row->status) }}</td><td>@if($row->status!='finalized')<form method="post" action="{{ route('distribution-new.settlements.finalize',$row->id) }}">@csrf<button class="btn btn-success btn-xs">Finalize</button></form>@endif</td></tr>@endforeach
</tbody></table>{{ $settlements->links() }}</div>
</div>
@endsection
