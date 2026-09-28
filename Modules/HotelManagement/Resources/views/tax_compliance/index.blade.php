@extends('layouts.app')
@section('title', 'Hotel Tax & Compliance')
@section('content')
<section class="content-header hm-page-header">
    <h1><i class="fa fa-balance-scale"></i> Tax & Compliance <small>Hotel tax, service charge and period reconciliation</small></h1>
</section>
<section class="content hm-pos-scope">
    @include('hotelmanagement::partials.nav')
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>Please check:</strong> {{ $errors->first() }}</div>@endif

    <div class="row hm-kpi-row">
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Active Taxes</div><div class="hm-kpi-value">{{ $taxCompliance['active_tax_count'] }}</div><div class="hm-kpi-sub">Current tax rules</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Service Rules</div><div class="hm-kpi-value">{{ $taxCompliance['active_service_count'] }}</div><div class="hm-kpi-sub">Active charge rules</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Tax Amount</div><div class="hm-kpi-value">{{ number_format($taxCompliance['tax_amount'], 2) }}</div><div class="hm-kpi-sub">Posted snapshots</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Gross Amount</div><div class="hm-kpi-value">{{ number_format($taxCompliance['gross_amount'], 2) }}</div><div class="hm-kpi-sub">Tax invoices</div></div></div>
    </div>

    <div class="row">
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Tax Rule</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.tax-compliance.tax-rule') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                    <div class="form-group"><label>Tax Code</label><input name="tax_code" class="form-control" placeholder="Auto if blank"></div>
                    <div class="form-group"><label>Tax Name</label><input name="tax_name" class="form-control" required></div>
                    <div class="form-group"><label>Tax Type</label><select name="tax_type" class="form-control"><option value="percentage">Percentage</option><option value="fixed">Fixed</option></select></div>
                    <div class="form-group"><label>Applies To</label><select name="applies_to" class="form-control"><option value="room">Room</option><option value="fb">F&B</option><option value="spa">Spa</option><option value="transport">Transport</option><option value="all">All</option></select></div>
                    <div class="form-group"><label>Rate %</label><input type="number" step="0.0001" name="rate" class="form-control" required></div>
                    <div class="form-group"><label>Inclusive Type</label><select name="inclusive_type" class="form-control"><option value="exclusive">Exclusive</option><option value="inclusive">Inclusive</option></select></div>
                    <div class="form-group"><label>Effective From</label><input type="date" name="effective_from" class="form-control"></div>
                    <div class="form-group"><label>Effective To</label><input type="date" name="effective_to" class="form-control"></div>
                </div>
                <label class="checkbox-inline"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-save"></i> Save Tax Rule</button></div>
            </form>
        </div></div></div>

        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Service Charge Rule</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.tax-compliance.service-charge-rule') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                    <div class="form-group"><label>Rule Code</label><input name="rule_code" class="form-control" placeholder="Auto if blank"></div>
                    <div class="form-group"><label>Rule Name</label><input name="rule_name" class="form-control" required></div>
                    <div class="form-group"><label>Applies To</label><select name="applies_to" class="form-control"><option value="all">All</option><option value="room">Room</option><option value="fb">F&B</option><option value="spa">Spa</option><option value="laundry">Laundry</option></select></div>
                    <div class="form-group"><label>Rate %</label><input type="number" step="0.0001" name="rate" class="form-control" required></div>
                    <div class="form-group"><label>Distribution</label><select name="distribution_method" class="form-control"><option value="manual">Manual</option><option value="department">Department</option><option value="employee_pool">Employee Pool</option></select></div>
                    <div class="form-group"><label>Effective From</label><input type="date" name="effective_from" class="form-control"></div>
                </div>
                <label class="checkbox-inline"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                <div class="text-right"><button class="btn hm-btn-excel"><i class="fa fa-save"></i> Save Service Rule</button></div>
            </form>
        </div></div></div>
    </div>

    <div class="row">
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Manual Tax Invoice Snapshot</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.tax-compliance.tax-invoice') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                    <div class="form-group"><label>Invoice No</label><input name="invoice_no" class="form-control" placeholder="Auto if blank"></div>
                    <div class="form-group"><label>Guest / Account</label><input name="guest_name" class="form-control"></div>
                    <div class="form-group"><label>Source</label><select name="source_type" class="form-control"><option value="folio">Folio</option><option value="pos">POS</option><option value="spa">Spa</option><option value="manual">Manual</option></select></div>
                    <div class="form-group"><label>Invoice Date</label><input type="date" name="invoice_date" class="form-control"></div>
                    <div class="form-group"><label>Net Amount</label><input type="number" step="0.0001" name="net_amount" class="form-control" required></div>
                    <div class="form-group"><label>Tax Rate %</label><input type="number" step="0.0001" name="tax_rate" class="form-control" value="0"></div>
                    <div class="form-group"><label>Service Charge %</label><input type="number" step="0.0001" name="service_charge_rate" class="form-control" value="0"></div>
                    <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="posted">Posted</option><option value="void">Void</option></select></div>
                </div>
                <div class="text-right"><button class="btn hm-btn-pdf"><i class="fa fa-calculator"></i> Post Snapshot</button></div>
            </form>
        </div></div></div>

        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Tax Period Summary</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.tax-compliance.period-summary') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                    <div class="form-group"><label>Period From</label><input type="date" name="period_from" class="form-control" required></div>
                    <div class="form-group"><label>Period To</label><input type="date" name="period_to" class="form-control" required></div>
                    <div class="form-group"><label>Room Revenue</label><input type="number" step="0.0001" name="room_revenue" class="form-control" value="0"></div>
                    <div class="form-group"><label>F&B Revenue</label><input type="number" step="0.0001" name="fb_revenue" class="form-control" value="0"></div>
                    <div class="form-group"><label>Other Revenue</label><input type="number" step="0.0001" name="other_revenue" class="form-control" value="0"></div>
                    <div class="form-group"><label>Tax Amount</label><input type="number" step="0.0001" name="tax_amount" class="form-control" value="0"></div>
                    <div class="form-group"><label>Service Charge</label><input type="number" step="0.0001" name="service_charge_amount" class="form-control" value="0"></div>
                    <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="draft">Draft</option><option value="approved">Approved</option><option value="posted">Posted</option></select></div>
                </div>
                <div class="form-group"><label>Remarks</label><textarea name="remarks" rows="2" class="form-control"></textarea></div>
                <div class="text-right"><button class="btn hm-btn-print"><i class="fa fa-save"></i> Save Summary</button></div>
            </form>
        </div></div></div>
    </div>

    <div class="box hm-card"><div class="box-header with-border"><div class="hm-toolbar"><input class="form-control hm-search-input" style="max-width:260px" placeholder="Search tax invoices..."><button class="btn hm-btn-csv">CSV</button><button class="btn hm-btn-excel">Excel</button><button class="btn hm-btn-pdf">PDF</button><button class="btn hm-btn-print">Print</button><button class="btn hm-btn-col">Column Visibility</button></div><h3 class="box-title">Tax Invoice Snapshots</h3></div><div class="box-body table-responsive">
        <table class="table table-bordered table-striped hm-table"><thead><tr><th>Invoice No</th><th>Date</th><th>Source</th><th>Guest</th><th class="text-right">Net</th><th class="text-right">Tax</th><th class="text-right">Service</th><th class="text-right">Gross</th><th>Status</th></tr></thead><tbody>
        @forelse($taxCompliance['tax_invoices'] as $i)<tr><td>{{ $i->invoice_no }}</td><td>{{ $i->invoice_date }}</td><td>{{ $i->source_type }}</td><td>{{ $i->guest_name }}</td><td class="text-right">{{ number_format($i->net_amount,2) }}</td><td class="text-right">{{ number_format($i->tax_amount,2) }}</td><td class="text-right">{{ number_format($i->service_charge_amount,2) }}</td><td class="text-right">{{ number_format($i->gross_amount,2) }}</td><td><span class="hm-badge {{ $i->status }}">{{ ucfirst($i->status) }}</span></td></tr>@empty<tr><td colspan="9" class="text-center text-muted">No tax invoice snapshots yet.</td></tr>@endforelse
        </tbody></table>
    </div></div>

    <div class="row">
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Tax Rules</h3></div><div class="box-body table-responsive"><table class="table table-bordered hm-table"><thead><tr><th>Code</th><th>Name</th><th>Applies</th><th class="text-right">Rate</th><th>Active</th></tr></thead><tbody>@forelse($taxCompliance['tax_rules'] as $t)<tr><td>{{ $t->tax_code }}</td><td>{{ $t->tax_name }}</td><td>{{ $t->applies_to }}</td><td class="text-right">{{ number_format($t->rate,4) }}%</td><td>{{ $t->is_active ? 'Yes' : 'No' }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">No tax rules.</td></tr>@endforelse</tbody></table></div></div></div>
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Period Summaries</h3></div><div class="box-body table-responsive"><table class="table table-bordered hm-table"><thead><tr><th>No</th><th>Period</th><th class="text-right">Tax</th><th class="text-right">Service</th><th>Status</th></tr></thead><tbody>@forelse($taxCompliance['period_summaries'] as $s)<tr><td>{{ $s->summary_no }}</td><td>{{ $s->period_from }} to {{ $s->period_to }}</td><td class="text-right">{{ number_format($s->tax_amount,2) }}</td><td class="text-right">{{ number_format($s->service_charge_amount,2) }}</td><td><span class="hm-badge {{ $s->status }}">{{ ucfirst($s->status) }}</span></td></tr>@empty<tr><td colspan="5" class="text-center text-muted">No summaries.</td></tr>@endforelse</tbody></table></div></div></div>
    </div>
</section>
@endsection
