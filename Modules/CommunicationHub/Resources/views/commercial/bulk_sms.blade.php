@extends('communicationhub::layout')
@section('communicationhub_title', 'Bulk SMS')
@section('communicationhub_content')
<div class="ch-card">
    <div class="ch-card-header">
        <div><h3 class="ch-card-title"><i class="fa fa-users"></i> Bulk SMS Queue</h3><div class="ch-card-subtitle">Paste numbers line-by-line or comma separated. Duplicate numbers are automatically removed.</div></div>
        <span class="ch-badge-soft info">Bulk Operation</span>
    </div>
    <form method="POST" action="{{ route('communicationhub.commercial.bulk_sms.store') }}">
        @csrf
        <div class="ch-card-body">
            <div class="row">
                <div class="col-md-4"><label>Client / Wallet</label><select name="client_id" class="form-control"><option value="">Internal / No wallet deduction</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name ?? '' }} - {{ $client->mobile ?? '' }}</option>@endforeach</select></div>
                <div class="col-md-12" style="margin-top:15px;"><label>Recipient Numbers</label><textarea name="numbers" rows="7" class="form-control" placeholder="947XXXXXXXX&#10;947YYYYYYYY&#10;947ZZZZZZZZ" required></textarea></div>
                <div class="col-md-12" style="margin-top:15px;"><label>Message</label><textarea name="message" rows="5" class="form-control" maxlength="1000" required></textarea></div>
            </div>
        </div>
        <div class="ch-card-body" style="border-top:1px solid #edf2f7;background:#fbfdff;"><button class="btn btn-primary"><i class="fa fa-list"></i> Queue Bulk SMS</button> <a href="{{ route('communicationhub.commercial.delivery_reports') }}" class="btn btn-default">View Delivery Report</a></div>
    </form>
</div>
@endsection
