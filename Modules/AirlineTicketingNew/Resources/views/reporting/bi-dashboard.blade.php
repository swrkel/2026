@extends('airlineticketingnew::layouts.app')
@section('atn-title','Business Intelligence')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel"><div class="atn-panel-header"><h4>Year over Year</h4></div>
<div class="atn-panel-body">
<p>Current: {{ number_format((float)$yearOverYear['current'],4) }}</p>
<p>Previous: {{ number_format((float)$yearOverYear['previous'],4) }}</p>
<p>Change: {{ $yearOverYear['change_percent'] === null ? 'N/A' : $yearOverYear['change_percent'].'%' }}</p>
</div></div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>Period</th><th>Sales</th></tr></thead><tbody>
@foreach($monthlySales as $row)<tr><td>{{ $row['period'] }}</td><td class="text-right">{{ number_format((float)$row['value'],4) }}</td></tr>@endforeach
</tbody></table></div></div>
@endsection
