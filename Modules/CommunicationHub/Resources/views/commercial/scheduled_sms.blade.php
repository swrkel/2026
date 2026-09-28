@extends('communicationhub::layout')
@section('communicationhub_title', 'Scheduled SMS')
@section('communicationhub_content')
<div class="ch-card">
    <div class="ch-card-header">
        <div><h3 class="ch-card-title"><i class="fa fa-clock-o"></i> Schedule SMS</h3><div class="ch-card-subtitle">Create future-dated SMS messages for reminders, campaigns and customer alerts.</div></div>
        <span class="ch-badge-soft info">Tenant DB + Business Scoped</span>
    </div>
    <form method="POST" action="{{ route('communicationhub.commercial.scheduled_sms.store') }}">
        @csrf
        <div class="ch-card-body">
            <div class="row">
                <div class="col-md-3"><label>Client / Wallet</label><select name="client_id" class="form-control"><option value="">Internal / No wallet deduction</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name ?? '' }} - {{ $client->mobile ?? '' }}</option>@endforeach</select></div>
                <div class="col-md-3"><label>Recipient Number</label><input name="recipient" class="form-control" required></div>
                <div class="col-md-3"><label>Schedule Date & Time</label><input name="scheduled_at" type="datetime-local" class="form-control" required></div>
                <div class="col-md-12" style="margin-top:15px;"><label>Message</label><textarea name="message" rows="5" class="form-control" required></textarea></div>
            </div>
        </div>
        <div class="ch-card-body" style="border-top:1px solid #edf2f7;background:#fbfdff;"><button class="btn btn-primary"><i class="fa fa-calendar-plus-o"></i> Schedule SMS</button></div>
    </form>
</div>
<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-list"></i> Scheduled Messages</h3><div class="ch-card-subtitle">Messages waiting for their scheduled sending time.</div></div></div>
    <div class="ch-card-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>ID</th><th>Client</th><th>Recipient</th><th>Message</th><th>Scheduled At</th><th>Status</th><th>Created</th></tr></thead>
            <tbody>
            @forelse($scheduled as $row)
                <tr><td>{{ $row->id }}</td><td>{{ $row->client_id ?? '-' }}</td><td>{{ $row->recipient ?? '-' }}</td><td>{{ Str::limit($row->message ?? $row->body ?? '', 80) }}</td><td>{{ $row->scheduled_at ?? '-' }}</td><td><span class="ch-badge-soft warning">{{ $row->status ?? 'scheduled' }}</span></td><td>{{ $row->created_at ?? '-' }}</td></tr>
            @empty
                <tr><td colspan="7"><div class="empty-state"><i class="fa fa-calendar-o"></i> No scheduled SMS messages yet.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
