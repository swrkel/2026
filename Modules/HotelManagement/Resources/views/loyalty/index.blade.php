@extends('hotelmanagement::layouts.app')

@section('hotel_content')
<section class="content-header">
    <h1>Loyalty & Membership <small>Guest points, tiers and rewards</small></h1>
</section>
<section class="content">
    @include('hotelmanagement::partials.nav')
    @if(session('status')) <div class="alert alert-success hm-alert">{{ session('status') }}</div> @endif

    <div class="row">
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Members</div><div class="hm-kpi-value">{{ $loyalty['members_count'] }}</div><div class="hm-kpi-sub">Total loyalty guests</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Active</div><div class="hm-kpi-value">{{ $loyalty['active_members'] }}</div><div class="hm-kpi-sub">Eligible for rewards</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Points</div><div class="hm-kpi-value">{{ number_format($loyalty['total_points'], 2) }}</div><div class="hm-kpi-sub">Current balance</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Tiers</div><div class="hm-kpi-value">{{ $loyalty['tiers_count'] }}</div><div class="hm-kpi-sub">Configured levels</div></div></div>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Create Loyalty Tier</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hotel-management.loyalty.tier') }}">
                        @csrf
                        <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(140px,1fr))">
                            <div class="form-group"><label>Tier Name</label><input name="name" class="form-control" required placeholder="Gold"></div>
                            <div class="form-group"><label>Code</label><input name="code" class="form-control" required placeholder="GOLD"></div>
                            <div class="form-group"><label>Min Points</label><input type="number" step="0.01" name="min_points" class="form-control" value="0"></div>
                            <div class="form-group"><label>Discount %</label><input type="number" step="0.01" name="discount_percent" class="form-control" value="0"></div>
                        </div>
                        <div class="form-group"><label>Benefits</label><textarea name="benefits" class="form-control" rows="3" placeholder="Late checkout, welcome drink, special discount"></textarea></div>
                        <label><input type="checkbox" name="is_active" value="1" checked> Active</label>
                        <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-save"></i> Save Tier</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Register Loyalty Member</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hotel-management.loyalty.member') }}">
                        @csrf
                        <div class="hm-form-grid" style="grid-template-columns:repeat(4,minmax(120px,1fr))">
                            <div class="form-group"><label>Guest ID</label><input type="number" name="guest_id" class="form-control"></div>
                            <div class="form-group"><label>Member No</label><input name="member_no" class="form-control" placeholder="Auto if blank"></div>
                            <div class="form-group"><label>Guest Name</label><input name="guest_name" class="form-control" required></div>
                            <div class="form-group"><label>Mobile</label><input name="mobile" class="form-control"></div>
                            <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control"></div>
                            <div class="form-group"><label>Tier</label><select name="tier_id" class="form-control"><option value="">None</option>@foreach($loyalty['tiers'] as $tier)<option value="{{ $tier->id }}">{{ $tier->name }}</option>@endforeach</select></div>
                            <div class="form-group"><label>Join Date</label><input type="date" name="join_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
                            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="hold">Hold</option><option value="inactive">Inactive</option></select></div>
                        </div>
                        <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-user-plus"></i> Save Member</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Member Register</h3></div>
        <div class="box-body">
            <div class="hm-toolbar"><input class="form-control hm-search-input" placeholder="Search member" style="max-width:260px"><button class="btn hm-btn-csv">CSV</button><button class="btn hm-btn-excel">Excel</button><button class="btn hm-btn-pdf">PDF</button><button class="btn hm-btn-print">Print</button><button class="btn hm-btn-col">Column Visibility</button></div>
            <div class="table-responsive"><table class="table hm-table"><thead><tr><th>ID</th><th>Member No</th><th>Guest</th><th>Mobile</th><th>Email</th><th>Tier</th><th>Points</th><th>Status</th><th>Post Points</th></tr></thead><tbody>
                @forelse($loyalty['members'] as $row)
                    <tr>
                        <td>{{ $row->id }}</td><td>{{ $row->member_no }}</td><td>{{ $row->guest_name }}</td><td>{{ $row->mobile }}</td><td>{{ $row->email }}</td><td>{{ $row->tier_name ?? '-' }}</td><td>{{ number_format($row->points_balance ?? 0, 2) }}</td><td><span class="hm-badge {{ $row->status ?? '' }}">{{ $row->status }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('hotel-management.loyalty.points', $row->id) }}" class="form-inline">
                                @csrf
                                <select name="type" class="form-control input-sm"><option value="earn">Earn</option><option value="redeem">Redeem</option><option value="adjust">Adjust</option><option value="expire">Expire</option></select>
                                <input type="number" step="0.01" name="points" class="form-control input-sm" placeholder="Points" style="width:90px" required>
                                <input name="reference_no" class="form-control input-sm" placeholder="Ref" style="width:90px">
                                <button class="btn btn-xs hm-btn-add">Post</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9"><div class="hm-empty">No loyalty members found yet.</div></td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-5"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Configured Tiers</h3></div><div class="box-body"><div class="table-responsive"><table class="table hm-table"><thead><tr><th>Code</th><th>Name</th><th>Min Points</th><th>Discount</th><th>Active</th></tr></thead><tbody>@forelse($loyalty['tiers'] as $tier)<tr><td>{{ $tier->code }}</td><td>{{ $tier->name }}</td><td>{{ number_format($tier->min_points,2) }}</td><td>{{ number_format($tier->discount_percent,2) }}%</td><td>{{ !empty($tier->is_active) ? 'Yes' : 'No' }}</td></tr>@empty<tr><td colspan="5"><div class="hm-empty">No loyalty tiers configured yet.</div></td></tr>@endforelse</tbody></table></div></div></div></div>
        <div class="col-md-7"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Latest Point Ledger</h3></div><div class="box-body"><div class="table-responsive"><table class="table hm-table"><thead><tr><th>Date</th><th>Member</th><th>Type</th><th>Points</th><th>Signed</th><th>Reference</th></tr></thead><tbody>@forelse($loyalty['ledger'] as $line)<tr><td>{{ $line->transaction_date }}</td><td>{{ $line->member_id }}</td><td>{{ $line->type }}</td><td>{{ number_format($line->points,2) }}</td><td>{{ number_format($line->signed_points,2) }}</td><td>{{ $line->reference_no }}</td></tr>@empty<tr><td colspan="6"><div class="hm-empty">No point ledger entries yet.</div></td></tr>@endforelse</tbody></table></div><ul>@foreach($loyalty['notes'] as $note)<li>{{ $note }}</li>@endforeach</ul></div></div></div>
    </div>
</section>
@endsection
