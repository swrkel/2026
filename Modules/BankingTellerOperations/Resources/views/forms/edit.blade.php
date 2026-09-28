@extends('banking-core-teller::layouts.app')
@section('module-content')
<div class="card"><div class="card-body">
    <h5>{{ $title ?? 'Form' }}</h5>
    <p class="text-muted">Standalone placeholder form. Connect fields according to your ERP UI standard before enabling production entry.</p>
    <a href="javascript:history.back()" class="btn btn-secondary">Back</a>
</div></div>
@endsection
