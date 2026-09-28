@extends('petropdnew::layouts.app')
@section('title','PONE Shift Source')
@section('page_title','PONE Shift Source Preview')
@section('pdnew_content')
@php($shift = $snapshot['shift'])
<div class="pdn-page-head">
    <div><h2>Shift {{ $shift['shift_number'] }}</h2><p>Immutable source preview from Pumper Dashboard-New.</p></div>
    <div class="pdn-actions">
        <a class="pdn-btn light" href="{{ route('petro-pd-new.sources.index') }}">Back</a>
        @if($sourceImport)
            @can('petro_pd_new.sources.refresh')
            <form method="post" action="{{ route('petro-pd-new.sources.verify',$sourceImport->id) }}">@csrf<button class="pdn-btn purple">Verify Source</button></form>
            @endcan
        @else
            @can('petro_pd_new.sources.import')
            <form method="post" action="{{ route('petro-pd-new.sources.import',$shift['id']) }}">@csrf<button class="pdn-btn purple">Import Snapshot</button></form>
            @endcan
        @endif
        @if((!$sourceImport || !$sourceImport->settlement_id) && !$settlementReference)
            @can('petro_pd_new.settlements.create')
            <form method="post" action="{{ route('petro-pd-new.sources.create-settlement',$shift['id']) }}" data-prevent-double-submit>@csrf
                <input type="hidden" name="shift_id" value="{{ $shift['id'] }}">
                <input type="hidden" name="settlement_date" value="{{ now()->toDateString() }}"><button class="pdn-btn success">Create Settlement</button>
            </form>
            @endcan
        @elseif($settlementReference)
            <span class="pdn-badge finalized">Already finalized: {{ $settlementReference->settlement_no }}</span>
        @endif
    </div>
</div>
<div class="pdn-card {{ $sourceImport && $sourceImport->import_status==='changed' ? 'pdn-source-bad' : 'pdn-source-ok' }}">
    <div class="pdn-summary">
        <div class="pdn-summary-item"><span>Operator</span><strong>{{ data_get($snapshot,'operator.display_name','Operator #'.$shift['operator_profile_id']) }}</strong></div>
        <div class="pdn-summary-item"><span>Opened</span><strong>{{ $shift['opened_at'] }}</strong></div>
        <div class="pdn-summary-item"><span>Closed</span><strong>{{ $shift['closed_at'] }}</strong></div>
        <div class="pdn-summary-item"><span>Source Status</span><strong>{{ $shift['status'] }}</strong></div>
        <div class="pdn-summary-item"><span>Meter Sales</span><strong>{{ number_format((float)$shift['meter_sales_total'],4) }}</strong></div>
        <div class="pdn-summary-item"><span>Other Sales</span><strong>{{ number_format((float)$shift['other_sales_total'],4) }}</strong></div>
        <div class="pdn-summary-item"><span>Payments</span><strong>{{ number_format((float)$shift['payments_total'],4) }}</strong></div>
        <div class="pdn-summary-item"><span>Source Hash</span><strong class="pdn-mono">{{ substr($snapshot['source_hash'],0,16) }}…</strong></div>
    </div>
</div>
<div class="pdn-grid two" style="margin-top:14px">
<div class="pdn-card"><h3>Pump Assignments</h3><div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Pump</th><th>Product</th><th class="amount">Opening</th><th class="amount">Closing</th><th class="amount">Testing</th><th class="amount">Sold</th><th class="amount">Amount</th></tr></thead><tbody>
@forelse($snapshot['assignments'] as $row)<tr><td>{{ $row['pump_id'] }}</td><td>{{ $row['product_id'] }}</td><td class="amount">{{ number_format((float)$row['opening_meter'],3) }}</td><td class="amount">{{ number_format((float)($row['closing_meter'] ?? $row['current_meter']),3) }}</td><td class="amount">{{ number_format((float)$row['testing_quantity'],3) }}</td><td class="amount">{{ number_format((float)$row['sold_quantity'],3) }}</td><td class="amount">{{ number_format((float)$row['amount'],4) }}</td></tr>@empty<tr><td colspan="7" class="pdn-empty">No assignments.</td></tr>@endforelse
</tbody></table></div></div>
<div class="pdn-card"><h3>Payments</h3><div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Payment No</th><th>Type</th><th>Reference</th><th>Status</th><th class="amount">Amount</th></tr></thead><tbody>
@forelse($snapshot['payments'] as $row)<tr><td>{{ $row['payment_number'] }}</td><td>{{ $row['payment_type'] }}</td><td>{{ $row['reference_no'] ?? $row['slip_no'] ?? $row['cheque_no'] ?? '—' }}</td><td>{{ $row['status'] }}</td><td class="amount">{{ number_format((float)$row['amount'],4) }}</td></tr>@empty<tr><td colspan="5" class="pdn-empty">No payments.</td></tr>@endforelse
</tbody></table></div></div>
</div>
<div class="pdn-card" style="margin-top:14px"><details class="pdn-details"><summary>Technical Source Contract</summary>
<p><strong>Contract:</strong> {{ $snapshot['contract'] }}</p><p class="pdn-mono">{{ $snapshot['source_hash'] }}</p>
<p>Assignments: {{ count($snapshot['assignments']) }}, meter readings: {{ count($snapshot['meter_readings']) }}, payments: {{ count($snapshot['payments']) }}, other sales: {{ count($snapshot['other_sales']) }}, unloads: {{ count($snapshot['unload_stocks']) }}, day entries: {{ count($snapshot['day_entries']) }}.</p>
</details></div>
@endsection
