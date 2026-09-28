@extends('autoservice::layouts.master')

@section('content')
<section class="content-header">
    <h1>Dealer Enterprise Centre <small>Fleet, AMC, corporate pricing and dealer controls</small></h1>
</section>

<section class="content">
    @include('autoservice::layouts.nav')

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="box box-solid">
        <div class="box-body">
            <form method="get" class="form-inline">
                <div class="form-group">
                    <label>From</label>
                    <input type="date" name="from" value="{{ $from }}" class="form-control input-sm">
                </div>
                <div class="form-group">
                    <label>To</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control input-sm">
                </div>
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control input-sm" placeholder="Fleet / vehicle / driver / contract">
                </div>
                <button class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Search</button>
                <a href="{{ route('autoservice.dealer_enterprise.index') }}" class="btn btn-default btn-sm">Reset</a>
                <a href="{{ route('autoservice.dealer_enterprise.export_fleet_jobs', request()->query()) }}" class="btn btn-success btn-sm"><i class="fa fa-download"></i> Export Fleet Jobs</a>
            </form>
        </div>
    </div>

    <div class="row">
        @foreach([
            'Fleet Customers' => $summary['fleet_customers'],
            'Active Contracts' => $summary['active_contracts'],
            'Service Packages' => $summary['service_packages'],
            'Drivers' => $summary['drivers'],
            'Fleet Jobs' => $summary['fleet_jobs'],
            'Fleet Revenue' => number_format($summary['fleet_revenue'], 2),
        ] as $label => $value)
            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="small-box bg-aqua">
                    <div class="inner"><h3>{{ $value }}</h3><p>{{ $label }}</p></div>
                    <div class="icon"><i class="fa fa-building"></i></div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title">Add Fleet / Corporate Customer</h3></div>
                <form method="post" action="{{ route('autoservice.dealer_enterprise.fleet_customers.store') }}">
                    @csrf
                    <div class="box-body">
                        <div class="form-group"><label>Fleet Name</label><input name="fleet_name" class="form-control" required></div>
                        <div class="form-group"><label>Contact ID</label><input name="contact_id" type="number" class="form-control" placeholder="Optional existing customer/contact ID"></div>
                        <div class="form-group"><label>Contract No</label><input name="contract_no" class="form-control"></div>
                        <div class="row">
                            <div class="col-md-6"><label>Credit Limit</label><input name="credit_limit" type="number" step="0.01" class="form-control"></div>
                            <div class="col-md-6"><label>Billing Cycle</label><input name="billing_cycle" class="form-control" placeholder="Monthly"></div>
                        </div>
                        <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="on_hold">On Hold</option><option value="closed">Closed</option></select></div>
                        <div class="form-group"><label>Note</label><textarea name="note" class="form-control" rows="2"></textarea></div>
                    </div>
                    <div class="box-footer"><button class="btn btn-primary btn-sm">Save Fleet Customer</button></div>
                </form>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-success">
                <div class="box-header with-border"><h3 class="box-title">Add Fleet Service / AMC Contract</h3></div>
                <form method="post" action="{{ route('autoservice.dealer_enterprise.contracts.store') }}">
                    @csrf
                    <div class="box-body">
                        <div class="form-group"><label>Fleet Customer</label><select name="fleet_customer_id" class="form-control" required>@foreach($fleetCustomers as $f)<option value="{{ $f->id }}">{{ $f->fleet_name }}</option>@endforeach</select></div>
                        <div class="form-group"><label>Contract No</label><input name="contract_no" class="form-control" required></div>
                        <div class="form-group"><label>Type</label><select name="contract_type" class="form-control"><option value="fleet_service">Fleet Service</option><option value="amc">AMC</option><option value="corporate_pricing">Corporate Pricing</option><option value="warranty_support">Warranty Support</option></select></div>
                        <div class="row"><div class="col-md-6"><label>Start</label><input type="date" name="start_date" class="form-control"></div><div class="col-md-6"><label>End</label><input type="date" name="end_date" class="form-control"></div></div>
                        <div class="row"><div class="col-md-6"><label>Vehicle Limit</label><input type="number" name="vehicle_limit" class="form-control"></div><div class="col-md-6"><label>Monthly Value</label><input type="number" step="0.01" name="monthly_value" class="form-control"></div></div>
                        <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="draft">Draft</option><option value="expired">Expired</option><option value="cancelled">Cancelled</option></select></div>
                    </div>
                    <div class="box-footer"><button class="btn btn-success btn-sm">Save Contract</button></div>
                </form>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-warning">
                <div class="box-header with-border"><h3 class="box-title">Add Fleet Driver</h3></div>
                <form method="post" action="{{ route('autoservice.dealer_enterprise.drivers.store') }}">
                    @csrf
                    <div class="box-body">
                        <div class="form-group"><label>Fleet Customer</label><select name="fleet_customer_id" class="form-control" required>@foreach($fleetCustomers as $f)<option value="{{ $f->id }}">{{ $f->fleet_name }}</option>@endforeach</select></div>
                        <div class="form-group"><label>Driver Name</label><input name="driver_name" class="form-control" required></div>
                        <div class="row"><div class="col-md-6"><label>Mobile</label><input name="mobile" class="form-control"></div><div class="col-md-6"><label>NIC</label><input name="nic_no" class="form-control"></div></div>
                        <div class="form-group"><label>License No</label><input name="license_no" class="form-control"></div>
                        <div class="form-group"><label>Assigned Vehicle ID</label><input name="assigned_vehicle_id" type="number" class="form-control"></div>
                        <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option><option value="blacklisted">Blacklisted</option></select></div>
                    </div>
                    <div class="box-footer"><button class="btn btn-warning btn-sm">Save Driver</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="box box-solid">
        <div class="box-header with-border"><h3 class="box-title">Fleet Customers</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped table-condensed">
                <thead><tr><th>Fleet Name</th><th>Linked Contact</th><th>Contract No</th><th>Credit Limit</th><th>Billing Cycle</th><th>Status</th><th>Note</th></tr></thead>
                <tbody>@forelse($fleetCustomers as $f)<tr><td>{{ $f->fleet_name }}</td><td>{{ $f->contact_name }}<br><small>{{ $f->contact_mobile }}</small></td><td>{{ $f->contract_no }}</td><td class="text-right">{{ number_format((float)$f->credit_limit, 2) }}</td><td>{{ $f->billing_cycle }}</td><td><span class="label label-info">{{ ucfirst($f->status) }}</span></td><td>{{ $f->note }}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted">No fleet customers found.</td></tr>@endforelse</tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6"><div class="box box-success"><div class="box-header with-border"><h3 class="box-title">Fleet / AMC Contracts</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-condensed"><thead><tr><th>Fleet</th><th>Contract</th><th>Type</th><th>Period</th><th>Value</th><th>Status</th></tr></thead><tbody>@forelse($contracts as $c)<tr><td>{{ $c->fleet_name }}</td><td>{{ $c->contract_no }}</td><td>{{ $c->contract_type }}</td><td>{{ $c->start_date }} - {{ $c->end_date }}</td><td class="text-right">{{ number_format((float)$c->monthly_value, 2) }}</td><td>{{ $c->status }}</td></tr>@empty<tr><td colspan="6" class="text-center text-muted">No contracts found.</td></tr>@endforelse</tbody></table></div></div></div>
        <div class="col-md-6"><div class="box box-warning"><div class="box-header with-border"><h3 class="box-title">Fleet Drivers</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-condensed"><thead><tr><th>Fleet</th><th>Driver</th><th>Mobile</th><th>License</th><th>Vehicle</th><th>Status</th></tr></thead><tbody>@forelse($drivers as $d)<tr><td>{{ $d->fleet_name }}</td><td>{{ $d->driver_name }}</td><td>{{ $d->mobile }}</td><td>{{ $d->license_no }}</td><td>{{ $d->registration_no }}</td><td>{{ $d->status }}</td></tr>@empty<tr><td colspan="6" class="text-center text-muted">No drivers found.</td></tr>@endforelse</tbody></table></div></div></div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Fleet Job Register</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped table-condensed">
                <thead><tr><th>Date</th><th>Fleet</th><th>Job No</th><th>Vehicle</th><th>Driver</th><th>Status</th><th>Invoice</th><th>Total</th><th>Paid</th></tr></thead>
                <tbody>@forelse($fleetJobs as $j)<tr><td>{{ $j->job_date }}</td><td>{{ $j->fleet_name }}</td><td>{{ $j->job_no }}</td><td>{{ $j->registration_no }}</td><td>{{ $j->driver_name }}</td><td>{{ $j->status }}</td><td>{{ $j->invoice_no }}</td><td class="text-right">{{ number_format((float)$j->total_amount, 2) }}</td><td class="text-right">{{ number_format((float)$j->paid_amount, 2) }}</td></tr>@empty<tr><td colspan="9" class="text-center text-muted">No fleet jobs found for selected period.</td></tr>@endforelse</tbody>
            </table>
        </div>
    </div>

    <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Corporate Pricing / Service Package Visibility</h3></div>
        <div class="box-body">
            <p class="text-muted">This stage adds the dealer-enterprise control layer. Corporate pricing records can be loaded through SQL/API/import and applied later in the job estimate/pricing flow without affecting small workshops.</p>
            <div class="row">
                <div class="col-md-6"><h4>Available Service Packages</h4><ul>@forelse($servicePackages as $p)<li>{{ $p->name ?? $p->code ?? 'Package' }} @if(isset($p->price)) - {{ number_format((float)$p->price, 2) }} @endif</li>@empty<li class="text-muted">No packages found.</li>@endforelse</ul></div>
                <div class="col-md-6"><h4>Corporate Pricing Items</h4><ul>@forelse($corporatePricing as $p)<li>{{ $p->fleet_name }} - {{ $p->item_name }}: {{ number_format((float)$p->special_price, 2) }}</li>@empty<li class="text-muted">No corporate pricing records found.</li>@endforelse</ul></div>
            </div>
        </div>
    </div>
</section>
@endsection
