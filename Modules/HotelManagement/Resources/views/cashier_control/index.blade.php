@extends('layouts.app')
@section('title', 'Hotel Cashier Control')
@section('content')
<section class="content-header hm-page-header">
    <h1><i class="fa fa-calculator"></i> Cashier Control <small>Front desk shift, cash drawer, safe drop and variance audit</small></h1>
</section>
<section class="content hm-pos-scope">
    @include('hotelmanagement::partials.nav')
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>Please check:</strong> {{ $errors->first() }}</div>@endif

    <div class="row hm-kpi-row">
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Open Shifts</div><div class="hm-kpi-value">{{ $cashierControl['open_shift_count'] }}</div><div class="hm-kpi-sub">Active cashier drawers</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Expected Cash</div><div class="hm-kpi-value">{{ number_format($cashierControl['expected_cash'], 2) }}</div><div class="hm-kpi-sub">After safe drops</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Declared Cash</div><div class="hm-kpi-value">{{ number_format($cashierControl['declared_cash'], 2) }}</div><div class="hm-kpi-sub">Closed shifts</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Variance</div><div class="hm-kpi-value">{{ number_format($cashierControl['variance_amount'], 2) }}</div><div class="hm-kpi-sub">Shortage / excess review</div></div></div>
    </div>

    <div class="row">
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Open Cashier Shift</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.cashier-control.open-shift') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                    <div class="form-group"><label>Cashier User ID</label><input type="number" name="cashier_user_id" class="form-control" placeholder="Optional"></div>
                    <div class="form-group"><label>Counter</label><input name="counter_name" class="form-control" value="Front Desk"></div>
                    <div class="form-group"><label>Opening Float</label><input type="number" step="0.01" name="opening_float" class="form-control" value="0"></div>
                    <div class="form-group"><label>Opened At</label><input type="datetime-local" name="opened_at" class="form-control"></div>
                </div>
                <div class="form-group"><label>Remarks</label><textarea name="remarks" rows="2" class="form-control"></textarea></div>
                <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-play"></i> Open Shift</button></div>
            </form>
        </div></div></div>

        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Safe Drop</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.cashier-control.safe-drop') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                    <div class="form-group"><label>Open Shift</label><select name="shift_id" class="form-control" required><option value="">Select</option>@foreach($cashierControl['shifts'] as $s)@if($s->status === 'open')<option value="{{ $s->id }}">{{ $s->shift_no }} - {{ $s->counter_name }}</option>@endif @endforeach</select></div>
                    <div class="form-group"><label>Drop Amount</label><input type="number" step="0.01" name="drop_amount" class="form-control" required></div>
                    <div class="form-group"><label>Safe Bag No</label><input name="safe_bag_no" class="form-control"></div>
                    <div class="form-group"><label>Received By</label><input name="received_by" class="form-control"></div>
                    <div class="form-group"><label>Drop Date/Time</label><input type="datetime-local" name="drop_datetime" class="form-control"></div>
                </div>
                <div class="form-group"><label>Remarks</label><textarea name="remarks" rows="2" class="form-control"></textarea></div>
                <div class="text-right"><button class="btn hm-btn-print"><i class="fa fa-lock"></i> Save Safe Drop</button></div>
            </form>
        </div></div></div>
    </div>

    <div class="row">
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Cash Count Line</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.cashier-control.cash-count') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(3,minmax(120px,1fr))">
                    <div class="form-group"><label>Shift</label><select name="shift_id" class="form-control" required><option value="">Select</option>@foreach($cashierControl['shifts'] as $s)@if($s->status === 'open')<option value="{{ $s->id }}">{{ $s->shift_no }}</option>@endif @endforeach</select></div>
                    <div class="form-group"><label>Denomination</label><input type="number" step="0.01" name="denomination" class="form-control" required></div>
                    <div class="form-group"><label>Quantity</label><input type="number" name="quantity" class="form-control" required></div>
                </div>
                <div class="text-right"><button class="btn hm-btn-excel"><i class="fa fa-plus"></i> Add Count Line</button></div>
            </form>
        </div></div></div>
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Close Cashier Shift</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.cashier-control.close-shift') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                    <div class="form-group"><label>Open Shift</label><select name="shift_id" class="form-control" required><option value="">Select</option>@foreach($cashierControl['shifts'] as $s)@if($s->status === 'open')<option value="{{ $s->id }}">{{ $s->shift_no }} - Expected {{ number_format($s->expected_cash,2) }}</option>@endif @endforeach</select></div>
                    <div class="form-group"><label>Declared Cash</label><input type="number" step="0.01" name="declared_cash" class="form-control" required></div>
                    <div class="form-group"><label>Closed At</label><input type="datetime-local" name="closed_at" class="form-control"></div>
                    <div class="form-group"><label>Variance Reason</label><input name="variance_reason" class="form-control"></div>
                </div>
                <div class="form-group"><label>Remarks</label><textarea name="remarks" rows="2" class="form-control"></textarea></div>
                <div class="text-right"><button class="btn hm-btn-pdf"><i class="fa fa-stop"></i> Close Shift</button></div>
            </form>
        </div></div></div>
    </div>

    <div class="box hm-card"><div class="box-header with-border"><div class="hm-toolbar"><input class="form-control hm-search-input" style="max-width:260px" placeholder="Search shifts..."><button class="btn hm-btn-csv">CSV</button><button class="btn hm-btn-excel">Excel</button><button class="btn hm-btn-pdf">PDF</button><button class="btn hm-btn-print">Print</button><button class="btn hm-btn-col">Column Visibility</button></div><h3 class="box-title">Cashier Shifts</h3></div><div class="box-body table-responsive">
        <table class="table table-bordered table-striped hm-table"><thead><tr><th>Shift No</th><th>Counter</th><th>Cashier</th><th>Opened</th><th>Closed</th><th class="text-right">Float</th><th class="text-right">Expected</th><th class="text-right">Declared</th><th class="text-right">Variance</th><th>Status</th></tr></thead><tbody>
        @forelse($cashierControl['shifts'] as $s)<tr><td>{{ $s->shift_no }}</td><td>{{ $s->counter_name }}</td><td>{{ $s->cashier_user_id }}</td><td>{{ $s->opened_at }}</td><td>{{ $s->closed_at }}</td><td class="text-right">{{ number_format($s->opening_float,2) }}</td><td class="text-right">{{ number_format($s->expected_cash,2) }}</td><td class="text-right">{{ number_format($s->declared_cash,2) }}</td><td class="text-right">{{ number_format($s->variance_amount,2) }}</td><td><span class="hm-badge {{ $s->status }}">{{ ucfirst($s->status) }}</span></td></tr>@empty<tr><td colspan="10" class="text-center text-muted">No cashier shifts yet.</td></tr>@endforelse
        </tbody></table>
    </div></div>

    <div class="row">
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Safe Drops</h3></div><div class="box-body table-responsive"><table class="table table-bordered hm-table"><thead><tr><th>Drop No</th><th>Shift</th><th>Date/Time</th><th>Bag</th><th class="text-right">Amount</th></tr></thead><tbody>@forelse($cashierControl['drops'] as $d)<tr><td>{{ $d->drop_no }}</td><td>{{ $d->shift_id }}</td><td>{{ $d->drop_datetime }}</td><td>{{ $d->safe_bag_no }}</td><td class="text-right">{{ number_format($d->drop_amount,2) }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">No safe drops yet.</td></tr>@endforelse</tbody></table></div></div></div>
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Variance Review</h3></div><div class="box-body table-responsive"><table class="table table-bordered hm-table"><thead><tr><th>No</th><th>Shift</th><th>Type</th><th class="text-right">Amount</th><th>Status</th><th>Review</th></tr></thead><tbody>@forelse($cashierControl['variances'] as $v)<tr><td>{{ $v->variance_no }}</td><td>{{ $v->shift_id }}</td><td>{{ ucfirst($v->variance_type) }}</td><td class="text-right">{{ number_format($v->variance_amount,2) }}</td><td><span class="hm-badge {{ $v->status }}">{{ str_replace('_',' ', ucfirst($v->status)) }}</span></td><td><form method="POST" action="{{ route('hotel-management.cashier-control.review-variance', $v->id) }}" class="form-inline">@csrf <select name="status" class="form-control input-sm"><option value="reviewed">Reviewed</option><option value="approved_writeoff">Approved Write-off</option><option value="recovered">Recovered</option></select> <input name="review_note" class="form-control input-sm" placeholder="Note"> <button class="btn btn-xs hm-btn-edit">Save</button></form></td></tr>@empty<tr><td colspan="6" class="text-center text-muted">No variances pending.</td></tr>@endforelse</tbody></table></div></div></div>
    </div>
</section>
@endsection
