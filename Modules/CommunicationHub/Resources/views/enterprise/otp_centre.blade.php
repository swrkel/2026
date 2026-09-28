@extends('communicationhub::layout')

@section('communicationhub_title', 'OTP Centre')
@section('communicationhub_content')
<div class="row">@foreach($otpStats as $label=>$count)<div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-key"></i></span><div class="info-box-content"><span class="info-box-text">{{ ucwords(str_replace('_',' ', $label)) }}</span><span class="info-box-number">{{ number_format($count) }}</span></div></div></div>@endforeach</div>
<div class="box box-primary"><div class="box-body"><p>OTP Centre supports login, registration, password reset, transaction approval and digital signature use-cases. Delivery channels can be SMS, Email, WhatsApp or Push based on CommunicationHub settings.</p></div></div>
@endsection
