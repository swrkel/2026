@extends('layouts.app')
@section('title', $title ?? 'Communication Hub')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'Communication Hub' }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

@php($title='OTP Center')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Generate OTP</h3></div><form method="POST" action="{{ route('communicationhub.otp.generate') }}">@csrf<div class="box-body"><div class="row"><div class="col-md-4"><label>Recipient</label><input class="form-control" name="recipient" required></div><div class="col-md-3"><label>Channel</label><select class="form-control" name="channel"><option value="sms">SMS</option><option value="email">Email</option><option value="whatsapp">WhatsApp</option></select></div><div class="col-md-3"><label>Purpose</label><input class="form-control" name="purpose" value="login"></div><div class="col-md-2"><label>&nbsp;</label><button class="btn btn-primary btn-block">Generate</button></div></div></div></form></div>
<div class="box"><div class="box-body table-responsive"><table class="table table-bordered"><thead><tr><th>Recipient</th><th>Channel</th><th>Purpose</th><th>Status</th><th>Expires</th></tr></thead><tbody>@foreach($otps as $otp)<tr><td>{{ $otp->recipient }}</td><td>{{ $otp->channel }}</td><td>{{ $otp->purpose }}</td><td>{{ $otp->status }}</td><td>{{ $otp->expires_at }}</td></tr>@endforeach</tbody></table>{{ $otps->links() }}</div></div>
</section>
@endsection
