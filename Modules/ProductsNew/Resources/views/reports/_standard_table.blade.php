<div class="productsnew-table-card">
    <div class="productsnew-toolbar"><button class="btn btn-success productsnew-btn-white">CSV</button><button class="btn btn-info productsnew-btn-white">Excel</button><button class="btn btn-danger productsnew-btn-white">PDF</button><button class="btn btn-warning productsnew-btn-white">Print</button><button class="btn btn-secondary productsnew-btn-white">Column Visibility</button></div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped productsnew-table">
            <thead><tr><th>SKU</th><th>Product</th><th>Category</th><th>Brand</th><th>Type</th><th>Stock Enabled</th><th>Alert Qty</th><th>Health</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr><td>{{ $row->sku }}</td><td>{{ $row->name }}</td><td>{{ $row->category }}</td><td>{{ $row->brand }}</td><td>{{ $row->type }}</td><td>{{ $row->enable_stock ? 'Yes' : 'No' }}</td><td>{{ number_format((float)$row->alert_quantity, 3) }}</td><td>{{ $row->health_score ?? 0 }}%</td><td>{{ $row->lifecycle_status ?? 'active' }}</td></tr>
                @empty
                    <tr><td colspan="9" class="text-center">No records found</td></tr>
                @endforelse
            </tbody>
            <tfoot><tr><th colspan="9">Record Count: {{ method_exists($rows, 'total') ? $rows->total() : count($rows) }}</th></tr></tfoot>
        </table>
    </div>
    @if(method_exists($rows, 'links')) {{ $rows->links() }} @endif
</div>
