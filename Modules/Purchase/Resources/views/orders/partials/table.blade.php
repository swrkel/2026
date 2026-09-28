<div class="table-responsive">
<table class="table table-bordered table-striped" id="purchase_orders_table">
<thead><tr>
<th>@lang('messages.actions')</th><th>@lang('purchase::lang.date')</th><th>@lang('purchase::lang.ref_no')</th><th>@lang('purchase::lang.supplier')</th><th class="text-right">@lang('purchase::lang.total')</th><th>@lang('purchase::lang.status')</th>
</tr></thead>
<tbody>
@foreach($rows ?? [] as $row)
<tr>
<td><a class="btn btn-xs btn-primary" href="{{ route('purchase.orders.show', $row->id) }}">@lang('messages.view')</a></td>
<td>{{ $row->transaction_date ?? '' }}</td>
<td>{{ $row->ref_no ?? '' }}</td>
<td>{{ $row->supplier_name ?? '' }}</td>
<td class="text-right">{{ number_format($row->final_total ?? 0, 2) }}</td>
<td>{{ $row->status ?? '' }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
