@php
    // Set safe defaults if variables are not passed
    $statement = $statement ?? null;
    $contact = $contact ?? null;
    $business_details = $business_details ?? null;
    $statement_details = $statement_details ?? collect();
@endphp

<style>
    .vat-126-statement-preview {
        font-family: Arial, sans-serif;
        font-size: 14px;
        padding: 30px;
    }

    .vat-126-statement-preview .invoice-info {
        margin-bottom: 30px;
    }

    .vat-126-statement-preview .invoice-info .right {
        float: right;
        text-align: left;
    }

    .vat-126-statement-preview .customer-info {
        margin-top: 100px;
        margin-bottom: 20px;
    }

    .vat-126-statement-preview table.invoice-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
        border: 1px solid #000;
    }

    .vat-126-statement-preview table.invoice-table th,
    .vat-126-statement-preview table.invoice-table td {
        border: 1px solid #000;
        padding: 6px 10px;
        text-align: left;
    }

    .vat-126-statement-preview table.invoice-table th {
        background-color: #f8f8f8;
    }

    .vat-126-statement-preview .totals {
        width: 40%;
        float: right;
        border-collapse: collapse;
    }

    .vat-126-statement-preview .totals td {
        border: 1px solid #000;
        padding: 6px 10px;
        text-align: right;
    }

    .vat-126-statement-preview .signatures {
        clear: both;
        margin-top: 150px;
    }

    .vat-126-statement-preview .signatures table {
        width: 100%;
        text-align: center;
    }

    .vat-126-statement-preview .signatures td {
        padding-top: 40px;
    }

    .vat-126-statement-preview .label {
        font-weight: bold;
    }
</style>

<div class="vat-126-statement-preview">
<section class="content">
    <!-- Invoice Top Right Info -->
    <div class="invoice-info">
        <div class="right">
            <p><strong>VAT No</strong> : {{ optional($business_details)->vat_number ?? '' }}</p>
            <p><strong>Statement #</strong> : {{ optional($statement)->statement_no ?? '' }}</p>
            <p><strong>Statement Date</strong> :
                {{ !empty($statement?->print_date) ? \Carbon\Carbon::parse($statement->print_date)->format('d/m/Y') : '' }}
            </p>

        </div>
    </div>

    <!-- Customer Information -->
    <div class="customer-info">
        <p><strong>Customer Name</strong> : {{ optional($contact)->name ?? '' }}</p>
        <p><strong>Address</strong> : {{ optional($contact)->address ?? '' }}</p>
        <p><strong>VAT No</strong> : {{ optional($contact)->tax_number ?? '' }}</p>
    </div>

    <!-- Item Table -->
    <table class="invoice-table">
        <thead>
            <tr>
                <th style="width: 15%;">Date</th>
                <th style="width: 45%;">Description</th>
                <th style="width: 10%;">QTY</th>
                <th style="width: 15%;">Unit Price</th>
                <th style="width: 15%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($statement_details as $detail)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($detail->tdate)->format('d/m/Y') }}</td>
                    <td>{{ $detail->product->name ?? 'Invoice' }}</td>
                    <td>{{ $detail->qty ?? 1 }}</td>
                    <td>{{ number_format($detail->unit_price ?? 0, 2) }}</td>
                    <td>{{ number_format($detail->invoice_amount ?? 0, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;">No data available</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Totals Section -->
    @php
        $total_without_vat = $statement_details->sum('invoice_amount');
        $vat_amount = $total_without_vat * 0.18;
        $grand_total = $total_without_vat + $vat_amount;
    @endphp

    <table class="totals">
        <tr>
            <td><strong>Total Without VAT</strong></td>
            <td>{{ number_format($total_without_vat, 2) }}</td>
        </tr>
        <tr>
            <td><strong>VAT 18%</strong></td>
            <td>{{ number_format($vat_amount, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Total</strong></td>
            <td>{{ number_format($grand_total, 2) }}</td>
        </tr>
    </table>

    <!-- Signatures -->
    <div class="signatures">
        <table>
            <tr>
                <td><strong>Prepared By</strong></td>
                <td><strong>Checked By</strong></td>
                <td><strong>Customer Signature</strong></td>
            </tr>
        </table>
    </div>
</section>
</div>
