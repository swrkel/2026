@extends('audit::central.layout')
@section('audit-title','Run Central Multi-Tenant Audit')
@section('audit-content')
<form method="post" action="{{ url('/audit/run') }}" class="audit-card audit-central-run-form">
    @csrf
    @include('audit::central.partials.scope_filters',['showDate'=>false,'showSearch'=>false])

    <div class="audit-card-title" style="margin-top:18px">Audit Areas</div>
    <div class="audit-note">All areas are selected by default. When Business or Location filters are used, database-wide System rules run once per database to prevent duplicate global findings.</div>
    <div class="audit-check-grid audit-central-module-grid">
        @foreach($modules as $module)
        <label><input type="checkbox" name="modules[]" value="{{ $module }}" checked> <strong>{{ $module }}</strong></label>
        @endforeach
    </div>

    <div class="audit-alert central-safety">
        <strong>How scope works:</strong> blank Tenant = all central + tenant databases; selected Business = only those businesses; selected Location = only those locations. The engine opens one tenant database at a time and never copies operational ERP records into the central database.
    </div>
    <div class="audit-actions"><button class="audit-btn primary" type="submit" data-confirm="Run Audit for the selected central / tenant scope?">Run Selected Central Audit</button></div>
</form>
@endsection
