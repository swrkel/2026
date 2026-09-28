@php
    /*
     * VAT Print - 163
     * A4 portrait VAT Invoice 2 layout based on the supplied 163 reference.
     * This is a separate print design and does not replace the existing formats.
     */
    $tax = (float) ($receipt_details->tax_rate->amount ?? 0);
    $normalized_sale_type = strtolower(trim((string) ($issue_customer_bill->sale_type ?? '')));
    $is_service_invoice = $normalized_sale_type === 'service';

    $vat_decimal_config = $vat_decimal_config ?? [
        'unit_vat_no_of_decimals' => 2,
        'unit_vat_rounding_off_required' => false,
        'sub_total_no_of_decimals' => 2,
        'sub_total_rounding_off_required' => false,
    ];

    $currency = session('currency', []);
    $decimal_separator = $currency['decimal_separator'] ?? '.';
    $thousand_separator = $currency['thousand_separator'] ?? ',';

    $applyConfiguredDecimals = function ($value, $decimals, $roundingRequired) {
        $value = is_numeric($value) ? (float) $value : 0.0;
        $decimals = min(max((int) $decimals, 0), 10);
        if ($roundingRequired) {
            return round($value, $decimals, PHP_ROUND_HALF_UP);
        }
        $factor = pow(10, $decimals);
        return $value < 0 ? ceil($value * $factor) / $factor : floor($value * $factor) / $factor;
    };

    $formatConfiguredNumber = function ($value, $decimals, $roundingRequired) use ($applyConfiguredDecimals, $decimal_separator, $thousand_separator) {
        $decimals = min(max((int) $decimals, 0), 10);
        return number_format(
            $applyConfiguredDecimals($value, $decimals, (bool) $roundingRequired),
            $decimals,
            $decimal_separator,
            $thousand_separator
        );
    };

    $formatQty = function ($value) use ($formatConfiguredNumber, $vat_decimal_config) {
        return $formatConfiguredNumber(
            $value,
            $vat_decimal_config['unit_vat_no_of_decimals'],
            $vat_decimal_config['unit_vat_rounding_off_required']
        );
    };

    $formatAmount = function ($value) use ($formatConfiguredNumber, $vat_decimal_config) {
        return $formatConfiguredNumber(
            $value,
            $vat_decimal_config['sub_total_no_of_decimals'],
            $vat_decimal_config['sub_total_rounding_off_required']
        );
    };

    $formatDate = function ($value) {
        if ($value instanceof \Carbon\Carbon) {
            return $value->format('m/d/Y');
        }
        if (empty($value) || $value === '-') {
            return '-';
        }
        try {
            return \Carbon\Carbon::parse($value)->format('m/d/Y');
        } catch (\Exception $e) {
            return '-';
        }
    };

    $business_location = $receipt_details->business_location ?? null;
    $business_address_parts = [];
    if ($business_location) {
        foreach (['landmark', 'city', 'state', 'zip_code', 'country'] as $field) {
            if (!empty($business_location->{$field})) {
                $business_address_parts[] = $business_location->{$field};
            }
        }
    }
    $business_address = implode(', ', array_unique($business_address_parts));
    if (empty($business_address)) {
        $business_address = trim((string) ($receipt_details->address ?? ''));
    }

    $business_phone = $business_location->mobile
        ?? $business_location->alternate_number
        ?? $receipt_details->sup_telephone_no
        ?? '-';
    $business_email = $business_location->email ?? '-';

    $customer = $receipt_details->customer ?? null;
    $customer_address_parts = [];
    if ($customer) {
        foreach (['address', 'address_2', 'address_3', 'landmark', 'city', 'state', 'zip_code', 'country'] as $field) {
            if (!empty($customer->{$field})) {
                $customer_address_parts[] = $customer->{$field};
            }
        }
    }
    $customer_address = implode(', ', array_unique($customer_address_parts));
    if (empty($customer_address)) {
        $customer_address = '-';
    }

    $customer_phone = $customer->mobile
        ?? $customer->landline
        ?? $customer->alternate_number
        ?? '-';
    $customer_tin = $customer->vat_number
        ?? $customer->tax_number
        ?? '-';

    $raw_total_including_vat = (float) ($issue_customer_bill->total_amount ?? 0);
    $raw_vat_amount = (float) ($issue_customer_bill->tax_amount ?? 0);
    $raw_total_value_supply = $raw_total_including_vat - $raw_vat_amount;
    $raw_final_total = $raw_total_including_vat + (float) ($issue_customer_bill->price_adjustment ?? 0);

    $total_value_supply = $formatAmount($raw_total_value_supply);
    $vat_amount = $formatAmount($raw_vat_amount);
    $total_including_vat = $formatAmount($raw_final_total);

    $payment_methods = '-';
    if (isset($payment_details) && $payment_details instanceof \Illuminate\Support\Collection) {
        $methods = $payment_details->pluck('method')->filter()->unique()->values();
        if ($methods->isNotEmpty()) {
            $payment_methods = $methods->implode(', ');
        }
    }

    // Keep the form-like 163 appearance compact enough for a single A4 page.
    // Two additional blank display rows are removed from the approved compact layout (7 -> 5).
    // This keeps the normal VAT Print - 163 invoice on one A4 page without cutting actual bill lines.
    $minimum_item_rows = 5;
    $blank_item_rows = max(0, $minimum_item_rows - $bill_details->count());
@endphp

<style>
    @page {
        size: A4 portrait;
        margin: 8mm;
    }

    html, body {
        margin: 0;
        padding: 0;
        background: #fff;
        color: #111827;
        font-family: Calibri, Arial, sans-serif;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    * {
        box-sizing: border-box;
    }

    .vat163-sheet {
        width: 100%;
        max-width: 194mm;
        margin: 0 auto;
        padding: 0 0.8mm;
        font-size: 11.83px;
        line-height: 1.35;
    }

    .vat163-header {
        text-align: center;
        margin-bottom: 5mm;
    }

    .vat163-business-name {
        font-size: 23.67px;
        line-height: 1.1;
        font-weight: 800;
        letter-spacing: .15px;
        margin: 0 0 2px;
        color: #111;
    }

    .vat163-business-address {
        font-size: 12.33px;
        color: #374151;
        margin: 0 0 4px;
    }

    .vat163-contact-row {
        display: flex;
        justify-content: center;
        gap: 28mm;
        font-size: 11.83px;
        color: #1f2937;
        margin-bottom: 5mm;
    }

    .vat163-contact-row strong {
        color: #111;
    }

    .vat163-title {
        display: inline-block;
        min-width: 54mm;
        padding: 2.2mm 8mm 2mm;
        border-radius: 9mm;
        background: #000000;
        box-shadow: inset 0 0 0 1000px #000000;
        color: #ffffff;
        font-size: 17.33px;
        line-height: 1;
        font-family: Calibri, Arial, sans-serif;
        font-weight: 700;
        letter-spacing: .4px;
    }

    .vat163-grid {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .vat163-two-col {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        table-layout: fixed;
        margin-bottom: 3mm;
    }

    .vat163-two-col > tbody > tr > td {
        width: 50%;
        border: 0;
        vertical-align: top;
        padding: 0;
    }

    .vat163-two-col > tbody > tr > td:first-child {
        padding-right: 2.2mm;
    }

    .vat163-two-col > tbody > tr > td:last-child {
        padding-left: 2.2mm;
    }

    .vat163-box {
        width: 100%;
        border: 1px solid #374151;
        border-radius: 1.2mm;
        background: #fff;
        box-shadow: inset 0 0 0 .15mm rgba(0,0,0,.03);
    }

    .vat163-meta-box {
        min-height: 15mm;
        padding: 4mm 3.5mm;
        font-family: Calibri, Arial, sans-serif;
        font-size: 12.33px;
    }

    .vat163-label {
        font-weight: 700;
        color: #111;
    }

    .vat163-party-box {
        min-height: 42mm;
        padding: 3mm 3.5mm;
    }

    .vat163-party-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-family: Calibri, Arial, sans-serif;
        font-size: 11.83px;
    }

    .vat163-party-table td {
        border: 0;
        padding: 1.2mm 0;
        vertical-align: top;
    }

    .vat163-party-table td:first-child {
        width: 34%;
        font-weight: 700;
        padding-right: 2mm;
        white-space: nowrap;
    }

    .vat163-party-table td:nth-child(2) {
        width: 3%;
        font-weight: 700;
    }

    .vat163-party-table td:last-child {
        width: 63%;
        overflow-wrap: anywhere;
    }

    .vat163-small-box {
        min-height: 10mm;
        padding: 2.4mm 3mm;
        font-family: Calibri, Arial, sans-serif;
        font-size: 11.83px;
    }

    .vat163-additional {
        min-height: 20mm;
        padding: 2.6mm 3mm;
        margin-bottom: 4mm;
        font-family: Calibri, Arial, sans-serif;
        font-size: 11.83px;
    }

    .vat163-additional-value {
        display: block;
        margin-top: 2mm;
        font-family: Calibri, Arial, sans-serif;
        font-size: 11.33px;
        font-weight: 400;
        color: #1f2937;
        white-space: pre-wrap;
    }

    .vat163-items,
    .vat163-totals,
    .vat163-bottom-row {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .vat163-items {
        margin-bottom: 0;
        font-family: Calibri, Arial, sans-serif;
    }

    .vat163-items th,
    .vat163-items td,
    .vat163-totals td,
    .vat163-bottom-row td {
        border: 1px solid #374151;
    }

    /* Keep the far-right rule visible on physical A4 printers/PDF print engines. */
    .vat163-items th:last-child,
    .vat163-items td:last-child,
    .vat163-totals td:last-child,
    .vat163-bottom-row td:last-child {
        border-right: 1.25px solid #111827 !important;
    }

    .vat163-items th {
        height: 13mm;
        padding: 2mm 1.5mm;
        text-align: center;
        vertical-align: middle;
        font-size: 11.83px;
        line-height: 1.12;
        font-weight: 700;
        background: #fbfbfb;
    }

    .vat163-items td {
        height: 8mm;
        padding: 1.3mm 1.6mm;
        vertical-align: middle;
        font-size: 11.33px;
        font-family: Calibri, Arial, sans-serif;
    }

    .vat163-items .num,
    .vat163-totals .amount {
        text-align: right;
        white-space: nowrap;
    }

    .vat163-items .center {
        text-align: center;
    }

    .vat163-totals td {
        height: 9.5mm;
        padding: 1.8mm 2mm;
        font-family: Calibri, Arial, sans-serif;
        font-size: 11.83px;
        font-weight: 700;
    }

    .vat163-totals .label {
        width: 82.4%;
    }

    .vat163-totals .amount {
        width: 17.6%;
        font-family: Calibri, Arial, sans-serif;
        font-weight: 700;
    }

    .vat163-bottom-row {
        margin-top: 3mm;
    }

    .vat163-bottom-row td {
        min-height: 10mm;
        padding: 2.1mm 2mm;
        font-family: Calibri, Arial, sans-serif;
        font-size: 11.83px;
        font-weight: 700;
    }

    .vat163-bottom-value {
        font-family: Calibri, Arial, sans-serif;
        font-weight: 400;
        margin-left: 2mm;
    }

    .vat163-signatures {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        table-layout: fixed;
        margin-top: 18mm;
        page-break-inside: avoid;
    }

    .vat163-signatures td {
        width: 33.333%;
        border: 0;
        text-align: center;
        padding: 0 8mm;
        vertical-align: bottom;
        font-size: 11.83px;
    }

    .vat163-sign-line {
        border-top: 1px dotted #4b5563;
        padding-top: 2.2mm;
        font-weight: 600;
        color: #111827;
    }

    .vat163-items tr,
    .vat163-totals,
    .vat163-party-box,
    .vat163-signatures {
        page-break-inside: avoid;
        break-inside: avoid;
    }

    @media print {
        /*
         * Let the browser use the A4 content box created by @page margins.
         * Do not force the body to 210mm inside that box; that caused the
         * right edge to run into the page margin and could trigger a second
         * printed page. This keeps left/right padding equal and adds a 0.8mm internal safety inset so the far-right border always prints.
         */
        html, body {
            width: 100%;
            min-height: 0;
            margin: 0;
            padding: 0;
        }

        .vat163-sheet {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0 0.8mm;
        }
    }
</style>

<div class="vat163-sheet">
    <div class="vat163-header">
        <div class="vat163-business-name">{{ $receipt_details->business_name ?? $receipt_details->display_name ?? 'Business Name' }}</div>
        <div class="vat163-business-address">{{ $business_address ?: 'Business Address' }}</div>
        <div class="vat163-contact-row">
            <span><strong>Tel:</strong> {{ $business_phone }}</span>
            <span><strong>Email:</strong> {{ $business_email }}</span>
        </div>
        <div class="vat163-title">TAX INVOICE</div>
    </div>

    <table class="vat163-two-col">
        <colgroup><col style="width:50%;"><col style="width:50%;"></colgroup>
        <tr>
            <td>
                <div class="vat163-box vat163-meta-box">
                    <span class="vat163-label">Date of Invoice :</span>
                    {{ $formatDate($issue_customer_bill->date ?? $receipt_details->invoice_date ?? null) }}
                </div>
            </td>
            <td>
                <div class="vat163-box vat163-meta-box">
                    <span class="vat163-label">Tax Invoice No :</span>
                    {{ $issue_customer_bill->customer_bill_no ?? $receipt_details->invoice_no ?? '-' }}
                </div>
            </td>
        </tr>
    </table>

    <table class="vat163-two-col">
        <colgroup><col style="width:50%;"><col style="width:50%;"></colgroup>
        <tr>
            <td>
                <div class="vat163-box vat163-party-box">
                    <table class="vat163-party-table">
                        <tr><td>Supplier's TIN</td><td>:</td><td>{{ $receipt_details->suppliers_tin ?? '-' }}</td></tr>
                        <tr><td>Supplier's Name</td><td>:</td><td>{{ $receipt_details->business_name ?? '-' }}</td></tr>
                        <tr><td>Address</td><td>:</td><td>{{ $business_address ?: '-' }}</td></tr>
                        <tr><td>Telephone No.</td><td>:</td><td>{{ $business_phone }}</td></tr>
                    </table>
                </div>
            </td>
            <td>
                <div class="vat163-box vat163-party-box">
                    <table class="vat163-party-table">
                        <tr><td>Purchaser's TIN</td><td>:</td><td>{{ $customer_tin }}</td></tr>
                        <tr><td>Purchaser's Name</td><td>:</td><td>{{ $receipt_details->customer_name ?? ($customer->name ?? '-') }}</td></tr>
                        <tr><td>Address</td><td>:</td><td>{{ $customer_address }}</td></tr>
                        <tr><td>Telephone No.</td><td>:</td><td>{{ $customer_phone }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="vat163-two-col">
        <colgroup><col style="width:50%;"><col style="width:50%;"></colgroup>
        <tr>
            <td>
                <div class="vat163-box vat163-small-box">
                    <span class="vat163-label">Date of Delivery :</span>
                    {{ $formatDate($issue_customer_bill->supplied_on ?? $receipt_details->delivery_date ?? null) }}
                </div>
            </td>
            <td>
                <div class="vat163-box vat163-small-box">
                    <span class="vat163-label">Place of Supply :</span>
                    {{ $issue_customer_bill->place_of_supply ?? '-' }}
                </div>
            </td>
        </tr>
    </table>

    <div class="vat163-box vat163-additional">
        <span class="vat163-label">Additional Information if any:</span>
        <span class="vat163-additional-value">{{ $issue_customer_bill->additional_information ?? '' }}</span>
    </div>

    <table class="vat163-items">
        <thead>
            <tr>
                @if ($is_service_invoice)
                    <th style="width:14.2%;">Reference</th>
                    <th style="width:68.2%;">Description of Goods or Services</th>
                    <th style="width:17.6%;">Amount<br>Excluding VAT<br>(Rs.)</th>
                @else
                    <th style="width:12.3%;">Reference</th>
                    <th style="width:45.1%;">Description of Goods or Services</th>
                    <th style="width:11%;">Quantity</th>
                    <th style="width:14%;">Unit Price</th>
                    <th style="width:17.6%;">Amount<br>Excluding VAT<br>(Rs.)</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($bill_details as $line)
                @php
                    $line_tax = (float) ($line->tax ?? 0);
                    $line_sub_total = (float) ($line->sub_total ?? 0);
                    $amount_excluding_vat = $line_sub_total - $line_tax;
                @endphp
                <tr>
                    <td class="center">{{ $issue_customer_bill->reference ?? $receipt_details->reference_no ?? '-' }}</td>
                    <td>{{ $line->product_description ?? $line->product_name ?? '-' }}</td>
                    @unless ($is_service_invoice)
                        <td class="num">{{ $formatQty($line->qty ?? 0) }}</td>
                        <td class="num">{{ $formatAmount($line->unit_price_before_tax ?? $line->unit_price ?? 0) }}</td>
                    @endunless
                    <td class="num">{{ $formatAmount($amount_excluding_vat) }}</td>
                </tr>
            @endforeach

            @for ($i = 0; $i < $blank_item_rows; $i++)
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    @unless ($is_service_invoice)
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    @endunless
                    <td>&nbsp;</td>
                </tr>
            @endfor
        </tbody>
    </table>

    <table class="vat163-totals">
        <tr>
            <td class="label">Total Value of Supply</td>
            <td class="amount">{{ $total_value_supply }}</td>
        </tr>
        <tr>
            <td class="label">VAT Amount (Total Value of Supply @ {{ $tax }}%)</td>
            <td class="amount">{{ $vat_amount }}</td>
        </tr>
        <tr>
            <td class="label">Total Amount Including VAT</td>
            <td class="amount">{{ $total_including_vat }}</td>
        </tr>
    </table>

    <table class="vat163-bottom-row">
        <tr>
            <td>
                Total Amount in words
                <span class="vat163-bottom-value">{{ $receipt_details->total_amount_words ?? '-' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                Mode of Payment
                <span class="vat163-bottom-value">{{ $payment_methods }}</span>
            </td>
        </tr>
    </table>

    <table class="vat163-signatures">
        <tr>
            <td><div class="vat163-sign-line">Prepared By</div></td>
            <td><div class="vat163-sign-line">Checked By</div></td>
            <td><div class="vat163-sign-line">Customer Signature</div></td>
        </tr>
    </table>
</div>

<script>
    (function () {
        'use strict';
        var printStarted = false;

        function startPrint() {
            if (printStarted) {
                return;
            }
            printStarted = true;

            var showDialog = function () {
                window.focus();
                window.setTimeout(function () {
                    window.print();
                }, 250);
            };

            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(showDialog, showDialog);
            } else {
                showDialog();
            }
        }

        if (document.readyState === 'complete') {
            startPrint();
        } else {
            window.addEventListener('load', startPrint, { once: true });
        }

        window.addEventListener('afterprint', function () {
            if (window.opener && !window.opener.closed) {
                window.close();
            }
        });
    })();
</script>
