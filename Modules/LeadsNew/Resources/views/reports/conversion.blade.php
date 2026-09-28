@extends('leadsnew::layouts.app')
@section('title', 'Conversion Report')
@section('leadsnew_subtitle', 'Review lead distribution by current outcome and workflow status.')
@section('leadsnew_content')
@include('leadsnew::components.toolbar')
<div class="ln-panel"><div class="ln-panel-header"><div><h3 class="ln-panel-title"><i class="fa fa-exchange"></i> Conversion Summary</h3><div class="ch-card-subtitle">Lead totals grouped by their current status.</div></div><span class="ln-badge">{{ count($rows) }} statuses</span></div><div class="ln-table table-responsive"><table class="table table-hover leads-new-datatable"><thead><tr><th>Status</th><th class="text-right">Total Leads</th></tr></thead><tbody>@forelse($rows as $row)<tr><td><strong>{{ $row->status ?: 'Unspecified' }}</strong></td><td class="text-right"><span class="ln-badge status-new">{{ number_format($row->total) }}</span></td></tr>@empty<tr><td colspan="2"><div class="ln-empty"><i class="fa fa-exchange"></i>No conversion data found.</div></td></tr>@endforelse</tbody></table></div></div>
@endsection
