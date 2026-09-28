@php
    // Safe date formatter to avoid Carbon parse errors on '-' or invalid values
    $formatDate = function ($value) {
        if ($value instanceof \Carbon\Carbon) {
            return $value->format('d/m/Y');
        }

        if (empty($value) || $value === '-') {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y');
        } catch (\Exception $e) {
            return '-';
        }
    };

    // Helper to strip any "Rs" prefix so we can add it ourselves
    $stripRs = function ($value) {
        return preg_replace('/Rs\s*/i', '', $value ?? '');
    };

    $tax_rate_obj = \Modules\Distribution\Entities\Core\TaxRate::where('business_id', $invoice->business_id)->first();
    $tax = $tax_rate_obj->amount ?? 0;

    $business_location = \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $invoice->business_id)->where('is_active', 1)->first() 
        ?? \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $invoice->business_id)->first();

    $business_address = '';
    if ($business_location) {
        $address_parts = array_filter([
            $business_location->city,
            $business_location->state,
            $business_location->zip_code,
            $business_location->country,
        ]);
        $business_address = implode(', ', $address_parts);
    }

    // Grand total computations
    $grand_total = (float)$invoice->grand_total;
    $tax_base_value = $grand_total / (1 + ($tax / 100));
    $vat_amount = $grand_total - $tax_base_value;

    // Word conversion helper closure
    $numberToWordsProfessional = function ($amount) {
        $amount = (float) $amount;
        if (empty($amount)) return 'Rupees Zero Only';

        $number = floor($amount);
        $fraction = round(($amount - $number) * 100);

        if ($fraction === 100) {
            $number += 1;
            $fraction = 0;
        }

        $a = [
            '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen',
            'Eighteen', 'Nineteen'
        ];
        $b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $inWords = function ($n) use (&$inWords, $a, $b) {
            if ($n === 0) return 'Zero';
            if ($n < 20) return $a[$n];
            if ($n < 100) {
                return $b[floor($n / 10)] . ($n % 10 ? ' ' . $a[$n % 10] : '');
            }
            if ($n < 1000) {
                return $a[floor($n / 100)] . ' Hundred' . ($n % 100 ? ' ' . $inWords($n % 100) : '');
            }
            if ($n < 1000000) {
                return $inWords(floor($n / 1000)) . ' Thousand' . ($n % 1000 ? ' ' . $inWords($n % 1000) : '');
            }
            if ($n < 1000000000) {
                return $inWords(floor($n / 1000000)) . ' Million' . ($n % 1000000 ? ' ' . $inWords($n % 1000000) : '');
            }
            return 'Number Too Large';
        };

        $words = 'Rupees ' . $inWords($number);

        if ($fraction > 0) {
            $words .= ' and Cents ' . $inWords($fraction);
        }

        $words .= ' Only';

        return $words;
    };

    $total_amount_words = $numberToWordsProfessional($grand_total);

    // Determine Mode of Payment based on paid amounts
    $payments = [];
    if ($invoice->payment_cash > 0) $payments[] = 'Cash';
    if ($invoice->payment_card > 0) $payments[] = 'Card';
    if ($invoice->payment_credit > 0) $payments[] = 'Credit';
    if ($invoice->payment_cheque > 0) $payments[] = 'Cheque';
    $payment_method = empty($payments) ? 'Credit' : implode(', ', $payments);
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VAT Print 2026</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/paper-css/0.3.0/paper.css">
    <style>
        @page {
            size: A4 portrait;
            margin: 5px;
        }

        body {
            margin: 5px;
            padding: 20px 40px;
            font-family: Calibri, sans-serif;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid #000;
            padding: 5px 6px;
            font-size: 13px;
        }

        .no-border {
            border: none !important;
        }

        .header-label {
            font-weight: bold;
            font-size: 13px;
        }

        .header-value {
            font-size: 13px;
        }

        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
        }

        .separator {
            border: 1px dotted black;
        }

        .signature-cell {
            border: none !important;
            text-align: center;
            padding: 0 40px;
        }

        .signature-cell hr {
            margin: 0;
        }

        .signature-label {
            margin-top: 10px;
            font-weight: bold;
            font-size: 13px;
        }

        .amount-label-cell {
            font-weight: bold;
            font-size: 13px;
        }

        .amount-value-cell {
            text-align: right;
            font-size: 13px;
            white-space: nowrap;
        }

        .amount-prefix-cell {
            width: 40px;
            text-align: left;
            font-weight: bold;
        }

        /* Boxes used for header rows (date, tax invoice, delivery, etc.) */
        .header-box {
            border: 1px solid #000;
            padding: 5px 6px;
            font-size: 13px;
        }

        /* Inner tables (supplier / purchaser details) should not show vertical separators */
        .no-inner-border td,
        .no-inner-border th {
            border: none !important;
            padding: 2px 0;
        }

        /* Remove grid lines but keep alignment */
        .no-grid,
        .no-grid th,
        .no-grid td {
            border: none !important;
        }

        .no-grid th,
        .no-grid td {
            padding: 5px 6px;
            font-size: 13px;
        }
    </style>
</head>
<body>
<div class="A4" style="width: 100% !important;">
    {{-- Top title + duplicate indicator --}}
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
        <div style="flex: 1; text-align: center;">
            <div style="display: inline-block; border: 1px solid #000; padding: 4px 30px; font-weight: bold; font-size: 14px;">
                TAX INVOICE
            </div>
        </div>
    </div>

    {{-- Date of invoice and Tax invoice no: two separate boxes, same row, with visible gap --}}
    <table style="margin-bottom: 6px; width: 100%; border-collapse: collapse; border: none;">
        <tr>
            {{-- Create the gap without adding outer left/right spacing --}}
            <td style="border: none; padding: 0 10px 0 0; width: 50%;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="header-box">
                            <span class="header-label">Date of Invoice:</span>
                            <span class="header-value">
                                {{ $formatDate($invoice->date) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="border: none; padding: 0 0 0 10px; width: 50%;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="header-box">
                            <span class="header-label">Tax Invoice No:</span>
                            <span class="header-value">{{ $invoice->invoice_no ?? '-' }}</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Supplier info and Purchaser info: two separate boxes, same row, with visible gap --}}
    <table style="margin-bottom: 6px; width:100%; border-collapse: collapse; border: none;">
        <tr>
            <td style="border: none; padding: 0 10px 0 0; width:50%; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="header-box" style="vertical-align: top;">
                            <table class="no-inner-border" style="width: 100%;">
                                <tr>
                                    <td class="header-label">Supplier's TIN:</td>
                                    <td class="header-value">{{ $business->tax_number_1 ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="header-label">Supplier's Name:</td>
                                    <td class="header-value">
                                        {{ strtoupper($business->name) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="header-label">Address:</td>
                                    <td class="header-value">
                                        {{ $business_address ?: ($business_location->landmark ?? '-') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="header-label">Telephone No:</td>
                                    <td class="header-value">{{ $business->mobile ?? $business_location->mobile ?? '-' }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="border: none; padding: 0 0 0 10px; width:50%; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="header-box" style="vertical-align: top;">
                            <table class="no-inner-border" style="width: 100%;">
                                <tr>
                                    <td class="header-label">Purchaser's TIN:</td>
                                    <td class="header-value">{{ $invoice->customer_vat_no ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="header-label">Purchaser's Name:</td>
                                    <td class="header-value">
                                        {{ strtoupper($invoice->customer->name ?? $invoice->customer_name ?? '-') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="header-label">Address:</td>
                                    <td class="header-value">{{ $invoice->customer_address ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="header-label">Telephone No:</td>
                                    <td class="header-value">
                                        {{ $invoice->customer_contact ?? '-' }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Date of delivery & Place of supply: two separate boxes, same row, with visible gap --}}
    <table style="margin-bottom: 6px; width:100%; border-collapse: collapse; border: none;">
        <tr>
            <td style="border: none; padding: 0 10px 0 0; width:50%;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="header-box">
                            <span class="header-label">Date of Delivery:</span>
                            <span class="header-value">
                                {{ $formatDate($invoice->delivery_date) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="border: none; padding: 0 0 0 10px; width:50%;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="header-box">
                            <span class="header-label">Place of Supply:</span>
                            <span class="header-value">{{ $invoice->place_of_supply ?? '-' }}</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Additional information (single full-width box, no vertical separator) --}}
    <table style="margin-bottom: 10px; width:100%; border-collapse: collapse;">
        <tr>
            <td class="header-box">
                <span class="header-label">Additional Information If:</span>
                <span class="header-value">{{ $invoice->additional_info ?? '-' }}</span>
            </td>
        </tr>
    </table>

    {{-- Items table (no visible grid lines) --}}
    <table class="no-grid" style="margin-bottom: 0;">
        <thead>
            <tr>
                <th class="section-title" style="text-align: center; width: 15%;">REFERENCE</th>
                <th class="section-title" style="text-align: center; width: 35%;">DESCRIPTION OF GOODS SERVICES</th>
                <th class="section-title" style="text-align: center; width: 15%;">QUANTITY</th>
                <th class="section-title" style="text-align: center; width: 15%;">UNIT PRICE</th>
                <th class="section-title" style="text-align: center; width: 20%;">AMOUNT EXCLUDING VAT (RS.)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $line_total_excl_vat = 0;
            @endphp
            @forelse($invoice->lines as $line)
                @php
                    $line_sub_total = (float)$line->final_amount;
                    $amount_excl_vat = $line_sub_total / (1 + ($tax / 100));
                    $line_total_excl_vat += $amount_excl_vat;
                    $unit_price_excl = (float)$line->unit_price / (1 + ($tax / 100));
                @endphp
                <tr>
                    <td style="text-align: center;">
                        {{ $line->product->sku ?? '-' }}
                    </td>
                    <td>
                        {{ $line->product->name ?? '-' }}
                    </td>
                    <td style="text-align: right;">
                        {{ number_format($line->qty, 2) }}
                    </td>
                    <td style="text-align: right;">
                        {{ number_format($unit_price_excl, 2) }}
                    </td>
                    <td style="text-align: right;">
                        {{ number_format($amount_excl_vat, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">&nbsp;</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totals section (no visible grid lines) --}}
    <table class="no-grid" style="margin-bottom: 10px; width:100%; border-collapse: collapse; border-top: none;">
        <tr>
            <td class="amount-label-cell" style="width: 70%;">
                Total Value of Supply
            </td>
            <td class="amount-value-cell" style="width: 30%;">
                Rs {{ number_format($tax_base_value, 2) }}
            </td>
        </tr>
        <tr>
            <td class="amount-label-cell">
                VAT Amount (Total Value Of Supply @ {{ $tax }}%)
            </td>
            <td class="amount-value-cell">
                Rs {{ number_format($vat_amount, 2) }}
            </td>
        </tr>
        <tr>
            <td class="amount-label-cell">
                Total Amount Including VAT
            </td>
            <td class="amount-value-cell">
                Rs {{ number_format($grand_total, 2) }}
            </td>
        </tr>
    </table>

    {{-- Amount in words (single full-width box, no vertical separator) --}}
    <table style="margin-bottom: 10px; width:100%; border-collapse: collapse;">
        <tr>
            <td class="header-box">
                <span class="header-label">Total Amount in Words:</span>
                <span class="header-value">
                    {{ $total_amount_words }}
                </span>
            </td>
        </tr>
    </table>

    {{-- Mode of payment (single full-width box, no vertical separator) --}}
    <table style="margin-bottom: 20px; width:100%; border-collapse: collapse;">
        <tr>
            <td class="header-box">
                <span class="header-label">Mode of Payment:</span>
                <span class="header-value">
                    {{ $payment_method }}
                </span>
            </td>
        </tr>
    </table>

    {{-- Signature lines --}}
    <table style="margin-top: 20px; border: none;">
        <tr>
            <td class="signature-cell">
                <div style="height: 60px;"></div>
                <hr class="separator">
                <div class="signature-label">Prepared By</div>
            </td>
            <td class="signature-cell">
                <div style="height: 60px;"></div>
                <hr class="separator">
                <div class="signature-label">Checked By</div>
            </td>
            <td class="signature-cell">
                <div style="height: 60px;"></div>
                <hr class="separator">
                <div class="signature-label">Customer Signature</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
