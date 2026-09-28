@extends('banking-core-teller::layouts.app')
@section('module-content')
<div class="row">
@foreach($summary as $label => $value)
    <div class="col-md-4"><div class="card mb-3"><div class="card-body"><h6>{{ ucwords(str_replace('_',' ', $label)) }}</h6><h3>{{ $value }}</h3></div></div></div>
@endforeach
</div>
<div class="card"><div class="card-body">
    <a class="btn btn-outline-primary" href="{{ route('banking.teller.drawers.index') }}">Drawers</a>
    <a class="btn btn-outline-primary" href="{{ route('banking.teller.slips.index') }}">Teller Slips</a>
    <a class="btn btn-outline-primary" href="{{ route('banking.teller.vault-requests.index') }}">Vault Requests</a>
    <a class="btn btn-outline-primary" href="{{ route('banking.teller.supervisor.approvals') }}">Supervisor Queue</a>
    <a class="btn btn-outline-primary" href="{{ route('banking.teller.reports.index') }}">Reports</a>
</div></div>
@endsection
