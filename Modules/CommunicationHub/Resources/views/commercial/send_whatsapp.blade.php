@extends('communicationhub::layout')
@section('communicationhub_title', 'Send WhatsApp')
@section('communicationhub_content')
<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-whatsapp"></i> Send WhatsApp Message</h3><div class="ch-card-subtitle">Queue an individual WhatsApp message through the Communication Hub provider engine.</div></div><a href="{{ route('communicationhub.commercial.whatsapp_dashboard') }}" class="btn btn-default btn-sm">Dashboard</a></div>
    <form method="POST" action="{{ route('communicationhub.commercial.send_whatsapp.store') }}">@csrf
        <div class="ch-card-body"><div class="row">
            <div class="col-md-3"><label>Business Profile</label><select name="profile_id" class="form-control"><option value="">Default profile</option>@foreach($profiles as $profile)<option value="{{ $profile->id }}">{{ $profile->profile_name ?? '' }} - {{ $profile->phone_number ?? '' }}</option>@endforeach</select></div>
            <div class="col-md-3"><label>Client / Wallet</label><select name="client_id" class="form-control"><option value="">Internal</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name ?? '' }} - {{ $client->mobile ?? '' }}</option>@endforeach</select></div>
            <div class="col-md-3"><label>Recipient</label><input name="recipient" class="form-control" placeholder="947XXXXXXXX" required></div>
            <div class="col-md-3"><label>Message Type</label><select name="message_type" class="form-control"><option value="text">Text</option><option value="image">Image</option><option value="document">Document / PDF</option><option value="video">Video</option><option value="audio">Audio</option><option value="location">Location</option></select></div>
            <div class="col-md-6" style="margin-top:15px"><label>Template</label><select class="form-control" onchange="if(this.value){document.getElementById('wa_body').value=this.value;}"><option value="">Select Template</option>@foreach($templates as $template)<option value="{{ $template->body ?? '' }}">{{ $template->template_name ?? '' }} ({{ $template->language_code ?? 'en' }})</option>@endforeach</select></div>
            <div class="col-md-6" style="margin-top:15px"><label>Media URL / Document URL</label><input name="media_url" class="form-control" placeholder="https://... optional"></div>
            <div class="col-md-12" style="margin-top:15px"><label>Message</label><textarea id="wa_body" name="message" rows="5" class="form-control" maxlength="4000" required></textarea></div>
            <div class="col-md-12" style="margin-top:15px"><label>Caption</label><input name="caption" class="form-control" placeholder="Optional media caption"></div>
        </div></div>
        <div class="ch-card-body" style="border-top:1px solid #edf2f7;background:#fbfdff"><button class="btn btn-primary"><i class="fa fa-paper-plane"></i> Queue WhatsApp</button></div>
    </form>
</div>
@endsection
