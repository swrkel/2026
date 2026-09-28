@extends('leadsnew::layouts.app')
@section('title', 'Campaigns')
@section('leadsnew_subtitle', 'Plan campaigns and compare budget, cost and generated revenue from one workspace.')
@section('leadsnew_content')
<div class="ln-split-grid">
    <div class="ln-panel">
        <div class="ln-panel-header"><div><h3 class="ln-panel-title"><i class="fa fa-bullhorn"></i> Campaign Register</h3><div class="ch-card-subtitle">Current and historical lead campaigns.</div></div><span class="ln-badge">{{ method_exists($campaigns, 'total') ? $campaigns->total() : count($campaigns) }} campaigns</span></div>
        <div class="ln-table table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Campaign</th><th>Period</th><th class="text-right">Budget</th><th class="text-right">Cost</th><th class="text-right">Revenue</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($campaigns as $campaign)
                    <tr>
                        <td><strong>{{ $campaign->name }}</strong><br><span class="text-muted">{{ $campaign->code ?: 'No code' }}</span></td>
                        <td>{{ $campaign->start_date ?: '-' }}<br><span class="text-muted">to {{ $campaign->end_date ?: '-' }}</span></td>
                        <td class="text-right">{{ number_format((float) $campaign->budget, 2) }}</td>
                        <td class="text-right">{{ number_format((float) $campaign->cost, 2) }}</td>
                        <td class="text-right"><strong>{{ number_format((float) $campaign->revenue, 2) }}</strong></td>
                        <td><span class="ln-badge {{ $campaign->is_active ? 'status-converted' : 'status-lost' }}">{{ $campaign->is_active ? 'Active' : 'Inactive' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="ln-empty"><i class="fa fa-bullhorn"></i>No campaigns created yet.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($campaigns, 'links'))<div class="ln-panel-body">{{ $campaigns->links() }}</div>@endif
    </div>
    <div class="ln-panel ln-sticky-panel">
        <div class="ln-panel-header"><h3 class="ln-panel-title"><i class="fa fa-plus-circle"></i> New Campaign</h3></div>
        <div class="ln-panel-body">
            <form method="post" action="{{ url('/leads-new/campaigns') }}">@csrf
                <div class="form-group"><label>Campaign Name</label><input type="text" name="name" class="form-control" value="{{ old('name') }}" required></div>
                <div class="form-group"><label>Code</label><input type="text" name="code" class="form-control" value="{{ old('code') }}"></div>
                <div class="row"><div class="col-sm-6"><div class="form-group"><label>Start Date</label><input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}"></div></div><div class="col-sm-6"><div class="form-group"><label>End Date</label><input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}"></div></div></div>
                <div class="row"><div class="col-sm-6"><div class="form-group"><label>Budget</label><input type="number" step="0.01" name="budget" class="form-control" value="{{ old('budget', 0) }}"></div></div><div class="col-sm-6"><div class="form-group"><label>Cost</label><input type="number" step="0.01" name="cost" class="form-control" value="{{ old('cost', 0) }}"></div></div></div>
                <div class="form-group"><label>Revenue</label><input type="number" step="0.01" name="revenue" class="form-control" value="{{ old('revenue', 0) }}"></div>
                <input type="hidden" name="is_active" value="1">
                <button class="btn btn-primary btn-block"><i class="fa fa-save"></i> Save Campaign</button>
            </form>
        </div>
    </div>
</div>
@endsection
