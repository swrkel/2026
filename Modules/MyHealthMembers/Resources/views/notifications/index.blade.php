@extends('layouts.app')
@section('title', 'My Health Notifications')

@section('content')
<section class="content-header">
    <h1>My Health Notifications</h1>
</section>

<section class="content">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row">
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-clock-o"></i></span><div class="info-box-content"><span class="info-box-text">Queued</span><span class="info-box-number">{{ $summary['queued'] ?? 0 }}</span></div></div></div>
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-check"></i></span><div class="info-box-content"><span class="info-box-text">Sent Today</span><span class="info-box-number">{{ $summary['sent_today'] ?? 0 }}</span></div></div></div>
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-warning"></i></span><div class="info-box-content"><span class="info-box-text">Failed</span><span class="info-box-number">{{ $summary['failed'] ?? 0 }}</span></div></div></div>
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-envelope"></i></span><div class="info-box-content"><span class="info-box-text">Templates</span><span class="info-box-number">{{ $summary['templates'] ?? 0 }}</span></div></div></div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Notification Queue</h3>
            <div class="box-tools">
                <a href="{{ route('myhealth.notifications.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Notification</a>
                <a href="{{ route('myhealth.notifications.templates.index') }}" class="btn btn-default btn-sm"><i class="fa fa-envelope"></i> Templates</a>
            </div>
        </div>
        <div class="box-body table-responsive">
            <form method="GET" class="form-inline" style="margin-bottom:15px;">
                <input type="text" name="member" value="{{ $filters['member'] ?? '' }}" class="form-control" placeholder="Member code / name / mobile">
                <select name="channel" class="form-control"><option value="">All Channels</option><option value="sms" @selected(($filters['channel'] ?? '') === 'sms')>SMS</option><option value="email" @selected(($filters['channel'] ?? '') === 'email')>Email</option><option value="whatsapp" @selected(($filters['channel'] ?? '') === 'whatsapp')>WhatsApp</option></select>
                <select name="status" class="form-control"><option value="">All Status</option><option value="queued" @selected(($filters['status'] ?? '') === 'queued')>Queued</option><option value="sent" @selected(($filters['status'] ?? '') === 'sent')>Sent</option><option value="failed" @selected(($filters['status'] ?? '') === 'failed')>Failed</option></select>
                <button class="btn btn-info" type="submit"><i class="fa fa-search"></i> Search</button>
            </form>
            <table class="table table-bordered table-striped">
                <thead><tr><th>Date</th><th>Member</th><th>Channel</th><th>Recipient</th><th>Purpose</th><th>Status</th><th>Message</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($notifications as $notification)
                        <tr>
                            <td>{{ optional($notification->created_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ optional($notification->member)->member_code }} {{ optional($notification->member)->name }}</td>
                            <td>{{ strtoupper($notification->channel) }}</td>
                            <td>{{ $notification->recipient }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $notification->purpose ?? '')) }}</td>
                            <td><span class="label label-{{ $notification->status === 'sent' ? 'success' : ($notification->status === 'failed' ? 'danger' : 'warning') }}">{{ ucfirst($notification->status) }}</span></td>
                            <td>{{ \Illuminate\Support\Str::limit($notification->message, 70) }}</td>
                            <td>
                                @if($notification->status !== 'sent')
                                    <form method="POST" action="{{ route('myhealth.notifications.sent', $notification) }}" style="display:inline">@csrf<button class="btn btn-xs btn-success">Sent</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">No notifications found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $notifications->appends(request()->query())->links() }}
        </div>
    </div>
</section>
@endsection
