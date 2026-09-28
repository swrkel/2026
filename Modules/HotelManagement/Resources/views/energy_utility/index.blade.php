@extends('layouts.app')
@section('title', 'Hotel Energy & Utilities')
@section('content')
<section class="content-header hm-page-header"><h1><i class="fa fa-bolt"></i> Energy & Utilities <small>Meter readings, consumption and cost allocation</small></h1></section>
<section class="content hm-pos-scope">
@include('hotelmanagement::partials.nav')
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><strong>Please check:</strong> {{ $errors->first() }}</div>@endif
<div class="row hm-kpi-row">
 <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Active Meters</div><div class="hm-kpi-value">{{ $energyUtility['active_meter_count'] }}</div><div class="hm-kpi-sub">Electricity / water / gas</div></div></div>
 <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Consumption</div><div class="hm-kpi-value">{{ number_format($energyUtility['totalUnits'],2) }}</div><div class="hm-kpi-sub">Total posted units</div></div></div>
 <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Utility Cost</div><div class="hm-kpi-value">{{ number_format($energyUtility['totalCost'],2) }}</div><div class="hm-kpi-sub">Reading cost value</div></div></div>
 <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Open Allocations</div><div class="hm-kpi-value">{{ $energyUtility['openAlloc'] }}</div><div class="hm-kpi-sub">Awaiting posting</div></div></div>
</div>
<div class="row">
 <div class="col-md-4"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Utility Meter</h3></div><div class="box-body"><form method="POST" action="{{ route('hotel-management.energy-utility.meter') }}">@csrf
  <div class="form-group"><label>Meter No</label><input name="meter_no" class="form-control" placeholder="Auto if blank"></div>
  <div class="form-group"><label>Meter Name</label><input name="meter_name" class="form-control" required></div>
  <div class="form-group"><label>Utility Type</label><select name="utility_type" class="form-control"><option value="electricity">Electricity</option><option value="water">Water</option><option value="gas">Gas</option><option value="solar">Solar</option><option value="other">Other</option></select></div>
  <div class="form-group"><label>Department</label><input name="department" class="form-control" placeholder="Rooms / Kitchen / Laundry"></div>
  <div class="form-group"><label>Unit</label><input name="unit_name" class="form-control" value="Unit"></div>
  <div class="form-group"><label>Rate Per Unit</label><input type="number" step="0.0001" name="rate_per_unit" class="form-control" value="0"></div>
  <label class="checkbox-inline"><input type="checkbox" name="is_active" value="1" checked> Active</label>
  <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-save"></i> Save Meter</button></div>
 </form></div></div></div>
 <div class="col-md-4"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Meter Reading</h3></div><div class="box-body"><form method="POST" action="{{ route('hotel-management.energy-utility.reading') }}">@csrf
  <div class="form-group"><label>Meter</label><select name="meter_id" class="form-control" required>@foreach($energyUtility['meters'] as $m)<option value="{{ $m->id }}">{{ $m->meter_no }} - {{ $m->meter_name }}</option>@endforeach</select></div>
  <div class="form-group"><label>Reading Date</label><input type="date" name="reading_date" class="form-control"></div>
  <div class="form-group"><label>Previous Reading</label><input type="number" step="0.0001" name="previous_reading" class="form-control" required></div>
  <div class="form-group"><label>Current Reading</label><input type="number" step="0.0001" name="current_reading" class="form-control" required></div>
  <div class="form-group"><label>Rate Per Unit</label><input type="number" step="0.0001" name="rate_per_unit" class="form-control" placeholder="Use meter rate if blank"></div>
  <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="draft">Draft</option><option value="approved">Approved</option><option value="posted">Posted</option></select></div>
  <div class="text-right"><button class="btn hm-btn-excel"><i class="fa fa-calculator"></i> Save Reading</button></div>
 </form></div></div></div>
 <div class="col-md-4"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Cost Allocation</h3></div><div class="box-body"><form method="POST" action="{{ route('hotel-management.energy-utility.allocation') }}">@csrf
  <div class="form-group"><label>Reading</label><select name="reading_id" class="form-control"><option value="">Manual</option>@foreach($energyUtility['readings'] as $r)<option value="{{ $r->id }}">{{ $r->reading_no }} - {{ number_format($r->total_cost,2) }}</option>@endforeach</select></div>
  <div class="form-group"><label>Allocation Type</label><select name="allocation_type" class="form-control"><option value="department">Department</option><option value="room">Room</option><option value="folio">Folio</option><option value="manual">Manual</option></select></div>
  <div class="form-group"><label>Target Reference</label><input name="target_reference" class="form-control" placeholder="Room / Dept / Folio"></div>
  <div class="form-group"><label>Allocated Amount</label><input type="number" step="0.0001" name="allocated_amount" class="form-control" required></div>
  <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="draft">Draft</option><option value="reviewed">Reviewed</option><option value="posted">Posted</option></select></div>
  <div class="text-right"><button class="btn hm-btn-pdf"><i class="fa fa-save"></i> Save Allocation</button></div>
 </form></div></div></div>
</div>
<div class="box hm-card"><div class="box-header with-border"><div class="hm-toolbar"><input class="form-control hm-search-input" style="max-width:260px" placeholder="Search readings..."><button class="btn hm-btn-csv">CSV</button><button class="btn hm-btn-excel">Excel</button><button class="btn hm-btn-pdf">PDF</button><button class="btn hm-btn-print">Print</button><button class="btn hm-btn-col">Column Visibility</button></div><h3 class="box-title">Utility Readings</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped hm-table"><thead><tr><th>No</th><th>Date</th><th>Meter</th><th class="text-right">Previous</th><th class="text-right">Current</th><th class="text-right">Units</th><th class="text-right">Cost</th><th>Status</th></tr></thead><tbody>@forelse($energyUtility['readings'] as $r)<tr><td>{{ $r->reading_no }}</td><td>{{ $r->reading_date }}</td><td>{{ $r->meter_id }}</td><td class="text-right">{{ number_format($r->previous_reading,4) }}</td><td class="text-right">{{ number_format($r->current_reading,4) }}</td><td class="text-right">{{ number_format($r->consumption_units,4) }}</td><td class="text-right">{{ number_format($r->total_cost,2) }}</td><td><span class="hm-badge {{ $r->status }}">{{ ucfirst($r->status) }}</span></td></tr>@empty<tr><td colspan="8" class="text-center text-muted">No utility readings yet.</td></tr>@endforelse</tbody></table></div></div>
<div class="row"><div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Meters</h3></div><div class="box-body table-responsive"><table class="table table-bordered hm-table"><thead><tr><th>No</th><th>Name</th><th>Type</th><th>Dept</th><th class="text-right">Rate</th></tr></thead><tbody>@forelse($energyUtility['meters'] as $m)<tr><td>{{ $m->meter_no }}</td><td>{{ $m->meter_name }}</td><td>{{ $m->utility_type }}</td><td>{{ $m->department }}</td><td class="text-right">{{ number_format($m->rate_per_unit,4) }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">No meters.</td></tr>@endforelse</tbody></table></div></div></div><div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Allocations</h3></div><div class="box-body table-responsive"><table class="table table-bordered hm-table"><thead><tr><th>No</th><th>Type</th><th>Target</th><th class="text-right">Amount</th><th>Status</th><th>Action</th></tr></thead><tbody>@forelse($energyUtility['allocations'] as $a)<tr><td>{{ $a->allocation_no }}</td><td>{{ $a->allocation_type }}</td><td>{{ $a->target_reference }}</td><td class="text-right">{{ number_format($a->allocated_amount,2) }}</td><td><span class="hm-badge {{ $a->status }}">{{ ucfirst($a->status) }}</span></td><td>@if($a->status!='posted')<form method="POST" action="{{ route('hotel-management.energy-utility.post-allocation',$a->id) }}">@csrf<button class="btn btn-xs hm-btn-add">Post</button></form>@endif</td></tr>@empty<tr><td colspan="6" class="text-center text-muted">No allocations.</td></tr>@endforelse</tbody></table></div></div></div></div>
</section>
@endsection
