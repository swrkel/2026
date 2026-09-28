@extends('hotelmanagement::layouts.app')
@section('hotel_content')
<section class="content-header">
    <h1>Hotel POS <small>Restaurant, minibar, service charges and room posting</small></h1>
</section>
<section class="content">
@include('hotelmanagement::partials.nav')
@if(session('status'))<div class="alert alert-success hm-alert">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger hm-alert"><strong>Please check:</strong> {{ $errors->first() }}</div>@endif

<div class="row">
    <div class="col-md-7">
        <div class="box hm-card">
            <div class="box-header hm-card-header"><h3 class="box-title">Post POS Order</h3></div>
            <div class="box-body">
                <form method="POST" action="{{ route('hotel-management.pos.store') }}">
                    @csrf
                    <input type="hidden" name="form_type" value="pos_order">
                    <div class="hm-form-grid">
                        <div class="form-group">
                            <label>Order Date</label>
                            <input name="order_date" type="date" value="{{ date('Y-m-d') }}" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Menu Item</label>
                            <select name="menu_item_id" class="form-control" required>
                                <option value="">Please Select</option>
                                @foreach($menuItems as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }} - {{ number_format((float) $item->price, 4) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Quantity</label>
                            <input name="quantity" type="number" step="0.0001" value="1.0000" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Guest Name</label>
                            <input name="guest_name" type="text" class="form-control" placeholder="Walk-in or in-house guest">
                        </div>
                        <div class="form-group">
                            <label>Room</label>
                            <select name="room_id" class="form-control">
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
                        <div class="form-group">
                            <label>Discount</label>
                            <input name="discount_amount" type="number" step="0.0001" value="0.0000" class="form-control">
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
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="room">Charge to Room</option>
                            </select>
                        </div>
                        <div class="form-group hm-span-2">
                            <label>Note</label>
                            <input name="note" type="text" class="form-control" placeholder="Kitchen/service note">
                        </div>
                    </div>
                    <br><button class="btn hm-btn-add">Post POS Order</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="box hm-card">
            <div class="box-header hm-card-header"><h3 class="box-title">Add POS Menu Item</h3></div>
            <div class="box-body">
                <form method="POST" action="{{ route('hotel-management.pos.store') }}">
                    @csrf
                    <input type="hidden" name="form_type" value="menu_item">
                    <div class="hm-form-grid hm-form-grid-2">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id" class="form-control">
                                <option value="">New Category Below</option>
                                @foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>New Category</label>
                            <input name="category_name" type="text" class="form-control" placeholder="Restaurant / Laundry">
                        </div>
                        <div class="form-group">
                            <label>Item Code</label>
                            <input name="item_code" type="text" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Item Name</label>
                            <input name="name" type="text" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Price</label>
                            <input name="price" type="number" step="0.0001" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Unit</label>
                            <input name="unit" type="text" value="unit" class="form-control">
                        </div>
                    </div>
                    <br><button class="btn hm-btn-add">Save Menu Item</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="box hm-card">
    <div class="box-header hm-card-header"><h3 class="box-title">Latest POS Orders</h3></div>
    <div class="box-body">
        @include('hotelmanagement::partials.toolbar')
        <div class="table-responsive">
            <table class="table hm-table table-striped">
                <thead><tr><th>Order No</th><th>Date</th><th>Guest</th><th>Room/Folio</th><th>Payment</th><th class="text-right">Total</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($orders as $row)
                    <tr>
                        <td>{{ $row->order_no ?? '' }}</td>
                        <td>{{ $row->order_date ?? '' }}</td>
                        <td>{{ $row->guest_name ?? 'Walk-in' }}</td>
                        <td>{{ $row->room_id ? 'Room #'.$row->room_id : '-' }} {{ $row->folio_id ? '/ Folio #'.$row->folio_id : '' }}</td>
                        <td>{{ ucfirst($row->payment_mode ?? '-') }}</td>
                        <td class="text-right">{{ number_format((float) ($row->grand_total ?? 0), 4) }}</td>
                        <td><span class="hm-badge {{ $row->status ?? '' }}">{{ ucfirst(str_replace('_',' ', $row->status ?? '-')) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="hm-empty">No POS orders found.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="box hm-card">
    <div class="box-header hm-card-header"><h3 class="box-title">Room Charges</h3></div>
    <div class="box-body">
        <div class="table-responsive">
            <table class="table hm-table table-striped">
                <thead><tr><th>Charge Ref</th><th>Type</th><th>Description</th><th class="text-right">Amount</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($charges as $row)
                    <tr>
                        <td>{{ $row->charge_ref ?? '' }}</td>
                        <td>{{ $row->charge_type ?? '' }}</td>
                        <td>{{ $row->description ?? '' }}</td>
                        <td class="text-right">{{ number_format((float) ($row->amount ?? 0), 4) }}</td>
                        <td>{{ $row->charge_date ?? '' }}</td>
                        <td><span class="hm-badge {{ $row->status ?? '' }}">{{ ucfirst($row->status ?? '-') }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="hm-empty">No charges found.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</section>
@endsection
