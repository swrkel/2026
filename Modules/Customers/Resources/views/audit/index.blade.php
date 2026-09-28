@extends('customers::layouts.action', ['title' => 'Customer Audit Trail'])
@section('customer_action_body')

<style>
.customer-sep-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:15px;flex-wrap:wrap}.customer-sep-card{border:1px solid #e5e7eb;border-radius:14px;background:#fff;box-shadow:0 8px 22px rgba(15,23,42,.06);padding:15px;margin-bottom:15px}.customer-sep-title{font-weight:700;color:#1f2937;margin:0 0 6px}.customer-sep-muted{color:#6b7280}.customer-sep-table{width:100%;min-width:760px}.customer-sep-table th{background:#f8fafc;color:#334155;font-weight:700;white-space:nowrap}.customer-sep-table td{vertical-align:middle!important}.customer-sep-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch}.customer-sep-badge{display:inline-block;padding:4px 9px;border-radius:999px;background:#eef2ff;color:#3730a3;font-weight:700;font-size:12px}.customer-sep-empty{padding:22px;text-align:center;color:#64748b;background:#f8fafc;border-radius:12px;border:1px dashed #cbd5e1}.customer-sep-danger{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;padding:10px;border-radius:10px;margin-bottom:12px}@media(max-width:768px){.customer-sep-table{min-width:680px}.customer-sep-card{padding:12px}}
</style>

@if(empty($tableStatus['customer_activities']))
    <div class="customer-sep-danger">Customer activities table is missing. Please run <strong>CUS_SEP_003_customer_audit_notes_attachments.sql</strong>.</div>
@endif
<div class="customer-sep-card">
    <div class="customer-sep-toolbar">
        <div>
            <h4 class="customer-sep-title">Audit Trail</h4>
            <p class="customer-sep-muted">Customer-owned audit log from the Customers module.</p>
        </div>
        <span class="customer-sep-badge">{{ $activities->count() }} Activities</span>
    </div>
    @if($activities->count())
        <div class="customer-sep-scroll">
            <table class="table table-bordered table-striped customer-sep-table">
                <thead><tr><th style="width:160px;">Date & Time</th><th style="width:180px;">Event</th><th>Description</th><th>Details</th></tr></thead>
                <tbody>
                @foreach($activities as $activity)
                    <tr>
                        <td>{{ optional($activity->created_at)->format('Y-m-d H:i') }}</td>
                        <td><span class="customer-sep-badge">{{ ucwords(str_replace('_', ' ', $activity->event)) }}</span></td>
                        <td>{{ $activity->description }}</td>
                        <td><small>{{ is_array($activity->properties) ? json_encode($activity->properties) : $activity->properties }}</small></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="customer-sep-empty">No audit activity found for this customer.</div>
    @endif
</div>
<div class="customer-sep-card">
    <h4 class="customer-sep-title">Customer Timeline</h4>
    @if($timeline->count())
        <div class="customer-sep-scroll">
            <table class="table table-bordered table-striped customer-sep-table">
                <thead><tr><th>Date & Time</th><th>Type</th><th>Title</th><th>Description</th></tr></thead>
                <tbody>
                    @foreach($timeline as $item)
                        <tr>
                            <td>{{ !empty($item['created_at']) ? $item['created_at']->format('Y-m-d H:i') : '' }}</td>
                            <td>{{ ucfirst($item['type']) }}</td>
                            <td>{{ $item['title'] }}</td>
                            <td>{{ $item['description'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="customer-sep-empty">No timeline entries found.</div>
    @endif
</div>
@endsection
