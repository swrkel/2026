@extends('leadsnew::layouts.app')
@section('title', 'Notification Centre')
@section('leadsnew_subtitle', 'Review unread Leads-New alerts and operational reminders assigned to you.')
@section('leadsnew_content')
<div class="ln-panel">
    <div class="ln-panel-header"><div><h3 class="ln-panel-title"><i class="fa fa-bell-o"></i> Unread Notifications</h3><div class="ch-card-subtitle">Most recent notifications are shown first.</div></div><span class="ln-badge status-new">{{ count($notifications ?? []) }} unread</span></div>
    <div class="ln-panel-body">
        <div class="ln-notification-list">
        @forelse($notifications ?? [] as $notification)
            <div class="ln-notification-item"><div class="ln-notification-icon"><i class="fa fa-bell"></i></div><div><strong>{{ $notification->title }}</strong><p>{{ $notification->message }}</p><small>{{ optional(\Carbon\Carbon::parse($notification->created_at))->format('Y-m-d h:i A') }}</small></div></div>
        @empty
            <div class="ln-empty"><i class="fa fa-check-circle"></i>You have no unread Leads-New notifications.</div>
        @endforelse
        </div>
    </div>
</div>
@endsection
