@extends('teaestate::layouts.app',['title'=>'Tea Finance Integration','heading'=>'Finance Integration'])
@section('tea_content')
<div class="tea-card"><div class="tea-card-h">Integration Status</div><div class="tea-card-b">
@if($financeAvailable)
<div class="alert alert-success">Finance ledger tables detected. Posted Tea events flow into Finance Account Books, Trial Balance, Profit &amp; Loss / Income Statement and Balance Sheet through the standard accounting ledger.</div>
@else
<div class="alert alert-warning">Finance/core accounting tables are not currently available. Tea operations continue normally and finance events stay pending for later reconciliation.</div>
@endif
<p class="tea-note">Tea Estate Management remains operationally standalone: no Finance PHP class is imported. The module-owned bridge writes balanced entries only to the common accounting ledger when Finance is available.</p>
</div></div>

<div class="tea-card"><div class="tea-card-h">Finance Account Mapping</div><div class="tea-card-b">
<form method="post" action="{{ route('teaestate.finance.mappings') }}">@csrf
<div class="tea-form">
<div><label>Location-specific Mapping</label><select name="location_id" id="tea_finance_location"><option value="0">All Locations / Default</option>@foreach($locationOptions as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
@foreach($purposes as $purpose=>$label)
<div><label>{{ $label }}</label><select name="mappings[{{ $purpose }}]" class="tea-finance-map" data-purpose="{{ $purpose }}"><option value="">Not mapped</option>@foreach($accountOptions as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
@endforeach
</div>
<div class="tea-actions"><button class="tea-btn">Save Mapping &amp; Reconcile</button></div>
</form>
<p class="tea-note">Configure the default mapping once, then add location-specific overrides only when that location must use different Finance accounts. Changing a mapping affects future and pending Tea postings; already-posted historical ledger rows are not silently rewritten.</p>
</div></div>

<div class="tea-card"><div class="tea-card-h">Posting Queue <form style="display:inline" method="post" action="{{ route('teaestate.finance.retry') }}">@csrf<button class="tea-btn secondary" style="padding:4px 8px">Retry Pending</button></form></div><div class="tea-card-b tea-scroll">
<table class="tea-table"><tr><th>ID</th><th>Date</th><th>Document</th><th>Event</th><th>Amount</th><th>Status</th><th>Finance Transaction</th><th>Error</th></tr>
@foreach($events as $e)<tr><td>{{ $e->id }}</td><td>{{ $e->operation_date }}</td><td>{{ $e->document_no }}</td><td>{{ $e->event_type }}</td><td>{{ number_format($e->amount,2) }}</td><td><span class="tea-status">{{ $e->status }}</span></td><td>{{ $e->core_transaction_id }}</td><td>{{ $e->error_message }}</td></tr>@endforeach
</table></div></div>

<script>
(function () {
    var matrix = @json($mappingMatrix ?? []);
    var location = document.getElementById('tea_finance_location');
    if (!location) return;
    function loadMapping() {
        var map = matrix[String(location.value)] || matrix[Number(location.value)] || {};
        document.querySelectorAll('.tea-finance-map').forEach(function (select) {
            var id = map[select.getAttribute('data-purpose')] || '';
            select.value = String(id || '');
        });
    }
    location.addEventListener('change', loadMapping);
    loadMapping();
})();
</script>
@endsection
