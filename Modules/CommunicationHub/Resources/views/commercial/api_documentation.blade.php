@extends('communicationhub::layout')
@section('communicationhub_title', 'API Documentation')
@section('communicationhub_content')
<div class="box box-primary"><div class="box-header"><h3 class="box-title">Versioned API v1</h3></div><div class="box-body">
<p>External clients can integrate with Communication Hub using versioned API endpoints.</p>
<pre>POST /api/v1/communication-hub/sms/send
GET  /api/v1/communication-hub/balance
GET  /api/v1/communication-hub/message-status/{id}</pre>
<p>Use API Tokens from this module. Keep token permissions restricted per client.</p>
</div></div>
@endsection
