<div class="rcm-card rcm-receive-paddy-original-list">
    <div class="rcm-settings-section-head">
        <div>
            <div class="rcm-section-title">Receive Paddy Records</div>
            <p class="rcm-muted">Existing Receive Paddy records remain available below the entry form.</p>
        </div>
    </div>

    @include('RiceMill::partials.functionality-bar',[
        'tableId'=>'rcm-receipts-table',
        'exportName'=>'rice-mill-paddy-receiving',
        'serverPaged'=>true,
        'paginator'=>$rows,
        'rowsLabel'=>'receipts'
    ])

    <div class="rcm-table-wrap">
        <table id="rcm-receipts-table" class="rcm-table rcm-managed-table">
            <thead>
                <tr>
                    <th>Receipt No</th>
                    <th>Weighbridge No</th>
                    <th>Stock Lot No</th>
                    <th>Received</th>
                    <th>Supplier</th>
                    <th>Paddy Variety</th>
                    <th>Vehicle</th>
                    <th class="rcm-num">Gross</th>
                    <th class="rcm-num">Tare</th>
                    <th class="rcm-num">Net</th>
                    <th class="rcm-num">Moisture %</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r->receipt_no }}</td>
                    <td>{{ optional($r->weighbridgeEntry)->entry_no ?: '-' }}</td>
                    <td>{{ optional($r->paddyLot)->lot_no ?: '-' }}</td>
                    <td>{{ $r->received_at }}</td>
                    <td>{{ $r->supplier_id }}</td>
                    <td>{{ optional($r->variety)->code }} {{ optional($r->variety)->name }}</td>
                    <td>{{ $r->vehicle_no }}</td>
                    <td class="rcm-num">{{ number_format($r->gross_weight,$rcmQuantityPrecision) }}</td>
                    <td class="rcm-num">{{ number_format($r->tare_weight,$rcmQuantityPrecision) }}</td>
                    <td class="rcm-num">{{ number_format($r->net_weight,$rcmQuantityPrecision) }}</td>
                    <td class="rcm-num">{{ $r->moisture_percent !== null ? number_format($r->moisture_percent,3) : '-' }}</td>
                    <td>{{ $r->quality_grade }}</td>
                </tr>
            @empty
                <tr data-rcm-empty-row>
                    <td colspan="12" class="rcm-muted">No receipts found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $rows->links() }}
</div>
