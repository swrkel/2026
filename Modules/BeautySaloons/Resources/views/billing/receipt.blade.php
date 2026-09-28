<div class="bs014-receipt">
    <h3>Beauty Saloons Receipt</h3>
    <p>Bill No: {{ $bill->bill_no ?? '' }}</p>
    <p>Date: {{ $bill->bill_date ?? '' }}</p>
    <p>Total: {{ number_format($bill->net_total ?? 0, 2) }}</p>
    <p>Paid: {{ number_format($bill->paid_amount ?? 0, 2) }}</p>
</div>
