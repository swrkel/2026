@extends('hotelmanagement::layouts.app')

@section('hotel_content')
<section class="content-header">
    <h1>Laundry <small>Guest laundry services, status flow, payment and folio posting</small></h1>
</section>
<section class="content">
    @include('hotelmanagement::partials.nav')
    @if(session('status')) <div class="alert alert-success hm-alert">{{ session('status') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger hm-alert">{{ session('error') }}</div> @endif

    <div class="row">
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Services</div><div class="hm-kpi-value">{{ $laundry['services_count'] }}</div><div class="hm-kpi-sub">Laundry items</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Today Orders</div><div class="hm-kpi-value">{{ $laundry['today_orders'] }}</div><div class="hm-kpi-sub">New orders today</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Pending</div><div class="hm-kpi-value">{{ $laundry['pending_count'] }}</div><div class="hm-kpi-sub">In process / ready</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Today Value</div><div class="hm-kpi-value">{{ number_format($laundry['today_revenue'], 2) }}</div><div class="hm-kpi-sub">Laundry revenue</div></div></div>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Laundry Service Setup</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hotel-management.laundry.service') }}">
                        @csrf
                        <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(140px,1fr))">
                            <div class="form-group"><label>Service Code</label><input name="service_code" class="form-control" required placeholder="LD-001"></div>
                            <div class="form-group"><label>Service Name</label><input name="service_name" class="form-control" required placeholder="Shirt Wash & Iron"></div>
                            <div class="form-group"><label>Category</label><input name="category" class="form-control" placeholder="Wash / Press / Dry Clean"></div>
                            <div class="form-group"><label>Unit</label><input name="unit" class="form-control" placeholder="Piece / Kg"></div>
                            <div class="form-group"><label>Standard Rate</label><input type="number" step="0.0001" name="standard_rate" class="form-control" value="0"></div>
                            <div class="form-group"><label>Express Rate</label><input type="number" step="0.0001" name="express_rate" class="form-control" value="0"></div>
                            <div class="form-group"><label>Active</label><select name="is_active" class="form-control"><option value="1">Yes</option><option value="0">No</option></select></div>
                        </div>
                        <div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="3"></textarea></div>
                        <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-save"></i> Save Service</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Record Laundry Order</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hotel-management.laundry.order') }}">
                        @csrf
                        <div class="hm-form-grid" style="grid-template-columns:repeat(4,minmax(120px,1fr))">
                            <div class="form-group"><label>Order No</label><input name="order_no" class="form-control" placeholder="Auto if blank"></div>
                            <div class="form-group"><label>Date</label><input type="date" name="order_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
                            <div class="form-group"><label>Room ID</label><input type="number" name="room_id" class="form-control"></div>
                            <div class="form-group"><label>Room No</label><input name="room_no" class="form-control"></div>
                            <div class="form-group"><label>Reservation ID</label><input type="number" name="reservation_id" class="form-control"></div>
                            <div class="form-group"><label>Folio ID</label><input type="number" name="folio_id" class="form-control" placeholder="For room charge"></div>
                            <div class="form-group"><label>Guest Name</label><input name="guest_name" class="form-control"></div>
                            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="received">Received</option><option value="washing">Washing</option><option value="pressing">Pressing</option><option value="ready">Ready</option></select></div>
                            <div class="form-group"><label>Service</label><select name="service_id" class="form-control" required><option value="">Select</option>@foreach($laundry['services'] as $service)<option value="{{ $service->id }}">{{ $service->service_code }} - {{ $service->service_name }}</option>@endforeach</select></div>
                            <div class="form-group"><label>Qty</label><input type="number" step="0.0001" name="qty" class="form-control" value="1" required></div>
                            <div class="form-group"><label>Rate Type</label><select name="rate_type" class="form-control"><option value="standard">Standard</option><option value="express">Express</option></select></div>
                            <div class="form-group"><label>Unit Price</label><input type="number" step="0.0001" name="unit_price" class="form-control" placeholder="Use setup rate if blank"></div>
                            <div class="form-group"><label>Discount</label><input type="number" step="0.0001" name="discount_amount" class="form-control" value="0"></div>
                            <div class="form-group"><label>Tax</label><input type="number" step="0.0001" name="tax_amount" class="form-control" value="0"></div>
                            <div class="form-group"><label>Pickup Time</label><input name="pickup_time" class="form-control" placeholder="10:00 AM"></div>
                            <div class="form-group"><label>Delivery Time</label><input name="delivery_time" class="form-control" placeholder="06:00 PM"></div>
                        </div>
                        <div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
                        <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-plus"></i> Save Order</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Laundry Order Register</h3></div>
        <div class="box-body">
            <div class="hm-toolbar"><input class="form-control hm-search-input" placeholder="Search laundry orders" style="max-width:260px"><button class="btn hm-btn-csv">CSV</button><button class="btn hm-btn-excel">Excel</button><button class="btn hm-btn-pdf">PDF</button><button class="btn hm-btn-print">Print</button><button class="btn hm-btn-col">Column Visibility</button></div>
            <div class="table-responsive"><table class="table hm-table"><thead><tr><th>ID</th><th>No</th><th>Date</th><th>Room</th><th>Guest</th><th>Service</th><th>Qty</th><th>Rate</th><th>Discount</th><th>Tax</th><th>Total</th><th>Paid</th><th>Balance</th><th>Folio</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                @forelse($laundry['orders'] as $row)
                    <tr>
                        <td>{{ $row->id }}</td><td>{{ $row->order_no }}</td><td>{{ $row->order_date }}</td><td>{{ $row->room_no }}</td><td>{{ $row->guest_name }}</td><td>{{ $row->service_name }}</td><td>{{ number_format($row->qty, 4) }}</td><td>{{ number_format($row->unit_price, 4) }}</td><td>{{ number_format($row->discount_amount, 4) }}</td><td>{{ number_format($row->tax_amount, 4) }}</td><td>{{ number_format($row->total_amount, 4) }}</td><td>{{ number_format($row->paid_amount, 4) }}</td><td>{{ number_format($row->balance_amount, 4) }}</td><td>{{ $row->folio_id ?: '-' }}</td><td><span class="hm-badge {{ $row->status ?? '' }}">{{ $row->status }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('hotel-management.laundry.status', $row->id) }}" class="form-inline" style="margin-bottom:5px">
                                @csrf
                                <select name="status" class="form-control input-sm"><option value="received">Received</option><option value="washing">Washing</option><option value="pressing">Pressing</option><option value="ready">Ready</option><option value="delivered">Delivered</option><option value="cancelled">Cancelled</option></select>
                                <button class="btn btn-xs hm-btn-edit">Update</button>
                            </form>
                            <form method="POST" action="{{ route('hotel-management.laundry.payment', $row->id) }}" class="form-inline" style="margin-bottom:5px">
                                @csrf
                                <select name="payment_method" class="form-control input-sm"><option value="cash">Cash</option><option value="card">Card</option><option value="bank">Bank</option></select>
                                <input name="paid_amount" type="number" step="0.0001" class="form-control input-sm" style="width:90px" value="{{ max(0, $row->balance_amount) }}">
                                <input name="payment_reference" class="form-control input-sm" style="width:100px" placeholder="Ref">
                                <button class="btn btn-xs hm-btn-excel" {{ ($row->balance_amount ?? 0) <= 0 ? 'disabled' : '' }}>Pay</button>
                            </form>
                            <form method="POST" action="{{ route('hotel-management.laundry.post-to-folio', $row->id) }}" class="form-inline">
                                @csrf
                                <button class="btn btn-xs hm-btn-add" {{ empty($row->folio_id) || ($row->status ?? '') === 'posted' ? 'disabled' : '' }}><i class="fa fa-share"></i> Post Folio</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="16"><div class="hm-empty">No laundry orders recorded yet.</div></td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Laundry Service Register</h3></div><div class="box-body"><div class="table-responsive"><table class="table hm-table"><thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Unit</th><th>Standard</th><th>Express</th><th>Status</th></tr></thead><tbody>@forelse($laundry['services'] as $service)<tr><td>{{ $service->service_code }}</td><td>{{ $service->service_name }}</td><td>{{ $service->category }}</td><td>{{ $service->unit }}</td><td>{{ number_format($service->standard_rate,4) }}</td><td>{{ number_format($service->express_rate,4) }}</td><td>{{ !empty($service->is_active) ? 'Active' : 'Inactive' }}</td></tr>@empty<tr><td colspan="7"><div class="hm-empty">No laundry services configured yet.</div></td></tr>@endforelse</tbody></table></div></div></div></div>
        <div class="col-md-4"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Controls</h3></div><div class="box-body"><div class="hm-kpi"><div class="hm-kpi-label">Unpaid Orders</div><div class="hm-kpi-value">{{ $laundry['unpaid_count'] }}</div><div class="hm-kpi-sub">Balance pending</div></div><ul>@foreach($laundry['notes'] as $note)<li>{{ $note }}</li>@endforeach</ul></div></div></div>
    </div>
</section>
@endsection
