@extends('communicationhub::layout')
@section('communicationhub_title', 'Credit Refills')
@section('communicationhub_content')
<div class="box box-primary"><div class="box-header"><h3 class="box-title">Add Credit Refill</h3></div><form method="POST" action="{{ route('communicationhub.commercial.credit_refills.store') }}">@csrf<div class="box-body"><div class="row">
<div class="col-md-3"><label>Client</label><select name="client_id" class="form-control" required>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name ?? '' }} {{ !empty($client->business_name) ? '(' . $client->business_name . ')' : '' }}</option>@endforeach</select></div>
<div class="col-md-3"><label>Package</label><select name="package_id" class="form-control"><option value="">Manual Refill</option>@foreach($packages as $package)<option value="{{ $package->id }}">{{ $package->name ?? '' }} - {{ $package->credits ?? 0 }} credits</option>@endforeach</select></div>
<div class="col-md-2"><label>Credits</label><input name="credits" type="number" class="form-control" required></div>
<div class="col-md-2"><label>Amount</label><input name="amount" type="number" step="0.01" class="form-control" value="0"></div>
<div class="col-md-2"><label>Reference</label><input name="reference" class="form-control"></div>
<div class="col-md-12"><label>Note</label><textarea name="note" class="form-control"></textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save Refill</button></div></form></div>
<div class="box"><div class="box-header"><h3 class="box-title">Recent Refills / Transactions</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><tr><th>ID</th><th>Client</th><th>Type</th><th>Credits</th><th>Opening</th><th>Closing</th><th>Amount</th><th>Ref</th></tr>@forelse($transactions as $row)<tr><td>{{ $row->id }}</td><td>{{ $row->client_id }}</td><td>{{ $row->type }}</td><td>{{ $row->credits }}</td><td>{{ $row->opening_balance }}</td><td>{{ $row->closing_balance }}</td><td>{{ number_format((float)$row->amount,2) }}</td><td>{{ $row->reference }}</td></tr>@empty<tr><td colspan="8">No transactions yet</td></tr>@endforelse</table></div></div>
@endsection
