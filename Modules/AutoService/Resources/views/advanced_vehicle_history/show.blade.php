@extends('layouts.app')
@section('title', 'Vehicle History')
@section('content')
@php
    $money = function($amount){ return number_format((float)$amount, 2); };
@endphp
<section class="content-header">
    <h1>Vehicle History <small>{{ $vehicle->registration_no }}</small></h1>
</section>
<section class="content">
    <div class="row">
        <div class="col-md-3 col-sm-6"><div class="small-box bg-aqua"><div class="inner"><h3>{{ $summary['jobCount'] ?? 0 }}</h3><p>Total Jobs</p></div><div class="icon"><i class="fa fa-wrench"></i></div></div></div>
        <div class="col-md-3 col-sm-6"><div class="small-box bg-green"><div class="inner"><h3>{{ $money($summary['invoiceTotal'] ?? 0) }}</h3><p>Lifetime Billing</p></div><div class="icon"><i class="fa fa-file-text"></i></div></div></div>
        <div class="col-md-3 col-sm-6"><div class="small-box bg-yellow"><div class="inner"><h3>{{ $money($summary['partsTotal'] ?? 0) }}</h3><p>Parts / Accessories</p></div><div class="icon"><i class="fa fa-cogs"></i></div></div></div>
        <div class="col-md-3 col-sm-6"><div class="small-box bg-red"><div class="inner"><h3>{{ $money($summary['balance'] ?? 0) }}</h3><p>Outstanding</p></div><div class="icon"><i class="fa fa-balance-scale"></i></div></div></div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Vehicle Profile</h3><div class="box-tools"><a href="{{ route('autoservice.advanced_vehicle_history.index') }}" class="btn btn-default btn-sm">Back</a></div></div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-3"><strong>Registration:</strong><br>{{ $vehicle->registration_no }}</div>
                <div class="col-md-3"><strong>Make / Model:</strong><br>{{ $vehicle->make }} {{ $vehicle->model }} {{ $vehicle->year }}</div>
                <div class="col-md-3"><strong>VIN:</strong><br>{{ $vehicle->vin }}</div>
                <div class="col-md-3"><strong>Engine / Chassis:</strong><br>{{ $vehicle->engine_no }} / {{ $vehicle->chassis_no }}</div>
                <div class="col-md-3" style="margin-top:10px"><strong>Current Odometer:</strong><br>{{ number_format((float)($vehicle->current_odometer ?? 0),0) }}</div>
                <div class="col-md-3" style="margin-top:10px"><strong>Last Service:</strong><br>{{ $vehicle->last_service_date }}</div>
                <div class="col-md-3" style="margin-top:10px"><strong>Next Service:</strong><br>{{ $vehicle->next_service_date }}</div>
                <div class="col-md-3" style="margin-top:10px"><strong>Next Odometer:</strong><br>{{ $vehicle->next_service_odometer ? number_format($vehicle->next_service_odometer) : '' }}</div>
            </div>
        </div>
    </div>

    <div class="box box-solid">
        <div class="box-header with-border"><h3 class="box-title">Filters</h3></div>
        <div class="box-body">
            <form method="get" class="row">
                <div class="col-md-2"><label>From</label><input type="date" name="from_date" class="form-control" value="{{ $filters['from_date'] ?? '' }}"></div>
                <div class="col-md-2"><label>To</label><input type="date" name="to_date" class="form-control" value="{{ $filters['to_date'] ?? '' }}"></div>
                <div class="col-md-2"><label>Status</label><input name="status" class="form-control" value="{{ $filters['status'] ?? '' }}"></div>
                <div class="col-md-2"><label>Part / Accessory</label><input name="part" class="form-control" value="{{ $filters['part'] ?? '' }}"></div>
                <div class="col-md-2"><label>Labour</label><input name="labour" class="form-control" value="{{ $filters['labour'] ?? '' }}"></div>
                <div class="col-md-2" style="padding-top:25px"><button class="btn btn-primary">Apply</button> <a class="btn btn-default" href="{{ route('autoservice.advanced_vehicle_history.show', $vehicle->id) }}">Reset</a></div>
            </form>
        </div>
    </div>

    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#jobs" data-toggle="tab">Service Jobs</a></li>
            <li><a href="#parts" data-toggle="tab">Parts & Accessories</a></li>
            <li><a href="#labour" data-toggle="tab">Labour</a></li>
            <li><a href="#invoices" data-toggle="tab">Invoices</a></li>
            <li><a href="#odometer" data-toggle="tab">Odometer</a></li>
            <li><a href="#documents" data-toggle="tab">Documents / Photos</a></li>
            <li><a href="#warranty" data-toggle="tab">Warranty</a></li>
            <li><a href="#timeline" data-toggle="tab">Timeline</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active table-responsive" id="jobs">
                <table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Job No</th><th>Type</th><th>Status</th><th>Odometer</th><th>Complaint</th><th>Total</th><th>Paid</th><th>Balance</th></tr></thead><tbody>
                @forelse($jobs as $j)<tr><td>{{ $j->job_date }}</td><td>{{ $j->job_no }}</td><td>{{ $j->job_type }}</td><td><span class="label label-info">{{ $j->status }}</span></td><td>{{ number_format((float)($j->odometer ?? 0),0) }}</td><td>{{ $j->customer_complaint }}</td><td>{{ $money($j->total_amount) }}</td><td>{{ $money($j->paid_amount) }}</td><td>{{ $money($j->balance_amount) }}</td></tr>@empty<tr><td colspan="9" class="text-center text-muted">No job history.</td></tr>@endforelse
                </tbody></table>
            </div>
            <div class="tab-pane table-responsive" id="parts">
                <p><a class="btn btn-success btn-sm" href="{{ route('autoservice.advanced_vehicle_history.parts_export', array_merge(['vehicle'=>$vehicle->id], request()->query())) }}"><i class="fa fa-download"></i> Export CSV</a></p>
                <table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Job No</th><th>Movement</th><th>Part / Accessory</th><th>Qty</th><th>Unit Price</th><th>Discount</th><th>Tax</th><th>Total</th><th>Reference</th></tr></thead><tbody>
                @forelse($parts as $p)@php($unit=$p->unit_price ?? $p->unit_cost ?? 0) @php($total=$p->line_total ?? (($p->quantity ?? 0)*$unit - ($p->discount_amount ?? 0) + ($p->tax_amount ?? 0)))<tr><td>{{ $p->movement_date ?? $p->created_at }}</td><td>{{ $p->job_no }}</td><td>{{ $p->movement_type }}</td><td>{{ $p->description }}</td><td>{{ number_format((float)$p->quantity,2) }}</td><td>{{ $money($unit) }}</td><td>{{ $money($p->discount_amount ?? 0) }}</td><td>{{ $money($p->tax_amount ?? 0) }}</td><td>{{ $money($total) }}</td><td>{{ $p->reference_no ?? '' }}</td></tr>@empty<tr><td colspan="10" class="text-center text-muted">No parts/accessories found.</td></tr>@endforelse
                </tbody></table>
            </div>
            <div class="tab-pane table-responsive" id="labour">
                <table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Job No</th><th>Description</th><th>Hours/Qty</th><th>Rate</th><th>Total</th></tr></thead><tbody>
                @forelse($labour as $l)<tr><td>{{ $l->job_date }}</td><td>{{ $l->job_no }}</td><td>{{ $l->description }}</td><td>{{ number_format((float)($l->hours ?? $l->quantity ?? 0),2) }}</td><td>{{ $money($l->rate ?? $l->unit_price ?? 0) }}</td><td>{{ $money($l->line_total ?? 0) }}</td></tr>@empty<tr><td colspan="6" class="text-center text-muted">No labour history.</td></tr>@endforelse
                </tbody></table>
            </div>
            <div class="tab-pane table-responsive" id="invoices">
                <table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Invoice No</th><th>Job No</th><th>Status</th><th>Total</th><th>Paid</th><th>Balance</th></tr></thead><tbody>
                @forelse($invoices as $i)<tr><td>{{ $i->invoice_date }}</td><td>{{ $i->invoice_no }}</td><td>{{ $i->job_no }}</td><td>{{ $i->status }}</td><td>{{ $money($i->total_amount) }}</td><td>{{ $money($i->paid_amount) }}</td><td>{{ $money($i->balance_amount) }}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted">No invoice history.</td></tr>@endforelse
                </tbody></table>
            </div>
            <div class="tab-pane table-responsive" id="odometer">
                <table class="table table-bordered"><thead><tr><th>Date</th><th>Job No</th><th>Odometer</th><th>Status</th></tr></thead><tbody>
                @forelse($odometer as $o)<tr><td>{{ $o->job_date }}</td><td>{{ $o->job_no }}</td><td>{{ number_format((float)$o->odometer,0) }}</td><td>{{ $o->status }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted">No odometer records.</td></tr>@endforelse
                </tbody></table>
            </div>
            <div class="tab-pane table-responsive" id="documents">
                <table class="table table-bordered"><thead><tr><th>Date</th><th>Type</th><th>Name</th><th>Customer Visible</th><th>Notes</th></tr></thead><tbody>
                @forelse($documents as $d)<tr><td>{{ $d->created_at }}</td><td>{{ $d->document_type ?? $d->type ?? '' }}</td><td>{{ $d->title ?? $d->file_name ?? $d->path ?? '' }}</td><td>{{ !empty($d->customer_visible) ? 'Yes' : 'No' }}</td><td>{{ $d->notes ?? $d->note ?? '' }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">No documents/photos.</td></tr>@endforelse
                </tbody></table>
            </div>
            <div class="tab-pane table-responsive" id="warranty">
                <table class="table table-bordered"><thead><tr><th>Date</th><th>Claim No</th><th>Status</th><th>Description</th><th>Resolution</th></tr></thead><tbody>
                @forelse($warranty as $w)<tr><td>{{ $w->created_at }}</td><td>{{ $w->claim_no ?? $w->id }}</td><td>{{ $w->status }}</td><td>{{ $w->description ?? $w->issue ?? '' }}</td><td>{{ $w->resolution_note ?? '' }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">No warranty claim history.</td></tr>@endforelse
                </tbody></table>
            </div>
            <div class="tab-pane table-responsive" id="timeline">
                <table class="table table-bordered"><thead><tr><th>Date/Time</th><th>Event</th><th>Title</th><th>Description</th></tr></thead><tbody>
                @forelse($timeline as $t)<tr><td>{{ $t->event_at }}</td><td>{{ $t->event_type }}</td><td>{{ $t->title }}</td><td>{{ $t->description }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted">No timeline events.</td></tr>@endforelse
                </tbody></table>
            </div>
        </div>
    </div>
</section>
@endsection
