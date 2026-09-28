@extends('audit::central.layout')
@section('audit-title','Central Audit Schedule Overview')
@section('audit-content')
<form method="get" class="audit-card audit-central-schedule-filter">
    <div class="audit-card-title">Tenant / Data Source</div>
    <div class="audit-note" style="margin-bottom:8px">Leave blank for all sources. Central view is read-only; create or change a schedule inside the relevant tenant Audit page.</div>
    <select class="audit-input audit-multi-select" name="source_keys[]" multiple size="7" style="width:100%">
        @foreach($source_options as $source)<option value="{{ $source['key'] }}" {{ in_array($source['key'],(array)request('source_keys',[]),true)?'selected':'' }}>{{ $source['label'] }}</option>@endforeach
    </select>
    <div class="audit-actions"><button class="audit-btn primary">Apply</button></div>
</form>
@if(!empty($errors))@foreach($errors as $error)<div class="audit-alert danger"><strong>{{ $error['source_label'] }}</strong>: {{ $error['message'] }}</div>@endforeach@endif
<div class="audit-card audit-table-wrap"><table class="audit-table"><thead><tr><th>Tenant / Source</th><th>Business</th><th>Schedule</th><th>Frequency</th><th>Enabled</th><th>Last Run</th><th>Created</th></tr></thead><tbody>
@forelse($rows as $r)<tr><td>{{ $r['source_label'] }}</td><td>{{ $r['business_name'] }}</td><td>{{ $r['name'] }}</td><td>{{ ucfirst($r['frequency']) }}</td><td><span class="audit-badge {{ $r['is_enabled']?'completed':'warning' }}">{{ $r['is_enabled']?'Enabled':'Disabled' }}</span></td><td>{{ $r['last_run_at'] }}</td><td>{{ $r['created_at'] }}</td></tr>@empty<tr><td colspan="7" class="audit-empty-cell">No schedules found in the selected sources.</td></tr>@endforelse
</tbody></table></div>
@endsection
