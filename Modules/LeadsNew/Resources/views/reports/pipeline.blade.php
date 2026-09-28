@extends('leadsnew::layouts.app')
@section('title', 'Pipeline Report')
@section('leadsnew_subtitle', 'Review opportunity value, stage and probability across the active lead pipeline.')
@section('leadsnew_content')
@include('leadsnew::components.toolbar')
<div class="ln-panel"><div class="ln-panel-header"><div><h3 class="ln-panel-title"><i class="fa fa-filter"></i> Opportunity Pipeline</h3><div class="ch-card-subtitle">Latest opportunity records are shown first.</div></div><span class="ln-badge">{{ count($rows ?? []) }} opportunities</span></div><div class="ln-table table-responsive"><table class="table table-hover leads-new-datatable"><thead><tr><th>Opportunity</th><th>Stage</th><th class="text-right">Value</th><th class="text-right">Probability</th></tr></thead><tbody>@forelse($rows ?? [] as $row)<tr><td><strong>{{ $row->name ?? $row->title ?? ('Opportunity #' . $row->id) }}</strong></td><td><span class="ln-badge">{{ $row->stage ?? $row->status ?? '-' }}</span></td><td class="text-right">{{ number_format((float)($row->estimated_value ?? $row->value ?? 0), 2) }}</td><td class="text-right">{{ number_format((float)($row->probability ?? 0), 1) }}%</td></tr>@empty<tr><td colspan="4"><div class="ln-empty"><i class="fa fa-filter"></i>No opportunities found.</div></td></tr>@endforelse</tbody></table></div></div>
@endsection
