<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

        <div class="modal-header">
            <h5 class="modal-title">
                Daily Summary Sheet #{{ $sheet->sheet_number }}
            </h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>

        <div class="modal-body">
            <div class="row">

                <div class="col-md-6">
                    <p><strong>Date:</strong> {{ $sheet->date->format('Y-m-d') }}</p>
                    <p><strong>Vehicle:</strong> {{ $sheet->vehicle->vehicle_no ?? '—' }}</p>
                    <p><strong>Route:</strong> {{ $sheet->route->name ?? '—' }}</p>
                    <p><strong>Sales Rep:</strong> {{ $sheet->salesRep->first_name ?? '—' }}</p>
                </div>

                <div class="col-md-6">
                    <p><strong>Distance (KM):</strong> {{ $sheet->distance_km }}</p>
                    <p><strong>Total This Page:</strong> {{ $sheet->total_this_page }}</p>
                    <p><strong>Grand Total:</strong> {{ $sheet->grand_total }}</p>
                    <p><strong>Status:</strong> {{ ucfirst($sheet->status) }}</p>
                </div>

            </div>

            <hr>

            <h5>Cash Summary</h5>
            <p><strong>Cash Deposited:</strong> {{ $sheet->cash_deposited }}</p>
            <p><strong>Cheque Deposited:</strong> {{ $sheet->cheque_deposited }}</p>
            <p><strong>Cash In Hand:</strong> {{ $sheet->cash_in_hand }}</p>

        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                Close
            </button>
        </div>

    </div>
</div>
