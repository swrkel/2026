<div class="reo-receipt-paper reo-saved-receipt">
    <div class="reo-receipt-head">
        <div>
            <div class="reo-receipt-business">{{ $organisation['business_name'] }}</div>
            <div class="reo-receipt-location">{{ $organisation['location_name'] }}</div>
            @if($organisation['location_address'])
                <div class="reo-receipt-address">{{ $organisation['location_address'] }}</div>
            @endif
        </div>
        <div class="reo-receipt-meta reo-receipt-meta-static">
            <div><span>Receipt No:</span> <strong>{{ $receipt->receipt_no }}</strong></div>
            <div><span>Date:</span> <strong>{{ $receipt->receipt_date?->format('Y-m-d') }}</strong></div>
        </div>
    </div>

    <div class="reo-receipt-fields reo-receipt-fields-static">
        <div class="reo-receipt-field"><span>Membership No:</span> <strong>{{ $receipt->membership_no ?: '—' }}</strong></div>
        <div class="reo-receipt-field"><span>Source:</span> <strong>{{ $receipt->source_name }}</strong></div>
        <div class="reo-receipt-field reo-receipt-amount-words"><span>Amount:</span> <strong>{{ $receipt->amount_in_words }}</strong></div>
    </div>

    <div class="reo-table-wrap reo-receipt-table-wrap">
        <table class="reo-table reo-receipt-table">
            <thead>
                <tr>
                    <th>Source Details</th>
                    <th class="reo-text-right reo-amount-col">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipt->details as $detail)
                    <tr>
                        <td>{{ $detail->source_detail }}</td>
                        <td class="reo-text-right">{{ number_format((float)$detail->amount, $currencyPrecision, '.', ',') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th class="reo-text-right">Total</th>
                    <th class="reo-text-right">{{ number_format((float)$receipt->total_amount, $currencyPrecision, '.', ',') }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="reo-cheque-block">
        <div class="reo-cheque-label">Cheque No:</div>
        <div class="reo-cheque-rows">
            @forelse($receipt->cheques as $cheque)
                <div class="reo-cheque-row">
                    <span><strong>{{ $cheque->cheque_number ?: '—' }}</strong></span>
                    <span>{{ $cheque->bank_name ?: '—' }}</span>
                    <span>{{ $cheque->cheque_date?->format('Y-m-d') ?: '—' }}</span>
                </div>
            @empty
                <div class="reo-muted">No cheque details.</div>
            @endforelse
        </div>
    </div>

    <div class="reo-signature-row">
        <div></div>
        <div class="reo-signature-box">Signature of the Officer</div>
    </div>
</div>
