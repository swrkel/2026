@extends('audit::central.layout')
@section('audit-title','Central Audit Rule Catalog')
@section('audit-content')
<div class="audit-rule-summary">
    <div class="audit-rule-stat"><span>Total Rules</span><strong>{{ number_format($rules->count()) }}</strong></div>
    <div class="audit-rule-stat"><span>Modules Covered</span><strong>{{ number_format($modules->count()) }}</strong></div>
    <div class="audit-rule-stat"><span>Mode</span><strong style="font-size:18px">Read-only Catalog</strong></div>
</div>
<div class="audit-card audit-rules-page">
    <div class="audit-rules-heading-row">
        <div><div class="audit-card-title">Audit Rules Available Across Tenants</div><div class="audit-note">Rule code and logic are shared by the standalone Audit module. Tenant-specific enable/disable settings remain inside each tenant database.</div></div>
        <div class="audit-rule-toolbar"><input class="audit-input audit-rule-search" placeholder="Search rules"><select class="audit-input audit-rule-module-filter"><option value="">All Modules</option>@foreach($modules as $m)<option value="{{ strtolower($m) }}">{{ $m }}</option>@endforeach</select></div>
    </div>
    <div class="audit-rule-table-wrap"><table class="audit-table audit-rule-table"><colgroup><col class="audit-rule-col-module"><col class="audit-rule-col-rule"><col class="audit-rule-col-description"><col class="audit-rule-col-severity"><col class="audit-rule-col-setting"></colgroup><thead><tr><th>Module</th><th>Rule</th><th>Description</th><th>Severity</th><th>Central Setting</th></tr></thead><tbody>
    @foreach($rules as $r)<tr class="audit-rule-row" data-module="{{ strtolower($r['module']) }}" data-search="{{ strtolower($r['module'].' '.$r['code'].' '.$r['title'].' '.$r['description']) }}"><td data-label="Module"><span class="audit-module-chip">{{ $r['module'] }}</span></td><td data-label="Rule"><div class="audit-rule-code">{{ $r['code'] }}</div><div class="audit-rule-name">{{ $r['title'] }}</div></td><td data-label="Description"><div class="audit-rule-description">{{ $r['description'] }}</div></td><td data-label="Severity"><span class="audit-badge {{ $r['severity'] }}">{{ ucfirst($r['severity']) }}</span></td><td data-label="Central Setting"><span class="audit-note">Managed per tenant/business</span></td></tr>@endforeach
    <tr class="audit-rule-empty" style="display:none"><td colspan="5">No rules match the search.</td></tr>
    </tbody></table></div>
</div>
@endsection
