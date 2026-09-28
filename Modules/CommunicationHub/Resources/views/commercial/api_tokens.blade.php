@extends('communicationhub::layout')
@section('communicationhub_title', 'API Tokens')
@section('communicationhub_content')
<div class="box box-primary"><div class="box-header"><h3 class="box-title">Create API Token</h3></div><form method="POST" action="{{ route('communicationhub.commercial.api_tokens.store') }}">@csrf<div class="box-body"><div class="row">
<div class="col-md-4"><label>Token Name</label><input name="name" class="form-control" required></div>
<div class="col-md-4"><label>Client</label><select name="client_id" class="form-control"><option value="">General Token</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name ?? '' }}</option>@endforeach</select></div>
<div class="col-md-2"><label>Daily Limit</label><input name="daily_limit" type="number" class="form-control" value="0"></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Create Token</button></div></form></div>
<div class="box"><div class="box-header"><h3 class="box-title">Tokens</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><tr><th>ID</th><th>Name</th><th>Token</th><th>Daily Limit</th><th>Status</th></tr>@forelse($tokens as $row)<tr><td>{{ $row->id }}</td><td>{{ $row->name }}</td><td>{{ substr($row->token ?? '',0,20) }}...</td><td>{{ $row->daily_limit ?? '' }}</td><td>{{ !empty($row->is_active) ? 'Active' : 'Inactive' }}</td></tr>@empty<tr><td colspan="5">No API tokens yet</td></tr>@endforelse</table></div></div>
@endsection
