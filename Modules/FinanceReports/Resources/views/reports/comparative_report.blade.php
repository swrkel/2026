@extends('layouts.app')
@section('title', 'Comparative Report - New')
@section('content')
<section class="content-header"><h1>Comparative Report - New <small>Finance Reports</small></h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => route('finance-reports.comparative-report-new')])
@include('financereports::layouts.toolbar')
<div class="alert alert-info">Previous period used for comparison: {{ $report['previous_start'] }} to {{ $report['previous_end'] }}</div>
<div class="box box-solid"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Metric</th><th class="text-right">Current Period</th><th class="text-right">Previous Period</th><th class="text-right">Change</th><th class="text-right">Change %</th></tr></thead><tbody>
@foreach($report['rows'] as $row)<tr><td>{{ $row->name }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->current, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->previous, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->change, 4, '.', '') }}</td><td class="text-right">{{ is_null($row->change_pct) ? '-' : number_format($row->change_pct, 2) . '%' }}</td></tr>@endforeach
</tbody></table></div></div>
</section>
@stop
