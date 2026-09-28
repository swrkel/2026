<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title">View Product Loading</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <div class="modal-body">
            <div class="row" style="margin-bottom: 15px;">
                <div class="col-md-3"><strong>Loading No:</strong> {{ $loading->loading_no }}</div>
                <div class="col-md-3"><strong>Date:</strong>
                    {{ \Carbon\Carbon::parse($loading->date_time)->format('Y-m-d') }}</div>
                <div class="col-md-3"><strong>Sales Rep:</strong> {{ $loading->salesRep->name ?? '-' }}</div>
                <div class="col-md-3"><strong>Vehicle:</strong> {{ $loading->vehicle->vehicle_no ?? '-' }}</div>
                <div class="col-md-3"><strong>Product Category:</strong> {{ $loading->category->name ?? 'All' }}</div>
                <div class="col-md-3"><strong>Product Subcategory:</strong> {{ $loading->subcategory->name ?? 'All' }}</div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>@lang( 'distribution::lang.index' )</th>
                            <th>@lang( 'distribution::lang.product' )</th>
                            <th>@lang( 'distribution::lang.available_qty' )</th>
                            <th>@lang( 'distribution::lang.vehicle_balance_qty' )</th>
                            <th>@lang( 'distribution::lang.requested_qty' )</th>
                            <th>@lang( 'distribution::lang.issued_qty' )</th>
                            <th>@lang( 'distribution::lang.unit_sale_price' )</th>
                            <th>@lang( 'distribution::lang.total_in_sale_price' )</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($loading->lines as $i => $line)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $line->product?->name ?? '-' }}</td>
                                <td>{{ number_format($line->available_qty ?? 0, 2) }}</td>
                                <td>{{ number_format($line->vehicle_balance_qty ?? 0, 2) }}</td>
                                <td>{{ number_format($line->requested_qty, 2) }}</td>
                                <td>{{ number_format($line->issued_qty, 2) }}</td>
                                <td>{{ number_format($line->sale_price, 2) }}</td>
                                <td>{{ number_format($line->line_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No products</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="7" class="text-right"><strong>Total Sale Amount:</strong></td>
                            <td><strong>{{ number_format($loading->total_sale_price, 2) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
    </div>
</div>
