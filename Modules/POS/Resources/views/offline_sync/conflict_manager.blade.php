@extends('pos::layouts.app', ['title' => 'POS Offline Sync Conflict Manager'])

@section('pos_content')
<div class="ch-kpi-grid ch-standard-grid">
    <div class="ch-kpi warning"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-triangle-exclamation"></i></div><div class="label-text">Open Conflicts</div></div><div class="value">{{ $summary['open'] ?? 0 }}</div><div class="hint">Need manager review</div><div class="spark"></div></div>
    <div class="ch-kpi success"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-check-double"></i></div><div class="label-text">Resolved Today</div></div><div class="value">{{ $summary['resolvedToday'] ?? 0 }}</div><div class="hint">Manager decisions</div><div class="spark"></div></div>
    <div class="ch-kpi danger"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-circle-xmark"></i></div><div class="label-text">Failed Queue</div></div><div class="value">{{ $summary['failed'] ?? 0 }}</div><div class="hint">Retry or resolve</div><div class="spark"></div></div>
    <div class="ch-kpi purple"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-rotate"></i></div><div class="label-text">Retryable</div></div><div class="value">{{ $summary['retryable'] ?? 0 }}</div><div class="hint">Failed/conflict items</div><div class="spark"></div></div>
</div>

<div class="ch-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-shield-halved text-primary"></i> Offline Sync Conflict Manager</h3>
            <div class="ch-card-subtitle">Resolve stock, credit, duplicate invoice, payment, return and register/shift synchronization risks before they affect ledgers or stock.</div>
        </div>
        <div>
            <button type="button" class="btn btn-success" id="pos-conflict-retry-all"><i class="fa fa-rotate"></i> Retry All Safe Items</button>
            <a href="{{ route('pos.offline_sync.index') }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Offline Sync</a>
        </div>
    </div>
    <div class="ch-card-body table-responsive">
        <table class="table table-bordered pos-standard-table" id="pos-conflict-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Invoice</th>
                    <th>Type</th>
                    <th>Message</th>
                    <th>Queue</th>
                    <th>Attempts</th>
                    <th>Status</th>
                    <th style="width:280px;">Manager Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr data-conflict-id="{{ $item->id }}">
                        <td>{{ $item->id }}</td>
                        <td><strong>{{ $item->offline_invoice_no ?: '-' }}</strong><br><small>{{ $item->device_uuid }}</small></td>
                        <td><span class="label label-warning">{{ str_replace('_', ' ', $item->conflict_type) }}</span><br><small>{{ $item->transaction_type ?: '-' }}</small></td>
                        <td>{{ $item->conflict_message }} @if($item->last_error)<br><small class="text-danger">{{ $item->last_error }}</small>@endif</td>
                        <td>{{ $item->queue_status ?: '-' }}</td>
                        <td>{{ $item->attempt_count ?: 0 }}</td>
                        <td>{!! $item->resolution_status === 'open' ? '<span class="label label-danger">Open</span>' : '<span class="label label-success">Resolved</span>' !!}</td>
                        <td>
                            @if($item->resolution_status === 'open')
                                <select class="form-control input-sm pos-conflict-action">
                                    @foreach($resolutionOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <textarea class="form-control input-sm pos-conflict-note" rows="2" placeholder="Manager note" style="margin-top:6px;"></textarea>
                                <div style="margin-top:6px;" class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-primary pos-conflict-resolve"><i class="fa fa-check"></i> Resolve</button>
                                    <button type="button" class="btn btn-info pos-conflict-retry"><i class="fa fa-rotate"></i> Retry</button>
                                </div>
                            @else
                                <small>{{ $item->resolution_note }}</small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">No sync conflicts currently need review.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-list-check text-success"></i> Resolution Rules</h3><div class="ch-card-subtitle">Use conservative actions first. Sensitive sales, credit and stock changes must remain auditable.</div></div></div>
    <div class="ch-card-body">
        <div class="row">
            <div class="col-md-4"><div class="alert alert-warning"><strong>Stock conflict:</strong> approve negative stock only where the business permits it, otherwise cancel or create manual adjustment.</div></div>
            <div class="col-md-4"><div class="alert alert-info"><strong>Credit conflict:</strong> convert to cash/card or manager approve credit over limit after checking Customers module statement.</div></div>
            <div class="col-md-4"><div class="alert alert-danger"><strong>Duplicate invoice:</strong> never post twice. Accept server version or cancel local duplicate.</div></div>
        </div>
    </div>
</div>

<script>
(function(){
    function postJson(url, data){
        return fetch(url, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body:JSON.stringify(data || {})}).then(function(r){ return r.json().then(function(j){ if(!r.ok){ throw j; } return j; }); });
    }
    document.querySelectorAll('.pos-conflict-retry').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = btn.closest('tr').getAttribute('data-conflict-id');
            btn.disabled = true;
            postJson('/pos-module/offline-sync/conflicts/' + id + '/retry', {}).then(function(){ location.reload(); }).catch(function(e){ alert(e.message || 'Retry failed'); btn.disabled=false; });
        });
    });
    document.querySelectorAll('.pos-conflict-resolve').forEach(function(btn){
        btn.addEventListener('click', function(){
            var row = btn.closest('tr');
            var id = row.getAttribute('data-conflict-id');
            var action = row.querySelector('.pos-conflict-action').value;
            var note = row.querySelector('.pos-conflict-note').value;
            if(!note){ alert('Please enter a manager note.'); return; }
            btn.disabled = true;
            postJson('/pos-module/offline-sync/conflicts/' + id + '/resolve', {resolution_action: action, resolution_note: note}).then(function(){ location.reload(); }).catch(function(e){ alert(e.message || 'Resolve failed'); btn.disabled=false; });
        });
    });
    var retryAll = document.getElementById('pos-conflict-retry-all');
    if(retryAll){ retryAll.addEventListener('click', function(){
        if(!confirm('Retry all failed/conflict queue items?')) return;
        retryAll.disabled = true;
        postJson('/pos-module/offline-sync/conflicts/retry-all', {limit:50}).then(function(){ location.reload(); }).catch(function(e){ alert(e.message || 'Retry all failed'); retryAll.disabled=false; });
    });}
})();
</script>
@endsection
