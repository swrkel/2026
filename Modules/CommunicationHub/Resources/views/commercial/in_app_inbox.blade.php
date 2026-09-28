@extends('communicationhub::layout')
@section('communicationhub_title', 'In-App Inbox')
@section('communicationhub_content')
@include('communicationhub::partials.professional-styles')
<div class="ch-card"><div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-inbox"></i> In-App Inbox</h3><div class="ch-card-subtitle">Business-safe notification inbox with read/archive actions.</div></div><a href="{{ route('communicationhub.commercial.send_in_app') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> New Alert</a></div>
<div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr><th>ID</th><th>Title</th><th>Message</th><th>Recipient</th><th>Type</th><th>Priority</th><th>Status</th><th>Read At</th><th>Action</th></tr></thead><tbody>
@forelse($notifications as $n)<tr><td>{{ $n->id ?? '' }}</td><td>{{ $n->title ?? '' }}</td><td>{{ Str::limit($n->body ?? $n->message ?? '', 90) }}</td><td>{{ $n->recipient_user_id ?? $n->recipient_role ?? $n->recipient_group ?? 'All' }}</td><td>{{ $n->notification_type ?? 'info' }}</td><td>{{ $n->priority ?? 'normal' }}</td><td><span class="label label-info">{{ $n->status ?? 'unread' }}</span></td><td>{{ $n->read_at ?? '-' }}</td><td><div class="btn-group"><button class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">Action <span class="caret"></span></button><ul class="dropdown-menu dropdown-menu-right"><li><form method="POST" action="{{ route('communicationhub.commercial.in_app.mark_read', $n->id ?? 0) }}">@csrf<button class="btn btn-link btn-xs" type="submit">Mark Read</button></form></li><li><form method="POST" action="{{ route('communicationhub.commercial.in_app.archive', $n->id ?? 0) }}">@csrf<button class="btn btn-link btn-xs" type="submit">Archive</button></form></li></ul></div></td></tr>
@empty<tr><td colspan="9" class="text-center text-muted">No notifications found.</td></tr>@endforelse
</tbody></table></div></div>
@endsection
