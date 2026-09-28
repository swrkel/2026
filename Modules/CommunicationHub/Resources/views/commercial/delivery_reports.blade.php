@extends('communicationhub::layout')
@section('communicationhub_title', 'Delivery Reports')
@section('communicationhub_content')
<div class="ch-toolbar">
    <div>
        <span class="ch-filter-pill"><i class="fa fa-truck"></i> Delivery Monitor</span>
        <span class="ch-filter-pill"><i class="fa fa-database"></i> Tenant database</span>
        <span class="ch-filter-pill"><i class="fa fa-building"></i> Business scoped</span>
    </div>
    <div>
        <form method="POST" action="{{ route('communicationhub.commercial.messages.process_pending') }}" style="display:inline;">@csrf<button class="btn btn-success"><i class="fa fa-play"></i> Process Pending (Test)</button></form>
        <a href="{{ route('communicationhub.commercial.send_sms') }}" class="btn btn-primary"><i class="fa fa-paper-plane"></i> Send SMS</a>
    </div>
</div>
<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-comments"></i> Message Status</h3><div class="ch-card-subtitle">Track queued, sent, delivered and failed messages with quick testing actions.</div></div></div>
    <div class="ch-card-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>ID</th><th>Channel</th><th>Recipient</th><th>Message</th><th>Status</th><th>Client</th><th>Sent At</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($messages as $row)
                @php $status = $row->status ?? 'pending'; @endphp
                <tr>
                    <td>{{ $row->id }}</td><td>{{ strtoupper($row->channel ?? 'sms') }}</td><td>{{ $row->recipient ?? '-' }}</td><td>{{ Str::limit($row->message ?? $row->body ?? '', 70) }}</td>
                    <td><span class="ch-badge-soft {{ $status == 'failed' ? 'danger' : ($status == 'pending' || $status == 'scheduled' ? 'warning' : '') }}">{{ ucfirst($status) }}</span></td>
                    <td>{{ $row->client_id ?? '-' }}</td><td>{{ $row->sent_at ?? '-' }}</td>
                    <td style="white-space:nowrap;">
                        <form method="POST" action="{{ route('communicationhub.commercial.messages.mark_sent', $row->id) }}" style="display:inline;">@csrf<button class="btn btn-xs btn-success">Sent</button></form>
                        <form method="POST" action="{{ route('communicationhub.commercial.messages.mark_failed', $row->id) }}" style="display:inline;">@csrf<button class="btn btn-xs btn-danger">Failed</button></form>
                        <form method="POST" action="{{ route('communicationhub.commercial.messages.retry', $row->id) }}" style="display:inline;">@csrf<button class="btn btn-xs btn-default">Retry</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty-state"><i class="fa fa-inbox"></i> No message delivery records yet.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-history"></i> Provider Delivery Events</h3><div class="ch-card-subtitle">Provider callbacks and detailed delivery events will appear here.</div></div></div>
    <div class="ch-card-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>ID</th><th>Message</th><th>Status</th><th>Provider</th><th>Response</th><th>Date</th></tr></thead>
            <tbody>@forelse($events as $event)<tr><td>{{ $event->id }}</td><td>{{ $event->message_id }}</td><td>{{ $event->status }}</td><td>{{ $event->provider_id ?? '-' }}</td><td>{{ $event->response_message ?? '-' }}</td><td>{{ $event->created_at ?? '-' }}</td></tr>@empty<tr><td colspan="6"><div class="empty-state">No provider events yet.</div></td></tr>@endforelse</tbody>
        </table>
    </div>
</div>
@endsection
