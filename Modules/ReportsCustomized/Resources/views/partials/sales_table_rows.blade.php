@foreach($final_sells as $sale)
<tr>
    <td>{{ $loop->iteration }}</td>
    <td>{{ $sale->lioc_display_name ?? $sale->cusname }}</td>
    <td class="text-right">{{ number_format($sale->final_total, 2) }}</td>
    <td>{{ \Carbon\Carbon::parse($sale->transaction_date)->format('d.m.Y') }}</td>
    <td>{{ $sale->lioc_vehicle ?? ($sale->customer_ref ?: $sale->invoice_no) }}</td>
    <td class="lioc-col-description">{{ $sale->lioc_description ?? '' }}</td>
</tr>
@endforeach
