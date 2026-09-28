@extends('layouts.app')
@section('title', $title)
@section('content')
<section class="content-header"><h1>{{ $title }} <small>Finance Reports</small></h1></section>
<section class="content">
<div class="row no-print"><div class="col-sm-12"><div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Filters</h3></div><div class="box-body"><form method="GET" action="{{ request()->url() }}"><div class="row">
<div class="col-md-3"><label>Branch / Location</label><select name="location_id" class="form-control select2" style="width:100%;"><option value="all">Consolidated - All Locations</option>@foreach($locations as $id => $name)<option value="{{ $id }}" {{ (string)$location_id === (string)$id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div>
<div class="col-md-3"><label>Contact</label><select name="contact_id" class="form-control select2" style="width:100%;"><option value="">Please Select</option>@foreach($contacts as $id => $name)<option value="{{ $id }}" {{ (string)$contact_id === (string)$id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div>
<div class="col-md-2"><label>Start Date</label><input type="date" name="start_date" value="{{ $start }}" class="form-control"></div><div class="col-md-2"><label>End Date</label><input type="date" name="end_date" value="{{ $end }}" class="form-control"></div><div class="col-md-2"><label>&nbsp;</label>{{-- data-auto-filter-button: this report has its own filter form rather than
     using financereports::layouts.filter, so it needs the hook naming here too.
     The global auto-filter script matches only "Apply", "Apply Filters" and
     "Filter" by name - narrow on purpose, so it can never auto-click a Save or
     Delete button - and "Generate" is not among them. --}}
                        <button class="btn btn-primary btn-block" type="submit" data-auto-filter-button><i class="fa fa-search"></i> Generate</button></div>
</div></form></div></div></div></div>
@include('financereports::layouts.toolbar')
@if($report)
@if(!empty($report['message']))<div class="alert alert-warning">{{ $report['message'] }}</div>@endif
<div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">{{ optional($report['contact'])->name }}</h3></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Reference</th><th class="text-right">Invoice Total</th><th class="text-right">Paid</th><th class="text-right">Returns / Credits</th><th class="text-right">Movement</th><th class="text-right">Running Balance</th></tr></thead><tbody>
<tr class="bg-gray"><td colspan="6"><strong>Opening Balance</strong></td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['opening_balance'], 4, '.', '') }}</td></tr>
@foreach($report['rows'] as $row)<tr><td>{{ @format_date($row->transaction_date) }}</td><td>{{ $row->reference_no }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->final_total, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->paid_amount, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->return_amount ?? 0, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->balance, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->running_balance, 4, '.', '') }}</td></tr>@endforeach
</tbody><tfoot><tr class="bg-gray"><th colspan="2">Records: {{ $report['totals']['records'] }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['invoiced'], 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['paid'], 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['returns'] ?? 0, 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['balance'], 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['closing_balance'], 4, '.', '') }}</th></tr></tfoot></table>
</div></div>
@else
<div class="alert alert-info">Please select a contact and generate the statement.</div>
@endif
</section>
@stop
