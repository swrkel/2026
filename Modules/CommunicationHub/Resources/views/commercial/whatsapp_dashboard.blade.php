@extends('communicationhub::layout')
@section('communicationhub_title', 'WhatsApp Dashboard')
@section('communicationhub_content')
@include('communicationhub::partials.professional-styles')
<div class="ch-card">
    <div class="ch-card-header">
        <div><h3 class="ch-card-title"><i class="fa fa-whatsapp"></i> WhatsApp Business Dashboard</h3><div class="ch-card-subtitle">Business-wise WhatsApp messaging, templates, schedules and delivery status.</div></div>
        <div>
            <a href="{{ route('communicationhub.commercial.send_whatsapp') }}" class="btn btn-primary btn-sm"><i class="fa fa-paper-plane"></i> Send WhatsApp</a>
            <a href="{{ route('communicationhub.commercial.whatsapp_profiles') }}" class="btn btn-default btn-sm"><i class="fa fa-cogs"></i> Profiles</a>
        </div>
    </div>
    <div class="ch-card-body">
        <div class="row">
            @foreach(['profiles'=>'Profiles','templates'=>'Templates','messages'=>'Messages','sent'=>'Sent','pending'=>'Pending','scheduled'=>'Scheduled','failed'=>'Failed'] as $key=>$label)
            <div class="col-md-3 col-sm-6"><div class="ch-stat-card"><div class="ch-stat-label">{{ $label }}</div><div class="ch-stat-value">{{ number_format($stats[$key] ?? 0) }}</div></div></div>
            @endforeach
        </div>
    </div>
</div>
<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title">Recent WhatsApp Queue</h3><div class="ch-card-subtitle">Latest queued/sent/failed WhatsApp records for this business.</div></div></div>
    <div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr><th>ID</th><th>Recipient</th><th>Type</th><th>Status</th><th>Scheduled</th><th>Created</th><th>Action</th></tr></thead><tbody>
        @forelse($messages as $msg)<tr><td>{{ $msg->id ?? '' }}</td><td>{{ $msg->recipient ?? '' }}</td><td>{{ $msg->message_type ?? 'text' }}</td><td><span class="label label-info">{{ $msg->status ?? '' }}</span></td><td>{{ $msg->scheduled_at ?? '-' }}</td><td>{{ $msg->created_at ?? '' }}</td><td><div class="btn-group"><button class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">Action <span class="caret"></span></button><ul class="dropdown-menu dropdown-menu-right"><li><form method="POST" action="{{ route('communicationhub.commercial.messages.retry', $msg->id ?? 0) }}">@csrf<button class="btn btn-link btn-xs" type="submit">Retry</button></form></li><li><form method="POST" action="{{ route('communicationhub.commercial.messages.mark_sent', $msg->id ?? 0) }}">@csrf<button class="btn btn-link btn-xs" type="submit">Mark Sent</button></form></li></ul></div></td></tr>
        @empty<tr><td colspan="7" class="text-center text-muted">No WhatsApp messages found.</td></tr>@endforelse
    </tbody></table></div>
</div>
@endsection
