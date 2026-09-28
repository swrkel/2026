@extends('communicationhub::layout')
@section('communicationhub_title', 'Email Dashboard')
@section('communicationhub_content')
<div class="row">
    <div class="col-md-3"><div class="ch-stat"><div class="ch-stat-label">Total Email</div><div class="ch-stat-value">{{ number_format($total) }}</div></div></div>
    <div class="col-md-3"><div class="ch-stat"><div class="ch-stat-label">Pending</div><div class="ch-stat-value">{{ number_format($pending) }}</div></div></div>
    <div class="col-md-3"><div class="ch-stat"><div class="ch-stat-label">Sent</div><div class="ch-stat-value">{{ number_format($sent) }}</div></div></div>
    <div class="col-md-3"><div class="ch-stat"><div class="ch-stat-label">Failed</div><div class="ch-stat-value">{{ number_format($failed) }}</div></div></div>
</div>
<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-envelope"></i> Recent Email Queue</h3><div class="ch-card-subtitle">Business-wise email queue, delivery and retry visibility.</div></div><a href="{{ route('communicationhub.email.compose') }}" class="btn btn-primary btn-sm"><i class="fa fa-pencil"></i> Compose Email</a></div>
    <div class="ch-card-body table-responsive"><table class="table table-hover table-striped"><thead><tr><th>ID</th><th>Recipient</th><th>Subject</th><th>Status</th><th>Scheduled</th><th>Created</th></tr></thead><tbody>@forelse($messages as $m)<tr><td>{{ $m->id ?? '' }}</td><td>{{ $m->recipient ?? '' }}</td><td>{{ $m->subject ?? '' }}</td><td><span class="label label-info">{{ $m->status ?? 'pending' }}</span></td><td>{{ $m->scheduled_at ?? '' }}</td><td>{{ $m->created_at ?? '' }}</td></tr>@empty<tr><td colspan="6" class="text-center text-muted">No email messages found.</td></tr>@endforelse</tbody></table></div>
</div>
@endsection
