@php
$font_size = $receipt_details->font_size;
$h_font_size = $receipt_details->header_font_size;
$f_font_size = $receipt_details->footer_font_size;
$b_font_size = $receipt_details->business_name_font_size;
$i_font_size = $receipt_details->invoice_heading_font_size;
$footer_top_margin = $receipt_details->footer_top_margin;
$admin_invoice_footer = $receipt_details->admin_invoice_footer;
$logo_height = $receipt_details->logo_height;
$logo_width = $receipt_details->logo_width;
$logo_margin_top = $receipt_details->logo_margin_top;
$logo_margin_bottom = $receipt_details->logo_margin_bottom;
$header_align = $receipt_details->header_align;
$contact_details = $receipt_details->contact_details;
$tax = $receipt_details->tax_rate->amount ?? 18;

$report_name = \Modules\Vat\Entities\VatSetting::where('business_id',request()->session()->get('user.business_id'))->where('status',1)->first()->tax_report_name ?? 'vat';

$invoice2_settings = \Modules\Vat\Entities\VatInvoice2Setting::where('business_id',request()->session()->get('user.business_id'))->first()->settings ?? json_encode(array());
$invoice2_settings = (object) json_decode($invoice2_settings);

// Convert amount to words - handle formatted numbers
function numberToWords($number) {
    $ones = array(
        0 => "Zero", 1 => "One", 2 => "Two", 3 => "Three", 4 => "Four", 5 => "Five", 6 => "Six", 7 => "Seven", 8 => "Eight", 9 => "Nine",
        10 => "Ten", 11 => "Eleven", 12 => "Twelve", 13 => "Thirteen", 14 => "Fourteen", 15 => "Fifteen", 16 => "Sixteen", 17 => "Seventeen", 18 => "Eighteen", 19 => "Nineteen"
    );
    $tens = array(
        0 => "Zero", 1 => "Ten", 2 => "Twenty", 3 => "Thirty", 4 => "Forty", 5 => "Fifty", 6 => "Sixty", 7 => "Seventy", 8 => "Eighty", 9 => "Ninety"
    );
    
    $number = (int) $number;
    
    if ($number < 20) {
        return $ones[$number];
    }
    if ($number < 100) {
        return $tens[floor($number / 10)] . (($number % 10 != 0) ? " " . $ones[$number % 10] : "");
    }
    if ($number < 1000) {
        return $ones[floor($number / 100)] . " Hundred" . (($number % 100 != 0) ? " and " . numberToWords($number % 100) : "");
    }
    if ($number < 1000000) {
        return numberToWords(floor($number / 1000)) . " Thousand" . (($number % 1000 != 0) ? " " . numberToWords($number % 1000) : "");
    }
    if ($number < 1000000000) {
        return numberToWords(floor($number / 1000000)) . " Million" . (($number % 1000000 != 0) ? " " . numberToWords($number % 1000000) : "");
    }
    return "Number too large";
}

// Parse final_total - handle both formatted and raw numbers
$final_total_raw = $receipt_details->final_total ?? '0';
$final_total_clean = preg_replace('/[^0-9.]/', '', $final_total_raw);
$final_total_num = floatval($final_total_clean);

if ($final_total_num == 0) {
    $final_total_num = floatval($receipt_details->total_amount ?? 0);
}

$amount_in_words = numberToWords(floor(abs($final_total_num)));
$decimal_part = round((abs($final_total_num) - floor(abs($final_total_num))) * 100);
$decimal_words = $decimal_part > 0 ? " and " . numberToWords($decimal_part) . " Cents" : "";
$full_amount_words = $amount_in_words . $decimal_words . " Only";

// Calculate totals
$total_value_of_supply = 0;
$vat_amount = 0;
foreach($bill_details as $line) {
    $total_value_of_supply += ($line->unit_price_before_tax * $line->qty);
    $vat_amount += $line->tax;
}
$total_including_vat = $total_value_of_supply + $vat_amount;
@endphp

<style>
html, body {
    background-color: #fff !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    font-size: 10pt;
}

@page { 
    size: A4 portrait; 
    margin: 15mm 20mm;
    background-color: #fff;
}

.A4 {
    background-color: #fff !important;
    margin: 0 auto;
    max-width: 210mm;
    padding: 20px;
}

/* Main Title Box */
.invoice-main-title {
    text-align: center;
    margin-bottom: 20px;
}

.invoice-main-title-box {
    border: 1px solid #000;
    padding: 8px 25px;
    display: inline-block;
    font-weight: bold;
    font-size: 12pt;
    letter-spacing: 1px;
}

/* Two Column Layout */
.two-column-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 15px;
}

.info-box {
    border: 1px solid #000;
    padding: 10px 12px;
    font-size: 9pt;
}

.info-box-row {
    margin-bottom: 5px;
    line-height: 1.4;
}

.info-box-label {
    display: inline-block;
    width: 100px;
    font-weight: normal;
}

.info-box-value {
    display: inline;
}

/* Full Width Box */
.full-width-box {
    border: 1px solid #000;
    padding: 10px 12px;
    margin-bottom: 20px;
    font-size: 9pt;
}

/* Table Styles */
.invoice-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    margin-bottom: 20px;
}

.invoice-table thead th {
    border-top: 1px solid #000;
    border-bottom: 1px solid #000;
    padding: 8px 8px;
    text-align: left;
    font-size: 9pt;
    font-weight: bold;
    vertical-align: top;
}

.invoice-table tbody td {
    border: none;
    padding: 4px 8px;
    font-size: 9pt;
    vertical-align: top;
}

.invoice-table tbody tr:last-child td {
    border-bottom: 1px solid #000;
    padding-bottom: 10px;
}

/* Summary Section (Right Aligned) */
.summary-row {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 5px;
    font-size: 9pt;
}

.summary-label {
    width: 200px;
    text-align: left;
    padding-right: 20px;
}

.summary-value {
    width: 120px;
    text-align: right;
}

/* Amount in Words Box */
.amount-words-box {
    border: 1px solid #000;
    padding: 12px 12px;
    margin-top: 15px;
    margin-bottom: 15px;
    font-size: 9pt;
}

.amount-words-label {
    font-weight: bold;
    margin-bottom: 5px;
}

/* Mode of Payment Box */
.payment-mode-box {
    border: 1px solid #000;
    padding: 10px 12px;
    margin-bottom: 40px;
    font-size: 9pt;
}

/* Signature Section */
.signature-section {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
    margin-top: 40px;
    margin-bottom: 50px;
}

.signature-box {
    text-align: center;
}

.signature-line {
    border-top: 3px solid #000;
    margin-bottom: 8px;
    width: 100%;
}

.signature-label {
    font-size: 9pt;
}

/* Footer */
.footer-text {
    text-align: center;
    font-size: 8pt;
    margin-top: 30px;
    padding-top: 15px;
}

.text-left { text-align: left; }
.text-right { text-align: right; }
.text-center { text-align: center; }
</style>

<div class="A4">
    <!-- Main Title -->
    <div class="invoice-main-title">
        <div class="invoice-main-title-box">TAX INVOICE</div>
    </div>

    <!-- Row 1: Date of Invoice & Tax Invoice No -->
    <div class="two-column-row">
        <div class="info-box">
            <div class="info-box-row">
                <span class="info-box-label">Date of Invoice:</span>
                <span class="info-box-value">{{ !empty($receipt_details->invoice_date) && $receipt_details->invoice_date != '11/30/-0001 00:00' ? $receipt_details->invoice_date : date('d/m/Y') }}</span>
            </div>
        </div>
        <div class="info-box">
            <div class="info-box-row">
                <span class="info-box-label">Tax Invoice No:</span>
                <span class="info-box-value">{{ $receipt_details->invoice_no ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    <!-- Row 2: Supplier's Info & Purchaser's Info -->
    <div class="two-column-row">
        <div class="info-box">
            <div class="info-box-row">
                <span class="info-box-label">Supplier's TIN:</span>
                <span class="info-box-value">{{ $receipt_details->tax_info1 ?? 'N/A' }}</span>
            </div>
            <div class="info-box-row">
                <span class="info-box-label">Supplier's Name:</span>
                <span class="info-box-value">{{ strtoupper($receipt_details->display_name ?? 'N/A') }}</span>
            </div>
            <div class="info-box-row">
                <span class="info-box-label">Address:</span>
                <span class="info-box-value">{!! strtoupper($receipt_details->address ?? 'N/A') !!}</span>
            </div>
            <div class="info-box-row">
                <span class="info-box-label">Telephone No:</span>
                <span class="info-box-value">{{ $receipt_details->contact ?? 'N/A' }}</span>
            </div>
        </div>
        <div class="info-box">
            <div class="info-box-row">
                <span class="info-box-label">Purchaser's TIN:</span>
                <span class="info-box-value">{{ $receipt_details->customer->vat_number ?? ($receipt_details->customer_tax_number ?? 'N/A') }}</span>
            </div>
            <div class="info-box-row">
                <span class="info-box-label">Purchaser's Name:</span>
                <span class="info-box-value">{{ strtoupper($receipt_details->customer_name ?? 'N/A') }}</span>
            </div>
            <div class="info-box-row">
                <span class="info-box-label">Address:</span>
                <span class="info-box-value">{{ $receipt_details->customer->address ?? ($receipt_details->contact_details['address'] ?? 'N/A') }}</span>
            </div>
            <div class="info-box-row">
                <span class="info-box-label">Telephone No:</span>
                <span class="info-box-value">{{ $receipt_details->customer->mobile ?? ($receipt_details->customer->phone ?? 'N/A') }}</span>
            </div>
        </div>
    </div>

    <!-- Row 3: Date of Delivery & Place of Supply -->
    <div class="two-column-row">
        <div class="info-box">
            <div class="info-box-row">
                <span class="info-box-label">Date of Delivery:</span>
                <span class="info-box-value">{{ !empty($receipt_details->invoice_date) && $receipt_details->invoice_date != '11/30/-0001 00:00' ? $receipt_details->invoice_date : date('d/m/Y') }}</span>
            </div>
        </div>
        <div class="info-box">
            <div class="info-box-row">
                <span class="info-box-label">Place of Supply:</span>
                <span class="info-box-value">{{ $receipt_details->location->name ?? 'Main Location' }}</span>
            </div>
        </div>
    </div>

    <!-- Additional Information -->
    <div class="full-width-box">
        <span class="info-box-label">Additional Information:</span>
        <span class="info-box-value">{{ $receipt_details->additional_info ?? 'N/A' }}</span>
    </div>

    <!-- Items Table -->
    <table class="invoice-table">
        <thead>
            <tr>
                <th style="width: 12%;">REFERENCE</th>
                <th style="width: 43%;">DESCRIPTION OF GOODS/SERVICES</th>
                <th style="width: 12%;" class="text-right">QUANTITY</th>
                <th style="width: 15%;" class="text-right">UNIT PRICE</th>
                <th style="width: 18%;" class="text-right">AMOUNT EXCLUDING VAT [Rs.]</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bill_details as $line)
            <tr>
                <td>{{ $receipt_details->reference_no ?? 'N/A' }}</td>
                <td>{{ strtoupper($line->product_name) }}</td>
                <td class="text-right">{{ @num_format($line->qty) }}</td>
                <td class="text-right">{{ @num_format($line->unit_price_before_tax) }}</td>
                <td class="text-right">Rs. {{ @num_format($line->unit_price_before_tax * $line->qty) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5">&nbsp;</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Summary (Right Aligned) -->
    <div class="summary-row">
        <div class="summary-label">Total Value of Supply</div>
        <div class="summary-value">Rs. {{ @num_format($total_value_of_supply) }}</div>
    </div>
    <div class="summary-row">
        <div class="summary-label">VAT Amount (Total Value of Supply @ {{ $tax }}%)</div>
        <div class="summary-value">Rs. {{ @num_format($vat_amount) }}</div>
    </div>
    <div class="summary-row">
        <div class="summary-label"><strong>Total Amount Including VAT</strong></div>
        <div class="summary-value"><strong>Rs. {{ @num_format($total_including_vat) }}</strong></div>
    </div>

    <!-- Amount in Words -->
    <div class="amount-words-box">
        <div class="amount-words-label">Total Amount in Words: Rupees {{ $full_amount_words }}</div>
    </div>

    <!-- Mode of Payment -->
    <div class="payment-mode-box">
        <span class="info-box-label">Mode of Payment:</span>
        <span class="info-box-value">
            @if(count($payment_details) > 0)
                @foreach($payment_details as $pmt)
                    {{ $pmt->method }}@if(!$loop->last), @endif
                @endforeach
            @else
                N/A
            @endif
        </span>
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Prepared By</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Checked By</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Customer Signature</div>
        </div>
    </div>

    <!-- Footer -->
    @if(!empty($admin_invoice_footer))
    <div class="footer-text">
        {!! $admin_invoice_footer !!}
    </div>
    @else
    <div class="footer-text">
        Thank You! Come Again. This Software is developed by SYZYGY Technologies. Contact: 077 4055 434 / 071 1616 192
    </div>
    @endif
</div>