@extends('layouts.app')
@section('title', 'Hotel Procurement')
@section('content')
<section class="content-header hm-page-header">
    <h1><i class="fa fa-shopping-cart"></i> Hotel Procurement <small>purchase requests, purchase orders and GRN</small></h1>
</section>
<section class="content hm-pos-scope">
    @include('hotelmanagement::partials.nav')
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="row hm-kpi-row">
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Suppliers</div><div class="hm-kpi-value">{{ $procurement['suppliers_count'] }}</div><div class="hm-kpi-sub">Active hotel vendors</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Pending Requests</div><div class="hm-kpi-value">{{ $procurement['pending_requests'] }}</div><div class="hm-kpi-sub">Awaiting order/action</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Open Orders</div><div class="hm-kpi-value">{{ $procurement['open_orders'] }}</div><div class="hm-kpi-sub">Draft/ordered/partial</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Ordered Value</div><div class="hm-kpi-value">{{ number_format($procurement['ordered_value'], 2) }}</div><div class="hm-kpi-sub">Current listed PO value</div></div></div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Supplier Setup</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hotel-management.procurement.supplier') }}">
                        @csrf
                        <div class="form-group"><label>Supplier Name</label><input name="supplier_name" class="form-control" required></div>
                        <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                            <div class="form-group"><label>Contact Person</label><input name="contact_person" class="form-control"></div>
                            <div class="form-group"><label>Mobile</label><input name="mobile" class="form-control"></div>
                            <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control"></div>
                            <div class="form-group"><label>Category</label><input name="category" class="form-control" placeholder="Food / Linen / Maintenance"></div>
                            <div class="form-group"><label>Credit Days</label><input type="number" name="credit_days" class="form-control" value="0"></div>
                            <div class="form-group"><label>Active</label><select name="is_active" class="form-control"><option value="1">Yes</option><option value="0">No</option></select></div>
                        </div>
                        <div class="form-group"><label>Address</label><textarea name="address" class="form-control" rows="2"></textarea></div>
                        <div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
                        <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-save"></i> Save Supplier</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Purchase Request</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hotel-management.procurement.request') }}">
                        @csrf
                        <div class="hm-form-grid" style="grid-template-columns:repeat(4,minmax(120px,1fr))">
                            <div class="form-group"><label>Request No</label><input name="request_no" class="form-control" placeholder="Auto if blank"></div>
                            <div class="form-group"><label>Date</label><input type="date" name="request_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
                            <div class="form-group"><label>Department</label><input name="department" class="form-control" required placeholder="Kitchen / Rooms / HK"></div>
                            <div class="form-group"><label>Requested By</label><input name="requested_by" class="form-control"></div>
                            <div class="form-group"><label>Required Date</label><input type="date" name="required_date" class="form-control"></div>
                            <div class="form-group"><label>Priority</label><select name="priority" class="form-control"><option value="normal">Normal</option><option value="urgent">Urgent</option><option value="critical">Critical</option></select></div>
                            <div class="form-group"><label>Item Name</label><input name="item_name" class="form-control" required></div>
                            <div class="form-group"><label>Qty</label><input type="number" step="0.001" name="qty" class="form-control" required></div>
                            <div class="form-group"><label>Estimated Unit Cost</label><input type="number" step="0.01" name="estimated_unit_cost" class="form-control" value="0"></div>
                            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="submitted">Submitted</option><option value="draft">Draft</option><option value="approved">Approved</option></select></div>
                        </div>
                        <div class="form-group"><label>Description / Remarks</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                        <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-plus"></i> Save Request</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Create Purchase Order</h3></div>
        <div class="box-body">
            <form method="POST" action="{{ route('hotel-management.procurement.order') }}">
                @csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(5,minmax(120px,1fr))">
                    <div class="form-group"><label>PO No</label><input name="po_no" class="form-control" placeholder="Auto if blank"></div>
                    <div class="form-group"><label>PO Date</label><input type="date" name="po_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
                    <div class="form-group"><label>Supplier</label><select name="supplier_id" class="form-control" required><option value="">Select</option>@foreach($procurement['suppliers'] as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->supplier_name }}</option>@endforeach</select></div>
                    <div class="form-group"><label>Link Request</label><select name="request_id" class="form-control"><option value="">Optional</option>@foreach($procurement['requests'] as $request)<option value="{{ $request->id }}">{{ $request->request_no }} - {{ $request->item_name }}</option>@endforeach</select></div>
                    <div class="form-group"><label>Expected Delivery</label><input type="date" name="expected_delivery_date" class="form-control"></div>
                    <div class="form-group"><label>Item Name</label><input name="item_name" class="form-control" required></div>
                    <div class="form-group"><label>Qty</label><input type="number" step="0.001" name="qty" class="form-control" required></div>
                    <div class="form-group"><label>Unit Cost</label><input type="number" step="0.01" name="unit_cost" class="form-control" required></div>
                    <div class="form-group"><label>Discount</label><input type="number" step="0.01" name="discount_amount" class="form-control" value="0"></div>
                    <div class="form-group"><label>Tax</label><input type="number" step="0.01" name="tax_amount" class="form-control" value="0"></div>
                    <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="ordered">Ordered</option><option value="draft">Draft</option><option value="approved">Approved</option></select></div>
                </div>
                <div class="form-group"><label>Description / Remarks</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-file-text-o"></i> Save PO</button></div>
            </form>
        </div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Purchase Order Register</h3></div>
        <div class="box-body">
            <div class="hm-toolbar"><input class="form-control hm-search-input" placeholder="Search purchase orders" style="max-width:260px"><button class="btn hm-btn-csv">CSV</button><button class="btn hm-btn-excel">Excel</button><button class="btn hm-btn-pdf">PDF</button><button class="btn hm-btn-print">Print</button><button class="btn hm-btn-col">Column Visibility</button></div>
            <div class="table-responsive"><table class="table hm-table"><thead><tr><th>ID</th><th>PO No</th><th>Date</th><th>Supplier</th><th>Item</th><th>Qty</th><th>Received</th><th>Unit</th><th>Net</th><th>Status</th><th>GRN / Status</th></tr></thead><tbody>
                @forelse($procurement['orders'] as $row)
                    <tr>
                        <td>{{ $row->id }}</td><td>{{ $row->po_no }}</td><td>{{ $row->po_date }}</td><td>{{ $row->supplier_name ?? '-' }}</td><td>{{ $row->item_name }}</td><td>{{ number_format($row->qty, 3) }}</td><td>{{ number_format($row->received_qty ?? 0, 3) }}</td><td>{{ number_format($row->unit_cost, 2) }}</td><td>{{ number_format($row->net_amount, 2) }}</td><td><span class="hm-badge {{ $row->status }}">{{ $row->status }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('hotel-management.procurement.grn', $row->id) }}" class="form-inline" style="margin-bottom:5px">
                                @csrf
                                <input name="grn_no" class="form-control input-sm" placeholder="GRN Auto" style="width:95px">
                                <input type="date" name="received_date" class="form-control input-sm" value="{{ date('Y-m-d') }}" style="width:135px">
                                <input type="number" step="0.001" name="received_qty" class="form-control input-sm" placeholder="Qty" style="width:90px" required>
                                <input type="number" step="0.001" name="accepted_qty" class="form-control input-sm" placeholder="Accepted" style="width:95px">
                                <button class="btn btn-xs hm-btn-add">GRN</button>
                            </form>
                            <form method="POST" action="{{ route('hotel-management.procurement.status', ['type' => 'order', 'id' => $row->id]) }}" class="form-inline">
                                @csrf
                                <select name="status" class="form-control input-sm"><option value="approved">Approved</option><option value="ordered">Ordered</option><option value="partial">Partial</option><option value="received">Received</option><option value="cancelled">Cancelled</option></select>
                                <button class="btn btn-xs hm-btn-edit">Update</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11"><div class="hm-empty">No purchase orders found yet.</div></td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Purchase Requests</h3></div><div class="box-body"><div class="table-responsive"><table class="table hm-table"><thead><tr><th>No</th><th>Department</th><th>Item</th><th>Qty</th><th>Estimated</th><th>Status</th></tr></thead><tbody>@forelse($procurement['requests'] as $request)<tr><td>{{ $request->request_no }}</td><td>{{ $request->department }}</td><td>{{ $request->item_name }}</td><td>{{ number_format($request->qty,3) }}</td><td>{{ number_format($request->estimated_total ?? 0,2) }}</td><td><span class="hm-badge {{ $request->status }}">{{ $request->status }}</span></td></tr>@empty<tr><td colspan="6"><div class="hm-empty">No purchase requests found.</div></td></tr>@endforelse</tbody></table></div></div></div></div>
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Latest GRNs</h3></div><div class="box-body"><div class="table-responsive"><table class="table hm-table"><thead><tr><th>GRN</th><th>Date</th><th>PO</th><th>Received</th><th>Accepted</th><th>Value</th></tr></thead><tbody>@forelse($procurement['grns'] as $grn)<tr><td>{{ $grn->grn_no }}</td><td>{{ $grn->received_date }}</td><td>{{ $grn->po_id }}</td><td>{{ number_format($grn->received_qty,3) }}</td><td>{{ number_format($grn->accepted_qty,3) }}</td><td>{{ number_format($grn->accepted_value ?? 0,2) }}</td></tr>@empty<tr><td colspan="6"><div class="hm-empty">No GRNs posted yet.</div></td></tr>@endforelse</tbody></table></div><ul>@foreach($procurement['notes'] as $note)<li>{{ $note }}</li>@endforeach</ul></div></div></div>
    </div>
</section>
@endsection
