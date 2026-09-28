@extends('layouts.app')
@section('title', 'Identity Sessions')
@section('content')
<section class="content-header"><h1>Identity Sessions</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body table-responsive">
<table class="table table-bordered table-striped">
<thead><tr><th>ID</th><th>Portal</th><th>IP</th><th>Status</th><th>Login</th><th>Expiry</th><th>Action</th></tr></thead>
<tbody>@foreach($sessions as $s)<tr><td>{{ $s->id }}</td><td>{{ $s->portal_type }}</td><td>{{ $s->ip_address }}</td><td>{{ $s->status }}</td><td>{{ $s->logged_in_at }}</td><td>{{ $s->expires_at }}</td><td>@if($s->status === 'active')<form method="POST" action="{{ route('identityaccess.sessions.revoke', $s->id) }}">@csrf<button class="btn btn-xs btn-danger">Revoke</button></form>@endif</td></tr>@endforeach</tbody>
</table>{{ $sessions->links() }}</div></div></section>
@endsection
