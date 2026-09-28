@php
    $pdfLocationName = trim((string) ($location_details->name ?? ($receipt_details->location_name ?? '')));
    $pdfBusinessAddress = trim((string) ($receipt_details->address ?? ''));
    $pdfBusinessMobile = trim((string) ($receipt_details->contact ?? ($location_details->mobile ?? '')));
@endphp
<div class="receipt-card">
    <div class="copy-label">{{ $copyLabel }}</div>
    <h2 class="bill-title">Credit Sale Bill</h2>

    <table class="info-table">
        <tr>
            <td><strong>{{ $business_details->name ?? '' }}</strong></td>
            <td>Bill No: {{ $bill_number ?? '' }}</td>
        </tr>
        @if($pdfLocationName !== '' || $pdfBusinessMobile !== '')
        <tr>
            <td>{{ $pdfLocationName }}</td>
            <td>@if($pdfBusinessMobile !== '') Mobile: {{ $pdfBusinessMobile }} @endif</td>
        </tr>
        @endif
        @if($pdfBusinessAddress !== '')
        <tr>
            <td>{!! nl2br(e($pdfBusinessAddress)) !!}</td>
            <td></td>
        </tr>
        @endif
        <tr>
            <td>Customer: {{ $customer_name ?? ($receipt_details->customer_name ?? '') }}</td>
            <td>Order No: {{ $order_number ?? '' }}</td>
        </tr>
        <tr>
            <td>Vehicle No: {{ $receipt_details->customer_reference ?? '' }}</td>
            <td></td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Sub Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lines as $index => $line)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $line['name'] ?? '' }}
                        @if(!empty($line['variation']))<br><small>{{ $line['variation'] }}</small>@endif
                    </td>
                    <td class="text-right">{{ $line['quantity'] ?? '' }}</td>
                    <td class="text-right">{{ $line['unit_price_inc_tax'] ?? '' }}</td>
                    <td class="text-right">{{ $line['line_total'] ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No credit-sale items were found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals">Total: {{ $receipt_details->total ?? '0.00' }}</div>

    @if($creditBillFooter !== '')
        <div class="receipt-footer">{!! nl2br(e($creditBillFooter)) !!}</div>
    @endif
</div>
