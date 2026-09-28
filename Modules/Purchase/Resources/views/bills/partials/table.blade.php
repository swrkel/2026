<div class="table-responsive">
<table class="table table-bordered table-striped" id="purchase_bills_table">
<thead><tr>
<th>@lang('messages.actions')</th>
<th>@lang('purchase::lang.date')</th>
<th>@lang('purchase::lang.ref_no')</th>
<th>@lang('purchase::lang.supplier')</th>
<th class="text-right">@lang('purchase::lang.total')</th>
<th>@lang('purchase::lang.payment_status')</th>
</tr></thead>
<tbody>
@foreach($rows ?? [] as $row)
<tr>
<td>
<div class="btn-group">
<button class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown">@lang('messages.actions') <span class="caret"></span></button>
<ul class="dropdown-menu">
<li><a href="{{ route('purchase.bills.show', $row->id) }}">@lang('messages.view')</a></li>
<li><a href="{{ route('purchase.bills.edit', $row->id) }}">@lang('messages.edit')</a></li>
</ul>
</div>
</td>
<td>{{ $row->transaction_date ?? '' }}</td>
<td>{{ $row->ref_no ?? '' }}</td>
<td>{{ $row->supplier_name ?? '' }}</td>
<td class="text-right">{{ number_format($row->final_total ?? 0, 2) }}</td>
<td>{{ $row->payment_status ?? '' }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
