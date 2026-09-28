<div class="reo-section-head">
    <div>
        <h2>List Receipt</h2>
        <p>View, print or edit only manually entered Receipt details. Edited Details appears only when a change has been recorded.</p>
    </div>
</div>

@include('reportsother::components.report-toolbar', [
    'tableId' => 'reo-receipt-list',
    'title' => 'Cash Receipt List',
    'searchId' => 'reo-receipt-list-search',
    'dateFrom' => $dateFrom,
    'dateTo' => $dateTo,
    'pageSize' => 25,
    'financialYearStartMonth' => $financialYearStartMonth ?? 1,
    'filterUrl' => route('reports-other.cash-receipt.index', ['tab' => 'list']),
    'shareUrl' => route('reports-other.cash-receipt.list.share'),
])

<div class="reo-table-wrap reo-list-table-wrap">
    <table class="reo-table" id="reo-receipt-list">
        <thead>
            <tr>
                <th data-reo-no-export>Action</th>
                <th>Date</th>
                <th>Receipt No</th>
                <th>Source</th>
                <th class="reo-text-right">Total Amount</th>
                <th>Entered By</th>
                <th data-reo-no-export>Edited Details</th>
            </tr>
        </thead>
        <tbody>
            @forelse($receipts as $receipt)
                <tr data-reo-date="{{ $receipt->receipt_date?->toDateString() }}">
                    <td class="reo-actions-cell" data-reo-no-export>
                        <a class="reo-btn reo-btn-view reo-btn-sm" href="{{ route('reports-other.cash-receipt.receipts.show', $receipt) }}">View</a>
                        <a class="reo-btn reo-btn-print reo-btn-sm" href="{{ route('reports-other.cash-receipt.receipts.print', $receipt) }}" target="_blank">Print</a>
                        <a class="reo-btn reo-btn-edit reo-btn-sm" href="{{ route('reports-other.cash-receipt.receipts.edit', $receipt) }}">Edit</a>
                    </td>
                    <td>{{ $receipt->receipt_date?->format('Y-m-d') }}</td>
                    <td><strong>{{ $receipt->receipt_no }}</strong></td>
                    <td>{{ $receipt->source_name }}</td>
                    <td class="reo-text-right">{{ number_format((float)$receipt->total_amount, $currencyPrecision, '.', ',') }}</td>
                    <td>{{ $receipt->entered_by_name ?: '—' }}</td>
                    <td data-reo-no-export>
                        @if($receipt->audits->isNotEmpty())
                            @php($auditId = 'reo-audit-'.$receipt->id)
                            <div class="reo-audit-wrap">
                                <button type="button" class="reo-btn reo-btn-audit reo-btn-sm" data-reo-audit-button data-audit-target="{{ $auditId }}">Edited Details</button>
                                <div class="reo-audit-hover" aria-hidden="true">
                                    @foreach($receipt->audits->take(5) as $audit)
                                        <div><strong>{{ $audit->field_label }}:</strong> {{ $audit->old_value ?: '—' }} → {{ $audit->new_value ?: '—' }}<br><small>{{ $audit->edited_by_name ?: 'User' }} · {{ optional($audit->edited_at)->format('Y-m-d H:i') }}</small></div>
                                    @endforeach
                                </div>
                                <template id="{{ $auditId }}">
                                    <div class="reo-audit-list">
                                        @foreach($receipt->audits as $audit)
                                            <div class="reo-audit-item">
                                                <div><strong>{{ $audit->field_label }}</strong></div>
                                                <div>{{ $audit->old_value ?: '—' }} <span class="reo-arrow">→</span> {{ $audit->new_value ?: '—' }}</div>
                                                <small>Changed by {{ $audit->edited_by_name ?: 'User' }} on {{ optional($audit->edited_at)->format('Y-m-d H:i') }}</small>
                                            </div>
                                        @endforeach
                                    </div>
                                </template>
                            </div>
                        @else
                            <span class="reo-muted">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="reo-empty-inline">No Receipts found for this date range.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
