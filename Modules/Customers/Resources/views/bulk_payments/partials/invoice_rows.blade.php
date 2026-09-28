@if($invoices->isEmpty())
    <tr class="bulk-empty-row">
        <td colspan="10">
            <div class="bulk-empty-state">
                <i class="fa fa-check-circle"></i>
                <strong>No outstanding invoices</strong>
                <span>The selected customer has no invoices available for allocation.</span>
            </div>
        </td>
    </tr>
@else
    @foreach($invoices as $invoice)
        @php
            $invoiceType = strtolower((string) ($invoice->type ?? ''));
            $isOpeningBalance = !empty($invoice->is_opening_balance)
                || in_array($invoiceType, ['opening_balance', 'fleet_opening_balance'], true);
            $invoiceLabel = $isOpeningBalance
                ? 'Opening Balance'
                : ($invoice->invoice_no ?: ($invoice->ref_no ?: ('Transaction #' . $invoice->id)));
            $reference = $isOpeningBalance ? '-' : ($invoice->order_no ?: $invoice->ref_no);
        @endphp
        <tr class="bulk-invoice-row" data-transaction-id="{{ (int) $invoice->id }}" data-outstanding="{{ number_format((float) $invoice->outstanding, 4, '.', '') }}">
            <td class="bulk-check-cell">
                <label class="bulk-check-wrap" title="Select {{ $invoiceLabel }}">
                    <input type="checkbox" class="bulk-row-check" value="{{ (int) $invoice->id }}">
                    <span></span>
                </label>
            </td>
            <td>{{ !empty($invoice->transaction_date) ? \Carbon\Carbon::parse($invoice->transaction_date)->format('d/m/Y') : '' }}</td>
            <td>
                <strong class="bulk-invoice-number">{{ $invoiceLabel }}</strong>
                <small>{{ ucwords(str_replace('_', ' ', (string) $invoice->type)) }}</small>
            </td>
            <td>{{ $reference ?: '-' }}</td>
            <td class="text-right">{{ number_format((float) $invoice->invoice_total, 2) }}</td>
            <td class="text-right">{{ number_format((float) $invoice->total_paid, 2) }}</td>
            <td class="text-right bulk-outstanding-cell">{{ number_format((float) $invoice->outstanding, 2) }}</td>
            <td class="bulk-interest-column {{ $interestEnabled ? '' : 'is-hidden' }}">
                <input
                    type="number"
                    min="0"
                    step="0.01"
                    name="interest[{{ (int) $invoice->id }}]"
                    class="form-control bulk-interest-input"
                    value="0.00"
                    disabled
                    aria-label="Interest for {{ $invoiceLabel }}"
                >
            </td>
            <td>
                <input
                    type="number"
                    min="0"
                    max="{{ number_format((float) $invoice->outstanding, 4, '.', '') }}"
                    step="0.01"
                    name="allocations[{{ (int) $invoice->id }}]"
                    class="form-control bulk-allocation-input"
                    value=""
                    disabled
                    aria-label="Payment allocation for {{ $invoiceLabel }}"
                >
            </td>
            <td class="text-right bulk-row-total">0.00</td>
        </tr>
    @endforeach
@endif
