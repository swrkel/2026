<div class="modal-dialog modal-xl">
    <div class="modal-content">

        <div class="modal-header">
            <h4 class="modal-title">
                Stock Transfer Details — {{ $transfer->reference_no }}
            </h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>

        <div class="modal-body">

            {{-- Transfer Info --}}
            <div class="row mb-3">
                <div class="col-md-3">
                    <strong>Date:</strong><br>
                    {{ \Carbon\Carbon::parse($transfer->date)->format('Y-m-d') }}
                </div>

                <div class="col-md-3">
                    <strong>Location:</strong><br>
                    {{ $transfer->location->name ?? '-' }}
                </div>

                <div class="col-md-3">
                    <strong>From Store:</strong><br>
                    {{ $transfer->store->name ?? '-' }}
                </div>

                <div class="col-md-3">
                    <strong>To Store:</strong><br>
                    {{ $transfer->toStore->name ?? '-' }}
                </div>
            </div>

            {{-- Products Table --}}
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transfer->lines as $line)
                            <tr>
                                <td>
                                    {{ $line->product->name }}
                                    @if($line->variation && $line->variation->name !== 'DUMMY')
                                        ({{ $line->variation->name }})
                                    @endif
                                </td>
                                <td>{{ $line->variation->sub_sku ?? '-' }}</td>
                                <td class="text-right">{{ number_format($line->qty, 2) }}</td>
                                <td class="text-right">{{ number_format($line->unit_sale_price, 2) }}</td>
                                <td class="text-right">{{ number_format($line->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-right">Total</th>
                            <th class="text-right">
                                {{ number_format($transfer->total_amount, 2) }}
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($transfer->note)
                <hr>
                <strong>Notes:</strong>
                <p>{{ $transfer->note }}</p>
            @endif

        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">
                Close
            </button>
        </div>

    </div>
</div>
