@extends('hotelmanagement::layouts.app')
@section('hotel_content')
<section class="content-header">
    <h1>Room Service <small>In-room dining requests, kitchen workflow and folio posting</small></h1>
</section>
<section class="content">
@include('hotelmanagement::partials.nav')
@if(session('status'))<div class="alert alert-success hm-alert">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger hm-alert"><strong>Please check:</strong> {{ $errors->first() }}</div>@endif

<div class="row">
    <div class="col-md-5">
        <div class="box hm-card">
            <div class="box-header hm-card-header"><h3 class="box-title">New Room Service Order</h3></div>
            <div class="box-body">
                <form method="POST" action="{{ route('hotel-management.room-service.store') }}">
                    @csrf
                    <div class="hm-form-grid hm-form-grid-2">
                        <div class="form-group">
                            <label>Order Date</label>
                            <input name="order_date" type="date" value="{{ date('Y-m-d') }}" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Delivery Time</label>
                            <input name="delivery_time" type="time" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Room</label>
                            <select name="room_id" class="form-control" required>
                                <option value="">Please Select</option>
                                @foreach($rooms as $r)<option value="{{ $r->id }}">{{ $r->room_no }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Folio</label>
                            <select name="folio_id" class="form-control">
                                <option value="">Please Select</option>
                                @foreach($folios as $f)<option value="{{ $f->id }}">{{ $f->folio_no }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-group hm-span-2">
                            <label>Guest Name</label>
                            <input name="guest_name" type="text" class="form-control" placeholder="In-house guest name">
                        </div>
                        <div class="form-group">
                            <label>Menu Item</label>
                            <select name="menu_item_id" class="form-control" required>
                                <option value="">Please Select</option>
                                @foreach($menuItems as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Quantity</label>
                            <input name="quantity" type="number" step="0.0001" value="1.0000" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Service Charge</label>
                            <input name="service_charge" type="number" step="0.0001" value="0.0000" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Tax</label>
                            <input name="tax_amount" type="number" step="0.0001" value="0.0000" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Payment Mode</label>
                            <select name="payment_mode" class="form-control" required>
                                <option value="room">Charge to Room</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Priority</label>
                            <select name="priority" class="form-control">
                                <option value="normal">Normal</option>
                                <option value="urgent">Urgent</option>
                                <option value="vip">VIP</option>
                            </select>
                        </div>
                        <div class="form-group hm-span-2">
                            <label>Kitchen / Delivery Note</label>
                            <input name="note" type="text" class="form-control" placeholder="No onion, child meal, delivery instruction etc.">
                        </div>
                    </div>
                    <br><button class="btn hm-btn-add">Save Room Service Order</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="box hm-card">
            <div class="box-header hm-card-header"><h3 class="box-title">Kitchen & Delivery Board</h3></div>
            <div class="box-body">
                @include('hotelmanagement::partials.toolbar')
                <div class="table-responsive">
                    <table class="table hm-table table-striped">
                        <thead><tr><th>No</th><th>Date/Time</th><th>Room/Folio</th><th>Item</th><th class="text-right">Total</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                        @forelse($orders as $row)
                            <tr>
                                <td>{{ $row->room_service_no }}</td>
                                <td>{{ $row->order_date }} {{ $row->delivery_time ? ' / '.$row->delivery_time : '' }}</td>
                                <td>{{ $row->room_no ?? ('#'.$row->room_id) }} {{ $row->folio_no ? '/ '.$row->folio_no : '' }}<br><small>{{ $row->guest_name }}</small></td>
                                <td>{{ $row->item_name }}<br><small>Qty: {{ number_format((float)$row->quantity, 4) }} | {{ ucfirst($row->priority ?? 'normal') }}</small></td>
                                <td class="text-right">{{ number_format((float)$row->grand_total, 4) }}</td>
                                <td><span class="hm-badge {{ $row->status }}">{{ ucfirst($row->status) }}</span></td>
                                <td>
                                    <form method="POST" action="{{ route('hotel-management.room-service.status', $row->id) }}" class="hm-inline-form">
                                        @csrf
                                        <select name="status" class="form-control input-sm" onchange="this.form.submit()">
                                            @foreach(['ordered','preparing','ready','delivered','cancelled'] as $status)
                                                <option value="{{ $status }}" {{ $row->status === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><div class="hm-empty">No room service orders found.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</section>
@endsection
