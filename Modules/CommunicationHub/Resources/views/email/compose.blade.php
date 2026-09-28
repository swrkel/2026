@extends('communicationhub::layout')
@section('communicationhub_title', 'Compose Email')
@section('communicationhub_content')
<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-pencil"></i> Compose Email</h3><div class="ch-card-subtitle">Queue a single business-scoped email using SMTP/Mailgun provider settings.</div></div><a href="{{ route('communicationhub.email.queue') }}" class="btn btn-default btn-sm"><i class="fa fa-list"></i> Email Queue</a></div>
    <form method="POST" action="{{ route('communicationhub.email.send') }}">@csrf
        <div class="ch-card-body"><div class="row">
            <div class="col-md-4"><label>Client</label><select class="form-control" name="client_id"><option value="">Internal / no client</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name ?? $client->business_name ?? '' }} - {{ $client->email ?? '' }}</option>@endforeach</select></div>
            <div class="col-md-4"><label>Recipient Email</label><input type="email" name="recipient" class="form-control" required placeholder="customer@example.com"></div>
            <div class="col-md-4"><label>Schedule At</label><input type="datetime-local" name="scheduled_at" class="form-control"></div>
            <div class="col-md-8" style="margin-top:15px;"><label>Subject</label><input name="subject" class="form-control" required maxlength="191"></div>
            <div class="col-md-4" style="margin-top:15px;"><label>Template</label><select class="form-control" onchange="if(this.value){var p=JSON.parse(this.value);document.querySelector('input[name=subject]').value=p.subject||'';document.getElementById('email_body').value=p.body||'';}"><option value="">Select Template</option>@foreach($templates as $template)@php $channel=$template->channel ?? null; @endphp @if(!$channel || $channel=='email')<option value='@json(["subject"=>$template->subject ?? $template->name ?? "", "body"=>$template->body ?? $template->content ?? ""])'>{{ $template->name ?? '' }}</option>@endif @endforeach</select></div>
            <div class="col-md-12" style="margin-top:15px;"><label>Body</label><textarea id="email_body" name="body" rows="9" class="form-control" required></textarea></div>
        </div></div>
        <div class="ch-card-body" style="border-top:1px solid #edf2f7;background:#fbfdff;"><button class="btn btn-primary"><i class="fa fa-envelope"></i> Queue Email</button> <a href="{{ route('communicationhub.email.dashboard') }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
@endsection
