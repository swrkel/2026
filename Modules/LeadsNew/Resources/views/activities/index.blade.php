@extends('leadsnew::layouts.app')
@section('title', 'Lead Activities')
@section('leadsnew_subtitle', 'Review calls, meetings, visits, notes and other engagement recorded against leads.')
@section('leadsnew_content')
<div class="ln-toolbar">
    <div class="ln-search">
        <input type="text" class="form-control js-ln-table-search" data-target="#ln-activities-table" placeholder="Search activities, leads or types...">
    </div>
    <a href="{{ url('/leads-new/leads') }}" class="btn btn-primary"><i class="fa fa-users"></i> Open Leads</a>
</div>
<div class="ln-panel">
    <div class="ln-panel-header">
        <div><h3 class="ln-panel-title"><i class="fa fa-history"></i> Activity Timeline</h3><div class="ch-card-subtitle">Latest engagement records are shown first.</div></div>
        <span class="ln-badge">{{ method_exists($activities, 'total') ? $activities->total() : count($activities) }} records</span>
    </div>
    <div class="ln-table table-responsive">
        <table id="ln-activities-table" class="table table-hover">
            <thead><tr><th>Date & Time</th><th>Lead</th><th>Type</th><th>Title</th><th>Description</th></tr></thead>
            <tbody>
            @forelse($activities as $activity)
                <tr>
                    <td>{{ optional($activity->activity_date)->format('Y-m-d h:i A') ?? optional($activity->created_at)->format('Y-m-d h:i A') ?? '-' }}</td>
                    <td>
                        @if($activity->lead)
                            <a href="{{ url('/leads-new/leads/' . $activity->lead_id) }}"><strong>{{ $activity->lead->name ?? $activity->lead->lead_no ?? ('Lead #' . $activity->lead_id) }}</strong></a>
                        @else
                            Lead #{{ $activity->lead_id }}
                        @endif
                    </td>
                    <td><span class="ln-badge">{{ ucfirst($activity->type ?? 'Activity') }}</span></td>
                    <td>{{ $activity->title ?? '-' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($activity->description ?? '-', 90) }}</td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="ln-empty"><i class="fa fa-history"></i>No activity records found.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($activities, 'links'))<div class="ln-panel-body">{{ $activities->appends(request()->query())->links() }}</div>@endif
</div>
@endsection
