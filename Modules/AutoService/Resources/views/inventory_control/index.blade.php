@extends('autoservice::layouts.master')

@section('content')
@include('autoservice::layouts.nav')

<section class="content-header">
    <h1>Inventory Control <small>Parts, accessories, reorder alerts and profitability</small></h1>
</section>

<section class="content">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="row">
        <div class="col-md-3"><div class="small-box bg-aqua"><div class="inner"><h3>{{ number_format($summary['parts_used'], 3) }}</h3><p>Parts Qty Used</p></div></div></div>
        <div class="col-md-3"><div class="small-box bg-green"><div class="inner"><h3>{{ number_format($summary['parts_sales'], 2) }}</h3><p>Parts Sales</p></div></div></div>
        <div class="col-md-3"><div class="small-box bg-yellow"><div class="inner"><h3>{{ number_format($summary['low_stock_items']) }}</h3><p>Low Stock Items</p></div></div></div>
        <div class="col-md-3"><div class="small-box bg-red"><div class="inner"><h3>{{ number_format($summary['jobs_with_parts']) }}</h3><p>Jobs With Parts</p></div></div></div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Filters</h3></div>
        <form method="get">
            <div class="box-body row">
                <div class="col-md-2"><label>From</label><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
                <div class="col-md-2"><label>To</label><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
                <div class="col-md-3"><label>Search</label><input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Part / SKU / Job / Vehicle / Customer"></div>
                <div class="col-md-2"><label>Stock Filter</label><select name="stock_filter" class="form-control"><option value="">All</option><option value="low" @selected($stockFilter==='low')>Low Stock</option><option value="out" @selected($stockFilter==='out')>Out of Stock</option></select></div>
                <div class="col-md-3" style="padding-top:25px"><button class="btn btn-primary"><i class="fa fa-search"></i> Search</button> <a href="{{ route('autoservice.inventory_control.index') }}" class="btn btn-default">Reset</a> <a class="btn btn-success" href="{{ route('autoservice.inventory_control.export', request()->query()) }}"><i class="fa fa-file-excel-o"></i> CSV</a></div>
            </div>
        </form>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="box box-solid">
                <div class="box-header with-border"><h3 class="box-title">Parts & Accessories Usage</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead><tr><th>Date</th><th>Job</th><th>Vehicle</th><th>Customer</th><th>Part / Accessory</th><th class="text-right">Qty</th><th class="text-right">Unit Price</th><th class="text-right">Discount</th><th class="text-right">Tax</th><th class="text-right">Total</th></tr></thead>
                        <tbody>
                            @forelse($usage as $row)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($row->created_at)->format('Y-m-d') }}</td>
                                    <td>{{ $row->job_no }}</td>
                                    <td>{{ $row->registration_no }}</td>
                                    <td>{{ $row->customer_name }}<br><small>{{ $row->customer_mobile }}</small></td>
                                    <td><strong>{{ $row->item_name }}</strong><br><small>SKU: {{ $row->sku ?: '-' }} | Stock: {{ number_format((float)($row->qty_available ?? 0), 3) }}</small></td>
                                    <td class="text-right">{{ number_format((float)$row->quantity, 3) }}</td>
                                    <td class="text-right">{{ number_format((float)$row->unit_price, 4) }}</td>
                                    <td class="text-right">{{ number_format((float)($row->discount_amount ?? 0), 4) }}</td>
                                    <td class="text-right">{{ number_format((float)($row->tax_amount ?? 0), 4) }}</td>
                                    <td class="text-right"><strong>{{ number_format((float)$row->total_amount, 4) }}</strong></td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="text-center text-muted">No parts usage found for the selected filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $usage->links() }}
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-warning">
                <div class="box-header with-border"><h3 class="box-title">Add / Update Stock Reference</h3></div>
                <form method="post" action="{{ route('autoservice.inventory_control.stock.store') }}">@csrf
                    <div class="box-body">
                        <input class="form-control" name="part_name" placeholder="Part / Accessory Name" required><br>
                        <input class="form-control" name="sku" placeholder="SKU / Code"><br>
                        <div class="row"><div class="col-xs-6"><input class="form-control" type="number" step="0.001" name="qty_available" placeholder="Qty" required></div><div class="col-xs-6"><input class="form-control" type="number" step="0.001" name="reorder_level" placeholder="Reorder Level"></div></div><br>
                        <div class="row"><div class="col-xs-6"><input class="form-control" type="number" step="0.0001" name="last_purchase_price" placeholder="Cost"></div><div class="col-xs-6"><input class="form-control" type="number" step="0.0001" name="selling_price" placeholder="Selling"></div></div><br>
                        <input class="form-control" name="preferred_supplier_name" placeholder="Preferred Supplier"><br>
                        <textarea class="form-control" name="notes" placeholder="Notes"></textarea>
                    </div>
                    <div class="box-footer"><button class="btn btn-warning btn-block">Save Stock Reference</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="box box-danger">
                <div class="box-header with-border"><h3 class="box-title">Low Stock / Reorder Board</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-condensed">
                        <thead><tr><th>Part</th><th>SKU</th><th class="text-right">Stock</th><th class="text-right">Reorder</th><th>Supplier</th><th>Action</th></tr></thead>
                        <tbody>
                        @forelse($stock as $s)
                            <tr class="{{ (float)$s->qty_available <= (float)$s->reorder_level ? 'danger' : '' }}">
                                <td>{{ $s->part_name }}</td><td>{{ $s->sku }}</td><td class="text-right">{{ number_format((float)$s->qty_available, 3) }}</td><td class="text-right">{{ number_format((float)$s->reorder_level, 3) }}</td><td>{{ $s->preferred_supplier_name }}</td>
                                <td><form method="post" action="{{ route('autoservice.inventory_control.reorder.store') }}" class="form-inline">@csrf<input type="hidden" name="part_stock_id" value="{{ $s->id }}"><input type="number" step="0.001" name="required_qty" class="form-control input-sm" style="width:80px" value="{{ max((float)$s->reorder_level - (float)$s->qty_available, 1) }}"><button class="btn btn-xs btn-danger">Reorder</button></form></td>
                            </tr>
                        @empty<tr><td colspan="6" class="text-center text-muted">No stock records.</td></tr>@endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border"><h3 class="box-title">Parts Profitability</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-condensed">
                        <thead><tr><th>Part</th><th class="text-right">Qty</th><th class="text-right">Sales</th><th class="text-right">Cost</th><th class="text-right">Gross Profit</th></tr></thead>
                        <tbody>
                        @forelse($profitability as $p)
                            <tr><td>{{ $p->item_name }}</td><td class="text-right">{{ number_format((float)$p->qty, 3) }}</td><td class="text-right">{{ number_format((float)$p->sales, 4) }}</td><td class="text-right">{{ number_format((float)$p->cost, 4) }}</td><td class="text-right"><strong>{{ number_format((float)$p->gross_profit, 4) }}</strong></td></tr>
                        @empty<tr><td colspan="5" class="text-center text-muted">No profitability data.</td></tr>@endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
