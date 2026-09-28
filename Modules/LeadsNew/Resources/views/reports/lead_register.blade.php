@extends('leadsnew::layouts.app')
@section('title', 'Lead Register Report')
@section('leadsnew_subtitle', 'Review and export the latest lead register from the standalone Leads-New module.')
@section('leadsnew_content')
@include('leadsnew::components.toolbar', ['csvUrl' => request()->fullUrlWithQuery(['export' => 'csv'])])
<div class="ln-panel"><div class="ln-panel-header"><div><h3 class="ln-panel-title"><i class="fa fa-list-alt"></i> Lead Register</h3><div class="ch-card-subtitle">Up to the latest 1,000 lead records.</div></div><span class="ln-badge">{{ count($rows) }} records</span></div><div class="ln-table table-responsive"><table class="table table-hover leads-new-datatable"><thead><tr><th>Lead No</th><th>Name</th><th>Mobile</th><th>Email</th><th>Status</th><th>Priority</th></tr></thead><tbody>@forelse($rows as $row)<tr><td><a href="{{ url('/leads-new/leads/' . $row->id) }}"><strong>{{ $row->lead_no ?? $row->id }}</strong></a></td><td>{{ $row->name ?? $row->lead_name ?? '-' }}</td><td>{{ $row->mobile ?? '-' }}</td><td>{{ $row->email ?? '-' }}</td><td><span class="ln-badge">{{ $row->status ?? '-' }}</span></td><td>{{ $row->priority ?? '-' }}</td></tr>@empty<tr><td colspan="6"><div class="ln-empty"><i class="fa fa-list-alt"></i>No lead records found.</div></td></tr>@endforelse</tbody></table></div></div>
@endsection
