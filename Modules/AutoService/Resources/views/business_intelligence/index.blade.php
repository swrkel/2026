@extends('autoservice::layouts.master')
@section('title','Auto Service Business Intelligence')
@section('autoservice_content')
<div class="row">
    <div class="col-md-2"><div class="box box-solid"><div class="box-body text-center"><h3>{{ $summary['invoices'] }}</h3><p>Invoices</p></div></div></div>
    <div class="col-md-2"><div class="box box-solid"><div class="box-body text-center"><h3>{{ number_format($summary['gross_revenue'], 2) }}</h3><p>Revenue</p></div></div></div>
    <div class="col-md-2"><div class="box box-solid"><div class="box-body text-center"><h3>{{ number_format($summary['outstanding'], 2) }}</h3><p>Outstanding</p></div></div></div>
    <div class="col-md-2"><div class="box box-solid"><div class="box-body text-center"><h3>{{ number_format($warrantyCost, 2) }}</h3><p>Warranty Cost</p></div></div></div>
    <div class="col-md-2"><div class="box box-solid"><div class="box-body text-center"><h3>{{ $retention['repeat_customers'] }}</h3><p>Repeat Customers</p></div></div></div>
    <div class="col-md-2"><div class="box box-solid"><div class="box-body text-center"><h3>{{ number_format($retention['retention_percent'], 2) }}%</h3><p>Retention</p></div></div></div>
</div>

<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Business Intelligence Filters</h3></div>
    <div class="box-body">
        <form method="get" class="row">
            <div class="col-md-4"><label>Search</label><input name="search" value="{{ $search }}" class="form-control" placeholder="Customer, mobile, vehicle no, job no, invoice no, part"></div>
            <div class="col-md-2"><label>From</label><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
            <div class="col-md-2"><label>To</label><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
            <div class="col-md-4" style="padding-top:25px;">
                <button class="btn btn-primary">Search</button>
                <a href="{{ route('autoservice.business_intelligence.index') }}" class="btn btn-default">Reset</a>
                <a href="{{ route('autoservice.business_intelligence.export', request()->query()) }}" class="btn btn-success">Export Job Profit CSV</a>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Parts & Accessories Profitability</h3></div><div class="box-body table-responsive">
            <table class="table table-bordered table-striped"><thead><tr><th>Part / Accessory</th><th>Qty</th><th>Revenue</th><th>Discount</th><th>Tax</th><th>Est. Cost</th><th>Est. Profit</th></tr></thead><tbody>
            @forelse($parts as $p)<tr><td>{{ $p->item_name }}</td><td>{{ number_format((float)$p->qty, 3) }}</td><td>{{ number_format((float)$p->revenue, 2) }}</td><td>{{ number_format((float)$p->discount, 2) }}</td><td>{{ number_format((float)$p->tax, 2) }}</td><td>{{ number_format((float)$p->estimated_cost, 2) }}</td><td>{{ number_format((float)$p->revenue - (float)$p->estimated_cost, 2) }}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted">No parts/accessories revenue found.</td></tr>@endforelse
            </tbody></table>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Labour Profitability</h3></div><div class="box-body table-responsive">
            <table class="table table-bordered table-striped"><thead><tr><th>Labour / Service</th><th>Qty</th><th>Revenue</th><th>Est. Cost</th><th>Est. Profit</th></tr></thead><tbody>
            @forelse($labour as $l)<tr><td>{{ $l->labour_name }}</td><td>{{ number_format((float)$l->qty, 3) }}</td><td>{{ number_format((float)$l->revenue, 2) }}</td><td>{{ number_format((float)$l->estimated_cost, 2) }}</td><td>{{ number_format((float)$l->revenue - (float)$l->estimated_cost, 2) }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">No labour revenue found.</td></tr>@endforelse
            </tbody></table>
        </div></div>
    </div>
</div>

<div class="row">
    <div class="col-md-6"><div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Technician Revenue</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Technician</th><th>Jobs</th><th>Revenue</th></tr></thead><tbody>@forelse($technicians as $t)<tr><td>{{ $t->name ?: 'Unassigned' }}</td><td>{{ $t->jobs }}</td><td>{{ number_format((float)$t->revenue, 2) }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted">No technician revenue found.</td></tr>@endforelse</tbody></table></div></div></div>
    <div class="col-md-6"><div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Monthly Revenue Trend</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Month</th><th>Invoices</th><th>Revenue</th></tr></thead><tbody>@forelse($monthlyTrend as $m)<tr><td>{{ $m->month }}</td><td>{{ $m->invoices }}</td><td>{{ number_format((float)$m->revenue, 2) }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted">No monthly revenue found.</td></tr>@endforelse</tbody></table></div></div></div>
</div>

<div class="row">
    <div class="col-md-6"><div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Top Customers</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Customer</th><th>Mobile</th><th>Invoices</th><th>Revenue</th><th>Outstanding</th></tr></thead><tbody>@forelse($customers as $c)<tr><td>{{ $c->name ?: 'Walk-in / Not Linked' }}</td><td>{{ $c->mobile }}</td><td>{{ $c->invoices }}</td><td>{{ number_format((float)$c->revenue, 2) }}</td><td>{{ number_format((float)$c->outstanding, 2) }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">No customer revenue found.</td></tr>@endforelse</tbody></table></div></div></div>
    <div class="col-md-6"><div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Top Vehicles</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Vehicle</th><th>Make / Model</th><th>Invoices</th><th>Revenue</th></tr></thead><tbody>@forelse($vehicles as $v)<tr><td>{{ $v->registration_no }}</td><td>{{ $v->make }} {{ $v->model }}</td><td>{{ $v->invoices }}</td><td>{{ number_format((float)$v->revenue, 2) }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted">No vehicle revenue found.</td></tr>@endforelse</tbody></table></div></div></div>
</div>

<div class="box box-warning">
    <div class="box-header with-border"><h3 class="box-title">Job Profitability Detail</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped"><thead><tr><th>Job No</th><th>Invoice No</th><th>Date</th><th>Customer</th><th>Vehicle</th><th>Parts</th><th>Labour</th><th>Discount</th><th>Tax</th><th>Total</th><th>Est. Cost</th><th>Est. Profit</th></tr></thead><tbody>
        @forelse($jobProfitability as $j)<tr><td>{{ $j->job_no }}</td><td>{{ $j->invoice_no }}</td><td>{{ $j->invoice_date }}</td><td>{{ $j->customer_name }}</td><td>{{ $j->registration_no }}</td><td>{{ number_format((float)$j->parts_revenue, 2) }}</td><td>{{ number_format((float)$j->labour_revenue, 2) }}</td><td>{{ number_format((float)$j->discount_amount, 2) }}</td><td>{{ number_format((float)$j->tax_amount, 2) }}</td><td>{{ number_format((float)$j->total_amount, 2) }}</td><td>{{ number_format((float)$j->estimated_cost, 2) }}</td><td>{{ number_format((float)$j->estimated_profit, 2) }}</td></tr>@empty<tr><td colspan="12" class="text-center text-muted">No job profitability records found.</td></tr>@endforelse
        </tbody></table>
    </div>
</div>
@endsection
