@extends('leadsnew::layouts.app')
@section('title', 'API Documentation')
@section('leadsnew_subtitle', 'Reference the module endpoints used for lead operations and integration checks.')
@section('leadsnew_content')
<div class="ln-card-grid">
    <div class="ln-report-card"><span class="report-icon"><i class="fa fa-heartbeat"></i></span><strong>Health Check</strong><span>Confirm that the module and tenant database requirements are available.</span><code>GET /leads-new/health-check</code></div>
    <div class="ln-report-card"><span class="report-icon"><i class="fa fa-users"></i></span><strong>Lead Register</strong><span>Open the main lead register for the current authenticated ERP tenant.</span><code>GET /leads-new/leads</code></div>
    <div class="ln-report-card"><span class="report-icon"><i class="fa fa-file-text-o"></i></span><strong>CSV Export</strong><span>Download lead data through the module-owned export endpoint.</span><code>GET /leads-new/export/csv</code></div>
    <div class="ln-report-card"><span class="report-icon"><i class="fa fa-calendar"></i></span><strong>Calendar Feed</strong><span>Retrieve the authenticated tenant's Leads-New calendar event feed.</span><code>GET /leads-new/calendar/feed</code></div>
</div>
<div class="ln-panel" style="margin-top:20px">
    <div class="ln-panel-header"><div><h3 class="ln-panel-title"><i class="fa fa-lock"></i> Integration Rules</h3><div class="ch-card-subtitle">The endpoints are part of the ERP web route stack.</div></div></div>
    <div class="ln-panel-body">
        <div class="ln-info-list">
            <div><i class="fa fa-shield"></i><span><strong>Authentication</strong><small>Use the active authenticated ERP session and its tenant middleware.</small></span></div>
            <div><i class="fa fa-database"></i><span><strong>Tenant Isolation</strong><small>Requests operate against the tenant database selected by the application.</small></span></div>
            <div><i class="fa fa-code"></i><span><strong>Response Handling</strong><small>Validate HTTP status and response content before importing data into another process.</small></span></div>
        </div>
    </div>
</div>
@endsection
