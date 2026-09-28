@extends('distributionnew::layouts.app')
@section('title','Loading Plan Details')
@section('page_actions')
@if($plan)
<form method="POST" action="{{ route('distributionnew.loading-plans.approve', $plan->id) }}" style="display:inline">@csrf<button class="btn btn-info">Approve</button></form>
<form method="POST" action="{{ route('distributionnew.loading-plans.convert', $plan->id) }}" style="display:inline">@csrf<button class="btn btn-primary">Convert to Loading</button></form>
@endif
@endsection
@section('module_content')
<div class="disnew-card">
@if(!$plan)<p class="text-danger">Loading plan not found.</p>@else
<div class="row"><div class="col-md-3"><b>Plan No</b><br>{{ $plan->plan_no }}</div><div class="col-md-3"><b>Date</b><br>{{ $plan->plan_date }}</div><div class="col-md-3"><b>Vehicle</b><br>{{ $plan->vehicle_id }}</div><div class="col-md-3"><b>Status</b><br>{{ $plan->status }}</div></div>
<hr>
<table class="table table-bordered disnew-table"><thead><tr><th>Product</th><th class="text-right">Ordered</th><th class="text-right">Planned</th><th class="text-right">Loaded</th></tr></thead><tbody>
@foreach($lines as $line)<tr><td>{{ $line->product_name ?: $line->product_id }}</td><td class="text-right">{{ number_format($line->ordered_qty,4) }}</td><td class="text-right">{{ number_format($line->planned_qty,4) }}</td><td class="text-right">{{ number_format($line->loaded_qty,4) }}</td></tr>@endforeach
</tbody></table>
@endif
</div>
@endsection
