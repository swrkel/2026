@extends('layouts.app')
@section('title', 'Hotel Revenue Management')
@section('content')
<section class="content-header hm-page-header">
    <h1><i class="fa fa-line-chart"></i> Revenue Management <small>Seasonal pricing, yield rules, corporate/agent contracts, group blocks and split folios</small></h1>
</section>
<section class="content hm-pos-scope">
    @include('hotelmanagement::partials.nav')
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>Please check:</strong> {{ $errors->first() }}</div>@endif

    <div class="row hm-kpi-row">
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Active Seasons</div><div class="hm-kpi-value">{{ $revenueManagement['active_seasons'] }}</div><div class="hm-kpi-sub">Seasonal rate plans</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Yield Rules</div><div class="hm-kpi-value">{{ $revenueManagement['active_yield_rules'] }}</div><div class="hm-kpi-sub">Occupancy based pricing</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Credit Exposure</div><div class="hm-kpi-value">{{ number_format($revenueManagement['credit_exposure'], 2) }}</div><div class="hm-kpi-sub">Corporate direct billing</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Blocked Rooms</div><div class="hm-kpi-value">{{ $revenueManagement['blocked_rooms'] }}</div><div class="hm-kpi-sub">Group reservations</div></div></div>
    </div>

    <div class="row">
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Seasonal Pricing</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.revenue-management.season') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                    <div class="form-group"><label>Season Code</label><input name="season_code" class="form-control" required placeholder="PEAK"></div>
                    <div class="form-group"><label>Season Name</label><input name="season_name" class="form-control" required></div>
                    <div class="form-group"><label>From</label><input type="date" name="date_from" class="form-control" required></div>
                    <div class="form-group"><label>To</label><input type="date" name="date_to" class="form-control" required></div>
                    <div class="form-group"><label>Adjustment Type</label><select name="rate_adjustment_type" class="form-control"><option value="percent">Percent</option><option value="fixed">Fixed Amount</option></select></div>
                    <div class="form-group"><label>Adjustment Value</label><input type="number" step="0.01" name="rate_adjustment_value" class="form-control" required></div>
                    <div class="form-group"><label>Active</label><select name="is_active" class="form-control"><option value="1">Yes</option><option value="0">No</option></select></div>
                </div>
                <div class="form-group"><label>Remarks</label><textarea name="remarks" rows="2" class="form-control"></textarea></div>
                <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-save"></i> Save Season</button></div>
            </form>
        </div></div></div>
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Yield Management Rule</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.revenue-management.yield-rule') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                    <div class="form-group"><label>Rule Name</label><input name="rule_name" class="form-control" required></div>
                    <div class="form-group"><label>Priority</label><input type="number" name="priority" class="form-control" value="1"></div>
                    <div class="form-group"><label>Occupancy From %</label><input type="number" step="0.01" name="occupancy_from" class="form-control" required></div>
                    <div class="form-group"><label>Occupancy To %</label><input type="number" step="0.01" name="occupancy_to" class="form-control" required></div>
                    <div class="form-group"><label>Adjustment Type</label><select name="adjustment_type" class="form-control"><option value="percent">Percent</option><option value="fixed">Fixed Amount</option></select></div>
                    <div class="form-group"><label>Adjustment Value</label><input type="number" step="0.01" name="adjustment_value" class="form-control" required></div>
                    <div class="form-group"><label>Active</label><select name="is_active" class="form-control"><option value="1">Yes</option><option value="0">No</option></select></div>
                </div>
                <div class="form-group"><label>Remarks</label><textarea name="remarks" rows="2" class="form-control"></textarea></div>
                <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-save"></i> Save Yield Rule</button></div>
            </form>
        </div></div></div>
    </div>

    <div class="row">
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Corporate Contract / Direct Billing</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.revenue-management.corporate-contract') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                    <div class="form-group"><label>Company Name</label><input name="company_name" class="form-control" required></div>
                    <div class="form-group"><label>Contact Person</label><input name="contact_person" class="form-control"></div>
                    <div class="form-group"><label>Mobile</label><input name="mobile" class="form-control"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control"></div>
                    <div class="form-group"><label>Contract From</label><input type="date" name="contract_from" class="form-control"></div>
                    <div class="form-group"><label>Contract To</label><input type="date" name="contract_to" class="form-control"></div>
                    <div class="form-group"><label>Rate Type</label><input name="rate_type" class="form-control" value="contracted"></div>
                    <div class="form-group"><label>Discount %</label><input type="number" step="0.01" name="discount_percent" class="form-control" value="0"></div>
                    <div class="form-group"><label>Credit Limit</label><input type="number" step="0.01" name="credit_limit" class="form-control" value="0"></div>
                    <div class="form-group"><label>Current Balance</label><input type="number" step="0.01" name="current_balance" class="form-control" value="0"></div>
                    <div class="form-group"><label>Direct Billing</label><select name="direct_billing_allowed" class="form-control"><option value="1">Allowed</option><option value="0">Not Allowed</option></select></div>
                    <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="hold">Hold</option><option value="expired">Expired</option></select></div>
                </div>
                <div class="form-group"><label>Remarks</label><textarea name="remarks" rows="2" class="form-control"></textarea></div>
                <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-building"></i> Save Corporate Contract</button></div>
            </form>
        </div></div></div>
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Travel Agent Contract</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.revenue-management.agent-contract') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                    <div class="form-group"><label>Agent Name</label><input name="agent_name" class="form-control" required></div>
                    <div class="form-group"><label>Agent Code</label><input name="agent_code" class="form-control" required></div>
                    <div class="form-group"><label>Commission Type</label><select name="commission_type" class="form-control"><option value="percent">Percent</option><option value="fixed">Fixed Amount</option></select></div>
                    <div class="form-group"><label>Commission Value</label><input type="number" step="0.01" name="commission_value" class="form-control" value="0"></div>
                    <div class="form-group"><label>Contract From</label><input type="date" name="contract_from" class="form-control"></div>
                    <div class="form-group"><label>Contract To</label><input type="date" name="contract_to" class="form-control"></div>
                    <div class="form-group"><label>Credit Limit</label><input type="number" step="0.01" name="credit_limit" class="form-control" value="0"></div>
                    <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="hold">Hold</option><option value="expired">Expired</option></select></div>
                </div>
                <div class="form-group"><label>Remarks</label><textarea name="remarks" rows="2" class="form-control"></textarea></div>
                <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-handshake-o"></i> Save Agent Contract</button></div>
            </form>
        </div></div></div>
    </div>

    <div class="row">
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Group Room Blocking</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.revenue-management.group-block') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                    <div class="form-group"><label>Group Name</label><input name="group_name" class="form-control" required></div>
                    <div class="form-group"><label>Blocked Rooms</label><input type="number" name="blocked_rooms" class="form-control" value="1" required></div>
                    <div class="form-group"><label>Arrival</label><input type="date" name="arrival_date" class="form-control" required></div>
                    <div class="form-group"><label>Departure</label><input type="date" name="departure_date" class="form-control" required></div>
                    <div class="form-group"><label>Rate Amount</label><input type="number" step="0.01" name="rate_amount" class="form-control" value="0"></div>
                    <div class="form-group"><label>Cutoff Date</label><input type="date" name="cutoff_date" class="form-control"></div>
                    <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="blocked">Blocked</option><option value="confirmed">Confirmed</option><option value="released">Released</option></select></div>
                </div>
                <div class="form-group"><label>Remarks</label><textarea name="remarks" rows="2" class="form-control"></textarea></div>
                <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-calendar-check-o"></i> Save Group Block</button></div>
            </form>
        </div></div></div>
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Split Folio Rule</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.revenue-management.split-folio') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                    <div class="form-group"><label>Rule Name</label><input name="rule_name" class="form-control" required></div>
                    <div class="form-group"><label>Payer Type</label><select name="payer_type" class="form-control"><option value="guest">Guest</option><option value="company">Company</option><option value="agent">Agent</option></select></div>
                    <div class="form-group"><label>Charge Category</label><input name="charge_category" class="form-control" value="room"></div>
                    <div class="form-group"><label>Split Type</label><select name="split_type" class="form-control"><option value="percent">Percent</option><option value="fixed">Fixed Amount</option></select></div>
                    <div class="form-group"><label>Split Value</label><input type="number" step="0.01" name="split_value" class="form-control" required></div>
                    <div class="form-group"><label>Active</label><select name="is_active" class="form-control"><option value="1">Yes</option><option value="0">No</option></select></div>
                </div>
                <div class="form-group"><label>Remarks</label><textarea name="remarks" rows="2" class="form-control"></textarea></div>
                <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-files-o"></i> Save Split Rule</button></div>
            </form>
        </div></div></div>
    </div>

    <div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Group Blocks</h3></div><div class="box-body table-responsive">
        <table class="table table-bordered table-striped hm-table"><thead><tr><th>Block No</th><th>Group</th><th>Stay</th><th class="text-right">Blocked</th><th class="text-right">Released</th><th class="text-right">Rate</th><th>Status</th><th>Release</th></tr></thead><tbody>
        @forelse($revenueManagement['group_blocks'] as $g)<tr>
            <td>{{ $g->block_no }}</td><td>{{ $g->group_name }}</td><td>{{ $g->arrival_date }} - {{ $g->departure_date }}</td><td class="text-right">{{ $g->blocked_rooms }}</td><td class="text-right">{{ $g->released_rooms }}</td><td class="text-right">{{ number_format($g->rate_amount,2) }}</td><td><span class="label label-info">{{ ucfirst($g->status) }}</span></td>
            <td><form method="POST" action="{{ route('hotel-management.revenue-management.release-rooms', $g->id) }}" class="form-inline">@csrf<input type="number" name="release_rooms" value="1" min="1" class="form-control input-sm" style="width:70px"><button class="btn btn-xs hm-btn-edit"><i class="fa fa-unlock"></i> Release</button></form></td>
        </tr>@empty<tr><td colspan="8" class="text-center text-muted">No group room blocks yet.</td></tr>@endforelse
        </tbody></table>
    </div></div>

    <div class="row">
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Corporate Contracts</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped hm-table"><thead><tr><th>No</th><th>Company</th><th class="text-right">Credit Limit</th><th class="text-right">Balance</th><th>Status</th></tr></thead><tbody>@forelse($revenueManagement['contracts'] as $c)<tr><td>{{ $c->contract_no }}</td><td>{{ $c->company_name }}</td><td class="text-right">{{ number_format($c->credit_limit,2) }}</td><td class="text-right">{{ number_format($c->current_balance,2) }}</td><td>{{ ucfirst($c->status) }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">No corporate contracts yet.</td></tr>@endforelse</tbody></table></div></div></div>
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Revenue Rules</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped hm-table"><thead><tr><th>Type</th><th>Name</th><th>Range</th><th class="text-right">Adjustment</th><th>Active</th></tr></thead><tbody>@foreach($revenueManagement['seasons'] as $s)<tr><td>Season</td><td>{{ $s->season_name }}</td><td>{{ $s->date_from }} - {{ $s->date_to }}</td><td class="text-right">{{ $s->rate_adjustment_type }} {{ number_format($s->rate_adjustment_value,2) }}</td><td>{{ $s->is_active ? 'Yes' : 'No' }}</td></tr>@endforeach @foreach($revenueManagement['yield_rules'] as $y)<tr><td>Yield</td><td>{{ $y->rule_name }}</td><td>{{ $y->occupancy_from }}% - {{ $y->occupancy_to }}%</td><td class="text-right">{{ $y->adjustment_type }} {{ number_format($y->adjustment_value,2) }}</td><td>{{ $y->is_active ? 'Yes' : 'No' }}</td></tr>@endforeach @if(empty($revenueManagement['seasons']) && empty($revenueManagement['yield_rules']))<tr><td colspan="5" class="text-center text-muted">No revenue rules yet.</td></tr>@endif</tbody></table></div></div></div>
    </div>
</section>
@endsection
