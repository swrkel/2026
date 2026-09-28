@extends('leadsnew::layouts.app')
@section('title', 'Search Results')
@section('leadsnew_subtitle', 'Review the Leads-New records matching the selected advanced search criteria.')
@section('leadsnew_content')
<div class="ln-toolbar"><div class="ln-search"><input type="text" class="form-control js-ln-table-search" data-target="#ln-search-results" placeholder="Filter displayed results..."></div><a href="{{ url('/leads-new/search') }}" class="btn btn-primary"><i class="fa fa-sliders"></i> Change Filters</a></div>
<div class="ln-panel"><div class="ln-panel-header"><div><h3 class="ln-panel-title"><i class="fa fa-list"></i> Matching Leads</h3><div class="ch-card-subtitle">Open a record to continue lead management.</div></div><span class="ln-badge">{{ method_exists($leads, 'total') ? $leads->total() : count($leads) }} results</span></div><div class="ln-table table-responsive"><table id="ln-search-results" class="table table-hover"><thead><tr><th>Lead No</th><th>Name</th><th>Company</th><th>Mobile</th><th>Email</th><th class="text-right">Action</th></tr></thead><tbody>
@forelse($leads as $lead)<tr><td><strong>{{ $lead->lead_no ?? $lead->id }}</strong></td><td>{{ $lead->name ?? '-' }}</td><td>{{ $lead->company_name ?? '-' }}</td><td>{{ $lead->mobile ?? '-' }}</td><td>{{ $lead->email ?? '-' }}</td><td class="text-right"><a class="btn btn-primary btn-xs" href="{{ url('/leads-new/leads/' . $lead->id) }}"><i class="fa fa-eye"></i> View</a></td></tr>@empty<tr><td colspan="6"><div class="ln-empty"><i class="fa fa-search"></i>No leads matched the selected filters.</div></td></tr>@endforelse
</tbody></table></div>@if(method_exists($leads, 'links'))<div class="ln-panel-body">{{ $leads->appends(request()->except('_token'))->links() }}</div>@endif</div>
@endsection
