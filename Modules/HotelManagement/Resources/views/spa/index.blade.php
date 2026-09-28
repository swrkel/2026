@extends('hotelmanagement::layouts.app')

@section('hotel_content')
<section class="content-header">
    <h1>Spa & Wellness <small>Services, appointments, payments and room charge bridge</small></h1>
</section>
<section class="content">
    @include('hotelmanagement::partials.nav')
    @if(session('status')) <div class="alert alert-success hm-alert">{{ session('status') }}</div> @endif

    <div class="row">
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Services</div><div class="hm-kpi-value">{{ $spa['services_count'] }}</div><div class="hm-kpi-sub">Active wellness items</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Today</div><div class="hm-kpi-value">{{ $spa['today_appointments'] }}</div><div class="hm-kpi-sub">Appointments</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Pending</div><div class="hm-kpi-value">{{ $spa['pending_count'] }}</div><div class="hm-kpi-sub">Need action</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Today Revenue</div><div class="hm-kpi-value">{{ number_format($spa['today_revenue'], 2) }}</div><div class="hm-kpi-sub">Posted payments</div></div></div>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Create Spa Service</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hotel-management.spa.service') }}">
                        @csrf
                        <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(140px,1fr))">
                            <div class="form-group"><label>Service Name</label><input name="name" class="form-control" required placeholder="Aroma Massage"></div>
                            <div class="form-group"><label>Code</label><input name="code" class="form-control" required placeholder="SPA001"></div>
                            <div class="form-group"><label>Category</label><input name="category" class="form-control" placeholder="Massage / Salon / Wellness"></div>
                            <div class="form-group"><label>Duration Minutes</label><input type="number" name="duration_minutes" class="form-control" value="60"></div>
                            <div class="form-group"><label>Price</label><input type="number" step="0.01" name="price" class="form-control" value="0"></div>
                            <div class="form-group"><label>Active</label><select name="is_active" class="form-control"><option value="1">Yes</option><option value="0">No</option></select></div>
                        </div>
                        <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3" placeholder="Service inclusions, therapist notes, package description"></textarea></div>
                        <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-save"></i> Save Service</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Book Spa Appointment</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hotel-management.spa.appointment') }}">
                        @csrf
                        <div class="hm-form-grid" style="grid-template-columns:repeat(4,minmax(120px,1fr))">
                            <div class="form-group"><label>Appointment No</label><input name="appointment_no" class="form-control" placeholder="Auto if blank"></div>
                            <div class="form-group"><label>Service</label><select name="service_id" class="form-control"><option value="">Select</option>@foreach($spa['services'] as $service)<option value="{{ $service->id }}">{{ $service->name }}</option>@endforeach</select></div>
                            <div class="form-group"><label>Guest ID</label><input type="number" name="guest_id" class="form-control"></div>
                            <div class="form-group"><label>Folio ID</label><input type="number" name="folio_id" class="form-control" placeholder="For room charge"></div>
                            <div class="form-group"><label>Guest Name</label><input name="guest_name" class="form-control" required></div>
                            <div class="form-group"><label>Mobile</label><input name="mobile" class="form-control"></div>
                            <div class="form-group"><label>Room No</label><input name="room_no" class="form-control"></div>
                            <div class="form-group"><label>Therapist</label><input name="therapist_name" class="form-control"></div>
                            <div class="form-group"><label>Date</label><input type="date" name="appointment_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
                            <div class="form-group"><label>Start</label><input type="time" name="start_time" class="form-control"></div>
                            <div class="form-group"><label>End</label><input type="time" name="end_time" class="form-control"></div>
                            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="booked">Booked</option><option value="confirmed">Confirmed</option><option value="in_progress">In Progress</option><option value="completed">Completed</option></select></div>
                            <div class="form-group"><label>Amount</label><input type="number" step="0.01" name="amount" class="form-control" value="0"></div>
                            <div class="form-group"><label>Discount</label><input type="number" step="0.01" name="discount_amount" class="form-control" value="0"></div>
                            <div class="form-group"><label>Tax</label><input type="number" step="0.01" name="tax_amount" class="form-control" value="0"></div>
                        </div>
                        <div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
                        <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-calendar-plus-o"></i> Save Appointment</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Appointment Register</h3></div>
        <div class="box-body">
            <div class="hm-toolbar"><input class="form-control hm-search-input" placeholder="Search appointment" style="max-width:260px"><button class="btn hm-btn-csv">CSV</button><button class="btn hm-btn-excel">Excel</button><button class="btn hm-btn-pdf">PDF</button><button class="btn hm-btn-print">Print</button><button class="btn hm-btn-col">Column Visibility</button></div>
            <div class="table-responsive"><table class="table hm-table"><thead><tr><th>ID</th><th>No</th><th>Date</th><th>Time</th><th>Guest</th><th>Room</th><th>Service</th><th>Therapist</th><th>Net</th><th>Paid</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                @forelse($spa['appointments'] as $row)
                    <tr>
                        <td>{{ $row->id }}</td><td>{{ $row->appointment_no }}</td><td>{{ $row->appointment_date }}</td><td>{{ $row->start_time }} - {{ $row->end_time }}</td><td>{{ $row->guest_name }}</td><td>{{ $row->room_no }}</td><td>{{ $row->service_name ?? '-' }}</td><td>{{ $row->therapist_name }}</td><td>{{ number_format($row->net_amount ?? 0, 2) }}</td><td>{{ number_format($row->paid_amount ?? 0, 2) }}</td><td><span class="hm-badge {{ $row->status ?? '' }}">{{ $row->status }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('hotel-management.spa.status', $row->id) }}" class="form-inline" style="margin-bottom:5px">
                                @csrf
                                <select name="status" class="form-control input-sm"><option value="confirmed">Confirmed</option><option value="in_progress">In Progress</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select>
                                <button class="btn btn-xs hm-btn-edit">Update</button>
                            </form>
                            <form method="POST" action="{{ route('hotel-management.spa.payment', $row->id) }}" class="form-inline">
                                @csrf
                                <select name="method" class="form-control input-sm"><option value="cash">Cash</option><option value="card">Card</option><option value="room_charge">Room Charge</option><option value="bank">Bank</option></select>
                                <input type="number" step="0.01" name="amount" class="form-control input-sm" placeholder="Amount" style="width:95px" required>
                                <input name="reference_no" class="form-control input-sm" placeholder="Ref" style="width:80px">
                                <button class="btn btn-xs hm-btn-add">Pay</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12"><div class="hm-empty">No spa appointments found yet.</div></td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Spa Services</h3></div><div class="box-body"><div class="table-responsive"><table class="table hm-table"><thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Duration</th><th>Price</th><th>Active</th></tr></thead><tbody>@forelse($spa['services'] as $service)<tr><td>{{ $service->code }}</td><td>{{ $service->name }}</td><td>{{ $service->category }}</td><td>{{ $service->duration_minutes }} mins</td><td>{{ number_format($service->price,2) }}</td><td>{{ !empty($service->is_active) ? 'Yes' : 'No' }}</td></tr>@empty<tr><td colspan="6"><div class="hm-empty">No services configured yet.</div></td></tr>@endforelse</tbody></table></div></div></div></div>
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Latest Payments</h3></div><div class="box-body"><div class="table-responsive"><table class="table hm-table"><thead><tr><th>Date</th><th>Appointment</th><th>Method</th><th>Amount</th><th>Reference</th></tr></thead><tbody>@forelse($spa['payments'] as $pay)<tr><td>{{ $pay->payment_date }}</td><td>{{ $pay->appointment_id }}</td><td>{{ $pay->method }}</td><td>{{ number_format($pay->amount,2) }}</td><td>{{ $pay->reference_no }}</td></tr>@empty<tr><td colspan="5"><div class="hm-empty">No spa payments posted yet.</div></td></tr>@endforelse</tbody></table></div><ul>@foreach($spa['notes'] as $note)<li>{{ $note }}</li>@endforeach</ul></div></div></div>
    </div>
</section>
@endsection
