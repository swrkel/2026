@extends('leadsnew::layouts.app')
@section('title', 'Lead Source Report')
@section('leadsnew_subtitle', 'Compare lead acquisition volume by source and identify the strongest channels.')
@section('leadsnew_content')
<div class="ln-toolbar"><div class="ln-search"><input type="text" class="form-control js-ln-table-search" data-target="#ln-source-report" placeholder="Search lead sources..."></div><a href="{{ url('/leads-new/reports') }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Reports Centre</a></div>
<div class="ln-panel"><div class="ln-panel-header"><div><h3 class="ln-panel-title"><i class="fa fa-compass"></i> Source Performance</h3><div class="ch-card-subtitle">Sources with the highest lead volume appear first.</div></div><span class="ln-badge">{{ count($rows) }} sources</span></div><div class="ln-table table-responsive"><table id="ln-source-report" class="table table-hover"><thead><tr><th>#</th><th>Lead Source</th><th class="text-right">Total Leads</th></tr></thead><tbody>
@forelse($rows as $index => $row)<tr><td>{{ $index + 1 }}</td><td><strong>{{ $row->source ?: 'Unspecified' }}</strong></td><td class="text-right"><span class="ln-badge status-new">{{ number_format($row->total) }}</span></td></tr>@empty<tr><td colspan="3"><div class="ln-empty"><i class="fa fa-compass"></i>No source data found.</div></td></tr>@endforelse
</tbody></table></div></div>
@endsection
