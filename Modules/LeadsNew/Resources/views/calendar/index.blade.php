@extends('leadsnew::layouts.app')
@section('title', __('leadsnew::lang.calendar_centre'))
@section('leadsnew_subtitle', __('leadsnew::lang.calendar_centre_help'))
@section('leadsnew_content')
<div class="ln-toolbar"><div class="ln-search"><input type="text" class="form-control js-ln-table-search" data-target="#ln-calendar-events" placeholder="Search calendar events..."></div><a href="{{ url('/leads-new/followups') }}" class="btn btn-primary"><i class="fa fa-calendar-check-o"></i> Follow-ups</a></div>
<div class="ln-panel"><div class="ln-panel-header"><div><h3 class="ln-panel-title"><i class="fa fa-calendar"></i> Scheduled Lead Events</h3><div class="ch-card-subtitle">Calls, meetings, visits, tasks and follow-ups in chronological order.</div></div><span class="ln-badge">{{ method_exists($events, 'total') ? $events->total() : count($events) }} events</span></div><div class="ln-table table-responsive"><table id="ln-calendar-events" class="table table-hover"><thead><tr><th>Start</th><th>End</th><th>Event</th><th>Status</th></tr></thead><tbody>
@forelse($events as $event)<tr><td>{{ optional($event->starts_at)->format('Y-m-d h:i A') ?? $event->starts_at ?? '-' }}</td><td>{{ optional($event->ends_at)->format('Y-m-d h:i A') ?? $event->ends_at ?? '-' }}</td><td><strong>{{ $event->title }}</strong></td><td><span class="ln-badge">{{ $event->status ?? 'Scheduled' }}</span></td></tr>@empty<tr><td colspan="4"><div class="ln-empty"><i class="fa fa-calendar-o"></i>No scheduled events found.</div></td></tr>@endforelse
</tbody></table></div>@if(method_exists($events, 'links'))<div class="ln-panel-body">{{ $events->links() }}</div>@endif</div>
@endsection
