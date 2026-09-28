@extends('communicationhub::layout')
@section('communicationhub_title', 'Bulk Email')
@section('communicationhub_content')
<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-users"></i> Bulk Email Campaign</h3><div class="ch-card-subtitle">Create an email campaign and queue all valid recipients safely under the current business.</div></div><a href="{{ route('communicationhub.email.dashboard') }}" class="btn btn-default btn-sm"><i class="fa fa-dashboard"></i> Dashboard</a></div>
    <form method="POST" action="{{ route('communicationhub.email.bulk.store') }}">@csrf
        <div class="ch-card-body"><div class="row">
            <div class="col-md-6"><label>Campaign Name</label><input name="campaign_name" class="form-control" required></div>
            <div class="col-md-3"><label>Schedule At</label><input type="datetime-local" name="scheduled_at" class="form-control"></div>
            <div class="col-md-12" style="margin-top:15px;"><label>Recipients</label><textarea name="recipients" rows="5" class="form-control" required placeholder="one@email.com&#10;two@email.com"></textarea></div>
            <div class="col-md-12" style="margin-top:15px;"><label>Subject</label><input name="subject" class="form-control" required></div>
            <div class="col-md-12" style="margin-top:15px;"><label>Body</label><textarea name="body" rows="9" class="form-control" required></textarea></div>
        </div></div>
        <div class="ch-card-body" style="border-top:1px solid #edf2f7;background:#fbfdff;"><button class="btn btn-primary"><i class="fa fa-plus-circle"></i> Queue Campaign</button></div>
    </form>
</div>
<div class="ch-card"><div class="ch-card-header"><div><h3 class="ch-card-title">Recent Campaigns</h3></div></div><div class="ch-card-body table-responsive"><table class="table table-striped"><thead><tr><th>ID</th><th>Name</th><th>Subject</th><th>Recipients</th><th>Status</th><th>Scheduled</th></tr></thead><tbody>@forelse($campaigns as $c)<tr><td>{{ $c->id ?? '' }}</td><td>{{ $c->name ?? '' }}</td><td>{{ $c->subject ?? '' }}</td><td>{{ $c->total_recipients ?? 0 }}</td><td>{{ $c->status ?? '' }}</td><td>{{ $c->scheduled_at ?? '' }}</td></tr>@empty<tr><td colspan="6" class="text-center text-muted">No email campaigns yet.</td></tr>@endforelse</tbody></table></div></div>
@endsection
