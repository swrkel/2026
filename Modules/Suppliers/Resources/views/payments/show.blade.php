@php
    $precision = max(0, min(6, (int) (session('business.currency_precision') ?? 2)));
    $method = trim((string) data_get($payment, 'method', ''));
    $methodLabel = $method !== '' ? ucwords(str_replace('_', ' ', $method)) : '-';
    $supplierName = trim((string) data_get($supplier, 'supplier_business_name', ''));
    if ($supplierName === '') {
        $supplierName = trim((string) data_get($supplier, 'name', '-'));
    }
    $reference = trim((string) data_get($payment, 'payment_ref_no', ''));
    if ($reference === '') {
        $reference = trim((string) data_get($transaction, 'invoice_no', data_get($transaction, 'ref_no', '-')));
    }

    $businessName = trim((string) data_get($business, 'name', '-'));
    $locationName = trim((string) data_get($location, 'name', ''));
    $addressParts = array_values(array_filter([
        trim((string) data_get($location, 'landmark', '')),
        trim((string) data_get($location, 'city', '')),
        trim((string) data_get($location, 'state', '')),
        trim((string) data_get($location, 'country', '')),
        trim((string) data_get($location, 'zip_code', '')),
    ], static fn ($value) => $value !== ''));

    $taxLines = [];
    foreach ([1, 2] as $index) {
        $number = trim((string) data_get($business, 'tax_number_' . $index, ''));
        if ($number === '') {
            continue;
        }
        $label = trim((string) data_get($business, 'tax_label_' . $index, ''));
        $taxLines[] = ($label !== '' ? $label : 'Tax No') . ': ' . $number;
    }

    $locationMobile = trim((string) data_get($location, 'mobile', ''));
    $locationEmail = trim((string) data_get($location, 'email', ''));
    $paymentNote = trim((string) data_get($payment, 'note', ''));
    $accountName = trim((string) data_get($account, 'name', ''));
@endphp

<div class="modal-dialog supplier-payment-receipt-dialog" role="document">
    <div class="modal-content supplier-payment-receipt">
        <div class="modal-header no-print">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">
                View Payment
                @if($reference !== '' && $reference !== '-')
                    <span class="supplier-payment-reference-title">( Reference No: {{ $reference }} )</span>
                @endif
            </h4>
        </div>

        <div class="modal-body supplier-payment-receipt-body">
            <div class="row supplier-payment-party-row">
                <div class="col-sm-5">
                    <div class="supplier-payment-party-label">Supplier:</div>
                    <div class="supplier-payment-party-value"><strong>{{ $supplierName ?: '-' }}</strong></div>
                    @if(!empty(data_get($supplier, 'contact_id')))
                        <div>{{ data_get($supplier, 'contact_id') }}</div>
                    @endif
                </div>

                <div class="col-sm-7">
                    <div class="supplier-payment-party-label">Business:</div>
                    <address class="supplier-payment-business-address">
                        <strong>{{ $businessName ?: '-' }}</strong>
                        @if($locationName !== '')
                            <br>{{ $locationName }}
                        @endif
                        @if($addressParts)
                            <br>{{ implode(', ', $addressParts) }}
                        @endif
                        @foreach($taxLines as $taxLine)
                            <br>{{ $taxLine }}
                        @endforeach
                        @if($locationMobile !== '')
                            <br>Mobile: {{ $locationMobile }}
                        @endif
                        @if($locationEmail !== '')
                            <br>Email: {{ $locationEmail }}
                        @endif
                    </address>
                </div>
            </div>

            <hr class="supplier-payment-receipt-separator">

            <div class="row supplier-payment-summary-row">
                <div class="col-sm-6">
                    <div class="supplier-payment-field">
                        <strong>Amount:</strong>
                        <span class="display_currency" data-currency_symbol="true">{{ number_format((float) data_get($payment, 'amount', 0), $precision, '.', '') }}</span>
                    </div>
                    <div class="supplier-payment-field">
                        <strong>Payment Method:</strong> {{ $methodLabel }}
                    </div>
                    @if($accountName !== '')
                        <div class="supplier-payment-field">
                            <strong>Payment Account:</strong> {{ $accountName }}
                        </div>
                    @endif
                    @if(in_array(strtolower($method), ['cheque', 'check'], true) && !empty(data_get($payment, 'cheque_number')))
                        <div class="supplier-payment-field"><strong>Cheque No:</strong> {{ data_get($payment, 'cheque_number') }}</div>
                    @endif
                    @if(!empty(data_get($payment, 'bank_name')))
                        <div class="supplier-payment-field"><strong>Bank:</strong> {{ data_get($payment, 'bank_name') }}</div>
                    @endif
                </div>

                <div class="col-sm-6">
                    <div class="supplier-payment-field">
                        <strong>Reference No:</strong> {{ $reference !== '' ? $reference : '-' }}
                    </div>
                    <div class="supplier-payment-field">
                        <strong>Paid on:</strong> {{ $effective_paid_on_display ?: '-' }}
                    </div>
                    <div class="supplier-payment-field supplier-payment-note-field">
                        <strong>Payment Note:</strong> {{ $paymentNote !== '' ? $paymentNote : '-' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer no-print">
            <button type="button" class="btn btn-primary" aria-label="Print"
                onclick="if (window.jQuery && jQuery.fn.printThis) { jQuery(this).closest('div.modal').printThis(); } else { window.print(); }">
                <i class="fa fa-print"></i> Print
            </button>
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
    </div>
</div>
