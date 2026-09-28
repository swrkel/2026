@extends('hotelmanagement::layouts.app')

@section('hotel_content')
<section class="content-header">
    <h1>Mini Bar <small>Room consumption, stock control and folio posting</small></h1>
</section>
<section class="content">
    @include('hotelmanagement::partials.nav')
    @if(session('status')) <div class="alert alert-success hm-alert">{{ session('status') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger hm-alert">{{ session('error') }}</div> @endif

    <div class="row">
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Mini Bar Items</div><div class="hm-kpi-value">{{ $minibar['items_count'] }}</div><div class="hm-kpi-sub">Configured items</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Today Consumption</div><div class="hm-kpi-value">{{ $minibar['today_consumptions'] }}</div><div class="hm-kpi-sub">Room postings today</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Pending</div><div class="hm-kpi-value">{{ $minibar['pending_count'] }}</div><div class="hm-kpi-sub">Not posted yet</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Today Value</div><div class="hm-kpi-value">{{ number_format($minibar['today_revenue'], 2) }}</div><div class="hm-kpi-sub">Mini bar revenue</div></div></div>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Mini Bar Item Setup</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hotel-management.minibar.item') }}">
                        @csrf
                        <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(140px,1fr))">
                            <div class="form-group"><label>Item Code</label><input name="item_code" class="form-control" required placeholder="MB-001"></div>
                            <div class="form-group"><label>Item Name</label><input name="item_name" class="form-control" required placeholder="Water Bottle"></div>
                            <div class="form-group"><label>Category</label><input name="category" class="form-control" placeholder="Beverage / Snack"></div>
                            <div class="form-group"><label>Unit</label><input name="unit" class="form-control" placeholder="Bottle / Pack"></div>
                            <div class="form-group"><label>Selling Price</label><input type="number" step="0.0001" name="selling_price" class="form-control" value="0"></div>
                            <div class="form-group"><label>Cost Price</label><input type="number" step="0.0001" name="cost_price" class="form-control" value="0"></div>
                            <div class="form-group"><label>Current Stock</label><input type="number" step="0.0001" name="current_stock" class="form-control" value="0"></div>
                            <div class="form-group"><label>Reorder Level</label><input type="number" step="0.0001" name="reorder_level" class="form-control" value="0"></div>
                            <div class="form-group"><label>Active</label><select name="is_active" class="form-control"><option value="1">Yes</option><option value="0">No</option></select></div>
                        </div>
                        <div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="3"></textarea></div>
                        <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-save"></i> Save Item</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Record Room Consumption</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hotel-management.minibar.consumption') }}">
                        @csrf
                        <div class="hm-form-grid" style="grid-template-columns:repeat(4,minmax(120px,1fr))">
                            <div class="form-group"><label>No</label><input name="consumption_no" class="form-control" placeholder="Auto if blank"></div>
                            <div class="form-group"><label>Date</label><input type="date" name="consumption_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
                            <div class="form-group"><label>Room ID</label><input type="number" name="room_id" class="form-control"></div>
                            <div class="form-group"><label>Room No</label><input name="room_no" class="form-control"></div>
                            <div class="form-group"><label>Reservation ID</label><input type="number" name="reservation_id" class="form-control"></div>
                            <div class="form-group"><label>Folio ID</label><input type="number" name="folio_id" class="form-control" placeholder="For room charge"></div>
                            <div class="form-group"><label>Guest Name</label><input name="guest_name" class="form-control"></div>
                            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="draft">Draft</option><option value="confirmed">Confirmed</option></select></div>
                            <div class="form-group"><label>Item</label><select name="item_id" class="form-control" required><option value="">Select</option>@foreach($minibar['items'] as $item)<option value="{{ $item->id }}">{{ $item->item_code }} - {{ $item->item_name }} ({{ number_format($item->current_stock, 4) }})</option>@endforeach</select></div>
                            <div class="form-group"><label>Qty</label><input type="number" step="0.0001" name="qty" class="form-control" value="1" required></div>
                            <div class="form-group"><label>Unit Price</label><input type="number" step="0.0001" name="unit_price" class="form-control" placeholder="Use item price if blank"></div>
                            <div class="form-group"><label>Discount</label><input type="number" step="0.0001" name="discount_amount" class="form-control" value="0"></div>
                            <div class="form-group"><label>Tax</label><input type="number" step="0.0001" name="tax_amount" class="form-control" value="0"></div>
                        </div>
                        <div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
                        <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-plus"></i> Save Consumption</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Mini Bar Consumption Register</h3></div>
        <div class="box-body">
            <div class="hm-toolbar"><input class="form-control hm-search-input" placeholder="Search mini bar consumption" style="max-width:260px"><button class="btn hm-btn-csv">CSV</button><button class="btn hm-btn-excel">Excel</button><button class="btn hm-btn-pdf">PDF</button><button class="btn hm-btn-print">Print</button><button class="btn hm-btn-col">Column Visibility</button></div>
            <div class="table-responsive"><table class="table hm-table"><thead><tr><th>ID</th><th>No</th><th>Date</th><th>Room</th><th>Guest</th><th>Item</th><th>Qty</th><th>Unit</th><th>Discount</th><th>Tax</th><th>Total</th><th>Folio</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                @forelse($minibar['consumptions'] as $row)
                    <tr>
                        <td>{{ $row->id }}</td><td>{{ $row->consumption_no }}</td><td>{{ $row->consumption_date }}</td><td>{{ $row->room_no }}</td><td>{{ $row->guest_name }}</td><td>{{ $row->item_name }}</td><td>{{ number_format($row->qty, 4) }}</td><td>{{ number_format($row->unit_price, 4) }}</td><td>{{ number_format($row->discount_amount, 4) }}</td><td>{{ number_format($row->tax_amount, 4) }}</td><td>{{ number_format($row->total_amount, 4) }}</td><td>{{ $row->folio_id ?: '-' }}</td><td><span class="hm-badge {{ $row->status ?? '' }}">{{ $row->status }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('hotel-management.minibar.status', $row->id) }}" class="form-inline" style="margin-bottom:5px">
                                @csrf
                                <select name="status" class="form-control input-sm"><option value="draft">Draft</option><option value="confirmed">Confirmed</option><option value="cancelled">Cancelled</option></select>
                                <button class="btn btn-xs hm-btn-edit">Update</button>
                            </form>
                            <form method="POST" action="{{ route('hotel-management.minibar.post-to-folio', $row->id) }}" class="form-inline">
                                @csrf
                                <button class="btn btn-xs hm-btn-add" {{ empty($row->folio_id) || ($row->status ?? '') === 'posted' ? 'disabled' : '' }}><i class="fa fa-share"></i> Post Folio</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="14"><div class="hm-empty">No mini bar consumption recorded yet.</div></td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Mini Bar Item Register</h3></div><div class="box-body"><div class="table-responsive"><table class="table hm-table"><thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Unit</th><th>Selling</th><th>Stock</th><th>Reorder</th><th>Status</th></tr></thead><tbody>@forelse($minibar['items'] as $item)<tr><td>{{ $item->item_code }}</td><td>{{ $item->item_name }}</td><td>{{ $item->category }}</td><td>{{ $item->unit }}</td><td>{{ number_format($item->selling_price,4) }}</td><td>{{ number_format($item->current_stock,4) }}</td><td>{{ number_format($item->reorder_level,4) }}</td><td>{{ !empty($item->is_active) ? 'Active' : 'Inactive' }}</td></tr>@empty<tr><td colspan="8"><div class="hm-empty">No mini bar items configured yet.</div></td></tr>@endforelse</tbody></table></div></div></div></div>
        <div class="col-md-4"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Controls</h3></div><div class="box-body"><div class="hm-kpi"><div class="hm-kpi-label">Low Stock</div><div class="hm-kpi-value">{{ $minibar['low_stock_count'] }}</div><div class="hm-kpi-sub">Items at or below reorder level</div></div><ul>@foreach($minibar['notes'] as $note)<li>{{ $note }}</li>@endforeach</ul></div></div></div>
    </div>
</section>
@endsection
