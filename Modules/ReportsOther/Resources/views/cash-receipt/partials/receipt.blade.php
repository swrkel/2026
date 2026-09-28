<div class="reo-section-head reo-receipt-section-head">
    <div>
        <h2>Receipt</h2>
        <p>The receipt design is always shown below. Select the Source/date to load the live Source details, amounts, membership and cheque information.</p>
    </div>
    <span class="reo-preview-badge">Live Receipt Design</span>
</div>

@if(!$numbering)
    <div class="reo-alert reo-alert-warning">
        Receipt numbering has not been configured. The receipt design is still visible for checking.
        <a href="{{ route('reports-other.cash-receipt.index', ['tab' => 'numbering']) }}">Open numbering setup</a>.
    </div>
@endif

@if($sources->isEmpty())
    <div class="reo-alert reo-alert-warning">
        No Source is configured for this business/location/store. The receipt design remains visible below.
        <a href="{{ route('reports-other.cash-receipt.index', ['tab' => 'mapping']) }}">Map Product Categories/Sub Categories to a Source</a> first.
    </div>
@endif

<form method="post"
      action="{{ route('reports-other.cash-receipt.receipts.store') }}"
      id="reo-receipt-entry"
      data-reo-receipt-entry
      data-preview-url="{{ route('reports-other.cash-receipt.preview') }}">
    @csrf

    <div class="reo-receipt-design-frame" data-reo-receipt-design>
        <div class="reo-receipt-design-label">RECEIPT PREVIEW</div>
        <div class="reo-receipt-paper">
            <div class="reo-receipt-head">
                <div>
                    <div class="reo-receipt-business">{{ $organisation['business_name'] ?: 'Business Name' }}</div>
                    <div class="reo-receipt-location">{{ $organisation['location_name'] ?: 'Business Location' }}</div>
                    <div class="reo-receipt-address">{{ $organisation['location_address'] ?: 'Business Location Address' }}</div>
                </div>
                <div class="reo-receipt-meta">
                    <div><span>Receipt No:</span> <strong data-reo-receipt-no>{{ $nextReceiptNo ?: 'Not configured' }}</strong></div>
                    <label><span>Date:</span>
                        <input class="reo-input reo-input-compact" type="date" name="receipt_date" value="{{ old('receipt_date', now()->toDateString()) }}" required data-reo-receipt-date>
                    </label>
                </div>
            </div>

            <div class="reo-receipt-fields">
                <label class="reo-receipt-field">
                    <span>Membership No:</span>
                    <input class="reo-input" type="text" name="membership_no" maxlength="120" value="{{ old('membership_no') }}" placeholder="Membership No" data-reo-membership>
                    <small data-reo-membership-help>Membership No will be checked after selecting the Source and date. Manual entry is allowed when unavailable.</small>
                </label>

                <label class="reo-receipt-field">
                    <span>Source:</span>
                    <select class="reo-input" name="source_id" required data-reo-source @disabled($sources->isEmpty())>
                        @forelse($sources as $source)
                            <option value="{{ $source->id }}" @selected((string)old('source_id', optional($sources->first())->id) === (string)$source->id)>{{ $source->source_name }}</option>
                        @empty
                            <option value="">No Sources configured</option>
                        @endforelse
                    </select>
                </label>

                <div class="reo-receipt-field reo-receipt-amount-words">
                    <span>Amount:</span>
                    <strong data-reo-amount-words>Amount in words will appear here Only</strong>
                </div>
            </div>

            <div class="reo-live-status" data-reo-preview-status>
                Receipt design is ready. Source details will load automatically when a Source and date are available.
            </div>

            <div class="reo-table-wrap reo-receipt-table-wrap">
                <table class="reo-table reo-receipt-table">
                    <thead>
                        <tr>
                            <th>Source Details</th>
                            <th class="reo-text-right reo-amount-col">Amount</th>
                        </tr>
                    </thead>
                    <tbody data-reo-source-details>
                        @for($i = 0; $i < 5; $i++)
                            <tr class="reo-receipt-placeholder-row">
                                <td>@if($i === 0) Source details will appear here @else &nbsp; @endif</td>
                                <td class="reo-text-right">@if($i === 0) {{ number_format(0, $currencyPrecision, '.', ',') }} @else &nbsp; @endif</td>
                            </tr>
                        @endfor
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="reo-text-right">Total</th>
                            <th class="reo-text-right" data-reo-total>{{ number_format(0, $currencyPrecision, '.', ',') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="reo-cheque-block">
                <div class="reo-cheque-label">Cheque No:</div>
                <div data-reo-cheques class="reo-cheque-rows">
                    <div class="reo-cheque-row reo-cheque-placeholder">
                        <span>Cheque Number</span><span>Bank</span><span>Cheque Date</span>
                    </div>
                </div>
            </div>

            <div class="reo-signature-row">
                <div></div>
                <div class="reo-signature-box">Signature of the Officer</div>
            </div>
        </div>
    </div>

    <div class="reo-actions reo-receipt-actions">
        <button class="reo-btn reo-btn-primary" type="submit" data-reo-save-receipt @disabled(!$numbering || $sources->isEmpty())>Save Receipt</button>
        <button class="reo-btn reo-btn-light" type="button" data-reo-print-design>Print Current Design</button>
        <a class="reo-btn reo-btn-light" href="{{ route('reports-other.cash-receipt.index', ['tab' => 'list']) }}">List Receipt</a>
    </div>
</form>
