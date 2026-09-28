@extends('layouts.app')
@section('title', 'Banking Release Audit - permissions')
@section('content')
<div class="banking-ui-audit-page">
    <h1>Banking Release Audit - permissions</h1>
    <p>This page is part of BKG-UI-005 Banking Audit & Release Readiness RC5.</p>
    <div class="card"><div class="card-body"><pre>{{ json_encode(get_defined_vars(), JSON_PRETTY_PRINT) }}</pre></div></div>
</div>
@endsection
