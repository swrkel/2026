@extends('hotelmanagement::layouts.app')

@section('hotel_content')
<section class="content-header">
    <h1>Valet Parking <small>Parking zones, tickets, vehicle release and payment control</small></h1>
</section>
<section class="content">
    @include('hotelmanagement::partials.nav')
    @if(session('status')) <div class="alert alert-success hm-alert">{{ session('status') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger hm-alert">{{ session('error') }}</div> @endif

    <div class="row">
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Parking Zones</div><div class="hm-kpi-value">{{ $valet['zones_count'] }}</div><div class="hm-kpi-sub">Configured hotel areas</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Today Tickets</div><div class="hm-kpi-value">{{ $valet['today_tickets'] }}</div><div class="hm-kpi-sub">New valet entries</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Active Vehicles</div><div class="hm-kpi-value">{{ $valet['active_tickets'] }}</div><div class="hm-kpi-sub">Parked / retrieving</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Today Revenue</div><div class="hm-kpi-value">{{ number_format($valet['today_revenue'], 2) }}</div><div class="hm-kpi-sub">Valet collections</div></div></div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Create Parking Zone</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hotel-management.valet.zone') }}">
                        @csrf
                        <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                            <div class="form-group"><label>Zone Code</label><input name="zone_code" class="form-control" required placeholder="A1"></div>
                            <div class="form-group"><label>Zone Name</label><input name="zone_name" class="form-control" required placeholder="Main Porch"></div>
                            <div class="form-group"><label>Capacity</label><input type="number" name="capacity" class="form-control" value="0"></div>
                            <div class="form-group"><label>Active</label><select name="is_active" class="form-control"><option value="1">Yes</option><option value="0">No</option></select></div>
                        </div>
                        <div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="3"></textarea></div>
                        <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-save"></i> Save Zone</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Create Valet Ticket</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hotel-management.valet.ticket') }}">
                        @csrf
                        <div class="hm-form-grid" style="grid-template-columns:repeat(4,minmax(120px,1fr))">
                            <div class="form-group"><label>Ticket No</label><input name="ticket_no" class="form-control" placeholder="Auto if blank"></div>
                            <div class="form-group"><label>Date</label><input type="date" name="ticket_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
                            <div class="form-group"><label>Zone</label><select name="zone_id" class="form-control"><option value="">Select</option>@foreach($valet['zones'] as $zone)<option value="{{ $zone->id }}">{{ $zone->zone_code }} - {{ $zone->zone_name }}</option>@endforeach</select></div>
                            <div class="form-group"><label>Room No</label><input name="room_no" class="form-control"></div>
                            <div class="form-group"><label>Guest Name</label><input name="guest_name" class="form-control"></div>
                            <div class="form-group"><label>Mobile</label><input name="mobile" class="form-control"></div>
                            <div class="form-group"><label>Vehicle No</label><input name="vehicle_no" class="form-control" required></div>
                            <div class="form-group"><label>Vehicle Type</label><input name="vehicle_type" class="form-control" placeholder="Car / Van / SUV"></div>
                            <div class="form-group"><label>Colour</label><input name="vehicle_colour" class="form-control"></div>
                            <div class="form-group"><label>Key Tag No</label><input name="key_tag_no" class="form-control"></div>
                            <div class="form-group"><label>Slot</label><input name="parked_slot" class="form-control"></div>
                            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="parked">Parked</option><option value="requested">Requested</option><option value="retrieving">Retrieving</option></select></div>
                            <div class="form-group"><label>In Time</label><input type="time" name="check_in_time" class="form-control" value="{{ date('H:i') }}"></div>
                            <div class="form-group"><label>Expected Out</label><input type="time" name="expected_out_time" class="form-control"></div>
                            <div class="form-group"><label>Driver</label><input name="driver_name" class="form-control"></div>
                            <div class="form-group"><label>Rate</label><input type="number" step="0.01" name="rate" class="form-control" value="0"></div>
                        </div>
                        <div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
                        <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-ticket"></i> Save Ticket</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Valet Ticket Register</h3></div>
        <div class="box-body">
            <div class="hm-toolbar"><input class="form-control hm-search-input" placeholder="Search valet ticket" style="max-width:260px"><button class="btn hm-btn-csv">CSV</button><button class="btn hm-btn-excel">Excel</button><button class="btn hm-btn-pdf">PDF</button><button class="btn hm-btn-print">Print</button><button class="btn hm-btn-col">Column Visibility</button></div>
            <div class="table-responsive"><table class="table hm-table"><thead><tr><th>ID</th><th>Ticket</th><th>Date</th><th>Guest</th><th>Room</th><th>Vehicle</th><th>Zone</th><th>Slot</th><th>In</th><th>Out</th><th>Rate</th><th>Paid</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                @forelse($valet['tickets'] as $row)
                    <tr>
                        <td>{{ $row->id }}</td><td>{{ $row->ticket_no }}</td><td>{{ $row->ticket_date }}</td><td>{{ $row->guest_name }}</td><td>{{ $row->room_no }}</td><td>{{ $row->vehicle_no }} {{ $row->vehicle_type ? ' / '.$row->vehicle_type : '' }}</td><td>{{ $row->zone_code ?? '-' }}</td><td>{{ $row->parked_slot }}</td><td>{{ $row->check_in_time }}</td><td>{{ $row->retrieved_time }}</td><td>{{ number_format($row->net_amount ?? 0, 2) }}</td><td>{{ number_format($row->paid_amount ?? 0, 2) }}</td><td><span class="hm-badge {{ $row->status ?? '' }}">{{ $row->status }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('hotel-management.valet.status', $row->id) }}" class="form-inline" style="margin-bottom:5px">
                                @csrf
                                <select name="status" class="form-control input-sm"><option value="requested">Requested</option><option value="retrieving">Retrieving</option><option value="released">Released</option><option value="cancelled">Cancelled</option></select>
                                <input type="time" name="retrieved_time" class="form-control input-sm" style="width:95px">
                                <button class="btn btn-xs hm-btn-edit">Update</button>
                            </form>
                            <form method="POST" action="{{ route('hotel-management.valet.payment', $row->id) }}" class="form-inline">
                                @csrf
                                <select name="payment_method" class="form-control input-sm"><option value="cash">Cash</option><option value="card">Card</option><option value="room_charge">Room Charge</option></select>
                                <input type="number" step="0.01" name="paid_amount" class="form-control input-sm" placeholder="Amount" style="width:95px" required>
                                <input name="payment_reference" class="form-control input-sm" placeholder="Ref" style="width:80px">
                                <button class="btn btn-xs hm-btn-add">Pay</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="14"><div class="hm-empty">No valet tickets found yet.</div></td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Parking Zones</h3></div><div class="box-body"><div class="table-responsive"><table class="table hm-table"><thead><tr><th>Code</th><th>Name</th><th>Capacity</th><th>Active</th><th>Remarks</th></tr></thead><tbody>@forelse($valet['zones'] as $zone)<tr><td>{{ $zone->zone_code }}</td><td>{{ $zone->zone_name }}</td><td>{{ $zone->capacity }}</td><td>{{ !empty($zone->is_active) ? 'Yes' : 'No' }}</td><td>{{ $zone->remarks }}</td></tr>@empty<tr><td colspan="5"><div class="hm-empty">No parking zones configured yet.</div></td></tr>@endforelse</tbody></table></div></div></div></div>
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Latest Valet Payments</h3></div><div class="box-body"><div class="table-responsive"><table class="table hm-table"><thead><tr><th>Date</th><th>Ticket</th><th>Method</th><th>Amount</th><th>Reference</th></tr></thead><tbody>@forelse($valet['payments'] as $pay)<tr><td>{{ $pay->payment_date }}</td><td>{{ $pay->ticket_id }}</td><td>{{ $pay->payment_method }}</td><td>{{ number_format($pay->paid_amount,2) }}</td><td>{{ $pay->payment_reference }}</td></tr>@empty<tr><td colspan="5"><div class="hm-empty">No valet payments posted yet.</div></td></tr>@endforelse</tbody></table></div><ul>@foreach($valet['notes'] as $note)<li>{{ $note }}</li>@endforeach</ul></div></div></div>
    </div>
</section>
@endsection
