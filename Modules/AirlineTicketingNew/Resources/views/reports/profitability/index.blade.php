@extends('airlineticketingnew::layouts.app')
@section('atn-title','Ticket Profitability Report')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>Ticket</th><th>Date</th><th>Sale</th><th>Supplier Cost</th><th>Commission</th><th>Gross Profit</th><th>Net Profit</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ $record->ticket_no }}</td><td>{{ $record->issue_date }}</td><td class="text-right">{{ number_format((float)$record->sale_amount,4) }}</td><td class="text-right">{{ number_format((float)$record->supplier_cost,4) }}</td><td class="text-right">{{ number_format((float)$record->agent_commission,4) }}</td><td class="text-right">{{ number_format((float)$record->gross_profit,4) }}</td><td class="text-right">{{ number_format((float)$record->net_profit,4) }}</td></tr>@empty<tr><td colspan="7" class="text-center">No records</td></tr>@endforelse
</tbody></table></div><div class="atn-pagination">{{ $records->links() }}</div></div>
@endsection
