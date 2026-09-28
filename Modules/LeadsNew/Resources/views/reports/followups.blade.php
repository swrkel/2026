@extends('leadsnew::layouts.app')
@section('title', 'Follow-up Report')
@section('leadsnew_subtitle', 'Monitor scheduled lead contacts and their completion status.')
@section('leadsnew_content')
@include('leadsnew::components.toolbar')
<div class="ln-panel"><div class="ln-panel-header"><div><h3 class="ln-panel-title"><i class="fa fa-calendar-check-o"></i> Follow-up Register</h3><div class="ch-card-subtitle">Latest follow-up records are shown first.</div></div><span class="ln-badge">{{ count($rows) }} records</span></div><div class="ln-table table-responsive"><table class="table table-hover leads-new-datatable"><thead><tr><th>Date</th><th>Lead</th><th>Type</th><th>Status</th><th>Notes</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row->followup_at ?? $row->followup_date ?? $row->created_at }}</td><td><a href="{{ url('/leads-new/leads/' . $row->lead_id) }}"><strong>Lead #{{ $row->lead_id }}</strong></a></td><td>{{ $row->type ?? '-' }}</td><td><span class="ln-badge">{{ $row->status ?? '-' }}</span></td><td>{{ \Illuminate\Support\Str::limit($row->note ?? $row->notes ?? '-', 80) }}</td></tr>@empty<tr><td colspan="5"><div class="ln-empty"><i class="fa fa-calendar-o"></i>No follow-up records found.</div></td></tr>@endforelse</tbody></table></div></div>
@endsection
