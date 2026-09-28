@extends('communicationhub::layout')
@section('communicationhub_title', 'Send SMS')
@section('communicationhub_content')
<div class="ch-card">
    <div class="ch-card-header">
        <div><h3 class="ch-card-title"><i class="fa fa-paper-plane"></i> Manual SMS</h3><div class="ch-card-subtitle">Queue a single SMS through the Communication Hub workflow. Wallet deduction is applied when a client is selected.</div></div>
        <a href="{{ route('communicationhub.commercial.delivery_reports') }}" class="btn btn-default btn-sm"><i class="fa fa-truck"></i> Delivery Report</a>
    </div>
    <form method="POST" action="{{ route('communicationhub.commercial.send_sms.store') }}">
        @csrf
        <div class="ch-card-body">
            <div class="row">
                <div class="col-md-4"><label>Client / Wallet</label><select name="client_id" class="form-control"><option value="">Internal / No wallet deduction</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name ?? '' }} - {{ $client->mobile ?? '' }}</option>@endforeach</select></div>
                <div class="col-md-4"><label>Recipient Number</label><input name="recipient" class="form-control" placeholder="947XXXXXXXX" required></div>
                <div class="col-md-4"><label>Template</label><select class="form-control" onchange="if(this.value){document.getElementById('sms_message_body').value=this.value;}"><option value="">Select Template</option>@foreach($templates as $template)<option value="{{ $template->body ?? $template->content ?? '' }}">{{ $template->name ?? '' }}</option>@endforeach</select></div>
                <div class="col-md-12" style="margin-top:15px;"><label>Message</label><textarea id="sms_message_body" name="message" rows="5" class="form-control" maxlength="1000" required></textarea><div class="ch-page-note" style="margin-top:6px;">Tip: Keep SMS under 160 characters where possible to reduce cost.</div></div>
            </div>
        </div>
        <div class="ch-card-body" style="border-top:1px solid #edf2f7;background:#fbfdff;"><button class="btn btn-primary"><i class="fa fa-paper-plane"></i> Queue SMS</button> <a href="{{ route('communicationhub.dashboard') }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
@endsection
