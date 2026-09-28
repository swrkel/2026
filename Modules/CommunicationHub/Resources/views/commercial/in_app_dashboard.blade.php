@extends('communicationhub::layout')
@section('communicationhub_title', 'In-App Dashboard')
@section('communicationhub_content')
@include('communicationhub::partials.professional-styles')
<div class="ch-card">
    <div class="ch-card-header">
        <div><h3 class="ch-card-title"><i class="fa fa-bell-o"></i> In-App Notification Dashboard</h3><div class="ch-card-subtitle">Business-wise ERP alerts, inbox messages, approvals and urgent notifications.</div></div>
        <div>
            <a href="{{ route('communicationhub.commercial.send_in_app') }}" class="btn btn-primary btn-sm"><i class="fa fa-paper-plane"></i> Send Alert</a>
            <a href="{{ route('communicationhub.commercial.in_app_inbox') }}" class="btn btn-default btn-sm"><i class="fa fa-inbox"></i> Inbox</a>
        </div>
    </div>
    <div class="ch-card-body"><div class="row">
        @foreach(['notifications'=>'Total Alerts','unread'=>'Unread','urgent'=>'Urgent','templates'=>'Templates','alerts'=>'System Alerts','approvals'=>'Approvals'] as $key=>$label)
            <div class="col-md-3 col-sm-6"><div class="ch-stat-card"><div class="ch-stat-label">{{ $label }}</div><div class="ch-stat-value">{{ number_format($stats[$key] ?? 0) }}</div></div></div>
        @endforeach
    </div></div>
</div>
<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title">Latest In-App Notifications</h3><div class="ch-card-subtitle">Latest system-visible messages for this business/location scope.</div></div></div>
    <div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr><th>ID</th><th>Title</th><th>Recipient</th><th>Type</th><th>Priority</th><th>Status</th><th>Created</th><th>Action</th></tr></thead><tbody>
        @forelse($notifications as $n)<tr><td>{{ $n->id ?? '' }}</td><td>{{ $n->title ?? '' }}</td><td>{{ $n->recipient_user_id ?? $n->recipient_role ?? $n->recipient_group ?? 'All' }}</td><td>{{ $n->notification_type ?? 'info' }}</td><td>{{ $n->priority ?? 'normal' }}</td><td><span class="label label-info">{{ $n->status ?? 'unread' }}</span></td><td>{{ $n->created_at ?? '' }}</td><td><div class="btn-group"><button class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">Action <span class="caret"></span></button><ul class="dropdown-menu dropdown-menu-right"><li><form method="POST" action="{{ route('communicationhub.commercial.in_app.mark_read', $n->id ?? 0) }}">@csrf<button class="btn btn-link btn-xs" type="submit">Mark Read</button></form></li><li><form method="POST" action="{{ route('communicationhub.commercial.in_app.archive', $n->id ?? 0) }}">@csrf<button class="btn btn-link btn-xs" type="submit">Archive</button></form></li></ul></div></td></tr>
        @empty<tr><td colspan="8" class="text-center text-muted">No in-app notifications found.</td></tr>@endforelse
    </tbody></table></div>
</div>
@endsection
