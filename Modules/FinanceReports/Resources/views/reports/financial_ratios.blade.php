@extends('layouts.app')
@section('title', 'Financial Ratios - New')
@section('content')
<section class="content-header"><h1>Financial Ratios - New <small>Finance Reports</small></h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => route('finance-reports.financial-ratios-new')])
@include('financereports::layouts.toolbar')
<div class="box box-solid"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Ratio / KPI</th><th class="text-right">Value</th></tr></thead><tbody>
@foreach($report['rows'] as $row)<tr><td>{{ $row->name }}</td><td class="text-right">{{ number_format($row->value, 4, '.', '') }}{{ $row->suffix }}</td></tr>@endforeach
</tbody></table></div></div>
</section>
@stop
