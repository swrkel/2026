@extends('communicationhub::layout')
@section('communicationhub_title', 'Bulk WhatsApp')
@section('communicationhub_content')
<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-users"></i> Bulk WhatsApp</h3><div class="ch-card-subtitle">Send business-scoped WhatsApp campaigns to multiple recipients.</div></div><a href="{{ route('communicationhub.commercial.whatsapp_dashboard') }}" class="btn btn-default btn-sm">Dashboard</a></div>
    <form method="POST" action="{{ route('communicationhub.commercial.bulk_whatsapp.store') }}">@csrf
        <div class="ch-card-body"><div class="row">
            <div class="col-md-4"><label>Profile</label><select name="profile_id" class="form-control"><option value="">Default profile</option>@foreach($profiles as $profile)<option value="{{ $profile->id }}">{{ $profile->profile_name ?? '' }}</option>@endforeach</select></div>
            <div class="col-md-4"><label>Client / Wallet</label><select name="client_id" class="form-control"><option value="">Internal</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name ?? '' }}</option>@endforeach</select></div>
            <div class="col-md-4"><label>Message Type</label><select name="message_type" class="form-control"><option value="text">Text</option><option value="image">Image</option><option value="document">Document</option><option value="video">Video</option></select></div>
            <div class="col-md-12" style="margin-top:15px"><label>Recipient Numbers</label><textarea name="numbers" rows="5" class="form-control" placeholder="One number per line or comma separated" required></textarea></div>
            <div class="col-md-6" style="margin-top:15px"><label>Template</label><select class="form-control" onchange="if(this.value){document.getElementById('bulk_wa_body').value=this.value;}"><option value="">Select Template</option>@foreach($templates as $template)<option value="{{ $template->body ?? '' }}">{{ $template->template_name ?? '' }}</option>@endforeach</select></div>
            <div class="col-md-6" style="margin-top:15px"><label>Media URL</label><input name="media_url" class="form-control" placeholder="Optional"></div>
            <div class="col-md-12" style="margin-top:15px"><label>Message</label><textarea id="bulk_wa_body" name="message" rows="5" class="form-control" maxlength="4000" required></textarea></div>
        </div></div>
        <div class="ch-card-body" style="border-top:1px solid #edf2f7;background:#fbfdff"><button class="btn btn-primary">Queue Bulk WhatsApp</button></div>
    </form>
</div>
@endsection
