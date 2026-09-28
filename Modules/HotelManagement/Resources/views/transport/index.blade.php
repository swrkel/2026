@extends('hotelmanagement::layouts.app')

@section('hotel_content')
<section class="content-header">
    <h1>Hotel Transport <small>Airport transfers, local trips, vehicles, payments and room charge bridge</small></h1>
</section>
<section class="content">
    @include('hotelmanagement::partials.nav')
    @if(session('status')) <div class="alert alert-success hm-alert">{{ session('status') }}</div> @endif

    <div class="row">
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Vehicles</div><div class="hm-kpi-value">{{ $transport['vehicles_count'] }}</div><div class="hm-kpi-sub">Active fleet records</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Today Trips</div><div class="hm-kpi-value">{{ $transport['today_bookings'] }}</div><div class="hm-kpi-sub">Pickup/drop bookings</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Pending</div><div class="hm-kpi-value">{{ $transport['pending_count'] }}</div><div class="hm-kpi-sub">Need driver/action</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Today Revenue</div><div class="hm-kpi-value">{{ number_format($transport['today_revenue'], 2) }}</div><div class="hm-kpi-sub">Posted payments</div></div></div>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Create Vehicle / Driver</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hotel-management.transport.vehicle') }}">
                        @csrf
                        <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(140px,1fr))">
                            <div class="form-group"><label>Vehicle No</label><input name="vehicle_no" class="form-control" required placeholder="CAB-001"></div>
                            <div class="form-group"><label>Vehicle Type</label><input name="vehicle_type" class="form-control" placeholder="Car / Van / Coach"></div>
                            <div class="form-group"><label>Driver Name</label><input name="driver_name" class="form-control"></div>
                            <div class="form-group"><label>Driver Mobile</label><input name="driver_mobile" class="form-control"></div>
                            <div class="form-group"><label>Seats</label><input type="number" name="seating_capacity" class="form-control" value="0"></div>
                            <div class="form-group"><label>Base Rate</label><input type="number" step="0.01" name="base_rate" class="form-control" value="0"></div>
                            <div class="form-group"><label>Active</label><select name="is_active" class="form-control"><option value="1">Yes</option><option value="0">No</option></select></div>
                        </div>
                        <div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="3" placeholder="Insurance, permit, driver note"></textarea></div>
                        <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-save"></i> Save Vehicle</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Create Transport Booking</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hotel-management.transport.booking') }}">
                        @csrf
                        <div class="hm-form-grid" style="grid-template-columns:repeat(4,minmax(120px,1fr))">
                            <div class="form-group"><label>Booking No</label><input name="booking_no" class="form-control" placeholder="Auto if blank"></div>
                            <div class="form-group"><label>Vehicle</label><select name="vehicle_id" class="form-control"><option value="">Select</option>@foreach($transport['vehicles'] as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->vehicle_no }} - {{ $vehicle->vehicle_type }}</option>@endforeach</select></div>
                            <div class="form-group"><label>Reservation ID</label><input type="number" name="reservation_id" class="form-control"></div>
                            <div class="form-group"><label>Folio ID</label><input type="number" name="folio_id" class="form-control" placeholder="For room charge"></div>
                            <div class="form-group"><label>Guest Name</label><input name="guest_name" class="form-control" required></div>
                            <div class="form-group"><label>Mobile</label><input name="mobile" class="form-control"></div>
                            <div class="form-group"><label>Room No</label><input name="room_no" class="form-control"></div>
                            <div class="form-group"><label>Trip Type</label><select name="trip_type" class="form-control"><option value="airport_pickup">Airport Pickup</option><option value="airport_drop">Airport Drop</option><option value="local_transfer">Local Transfer</option><option value="tour">Tour</option></select></div>
                            <div class="form-group"><label>Pickup Date</label><input type="date" name="pickup_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
                            <div class="form-group"><label>Pickup Time</label><input type="time" name="pickup_time" class="form-control"></div>
                            <div class="form-group"><label>Flight No</label><input name="flight_no" class="form-control"></div>
                            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="requested">Requested</option><option value="confirmed">Confirmed</option><option value="assigned">Assigned</option><option value="started">Started</option></select></div>
                            <div class="form-group"><label>Driver</label><input name="driver_name" class="form-control"></div>
                            <div class="form-group"><label>Rate</label><input type="number" step="0.01" name="rate" class="form-control" value="0"></div>
                            <div class="form-group"><label>Discount</label><input type="number" step="0.01" name="discount_amount" class="form-control" value="0"></div>
                            <div class="form-group"><label>Tax</label><input type="number" step="0.01" name="tax_amount" class="form-control" value="0"></div>
                        </div>
                        <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(180px,1fr))">
                            <div class="form-group"><label>Pickup Location</label><input name="pickup_location" class="form-control" placeholder="Airport / Hotel / Address"></div>
                            <div class="form-group"><label>Drop Location</label><input name="drop_location" class="form-control" placeholder="Hotel / Airport / Address"></div>
                        </div>
                        <div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
                        <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-car"></i> Save Booking</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Transport Booking Register</h3></div>
        <div class="box-body">
            <div class="hm-toolbar"><input class="form-control hm-search-input" placeholder="Search transport booking" style="max-width:260px"><button class="btn hm-btn-csv">CSV</button><button class="btn hm-btn-excel">Excel</button><button class="btn hm-btn-pdf">PDF</button><button class="btn hm-btn-print">Print</button><button class="btn hm-btn-col">Column Visibility</button></div>
            <div class="table-responsive"><table class="table hm-table"><thead><tr><th>ID</th><th>No</th><th>Date</th><th>Time</th><th>Guest</th><th>Room</th><th>Trip</th><th>Vehicle</th><th>Pickup</th><th>Drop</th><th>Net</th><th>Paid</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                @forelse($transport['bookings'] as $row)
                    <tr>
                        <td>{{ $row->id }}</td><td>{{ $row->booking_no }}</td><td>{{ $row->pickup_date }}</td><td>{{ $row->pickup_time }}</td><td>{{ $row->guest_name }}</td><td>{{ $row->room_no }}</td><td>{{ $row->trip_type }}</td><td>{{ $row->vehicle_no ?? '-' }}</td><td>{{ $row->pickup_location }}</td><td>{{ $row->drop_location }}</td><td>{{ number_format($row->net_amount ?? 0, 2) }}</td><td>{{ number_format($row->paid_amount ?? 0, 2) }}</td><td><span class="hm-badge {{ $row->status ?? '' }}">{{ $row->status }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('hotel-management.transport.status', $row->id) }}" class="form-inline" style="margin-bottom:5px">
                                @csrf
                                <select name="status" class="form-control input-sm"><option value="confirmed">Confirmed</option><option value="assigned">Assigned</option><option value="started">Started</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select>
                                <button class="btn btn-xs hm-btn-edit">Update</button>
                            </form>
                            <form method="POST" action="{{ route('hotel-management.transport.payment', $row->id) }}" class="form-inline">
                                @csrf
                                <select name="method" class="form-control input-sm"><option value="cash">Cash</option><option value="card">Card</option><option value="room_charge">Room Charge</option><option value="bank">Bank</option></select>
                                <input type="number" step="0.01" name="amount" class="form-control input-sm" placeholder="Amount" style="width:95px" required>
                                <input name="reference_no" class="form-control input-sm" placeholder="Ref" style="width:80px">
                                <button class="btn btn-xs hm-btn-add">Pay</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="14"><div class="hm-empty">No transport bookings found yet.</div></td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Vehicle Register</h3></div><div class="box-body"><div class="table-responsive"><table class="table hm-table"><thead><tr><th>Vehicle</th><th>Type</th><th>Driver</th><th>Mobile</th><th>Seats</th><th>Base Rate</th><th>Active</th></tr></thead><tbody>@forelse($transport['vehicles'] as $vehicle)<tr><td>{{ $vehicle->vehicle_no }}</td><td>{{ $vehicle->vehicle_type }}</td><td>{{ $vehicle->driver_name }}</td><td>{{ $vehicle->driver_mobile }}</td><td>{{ $vehicle->seating_capacity }}</td><td>{{ number_format($vehicle->base_rate,2) }}</td><td>{{ !empty($vehicle->is_active) ? 'Yes' : 'No' }}</td></tr>@empty<tr><td colspan="7"><div class="hm-empty">No vehicles configured yet.</div></td></tr>@endforelse</tbody></table></div></div></div></div>
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Latest Payments</h3></div><div class="box-body"><div class="table-responsive"><table class="table hm-table"><thead><tr><th>Date</th><th>Booking</th><th>Method</th><th>Amount</th><th>Reference</th></tr></thead><tbody>@forelse($transport['payments'] as $pay)<tr><td>{{ $pay->payment_date }}</td><td>{{ $pay->booking_id }}</td><td>{{ $pay->method }}</td><td>{{ number_format($pay->amount,2) }}</td><td>{{ $pay->reference_no }}</td></tr>@empty<tr><td colspan="5"><div class="hm-empty">No transport payments posted yet.</div></td></tr>@endforelse</tbody></table></div><ul>@foreach($transport['notes'] as $note)<li>{{ $note }}</li>@endforeach</ul></div></div></div>
    </div>
</section>
@endsection
